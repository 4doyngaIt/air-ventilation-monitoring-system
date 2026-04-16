<?php
$page_title='Dashboard';
include 'header.php';
$uid=$_SESSION['user_id'];
$ts=$conn->query("SELECT COUNT(*) FROM sensors")->fetch_row()[0];
$as=$conn->query("SELECT COUNT(*) FROM sensors WHERE status='Active'")->fetch_row()[0];
$is=$conn->query("SELECT COUNT(*) FROM sensors WHERE status='Inactive'")->fetch_row()[0];
$von=$conn->query("SELECT COUNT(DISTINCT sr.sensor_id) FROM sensor_readings sr INNER JOIN(SELECT sensor_id,MAX(created_at)md FROM sensor_readings GROUP BY sensor_id)l ON sr.sensor_id=l.sensor_id AND sr.created_at=l.md WHERE sr.vent_state='on'")->fetch_row()[0];
$rain=$conn->query("SELECT COUNT(DISTINCT sr.sensor_id) FROM sensor_readings sr INNER JOIN(SELECT sensor_id,MAX(created_at)md FROM sensor_readings GROUP BY sensor_id)l ON sr.sensor_id=l.sensor_id AND sr.created_at=l.md WHERE sr.rainfall>0")->fetch_row()[0];
$avgT=$conn->query("SELECT AVG(temperature) FROM sensor_readings sr INNER JOIN(SELECT sensor_id,MAX(created_at)md FROM sensor_readings GROUP BY sensor_id)l ON sr.sensor_id=l.sensor_id AND sr.created_at=l.md")->fetch_row()[0]??0;
$hist=[];$r=$conn->query("SELECT DATE_FORMAT(created_at,'%H:00')hour,COUNT(*)actions FROM activity_logs WHERE created_at>=DATE_SUB(NOW(),INTERVAL 24 HOUR) GROUP BY hour ORDER BY created_at");
if($r)while($row=$r->fetch_assoc())$hist[]=$row;
$total_users=$conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
?>
<div class="stats-grid stagger">
  <div class="stat-card fade-up green">
    <span class="stat-icon">🌿</span>
    <div class="stat-label">Active Sensors</div>
    <div class="stat-val"><?=$as?><span class="unit">/ <?=$ts?></span></div>
    <div class="stat-sub"><?=$is?> inactive</div>
  </div>
  <div class="stat-card fade-up">
    <span class="stat-icon">💨</span>
    <div class="stat-label">Vents Open</div>
    <div class="stat-val"><?=$von?></div>
    <div class="stat-sub">currently running</div>
  </div>
  <div class="stat-card fade-up amber">
    <span class="stat-icon">🌡️</span>
    <div class="stat-label">Avg Temperature</div>
    <div class="stat-val"><?=number_format($avgT,1)?><span class="unit">°C</span></div>
    <div class="stat-sub">across all sensors</div>
  </div>
  <div class="stat-card fade-up blue">
    <span class="stat-icon">🌧️</span>
    <div class="stat-label">Rain Detected</div>
    <div class="stat-val"><?=$rain?></div>
    <div class="stat-sub">sensors with rainfall</div>
  </div>
</div>
<div class="auto-note"><i class="fas fa-robot"></i> <strong>Auto Mode Active:</strong> Vents open automatically above threshold temperature and close when rain is detected.</div>
<div class="charts-grid">
  <div class="card">
    <div class="card-head"><div class="card-title"><i class="fas fa-chart-pie" style="color:var(--clay)"></i> Sensor Status</div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="pieChart"></canvas></div></div>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title"><i class="fas fa-chart-bar" style="color:var(--clay)"></i> Activity (Last 24h)</div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="barChart"></canvas></div></div>
  </div>
</div>
<div class="card">
  <div class="card-head"><div class="card-title"><i class="fas fa-bolt" style="color:var(--clay)"></i> Quick Actions</div></div>
  <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="/hangin/app/admin/user-management.php" class="btn btn-primary"><i class="fas fa-users"></i> Manage Users</a>
    <a href="/hangin/app/admin/view-user.php" class="btn btn-secondary"><i class="fas fa-eye"></i> View All Sensors</a>
    <a href="/hangin/app/admin/activity-logs.php" class="btn btn-secondary"><i class="fas fa-history"></i> Activity Logs</a>
  </div>
</div>
<script>
new Chart(document.getElementById('pieChart'),{type:'doughnut',data:{labels:['Active','Inactive'],
  datasets:[{data:[<?=$as?>,<?=$is?>],backgroundColor:['#4a7c28','#94a3b8'],borderColor:['#fff','#fff'],borderWidth:3,hoverOffset:4}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{padding:20,usePointStyle:true}}},cutout:'65%'}});
<?php if(!empty($hist)):?>
new Chart(document.getElementById('barChart'),{type:'bar',data:{
  labels:<?=json_encode(array_column($hist,'hour'))?>,
  datasets:[{label:'Actions',data:<?=json_encode(array_column($hist,'actions'))?>,backgroundColor:'#c4622d',borderRadius:6,borderSkipped:false}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}}}});
<?php else:?>
new Chart(document.getElementById('barChart'),{type:'bar',data:{labels:['No Data'],datasets:[{data:[0],backgroundColor:'#e0d6cc',borderRadius:6}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},title:{display:true,text:'No activity in the last 24 hours',color:'#7a6355'}},scales:{y:{beginAtZero:true,max:1},x:{grid:{display:false}}}}});
<?php endif;?>
</script>
<?php include 'footer.php';?>
