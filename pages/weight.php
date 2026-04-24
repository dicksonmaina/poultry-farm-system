<?php
/**
 * Weight Records Page
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO weight_records (flock_id, record_date, average_weight, sample_size, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['flock_id'], $_POST['record_date'], $_POST['average_weight'], $_POST['sample_size'], $_POST['notes']]);
    $message = "Weight record added";
}

$flocks = getDB()->query("SELECT * FROM flocks WHERE status = 'active'")->fetchAll();
$weights = getDB()->query("SELECT w.*, f.name as flock_name FROM weight_records w JOIN flocks f ON w.flock_id = f.id ORDER BY w.record_date DESC")->fetchAll();
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Weight Records</h1>
        <p class="text-gray-400">Monitor bird growth</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
        <i class="fas fa-plus"></i> Record Weight
    </button>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= $message ?></div>
<?php endif; ?>

<div class="card rounded-lg overflow-hidden" style="background-color:#162019">
    <table class="min-w-full">
        <thead style="background-color:#1a2a1f">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Flock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Avg Weight</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Sample</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Notes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($weights as $w): ?>
            <tr>
                <td class="px-4 py-3 text-gray-400"><?= date('M d, Y', strtotime($w['record_date'])) ?></td>
                <td class="px-4 py-3 text-white"><?= htmlspecialchars($w['flock_name']) ?></td>
                <td class="px-4 py-3 font-bold" style="color:var(--green-accent)"><?= $w['average_weight'] ?> kg</td>
                <td class="px-4 py-3 text-gray-400"><?= $w['sample_size'] ?? '-' ?></td>
                <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($w['notes'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($weights)): ?>
            <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">No weight records</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="addModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Record Weight</h2>
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
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Date</label>
                    <input type="date" name="record_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Avg Weight (kg)</label>
                    <input type="number" name="average_weight" step="0.01" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Sample Size</label>
                <input type="number" name="sample_size" min="1" placeholder="Optional" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold" style="background-color:var(--green-accent)">Save</button>
            </div>
        </form>
    </div>
</div>