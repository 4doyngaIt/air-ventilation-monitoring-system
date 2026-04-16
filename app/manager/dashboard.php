<?php
require_once __DIR__.'/../../config/config.php';
auth_guard('manager','admin');
$me=$_SESSION['username']??'';
$sensor_table=db_q("SELECT * FROM sensors ORDER BY sensor_id ASC");
$users=db_q("SELECT user_id,username,email,role FROM users ORDER BY user_id ASC");
$map_sensors=db_q("SELECT sensor_id,location,latitude,longitude,status FROM sensors");
$ts=count($sensor_table);
$as=count(array_filter($sensor_table,fn($s)=>$s['status']==='Active'));
$alerts=array_filter($sensor_table,fn($s)=>$s['status']!=='Active');
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manager Dashboard | AirVent Monitor</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Epilogue:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<link rel="stylesheet" href="/hangin/assets/style.css">
</head><body>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo"><div class="brand-icon">💨</div>
      <div><div class="brand-name">AirVent</div><div class="brand-sub">Monitor v<?=APP_VER?></div></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Overview</div>
    <a href="#" onclick="showSec('home',this)" id="lk-home" class="active"><i class="fas fa-home"></i> Dashboard</a>
    <div class="nav-label">Monitoring</div>
    <a href="#" onclick="showSec('mapSec',this)" id="lk-map"><i class="fas fa-map-marked-alt"></i> Sensor Map</a>
    <a href="#" onclick="showSec('sensorSec',this)" id="lk-sensor"><i class="fas fa-broadcast-tower"></i> Monitor Sensors</a>
    <div class="nav-label">Users</div>
    <a href="#" onclick="showSec('userSec',this)" id="lk-users"><i class="fas fa-users"></i> View Users</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-av"><?=strtoupper(substr($me,0,1))?></div>
      <div><div class="user-name"><?=e($me)?></div><div class="user-role">manager</div></div>
    </div>
    <a href="/hangin/auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </div>
