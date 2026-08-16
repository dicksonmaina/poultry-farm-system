<?php
require_once '../config.php';
$pdo = getDB();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $allowed = ['new','contacted','closed'];
    if ($id > 0 && in_array($status, $allowed, true)) {
        $stmt = $pdo->prepare('UPDATE support_requests SET status=:status WHERE id=:id');
        $stmt->execute([':status'=>$status, ':id'=>$id]);
        $msg = 'Status updated.';
    } else {
        $err = 'Invalid update.';
    }
}

$stmt = $pdo->query('SELECT * FROM support_requests ORDER BY created_at DESC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    .admin-card{background:#162019;border-radius:8px;padding:16px;}
    table{width:100%;border-collapse:collapse;}
    th,td{padding:8px;border-bottom:1px solid #243a2c;text-align:left;vertical-align:top;}
    th{color:#3ddc6e;}
    .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;}
    .pill-new{background:#064e3b;color:#ecfdf5;}
    .pill-contacted{background:#1e3a8a;color:#eff6ff;}
    .pill-closed{background:#3f3f46;color:#f4f4f5;}
    .actions{display:flex;gap:8px;flex-wrap:wrap;}
    .actions form{display:inline;}
    .muted{color:#9ca3af;font-size:13px;}
</style>
<div class="admin-card">
    <h1 class="text-xl font-bold mb-4">Support Requests</h1>
    <?php if ($msg): ?><div class="p-2 rounded bg-green-900/40 border border-green-600/50 text-green-200 mb-3"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="p-2 rounded bg-red-900/40 border border-red-600/50 text-red-200 mb-3"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Farm</th>
                    <th>Contact</th>
                    <th>Plan</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['farm_name']) ?></td>
                        <td><?= htmlspecialchars($r['contact']) ?></td>
                        <td><?= htmlspecialchars($r['plan_interest']) ?></td>
                        <td class="muted"><?= htmlspecialchars($r['message'] ?? '') ?></td>
                        <td><span class="pill pill-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                        <td>
                            <div class="actions">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <select name="status" class="rounded bg-gray-800 border border-gray-700 text-white px-2 py-1">
                                        <?php foreach (['new','contacted','closed'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="px-2 py-1 rounded text-black" style="background-color: var(--green-accent); color: #000;">Save</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="9" class="muted">No support requests yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
