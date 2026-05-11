<?php
require_once "../includes/db.php";
$stmt = $pdo->query("SELECT SUM(current_count) as total_birds FROM flocks WHERE status="active"");
$total_birds = $stmt->fetch()["total_birds"];
$stmt = $pdo->query("SELECT COUNT(*) as active FROM flocks WHERE status='active'");
$active_flocks = $stmt->fetch()["active"];
$stmt = $pdo->query("SELECT SUM(total_eggs) FROM egg_production WHERE MONTH(production_date)=MONTH(NOW())");
$monthly_eggs = $stmt->fetch()[0] ?? 0;
$stmt = $pdo->query("SELECT SUM(total_price) FROM sales WHERE MONTH(sale_date)=MONTH(NOW())");
$revenue_mtd = $stmt->fetch()[0] ?? 0;
$stmt = $pdo->query("SELECT COUNT(*) FROM mortality");
$mortality_rate = round(($stmt->fetch()[0]/$total_birds)*100, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgroTech Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#080d0b;--card:#111d17;--sidebar:#080f0b;--green:#22c55e;--text:#ecfdf5;--muted:#6b7280;--border:rgba(34,197,94,0.12)}
        body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);margin:0;display:flex;min-height:100vh}
        .sidebar{width:250px;background:var(--sidebar);padding:20px}
        .main{flex:1;padding:30px}
        .kpi-value{color:var(--green);font-weight:700;font-size:1.8rem}
        .kpi-card{background:var(--card);padding:20px;border-radius:12px;border:1px solid var(--border);margin-bottom:20px}
        .nav-link{display:block;padding:12px;margin:8px 0;background:var(--card);color:var(--text);border-radius:8px;text-decoration:none}
        .nav-link:hover{background:var(--green);color:#080d0b}
    </style>
</head>
<body>
<div class="sidebar">
    <div style="font-family:'Space Grotesk',sans-serif;font-size:1.5rem;color:var(--green);margin-bottom:30px">AgroTech</div>
    <a href="dashboard.php" class="nav-link">Dashboard</a>
    <a href="fcr.php" class="nav-link">FCR Calculator</a>
    <a href="jarvis.php" class="nav-link">JARVIS AGI</a>
</div>
<div class="main">
    <h1 style="font-family:'Space Grotesk',sans-serif;color:var(--green)">Farm Dashboard</h1>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-top:20px">
        <div class="kpi-card"><div class="kpi-value"><?=number_format($total_birds)?></div><div>Total Birds</div></div>
        <div class="kpi-card"><div class="kpi-value"><?=$active_flocks?></div><div>Active Flocks</div></div>
        <div class="kpi-card"><div class="kpi-value"><?=number_format($monthly_eggs)?></div><div>Eggs This Month</div></div>
        <div class="kpi-card"><div class="kpi-value">KSH <?=number_format($revenue_mtd)?></div><div>Revenue MTD</div></div>
        <div class="kpi-card"><div class="kpi-value"><?=$mortality_rate?>%</div><div>Mortality Rate</div></div>
    </div>
</div>
</body>
</html>