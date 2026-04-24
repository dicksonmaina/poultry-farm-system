<?php
/**
 * Reports Page - Enhanced with date filter, production/f financials, export
 */

// Handle date filter
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$days = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;

// Get data for date range
$flocks = getDB()->query("SELECT * FROM flocks WHERE status = 'active'")->fetchAll();
$totalBirds = getDB()->query("SELECT COALESCE(SUM(current_count), 0) as total FROM flocks WHERE status = 'active'")->fetch();

// Egg production summary
$eggSummary = getDB()->prepare("SELECT 
    COALESCE(SUM(total_eggs), 0) as total,
    COALESCE(SUM(broken + rejected), 0) as losses
    FROM egg_production WHERE production_date BETWEEN ? AND ?");
$eggSummary->execute([$startDate, $endDate]);
$eggData = $eggSummary->fetch();

// Feed consumption summary  
$feedSummary = getDB()->prepare("SELECT COALESCE(SUM(quantity_kg), 0) as total FROM feed_inventory");
$feedSummary->execute();
$feedStock = $feedSummary->fetch();

// Mortality summary
$mortSummary = getDB()->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM mortality WHERE death_date BETWEEN ? AND ?");
$mortSummary->execute([$startDate, $endDate]);
$mortData = $mortSummary->fetch();

// Financial summary
$expSummary = getDB()->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE expense_date BETWEEN ? AND ?");
$expSummary->execute([$startDate, $endDate]);
$expData = $expSummary->fetch();

$incSummary = getDB()->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE income_date BETWEEN ? AND ?");
$incSummary->execute([$startDate, $endDate]);
$incData = $incSummary->fetch();

$netProfit = $incData['total'] - $expData['total'];

// Weekly egg data
$weeklyEggs = getDB()->prepare("SELECT production_date, SUM(total_eggs) as total FROM egg_production WHERE production_date BETWEEN ? AND ? GROUP BY production_date ORDER BY production_date");
$weeklyEggs->execute([$startDate, $endDate]);
$weeklyEggData = $weeklyEggs->fetchAll();

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="poultry_report_' . $startDate . '_' . $endDate . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Poultry Farm Report', $startDate . ' to ' . $endDate]);
    fputcsv($output, []);
    fputcsv($output, ['Production Summary']);
    fputcsv($output, ['Total Birds', $totalBirds['total']]);
    fputcsv($output, ['Eggs Produced', $eggData['total']]);
    fputcsv($output, ['Egg Losses', $eggData['losses']]);
    fputcsv($output, ['Mortality', $mortData['total']]);
    fputcsv($output, ['Feed Stock (kg)', $feedStock['total']]);
    fputcsv($output, []);
    fputcsv($output, ['Financial Summary']);
    fputcsv($output, ['Income', 'KES ' . number_format($incData['total'])]);
    fputcsv($output, ['Expenses', 'KES ' . number_format($expData['total'])]);
    fputcsv($output, ['Net Profit', 'KES ' . number_format($netProfit)]);
    fputcsv($output, []);
    fputcsv($output, ['Flock Performance']);
    fputcsv($output, ['Flock', 'Breed', 'Count', 'Eggs', 'Production Rate %']);
    foreach ($flocks as $f) {
        $fEggs = getDB()->prepare("SELECT COALESCE(SUM(total_eggs), 0) as total FROM egg_production WHERE flock_id = ? AND production_date BETWEEN ? AND ?");
        $fEggs->execute([$f['id'], $startDate, $endDate]);
        $eggTotal = $fEggs->fetch()['total'];
        $prodRate = $f['current_count'] > 0 ? round(($eggTotal / $f['current_count']) * 100) : 0;
        fputcsv($output, [$f['name'], $f['breed'], $f['current_count'], $eggTotal, $prodRate . '%']);
    }
    fclose($output);
    exit;
}
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Reports</h1>
        <p class="text-gray-400">Farm performance overview</p>
    </div>
    <div class="flex gap-2">
        <button onclick="window.print()" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:#2a2a2a">
            <i class="fas fa-print"></i> Print
        </button>
        <a href="?page=reports&export=csv&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </div>
</div>

<!-- Date Range Filter -->
<form method="GET" class="mb-6 p-4 rounded-lg" style="background-color:#162019">
    <input type="hidden" name="page" value="reports">
    <div class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-gray-400 text-sm mb-2">Start Date</label>
            <input type="date" name="start_date" value="<?= $startDate ?>" class="px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
        </div>
        <div>
            <label class="block text-gray-400 text-sm mb-2">End Date</label>
            <input type="date" name="end_date" value="<?= $endDate ?>" class="px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
        </div>
        <div>
            <label class="block text-gray-400 text-sm mb-2">Period</label>
            <select onchange="this.form.submit()" class="px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                <option value="7" <?= $days==7?'selected':'' ?>>Last 7 Days</option>
                <option value="30" <?= $days==30?'selected':'' ?>>Last 30 Days</option>
                <option value="90" <?= $days==90?'selected':'' ?>>Last 90 Days</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">Filter</button>
    </div>
    <p class="text-gray-400 text-sm mt-2">Showing data for <?= $days ?> days (<?= date('M d, Y', strtotime($startDate)) ?> - <?= date('M d, Y', strtotime($endDate)) ?>)</p>
