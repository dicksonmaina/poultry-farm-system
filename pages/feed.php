<?php
/**
 * Feed Inventory Page
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO feed_inventory (feed_type, quantity_kg, unit_price, supplier, expiry_date, date_received) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['feed_type'], $_POST['quantity_kg'], $_POST['unit_price'], $_POST['supplier'], $_POST['expiry_date'], $_POST['date_received']]);
    $message = "Feed added successfully";
}

$feed = getDB()->query("SELECT * FROM feed_inventory ORDER BY date_received DESC")->fetchAll();
$total = array_sum(array_column($feed, 'quantity_kg'));
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Feed Inventory</h1>
        <p class="text-gray-400">Manage feed stock</p>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
        <i class="fas fa-plus"></i> Add Feed
    </button>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= $message ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Total Stock</p>
        <p class="text-2xl font-bold text-white"><?= number_format($total) ?> <span class="text-sm text-gray-400">kg</span></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Feed Types</p>
        <p class="text-2xl font-bold text-white"><?= count($feed) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Status</p>
        <p class="text-2xl font-bold <?= $total < 50 ? 'text-red-400' : 'text-green-400' ?>"><?= $total < 50 ? 'Low Stock' : 'Adequate' ?></p>
    </div>
</div>

<div class="card rounded-lg overflow-hidden" style="background-color:#162019">
    <table class="min-w-full">
        <thead style="background-color:#1a2a1f">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Quantity</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Unit Price</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Supplier</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Expiry</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Received</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($feed as $f): ?>
            <tr>
                <td class="px-4 py-3 text-white font-bold"><?= htmlspecialchars($f['feed_type']) ?></td>
                <td class="px-4 py-3 text-white"><?= number_format($f['quantity_kg']) ?> kg</td>
                <td class="px-4 py-3 text-gray-400">KES <?= number_format($f['unit_price']) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($f['supplier'] ?? '-') ?></td>
                <td class="px-4 py-3 text-gray-400"><?= date('M d, Y', strtotime($f['expiry_date'])) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= date('M d, Y', strtotime($f['date_received'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($feed)): ?>
            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">No feed records</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="addModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Add Feed Stock</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Feed Type</label>
                <input type="text" name="feed_type" required placeholder="e.g., Layers Mash" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Quantity (kg)</label>
                <input type="number" name="quantity_kg" required min="1" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Unit Price (KES)</label>
                <input type="number" name="unit_price" required min="0" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Supplier</label>
                <input type="text" name="supplier" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Date Received</label>
                    <input type="date" name="date_received" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Expiry Date</label>
                    <input type="date" name="expiry_date" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold" style="background-color:var(--green-accent)">Save</button>
            </div>
        </form>
    </div>
</div>