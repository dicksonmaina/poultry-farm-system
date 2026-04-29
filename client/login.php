<?php
session_start();
require_once '../config.php';

$error = '';
$success = '';
$show_register = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            $stmt = getDB()->prepare("SELECT id, name, password FROM client_register WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['client_id'] = $user['id'];
                $_SESSION['client_name'] = $user['name'];
                header('Location: catalog.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Please fill in all fields';
        }
    } elseif (isset($_POST['register'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($name && $email && $phone && $password && $confirm_password) {
            if ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters';
            } else {
                // Check if email already exists
                $stmt = getDB()->prepare("SELECT id FROM client_register WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already registered';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = getDB()->prepare("INSERT INTO client_register (name, email, phone, password) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $hashed_password]);
                    $success = 'Registration successful! Please login.';
                    $show_register = false;
                }
            }
        } else {
            $error = 'Please fill in all fields';
        }
    }
}

// If already logged in, redirect to catalog
if (isset($_SESSION['client_id'])) {
    header('Location: catalog.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poultry Farm - Client Login</title>
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
<body class="text-gray-100 min-h-screen flex items-center justify-center bg-gray-900">
    <div class="w-full max-w-md p-6 space-y-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold accent">Poultry Farm Client Portal</h1>
            <p class="text-gray-400">Access your account to place orders and track deliveries</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-600/20 border border-red-500 text-red-400 px-4 py-3 rounded-md">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-600/20 border border-green-500 text-green-400 px-4 py-3 rounded-md">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="card p-6 rounded-lg space-y-4">
            <div class="flex justify-center space-x-4">
                <button id="loginBtn" class="px-4 py-2 text-sm font-medium <?=$_POST['register'] ?? false ? 'text-gray-400' : 'text-white accent-bg'?> rounded-lg transition-colors"
                    >Login</button>
                <button id="registerBtn" class="px-4 py-2 text-sm font-medium <?=$_POST['register'] ?? false ? 'text-white accent-bg' : 'text-gray-400'?> rounded-lg transition-colors"
                    >Register</button>
            </div>

            <form id="authForm" method="POST" action="" class="space-y-4">
                <div id="registerFields" class="hidden space-y-3">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Full Name</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                            value="<?=htmlspecialchars($_POST['name'] ?? '')?>">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                            value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required
                            class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                            value="<?=htmlspecialchars($_POST['phone'] ?? '')?>">
                    </div>
                </div>

                <div class="space-y-3">
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                    <input type="email" id="emailLogin" name="email" required
                        class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                        value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
                </div>

                <div class="space-y-3">
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                        <?=isset($_POST['register']) && $_POST['register'] ? '' : 'autofocus'?>>
                </div>

                <div id="confirmPasswordField" class="hidden space-y-3">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-1">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <?php if (isset($_POST['register']) && $_POST['register']): ?>
                    <button type="submit" name="register" class="w-full accent-bg text-white py-2 px-4 rounded-lg hover:opacity-90 transition-colors font-medium"
                        >Register</button>
                <?php else: ?>
                    <button type="submit" name="login" class="w-full accent-bg text-white py-2 px-4 rounded-lg hover:opacity-90 transition-colors font-medium"
                        >Login</button>
                <?php endif; ?>
            </form>

            <p class="text-center text-xs text-gray-500">
                Already have an account? <a href="#" id="toggleLink" class="text-green-400 hover:underline">Login</a>
            </p>
        </div>
    </div>

    <script>
        const showRegister = <?=json_encode(isset($_POST['register']) && $_POST['register'])?>;
        document.getElementById('loginBtn').addEventListener('click', () => {
            document.getElementById('registerFields').classList.add('hidden');
            document.getElementById('confirmPasswordField').classList.add('hidden');
            document.getElementById('registerBtn').classList.remove('text-white', 'accent-bg');
            document.getElementById('registerBtn').classList.add('text-gray-400');
            document.getElementById('loginBtn').classList.add('text-white', 'accent-bg');
            document.getElementById('loginBtn').classList.remove('text-gray-400');
            document.getElementById('toggleLink').textContent = 'Don\'t have an account? Register';
            document.getElementById('authForm').action = window.location.href;
            document.querySelector('button[name="login"]').style.display = 'inline-block';
            document.querySelector('button[name="register"]').style.display = 'none';
            showRegister = false;
        });
        document.getElementById('registerBtn').addEventListener('click', () => {
            document.getElementById('registerFields').classList.remove('hidden');
            document.getElementById('confirmPasswordField').classList.remove('hidden');
            document.getElementById('loginBtn').classList.remove('text-white', 'accent-bg');
            document.getElementById('loginBtn').classList.add('text-gray-400');
            document.getElementById('registerBtn').classList.add('text-white', 'accent-bg');
            document.getElementById('registerBtn').classList.remove('text-gray-400');
            document.getElementById('toggleLink').textContent = 'Already have an account? Login';
            document.getElementById('authForm').action = window.location.href;
            document.querySelector('button[name="login"]').style.display = 'none';
            document.querySelector('button[name="register"]').style.display = 'inline-block';
            showRegister = true;
        });
        document.getElementById('toggleLink').addEventListener('click', (e) => {
            e.preventDefault();
            if (document.getElementById('registerFields').classList.contains('hidden')) {
                document.getElementById('registerBtn').click();
            } else {
                document.getElementById('loginBtn').click();
            }
        });
        // Initialize state
        if (showRegister) {
            document.getElementById('registerBtn').click();
        }
    </script>
</body>
</html>