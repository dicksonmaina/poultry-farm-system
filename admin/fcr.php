<?php
require_once "../includes/db.php";
$flock_id = $_GET['flock_id'] ?? null;
if ($flock_id) {
    $stmt = $pdo->prepare("SELECT f.name, SUM(fc.quantity_kg) as total_feed, SUM(ep.total_eggs) as total_eggs, ROUND(SUM(fc.quantity_kg)/SUM(ep.total_eggs),2) as fcr FROM flocks f LEFT JOIN feed_consumption fc ON f.id=fc.flock_id LEFT JOIN egg_production ep ON f.id=ep.flock_id WHERE f.id=? GROUP BY f.id");
    $stmt->execute([$flock_id]);
    $result = $stmt->fetch();
}
$flocks = $pdo->query("SELECT id,name FROM flocks WHERE status='active'")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>FCR Calculator</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<style>:root{--bg:#080d0b;--card:#111d17;--green:#22c55e;--text:#ecfdf5;--border:rgba(34,197,94,0.12)}body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);margin:0;display:flex}.sidebar{width:250px;background:#080f0b;padding:20px}.main{flex:1;padding:30px}.card{background:var(--card);padding:20px;border-radius:12px;border:1px solid var(--border)}.form-group{margin-bottom:15px}label{display:block;margin-bottom:5px;color:#6b7280}select{width:100%;padding:12px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:var(--text)}.btn{width:100%;padding:12px;border:none;border-radius:8px;background:var(--green);color:var(--bg);font-weight:600;cursor:pointer}.nav-link{display:block;padding:12px;margin:8px 0;background:var(--card);color:var(--text);border-radius:8px;text-decoration:none}.nav-link:hover{background:var(--green);color:var(--bg)}</style></head><body>
<div class="sidebar"><div style="font-family:'Space Grotesk',sans-serif;font-size:1.5rem;color:var(--green);margin-bottom:30px">AgroTech</div>
<a href="dashboard.php" class="nav-link">Dashboard</a><a href="fcr.php" class="nav-link">FCR Calculator</a><a href="jarvis.php" class="nav-link">JARVIS AGI</a></div>
<div class="main"><h1 style="font-family:'Space Grotesk',sans-serif;color:var(--green)">FCR Calculator</h1>
<div class="card" style="max-width:500px"><form method="GET"><div class="form-group"><label>Select Flock</label><select name="flock_id"><option value="">-- Select --</option><?php foreach($flocks as $f):?><option value="<?=$f['id']?>"<?=$flock_id==$f['id']?'selected':''?>><?=htmlspecialchars($f['name'])?></option><?php endforeach;?></select></div><button class="btn">Calculate</button></form></div>
<?php if($flock_id && $result):?><div class="card" style="max-width:500px;margin-top:20px"><h3>Result: <?=htmlspecialchars($result['name'])?></h3><div style="font-size:2.5rem;color:var(--green);font-weight:700"><?=round($result['fcr'] ?? 0, 2)?></div><p style="color:#6b7280">kg feed per egg</p></div><?php endif;?></div></body></html>