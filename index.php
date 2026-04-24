<?php
/**
 * Poultry Farm System - Main Entry Point
 */

session_start();

require_once 'config.php';

$page = $_GET['page'] ?? 'dashboard';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) && $page !== 'login') {
    header('Location: index.php?page=login');
    exit;
}

$pages = [
    'dashboard' => 'pages/dashboard.php',
    'flocks' => 'pages/flocks.php',
    'birds' => 'pages/birds.php',
    'eggs' => 'pages/eggs.php',
    'feed' => 'pages/feed.php',
    'vaccines' => 'pages/vaccines.php',
    'mortality' => 'pages/mortality.php',
    'weight' => 'pages/weight.php',
    'finance' => 'pages/finance.php',
    'sales' => 'pages/sales.php',
    'reports' => 'pages/reports.php',
    'settings' => 'pages/settings.php',
    'login' => 'pages/login.php',
    'logout' => 'pages/logout.php'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poultry Farm Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0a0f0d;
            --card-bg: #162019;
            --green-accent: #3ddc6e;
        }
        body {
            background-color: var(--bg-dark);
        }
        .sidebar {
            background-color: #0d1a12;
            border-right: 1px solid #1f2d25;
        }
        .nav-item {
            color: #9ca3af;
        }
        .nav-item:hover, .nav-item.active {
            background-color: #1a2a1f;
            color: var(--green-accent);
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex" style="background-color: var(--bg-dark)">
        <?php if (isset($_SESSION['user_id'])): ?>
        <aside class="w-64 sidebar text-white flex-shrink-0 fixed h-full">
            <div class="p-4 border-b border-gray-800">
                <h1 class="text-xl font-bold"><i class="fas fa-drumstick-bite" style="color: var(--green-accent)"></i> Poultry Farm</h1>
            </div>
            <nav class="mt-4">
                <a href="?page=dashboard" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='dashboard'?'active':'' ?>">
                    <i class="fas fa-home w-6"></i> Dashboard
                </a>
                <a href="?page=flocks" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='flocks'?'active':'' ?>">
                    <i class="fas fa-feather-alt w-6"></i> Flocks
                </a>
                <a href="?page=birds" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='birds'?'active':'' ?>">
                    <i class="fas fa-crow w-6"></i> Birds
                </a>
                <a href="?page=eggs" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='eggs'?'active':'' ?>">
                    <i class="fas fa-egg w-6"></i> Egg Production
                </a>
                <a href="?page=feed" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='feed'?'active':'' ?>">
                    <i class="fas fa-seedling w-6"></i> Feed
                </a>
                <a href="?page=vaccines" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='vaccines'?'active':'' ?>">
                    <i class="fas fa-syringe w-6"></i> Vaccinations
                </a>
                <a href="?page=mortality" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='mortality'?'active':'' ?>">
                    <i class="fas fa-skull w-6"></i> Mortality
                </a>
                <a href="?page=weight" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='weight'?'active':'' ?>">
                    <i class="fas fa-weight w-6"></i> Weight Records
                </a>
                <a href="?page=finance" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='finance'?'active':'' ?>">
                    <i class="fas fa-money-bill w-6"></i> Finance
                </a>
                <a href="?page=sales" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='sales'?'active':'' ?>">
                    <i class="fas fa-shopping-cart w-6"></i> Sales
                </a>
                <a href="?page=reports" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='reports'?'active':'' ?>">
                    <i class="fas fa-chart-bar w-6"></i> Reports
                </a>
                <a href="?page=settings" class="nav-item block px-4 py-2 hover:bg-green-700/20 <?= $page=='settings'?'active':'' ?>">
                    <i class="fas fa-cog w-6"></i> Settings
                </a>
                <a href="?page=logout" class="nav-item block px-4 py-2 hover:bg-green-700/20 mt-4 border-t border-gray-800">
                    <i class="fas fa-sign-out-alt w-6"></i> Logout
                </a>
            </nav>
        </aside>
        <?php endif; ?>
        
        <main class="flex-1 p-6 <?= isset($_SESSION['user_id']) ? 'ml-64' : '' ?>">
            <?php
            if (isset($pages[$page]) && file_exists($pages[$page])) {
                require_once $pages[$page];
            } else {
                require_once 'pages/dashboard.php';
            }
            ?>
        </main>
    </div>
</body>
</html>