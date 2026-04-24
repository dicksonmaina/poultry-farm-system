<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product = $_POST['product'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);

    if ($product && $quantity > 0 && $price > 0) {
        $total = $quantity * $price;
        $stmt = getDB()->prepare("INSERT INTO client_orders (client_id, product, quantity, unit_price, total_kes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['client_id'], $product, $quantity, $price, $total]);
        $success = 'Order placed successfully!';
    } else {
        $error = 'Invalid order details';
    }
}

// Get product details from query string (if coming from catalog)
$product = $_GET['product'] ?? '';
$price = (float)($_GET['price'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poultry Farm - Place Order</title>
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
    </style>
</head>
<body class="text-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-gray-900/50 backdrop-blur-sm mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold accent">Place Order</h1>
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
        <?php if ($error): ?>
            <div class="mb-6 bg-red-600/20 border border-red-500 text-red-400 px-4 py-3 rounded-md">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-600/20 border border-green-500 text-green-400 px-4 py-3 rounded-md">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="card p-6 rounded-lg">
            <h2 class="text-xl font-bold accent mb-4">Order Form</h2>
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="product" class="block text-sm font-medium text-gray-300 mb-1">Product</label>
                    <input type="text" id="product" name="product" required
                        class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                        value="<?=htmlspecialchars($product)?>" <?= $product ? 'readonly' : '' ?>>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-300 mb-1">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="1" required
                            class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                            value="1">
                    </div>
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-300 mb-1">Unit Price (KES)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required
                            class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                            value="<?=number_format($price, 2)?>" <?= $price ? 'readonly' : '' ?>>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-300">Total</p>
                            <p id="totalDisplay" class="text-2xl font-bold accent">KES 0.00</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full accent-bg text-white py-2 px-4 rounded-lg hover:opacity-90 transition-colors font-medium mt-4"
                    >Place Order</button>
            </form>
        </div>
    </main>

    <script>
        const quantityInput = document.getElementById('quantity');
        const priceInput = document.getElementById('price');
        const totalDisplay = document.getElementById('totalDisplay');

        function calculateTotal() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const total = quantity * price;
            totalDisplay.textContent = 'KES ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        quantityInput.addEventListener('input', calculateTotal);
        priceInput.addEventListener('input', calculateTotal);

        // Initialize total on page load
        calculateTotal();
    </script>
</body>
</html>