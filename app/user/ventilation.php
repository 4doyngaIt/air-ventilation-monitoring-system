<?php
$page_title='Ventilation Control';
include 'header.php';
$sensors=db_q("SELECT * FROM sensors ORDER BY sensor_id ASC");
?>
<div class="auto-note"><i class="fas fa-robot"></i> <strong>Automatic Mode:</strong> System auto-opens on high heat, auto-closes on rain. Use manual override below.</div>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <h3 style="font-family:var(--font-head);font-size:.95rem;font-weight:700"><i class="fas fa-fan" style="color:var(--clay)"></i> Ventilation Controls</h3>
  <div class="live-dot">Status updates 10s</div>
</div>
<div class="sensor-grid">
<?php foreach($sensors as $row):?>
<div class="sensor-card" id="vc-<?=$row['sensor_id']?>">
  <div class="s-head">
    <div><div class="s-id"><i class="fas fa-microchip"></i> Sensor #<?=$row['sensor_id']?></div>
      <div class="s-loc"><i class="fas fa-map-marker-alt"></i> <?=e($row['location'])?></div></div>
    <span class="badge badge-<?=strtolower($row['status'])==='active'?'active':'inactive'?>"><?=$row['status']?></span>
  </div>
  <div id="vs-<?=$row['sensor_id']?>" style="margin:10px 0;min-height:24px;font-size:.83rem;color:var(--muted)">
    <i class="fas fa-spinner fa-spin"></i> Loading current state…
  </div>
  <div class="vent-controls">
    <button class="btn btn-on btn-sm" onclick="ctrlVent(<?=$row['sensor_id']?>,'on','manual')"><i class="fas fa-play"></i> Open Vent</button>
    <button class="btn btn-off btn-sm" onclick="ctrlVent(<?=$row['sensor_id']?>,'off','manual')"><i class="fas fa-stop"></i> Close Vent</button>
    <button class="btn btn-auto btn-sm" onclick="ctrlVent(<?=$row['sensor_id']?>,'on','automatic')"><i class="fas fa-sync-alt"></i> Set Auto</button>
  </div>
</div>
<?php endforeach;?>
</div>
<script>
async function loadStates(){
  try{
    const r=await fetch('/hangin/app/sensor_data.php?action=sensors');
    const sensors=await r.json();
    sensors.forEach(s=>{
      const el=document.getElementById('vs-'+s.sensor_id);
      const card=document.getElementById('vc-'+s.sensor_id);
      if(!el)return;
      const vs=String(s.vent_state||'off').toUpperCase();
      const md=String(s.mode||'manual').toUpperCase();
      const isOpen=vs==='ON';
      const isRain=+s.rainfall>0;
      const isHot=+(s.temperature||0)>35;
      const color=isOpen?'var(--forest-mid)':(isRain?'var(--rain)':'var(--muted)');
      el.innerHTML=`<span style="font-weight:600;color:${color}">● Vent ${vs}</span> <span style="color:var(--muted)">(${md})</span>`
        +(isRain?' <span style="color:var(--rain);font-size:.78rem">🌧️ Rain detected – auto-closing</span>':'')
        +(isHot&&!isRain?' <span style="color:var(--clay);font-size:.78rem">🌡️ High temp – auto-opening</span>':'');
      if(card){card.className='sensor-card '+(isRain?'rain':(isOpen?'vent-on':'vent-off'));}
    });
  }catch(e){}
}
async function ctrlVent(sid,action,mode){
  const btn=event.target.closest('button');
  const orig=btn.innerHTML;btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
  try{
    const r=await fetch('/hangin/app/sensor_control.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sensor_id:sid,action,mode})});
    const d=await r.json();
    if(d.status==='ok'){await loadStates();}
    else{alert('Error: '+d.msg);}
  }catch(e){alert('Connection error.');}
  finally{btn.innerHTML=orig;btn.disabled=false;}
}
loadStates();setInterval(loadStates,10000);
</script>
<?php include 'footer.php';?>
