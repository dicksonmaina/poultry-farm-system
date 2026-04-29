<?php
/**
 * Mortality Page
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO mortality (flock_id, death_date, cause, quantity, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['flock_id'], $_POST['death_date'], $_POST['cause'], $_POST['quantity'], $_POST['notes']]);
    
    // Update flock count
    $update = $db->prepare("UPDATE flocks SET current_count = current_count - ? WHERE id = ?");
    $update->execute([$_POST['quantity'], $_POST['flock_id']]);
    
    $message = "Mortality recorded";
}

$flocks = getDB()->query("SELECT * FROM flocks WHERE status = 'active'")->fetchAll();
$mortality = getDB()->query("SELECT m.*, f.name as flock_name FROM mortality m JOIN flocks f ON m.flock_id = f.id ORDER BY m.death_date DESC")->fetchAll();

// Monthly stats
$monthly = getDB()->query("SELECT SUM(quantity) as total FROM mortality WHERE MONTH(death_date) = MONTH(CURDATE())")->fetch();
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Mortality Records</h1>
        <p class="text-gray-400">Track bird losses</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
        <i class="fas fa-plus"></i> Record Death
    </button>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= $message ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">This Month</p>
        <p class="text-2xl font-bold text-red-400"><?= number_format($monthly['total'] ?? 0) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Total Records</p>
        <p class="text-2xl font-bold text-white"><?= count($mortality) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Status</p>
        <p class="text-xl font-bold <?= ($monthly['total'] ?? 0) > 10 ? 'text-red-400' : 'text-green-400' ?>">
            <?= ($monthly['total'] ?? 0) > 10 ? 'High Mortality' : 'Normal' ?>
        </p>
    </div>
</div>

<div class="card rounded-lg overflow-hidden" style="background-color:#162019">
    <table class="min-w-full">
        <thead style="background-color:#1a2a1f">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Flock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Quantity</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Cause</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Notes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($mortality as $m): ?>
            <tr>
                <td class="px-4 py-3 text-gray-400"><?= date('M d, Y', strtotime($m['death_date'])) ?></td>
                <td class="px-4 py-3 text-white"><?= htmlspecialchars($m['flock_name']) ?></td>
                <td class="px-4 py-3 text-red-400 font-bold"><?= number_format($m['quantity']) ?></td>
                <td class="px-4 py-3 text-gray-300"><?= htmlspecialchars($m['cause'] ?? '-') ?></td>
                <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($m['notes'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($mortality)): ?>
            <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">No mortality records</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="addModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Record Mortality</h2>
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
                    <input type="date" name="death_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Quantity</label>
                    <input type="number" name="quantity" required min="1" value="1" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Cause</label>
                <input type="text" name="cause" placeholder="e.g., Disease, Predator, Heat stress" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Notes</label>
                <textarea name="notes" rows="2" placeholder="Additional details..." class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold" style="background-color:var(--green-accent)">Save</button>
            </div>
        </form>
    </div>
</div>