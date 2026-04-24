<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

$stmt = getDB()->prepare("SELECT * FROM client_orders WHERE client_id = ? ORDER BY date DESC");
$stmt->execute([$_SESSION['client_id']]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poultry Farm - My Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --bg-dark: #0a0f0d;
            --card-bg: #162019;
            --green-accent: #3ddc6e;
        }
        body {
            background-color: var(--bg-dark);
        }
        .card {
            background-color: var(--card-bg);
        }
        .accent {
            color: var(--green-accent);
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #d1ecf1; color: #0c5460; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body class="text-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-gray-900/50 backdrop-blur-sm mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold accent">My Orders</h1>
                </div>
                <div>
                    <a href="catalog.php" class="text-sm text-green-400 hover:underline">← Back to Catalog</a>
                    <span class="mx-2 text-gray-400">|</span>
                    <a href="logout.php" class="text-sm text-red-400 hover:underline">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        <?php if (empty($orders)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500">You haven't placed any orders yet.</p>
                <a href="catalog.php" class="mt-4 inline-block accent-bg text-white px-4 py-2 rounded-lg hover:opacity-90 transition-colors font-medium">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="card p-6 rounded-lg border border-gray-700/50">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-semibold text-white"><?=htmlspecialchars($order['product'])?></h3>
                                <p class="text-sm text-gray-400">Order #<?=htmlspecialchars($order['id'])?></p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium 
                                    <?= 
                                    $order['status'] === 'pending' ? 'status-pending' :
                                    ($order['status'] === 'processing' ? 'status-processing' :
                                    ($order['status'] === 'delivered' ? 'status-delivered' : 'status-cancelled'))
                                    ?>">
                                    <?=ucfirst($order['status'])?>
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4 text-sm">
                            <div>
                                <p class="text-gray-400">Quantity</p>
                                <p class="text-white font-medium"><?=htmlspecialchars($order['quantity'])?></p>
                            </div>
                            <div>
                                <p class="text-gray-400">Unit Price</p>
                                <p class="text-white font-medium">KES <?=number_format($order['unit_price'], 2)?></p>
                            </div>
                            <div>
                                <p class="text-gray-400">Total</p>
                                <p class="text-white font-medium accent">KES <?=number_format($order['total_kes'], 2)?></p>
                            </div>
                            <div>
                                <p class="text-gray-400">Order Date</p>
                                <p class="text-white font-medium"><?=htmlspecialchars(date('M d, Y H:i', strtotime($order['date'])))?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>