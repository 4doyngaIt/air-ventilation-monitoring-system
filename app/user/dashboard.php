<?php
session_start();
include __DIR__ . "/../../config/db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user'){
    header("Location: ../../login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* GET USER INFO */
$stmt = $conn->prepare("SELECT username,email,role FROM users WHERE user_id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* GET LATEST SENSOR READING */
$query = "
SELECT r.*, s.location
FROM sensor_readings r
JOIN sensors s ON r.sensor_id=s.sensor_id
ORDER BY r.created_at DESC
LIMIT 1
";
$reading = $conn->query($query)->fetch_assoc();

/* HANDLE VENT CONTROL */
if(isset($_POST['action'])){
    $action = $_POST['action'];
    $sensor_id = $reading['sensor_id'];

    if($action == "automatic"){
        $mode = "automatic";
        $vent = $reading['vent_state'];
    }else{
        $mode = "manual";
        $vent = $action;

        $stmt = $conn->prepare("
        INSERT INTO ventilation_logs(user_id,sensor_id,action)
        VALUES(?,?,?)
        ");
        $stmt->bind_param("iis",$user_id,$sensor_id,$vent);
        $stmt->execute();
    }

    $stmt = $conn->prepare("
    INSERT INTO sensor_readings
    (sensor_id,temperature,humidity,is_raining,vent_state,mode)
    VALUES(?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "iddiss",
        $sensor_id,
        $reading['temperature'],
        $reading['humidity'],
        $reading['is_raining'],
        $vent,
        $mode
    );
    $stmt->execute();

    // Redirect to ventilation section to skip welcome
    header("Location: dashboard.php?section=ventilation");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>User Dashboard</title>
<style>
body{
    font-family:Arial;
    margin:0;
    display:flex;
    background:#f4f6f9;
}

/* SIDEBAR */
.sidebar{
    width:200px;
    background:#0077cc;
    color:white;
    height:100vh;
    padding-top:20px;
}
.sidebar a{
    display:block;
    padding:15px;
    color:white;
    text-decoration:none;
    font-weight:bold;
}
.sidebar a:hover{ background:#005fa3; }

/* MAIN */
.main{
    flex:1;
    padding:30px;
    position:relative;
}

/* LOGOUT BUTTON TOP-RIGHT */
.logout-btn{
    position:absolute;
    top:20px;
    right:30px;
    background:#dc3545;
    color:white;
    padding:10px 15px;
    text-decoration:none;
    border-radius:5px;
}

/* SECTIONS */
.section{ display:none; }

h2{ color:#0077cc; text-align:center; }

/* CENTER CARD */
.dashboard-center{
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:60vh;
}

/* CARD */
.card{
    background:white;
    padding:30px;
    border-radius:12px;
    box-shadow:0 0 15px rgba(0,0,0,0.2);
    max-width:400px;
    width:100%;
    text-align:center;
}

/* BUTTONS */
.remote{
    display:flex;
    gap:20px;
    margin-top:20px;
    justify-content:center;
}
.remote button{
    padding:12px 20px;
    font-size:16px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
.on{background:#28a745;color:white;}
.off{background:#dc3545;color:white;}
.auto{background:#ffc107;color:black;}

/* SENSOR BOX */
.box-container{
    display:flex;
    gap:20px;
    justify-content:center;
    margin-top:20px;
    perspective:1000px;
}
.box{
    flex:1;
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 0 8px #ccc;
    text-align:center;
    max-width:200px;

    transform: scale(0.2) translateX(0);
    opacity:0;
    transition: transform 0.8s ease, opacity 0.8s ease;
}
.box.animate{
    opacity:1;
    transform: scale(1) translateX(0);
}
.box.temp.animate{ transition-delay:0.2s; }
.box.hum.animate{ transition-delay:0.4s; }
.box.rain.animate{ transition-delay:0.6s; }

/* PROFILE */
.profile-box{
    background:white;
    padding:30px;
    border-radius:12px;
    box-shadow:0 0 15px rgba(0,0,0,0.2);
    width:350px;
    margin:40px auto;
    text-align:center;
}
.profile-icon{
    font-size:60px;
    color:#0077cc;
    margin-bottom:15px;
}

/* WELCOME ANIMATION */
.welcome-card{
    background:white;
    padding:30px;
    border-radius:12px;
    box-shadow:0 0 15px rgba(0,0,0,0.2);
    max-width:400px;
    width:100%;
    text-align:center;
    opacity:0;
    transform: translateY(-20px);
    animation: fadeIn 1.2s forwards;
}
.welcome-text{
    font-size:26px;
    color:#0077cc;
    margin-bottom:15px;
    letter-spacing:1px;
    opacity:0;
    animation: typing 1.5s forwards 0.5s;
}
.subtitle{
    font-size:16px;
    color:#555;
    opacity:0;
    animation: fadeIn 1s forwards 2s;
    margin-bottom:20px;
}
@keyframes fadeIn{ to{opacity:1; transform:translateY(0);} }
@keyframes typing{ 0%{opacity:0; transform:translateX(-20px);} 100%{opacity:1; transform:translateX(0);} }
</style>

<script>
function showSection(id){
    let sections=document.querySelectorAll(".section");
    sections.forEach(sec=>sec.style.display="none");
    let active=document.getElementById(id);
    if(active){
        active.style.display="block";
        if(id==="sensor") animateSensorBoxes();
    }
}

// Sensor monitor box animation
function animateSensorBoxes(){
    const boxes = document.querySelectorAll("#sensor .box");
    boxes.forEach(box => box.classList.remove("animate"));
    setTimeout(()=>{ boxes.forEach(box => box.classList.add("animate")); }, 100);
}

// Auto transition from welcome to Ventilation Control
function autoTransition(){
    setTimeout(()=>{ showSection("ventilation"); }, 5000);
}

window.onload=function(){
    // Check URL param to skip welcome if redirected from POST
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if(section==="ventilation"){
        showSection("ventilation");
    }else{
        showSection("dashboard");
        autoTransition();
    }
}
</script>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <a href="#" onclick="showSection('ventilation'); return false;">DASHBOARD</a>
    <a href="#" onclick="showSection('sensor'); return false;">SENSOR MONITOR</a>
    <a href="#" onclick="showSection('profile'); return false;">PROFILE</a>
</div>

<!-- MAIN -->
<div class="main">
    <a href="../../login/login.php" class="logout-btn">LOGOUT</a>

    <!-- WELCOME -->
    <div id="dashboard" class="section">
        <div class="dashboard-center">
            <div class="welcome-card">
                <h2 class="welcome-text">Welcome, <?= htmlspecialchars($user['username']) ?>!</h2>
                <p class="subtitle">Your Air Ventilation Monitoring System</p>
            </div>
        </div>
    </div>

    <!-- VENTILATION CONTROL -->
    <div id="ventilation" class="section">
        <h2>Ventilation Control</h2>
        <div class="dashboard-center">
            <div class="card">
                <p><b>Sensor Location:</b> <?= htmlspecialchars($reading['location']) ?></p>
                <p><b>Vent State:</b> <?= strtoupper($reading['vent_state']) ?></p>
                <p><b>Mode:</b> <?= strtoupper($reading['mode']) ?></p>
                <form method="POST">
                    <div class="remote">
                        <button class="on" name="action" value="on">ON</button>
                        <button class="off" name="action" value="off">OFF</button>
                        <button class="auto" name="action" value="automatic">AUTOMATIC</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SENSOR MONITOR -->
    <div id="sensor" class="section">
        <h2>Sensor Monitor</h2>
        <div class="box-container">
            <div class="box temp">
                <h3>Temperature</h3>
                <h1><?= $reading['temperature'] ?>°C</h1>
            </div>
            <div class="box hum">
                <h3>Humidity</h3>
                <h1><?= $reading['humidity'] ?>%</h1>
            </div>
            <div class="box rain">
                <h3>Rain Status</h3>
                <h1><?= $reading['is_raining'] ? "RAINING" : "NO RAIN" ?></h1>
            </div>
        </div>
    </div>

    <!-- PROFILE -->
    <div id="profile" class="section">
        <h2>Profile</h2>
        <div class="profile-box">
            <div class="profile-icon">&#128100;</div>
            <p><b>Username:</b> <?= htmlspecialchars($user['username']) ?></p>
            <p><b>Email:</b> <?= htmlspecialchars($user['email']) ?></p>
            <p><b>Role:</b> <?= $user['role'] ?></p>
        </div>
    </div>
</div>

</body>
</html>