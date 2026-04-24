<?php
/**
 * Dashboard Page - Poultry Farm System
 */

$stats = [];

// Total Birds
$stmt = getDB()->query("SELECT COALESCE(SUM(current_count), 0) as total FROM flocks WHERE status = 'active'");
$stats['total_birds'] = $stmt->fetch()['total'] ?? 0;

// Active Flocks Count
$stmt = getDB()->query("SELECT COUNT(*) as cnt FROM flocks WHERE status = 'active'");
$stats['active_flocks'] = $stmt->fetch()['cnt'] ?? 0;

// Today's Egg Production
$stmt = getDB()->query("SELECT COALESCE(SUM(total_eggs), 0) as total FROM egg_production WHERE production_date = CURDATE()");
$stats['today_eggs'] = $stmt->fetch()['total'] ?? 0;

// Mortality Today
$stmt = getDB()->query("SELECT COALESCE(SUM(quantity), 0) as total FROM mortality WHERE death_date = CURDATE()");
$stats['mortality_today'] = $stmt->fetch()['total'] ?? 0;

// Month Revenue (from income table)
$stmt = getDB()->query("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE MONTH(income_date) = MONTH(CURDATE()) AND YEAR(income_date) = YEAR(CURDATE())");
$stats['month_revenue'] = $stmt->fetch()['total'] ?? 0;

// Month Expenses
$stmt = getDB()->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
$stats['month_expenses'] = $stmt->fetch()['total'] ?? 0;

// Feed Stock
$stmt = getDB()->query("SELECT COALESCE(SUM(quantity_kg), 0) as total FROM feed_inventory");
$stats['feed_stock'] = $stmt->fetch()['total'] ?? 0;

// Active Flocks
$stmt = getDB()->query("SELECT * FROM flocks WHERE status = 'active' ORDER BY date_acquired DESC");
$flocks = $stmt->fetchAll();

// Vaccination Alerts (due within 7 days)
$stmt = getDB()->query("SELECT v.*, f.name as flock_name FROM vaccinations v JOIN flocks f ON v.flock_id = f.id WHERE v.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND v.next_due_date >= CURDATE()");
$vaccine_alerts = $stmt->fetchAll();

// Low Feed Alerts (< 50kg)
$low_feed_alerts = $stats['feed_stock'] < 50 ? true : false;

// Format KES
function kes($amount) {
    return 'KES ' . number_format($amount, 0);
}
?>
<style>
    :root {
        --bg-dark: #0a0f0d;
        --card-bg: #162019;
        --green-accent: #3ddc6e;
    }
    body {
        background-color: var(--bg-dark);
    }
    .card {
        background-color: var(--card-bg);
    }
    .accent {
        color: var(--green-accent);
    }
    .accent-bg {
        background-color: var(--green-accent);
    }
</style>

<div class="mb-6">
    <h1 class="text-2xl font-bold" style="color: var(--green-accent)">Dashboard</h1>
    <p class="text-gray-400">Overview of your poultry farm</p>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4 rounded-lg" style="background-color: #162019">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: rgba(61,220,110,0.15)">
                <i class="fas fa-crow text-xl" style="color: var(--green-accent)"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Total Birds</p>
                <p class="text-2xl font-bold" style="color: var(--green-accent)"><?= number_format($stats['total_birds']) ?></p>
            </div>
        </div>
    </div>
    
    <div class="card p-4 rounded-lg" style="background-color: #162019">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: rgba(251,191,36,0.15)">
                <i class="fas fa-egg text-xl" style="color: #fbbf24"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Eggs Today</p>
                <p class="text-2xl font-bold text-white"><?= number_format($stats['today_eggs']) ?></p>
            </div>
        </div>
    </div>
    
    <div class="card p-4 rounded-lg" style="background-color: #162019">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: rgba(239,68,68,0.15)">
                <i class="fas fa-skull text-xl" style="color: #ef4444"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Mortality Today</p>
                <p class="text-2xl font-bold <?= $stats['mortality_today'] > 0 ? 'text-red-500' : 'text-white' ?>">
                    <?= $stats['mortality_today'] ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="card p-4 rounded-lg" style="background-color: #162019">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: rgba(96,165,250,0.15)">
                <i class="fas fa-money-bill-wave text-xl" style="color: #60a5fa"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-400 text-sm">Revenue (Month)</p>
                <p class="text-2xl font-bold text-white"><?= kes($stats['month_revenue']) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Alerts Section -->
