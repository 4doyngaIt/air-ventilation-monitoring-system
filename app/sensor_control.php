<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../config/config.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['status'=>'error']);exit;}
$d=json_decode(file_get_contents('php://input'),true);
$sid=intval($d['sensor_id']??0);
$action=$d['action']??'';
$mode=$d['mode']??'manual';
$uid=$_SESSION['user_id']??1;
if(!$sid||!in_array($action,['on','off'])||!in_array($mode,['manual','automatic'])){
    echo json_encode(['status'=>'error','msg'=>'invalid']);exit;
}
$cur=db_one("SELECT * FROM sensor_readings WHERE sensor_id=? ORDER BY created_at DESC LIMIT 1","i",[$sid]);
if(!$cur)$cur=['temperature'=>0,'humidity'=>0,'rainfall'=>0,'wind_speed'=>0,'sunlight_intensity'=>0,'weather_condition'=>'unknown'];
db_run("INSERT INTO sensor_readings(sensor_id,temperature,humidity,rainfall,wind_speed,sunlight_intensity,weather_condition,vent_state,mode)VALUES(?,?,?,?,?,?,?,?,?)",
    "idddddsss",[$sid,$cur['temperature'],$cur['humidity'],$cur['rainfall'],$cur['wind_speed'],$cur['sunlight_intensity'],$cur['weather_condition'],$action,$mode]);
db_run("INSERT INTO ventilation_logs(user_id,sensor_id,action,mode)VALUES(?,?,?,?)","iiss",[$uid,$sid,$action,$mode]);
log_act($uid,'sensor',"Sensor #$sid vent $action ($mode)");
echo json_encode(['status'=>'ok','sensor_id'=>$sid,'action'=>$action,'mode'=>$mode]);
