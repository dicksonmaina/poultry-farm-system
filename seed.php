<?php
/**
 * Seed Data - Poultry Farm System
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'poultry_farm';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database. Inserting seed data...\n";
    
    // Insert flocks
    $pdo->exec("INSERT INTO flocks (name, breed, date_acquired, initial_count, current_count, status) VALUES 
        ('Kuroiler Batch A', 'Kuroiler', '2026-01-15', 500, 485, 'active'),
        ('Kenbro Layer Flock', 'Kenbro', '2026-02-01', 300, 295, 'active'),
        ('Improved Native', 'Indigenous', '2026-03-01', 200, 198, 'active')");
    echo "Flocks inserted.\n";
    
    // Insert egg production
    $pdo->exec("INSERT INTO egg_production (flock_id, production_date, total_eggs, broken, rejected, medium, large, extra_large) VALUES 
        (1, '2026-04-21', 420, 2, 5, 100, 250, 70),
        (2, '2026-04-21', 285, 1, 3, 80, 180, 25),
        (1, '2026-04-20', 415, 3, 4, 95, 245, 75),
        (2, '2026-04-20', 280, 2, 2, 75, 178, 27)");
    echo "Egg production inserted.\n";
    
    // Insert feed inventory
    $pdo->exec("INSERT INTO feed_inventory (feed_type, quantity_kg, unit_price, supplier, expiry_date, date_received) VALUES 
        ('Layers Mash', 2500, 45, 'Kenchick Ltd', '2026-06-15', '2026-04-01'),
        ('Broiler Starter', 1200, 52, 'Kenchick Ltd', '2026-05-20', '2026-04-10'),
        ('Grower Mash', 800, 48, 'Farm Supplies Co', '2026-06-01', '2026-04-15')");
    echo "Feed inventory inserted.\n";
    
    // Insert mortality
    $pdo->exec("INSERT INTO mortality (flock_id, death_date, cause, quantity, notes) VALUES 
        (1, '2026-04-19', 'Heat stress', 2, 'High temperatures'),
        (1, '2026-04-15', 'Unknown', 1, 'Investigation pending'),
        (2, '2026-04-18', 'Predator', 1, 'Fox attack')");
    echo "Mortality records inserted.\n";
    
    // Insert vaccinations
    $pdo->exec("INSERT INTO vaccinations (flock_id, vaccine_name, date_administered, next_due_date, batch_number, administered_by) VALUES 
        (1, 'Newcastle Disease', '2026-03-15', '2026-04-25', 'ND-2026-001', 'Dr. Ochieng'),
        (1, 'Gumboro', '2026-03-01', '2026-04-22', 'GMB-2026-012', 'Dr. Ochieng'),
        (2, 'Newcastle Disease', '2026-03-20', '2026-04-30', 'ND-2026-002', 'Dr. Ochieng')");
    echo "Vaccinations inserted.\n";
    
    // Insert expenses
    $pdo->exec("INSERT INTO expenses (flock_id, category, description, amount, expense_date) VALUES 
        (1, 'Feed', 'Layers Mash purchase', 45000, '2026-04-01'),
        (2, 'Feed', 'Broiler Starter purchase', 25000, '2026-04-10'),
        (1, 'Vaccines', 'Newcastle Disease vaccines', 8500, '2026-03-15'),
        (2, 'Utilities', 'Water and electricity', 12000, '2026-04-15'),
        (1, 'Labor', 'Farm worker wages', 25000, '2026-04-15')");
    echo "Expenses inserted.\n";
    
    // Insert income
    $pdo->exec("INSERT INTO income (flock_id, source, description, amount, income_date) VALUES 
        (1, 'Egg Sales', 'Daily egg sales', 28000, '2026-04-20'),
        (1, 'Egg Sales', 'Daily egg sales', 28500, '2026-04-21'),
        (2, 'Egg Sales', 'Daily egg sales', 19000, '2026-04-20'),
        (2, 'Egg Sales', 'Daily egg sales', 19200, '2026-04-21'),
        (1, 'Bird Sales', 'Sold 10 culls', 15000, '2026-04-18')");
    echo "Income inserted.\n";
    
    echo "\n=== SEED DATA COMPLETE ===\n";
    echo "Total Birds: 978\n";
    echo "Active Flocks: 3\n";
    echo "Sample data ready for dashboard!\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}