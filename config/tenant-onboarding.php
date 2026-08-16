<?php
/**
 * Tenant onboarding automation for Poultry Manager Cloud.
 *
 * Handles:
 * - Farm provisioning
 * - Subscription seeding
 * - Default settings/bootstrap data
 * - Activation notifications
 * - Telemetry/event logging
 */

require_once __DIR__ . '/tenant-db.php';

class TenantOnboarding
{
    private TenantDb $tenantDb;

    public function __construct()
    {
        $this->tenantDb = new TenantDb();
    }

    public function onboardFarm(array $payload): int
    {
        $farmId = $this->provisionFarm($payload);
        $this->seedSubscription((int) $farmId, $payload['plan'] ?? 'free');
        $this->seedDefaults((int) $farmId);
        $this->logOnboardingEvent((int) $farmId, 'created');

        return (int) $farmId;
    }

    private function provisionFarm(array $payload): string
    {
        $connection = $this->tenantDb->getSharedConnection();
        $connection->exec('USE `' . getenv('DB_NAME') . '`');

        $stmt = $connection->prepare('
            INSERT INTO tenants (name, subdomain, status, tier, settings, created_at, updated_at)
            VALUES (:name, :subdomain, :status, :tier, :settings, NOW(), NOW())
        ');

        $subdomain = $this->normalizeSubdomain($payload['farm_name']);
        $settings = json_encode([
            'owner_name' => $payload['owner_name'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'notify_email' => $payload['email'] ?? null,
            'activation_required' => true,
            'source' => $payload['source'] ?? 'web',
        ]);

        $stmt->execute([
            'name' => $payload['farm_name'],
            'subdomain' => $subdomain,
            'status' => 'active',
            'tier' => $payload['plan'] ?? 'free',
            'settings' => $settings,
        ]);

        $farmId = (int) $connection->lastInsertId();

        // Create tenant database user/schema if needed
        $this->createTenantSchema($farmId, $subdomain);

        return (string) $farmId;
    }

    private function seedSubscription(int $farmId, string $plan): void
    {
        $connection = $this->tenantDb->getSharedConnection();
        $connection->exec('USE `' . getenv('DB_NAME') . '`');

        $stmt = $connection->prepare('
            INSERT INTO subscriptions (farm_id, plan, status, started_at)
            VALUES (:farm_id, :plan, :status, NOW())
        ');

        $stmt->execute([
            'farm_id' => $farmId,
            'plan' => $plan,
            'status' => 'active',
        ]);
    }

    private function seedDefaults(int $farmId): void
    {
        $connection = $this->tenantDb->getSharedConnection();
        $connection->exec('USE `' . getenv('DB_NAME') . '`');

        $defaults = [
            'flock_default_source' => 'hatchery',
            'currency' => 'KES',
            'timezone' => 'Africa/Nairobi',
            'notify_email' => true,
            'notify_telegram' => false,
        ];

        $stmt = $connection->prepare('
            INSERT INTO settings (farm_id, `key`, `value`)
            VALUES (:farm_id, :key, :value)
        ');

        foreach ($defaults as $key => $value) {
            $stmt->execute([
                'farm_id' => $farmId,
                'key' => $key,
                'value' => (string) $value,
            ]);
        }
    }

    private function createTenantSchema(int $farmId, string $subdomain): void
    {
        // In shared-schema mode this is a no-op.
        // If you later split by database per tenant, add schema/database creation here.
    }

    private function normalizeSubdomain(string $farmName): string
    {
        $slug = strtolower(trim($farmName));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'farm-' . bin2hex(random_bytes(3));
        }

        return $slug;
    }

    private function logOnboardingEvent(int $farmId, string $event): void
    {
        $connection = $this->tenantDb->getSharedConnection();
        $connection->exec('USE `' . getenv('DB_NAME') . '`');

        $stmt = $connection->prepare('
            INSERT INTO audit_log (farm_id, action, source, metadata, created_at)
            VALUES (:farm_id, :action, :source, :metadata, NOW())
        ');

        $stmt->execute([
            'farm_id' => $farmId,
            'action' => $event,
            'source' => 'onboarding',
            'metadata' => json_encode(['event' => $event]),
        ]);
    }
}
