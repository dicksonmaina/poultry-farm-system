<?php
/**
 * M-Pesa webhook receiver for subscription payments.
 *
 * Flow:
 * - Receive STK Push callback from Safaricom Daraja
 * - Look up pending payment by checkout_request_id
 * - Update payment status and subscription state
 * - Record audit log
 *
 * This replaces the broken farm_id-from-query-param pattern.
 */

require_once __DIR__ . '/../config/tenant-api-bootstrap.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

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

if (!$checkoutRequestId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing checkout request ID']);
    exit;
}

$tenantApp = new TenantApiBootstrap();

// Look up pending payment by checkout_request_id across all tenants
// In production, add an index on payments.reference for faster lookup
$mainDb = $tenantApp->getMainConnection();
$stmt = $mainDb->prepare('
    SELECT id, farm_id, amount, method, status, metadata
    FROM payments
    WHERE reference = :reference
    ORDER BY id DESC
    LIMIT 1
');
$stmt->execute(['reference' => $checkoutRequestId]);
$payment = $stmt->fetch();

if (!$payment) {
    http_response_code(404);
    echo json_encode(['error' => 'Payment not found', 'checkout_request_id' => $checkoutRequestId]);
    exit;
}

$farmId = (int) $payment['farm_id'];
$connection = $tenantApp->getTenantConnection($farmId);

if ($resultCode !== 0) {
    // Mark payment as failed if still pending
    if ($payment['status'] === 'pending') {
        $update = $connection->prepare('
            UPDATE payments
            SET status = :status, metadata = :metadata
            WHERE id = :id
        ');
        $update->execute([
            'status' => 'failed',
            'metadata' => json_encode([
                'checkout_request_id' => $checkoutRequestId,
                'merchant_request_id' => $merchantRequestId,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
            ]),
            'id' => $payment['id'],
        ]);
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'failed',
        'payment_id' => $payment['id'],
        'farm_id' => $farmId,
        'result_code' => $resultCode,
        'result_desc' => $resultDesc,
    ]);
    exit;
}

if (!$amount) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing amount in callback metadata']);
    exit;
}

// Idempotency: do not double-credit a paid payment
if ($payment['status'] === 'paid') {
    http_response_code(200);
    echo json_encode([
        'status' => 'already_paid',
        'payment_id' => $payment['id'],
        'farm_id' => $farmId,
        'amount' => (float) $payment['amount'],
    ]);
    exit;
}

try {
    $connection->beginTransaction();

    $stmt = $connection->prepare('
        UPDATE payments
        SET status = :status,
            paid_at = NOW(),
            reference = :reference,
            metadata = :metadata
        WHERE id = :id
    ');
    $stmt->execute([
        'status' => 'paid',
        'reference' => $receipt ?: $checkoutRequestId,
        'metadata' => json_encode([
            'checkout_request_id' => $checkoutRequestId,
            'merchant_request_id' => $merchantRequestId,
            'phone' => $phone,
            'receipt' => $receipt,
            'result_desc' => $resultDesc,
        ]),
        'id' => $payment['id'],
    ]);

    $tenantApp->logAudit('payment_received', null, 'payment', (int) $payment['id'], [
        'farm_id' => $farmId,
        'amount' => (float) $amount,
        'receipt' => $receipt,
        'checkout_request_id' => $checkoutRequestId,
    ]);

    $connection->commit();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'payment_id' => (int) $payment['id'],
        'farm_id' => $farmId,
        'amount' => (float) $amount,
    ]);
    exit;
} catch (Throwable $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
    ]);
    exit;
}
