<?php
/**
 * KILO CODE PHASE 1: Database Schema Upgrade
 * Executes all ALTER TABLE and CREATE TABLE statements
 * Password protected with: 39019127
 */

// Security check
if (!isset($_GET['pass']) || $_GET['pass'] !== '39019127') {
    http_response_code(403);
    die("Access Denied: Invalid password");
}

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

$results = [];
$errors = [];

try {
    // ═══ ALTER TABLE: Add new columns to flocks ═══
    
    $statements = [
        // Add feed_stock_kg column
        "ALTER TABLE flocks ADD COLUMN IF NOT EXISTS feed_stock_kg DECIMAL(10,2) DEFAULT 0",
        
        // Add last_weight_check column
        "ALTER TABLE flocks ADD COLUMN IF NOT EXISTS last_weight_check DATE NULL",
        
        // Add health_status column
        "ALTER TABLE flocks ADD COLUMN IF NOT EXISTS health_status ENUM('excellent','good','fair','poor') DEFAULT 'good'",
        
        // Add columns to sales table
        "ALTER TABLE sales ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100) NULL",
        
        "ALTER TABLE sales ADD COLUMN IF NOT EXISTS payment_method ENUM('cash','mpesa','bank','credit') DEFAULT 'cash'",
        
        // ═══ CREATE TABLE: vaccination_schedules ═══
        "CREATE TABLE IF NOT EXISTS vaccination_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT NOT NULL,
            vaccine_name VARCHAR(100),
            administered_date DATE,
            next_due_date DATE,
            status ENUM('pending','done','overdue') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE CASCADE
        )",
        
        // ═══ CREATE TABLE: system_alerts ═══
        "CREATE TABLE IF NOT EXISTS system_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            alert_type ENUM('info','warning','danger','success') DEFAULT 'info',
            title VARCHAR(200),
            message TEXT,
            flock_id INT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            auto_generated BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE SET NULL
        )",
    ];
    
    foreach ($statements as $index => $sql) {
        try {
            $pdo->exec($sql);
            $results[] = [
                'index' => $index + 1,
                'status' => 'success',
                'sql' => substr($sql, 0, 80) . '...'
            ];
        } catch (PDOException $e) {
            $errors[] = [
                'index' => $index + 1,
                'error' => $e->getMessage(),
                'sql' => substr($sql, 0, 80) . '...'
            ];
        }
    }
    
    // ═══ VERIFY SCHEMA ═══
    
    $verification = [];
    
    // Check flocks table columns
    $cols = $pdo->query("DESCRIBE flocks")->fetchAll();
    $column_names = array_column($cols, 'Field');
    $verification['flocks_columns'] = [
        'feed_stock_kg' => in_array('feed_stock_kg', $column_names),
        'last_weight_check' => in_array('last_weight_check', $column_names),
        'health_status' => in_array('health_status', $column_names),
    ];
    
    // Check vaccination_schedules table exists
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $verification['tables_exist'] = [
        'vaccination_schedules' => in_array('vaccination_schedules', $tables),
        'system_alerts' => in_array('system_alerts', $tables),
    ];
    
    // Check sales columns
    $cols = $pdo->query("DESCRIBE sales")->fetchAll();
    $column_names = array_column($cols, 'Field');
    $verification['sales_columns'] = [
        'customer_name' => in_array('customer_name', $column_names),
        'payment_method' => in_array('payment_method', $column_names),
    ];
    
} catch (PDOException $e) {
    $errors[] = [
        'critical' => true,
        'error' => 'Database connection error: ' . $e->getMessage()
    ];
}

echo json_encode([
    'phase' => 'Phase 1: Database Schema Upgrade',
    'timestamp' => date('Y-m-d H:i:s'),
    'statements_executed' => count($results),
    'errors_count' => count($errors),
    'results' => $results,
    'errors' => $errors,
    'verification' => $verification ?? [],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
