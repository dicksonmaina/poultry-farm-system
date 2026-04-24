<?php
/**
 * Login Page
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = getDB()->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        $log = getDB()->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (?, 'login', ?)");
        $log->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
        
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<style>
    :root {
        --bg-dark: #0a0f0d;
        --card-bg: #162019;
        --green-accent: #3ddc6e;
    }
    body {
        background-color: var(--bg-dark);
    }
    .login-bg {
        background: linear-gradient(135deg, #0d1a12 0%, #162019 100%);
    }
</style>

<div class="min-h-screen flex items-center justify-center login-bg">
    <div class="card p-8 rounded-lg shadow-2xl w-96" style="background-color: #162019">
        <div class="text-center mb-6">
            <i class="fas fa-drumstick-bite text-5xl" style="color: var(--green-accent)"></i>
            <h1 class="text-2xl font-bold mt-2 text-white">Poultry Farm</h1>
            <p class="text-gray-400">Management System</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-900/30 border border-red-600/50 text-red-400 px-4 py-2 rounded mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-400 text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" required
                    class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-green-500">
            </div>
            <div class="mb-6">
                <label class="block text-gray-400 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-green-500">
            </div>
            <button type="submit" class="w-full text-white py-2 rounded-lg font-bold hover:opacity-90" style="background-color: var(--green-accent)">
                Login
            </button>
        </form>
        <p class="text-center text-gray-500 text-sm mt-4">Default: admin / admin123</p>
    </div>
</div>