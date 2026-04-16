<?php
header('Content-Type: application/json');
require_once __DIR__.'/../config/config.php';
$action=$_GET['action']??'';
if($action==='sensors'){
    $rows=db_q("SELECT s.sensor_id,s.location,s.status,
        r.temperature,r.humidity,r.rainfall,r.wind_speed,
        r.sunlight_intensity,r.weather_condition,r.vent_state,r.mode,r.created_at
        FROM sensors s
        LEFT JOIN sensor_readings r ON r.reading_id=(
            SELECT reading_id FROM sensor_readings WHERE sensor_id=s.sensor_id ORDER BY created_at DESC LIMIT 1)
        ORDER BY s.sensor_id ASC");
    echo json_encode($rows);exit;
}
echo json_encode(['error'=>'unknown action']);
