<?php
require_once '../includes/db.php';
session_start();

// KPI Queries
$total_birds = $pdo->query("SELECT COALESCE(SUM(current_count),0) as t FROM flocks WHERE status='active'")->fetch()['t'];
$eggs_today = $pdo->query("SELECT COALESCE(SUM(total_eggs),0) as t FROM egg_production WHERE production_date=CURDATE()")->fetch()['t'];
$feed_today = $pdo->query("SELECT COALESCE(SUM(quantity_kg),0) as t FROM feed_consumption WHERE consumption_date=CURDATE()")->fetch()['t'];
$active_flocks = $pdo->query("SELECT COUNT(*) as t FROM flocks WHERE status='active'")->fetch()['t'];
$revenue_mtd = $pdo->query("SELECT COALESCE(SUM(total_price),0) as t FROM sales WHERE MONTH(sale_date)=MONTH(NOW()) AND YEAR(sale_date)=YEAR(NOW())")->fetch()['t'];
$mortality_week = $pdo->query("SELECT COALESCE(SUM(quantity),0) as t FROM mortality WHERE death_date>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch()['t'];
$mortality_rate = $total_birds > 0 ? round(($mortality_week/$total_birds)*100,1) : 0;

// Egg chart data
$egg_rows = $pdo->query("SELECT production_date as d, SUM(total_eggs) as e FROM egg_production WHERE production_date>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY production_date ORDER BY production_date ASC")->fetchAll();
$egg_labels = json_encode(array_column($egg_rows,'d'));
$egg_data = json_encode(array_map('intval',array_column($egg_rows,'e')));

// Alerts
$alerts = [];
$low_feed = $pdo->query("SELECT name FROM flocks WHERE status='active' AND current_count > 0 LIMIT 3")->fetchAll();
if($eggs_today == 0) $alerts[] = ['type'=>'warning','msg'=>'No egg collection recorded today'];
if($mortality_week > 5) $alerts[] = ['type'=>'danger','msg'=>"$mortality_week bird deaths this week"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kiambo Poultry Farm — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#080d0b;--card:#111d17;--green:#22c55e;--text:#ecfdf5;--text2:#6b7280;--border:rgba(34,197,94,0.12)}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;display:flex;min-height:100vh}
.sidebar{width:220px;background:#080f0b;border-right:1px solid var(--border);padding:20px 0;flex-shrink:0;display:flex;flex-direction:column}
.logo{padding:0 16px 16px;border-bottom:1px solid var(--border);margin-bottom:12px}
.logo-title{font-family:'Space Grotesk',sans-serif;font-size:16px;font-weight:700}
.logo-sub{font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.1em}
.nav-label{font-size:9px;color:var(--text2);text-transform:uppercase;letter-spacing:.1em;padding:8px 16px 4px}
.nav-item{display:flex;align-items:center;gap:8px;padding:8px 16px;font-size:13px;color:var(--text2);text-decoration:none;transition:.15s;border-radius:0}
.nav-item:hover,.nav-item.active{color:var(--green);background:rgba(34,197,94,0.06)}
.status{margin:12px 16px;padding:8px;background:rgba(34,197,94,0.06);border:1px solid var(--border);border-radius:8px;font-size:11px;color:var(--text2);display:flex;align-items:center;gap:6px}
.dot{width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 6px var(--green)}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}
.header{padding:16px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.page-title{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700}
.page-sub{font-size:12px;color:var(--text2)}
.content{flex:1;overflow-y:auto;padding:20px 24px}
.kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px;position:relative;overflow:hidden}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--green),transparent)}
.kpi-icon{font-size:20px;margin-bottom:8px}
.kpi-label{font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px}
.kpi-value{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;margin-bottom:4px}
.kpi-trend{font-size:10px;color:var(--green)}
.charts-row{display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-bottom:12px}
.chart-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px}
.chart-title{font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:600;margin-bottom:14px}
.alerts-section{margin-bottom:12px}
.alert{padding:10px 14px;border-radius:8px;border:1px solid;margin-bottom:6px;font-size:12px}
.alert.warning{border-color:rgba(245,158,11,.3);background:rgba(245,158,11,.05);color:#f59e0b}
.alert.danger{border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.05);color:#ef4444}
.alert.success{border-color:rgba(34,197,94,.3);background:rgba(34,197,94,.05);color:var(--green)}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="logo">
    <div style="font-size:24px;margin-bottom:4px">🐔</div>
    <div class="logo-title">Kiambo PF</div>
    <div class="logo-sub">Farm Intelligence</div>
  </div>
  <div class="status"><div class="dot"></div> System Live</div>
  <div class="nav-label">Operations</div>
  <a href="dashboard.php" class="nav-item active">📊 Dashboard</a>
  <a href="../flocks.php" class="nav-item">🐦 Flocks</a>
  <a href="../birds.php" class="nav-item">🐓 Birds</a>
  <a href="../egg_collection.php" class="nav-item">🥚 Egg Production</a>
  <div class="nav-label">Management</div>
  <a href="../feed.php" class="nav-item">🌾 Feed</a>
  <a href="../vaccinations.php" class="nav-item">💉 Vaccinations</a>
  <a href="../mortality.php" class="nav-item">📋 Mortality</a>
  <div class="nav-label">Business</div>
  <a href="../finance.php" class="nav-item">💰 Finance</a>
  <a href="../sales.php" class="nav-item">🛒 Sales</a>
  <a href="../reports.php" class="nav-item">📄 Reports</a>
</aside>
<div class="main">
  <div class="header">
    <div>
      <div class="page-title">Farm Dashboard</div>
      <div class="page-sub">Kiambo Poultry Farm, Kiambu County</div>
    </div>
  </div>
  <div class="content">
    <div class="kpi-grid">
      <div class="kpi-card"><div class="kpi-icon">🐔</div><div class="kpi-label">Total Birds</div><div class="kpi-value"><?=number_format($total_birds)?></div><div class="kpi-trend">Active flocks</div></div>
      <div class="kpi-card"><div class="kpi-icon">🥚</div><div class="kpi-label">Eggs Today</div><div class="kpi-value"><?=number_format($eggs_today)?></div><div class="kpi-trend">Today's collection</div></div>
      <div class="kpi-card"><div class="kpi-icon">🌾</div><div class="kpi-label">Feed Today (kg)</div><div class="kpi-value"><?=number_format($feed_today,1)?></div><div class="kpi-trend">Within target</div></div>
      <div class="kpi-card"><div class="kpi-icon">🏠</div><div class="kpi-label">Active Flocks</div><div class="kpi-value"><?=$active_flocks?></div><div class="kpi-trend">All healthy</div></div>
      <div class="kpi-card"><div class="kpi-icon">💰</div><div class="kpi-label">Revenue MTD</div><div class="kpi-value" style="font-size:16px">KSH <?=number_format($revenue_mtd)?></div><div class="kpi-trend">This month</div></div>
      <div class="kpi-card"><div class="kpi-icon">📉</div><div class="kpi-label">Mortality Rate</div><div class="kpi-value"><?=$mortality_rate?>%</div><div class="kpi-trend">Last 7 days</div></div>
    </div>
    <div class="alerts-section">
      <?php if(empty($alerts)):?><div class="alert success">✅ All systems normal — no alerts</div><?php endif;?>
      <?php foreach($alerts as $a):?><div class="alert <?=$a['type']?>"><?=$a['msg']?></div><?php endforeach;?>
    </div>
    <div class="charts-row">
      <div class="chart-card">
        <div class="chart-title">Egg Production — Last 30 Days</div>
        <canvas id="eggChart" height="120"></canvas>
      </div>
      <div class="chart-card">
        <div class="chart-title">Quick Stats</div>
        <div style="color:var(--text2);font-size:13px;line-height:2">
          <div>Total birds: <strong style="color:var(--text)"><?=number_format($total_birds)?></strong></div>
          <div>Active flocks: <strong style="color:var(--text)"><?=$active_flocks?></strong></div>
          <div>Eggs today: <strong style="color:var(--text)"><?=number_format($eggs_today)?></strong></div>
          <div>Feed today: <strong style="color:var(--text)"><?=$feed_today?> kg</strong></div>
          <div>Deaths this week: <strong style="color:var(--text)"><?=$mortality_week?></strong></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
Chart.defaults.color='#6b7280';
const ctx=document.getElementById('eggChart').getContext('2d');
const g=ctx.createLinearGradient(0,0,0,200);
g.addColorStop(0,'rgba(34,197,94,0.3)');g.addColorStop(1,'rgba(34,197,94,0)');
new Chart(ctx,{type:'line',data:{labels:<?=$egg_labels?>,datasets:[{data:<?=$egg_data?>,borderColor:'#22c55e',backgroundColor:g,fill:true,tension:0.4,pointRadius:0,borderWidth:2}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(34,197,94,0.08)'},ticks:{maxTicksLimit:6}},y:{grid:{color:'rgba(34,197,94,0.08)'},beginAtZero:true}}}});
</script>
</body>
</html>
