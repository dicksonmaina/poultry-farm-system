<?php
// Returns all dashboard data as JSON
// Called by dashboard.php via AJAX

require_once '../config.php';
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$data = [];

try {
    $pdo = getDB();

    // Total live birds
    $result = $pdo->query("SELECT SUM(current_count) as total FROM flocks WHERE status='active'");
    $row = $result->fetch();
    $data['total_birds'] = (int)($row['total'] ?? 0);

    // Eggs today
    $result = $pdo->query("SELECT SUM(total_eggs) as today FROM egg_production WHERE DATE(production_date)=CURDATE()");
    $row = $result->fetch();
    $data['eggs_today'] = (int)($row['today'] ?? 0);

    // Revenue month-to-date (assuming income table for revenue, but we have sales for revenue? Let's use income table if exists, else sales)
    // We have both income and sales tables. The original dashboard used income for revenue and expenses for expenses.
    // We'll follow the original: revenue from income, expenses from expenses.
    $result = $pdo->query("SELECT SUM(amount) as mtd FROM income WHERE MONTH(income_date)=MONTH(NOW()) AND YEAR(income_date)=YEAR(NOW())");
    $row = $result->fetch();
    $data['revenue_mtd'] = (float)($row['mtd'] ?? 0);

    // Active flocks
    $result = $pdo->query("SELECT COUNT(*) as count FROM flocks WHERE status='active'");
    $row = $result->fetch();
    $data['active_flocks'] = (int)($row['count'] ?? 0);

    // Mortality rate (this week)
    $result = $pdo->query("
        SELECT 
            ROUND((SUM(m.quantity) / NULLIF(SUM(f.initial_count),0)) * 100, 2) as rate
        FROM mortality m
        JOIN flocks f ON m.flock_id = f.id
        WHERE m.death_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $row = $result->fetch();
    $data['mortality_rate'] = (float)($row['rate'] ?? 0);

    // FCR (Feed Conversion Ratio)
    $result = $pdo->query("
        SELECT ROUND(SUM(quantity_kg) / NULLIF(SUM(total_eggs),1), 2) as fcr
        FROM (
            SELECT fi.flock_id, fi.quantity_kg, ep.total_eggs
            FROM feed_inventory fi
            JOIN egg_production ep ON fi.flock_id = ep.flock_id 
                AND DATE(fi.date_recorded) = DATE(ep.production_date)
            WHERE fi.date_recorded >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) fcr_data
    ");
    $row = $result->fetch();
    $data['fcr'] = (float)($row['fcr'] ?? 0);

    // Egg history (30 days)
    $result = $pdo->query("
        SELECT DATE(production_date) as date, SUM(total_eggs) as eggs
        FROM egg_production 
        WHERE production_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(production_date)
        ORDER BY date ASC
    ");
    $egg_history = $result->fetchAll(PDO::FETCH_ASSOC);
    $data['egg_labels'] = array_column($egg_history, 'date');
    $data['egg_data'] = array_map('intval', array_column($egg_history, 'eggs'));

    // Finance 6 months (income vs expenses)
    $result = $pdo->query("
        SELECT 
            DATE_FORMAT(month_date, '%b %Y') as month,
            IFNULL(SUM(revenue), 0) as revenue,
            IFNULL(SUM(expenses), 0) as expenses
        FROM (
            SELECT DATE_FORMAT(income_date, '%Y-%m-01') as month_date,
                   SUM(amount) as revenue, 0 as expenses
            FROM income WHERE income_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month_date
        ) r
        LEFT JOIN (
            SELECT DATE_FORMAT(expense_date, '%Y-%m-01') as month_date,
                   0 as revenue, SUM(amount) as expenses
            FROM expenses WHERE expense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month_date
        ) e ON r.month_date = e.month_date
        GROUP BY month_date
        ORDER BY month_date ASC
    ");
    $data['finance'] = $result->fetchAll(PDO::FETCH_ASSOC);

    // Smart Alerts
    $alerts = [];

    // Low feed alert
    $low_feed = $pdo->query("
        SELECT f.name as flock_name, f.feed_stock_kg 
        FROM flocks f 
        WHERE f.status='active' AND f.feed_stock_kg < 50
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($low_feed as $f) {
        $alerts[] = ['type'=>'warning','icon'=>'🌾','message'=>"Low feed: {$f['flock_name']} has {$f['feed_stock_kg']}kg remaining"];
    }

    // High mortality alert
    $high_mortality = $pdo->query("
        SELECT f.name as flock_name, SUM(m.quantity) as dead
        FROM mortality m JOIN flocks f ON m.flock_id=f.id
        WHERE m.death_date >= DATE_SUB(NOW(), INTERVAL 3 DAY)
        GROUP BY f.id HAVING dead > 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($high_mortality as $m) {
        $alerts[] = ['type'=>'danger','icon'=>'⚠️','message'=>"High mortality: {$m['flock_name']} — {$m['dead']} deaths in 3 days"];
    }

    // Vaccination due
    $vax_due = $pdo->query("
        SELECT f.name as flock_name, v.vaccine_name, v.next_due_date
        FROM vaccination_schedules v JOIN flocks f ON v.flock_id=f.id
        WHERE v.next_due_date <= DATE_ADD(NOW(), INTERVAL 3 DAY) AND v.status='pending'
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($vax_due as $v) {
        $alerts[] = ['type'=>'info','icon'=>'💉','message'=>"Vaccination due: {$v['flock_name']} — {$v['vaccine_name']} on {$v['next_due_date']}"];
    }

    $data['alerts'] = $alerts;
    $data['alert_count'] = count($alerts);

    // Farm health score (0-100)
    $score = 100;
    $score -= count($low_feed) * 15;
    $score -= count($high_mortality) * 20;
    $score -= count($vax_due) * 10;
    $data['health_score'] = max(0, min(100, $score));

    // Recent activity (last 10 events)
    $result = $pdo->query("
        (SELECT 'egg' as type, CONCAT('Eggs collected: ', total_eggs) as desc, production_date as ts FROM egg_production ORDER BY production_date DESC LIMIT 3)
        UNION
        (SELECT 'feed' as type, CONCAT('Feed logged: ', quantity_kg, 'kg') as desc, date_recorded as ts FROM feed_inventory ORDER BY date_recorded DESC LIMIT 3)
        UNION
        (SELECT 'sale' as type, CONCAT('Sale: KES ', amount) as desc, income_date as ts FROM income ORDER BY income_date DESC LIMIT 4)
        ORDER BY ts DESC LIMIT 10
    ");
    $data['activity'] = $result->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>