<?php if (!empty($vaccine_alerts) || $low_feed_alerts): ?>
<div class="mb-6">
    <h2 class="text-lg font-bold mb-3 text-white">Alerts</h2>
    <div class="space-y-2">
        <?php foreach ($vaccine_alerts as $alert): ?>
        <div class="p-3 rounded-lg bg-yellow-900/30 border border-yellow-600/50 flex items-center">
            <i class="fas fa-syringe text-yellow-500 mr-3"></i>
            <span class="text-yellow-200">Vaccination due for <?= htmlspecialchars($alert['flock_name']) ?>: <?= htmlspecialchars($alert['vaccine_name']) ?> (Due: <?= date('M d', strtotime($alert['next_due_date'])) ?>)</span>
        </div>
        <?php endforeach; ?>
        <?php if ($low_feed_alerts): ?>
        <div class="p-3 rounded-lg bg-red-900/30 border border-red-600/50 flex items-center">
            <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
            <span class="text-red-200">Low feed stock: <?= number_format($stats['feed_stock']) ?> kg - Order soon!</span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Active Flocks Table -->
<div class="card rounded-lg overflow-hidden" style="background-color: #162019">
    <div class="p-4 border-b border-gray-700">
        <h2 class="text-lg font-bold text-white">Active Flocks</h2>
    </div>
    <table class="min-w-full">
        <thead style="background-color: #1a2a1f">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Breed</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Initial</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Current</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($flocks as $flock): ?>
            <tr>
                <td class="px-4 py-3 text-white"><?= htmlspecialchars($flock['name']) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($flock['breed'] ?? '-') ?></td>
                <td class="px-4 py-3 text-gray-400"><?= date('M d, Y', strtotime($flock['date_acquired'])) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= number_format($flock['initial_count']) ?></td>
                <td class="px-4 py-3 font-bold text-white"><?= number_format($flock['current_count']) ?></td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-full" style="background-color: rgba(61,220,110,0.2); color: var(--green-accent)">
                        <?= ucfirst($flock['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($flocks)): ?>
            <tr>
                <td colspan="6" class="px-4 py-4 text-center text-gray-500">No active flocks. Add your first flock!</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Quick Stats Footer -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
    <div class="card p-4 rounded-lg" style="background-color: #162019">
        <h3 class="text-sm font-bold text-gray-400 mb-3">Monthly Summary</h3>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-400">Expenses</span>
                <span class="text-red-400"><?= kes($stats['month_expenses']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Income</span>
                <span class="text-green-400"><?= kes($stats['month_revenue']) ?></span>
            </div>
            <div class="flex justify-between border-t border-gray-700 pt-2">
                <span class="text-white font-bold">Net Profit</span>
                <span class="font-bold" style="color: var(--green-accent)"><?= kes($stats['month_revenue'] - $stats['month_expenses']) ?></span>
            </div>
        </div>
    </div>
    
    <div class="card p-4 rounded-lg" style="background-color: #162019">
        <h3 class="text-sm font-bold text-gray-400 mb-3">Feed Stock</h3>
        <div class="flex items-center justify-between">
            <span class="text-white text-2xl font-bold"><?= number_format($stats['feed_stock']) ?> <span class="text-sm text-gray-400">kg</span></span>
            <?php if ($stats['feed_stock'] < 50): ?>
            <span class="text-red-400 text-sm"><i class="fas fa-exclamation-circle"></i> Low Stock</span>
            <?php else: ?>
            <span class="text-green-400 text-sm"><i class="fas fa-check-circle"></i> Adequate</span>
            <?php endif; ?>
        </div>
    </div>
</div>