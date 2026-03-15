<?php
if(session_status() === PHP_SESSION_NONE){
session_start();
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Air Ventilation Monitoring</title>

<link rel="stylesheet" href="../../assets/style.css">

</head>

<body>

<div class="sidebar">

<div class="logo">
AirVent System
</div>

<a href="dashboard.php">Dashboard</a>
<a href="weather.php">Weather Monitor</a>
<a href="ventilation.php">Ventilation Control</a>


<a href="../../login/login.php">Logout</a>

</div>

<div class="main">