<?php
/**
 * Sales Page
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    $total = $_POST['quantity'] * $_POST['unit_price'];
    $stmt = $db->prepare("INSERT INTO sales (flock_id, product_type, quantity, unit_price, total_price, buyer_name, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['flock_id'], $_POST['product_type'], $_POST['quantity'], $_POST['unit_price'], $total, $_POST['buyer_name'], $_POST['sale_date']]);
    
    // Record income
    $inc = $db->prepare("INSERT INTO income (flock_id, source, description, amount, income_date) VALUES (?, ?, ?, ?, ?)");
    $inc->execute([$_POST['flock_id'], $_POST['product_type'] === 'bird' ? 'Bird Sales' : 'Egg Sales', "Sale to " . $_POST['buyer_name'], $total, $_POST['sale_date']]);
    
    $message = "Sale recorded successfully";
}

$flocks = getDB()->query("SELECT * FROM flocks WHERE status = 'active'")->fetchAll();
$sales = getDB()->query("SELECT s.*, f.name as flock_name FROM sales s LEFT JOIN flocks f ON s.flock_id = f.id ORDER BY s.sale_date DESC")->fetchAll();
$monthly = getDB()->query("SELECT COALESCE(SUM(total_price), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE())")->fetch();

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Product', 'Flock', 'Quantity', 'Unit Price', 'Total', 'Buyer']);
    foreach ($sales as $row) {
        fputcsv($out, [$row['sale_date'], ucfirst($row['product_type']), $row['flock_name'] ?? 'N/A', $row['quantity'], 'KES ' . number_format($row['unit_price']), 'KES ' . number_format($row['total_price']), $row['buyer_name'] ?? '']);
    }
    fclose($out);
    exit;
}
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center flex-wrap gap-2">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Sales</h1>
        <p class="text-gray-400">Record product sales</p>
    </div>
    <div class="flex gap-2">
        <a href="?page=sales&export=csv" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:#2a2a2a">
            <i class="fas fa-download"></i> Export CSV
        </a>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
            <i class="fas fa-plus"></i> New Sale
        </button>
    </div>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= $message ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Monthly Sales</p>
        <p class="text-2xl font-bold text-green-400">KES <?= number_format($monthly['total']) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Total Transactions</p>
        <p class="text-2xl font-bold text-white"><?= count($sales) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Avg Transaction</p>
        <p class="text-2xl font-bold text-white">KES <?= count($sales) > 0 ? number_format($monthly['total'] / count($sales)) : 0 ?></p>
    </div>
</div>

<div class="card rounded-lg overflow-hidden" style="background-color:#162019">
    <table class="min-w-full">
        <thead style="background-color:#1a2a1f">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Product</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Qty</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Unit Price</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Total</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Buyer</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php foreach ($sales as $s): ?>
            <tr>
                <td class="px-4 py-3 text-gray-400"><?= date('M d, Y', strtotime($s['sale_date'])) ?></td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded" style="background-color:rgba(61,220,110,0.2);color:var(--green-accent)">
                        <?= ucfirst($s['product_type']) ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-white"><?= number_format($s['quantity']) ?></td>
                <td class="px-4 py-3 text-gray-400">KES <?= number_format($s['unit_price']) ?></td>
                <td class="px-4 py-3 font-bold text-green-400">KES <?= number_format($s['total_price']) ?></td>
                <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($s['buyer_name'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sales)): ?>
            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">No sales recorded</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="addModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Record Sale</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Product Type</label>
                <select name="product_type" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                    <option value="egg">Eggs</option>
                    <option value="bird">Birds</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Flock</label>
                <select name="flock_id" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                    <option value="">N/A</option>
                    <?php foreach ($flocks as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Quantity</label>
                    <input type="number" name="quantity" required min="1" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-2">Unit Price (KES)</label>
                    <input type="number" name="unit_price" required min="1" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Buyer Name</label>
                <input type="text" name="buyer_name" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Date</label>
                <input type="date" name="sale_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold" style="background-color:var(--green-accent)">Save</button>
            </div>
        </form>
    </div>
</div>