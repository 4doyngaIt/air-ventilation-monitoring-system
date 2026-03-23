<?php
header("Content-Type: application/json");
include "../config/db.php";

$action = $_GET['action'] ?? '';

if($action == 'sensors'){
    $result = $conn->query("
        SELECT s.sensor_id,s.location,
        r.temperature,r.humidity,r.rainfall,r.wind_speed,r.sunlight_intensity,r.weather_condition,r.vent_state,r.mode
        FROM sensors s
        LEFT JOIN sensor_readings r
        ON r.reading_id = (
            SELECT reading_id
            FROM sensor_readings
            WHERE sensor_id=s.sensor_id
            ORDER BY created_at DESC
            LIMIT 1
        )
    ");

    $sensors = [];
    while($row = $result->fetch_assoc()){
        $sensors[] = $row;
    }

    echo json_encode($sensors);
}