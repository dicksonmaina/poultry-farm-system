<?php
/**
 * Egg Production Page
 */

function kes($amount) { return 'KES ' . number_format($amount, 0); }

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO egg_production (flock_id, production_date, total_eggs, broken, rejected, medium, large, extra_large) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['flock_id'], $_POST['production_date'], $_POST['total_eggs'],
        $_POST['broken'], $_POST['rejected'], $_POST['medium'], $_POST['large'], $_POST['extra_large']
    ]);
    $message = "Egg production recorded successfully";
}

// Get flocks
$flocks = getDB()->query("SELECT * FROM flocks WHERE status = 'active'")->fetchAll();

// Get today's production
$today = getDB()->query("SELECT ep.*, f.name as flock_name FROM egg_production ep JOIN flocks f ON ep.flock_id = f.id WHERE ep.production_date = CURDATE() ORDER BY ep.id DESC")->fetchAll();

// Get weekly summary
$weekly = getDB()->query("SELECT SUM(total_eggs) as total, SUM(broken + rejected) as losses FROM egg_production WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch();

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $data = getDB()->query("SELECT ep.*, f.name as flock_name FROM egg_production ep JOIN flocks f ON ep.flock_id = f.id ORDER BY ep.production_date DESC")->fetchAll();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="egg_production.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Flock', 'Total', 'Broken', 'Rejected', 'Medium', 'Large', 'XL']);
    foreach ($data as $row) {
        fputcsv($out, [$row['production_date'], $row['flock_name'], $row['total_eggs'], $row['broken'], $row['rejected'], $row['medium'], $row['large'], $row['extra_large']]);
    }
    fclose($out);
    exit;
}
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center flex-wrap gap-2">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Egg Production</h1>
        <p class="text-gray-400">Track daily egg output</p>
    </div>
    <div class="flex gap-2">
        <a href="?page=eggs&export=csv" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:#2a2a2a">
            <i class="fas fa-download"></i> Export CSV
        </a>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
            <i class="fas fa-plus"></i> Record Eggs
        </button>
    </div>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= $message ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Today's Total</p>
        <p class="text-2xl font-bold text-white"><?= number_format(array_sum(array_column($today, 'total_eggs'))) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Weekly Total</p>
        <p class="text-2xl font-bold text-white"><?= number_format($weekly['total'] ?? 0) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Losses (Broken/Rejected)</p>
        <p class="text-2xl font-bold text-red-400"><?= number_format($weekly['losses'] ?? 0) ?></p>
    </div>
</div>

<div class="card rounded-lg overflow-hidden" style="background-color:#162019">
    <div class="p-4 border-b border-gray-700"><h2 class="text-lg font-bold text-white">Today's Production</h2></div>
    <table class="min-w-full">
        <thead style="background-color:#1a2a1f">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Flock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Total</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Medium</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Large</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">XL</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Broken/Rejected</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($today as $row): ?>
            <tr>
                <td class="px-4 py-3 text-white"><?= htmlspecialchars($row['flock_name']) ?></td>
                <td class="px-4 py-3 font-bold" style="color:var(--green-accent)"><?= number_format($row['total_eggs']) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= number_format($row['medium']) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= number_format($row['large']) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= number_format($row['extra_large']) ?></td>
                <td class="px-4 py-3 text-red-400"><?= $row['broken'] + $row['rejected'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($today)): ?>
            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">No records today</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Record Egg Production</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Flock</label>
                <select name="flock_id" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                    <?php foreach ($flocks as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Date</label>
                <input type="date" name="production_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Total Eggs</label>
                    <input type="number" name="total_eggs" required min="0" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Broken</label>
                    <input type="number" name="broken" value="0" min="0" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Medium</label>
                    <input type="number" name="medium" value="0" min="0" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Large</label>
                    <input type="number" name="large" value="0" min="0" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Extra Large</label>
                    <input type="number" name="extra_large" value="0" min="0" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold" style="background-color:var(--green-accent)">Save</button>
            </div>
        </form>
    </div>
</div>