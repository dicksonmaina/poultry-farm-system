<?php
/**
 * Flocks Management Page
 */
$page = 'flocks';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $db = getDB();
        
        if ($_POST['action'] === 'add') {
            $stmt = $db->prepare("INSERT INTO flocks (name, breed, date_acquired, initial_count, current_count) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['breed'],
                $_POST['date_acquired'],
                $_POST['initial_count'],
                $_POST['initial_count']
            ]);
            $message = "Flock added successfully";
        } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
            $stmt = $db->prepare("UPDATE flocks SET name=?, breed=?, status=? WHERE id=?");
            $stmt->execute([$_POST['name'], $_POST['breed'], $_POST['status'], $_POST['id']]);
            $message = "Flock updated successfully";
        }
    }
}

// Get all flocks
$stmt = getDB()->query("SELECT * FROM flocks ORDER BY date_acquired DESC");
$flocks = $stmt->fetchAll();
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Flock Management</h1>
        <p class="text-gray-400">Manage your poultry flocks</p>
    </div>
    <button @click="$refs.modal.classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
        <i class="fas fa-plus"></i> Add Flock
    </button>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card rounded-lg overflow-hidden" style="background-color:#162019">
    <table class="min-w-full">
        <thead style="background-color:#1a2a1f">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Breed</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date Acquired</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Initial</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Current</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($flocks as $flock): ?>
            <tr>
                <td class="px-6 py-4 text-white"><?= htmlspecialchars($flock['name']) ?></td>
                <td class="px-6 py-4 text-gray-400"><?= htmlspecialchars($flock['breed'] ?? '-') ?></td>
                <td class="px-6 py-4 text-gray-400"><?= date('M d, Y', strtotime($flock['date_acquired'])) ?></td>
                <td class="px-6 py-4 text-gray-400"><?= number_format($flock['initial_count']) ?></td>
                <td class="px-6 py-4 font-bold" style="color:var(--green-accent)"><?= number_format($flock['current_count']) ?></td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full <?= $flock['status']=='active'?'bg-green-900/50 text-green-300':'bg-gray-700 text-gray-400' ?>">
                        <?= ucfirst($flock['status']) ?>
                    </span>
                </td>
                <td class="px-6 py-4">
                    <button class="text-blue-400 hover:text-blue-300 mr-2"><i class="fas fa-edit"></i></button>
                    <button class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($flocks)): ?>
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No flocks yet. Add your first flock!</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Flock Modal -->
<div x-show="!$refs.modal?.classList.contains('hidden')" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-data>
    <div x-ref="modal" class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Add New Flock</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Flock Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Breed</label>
                <input type="text" name="breed" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white" placeholder="e.g., Kuroiler, Kenbro">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Date Acquired</label>
                <input type="date" name="date_acquired" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Initial Count</label>
                <input type="number" name="initial_count" required min="1" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="$refs.modal.classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold" style="background-color:var(--green-accent)">Add Flock</button>
            </div>
        </form>
    </div>
</div>