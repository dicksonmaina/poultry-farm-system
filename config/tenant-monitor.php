<?php
/**
 * Tenant monitoring service for Poultry Manager Cloud.
 *
 * Responsibilities:
 * - Farm health checks
 * - Subscription status monitoring
 * - Payment failure alerting
 * - Usage telemetry
 * - Anomaly detection
 */

require_once __DIR__ . '/tenant-db.php';

class TenantMonitor
{
    private TenantDb $tenantDb;

    public function __construct()
    {
        $this->tenantDb = new TenantDb();
    }

    public function getFarmHealth(int $farmId): array
    {
        $connection = $this->tenantDb->getTenantConnection($farmId);

        $health = [
            'farm_id' => $farmId,
            'db_ok' => false,
            'subscription_active' => false,
            'last_payment_status' => null,
            'open_support_requests' => 0,
            'recent_activity_count' => 0,
            'alerts' => [],
        ];

        try {
            $connection->exec('USE `' . getenv('DB_NAME') . '`');

            $health['db_ok'] = true;

            $stmt = $connection->prepare('
                SELECT status, plan
                FROM subscriptions
                WHERE farm_id = :farm_id
                ORDER BY started_at DESC
                LIMIT 1
            ');
            $stmt->execute(['farm_id' => $farmId]);
            $subscription = $stmt->fetch();

            if ($subscription) {
                $health['subscription_active'] = $subscription['status'] === 'active';
                $health['plan'] = $subscription['plan'] ?? null;
            }

            $stmt = $connection->prepare('
                SELECT status
                FROM payments
                WHERE farm_id = :farm_id
                ORDER BY paid_at DESC
                LIMIT 1
            ');
            $stmt->execute(['farm_id' => $farmId]);
            $payment = $stmt->fetch();
            $health['last_payment_status'] = $payment['status'] ?? null;

            $stmt = $connection->prepare('
                SELECT COUNT(*) AS open_requests
                FROM support_requests
                WHERE farm_id = :farm_id
                  AND status NOT IN (\'resolved\', \'closed\')
            ');
            $stmt->execute(['farm_id' => $farmId]);
            $support = $stmt->fetch();
            $health['open_support_requests'] = (int) ($support['open_requests'] ?? 0);

            $stmt = $connection->prepare('
                SELECT COUNT(*) AS recent_activity
                FROM audit_log
                WHERE farm_id = :farm_id
                  AND created_at >= NOW() - INTERVAL 7 DAY
            ');
            $stmt->execute(['farm_id' => $farmId]);
            $activity = $stmt->fetch();
            $health['recent_activity_count'] = (int) ($activity['recent_activity'] ?? 0);
        } catch (Throwable $e) {
            $health['alerts'][] = 'db_error';
            $health['db_error'] = $e->getMessage();
        }

        if (!$health['subscription_active']) {
            $health['alerts'][] = 'subscription_inactive';
        }

        if ($health['last_payment_status'] === 'failed' || $health['last_payment_status'] === 'pending') {
            $health['alerts'][] = 'payment_issue';
        }

        if ($health['open_support_requests'] > 3) {
            $health['alerts'][] = 'support_backlog';
        }

        if ($health['recent_activity_count'] === 0) {
            $health['alerts'][] = 'inactive_account';
        }

        return $health;
    }

    public function getAllFarmHealth(): array
    {
        $connection = $this->tenantDb->getSharedConnection();
        $connection->exec('USE `' . getenv('DB_NAME') . '`');

        $stmt = $connection->prepare('
            SELECT farm_id, name, status, tier
            FROM tenants
            WHERE status = \'active\'
        ');
        $stmt->execute();
        $tenants = $stmt->fetchAll();

        $results = [];
        foreach ($tenants as $tenant) {
            $farmId = (int) $tenant['farm_id'];
            $results[] = [
                'farm_id' => $farmId,
                'name' => $tenant['name'],
                'tier' => $tenant['tier'],
                'health' => $this->getFarmHealth($farmId),
            ];
        }

        return $results;
    }
}
