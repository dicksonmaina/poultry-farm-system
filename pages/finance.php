<?php
/**
 * Finance Page - Expenses & Income
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    $db = getDB();
    if ($_POST['type'] === 'expense') {
        $stmt = $db->prepare("INSERT INTO expenses (flock_id, category, description, amount, expense_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['flock_id'], $_POST['category'], $_POST['description'], $_POST['amount'], $_POST['date']]);
    } else {
        $stmt = $db->prepare("INSERT INTO income (flock_id, source, description, amount, income_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['flock_id'], $_POST['source'], $_POST['description'], $_POST['amount'], $_POST['date']]);
    }
    $message = ucfirst($_POST['type']) . " recorded successfully";
}

$flocks = getDB()->query("SELECT * FROM flocks WHERE status = 'active'")->fetchAll();
$expenses = getDB()->query("SELECT e.*, f.name as flock_name FROM expenses e LEFT JOIN flocks f ON e.flock_id = f.id ORDER BY e.expense_date DESC LIMIT 20")->fetchAll();
$income = getDB()->query("SELECT i.*, f.name as flock_name FROM income i LEFT JOIN flocks f ON i.flock_id = f.id ORDER BY i.income_date DESC LIMIT 20")->fetchAll();

// Monthly totals
$monthExp = getDB()->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE())")->fetch();
$monthInc = getDB()->query("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE MONTH(income_date) = MONTH(CURDATE())")->fetch();
$net = $monthInc['total'] - $monthExp['total'];

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="finance.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Finance Report - Expenses']);
    fputcsv($out, ['Date', 'Category', 'Description', 'Amount (KES)']);
    foreach ($expenses as $row) {
        fputcsv($out, [$row['expense_date'], $row['category'], $row['description'] ?? '', number_format($row['amount'])]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Income']);
    fputcsv($out, ['Date', 'Source', 'Description', 'Amount (KES)']);
    foreach ($income as $row) {
        fputcsv($out, [$row['income_date'], $row['source'], $row['description'] ?? '', number_format($row['amount'])]);
    }
    fclose($out);
    exit;
}
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6 flex justify-between items-center flex-wrap gap-2">
    <div>
        <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Finance</h1>
        <p class="text-gray-400">Track expenses and income</p>
    </div>
    <div class="flex gap-2">
        <a href="?page=finance&export=csv" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:#2a2a2a">
            <i class="fas fa-download"></i> Export CSV
        </a>
        <button onclick="document.getElementById('expenseModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white bg-red-600 hover:bg-red-700">
            <i class="fas fa-minus"></i> Expense
        </button>
        <button onclick="document.getElementById('incomeModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg font-bold text-white" style="background-color:var(--green-accent)">
            <i class="fas fa-plus"></i> Income
        </button>
    </div>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= $message ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Income (Month)</p>
        <p class="text-xl font-bold text-green-400">KES <?= number_format($monthInc['total']) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Expenses (Month)</p>
        <p class="text-xl font-bold text-red-400">KES <?= number_format($monthExp['total']) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">Net Profit</p>
        <p class="text-xl font-bold <?= $net >= 0 ? 'text-green-400' : 'text-red-400' ?>">KES <?= number_format($net) ?></p>
    </div>
    <div class="card p-4 rounded-lg" style="background-color:#162019">
        <p class="text-gray-400 text-sm">ROI</p>
        <p class="text-xl font-bold text-white"><?= $monthExp['total'] > 0 ? round(($net / $monthExp['total']) * 100) : 0 ?>%</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Expenses -->
    <div class="card rounded-lg overflow-hidden" style="background-color:#162019">
        <div class="p-4 border-b border-gray-700"><h2 class="text-lg font-bold text-red-400"><i class="fas fa-minus-circle"></i> Recent Expenses</h2></div>
        <table class="min-w-full">
            <thead style="background-color:#1a2a1f">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php foreach ($expenses as $e): ?>
                <tr>
                    <td class="px-4 py-3 text-gray-400"><?= date('M d', strtotime($e['expense_date'])) ?></td>
                    <td class="px-4 py-3 text-white"><?= htmlspecialchars($e['category']) ?></td>
                    <td class="px-4 py-3 text-red-400 font-bold">KES <?= number_format($e['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($expenses)): ?>
                <tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">No expenses</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Income -->
    <div class="card rounded-lg overflow-hidden" style="background-color:#162019">
        <div class="p-4 border-b border-gray-700"><h2 class="text-lg font-bold text-green-400"><i class="fas fa-plus-circle"></i> Recent Income</h2></div>
        <table class="min-w-full">
            <thead style="background-color:#1a2a1f">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Source</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php foreach ($income as $i): ?>
                <tr>
                    <td class="px-4 py-3 text-gray-400"><?= date('M d', strtotime($i['income_date'])) ?></td>
                    <td class="px-4 py-3 text-white"><?= htmlspecialchars($i['source']) ?></td>
                    <td class="px-4 py-3 text-green-400 font-bold">KES <?= number_format($i['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($income)): ?>
                <tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">No income</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Expense Modal -->
<div id="expenseModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Add Expense</h2>
        <form method="POST">
            <input type="hidden" name="type" value="expense">
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Category</label>
                <select name="category" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                    <option value="Feed">Feed</option>
                    <option value="Vaccines">Vaccines</option>
                    <option value="Labor">Labor</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Equipment">Equipment</option>
                    <option value="Transport">Transport</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Amount (KES)</label>
                <input type="number" name="amount" required min="1" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Description</label>
                <input type="text" name="description" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Date</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('expenseModal').classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold bg-red-600">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Income Modal -->
<div id="incomeModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Add Income</h2>
        <form method="POST">
            <input type="hidden" name="type" value="income">
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Source</label>
                <select name="source" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                    <option value="Egg Sales">Egg Sales</option>
                    <option value="Bird Sales">Bird Sales</option>
                    <option value="Manure Sales">Manure Sales</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Amount (KES)</label>
                <input type="number" name="amount" required min="1" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Description</label>
                <input type="text" name="description" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Date</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('incomeModal').classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold" style="background-color:var(--green-accent)">Save</button>
            </div>
        </form>
    </div>
</div>