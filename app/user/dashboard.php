<?php
include "../../config/db.php";
include "header.php";
?>

<h1>Dashboard</h1>
<div class="cards" id="dashboard-cards"></div>

<h2>Sensor Monitoring</h2>
<div class="sensor-grid" id="sensor-grid"></div>

<script>
async function loadDashboard(){
    const res = await fetch("../../api/sensor_data.php?action=sensors");
    const sensors = await res.json();

    // --- Dashboard Cards ---
let activeSensors = sensors.filter(s => s.temperature !== null).length; 
let sensorsRunning = sensors.filter(s => s.vent_state.toLowerCase() === 'on').length;
let avgTemp = sensors.reduce((a,b)=>a+b.temperature,0)/sensors.length || 0;
let rainSensors = sensors.filter(s=>s.rainfall>0).length;

document.getElementById("dashboard-cards").innerHTML = `
    <div class="card">Active Sensors<h1>${activeSensors}</h1></div>
    <div class="card">Sensors Running<h1>${sensorsRunning}</h1></div>
    <div class="card">Avg Temperature<h1>${avgTemp.toFixed(1)}°C</h1></div>
    <div class="card">Sensors with Rain<h1>${rainSensors}</h1></div>
`;

    // --- Sensor Grid ---
    let grid = "";
sensors.forEach(s=>{
    // Determine vent status class for color
    let ventClass = s.mode.toLowerCase() === 'automatic' ? 'status-auto' : (s.vent_state.toLowerCase() === 'on' ? 'status-on' : 'status-off');

    grid += `
    <div class="sensor">
        <div class="sensor-header">
            <h3>Sensor ${s.sensor_id} — ${s.location}</h3>
            <div class="sensor-status ${ventClass}">
                ${s.vent_state.toUpperCase()} (${s.mode.toUpperCase()})
            </div>
        </div>
        <div class="sensor-body">
            <p>🌡 <span>Temperature:</span> ${s.temperature} °C</p>
            <p>💧 <span>Humidity:</span> ${s.humidity} %</p>
            <p>🌧 <span>Rainfall:</span> ${s.rainfall} mm</p>
            <p>💨 <span>Wind:</span> ${s.wind_speed} m/s</p>
            <p>☀ <span>Sunlight:</span> ${s.sunlight_intensity}</p>
            <p>🌤 <span>Condition:</span> ${s.weather_condition}</p>
        </div>
    </div>
    `;
});
document.getElementById("sensor-grid").innerHTML = grid;
}

// Auto-refresh every 5s
loadDashboard();
setInterval(loadDashboard,5000);
</script>

<?php include "footer.php"; ?>