</aside>
<div class="main-wrap">
  <div class="topbar">
    <div class="topbar-title" id="secTitle">Dashboard</div>
    <div class="topbar-right">
      <div class="live-dot">LIVE</div>
      <span class="role-pill manager">Manager</span>
      <div class="topbar-av"><?=strtoupper(substr($me,0,1))?></div>
    </div>
  </div>
  <div class="content">

  <!-- HOME -->
  <div id="home" class="sec-panel">
    <?php if(count($alerts)>0):?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <strong><?=count($alerts)?> sensor(s)</strong> are inactive and may need attention.</div>
    <?php endif;?>
    <div class="auto-note"><i class="fas fa-robot"></i> <strong>Auto Mode Active:</strong> Vents open on high temperature, close on rain detection.</div>
    <div class="stats-grid">
      <div class="stat-card green"><span class="stat-icon">🌿</span><div class="stat-label">Total Sensors</div><div class="stat-val"><?=$ts?></div></div>
      <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-label">Active</div><div class="stat-val" style="color:var(--forest-mid)"><?=$as?></div></div>
      <div class="stat-card red"><span class="stat-icon">⚠️</span><div class="stat-label">Inactive</div><div class="stat-val" style="color:var(--danger)"><?=$ts-$as?></div></div>
      <div class="stat-card blue"><span class="stat-icon">👥</span><div class="stat-label">Total Users</div><div class="stat-val" style="color:var(--sky)"><?=count($users)?></div></div>
    </div>
    <div class="card"><div class="card-head"><div class="card-title"><i class="fas fa-info-circle" style="color:var(--clay)"></i> System Info</div></div>
      <div class="card-body"><p style="color:var(--muted);line-height:1.75">
        Welcome to the AirVent Manager Dashboard. Monitor all farm sensors, view the coverage map, and track user activity.
        Sensors automatically open ventilation when temperature exceeds threshold and close when rain is detected.
      </p>
      <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
        <button onclick="showSec('mapSec',document.getElementById('lk-map'))" class="btn btn-primary btn-sm"><i class="fas fa-map"></i> Open Map</button>
        <button onclick="showSec('sensorSec',document.getElementById('lk-sensor'))" class="btn btn-secondary btn-sm"><i class="fas fa-broadcast-tower"></i> Monitor Sensors</button>
      </div></div></div>
  </div>

  <!-- MAP -->
  <div id="mapSec" class="sec-panel" style="display:none">
    <div class="card" style="margin-bottom:0"><div class="card-head">
      <div class="card-title"><i class="fas fa-map-marked-alt" style="color:var(--clay)"></i> Farm Sensor Coverage Map</div></div>
      <div class="card-body" style="padding:0;position:relative">
        <div id="map"></div>
        <div style="position:absolute;bottom:16px;right:16px;background:#fff;padding:12px 16px;border-radius:var(--radius);box-shadow:var(--shadow-md);font-size:.8rem;z-index:1000">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px"><div style="width:11px;height:11px;border-radius:50%;background:#4a7c28;border:2px solid #fff"></div> Active</div>
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px"><div style="width:11px;height:11px;border-radius:50%;background:#b91c1c;border:2px solid #fff"></div> Inactive</div>
          <div style="display:flex;align-items:center;gap:6px"><div style="width:14px;height:3px;background:rgba(196,98,45,.7);border-radius:2px;margin:auto 0"></div> Coverage</div>
        </div>
      </div></div>
  </div>

  <!-- SENSORS -->
  <div id="sensorSec" class="sec-panel" style="display:none">
    <div class="filter-bar">
      <div class="search-wrap"><i class="fas fa-search"></i><input type="text" id="sSrch" placeholder="Search location..." onkeyup="filterS()"></div>
      <div class="filter-group"><label>Status</label>
        <select id="sFlt" onchange="filterS()"><option value="all">All</option><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
      <div style="margin-left:auto;display:flex;gap:8px">
        <button class="btn btn-primary btn-sm" id="vg" onclick="toggleView('grid')"><i class="fas fa-th-large"></i> Grid</button>
        <button class="btn btn-secondary btn-sm" id="vt" onclick="toggleView('table')"><i class="fas fa-list"></i> Table</button>
      </div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <div class="live-dot">LIVE DATA</div>
      <span style="font-size:.75rem;color:var(--muted)">Updates every 30s · <span id="lastUpd"><?=date('H:i:s')?></span></span>
    </div>
    <div id="sGrid" class="sensor-grid">
    <?php foreach($sensor_table as $s): $isA=strtolower($s['status'])==='active';?>
    <div class="sensor-card <?=$isA?'vent-on':'vent-off'?>" data-loc="<?=e($s['location'])?>" data-status="<?=$s['status']?>">
      <div class="s-head">
        <div><div class="s-id"><i class="fas fa-microchip"></i> Sensor #<?=$s['sensor_id']?></div>
          <div class="s-loc"><i class="fas fa-map-marker-alt"></i> <?=e($s['location'])?></div></div>
        <span class="badge badge-<?=$isA?'active':'inactive'?>"><?=$s['status']?></span>
      </div>
      <div class="s-readings">
        <div class="s-reading"><div class="s-r-label">📍 Latitude</div><div class="s-r-val" style="font-size:.8rem"><?=$s['latitude']?></div></div>
        <div class="s-reading"><div class="s-r-label">📍 Longitude</div><div class="s-r-val" style="font-size:.8rem"><?=$s['longitude']?></div></div>
      </div>
    </div>
    <?php endforeach;?>
    </div>
    <div id="sTable" style="display:none">
      <div class="card"><div class="card-body p0"><table class="data-table">
        <thead><tr><th>#</th><th>Location</th><th>Status</th><th>Latitude</th><th>Longitude</th></tr></thead>
        <tbody>
        <?php foreach($sensor_table as $s): $isA=strtolower($s['status'])==='active';?>
        <tr><td style="color:var(--muted)"><strong>#<?=$s['sensor_id']?></strong></td>
          <td><?=e($s['location'])?></td>
          <td><span class="badge badge-<?=$isA?'active':'inactive'?>"><?=$s['status']?></span></td>
          <td style="font-size:.82rem;color:var(--muted)"><?=$s['latitude']?></td>
          <td style="font-size:.82rem;color:var(--muted)"><?=$s['longitude']?></td></tr>
        <?php endforeach;?>
        </tbody>
      </table></div></div>
    </div>
  </div>

  <!-- USERS -->
  <div id="userSec" class="sec-panel" style="display:none">
    <div class="card"><div class="card-head"><div class="card-title"><i class="fas fa-users" style="color:var(--clay)"></i> System Users</div></div>
      <div class="card-body p0"><table class="data-table">
        <thead><tr><th>#</th><th>User</th><th>Email</th><th>Role</th></tr></thead>
        <tbody>
        <?php foreach($users as $u):?>
        <tr><td style="color:var(--muted);font-size:.83rem">#<?=$u['user_id']?></td>
          <td><div class="user-cell"><div class="user-av-sm"><?=strtoupper(substr($u['username'],0,1))?></div><?=e($u['username'])?></div></td>
          <td style="color:var(--muted)"><?=e($u['email'])?></td>
          <td><span class="badge badge-<?=$u['role']?>"><?=ucfirst($u['role'])?></span></td></tr>
        <?php endforeach;?>
        </tbody>
      </table></div></div>
  </div>

  </div>
