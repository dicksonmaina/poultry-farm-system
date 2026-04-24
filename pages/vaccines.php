<?php
/**
 * Vaccinations Page
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO vaccinations (flock_id, vaccine_name, date_administered, next_due_date, batch_number, administered_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['flock_id'], $_POST['vaccine_name'], $_POST['date_administered'], $_POST['next_due_date'], $_POST['batch_number'], $_POST['administered_by'], $_POST['notes']]);
    $message = "Vaccination recorded";
}

$flocks = getDB()->query("SELECT * FROM flocks WHERE status = 'active'")->fetchAll();
$vaccines = getDB()->query("SELECT v.*, f.name as flock_name FROM vaccinations v JOIN flocks f ON v.flock_id = f.id ORDER BY v.date_administered DESC")->fetchAll();

// Upcoming vaccinations
$upcoming = getDB()->query("SELECT v.*, f.name as flock_name FROM vaccinations v JOIN flocks f ON v.flock_id = f.id WHERE v.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND v.next_due_date >= CURDATE()")->fetchAll();
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Vaccinations</h1>
        <p class="text-gray-400">Track vaccination schedules</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
        <i class="fas fa-plus"></i> Record Vaccination
    </button>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= $message ?></div>
<?php endif; ?>

<?php if (!empty($upcoming)): ?>
<div class="mb-6">
    <h2 class="text-lg font-bold mb-3 text-yellow-400"><i class="fas fa-exclamation-circle"></i> Due This Week</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <?php foreach ($upcoming as $u): ?>
        <div class="card p-4 rounded-lg border border-yellow-600/50" style="background-color:#162019">
            <div class="flex justify-between">
                <span class="text-white font-bold"><?= htmlspecialchars($u['vaccine_name']) ?></span>
                <span class="text-yellow-400 text-sm"><?= date('M d', strtotime($u['next_due_date'])) ?></span>
            </div>
            <p class="text-gray-400 text-sm"><?= htmlspecialchars($u['flock_name']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card rounded-lg overflow-hidden" style="background-color:#162019">
    <div class="p-4 border-b border-gray-700"><h2 class="text-lg font-bold text-white">Vaccination Records</h2></div>
    <table class="min-w-full">
        <thead style="background-color:#1a2a1f">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Flock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Vaccine</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Given</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Next Due</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Administered By</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($vaccines as $v): ?>
            <tr>
                <td class="px-4 py-3 text-white"><?= htmlspecialchars($v['flock_name']) ?></td>
                <td class="px-4 py-3 font-bold" style="color:var(--green-accent)"><?= htmlspecialchars($v['vaccine_name']) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= date('M d, Y', strtotime($v['date_administered'])) ?></td>
                <td class="px-4 py-3 <?= strtotime($v['next_due_date']) < time() ? 'text-red-400' : 'text-white' ?>"><?= date('M d, Y', strtotime($v['next_due_date'])) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($v['administered_by'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($vaccines)): ?>
            <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">No vaccination records</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="addModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Record Vaccination</h2>
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
                <label class="block text-gray-400 text-sm mb-2">Vaccine Name</label>
                <input type="text" name="vaccine_name" required placeholder="e.g., Newcastle Disease" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Date Given</label>
                    <input type="date" name="date_administered" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Next Due</label>
                    <input type="date" name="next_due_date" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Administered By</label>
                <input type="text" name="administered_by" placeholder="Dr. Name" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Batch Number</label>
                <input type="text" name="batch_number" placeholder="Optional" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
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