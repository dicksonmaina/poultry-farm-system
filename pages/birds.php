<?php
/**
 * Birds Management - Individual Bird Records
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO birds (flock_id, ring_number, gender, hatch_date, weight, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['flock_id'], $_POST['ring_number'], $_POST['gender'], $_POST['hatch_date'], $_POST['weight'], $_POST['status'], $_POST['notes']]);
    $message = "Bird record added";
}

$flocks = getDB()->query("SELECT * FROM flocks WHERE status = 'active'")->fetchAll();
$birds = getDB()->query("SELECT b.*, f.name as flock_name FROM birds b JOIN flocks f ON b.flock_id = f.id ORDER BY b.id DESC LIMIT 50")->fetchAll();
$totalBirds = getDB()->query("SELECT COUNT(*) as total FROM birds WHERE status = 'alive'")->fetch();
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Individual Birds</h1>
        <p class="text-gray-400">Track individual bird records</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
        <i class="fas fa-plus"></i> Add Bird
    </button>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= $message ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Total Recorded</p>
        <p class="text-2xl font-bold text-white"><?= count($birds) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Males</p>
        <p class="text-2xl font-bold text-blue-400"><?= count(array_filter($birds, fn($b) => $b['gender'] === 'male')) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Females</p>
        <p class="text-2xl font-bold text-pink-400"><?= count(array_filter($birds, fn($b) => $b['gender'] === 'female')) ?></p>
    </div>
</div>

<div class="card rounded-lg overflow-hidden" style="background-color:#162019">
    <table class="min-w-full">
        <thead style="background-color:#1a2a1f">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Ring #</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Flock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Gender</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Hatch Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Weight</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($birds as $b): ?>
            <tr>
                <td class="px-4 py-3 text-white font-mono"><?= htmlspecialchars($b['ring_number'] ?? '-') ?></td>
                <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($b['flock_name']) ?></td>
                <td class="px-4 py-3">
                    <?php if ($b['gender'] === 'male'): ?>
                    <span class="text-blue-400"><i class="fas fa-mars"></i> Male</span>
                    <?php else: ?>
                    <span class="text-pink-400"><i class="fas fa-venus"></i> Female</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-gray-400"><?= $b['hatch_date'] ? date('M d, Y', strtotime($b['hatch_date'])) : '-' ?></td>
                <td class="px-4 py-3 text-gray-400"><?= $b['weight'] ? $b['weight'] . ' kg' : '-' ?></td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded <?= $b['status'] === 'alive' ? 'bg-green-900/50 text-green-300' : 'bg-gray-700 text-gray-400' ?>">
                        <?= ucfirst($b['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($birds)): ?>
            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">No individual bird records yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="addModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Add Bird Record</h2>
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
                <label class="block text-gray-400 text-sm mb-2">Ring Number</label>
                <input type="text" name="ring_number" placeholder="Optional" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Gender</label>
                    <select name="gender" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Status</label>
                    <select name="status" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                        <option value="alive">Alive</option>
                        <option value="sold">Sold</option>
                        <option value="slaughtered">Slaughtered</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Hatch Date</label>
                    <input type="date" name="hatch_date" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Weight (kg)</label>
                    <input type="number" name="weight" step="0.01" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
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