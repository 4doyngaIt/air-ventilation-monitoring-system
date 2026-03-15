<?php
include "../../config/db.php";
include "header.php";
?>

<h1>Weather Monitor</h1>
<div class="sensor-grid" id="weather-grid"></div>

<script>
async function loadWeather(){
    const res = await fetch("../../api/sensor_data.php?action=sensors");
    const sensors = await res.json();
    let html = "";

    sensors.forEach(s => {
        // Determine condition based on rainfall and sunlight
        let condition = s.weather_condition || "Unknown";
        if(s.rainfall > 0){
            condition = "Sunny 🌧";
        } else if(s.sunlight_intensity > 50){
            condition = "Sunny ☀";
        } else if(s.sunlight_intensity <= 50 && s.sunlight_intensity > 0){
            condition = "Sunny 🌤";
        }

        // Vent status color
        let ventClass = s.mode.toLowerCase() === 'automatic' ? 'status-auto' : (s.vent_state.toLowerCase() === 'on' ? 'status-on' : 'status-off');

        html += `
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
                <p>🌧 <span>Rainfall:</span> ${condition}</p>
                <p>💨 <span>Wind Speed:</span> ${s.wind_speed} m/s</p>
                <p>☀ <span>Sunlight:</span> ${s.sunlight_intensity}</p>
            </div>
        </div>`;
    });

    document.getElementById("weather-grid").innerHTML = html;
}

// Load every 5s
loadWeather();
setInterval(loadWeather, 5000);
</script>

<?php include "footer.php"; ?>