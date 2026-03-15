<?php
session_start();
include "../config/db.php";

$data = json_decode(file_get_contents("php://input"),true);
$sensor_id = $data['sensor_id'];
$action = $data['action'];
$mode = $data['mode'];
$user_id = $_SESSION['user_id'] ?? 1;

/* Get latest sensor reading */
$stmt = $conn->prepare("SELECT * FROM sensor_readings WHERE sensor_id=? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i",$sensor_id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
if(!$current){
    $current = ["temperature"=>0,"humidity"=>0,"rainfall"=>0,"wind_speed"=>0,"sunlight_intensity"=>0,"weather_condition"=>"unknown"];
}

/* Insert new sensor reading */
$stmt = $conn->prepare("
INSERT INTO sensor_readings
(sensor_id,temperature,humidity,rainfall,wind_speed,sunlight_intensity,weather_condition,vent_state,mode)
VALUES (?,?,?,?,?,?,?,?,?)
");
$stmt->bind_param(
    "iddddddss",
    $sensor_id,
    $current['temperature'],
    $current['humidity'],
    $current['rainfall'],
    $current['wind_speed'],
    $current['sunlight_intensity'],
    $current['weather_condition'],
    $action,
    $mode
);
$stmt->execute();

/* Log action */
$stmt = $conn->prepare("INSERT INTO ventilation_logs(user_id,sensor_id,action,mode) VALUES (?,?,?,?)");
$stmt->bind_param("iiss",$user_id,$sensor_id,$action,$mode);
$stmt->execute();

echo json_encode(["status"=>"ok"]);