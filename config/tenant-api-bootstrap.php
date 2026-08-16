<?php
/**
 * Tenant-aware API bootstrap for Poultry Manager Cloud.
 *
 * Responsibilities:
 * - Resolve farm_id from subdomain, JWT, or session
 * - Enforce tenant isolation on every request
 * - Provide shared helpers for support, subscriptions, and audit logging
 */

require_once __DIR__ . '/tenant-db.php';

class TenantApiBootstrap {
    private TenantDB $tenantDb;
    private ?array $tenant = null;
    private ?int $farmId = null;

    public function __construct() {
        $this->tenantDb = new TenantDB();
        $this->resolveTenant();
    }

    private function resolveTenant(): void {
        // 1. Subdomain-based resolution for hosted SaaS
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!empty($host) && str_contains($host, '.')) {
            $subdomain = explode('.', $host)[0];
            if ($subdomain && $subdomain !== 'www' && $subdomain !== 'app') {
                $this->tenant = $this->tenantDb->getTenant($subdomain);
                if ($this->tenant) {
                    $this->farmId = (int) $this->tenant['farm_id'];
                    return;
                }
            }
        }

        // 2. JWT-based resolution
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            $payload = $this->decodeJwt($token);
            if (!empty($payload['farm_id'])) {
                $this->farmId = (int) $payload['farm_id'];
                $this->tenant = $this->tenantDb->getTenant($this->farmId);
                return;
            }
        }

        // 3. Session-based resolution for web UI
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['farm_id'])) {
            $this->farmId = (int) $_SESSION['farm_id'];
            $this->tenant = $this->tenantDb->getTenant($this->farmId);
            return;
        }

        // No tenant resolved
        $this->farmId = null;
        $this->tenant = null;
    }

    public function getFarmId(): ?int {
        return $this->farmId;
    }

    public function getTenant(): ?array {
        return $this->tenant;
    }

    public function requireTenant(): int {
        if ($this->farmId === null || $this->tenant === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Tenant context required']);
            exit;
        }

        if ($this->tenant['status'] !== 'active') {
            http_response_code(403);
            echo json_encode(['error' => 'Tenant inactive or suspended']);
            exit;
        }

        return $this->farmId;
    }

    public function getMainConnection(): PDO {
        return $this->tenantDb->getMainConnection();
    }

    public function getTenantConnection(int $farmId): PDO {
        return $this->tenantDb->getConnection($farmId);
    }

    public function createSupportRequest(array $data): int {
        $data['farm_id'] = $data['farm_id'] ?? $this->farmId;
        $data['source'] = $data['source'] ?? 'api';

        return $this->tenantDb->createSupportRequest($data);
    }

    public function logAudit(string $action, ?int $userId = null, ?string $entityType = null, ?int $entityId = null, array $changes = []): void {
        $main = $this->tenantDb->getMainConnection();
        $stmt = $main->prepare('
            INSERT INTO audit_log (farm_id, user_id, action, entity_type, entity_id, changes, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $this->farmId,
            $userId,
            $action,
            $entityType,
            $entityId,
            json_encode($changes),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    private function decodeJwt(string $token): ?array {
        // Lightweight JWT decode for farm_id claim only.
        // For production, validate signature with the tenant's public key.
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        return is_array($payload) ? $payload : null;
    }

    private function base64UrlDecode(string $input): string {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }
}

// Bootstrap the tenant context for API requests
$tenantApp = new TenantApiBootstrap();
$farmId = $tenantApp->getFarmId();
