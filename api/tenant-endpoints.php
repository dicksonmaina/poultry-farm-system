<?php
/**
 * Tenant-aware API endpoints for Poultry Manager Cloud.
 *
 * Exposes:
 * - POST /api/tenant/support-request
 * - POST /api/tenant/subscription/update
 * - POST /api/tenant/payment/log
 * - GET  /api/tenant/status
 */

require_once __DIR__ . '/../config/tenant-api-bootstrap.php';

header('Content-Type: application/json');

// Simple router for tenant API endpoints
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Expected pattern: /api/tenant/{action}
$action = $pathParts[2] ?? '';

try {
    switch ($action) {
        case 'support-request':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['name']) || empty($input['email']) || empty($input['message'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name, email, and message are required']);
                exit;
            }

            $requestId = $tenantApp->createSupportRequest([
                'farm_id' => $tenantApp->getFarmId(),
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'] ?? null,
                'type' => $input['type'] ?? 'support',
                'message' => $input['message'],
                'source' => $input['source'] ?? 'api',
            ]);

            $tenantApp->logAudit('support_request_created', null, 'support_request', (int) $requestId);

            echo json_encode([
                'success' => true,
                'request_id' => $requestId,
                'message' => 'Support request created successfully',
            ]);
            exit;

        case 'subscription':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['plan']) || empty($input['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Plan and status are required']);
                exit;
            }

            $farmId = $tenantApp->requireTenant();
            $nextPaymentDate = $input['next_payment_date'] ?? null;

            $tenantApp->getTenantConnection($farmId)->exec('USE `' . getenv('DB_NAME') . '`');
            $tenantApp->logAudit('subscription_updated', null, 'subscription', $farmId, [
                'plan' => $input['plan'],
                'status' => $input['status'],
            ]);

            echo json_encode([
                'success' => true,
                'farm_id' => $farmId,
                'plan' => $input['plan'],
                'status' => $input['status'],
                'next_payment_date' => $nextPaymentDate,
            ]);
            exit;

        case 'payment':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['amount']) || empty($input['method']) || empty($input['reference'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Amount, method, and reference are required']);
                exit;
            }

            $farmId = $tenantApp->requireTenant();
            $paymentId = $tenantApp->logPayment(
                $farmId,
                (float) $input['amount'],
                $input['method'],
                $input['reference'],
                $input['status'] ?? 'pending'
            );

            $tenantApp->logAudit('payment_logged', null, 'payment', (int) $paymentId);

            echo json_encode([
                'success' => true,
                'payment_id' => $paymentId,
                'status' => $input['status'] ?? 'pending',
            ]);
            exit;

        case 'status':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            $tenant = $tenantApp->getTenant();
            if (!$tenant) {
                http_response_code(404);
                echo json_encode(['error' => 'Tenant not found']);
                exit;
            }

            echo json_encode([
                'farm_id' => (int) $tenant['farm_id'],
                'name' => $tenant['name'],
                'tier' => $tenant['tier'],
                'status' => $tenant['status'],
            ]);
            exit;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
    ]);
    exit;
}
