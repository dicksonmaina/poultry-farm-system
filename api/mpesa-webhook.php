<?php
/**
 * M-Pesa webhook receiver for subscription payments.
 *
 * Flow:
 * - Receive STK Push callback from Safaricom Daraja
 * - Validate payment metadata
 * - Update tenant subscription state
 * - Record payment and audit log
 */

require_once __DIR__ . '/../config/tenant-api-bootstrap.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

// Minimal validation of Daraja callback structure
$body = $input['Body'] ?? [];
$stkCallback = $body['stkCallback'] ?? [];
$merchantRequestId = $stkCallback['MerchantRequestID'] ?? null;
$checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
$resultCode = $stkCallback['ResultCode'] ?? null;
$resultDesc = $stkCallback['ResultDesc'] ?? null;
$callbackMetadata = $stkCallback['CallbackMetadata'] ?? [];
$items = $callbackMetadata['Item'] ?? [];

$amount = null;
$phone = null;
$receipt = null;

foreach ($items as $item) {
    $name = $item['Name'] ?? '';
    $value = $item['Value'] ?? null;

    if ($name === 'Amount') {
        $amount = $value;
    } elseif ($name === 'PhoneNumber') {
        $phone = $value;
    } elseif ($name === 'MpesaReceiptNumber') {
        $receipt = $value;
    }
}

if ($resultCode !== 0) {
    http_response_code(200);
    echo json_encode([
        'status' => 'failed',
        'result_code' => $resultCode,
        'result_desc' => $resultDesc,
    ]);
    exit;
}

if (!$amount || !$checkoutRequestId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing amount or checkout request ID']);
    exit;
}

// Map checkout request ID back to farm/subscription context
// In production, store checkout_request_id -> farm_id mapping before initiating STK Push
$farmId = $_REQUEST['farm_id'] ?? null;

if (!$farmId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing farm context']);
    exit;
}

$tenantApp = new TenantApiBootstrap();
$connection = $tenantApp->getTenantConnection((int) $farmId);

$connection->exec('USE `' . getenv('DB_NAME') . '`');

try {
    $stmt = $connection->prepare('
        INSERT INTO payments (farm_id, amount, method, reference, status, paid_at, metadata)
        VALUES (:farm_id, :amount, :method, :reference, :status, NOW(), :metadata)
    ');
    $stmt->execute([
        'farm_id' => (int) $farmId,
        'amount' => (float) $amount,
        'method' => 'mpesa',
        'reference' => $receipt ?: $checkoutRequestId,
        'status' => 'paid',
        'metadata' => json_encode([
            'checkout_request_id' => $checkoutRequestId,
            'merchant_request_id' => $merchantRequestId,
            'phone' => $phone,
            'result_desc' => $resultDesc,
        ]),
    ]);

    $paymentId = (int) $connection->lastInsertId();

    $tenantApp->logAudit('payment_received', null, 'payment', $paymentId, [
        'farm_id' => (int) $farmId,
        'amount' => (float) $amount,
        'receipt' => $receipt,
        'checkout_request_id' => $checkoutRequestId,
    ]);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'payment_id' => $paymentId,
        'farm_id' => (int) $farmId,
        'amount' => (float) $amount,
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
    ]);
    exit;
}
