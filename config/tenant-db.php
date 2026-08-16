<?php
/**
 * Tenant-aware database connection for multi-tenant SaaS mode.
 *
 * Usage:
 *   $tenantDb = new TenantDB();
 *   $pdo = $tenantDb->getConnection($farmId);
 *
 * Or via helper:
 *   $pdo = get_tenant_db($farmId);
 */

class TenantDB {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $port;
    private $options;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbname = getenv('DB_NAME') ?: 'poultry_farm_system';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->charset = getenv('DB_CHARSET') ?: 'utf8mb4';
        $this->port = getenv('DB_PORT') ?: 3306;

        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    /**
     * Get a shared connection to the main database.
     * Used for tenant lookup and cross-tenant admin operations.
     */
    public function getMainConnection() {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
        return new PDO($dsn, $this->username, $this->password, $this->options);
    }

    /**
     * Get a connection for a specific tenant.
     * In shared-schema mode, this is the same as main connection;
     * tenant isolation is enforced at the query layer via farm_id.
     */
    public function getConnection($farmId) {
        // Validate farm_id
        if (empty($farmId) || !is_numeric($farmId)) {
            throw new InvalidArgumentException('Invalid farm_id for tenant connection');
        }

        // Verify farm exists and is active
        $main = $this->getMainConnection();
        $stmt = $main->prepare('SELECT farm_id, status FROM tenants WHERE farm_id = ? AND status = ?');
        $stmt->execute([$farmId, 'active']);
        $tenant = $stmt->fetch();

        if (!$tenant) {
            throw new RuntimeException('Tenant not found or inactive: ' . $farmId);
        }

        // Return main connection - all queries must include farm_id filter
        return $main;
    }

    /**
     * Create a new tenant database entry.
     * Call this during farm onboarding.
     */
    public function createTenant($name, $email, $tier = 'free', $settings = []) {
        $main = $this->getMainConnection();
        $stmt = $main->prepare('
            INSERT INTO tenants (name, email, tier, status, settings)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $name,
            $email,
            $tier,
            'active',
            json_encode($settings)
        ]);

        return $main->lastInsertId();
    }

    /**
     * Get tenant by subdomain or farm_id.
     */
    public function getTenant($identifier) {
        $main = $this->getMainConnection();

        if (is_numeric($identifier)) {
            $stmt = $main->prepare('SELECT * FROM tenants WHERE farm_id = ?');
            $stmt->execute([$identifier]);
        } else {
            $stmt = $main->prepare('SELECT * FROM tenants WHERE subdomain = ?');
            $stmt->execute([$identifier]);
        }

        return $stmt->fetch();
    }

    /**
     * Update tenant subscription status.
     */
    public function updateSubscription($farmId, $plan, $status, $nextPaymentDate = null) {
        $main = $this->getMainConnection();
        $stmt = $main->prepare('
            INSERT INTO subscriptions (farm_id, plan, status, next_payment_date)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE plan = ?, status = ?, next_payment_date = ?
        ');
        $stmt->execute([
            $farmId, $plan, $status, $nextPaymentDate,
            $farmId, $plan, $status, $nextPaymentDate
        ]);
    }

    /**
     * Log a payment transaction.
     */
    public function logPayment($farmId, $amount, $method, $reference, $status = 'pending') {
        $main = $this->getMainConnection();
        $stmt = $main->prepare('
            INSERT INTO payments (farm_id, amount, method, reference, status)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $farmId,
            $amount,
            $method,
            $reference,
            $status
        ]);

        return $main->lastInsertId();
    }

    /**
     * Create a support request from any channel.
     */
    public function createSupportRequest($data) {
        $main = $this->getMainConnection();
        $stmt = $main->prepare('
            INSERT INTO support_requests (farm_id, name, email, phone, type, message, source, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['farm_id'] ?? null,
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['type'] ?? 'support',
            $data['message'],
            $data['source'] ?? 'web',
            'open'
        ]);

        return $main->lastInsertId();
    }
}

/**
 * Helper function to get tenant database connection.
 */
function get_tenant_db($farmId) {
    static $tenantDb = null;

    if ($tenantDb === null) {
        $tenantDb = new TenantDB();
    }

    return $tenantDb->getConnection($farmId);
}