</div>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
function showSec(id,lk){
  document.querySelectorAll('.sec-panel').forEach(s=>s.style.display='none');
  document.getElementById(id).style.display='block';
  document.querySelectorAll('.sidebar-nav a').forEach(a=>a.classList.remove('active'));
  if(lk)lk.classList.add('active');
  const t={home:'Dashboard',mapSec:'Sensor Map',sensorSec:'Monitor Sensors',userSec:'View Users'};
  document.getElementById('secTitle').textContent=t[id]??id;
  if(id==='mapSec'&&window.lmap)setTimeout(()=>window.lmap.invalidateSize(),150);
}
function filterS(){
  const q=document.getElementById('sSrch').value.toLowerCase();
  const st=document.getElementById('sFlt').value;
  document.querySelectorAll('#sGrid .sensor-card').forEach(c=>{
    c.style.display=(c.dataset.loc.toLowerCase().includes(q)&&(st==='all'||c.dataset.status===st))?'':'none';
  });
}
function toggleView(v){
  document.getElementById('sGrid').style.display=v==='grid'?'grid':'none';
  document.getElementById('sTable').style.display=v==='table'?'block':'none';
  document.getElementById('vg').className='btn btn-sm '+(v==='grid'?'btn-primary':'btn-secondary');
  document.getElementById('vt').className='btn btn-sm '+(v==='table'?'btn-primary':'btn-secondary');
}
setInterval(()=>{document.getElementById('lastUpd').textContent=new Date().toLocaleTimeString('en-PH');},30000);
window.addEventListener('load',()=>{
  const sensors=<?=json_encode($map_sensors)?>;
  const valid=sensors.filter(s=>s.latitude&&s.longitude&&!isNaN(+s.latitude)&&!isNaN(+s.longitude));
  if(!valid.length)return;
  const cLat=valid.reduce((a,s)=>a+(+s.latitude),0)/valid.length;
  const cLng=valid.reduce((a,s)=>a+(+s.longitude),0)/valid.length;
  window.lmap=L.map('map').setView([cLat,cLng],14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(window.lmap);
  const pts=[];
  valid.forEach(s=>{
    const lat=+s.latitude,lng=+s.longitude;pts.push([lat,lng]);
    const color=s.status==='Active'?'#4a7c28':'#b91c1c';
    const icon=L.divIcon({className:'',html:`<div style="background:${color};width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3)"></div>`,iconSize:[14,14],iconAnchor:[7,7]});
    L.marker([lat,lng],{icon}).addTo(window.lmap).bindPopup(`<strong>Sensor #${s.sensor_id}</strong><br>${s.location}<br><span style="color:${color};font-weight:600">${s.status}</span>`);
  });
  if(pts.length>=3)L.polygon(pts,{color:'#c4622d',weight:2,opacity:.7,fillColor:'#c4622d',fillOpacity:.1,dashArray:'6,10'}).addTo(window.lmap);
  const grp=L.featureGroup(valid.map(s=>L.marker([+s.latitude,+s.longitude])));
  window.lmap.fitBounds(grp.getBounds().pad(.25));
});
</script>
</body></html>
