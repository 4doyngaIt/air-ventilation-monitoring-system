<?php
$page_title='Weather Monitor';
include 'header.php';
?>
<div class="auto-note"><i class="fas fa-info-circle"></i> Weather data is pulled from sensor readings. Data refreshes every 5 seconds.</div>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
  <h3 style="font-family:var(--font-head);font-size:.95rem;font-weight:700"><i class="fas fa-cloud-sun" style="color:var(--clay)"></i> Live Weather Readings</h3>
  <div class="live-dot">5s refresh</div>
</div>
<div class="sensor-grid" id="weather-grid"></div>
<script>
async function loadWeather(){
  try{
    const r=await fetch('/hangin/app/sensor_data.php?action=sensors');
    const sensors=await r.json();
    const grid=document.getElementById('weather-grid');
    if(!sensors.length){grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--muted)">No sensor data.</div>';return;}
    grid.innerHTML=sensors.map(s=>{
      const rain=+s.rainfall>0,sun=+s.sunlight_intensity>50;
      let cond='☁️ Cloudy',cls='';
      if(rain){cond='🌧️ Rainy';cls='rain';}
      else if(sun){cond='☀️ Sunny';}
      else{cond='🌤️ Partly Cloudy';}
      const temp=+(s.temperature||0);
      const tc=temp>35?'hot':(temp<20?'cool':'');
      return`<div class="sensor-card ${cls}">
        <div class="s-head">
          <div><div class="s-id"><i class="fas fa-satellite-dish"></i> Sensor #${s.sensor_id}</div>
            <div class="s-loc"><i class="fas fa-map-marker-alt"></i> ${s.location}</div></div>
          <span class="vent-badge ${rain?'off':'on'}">${cond}</span>
        </div>
        <div class="s-readings">
          <div class="s-reading"><div class="s-r-label">🌡️ Temp</div><div class="s-r-val ${tc}">${s.temperature??'—'}°C</div></div>
          <div class="s-reading"><div class="s-r-label">💧 Humidity</div><div class="s-r-val">${s.humidity??'—'}%</div></div>
          <div class="s-reading"><div class="s-r-label">🌧️ Rainfall</div><div class="s-r-val ${rain?'wet':''}">${s.rainfall||0} mm</div></div>
          <div class="s-reading"><div class="s-r-label">☀️ Sunlight</div><div class="s-r-val">${s.sunlight_intensity||0}</div></div>
        </div>
        <div class="s-footer"><span>💨 Wind: ${s.wind_speed||0} m/s</span><span>${s.weather_condition||'unknown'}</span></div>
      </div>`;
    }).join('');
  }catch(e){console.error(e);}
}
loadWeather();setInterval(loadWeather,5000);
</script>
<?php include 'footer.php';?>
