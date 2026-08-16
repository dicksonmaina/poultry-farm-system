<?php
/**
 * Public signup / onboarding entrypoint for Poultry Manager Cloud.
 *
 * Creates tenant record, seeds subscription, and redirects into tenant context.
 */

require_once __DIR__ . '/../config/tenant-api-bootstrap.php';

$tenantApp = new TenantApiBootstrap();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $farmName = trim($_POST['farm_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $plan = $_POST['plan'] ?? 'starter';

    if (!$name || !$email || !$farmName || !$password) {
        $error = 'Name, email, farm name, and password are required.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $farmId = $tenantApp->onboardFarm([
                'name' => $farmName,
                'owner_name' => $name,
                'email' => $email,
                'phone' => $phone,
                'plan' => $plan,
                'password_hash' => $passwordHash,
            ]);

            // Auto-login after signup
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['farm_id'] = $farmId;

            $success = 'Account created. Redirecting to your dashboard...';
            header('Location: /dashboard');
            exit;
        } catch (Throwable $e) {
            $error = 'Signup failed: ' . $e->getMessage();
        }
    }
}

renderSignupPage($error, $success);

function renderSignupPage(?string $error, ?string $success): void {
    $title = 'Sign Up - Poultry Manager Cloud';
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
            .container { max-width: 480px; margin: 0 auto; padding: 2rem; }
            header { background: #2c3e50; color: white; padding: 1rem 0; }
            nav a { color: white; text-decoration: none; margin-right: 1rem; }
            .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 1.5rem; }
            label { display: block; margin-bottom: 0.25rem; font-weight: 600; }
            input, select { width: 100%; padding: 0.6rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; }
            button { width: 100%; background: #27ae60; color: white; padding: 0.75rem; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
            .error { color: #c0392b; margin-bottom: 1rem; }
            .success { color: #27ae60; margin-bottom: 1rem; }
        </style>
    </head>
    <body>
        <header>
            <div class="container">
                <nav>
                    <a href="/">← Back to home</a>
                </nav>
            </div>
        </header>

        <main class="container">
            <h1>Create your farm account</h1>
            <p>Start with a free trial. No credit card required.</p>

            <div class="card">
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="post">
                    <label for="name">Your name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required />

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />

                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" />

                    <label for="farm_name">Farm name</label>
                    <input type="text" id="farm_name" name="farm_name" value="<?php echo htmlspecialchars($_POST['farm_name'] ?? ''); ?>" required />

                    <label for="plan">Plan</label>
                    <select id="plan" name="plan">
                        <option value="free">Free</option>
                        <option value="starter" selected>Starter</option>
                        <option value="professional">Professional</option>
                    </select>

                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8" />

                    <button type="submit">Create account</button>
                </form>
            </div>
        </main>
    </body>
    </html>
    <?php
}
