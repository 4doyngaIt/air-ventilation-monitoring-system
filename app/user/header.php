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

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>

<div class="sidebar">

    <div class="logo">
        <i class="fa-solid fa-wind"></i> AirVent System
    </div>

    <a href="dashboard.php">
        <i class="fa-solid fa-chart-line"></i> Dashboard
    </a>

    <a href="weather.php">
        <i class="fa-solid fa-cloud-sun"></i> Weather Monitor
    </a>

    <a href="ventilation.php">
        <i class="fa-solid fa-fan"></i> Ventilation Control
    </a>

    <!-- Logout Button -->
    <a href="../../login/login.php" class="logout">
        <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </a>

</div>

<div class="main">