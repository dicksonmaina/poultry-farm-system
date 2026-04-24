<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

$stmt = getDB()->query("SELECT * FROM flocks WHERE status = 'active'");
$flocks = $stmt->fetchAll();

$eggStmt = getDB()->query("SELECT * FROM egg_production WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY production_date DESC LIMIT 10");
$recentEggs = $eggStmt->fetchAll();

// For simplicity, we'll show some products for sale
$products = [
    ['name' => 'Fresh Eggs (tray of 30)', 'price' => 300, 'unit' => 'tray', 'image' => 'eggs.jpg'],
    ['name' => 'Chicken (Live, 2kg)', 'price' => 500, 'unit' => 'bird', 'image' => 'chicken.jpg'],
    ['name' => 'Chicken (Dressed, 1.5kg)', 'price' => 650, 'unit' => 'bird', 'image' => 'dressed_chicken.jpg'],
    ['name' => 'Turkey Eggs (tray of 20)', 'price' => 400, 'unit' => 'tray', 'image' => 'turkey_eggs.jpg'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poultry Farm - Product Catalog</title>
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
        .accent-bg {
            background-color: var(--green-accent);
        }
        .hover-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 16px -4px rgba(61, 220, 110, 0.2);
        }
    </style>
</head>
<body class="text-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-gray-900/50 backdrop-blur-sm mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold accent">Product Catalog</h1>
                </div>
                <div>
                    <a href="my_orders.php" class="text-sm text-green-400 hover:underline">My Orders</a>
                    <span class="mx-2 text-gray-400">|</span>
                    <a href="logout.php" class="text-sm text-red-400 hover:underline">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        <div class="mb-6">
            <h2 class="text-xl font-bold accent">Available Products</h2>
            <p class="text-gray-400">Fresh products from our farm</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($products as $product): ?>
                <div class="card p-6 rounded-lg hover-card transition-transform duration-200 border border-gray-700/50 hover:border-green-500/50">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-900/50 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-white"><?=htmlspecialchars($product['name'])?></h3>
                            <p class="text-sm text-gray-400"><?=htmlspecialchars($product['unit'])?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-2xl font-bold accent">KES <?=number_format($product['price'], 0)?></p>
                        <p class="text-sm text-gray-400 mt-1">Per <?=htmlspecialchars($product['unit'])?></p>
                    </div>
                    <a href="order.php?product=<?=urlencode($product['name'])?>&price=<?=$product['price']?>" class="mt-4 inline-block accent-bg text-white px-4 py-2 rounded-lg hover:opacity-90 transition-colors font-medium text-sm">
                        Order Now
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Farm Stats -->
        <div class="mt-12">
            <h2 class="text-xl font-bold accent mb-4">Farm Status</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card p-4 rounded-lg border border-gray-700/50">
                    <h3 class="text-sm font-medium text-gray-400">Active Flocks</h3>
                    <p class="text-2xl font-bold accent mt-2"><?=count($flocks)?></p>
                </div>
                <div class="card p-4 rounded-lg border border-gray-700/50">
                    <h3 class="text-sm font-medium text-gray-400">Today's Eggs</h3>
                    <?php
                    $todayEggsStmt = getDB()->query("SELECT COALESCE(SUM(total_eggs), 0) as total FROM egg_production WHERE production_date = CURDATE()");
                    $todayEggs = $todayEggsStmt->fetch()['total'];
                    ?>
                    <p class="text-2xl font-bold accent mt-2"><?=number_format($todayEggs, 0)?></p>
                </div>
                <div class="card p-4 rounded-lg border border-gray-700/50">
                    <h3 class="text-sm font-medium text-gray-400">Total Birds</h3>
                    <?php
                    $totalBirdsStmt = getDB()->query("SELECT COALESCE(SUM(current_count), 0) as total FROM flocks WHERE status = 'active'");
                    $totalBirds = $totalBirdsStmt->fetch()['total'];
                    ?>
                    <p class="text-2xl font-bold accent mt-2"><?=number_format($totalBirds, 0)?></p>
                </div>
                <div class="card p-4 rounded-lg border border-gray-700/50">
                    <h3 class="text-sm font-medium text-gray-400">Available Products</h3>
                    <p class="text-2xl font-bold accent mt-2"><?=count($products)?></p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>