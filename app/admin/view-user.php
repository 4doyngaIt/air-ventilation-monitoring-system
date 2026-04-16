<?php
$page_title='Sensor Monitor';
include 'header.php';
$vid=isset($_GET['view_user'])?intval($_GET['view_user']):null;
if(!$vid){header('Location:/hangin/app/admin/user-management.php');exit;}
$vu=db_one("SELECT * FROM users WHERE user_id=?","i",[$vid]);
if(!$vu){header('Location:/hangin/app/admin/user-management.php');exit;}
$sensors=db_q("SELECT s.*,r.temperature,r.humidity,r.weather_condition,r.vent_state,r.mode,r.rainfall,r.wind_speed,r.sunlight_intensity,r.created_at as last_read
  FROM sensors s LEFT JOIN(SELECT sr1.* FROM sensor_readings sr1 INNER JOIN(SELECT sensor_id,MAX(created_at)md FROM sensor_readings GROUP BY sensor_id)sr2 ON sr1.sensor_id=sr2.sensor_id AND sr1.created_at=sr2.md)r ON s.sensor_id=r.sensor_id ORDER BY s.sensor_id");
$hist=db_q("SELECT DATE_FORMAT(created_at,'%H:00')hour,AVG(temperature)temp,AVG(humidity)humidity FROM sensor_readings WHERE created_at>=DATE_SUB(NOW(),INTERVAL 24 HOUR) GROUP BY hour ORDER BY created_at ASC");
?>
<div class="view-banner"><div><i class="fas fa-eye"></i> Monitoring: <strong><?=e($vu['username'])?></strong>'s sensors</div>
  <a href="/hangin/app/admin/user-management.php" style="color:#fff;font-weight:600"><i class="fas fa-arrow-left"></i> Back</a></div>
<div class="sensor-grid">
<?php foreach($sensors as $s):
  $vs=strtolower($s['vent_state']??'off');
  $hasRain=($s['rainfall']??0)>0;
  $cardClass=$hasRain?'rain':($vs==='on'?'vent-on':'vent-off');
  $temp=$s['temperature']??0;
  $tempClass=$temp>35?'hot':($temp<20?'cool':'');
?>
<div class="sensor-card <?=$cardClass?>">
  <div class="s-head">
    <div><div class="s-id"><i class="fas fa-microchip"></i> Sensor #<?=$s['sensor_id']?></div>
      <div class="s-loc"><i class="fas fa-map-marker-alt"></i> <?=e($s['location'])?></div></div>
    <span class="vent-badge <?=$vs?>"><?=strtoupper($vs)?></span>
  </div>
  <div class="s-readings">
    <div class="s-reading"><div class="s-r-label">🌡️ Temp</div><div class="s-r-val <?=$tempClass?>"><?=$temp?°C:'—'?></div></div>
    <div class="s-reading"><div class="s-r-label">💧 Humidity</div><div class="s-r-val"><?=($s['humidity']??'—')?>%</div></div>
    <div class="s-reading"><div class="s-r-label">🌧️ Rainfall</div><div class="s-r-val <?=$hasRain?'wet':''?>"><?=($s['rainfall']??0)?> mm</div></div>
    <div class="s-reading"><div class="s-r-label">💨 Wind</div><div class="s-r-val"><?=($s['wind_speed']??0)?> m/s</div></div>
  </div>
  <div class="s-footer">
    <span class="s-condition"><?=$hasRain?'🌧️ Rainy':($temp>35?'🔥 Hot':'🌤️ Normal')?></span>
    <span><?=strtoupper($s['mode']??'N/A')?> mode</span>
  </div>
</div>
<?php endforeach;?>
</div>
<?php if(!empty($hist)):?>
<div class="card"><div class="card-head"><div class="card-title"><i class="fas fa-chart-area" style="color:var(--clay)"></i> 24h Environment History</div></div>
  <div class="card-body"><div class="chart-wrap"><canvas id="hChart"></canvas></div></div></div>
<script>
new Chart(document.getElementById('hChart'),{type:'line',data:{
  labels:<?=json_encode(array_column($hist,'hour'))?>,
  datasets:[{label:'Temperature °C',data:<?=json_encode(array_column($hist,'temp'))?>,borderColor:'#c4622d',backgroundColor:'rgba(196,98,45,.08)',fill:true,tension:.4},
            {label:'Humidity %',data:<?=json_encode(array_column($hist,'humidity'))?>,borderColor:'#3b6ea5',backgroundColor:'rgba(59,110,165,.08)',fill:true,tension:.4,yAxisID:'y1'}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',align:'end'}},
  scales:{y:{position:'left',title:{display:true,text:'°C'}},y1:{position:'right',title:{display:true,text:'%'},grid:{display:false}},x:{grid:{display:false}}}}});
</script>
<?php endif;?>
<?php include 'footer.php';?>
