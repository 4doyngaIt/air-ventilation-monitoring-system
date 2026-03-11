<?php
session_start();

// Simulate environmental conditions
$sun_heat = rand(20, 40);  // °C
$is_raining = rand(0, 1);  // 0 = no, 1 = yes

// Check mode
$mode = $_POST['mode'] ?? 'automatic'; // automatic or manual
$manual_state = $_POST['vent_state'] ?? 'off'; // only used in manual mode

// Determine ventilation state
if($mode === 'automatic'){
    if($sun_heat > 30 && !$is_raining){
        $vent_state = 'on';
    } else {
        $vent_state = 'off';
    }
} else {
    $vent_state = $manual_state;
}

// Capitalize mode for display
$display_mode = ucfirst($mode);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Air Ventilation Monitoring - Mode Display</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 30px 50px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            text-align: center;
            width: 400px;
        }
        h2 {
            margin-bottom: 25px;
        }
        .reading, .status, .mode {
            font-size: 1.2em;
            margin: 12px 0;
        }
        .status span, .mode span {
            font-weight: bold;
            font-size: 1.3em;
        }
        .alert {
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }
        button {
            padding: 12px 25px;
            font-size: 1em;
            margin: 8px 5px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            color: white;
        }
        .on { background: #4CAF50; }
        .off { background: #f44336; }
        .auto { background: #2196F3; }
        .buttons-group { margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Air Ventilation Monitoring</h2>

    <div class="reading">Sun Heat: <?= $sun_heat ?> °C</div>
    <div class="reading">Rain: <?= $is_raining ? 'Yes' : 'No' ?></div>

    <!-- Display current mode -->
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
</div>

</body>
</html>