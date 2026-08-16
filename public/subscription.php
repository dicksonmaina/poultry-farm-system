<?php
/**
 * Tenant subscription management page.
 *
 * Shows current plan, payment history, and plan change options.
 */

require_once __DIR__ . '/../config/tenant-api-bootstrap.php';

$tenantApp = new TenantApiBootstrap();
$farmId = $tenantApp->requireTenant();
$tenant = $tenantApp->getTenant();

$paymentHistory = [];
try {
    $stmt = $tenantApp->getTenantConnection($farmId)->prepare('
        SELECT id, amount, method, reference, status, paid_at
        FROM payments
        WHERE farm_id = :farm_id
        ORDER BY paid_at DESC
        LIMIT 20
    ');
    $stmt->execute(['farm_id' => $farmId]);
    $paymentHistory = $stmt->fetchAll();
} catch (Throwable $e) {
    $paymentHistory = [];
}

renderSubscriptionPage($tenant, $paymentHistory);

function renderSubscriptionPage(array $tenant, array $paymentHistory): void {
    $title = 'Subscription - ' . $tenant['name'] . ' - Poultry Manager Cloud';
    $plan = $tenant['tier'] ?? 'free';
    $status = $tenant['status'] ?? 'active';
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
            .container { max-width: 960px; margin: 0 auto; padding: 2rem; }
            header { background: #2c3e50; color: white; padding: 1rem 0; }
            nav { display: flex; justify-content: space-between; align-items: center; }
            .logo { font-size: 1.5rem; font-weight: bold; }
            .nav-links a { color: white; text-decoration: none; margin-left: 1.5rem; }
            .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 1.5rem; }
            .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; }
            .badge-active { background: #d4edda; color: #155724; }
            .badge-free { background: #e2e3e5; color: #383d41; }
            table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
            th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #eee; }
            th { color: #2c3e50; }
            .upgrade-button { display: inline-block; background: #3498db; color: white; padding: 0.6rem 1rem; text-decoration: none; border-radius: 4px; margin-top: 1rem; }
        </style>
    </head>
    <body>
        <header>
            <div class="container">
                <nav>
                    <div class="logo">🐔 <?php echo htmlspecialchars($tenant['name']); ?></div>
                    <div class="nav-links">
                        <a href="/dashboard">Dashboard</a>
                        <a href="/subscription">Subscription</a>
                        <a href="/support">Support</a>
                        <a href="/logout">Logout</a>
                    </div>
                </nav>
            </div>
        </header>

        <main class="container">
            <h1>Subscription</h1>
            <p>Manage your plan, payments, and upgrades.</p>

            <div class="section">
                <h2>Current Plan</h2>
                <p><strong>Plan:</strong> <?php echo htmlspecialchars(ucfirst($plan)); ?></p>
                <p><strong>Status:</strong>
                    <span class="badge <?php echo $status === 'active' ? 'badge-active' : 'badge-free'; ?>">
                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                    </span>
                </p>

                <a class="upgrade-button" href="/support?topic=upgrade">Request upgrade</a>
            </div>

            <div class="section">
                <h2>Payment History</h2>
                <?php if (!$paymentHistory): ?>
                    <p>No payments recorded yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentHistory as $payment): ?>
                                <tr>
                                    <td><?php echo (int) $payment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['method']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['reference']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['status']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['paid_at'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </body>
    </html>
    <?php
}
