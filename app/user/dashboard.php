<?php
include "../../config/db.php";
include "header.php";
?>

<h1>Dashboard</h1>

<!-- Search Bar Container -->
<div class="search-container">
    <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="sensor-search" placeholder="Search sensor number..." oninput="filterSensors()">
    </div>
</div>

<div class="cards" id="dashboard-cards"></div>

<h2>Sensor Monitoring</h2>
<div class="sensor-grid" id="sensor-grid"></div>

<style>
.search-container {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
}
.search-box {
    position: relative;
    display: flex;
    align-items: center;
}
.search-icon {
    position: absolute;
    left: 12px;
    font-size: 16px;
    color: #888;
    pointer-events: none;
}
#sensor-search {
    padding: 10px 15px 10px 35px;
    border: 1px solid #ddd;
    border-radius: 25px;
    font-size: 14px;
    width: 220px;
    transition: all 0.3s ease;
    background-color: #f9f9f9;
}
#sensor-search:focus {
    outline: none;
    border-color: #4CAF50;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
}
.sensor.hidden {
    display: none;
}
</style>

<script>
let allSensors = []; // Store all sensors for filtering

async function loadDashboard(){
    const res = await fetch("../../app/sensor_data.php?action=sensors");
    allSensors = await res.json(); // Store globally

    updateDashboard(allSensors);
}

function updateDashboard(sensors) {
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
        <div class="sensor" data-sensor-id="${s.sensor_id}">
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

function filterSensors() {
    const searchTerm = document.getElementById("sensor-search").value.toLowerCase().trim();
    const sensorElements = document.querySelectorAll(".sensor");
    
    sensorElements.forEach(el => {
        const sensorId = el.getAttribute("data-sensor-id").toLowerCase();
        if (sensorId.includes(searchTerm)) {
            el.classList.remove("hidden");
        } else {
            el.classList.add("hidden");
        }
    });
}

// Auto-refresh every 5s
loadDashboard();
setInterval(loadDashboard,5000);
</script>

<?php include "footer.php"; ?>