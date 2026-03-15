<?php
session_start();
include "../../config/db.php";
include "header.php";


$sensors = $conn->query("SELECT * FROM sensors");
?>

<h1>Ventilation Control</h1>
<div class="sensor-grid">
<?php while($row=$sensors->fetch_assoc()): ?>
<div class="sensor" id="vent-<?= $row['sensor_id'] ?>">
    <h3><?= $row['location'] ?></h3>
    <button class="btn on" onclick="controlVent(<?= $row['sensor_id'] ?>,'on','manual')">ON</button>
    <button class="btn off" onclick="controlVent(<?= $row['sensor_id'] ?>,'off','manual')">OFF</button>
    <button class="btn auto" onclick="controlVent(<?= $row['sensor_id'] ?>,'on','automatic')">AUTO</button>
    <p id="vent-status-<?= $row['sensor_id'] ?>"></p>
</div>
<?php endwhile; ?>
</div>

<script>
async function controlVent(sensor_id,action,mode){
    const res = await fetch("../../api/sensor_control.php",{
        method:"POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({sensor_id,action,mode})
    });
    const data = await res.json();
    if(data.status=="ok"){
        document.getElementById(`vent-status-${sensor_id}`).innerText = `Vent: ${action.toUpperCase()} (${mode.toUpperCase()})`;
        // Trigger dashboard refresh
        if(window.loadDashboard) loadDashboard();
    }
}
</script>

<?php include "footer.php"; ?>