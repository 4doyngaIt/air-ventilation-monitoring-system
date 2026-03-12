<?php
session_start();
include __DIR__ . "/../../config/db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager'){
    header("Location: ../../login/login.php");
    exit();
}

/* USERS */
$users_result = $conn->query("SELECT user_id,username,email,role FROM users ORDER BY user_id ASC");

/* SENSOR TABLE */
$sensor_table = $conn->query("SELECT * FROM sensors ORDER BY sensor_id ASC");

/* SENSOR MAP DATA */
$map_sensors = [];
$map_query = $conn->query("SELECT sensor_id,location,latitude,longitude FROM sensors");

if($map_query){
    while($row = $map_query->fetch_assoc()){
        $map_sensors[] = $row;
    }
}

/* TOTAL COUNTS */
$total_users = ($users_result) ? $users_result->num_rows : 0;
$total_sensors = ($sensor_table) ? $sensor_table->num_rows : 0;
?>

<!DOCTYPE html>
<html>
<head>

<title>Manager Dashboard</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<style>

body{
font-family:Arial;
margin:0;
display:flex;
background:#f4f7f9;
}

.sidebar{
width:180px;
background:#0077cc;
color:#fff;
height:100vh;
display:flex;
flex-direction:column;
padding-top:20px;
}

.sidebar a{
color:#fff;
padding:15px 20px;
text-decoration:none;
display:block;
font-weight:bold;
}

.sidebar a:hover{
background:#005fa3;
}

.main{
flex:1;
padding:25px;
}

h2{
color:#0077cc;
}

.cards{
display:flex;
gap:20px;
margin-top:20px;
}

.card{
flex:1;
background:white;
padding:20px;
border-radius:8px;
box-shadow:0 0 10px #ccc;
text-align:center;
}

.card span{
font-size:35px;
display:block;
margin-bottom:10px;
}

#map{
width:100%;
height:400px;
margin-top:20px;
border-radius:10px;
}

table{
width:100%;
border-collapse:collapse;
margin-top:20px;
background:white;
box-shadow:0 0 8px #ccc;
}

th,td{
padding:10px;
border:1px solid #ddd;
text-align:center;
}

th{
background:#0077cc;
color:white;
}

.section{
display:none;
}

</style>

<script>

function showSection(id){

let sections=document.querySelectorAll('.section');

sections.forEach(function(section){
section.style.display='none';
});

document.getElementById(id).style.display='block';

}

window.onload=function(){
showSection('home');
}

</script>

</head>

<body>

<div class="sidebar">

<a href="#" onclick="showSection('home')">Dashboard</a>

<a href="#" onclick="showSection('mapSection')">Sensor Map</a>

<a href="#" onclick="showSection('sensor')">Monitor Sensors</a>

<a href="#" onclick="showSection('users')">View Users</a>

<a href="../../login/login.php">Logout</a>

</div>

<div class="main">

<div id="home" class="section">

<h2>Welcome Manager, <?php echo $_SESSION['username']; ?></h2>

<div class="cards">

<div class="card">
<span>📡</span>
<h3>Total Sensors</h3>
<p><?php echo $total_sensors; ?></p>
</div>

<div class="card">
<span>👥</span>
<h3>Total Users</h3>
<p><?php echo $total_users; ?></p>
</div>

</div>

</div>


<div id="mapSection" class="section">

<h2>Sensor Location Map</h2>

<div id="map"></div>

</div>


<div id="sensor" class="section">

<h2>Sensor Monitoring</h2>

<table>

<tr>
<th>ID</th>
<th>Location</th>
<th>Status</th>
<th>Sun Heat</th>
<th>Rain</th>
<th>Last Updated</th>
</tr>

<?php
if($sensor_table && $sensor_table->num_rows > 0){

while($sensor=$sensor_table->fetch_assoc()){

$sun_heat = isset($sensor['sun_heat']) ? $sensor['sun_heat'] : "no";
$rain = isset($sensor['rain']) ? $sensor['rain'] : "no";
$last_updated = isset($sensor['last_updated']) ? $sensor['last_updated'] : "N/A";
?>

<tr>

<td><?php echo $sensor['sensor_id']; ?></td>

<td><?php echo htmlspecialchars($sensor['location']); ?></td>

<td><?php echo htmlspecialchars($sensor['status']); ?></td>

<td><?php echo ($sun_heat=="yes") ? "☀ Detected" : "❌ None"; ?></td>

<td><?php echo ($rain=="yes") ? "🌧 Detected" : "❌ None"; ?></td>

<td><?php echo $last_updated; ?></td>

</tr>

<?php
}

}else{
?>

<tr>
<td colspan="6">No sensors found</td>
</tr>

<?php } ?>

</table>

</div>


<div id="users" class="section">

<h2>System Users</h2>

<table>

<tr>
<th>ID</th>
<th>Username</th>
<th>Email</th>
<th>Role</th>
</tr>

<?php
if($users_result && $users_result->num_rows > 0){
while($u=$users_result->fetch_assoc()){
?>

<tr>

<td><?php echo $u['user_id']; ?></td>

<td><?php echo htmlspecialchars($u['username']); ?></td>

<td><?php echo htmlspecialchars($u['email']); ?></td>

<td><?php echo $u['role']; ?></td>

</tr>

<?php
}
}
?>

</table>

</div>

</div>


<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>

var sensors = <?php echo json_encode($map_sensors); ?>;

var map = L.map('map').setView([7.0731,125.6128],13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
maxZoom:19
}).addTo(map);

sensors.forEach(function(sensor){

if(sensor.latitude && sensor.longitude){

var marker=L.marker([
parseFloat(sensor.latitude),
parseFloat(sensor.longitude)
]).addTo(map);

marker.bindPopup(
"<b>Sensor ID:</b> "+sensor.sensor_id+
"<br><b>Location:</b> "+sensor.location
);

}

});

</script>

</body>
</html>