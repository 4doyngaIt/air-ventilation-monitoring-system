<?php
session_start();
include __DIR__ . "/../../config/db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager'){
    header("Location: ../../login/login.php");
    exit();
}

/* FETCH DATA */

$users = $conn->query("SELECT user_id,username,email,role FROM users ORDER BY user_id ASC");
$sensors = $conn->query("SELECT * FROM sensors ORDER BY sensor_id ASC");

$total_users = $users->num_rows;
$total_sensors = $sensors->num_rows;

?>

<!DOCTYPE html>
<html>
<head>

<title>Manager Dashboard</title>

<style>

body{
font-family:Arial;
margin:0;
display:flex;
background:#f4f7f9;
}

/* SIDEBAR */

.sidebar{
width:220px;
background:#2c3e50;
color:white;
height:100vh;
display:flex;
flex-direction:column;
}

.sidebar h2{
text-align:center;
padding:15px;
margin:0;
border-bottom:1px solid #3f5870;
}

.sidebar a{
color:white;
padding:15px 20px;
text-decoration:none;
font-weight:bold;
display:block;
}

.sidebar a:hover{
background:#1a252f;
}

/* LOGOUT BUTTON */

.logout{
margin-top:auto;
background:#e74c3c;
text-align:center;
}

.logout:hover{
background:#c0392b;
}

/* MAIN */

.main{
flex:1;
padding:25px;
}

/* DASHBOARD CARDS */

.cards{
display:flex;
gap:20px;
margin-top:20px;
}

.card{
flex:1;
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 0 10px #ccc;
text-align:center;
}

.card span{
font-size:35px;
}

/* MAP */

#map{
width:100%;
height:400px;
margin-top:20px;
border-radius:10px;
}

/* TABLE */

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
background:#2c3e50;
color:white;
}

.section{
display:none;
}

</style>

<script>

function showSection(id){

let sections=document.querySelectorAll('.section');

sections.forEach(s=>{
s.style.display="none";
});

document.getElementById(id).style.display="block";

}

window.onload=function(){
showSection('home');
}

/* GOOGLE MAP */

function initMap(){

var location = {lat:7.0731, lng:125.6128}; // example: Davao City

var map = new google.maps.Map(document.getElementById("map"),{
zoom:13,
center:location
});

var marker = new google.maps.Marker({
position:location,
map:map,
title:"Sensor Location"
});

}

</script>

</head>

<body>

<div class="sidebar">

<h2>Manager</h2>

<a href="#" onclick="showSection('home')">Dashboard</a>
<a href="#" onclick="showSection('mapSection')">Sensor Map</a>
<a href="#" onclick="showSection('sensor')">Monitor Sensors</a>
<a href="#" onclick="showSection('users')">View Users</a>

<a class="logout" href="../../login/logout.php">Logout</a>

</div>

<div class="main">

<!-- HOME -->

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

<!-- MAP -->

<div id="mapSection" class="section">

<h2>Sensor Location Map</h2>

<div id="map"></div>

</div>

<!-- SENSOR TABLE -->

<div id="sensor" class="section">

<h2>Sensor Monitoring</h2>

<table>

<tr>
<th>ID</th>
<th>Location</th>
<th>Status</th>
<th>Temperature</th>
<th>Humidity</th>
<th>Last Updated</th>
</tr>

<?php while($sensor=$sensors->fetch_assoc()): ?>

<tr>

<td><?php echo $sensor['sensor_id']; ?></td>
<td><?php echo htmlspecialchars($sensor['location']); ?></td>
<td><?php echo $sensor['status']; ?></td>
<td><?php echo $sensor['temperature']; ?></td>
<td><?php echo $sensor['humidity']; ?></td>
<td><?php echo $sensor['last_updated']; ?></td>

</tr>

<?php endwhile; ?>

</table>

</div>

<!-- USERS -->

<div id="users" class="section">

<h2>System Users</h2>

<table>

<tr>
<th>ID</th>
<th>Username</th>
<th>Email</th>
<th>Role</th>
</tr>

<?php while($u=$users->fetch_assoc()): ?>

<tr>

<td><?php echo $u['user_id']; ?></td>
<td><?php echo htmlspecialchars($u['username']); ?></td>
<td><?php echo htmlspecialchars($u['email']); ?></td>
<td><?php echo $u['role']; ?></td>

</tr>

<?php endwhile; ?>

</table>

</div>

</div>

<!-- GOOGLE MAP SCRIPT -->

<script async
src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap">
</script>

</body>
</html>