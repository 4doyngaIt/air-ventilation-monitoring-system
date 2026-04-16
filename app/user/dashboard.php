<?php
$page_title='Dashboard';
include 'header.php';
?>
<div class="auto-note"><i class="fas fa-robot"></i> <strong>Auto Mode:</strong> Vents open when temperature is high · close when rain is detected. Manual override available in Vent Control.</div>
<div class="stats-grid stagger" id="stat-grid">
  <div class="stat-card fade-up green"><span class="stat-icon">🌿</span><div class="stat-label">Active Sensors</div><div class="stat-val" id="s-active">—</div></div>
  <div class="stat-card fade-up"><span class="stat-icon">💨</span><div class="stat-label">Vents Open</div><div class="stat-val" id="s-open">—</div></div>
  <div class="stat-card fade-up amber"><span class="stat-icon">🌡️</span><div class="stat-label">Avg Temperature</div><div class="stat-val" id="s-temp">—</div></div>
  <div class="stat-card fade-up blue"><span class="stat-icon">🌧️</span><div class="stat-label">Rain Detected</div><div class="stat-val" id="s-rain">—</div></div>
</div>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px">
  <h3 style="font-family:var(--font-head);font-size:.95rem;font-weight:700"><i class="fas fa-satellite-dish" style="color:var(--clay)"></i> Live Sensor Readings</h3>
  <div style="display:flex;align-items:center;gap:12px">
    <div class="live-dot">Auto-refresh 5s</div>
    <div class="search-wrap"><i class="fas fa-search"></i><input type="text" id="srch" placeholder="Search sensor..." oninput="filterCards()"></div>
  </div>
</div>
<div class="sensor-grid" id="sensor-grid"></div>
<script>
let allS=[];
async function load(){
  try{
    const r=await fetch('/hangin/app/sensor_data.php?action=sensors');
    allS=await r.json();
    document.getElementById('s-active').textContent=allS.filter(s=>s.temperature!=null).length;
    document.getElementById('s-open').textContent=allS.filter(s=>String(s.vent_state).toLowerCase()==='on').length;
    const avg=allS.length?allS.reduce((a,s)=>a+(+s.temperature||0),0)/allS.length:0;
    document.getElementById('s-temp').textContent=avg.toFixed(1)+'°C';
    document.getElementById('s-rain').textContent=allS.filter(s=>+s.rainfall>0).length;
    renderCards(allS);
  }catch(e){console.error(e);}
}
function renderCards(sensors){
  const q=document.getElementById('srch').value.toLowerCase();
  const filtered=sensors.filter(s=>!q||String(s.sensor_id).includes(q)||String(s.location).toLowerCase().includes(q));
  const grid=document.getElementById('sensor-grid');
  if(!filtered.length){grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--muted)"><i class="fas fa-satellite-dish" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.35"></i>No sensors found.</div>';return;}
  grid.innerHTML=filtered.map(s=>{
    const vs=String(s.vent_state||'off').toLowerCase();
    const rain=+s.rainfall>0;
    const temp=+(s.temperature||0);
    const card=rain?'rain':(vs==='on'?'vent-on':'vent-off');
    const tc=temp>35?'hot':(temp<20?'cool':'');
    const mode=String(s.mode||'auto').toUpperCase();
    const cond=rain?'🌧️ Rain – Vent Closing':(temp>35?'🔥 High Temp – Vent Open':'🌤️ Normal Conditions');
    return`<div class="sensor-card ${card}" data-id="${s.sensor_id}" data-loc="${s.location}">
      <div class="s-head">
        <div><div class="s-id"><i class="fas fa-microchip"></i> Sensor #${s.sensor_id}</div>
          <div class="s-loc"><i class="fas fa-map-marker-alt"></i> ${s.location||'Unknown'}</div></div>
        <span class="vent-badge ${vs}">${vs.toUpperCase()}</span>
      </div>
      <div class="s-readings">
        <div class="s-reading"><div class="s-r-label">🌡️ Temp</div><div class="s-r-val ${tc}">${s.temperature!=null?s.temperature+'°C':'—'}</div></div>
        <div class="s-reading"><div class="s-r-label">💧 Humidity</div><div class="s-r-val">${s.humidity!=null?s.humidity+'%':'—'}</div></div>
        <div class="s-reading"><div class="s-r-label">🌧️ Rainfall</div><div class="s-r-val ${+s.rainfall>0?'wet':''}">${s.rainfall||0} mm</div></div>
        <div class="s-reading"><div class="s-r-label">💨 Wind</div><div class="s-r-val">${s.wind_speed||0} m/s</div></div>
      </div>
      <div class="s-footer"><span class="s-condition">${cond}</span><span style="font-family:var(--font-mono);font-size:.68rem">${mode}</span></div>
    </div>`;
  }).join('');
}
function filterCards(){renderCards(allS);}
load();setInterval(load,5000);
</script>
<?php include 'footer.php';?>
