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
    <title>Poultry Farm - Order Tracking</title>
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
        .tracking-step {
            display: flex;
            align-items: center;
            space-x: 3;
        }
        .tracking-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .tracking-dot.completed { background-color: var(--green-accent); }
        .tracking-dot.active { background-color: var(--green-accent); animation: pulse 1.5s infinite; }
        .tracking-dot.pending { background-color: #4b5563; }
        @keyframes pulse {
            0% { transform: scale(0.9); opacity: 0.7; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.7; }
        }
        .tracking-line {
            height: 2px;
            flex-grow: 1;
            background-color: #4b5563;
        }
        .tracking-line.completed { background-color: var(--green-accent); }
    </style>
</head>
<body class="text-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-gray-900/50 backdrop-blur-sm mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold accent">Order Tracking</h1>
                </div>
                <div>
                    <a href="my_orders.php" class="text-sm text-green-400 hover:underline">← My Orders</a>
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
                <p class="text-gray-500">You have no orders to track.</p>
                <a href="catalog.php" class="mt-4 inline-block accent-bg text-white px-4 py-2 rounded-lg hover:opacity-90 transition-colors font-medium">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-8">
                <?php foreach ($orders as $order): ?>
                    <div class="card p-6 rounded-lg border border-gray-700/50">
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-white flex justify-between items-center">
                                Order #<?=htmlspecialchars($order['id'])?>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium 
                                    <?= 
                                    $order['status'] === 'pending' ? 'status-pending' :
                                    ($order['status'] === 'processing' ? 'status-processing' :
                                    ($order['status'] === 'delivered' ? 'status-delivered' : 'status-cancelled'))
                                    ?>">
                                    <?=ucfirst($order['status'])?>
                                </span>
                            </h2>
                            <p class="text-sm text-gray-400 mt-1">Placed on: <?=htmlspecialchars(date('M d, Y H:i', strtotime($order['date'])))?></p>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-xl font-bold accent mb-4">Tracking Progress</h3>
                            <div class="space-y-4">
                                <!-- Step 1: Order Placed -->
                                <div class="tracking-step">
                                    <div class="tracking-dot 
                                        <?= 
                                        in_array($order['status'], ['processing', 'delivered']) ? 'completed' : 
                                        ($order['status'] === 'pending' ? 'active' : 'pending')
                                        ?>">
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-white">Order Placed</h4>
                                        <p class="text-sm text-gray-400"><?=htmlspecialchars(date('M d, Y H:i', strtotime($order['date'])))?></p>
                                    </div>
                                </div>
                                <div class="tracking-line 
                                    <?= 
                                    in_array($order['status'], ['processing', 'delivered']) ? 'completed' : 'pending'
                                    ?>"></div>

                                <!-- Step 2: Processing -->
                                <div class="tracking-step">
                                    <div class="tracking-dot 
                                        <?= 
                                        $order['status'] === 'delivered' ? 'completed' : 
                                        ($order['status'] === 'processing' ? 'active' : 'pending')
                                        ?>">
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-white">Processing</h4>
                                        <p class="text-sm text-gray-400">Preparing your order for shipment</p>
                                    </div>
                                </div>
                                <div class="tracking-line 
                                    <?= 
                                    $order['status'] === 'delivered' ? 'completed' : 'pending'
                                    ?>"></div>

                                <!-- Step 3: Delivered -->
                                <div class="tracking-step">
                                    <div class="tracking-dot 
                                        <?= 
                                        $order['status'] === 'delivered' ? 'completed' : 'pending'
                                        ?>">
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-white">Delivered</h4>
                                        <p class="text-sm text-gray-400">
                                            <?= 
                                            $order['status'] === 'delivered' ? 
                                            htmlspecialchars(date('M d, Y H:i', strtotime($order['date'] . ' +2 days'))) : 
                                            'Pending'
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-700/50">
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-gray-300">Order Details</p>
                                <div class="text-sm space-y-1">
                                    <p><span class="font-medium">Product:</span> <?=htmlspecialchars($order['product'])?></p>
                                    <p><span class="font-medium">Quantity:</span> <?=htmlspecialchars($order['quantity'])?></p>
                                    <p><span class="font-medium">Unit Price:</span> KES <?=number_format($order['unit_price'], 2)?></p>
                                    <p><span class="font-medium">Total:</span> <span class="accent font-bold">KES <?=number_format($order['total_kes'], 2)?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>