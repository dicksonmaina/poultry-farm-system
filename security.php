<?php
/**
 * Security Hardening - Poultry Farm Management System
 * CSRF protection, session security, rate limiting
 */

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// CSRF Token Management
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
}

// Rate limiting for login attempts
function check_rate_limit($key, $max_attempts = 5, $window_seconds = 300) {
    $now = time();
    $file = sys_get_temp_dir() . '/rate_limit_' . md5($key) . '.json';

    $attempts = [];
    if (file_exists($file)) {
        $attempts = json_decode(file_get_contents($file), true) ?: [];
    }

    $attempts = array_filter($attempts, function($ts) use ($now, $window_seconds) {
        return ($now - $ts) < $window_seconds;
    });

    if (count($attempts) >= $max_attempts) {
        $oldest = min($attempts);
        $wait = $window_seconds - ($now - $oldest);
        http_response_code(429);
        die("Too many login attempts. Try again in {$wait} seconds.");
    }

    $attempts[] = $now;
    file_put_contents($file, json_encode($attempts));

    return true;
}

// Input sanitization helpers
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_password_strength($password) {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain an uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain a lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain a number";
    }
    return true;
}

// Auth helper
function require_auth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
}

function require_admin() {
    require_auth();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('Admin access required');
    }
}
