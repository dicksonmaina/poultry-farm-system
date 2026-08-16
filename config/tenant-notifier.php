<?php
/**
 * Tenant notification service for Poultry Manager Cloud.
 *
 * Responsibilities:
 * - Send payment and subscription alerts
 * - Send onboarding/welcome messages
 * - Send support status updates
 * - Send farm health alerts
 */

require_once __DIR__ . '/tenant-db.php';

class TenantNotifier
{
    private TenantDb $tenantDb;

    public function __construct()
    {
        $this->tenantDb = new TenantDb();
    }

    public function sendWelcome(int $farmId): void
    {
        $tenant = $this->getTenant($farmId);
        if (!$tenant) {
            return;
        }

        $email = $this->getSetting($farmId, 'notify_email') ? ($tenant['email'] ?? null) : null;
        $message = 'Welcome to Poultry Manager Cloud. Your farm is now active.';

        if ($email) {
            $this->sendEmail($email, 'Welcome to Poultry Manager Cloud', $message);
        }

        $this->logNotification($farmId, 'welcome', $message);
    }

    public function sendSubscriptionUpdate(int $farmId, string $plan, string $status): void
    {
        $tenant = $this->getTenant($farmId);
        if (!$tenant) {
            return;
        }

        $email = $tenant['email'] ?? null;
        $message = "Your subscription is now {$plan} with status {$status}.";

        if ($email) {
            $this->sendEmail($email, 'Subscription Updated', $message);
        }

        $this->logNotification($farmId, 'subscription_update', $message);
    }

    public function sendPaymentConfirmation(int $farmId, float $amount, string $reference): void
    {
        $tenant = $this->getTenant($farmId);
        if (!$tenant) {
            return;
        }

        $email = $tenant['email'] ?? null;
        $message = "Payment of {$amount} received. Reference: {$reference}.";

        if ($email) {
            $this->sendEmail($email, 'Payment Confirmed', $message);
        }

        $this->logNotification($farmId, 'payment_confirmed', $message);
    }

    public function sendHealthAlerts(int $farmId, array $alerts): void
    {
        if (!$alerts) {
            return;
        }

        $tenant = $this->getTenant($farmId);
        if (!$tenant) {
            return;
        }

        $email = $tenant['email'] ?? null;
        $message = 'Farm health alerts: ' . implode(', ', $alerts);

        if ($email) {
            $this->sendEmail($email, 'Farm Health Alert', $message);
        }

        $this->logNotification($farmId, 'health_alert', $message);
    }

    private function getTenant(int $farmId): ?array
    {
        $connection = $this->tenantDb->getSharedConnection();
        $connection->exec('USE `' . getenv('DB_NAME') . '`');

        $stmt = $connection->prepare('
            SELECT farm_id, name, email, phone, settings
            FROM tenants
            WHERE farm_id = :farm_id
        ');
        $stmt->execute(['farm_id' => $farmId]);

        $tenant = $stmt->fetch();
        return $tenant ?: null;
    }

    private function getSetting(int $farmId, string $key): ?string
    {
        $connection = $this->tenantDb->getSharedConnection();
        $connection->exec('USE `' . getenv('DB_NAME') . '`');

        $stmt = $connection->prepare('
            SELECT value
            FROM settings
            WHERE farm_id = :farm_id
              AND `key` = :key
        ');
        $stmt->execute([
            'farm_id' => $farmId,
            'key' => $key,
        ]);

        $row = $stmt->fetch();
        return $row ? (string) $row['value'] : null;
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        // Integrate with your mailer/SES/SMTP provider here.
    }

    private function logNotification(int $farmId, string $type, string $message): void
    {
        $connection = $this->tenantDb->getSharedConnection();
        $connection->exec('USE `' . getenv('DB_NAME') . '`');

        $stmt = $connection->prepare('
            INSERT INTO audit_log (farm_id, action, source, metadata, created_at)
            VALUES (:farm_id, :action, :source, :metadata, NOW())
        ');

        $stmt->execute([
            'farm_id' => $farmId,
            'action' => $type,
            'source' => 'notifier',
            'metadata' => json_encode(['message' => $message]),
        ]);
    }
}
