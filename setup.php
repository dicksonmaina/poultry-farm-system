<?php
/**
 * Database Setup - Poultry Farm System
 * Creates all 14 tables for 8 modules
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'poultry_farm';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$dbname' created/verified.\n";
    
    $pdo->exec("USE $dbname");
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($existingTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $tables = [
        // 1. FLOCKS MANAGEMENT
        "CREATE TABLE IF NOT EXISTS flocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            breed VARCHAR(50),
            date_acquired DATE NOT NULL,
            initial_count INT NOT NULL,
            current_count INT DEFAULT 0,
            status ENUM('active', 'sold', 'deceased') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // 2. BIRDS (Individual Bird Records)
        "CREATE TABLE IF NOT EXISTS birds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT NOT NULL,
            ring_number VARCHAR(20) UNIQUE,
            gender ENUM('male', 'female') NOT NULL,
            hatch_date DATE,
            weight DECIMAL(5,2),
            status ENUM('alive', 'sold', 'deceased', 'slaughtered') DEFAULT 'alive',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE CASCADE
        )",
        
        // 3. EGG PRODUCTION
        "CREATE TABLE IF NOT EXISTS egg_production (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT NOT NULL,
            production_date DATE NOT NULL,
            total_eggs INT DEFAULT 0,
            broken INT DEFAULT 0,
            rejected INT DEFAULT 0,
            medium INT DEFAULT 0,
            large INT DEFAULT 0,
            extra_large INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE CASCADE
        )",
        
        // 4. FEED INVENTORY
        "CREATE TABLE IF NOT EXISTS feed_inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            feed_type VARCHAR(50) NOT NULL,
            quantity_kg DECIMAL(10,2) DEFAULT 0,
            unit_price DECIMAL(10,2),
            supplier VARCHAR(100),
            expiry_date DATE,
            date_received DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // 5. FEED CONSUMPTION
        "CREATE TABLE IF NOT EXISTS feed_consumption (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT NOT NULL,
            feed_inventory_id INT,
            consumption_date DATE NOT NULL,
            quantity_kg DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE CASCADE,
            FOREIGN KEY (feed_inventory_id) REFERENCES feed_inventory(id) ON DELETE SET NULL
        )",
        
        // 6. VACCINATIONS
        "CREATE TABLE IF NOT EXISTS vaccinations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT NOT NULL,
            vaccine_name VARCHAR(100) NOT NULL,
            date_administered DATE NOT NULL,
            next_due_date DATE,
            batch_number VARCHAR(50),
            administered_by VARCHAR(100),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE CASCADE
        )",
        
        // 7. MORTALITY RECORDS
        "CREATE TABLE IF NOT EXISTS mortality (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT NOT NULL,
            bird_id INT,
            death_date DATE NOT NULL,
            cause VARCHAR(100),
            quantity INT DEFAULT 1,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE CASCADE,
            FOREIGN KEY (bird_id) REFERENCES birds(id) ON DELETE SET NULL
        )",
        
        // 8. WEIGHT RECORDS
        "CREATE TABLE IF NOT EXISTS weight_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT NOT NULL,
            record_date DATE NOT NULL,
            average_weight DECIMAL(6,2),
            sample_size INT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE CASCADE
        )",
        
        // 9. EXPENSES
        "CREATE TABLE IF NOT EXISTS expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT,
            category VARCHAR(50) NOT NULL,
            description TEXT,
            amount DECIMAL(10,2) NOT NULL,
            expense_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE SET NULL
        )",
        
        // 10. INCOME
        "CREATE TABLE IF NOT EXISTS income (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT,
            source VARCHAR(50) NOT NULL,
            description TEXT,
            amount DECIMAL(10,2) NOT NULL,
            income_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE SET NULL
        )",
        
        // 11. SALES
        "CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flock_id INT,
            bird_id INT,
            product_type ENUM('bird', 'egg') NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            buyer_name VARCHAR(100),
            sale_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE SET NULL,
            FOREIGN KEY (bird_id) REFERENCES birds(id) ON DELETE SET NULL
        )",
        
        // 12. USERS (Staff/Admins)
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
            email VARCHAR(100),
            phone VARCHAR(20),
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // 13. ACTIVITY LOG
        "CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        // 14. SETTINGS
        "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            description VARCHAR(255),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    
    echo "All 14 tables created successfully.\n";
    
    // Insert default admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, full_name, role) VALUES ('admin', '$hash', 'Farm Administrator', 'admin')");
        echo "Default admin user created (admin/admin123)\n";
    }
    
    echo "\n=== SETUP COMPLETE ===\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}