<?php
/**
 * Tenant-aware public entrypoint for Poultry Manager Cloud.
 *
 * Responsibilities:
 * - Resolve tenant from subdomain, JWT, or session
 * - Enforce tenant isolation
 * - Route public landing, API, and dashboard contexts
 */

require_once __DIR__ . '/../config/tenant-api-bootstrap.php';

$tenantApp = new TenantApiBootstrap();
$farmId = $tenantApp->getFarmId();
$tenant = $tenantApp->getTenant();

// If no tenant resolved, show public landing page
if (!$farmId || !$tenant) {
    $publicPath = $_SERVER['REQUEST_URI'] ?? '/';

    if (str_starts_with($publicPath, '/api/')) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Tenant context required for API access']);
        exit;
    }

    renderPublicPage();
    exit;
}

// Tenant resolved - enforce active status
$farmId = $tenantApp->requireTenant();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['farm_id'] = $farmId;
$_SESSION['tenant'] = $tenant;

$path = $_SERVER['REQUEST_URI'] ?? '/';

if (str_starts_with($path, '/api/')) {
    require_once __DIR__ . '/../api/tenant-endpoints.php';
    exit;
}

renderTenantDashboard($tenant);

function renderPublicPage(): void {
    $title = 'Poultry Manager Cloud - Simple Farm Management for East Africa';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
            header { background: #2c3e50; color: white; padding: 1rem 0; }
            nav { display: flex; justify-content: space-between; align-items: center; }
            .logo { font-size: 1.5rem; font-weight: bold; }
            .nav-links a { color: white; text-decoration: none; margin-left: 2rem; }
            .hero { background: #3498db; color: white; padding: 4rem 0; text-align: center; }
            .hero h1 { font-size: 2.5rem; margin-bottom: 1rem; }
            .hero p { font-size: 1.2rem; margin-bottom: 2rem; }
            .cta-button { display: inline-block; background: #e74c3c; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 5px; font-size: 1.1rem; }
            .features { padding: 4rem 0; }
            .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem; }
            .feature { text-align: center; padding: 2rem; }
            .feature h3 { color: #2c3e50; margin-bottom: 1rem; }
            footer { background: #2c3e50; color: white; padding: 2rem 0; text-align: center; }
        </style>
    </head>
    <body>
        <header>
            <div class="container">
                <nav>
                    <div class="logo">🐔 Poultry Manager Cloud</div>
                    <div class="nav-links">
                        <a href="#features">Features</a>
                        <a href="#pricing">Pricing</a>
                        <a href="/signup">Sign Up</a>
                        <a href="/login">Login</a>
                    </div>
                </nav>
            </div>
        </header>

        <section class="hero">
            <div class="container">
                <h1>Simple Farm Management for East Africa</h1>
                <p>Track flocks, production, feed, health, and sales. Built for poultry farmers who want to move beyond spreadsheets.</p>
                <a href="/signup" class="cta-button">Start Free Trial</a>
            </div>
        </section>

        <section class="features" id="features">
            <div class="container">
                <h2 style="text-align: center; margin-bottom: 2rem;">Everything You Need to Run Your Poultry Farm</h2>
                <div class="features-grid">
                    <div class="feature">
                        <h3>🐔 Flock Management</h3>
                        <p>Track your birds from arrival to sale. Monitor mortality, growth, and performance in real-time.</p>
                    </div>
                    <div class="feature">
                        <h3>🥚 Production Tracking</h3>
                        <p>Log daily egg production, track trends, and optimize your laying cycles for better yields.</p>
                    </div>
                    <div class="feature">
                        <h3>🌾 Feed Management</h3>
                        <p>Monitor feed inventory, costs, and conversion rates. Never run out or over-order again.</p>
                    </div>
                    <div class="feature">
                        <h3>💊 Health Records</h3>
                        <p>Keep vaccination schedules, health checks, and treatment records organized and accessible.</p>
                    </div>
                    <div class="feature">
                        <h3>📊 Reports & Analytics</h3>
                        <p>Get insights into your farm's performance with simple, actionable reports.</p>
                    </div>
                    <div class="feature">
                        <h3>💰 Sales & Finance</h3>
                        <p>Track sales, expenses, and profitability. Know exactly where your money is going.</p>
                    </div>
                </div>
            </div>
        </section>

        <footer>
            <div class="container">
                <p>&copy; 2026 Poultry Manager Cloud. Built for farmers, by farmers.</p>
            </div>
        </footer>
    </body>
    </html>
    <?php
}

function renderTenantDashboard(array $tenant): void {
    $title = $tenant['name'] . ' - Poultry Manager Cloud';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; }
            .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
            header { background: #2c3e50; color: white; padding: 1rem 0; }
            nav { display: flex; justify-content: space-between; align-items: center; }
            .logo { font-size: 1.5rem; font-weight: bold; }
            .dashboard { padding: 2rem 0; }
            .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
            .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .card h3 { color: #2c3e50; margin-bottom: 0.5rem; }
            .card .metric { font-size: 2rem; font-weight: bold; color: #3498db; }
        </style>
    </head>
    <body>
        <header>
            <div class="container">
                <nav>
                    <div class="logo">🐔 <?php echo htmlspecialchars($tenant['name']); ?></div>
                    <div class="nav-links">
                        <a href="/dashboard">Dashboard</a>
                        <a href="/flocks">Flocks</a>
                        <a href="/production">Production</a>
                        <a href="/reports">Reports</a>
                        <a href="/logout">Logout</a>
                    </div>
                </nav>
            </div>
        </header>

        <main class="dashboard">
            <div class="container">
                <h1>Welcome to your farm dashboard</h1>
                <p>Plan: <?php echo htmlspecialchars($tenant['tier']); ?> | Status: <?php echo htmlspecialchars($tenant['status']); ?></p>

                <div class="dashboard-grid">
                    <div class="card">
                        <h3>Total Birds</h3>
                        <div class="metric">0</div>
                    </div>
                    <div class="card">
                        <h3>Today's Eggs</h3>
                        <div class="metric">0</div>
                    </div>
                    <div class="card">
                        <h3>Feed Stock</h3>
                        <div class="metric">0 kg</div>
                    </div>
                    <div class="card">
                        <h3>Pending Tasks</h3>
                        <div class="metric">0</div>
                    </div>
                </div>
            </div>
        </main>
    </body>
    </html>
    <?php
}
