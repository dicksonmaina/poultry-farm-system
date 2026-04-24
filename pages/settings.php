<?php
/**
 * Settings Page
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    if ($_POST['action'] === 'add_user') {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['username'], $hash, $_POST['full_name'], $_POST['role'], $_POST['email'], $_POST['phone']]);
        $message = "User added successfully";
    } elseif ($_POST['action'] === 'update_profile') {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $_SESSION['user_id']]);
        $_SESSION['full_name'] = $_POST['full_name'];
        $message = "Profile updated";
    }
}

$users = getDB()->query("SELECT id, username, full_name, role, email, status, last_login FROM users ORDER BY created_at DESC")->fetchAll();
$currentUser = getDB()->prepare("SELECT * FROM users WHERE id = ?")->execute([$_SESSION['user_id']])->fetch();
?>
<style>:root{--bg-dark:#0a0f0d;--card-bg:#162019;--green-accent:#3ddc6e}body{background-color:var(--bg-dark)}</style>

<div class="mb-6">
    <h1 class="text-2xl font-bold" style="color:var(--green-accent)">Settings</h1>
    <p class="text-gray-400">Manage system settings</p>
</div>

<?php if (isset($message)): ?>
<div class="mb-4 p-3 rounded-lg bg-green-900/30 border border-green-600/50 text-green-400"><?= $message ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Profile -->
    <div class="card rounded-lg p-4" style="background-color:#162019">
        <h2 class="text-lg font-bold mb-4 text-white"><i class="fas fa-user"></i> My Profile</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Username</label>
                <input type="text" value="<?= htmlspecialchars($_SESSION['username']) ?>" disabled class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-gray-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($_SESSION['full_name']) ?>" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Email</label>
                <input type="email" name="email" placeholder="email@example.com" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Phone</label>
                <input type="text" name="phone" placeholder="+254..." class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold" style="background-color:var(--green-accent)">Update Profile</button>
        </form>
    </div>

    <!-- Users -->
    <div class="card rounded-lg p-4" style="background-color:#162019">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-white"><i class="fas fa-users"></i> User Management</h2>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <button onclick="document.getElementById('addUserModal').classList.remove('hidden')" class="px-3 py-1 rounded text-sm font-bold text-white" style="background-color:var(--green-accent)">
                <i class="fas fa-plus"></i> Add User
            </button>
            <?php endif; ?>
        </div>
        <table class="min-w-full">
            <thead style="background-color:#1a2a1f">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase">User</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase">Role</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="px-3 py-2">
                        <div class="text-white"><?= htmlspecialchars($u['full_name']) ?></div>
                        <div class="text-gray-500 text-xs"><?= htmlspecialchars($u['username']) ?></div>
                    </td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded <?= $u['role'] === 'admin' ? 'bg-purple-900/50 text-purple-300' : 'bg-gray-700 text-gray-300' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded <?= $u['status'] === 'active' ? 'bg-green-900/50 text-green-300' : 'bg-red-900/50 text-red-300' ?>">
                            <?= ucfirst($u['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- System Info -->
<div class="card rounded-lg p-4 mt-6" style="background-color:#162019">
    <h2 class="text-lg font-bold mb-4 text-white"><i class="fas fa-info-circle"></i> System Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <p class="text-gray-400 text-sm">Version</p>
            <p class="text-white">1.0.0</p>
        </div>
        <div>
            <p class="text-gray-400 text-sm">Database</p>
            <p class="text-white">poultry_farm</p>
        </div>
        <div>
            <p class="text-gray-400 text-sm">PHP Version</p>
            <p class="text-white"><?= phpversion() ?></p>
        </div>
        <div>
            <p class="text-gray-400 text-sm">Server</p>
            <p class="text-white">Apache/MySQL</p>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="card p-6 rounded-lg w-full max-w-md" style="background-color:#162019">
        <h2 class="text-xl font-bold mb-4 text-white">Add New User</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Username</label>
                <input type="text" name="username" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Full Name</label>
                <input type="text" name="full_name" required class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Password</label>
                <input type="password" name="password" required min="6" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Role</label>
                <select name="role" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
                    <option value="staff">Staff</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Email</label>
                <input type="email" name="email" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Phone</label>
                <input type="text" name="phone" class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white font-bold" style="background-color:var(--green-accent)">Add User</button>
            </div>
        </form>
    </div>
</div>