</form>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Total Birds</p>
        <p class="text-2xl font-bold text-white"><?= number_format($totalBirds['total']) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Eggs (<?= $days ?> days)</p>
        <p class="text-2xl font-bold" style="color:var(--green-accent)"><?= number_format($eggData['total']) ?></p>
        <p class="text-xs text-gray-500">Losses: <?= number_format($eggData['losses']) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Deaths (<?= $days ?> days)</p>
        <p class="text-2xl font-bold text-red-400"><?= number_format($mortData['total']) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Net Profit</p>
        <p class="text-2xl font-bold <?= $netProfit >= 0 ? 'text-green-400' : 'text-red-400' ?>">KES <?= number_format($netProfit) ?></p>
    </div>
</div>

<!-- Flock Performance Table -->
<div class="card rounded-lg overflow-hidden mb-6" style="background-color:#162019">
    <div class="p-4 border-b border-gray-700 flex justify-between items-center">
        <h2 class="text-lg font-bold text-white">Flock Performance</h2>
    </div>
    <table class="min-w-full">
        <thead style="background-color:#1a2a1f">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Flock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Breed</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Current</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Eggs</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Daily Avg</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Egg Rate %</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($flocks as $f): 
                $fEggs = getDB()->prepare("SELECT COALESCE(SUM(total_eggs), 0) as total, COUNT(*) as days FROM egg_production WHERE flock_id = ? AND production_date BETWEEN ? AND ?");
                $fEggs->execute([$f['id'], $startDate, $endDate]);
                $fData = $fEggs->fetch();
                $eggTotal = $fData['total'];
                $daysWithData = $fData['days'] > 0 ? $fData['days'] : 1;
                $dailyAvg = round($eggTotal / $daysWithData);
                $prodRate = $f['current_count'] > 0 ? round(($eggTotal / $f['current_count']) * 100) : 0;
            ?>
            <tr>
                <td class="px-4 py-3 text-white font-bold"><?= htmlspecialchars($f['name']) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($f['breed'] ?? '-') ?></td>
                <td class="px-4 py-3 text-white"><?= number_format($f['current_count']) ?></td>
                <td class="px-4 py-3 text-white"><?= number_format($eggTotal) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= number_format($dailyAvg) ?></td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-full font-bold" style="background-color:rgba(61,220,110,0.2);color:var(--green-accent)"><?= $prodRate ?>%</span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Financial Summary -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card rounded-lg p-4" style="background-color:#162019">
        <h2 class="text-lg font-bold mb-4 text-white">Financial Summary</h2>
        <div class="space-y-3">
            <div class="flex justify-between items-center p-3 rounded-lg bg-gray-800/50">
                <span class="text-gray-400">Total Income</span>
                <span class="text-xl font-bold text-green-400">KES <?= number_format($incData['total']) ?></span>
            </div>
            <div class="flex justify-between items-center p-3 rounded-lg bg-gray-800/50">
                <span class="text-gray-400">Total Expenses</span>
                <span class="text-xl font-bold text-red-400">KES <?= number_format($expData['total']) ?></span>
            </div>
            <div class="flex justify-between items-center p-3 rounded-lg" style="background-color:var(--green-accent);color:#0a0f0d">
                <span class="font-bold">Net Profit</span>
                <span class="text-xl font-bold">KES <?= number_format($netProfit) ?></span>
            </div>
            <?php if ($expData['total'] > 0): ?>
            <div class="flex justify-between items-center p-3 rounded-lg bg-gray-800/50">
                <span class="text-gray-400">ROI</span>
                <span class="text-xl font-bold" style="color:var(--green-accent)"><?= round(($netProfit / $expData['total']) * 100) ?>%</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card rounded-lg p-4" style="background-color:#162019">
        <h2 class="text-lg font-bold mb-4 text-white">Daily Egg Production</h2>
        <?php if (!empty($weeklyEggData)): ?>
        <div class="space-y-2">
            <?php foreach ($weeklyEggData as $day): 
                $maxEggs = max(array_column($weeklyEggData, 'total'));
                $pct = $maxEggs > 0 ? ($day['total'] / $maxEggs) * 100 : 0;
            ?>
            <div class="flex justify-between items-center">
                <span class="text-gray-400"><?= date('D, M d', strtotime($day['production_date'])) ?></span>
                <div class="flex items-center gap-2">
                    <div class="w-24 h-2 bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width:<?= $pct ?>%;background-color:var(--green-accent)"></div>
                    </div>
                    <span class="text-white font-bold w-16 text-right"><?= number_format($day['total']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-gray-500">No egg production data</p>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
    <div class="card p-4 rounded-lg text-center" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Feed Stock</p>
        <p class="text-xl font-bold text-white"><?= number_format($feedStock['total']) ?> kg</p>
    </div>
    <div class="card p-4 rounded-lg text-center" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Active Flocks</p>
        <p class="text-xl font-bold text-white"><?= count($flocks) ?></p>
    </div>
    <div class="card p-4 rounded-lg text-center" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Days in Period</p>
        <p class="text-xl font-bold text-white"><?= $days ?></p>
    </div>
    <div class="card p-4 rounded-lg text-center" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Avg Eggs/Day</p>
        <p class="text-xl font-bold text-white"><?= number_format($eggData['total'] / $days) ?></p>
    </div>
</div>