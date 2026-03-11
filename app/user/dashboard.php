<?php
session_start();
include "../../config/db.php"; // Correct path from app/user/dashboard.php

// Redirect to login if not logged in
if(!isset($_SESSION['user_id'])){
    header("Location: ../../login/login.php");
    exit();
}

// For this example, we assume sensor_id = 1 (can loop all sensors if needed)
$sensor_id = 1;

// Fetch the latest sensor reading
$stmt = $conn->prepare("SELECT * FROM sensor_readings WHERE sensor_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $sensor_id);
$stmt->execute();
$result = $stmt->get_result();
$latest_reading = $result->fetch_assoc();

// Default values if no reading exists
if(!$latest_reading){
    $sun_heat = 25;
    $is_raining = 0;
    $vent_state = 'off';
    $mode = 'automatic';
} else {
    $sun_heat = $latest_reading['temperature'];
    $is_raining = $latest_reading['is_raining'];
    $vent_state = $latest_reading['vent_state'];
    $mode = $latest_reading['mode'];
}

// Handle POST (manual or automatic mode)
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $mode = $_POST['mode'] ?? 'automatic';
    $manual_state = $_POST['vent_state'] ?? 'off';

    if($mode === 'automatic'){
        if($sun_heat > 30 && !$is_raining){
            $vent_state = 'on';
        } else {
            $vent_state = 'off';
        }
    } else {
        $vent_state = $manual_state;
    }

    // Save the new reading into DB
    $stmt = $conn->prepare("INSERT INTO sensor_readings (sensor_id, temperature, humidity, is_raining, vent_state, mode) VALUES (?, ?, ?, ?, ?, ?)");
    $humidity = 50; // simulate or fetch real humidity
    $stmt->bind_param("iidiss", $sensor_id, $sun_heat, $humidity, $is_raining, $vent_state, $mode);
    $stmt->execute();
}

$display_mode = ucfirst($mode);
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard - Air Ventilation Monitoring</title>
    <style>
        body { font-family: Arial; background: #f4f6f9; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
        .container { background:#fff; padding:30px 50px; border-radius:15px; box-shadow:0 0 15px rgba(0,0,0,0.2); text-align:center; width:400px; }
        h2 { margin-bottom:25px; }
        .reading, .status, .mode { font-size:1.2em; margin:12px 0; }
        .status span, .mode span { font-weight:bold; font-size:1.3em; }
        .alert { color:red; font-weight:bold; margin-top:10px; }
        button { padding:12px 25px; font-size:1em; margin:8px 5px; border-radius:8px; border:none; cursor:pointer; color:white; }
        .on { background:#4CAF50; }
        .off { background:#f44336; }
        .auto { background:#2196F3; }
        .buttons-group { margin-top:20px; }
        a { display:block; margin-top:20px; color:#0077cc; text-decoration:none; }
    </style>
</head>
<body>
<div class="container">
    <h2>Air Ventilation Monitoring</h2>

    <div class="reading">Sun Heat: <?= $sun_heat ?> °C</div>
    <div class="reading">Rain: <?= $is_raining ? 'Yes' : 'No' ?></div>
    <div class="mode">Current Mode: <span><?= $display_mode ?></span></div>
    <div class="status">Ventilation: <span style="color: <?= $vent_state === 'on' ? 'green':'red' ?>"><?= strtoupper($vent_state) ?></span></div>

    <!-- Manual Buttons -->
    <div class="buttons-group">
        <form method="POST" style="display:inline-block;">
            <input type="hidden" name="mode" value="manual">
            <button type="submit" name="vent_state" value="on" class="on">Turn ON</button>
            <button type="submit" name="vent_state" value="off" class="off">Turn OFF</button>
        </form>
    </div>

    <!-- Automatic Mode Button -->
    <div class="buttons-group">
        <form method="POST" style="display:inline-block;">
            <input type="hidden" name="mode" value="automatic">
            <button type="submit" class="auto">Set Automatic Mode</button>
        </form>
    </div>

    <!-- Alerts -->
    <div>
        <?php
        if($sun_heat > 35){
            echo "<div class='alert'>Alert: Extreme heat detected!</div>";
        }
        if($is_raining && $vent_state === 'on'){
            echo "<div class='alert'>Alert: Ventilation should be closed during rain!</div>";
        }
        ?>
    </div>

  <a href="../../login/logout.php">Logout</a>
</div>
</body>
</html>