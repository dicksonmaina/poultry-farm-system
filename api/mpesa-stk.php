<?php
/**
 * Initiate M-Pesa STK Push for subscription payments.
 *
 * Expected POST:
 *   - farm_id: int
 *   - phone: string in format 2547XXXXXXXXX
 *   - amount: int in KES
 *   - reference: optional account reference
 *
 * Returns JSON with CheckoutRequestID or error.
 */

require_once __DIR__ . '/../config/tenant-api-bootstrap.php';
require_once __DIR__ . '/../config/mpesa.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$farmId = isset($input['farm_id']) ? (int) $input['farm_id'] : 0;
$phone = isset($input['phone']) ? trim((string) $input['phone']) : '';
$amount = isset($input['amount']) ? (int) $input['amount'] : 0;
$reference = isset($input['reference']) ? trim((string) $input['reference']) : '';

if ($farmId <= 0 || $phone === '' || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'farm_id, phone, and amount are required']);
    exit;
}

$tenantApp = new TenantApiBootstrap();
$connection = $tenantApp->getTenantConnection($farmId);

try {
    $client = mpesa_client();

    $response = $client->push([
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => env_mpesa('MPESA_SHORTCODE'),
        'PhoneNumber' => $phone,
        'AccountReference' => $reference ?: ('Farm-' . $farmId),
        'TransactionDesc' => 'Poultry Farm Subscription',
        'QueueTimeOutURL' => env_mpesa('MPESA_CALLBACK_BASE_URL', '') . '/api/mpesa-webhook.php',
        'ResultURL' => env_mpesa('MPESA_CALLBACK_BASE_URL', '') . '/api/mpesa-webhook.php',
    ]);

    $checkoutRequestId = $response['CheckoutRequestID'] ?? null;
    $merchantRequestId = $response['MerchantRequestID'] ?? null;

    if (!$checkoutRequestId) {
        http_response_code(500);
        echo json_encode([
            'error' => 'STK Push initiation failed',
            'response' => $response,
        ]);
        exit;
    }

    // Persist pending payment for reconciliation
    $stmt = $connection->prepare('
        INSERT INTO payments (farm_id, amount, method, reference, status, metadata)
        VALUES (:farm_id, :amount, :method, :reference, :status, :metadata)
    ');
    $stmt->execute([
        'farm_id' => $farmId,
        'amount' => $amount,
        'method' => 'mpesa',
        'reference' => $checkoutRequestId,
        'status' => 'pending',
        'metadata' => json_encode([
            'checkout_request_id' => $checkoutRequestId,
            'merchant_request_id' => $merchantRequestId,
            'phone' => $phone,
            'reference' => $reference,
        ]),
    ]);

    $paymentId = (int) $connection->lastInsertId();

    $tenantApp->logAudit('mpesa_stk_initiated', null, 'payment', $paymentId, [
        'farm_id' => $farmId,
        'amount' => $amount,
        'checkout_request_id' => $checkoutRequestId,
    ]);

    http_response_code(200);
    echo json_encode([
        'status' => 'pending',
        'payment_id' => $paymentId,
        'checkout_request_id' => $checkoutRequestId,
        'merchant_request_id' => $merchantRequestId,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
    ]);
    exit;
}
