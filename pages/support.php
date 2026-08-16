<?php
/**
 * Support & Subscription Requests
 */

require_once '../config.php';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $farm = trim($_POST['farm_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $plan = trim($_POST['plan'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $farm && $contact && $plan) {
        $stmt = getDB()->prepare("
            INSERT INTO support_requests (name, farm_name, contact, plan_interest, message, status, created_at)
            VALUES (:name, :farm, :contact, :plan, :message, 'new', NOW())
        ");
        $stmt->execute([
            ':name' => $name,
            ':farm' => $farm,
            ':contact' => $contact,
            ':plan' => $plan,
            ':message' => $message
        ]);
        $success = 'Request received. We will contact you shortly.';
    } else {
        $error = 'Please fill all required fields.';
    }
}
?>
<style>
    .support-card { background-color: #162019; border-radius: 8px; padding: 24px; }
    .support-title { color: #3ddc6e; }
</style>

<div class="mb-6">
    <h1 class="text-2xl font-bold support-title">Support & Subscriptions</h1>
    <p class="text-gray-400">Request setup help, custom modules, or ongoing support.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="support-card">
        <h3 class="text-white font-bold mb-2">Installation & Setup</h3>
        <p class="text-gray-400 text-sm mb-2">From $50</p>
        <p class="text-gray-500 text-sm">Server setup, database setup, and admin handover.</p>
    </div>
    <div class="support-card">
        <h3 class="text-white font-bold mb-2">Custom Module</h3>
        <p class="text-gray-400 text-sm mb-2">From $100</p>
        <p class="text-gray-500 text-sm">Custom reports, integrations, or workflow changes.</p>
    </div>
    <div class="support-card">
        <h3 class="text-white font-bold mb-2">Support Subscription</h3>
        <p class="text-gray-400 text-sm mb-2">From $20/month</p>
        <p class="text-gray-500 text-sm">Priority support, updates guidance, and monitoring.</p>
    </div>
</div>

<div class="support-card">
    <h2 class="text-lg font-bold text-white mb-4">Request Support</h2>

    <?php if ($success): ?>
        <div class="p-3 rounded bg-green-900/40 border border-green-600/50 text-green-200 mb-4"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-3 rounded bg-red-900/40 border border-red-600/50 text-red-200 mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-gray-300 text-sm mb-1">Name *</label>
            <input type="text" name="name" class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2" required>
        </div>
        <div>
            <label class="block text-gray-300 text-sm mb-1">Farm Name *</label>
            <input type="text" name="farm_name" class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2" required>
        </div>
        <div>
            <label class="block text-gray-300 text-sm mb-1">Contact *</label>
            <input type="text" name="contact" class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2" placeholder="Phone, email, or WhatsApp" required>
        </div>
        <div>
            <label class="block text-gray-300 text-sm mb-1">Plan Interest *</label>
            <select name="plan" class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2" required>
                <option value="">Select plan</option>
                <option value="Installation">Installation & Setup</option>
                <option value="Custom Module">Custom Module</option>
                <option value="Subscription">Support Subscription</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div>
            <label class="block text-gray-300 text-sm mb-1">Message</label>
            <textarea name="message" class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2" rows="4"></textarea>
        </div>
        <button type="submit" class="px-4 py-2 rounded text-black font-bold" style="background-color: var(--green-accent); color: #000;">Submit Request</button>
    </form>
</div>
