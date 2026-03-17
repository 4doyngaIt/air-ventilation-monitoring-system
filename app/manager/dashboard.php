<?php
session_start();
include __DIR__ . "/../../config/db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager'){
    header("Location: ../../login/login.php");
    exit();
}

/* CHECK IF last_updated COLUMN EXISTS */
$check_column = $conn->query("SHOW COLUMNS FROM sensors LIKE 'last_updated'");
$has_last_updated = ($check_column && $check_column->num_rows > 0);

/* USERS */
$users_result = $conn->query("SELECT user_id,username,email,role FROM users ORDER BY user_id ASC");

/* SENSOR TABLE - Enhanced with recent data */
$sensor_table = $conn->query("SELECT * FROM sensors ORDER BY sensor_id ASC");

/* SENSOR MAP DATA */
$map_sensors = [];
$map_query = $conn->query("SELECT sensor_id,location,latitude,longitude,status FROM sensors");

if($map_query){
    while($row = $map_query->fetch_assoc()){
        $map_sensors[] = $row;
    }
}

/* TOTAL COUNTS */
$total_users = ($users_result) ? $users_result->num_rows : 0;
$total_sensors = ($sensor_table) ? $sensor_table->num_rows : 0;

/* STATUS COUNTS FOR CHARTS */
$active_sensors = $conn->query("SELECT COUNT(*) as count FROM sensors WHERE status='active'")->fetch_assoc()['count'] ?? 0;
$inactive_sensors = $total_sensors - $active_sensors;

/* ALERTS - Sensors needing attention (only if last_updated exists) */
$alerts = [];
if($has_last_updated) {
    $alert_query = $conn->query("SELECT * FROM sensors WHERE status != 'active' OR last_updated < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    if($alert_query){
        while($row = $alert_query->fetch_assoc()){
            $alerts[] = $row;
        }
    }
} else {
    // Fallback: just check for inactive sensors
    $alert_query = $conn->query("SELECT * FROM sensors WHERE status != 'active'");
    if($alert_query){
        while($row = $alert_query->fetch_assoc()){
            $alerts[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Manager Dashboard | Air Ventilation System</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #f3f4f6;
    min-height: 100vh;
    display: flex;
    color: #1f2937;
}

/* Dark Sidebar - Matching Image Design */
.sidebar {
    width: 260px;
    background: #1e293b;
    height: 100vh;
    display: flex;
    flex-direction: column;
    padding: 0;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1000;
}

.sidebar-brand {
    padding: 24px 24px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-brand i {
    font-size: 1.5rem;
    color: #10b981;
}

.sidebar-brand h2 {
    color: #10b981;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: -0.5px;
}

.sidebar-nav {
    flex: 1;
    padding: 0 12px;
}

.sidebar a {
    color: #94a3b8;
    padding: 14px 16px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    margin: 4px 0;
    border-radius: 8px;
    border-left: 3px solid transparent;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.05);
    color: #e2e8f0;
}

.sidebar a.active {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border-left-color: #10b981;
}

.sidebar a i {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.logout-btn {
    margin-top: auto;
    border-top: 1px solid #e2e8f0;
    padding-top: 20px;
}

/* Main Content */
.main {
    flex: 1;
    margin-left: 260px;
    padding: 40px;
    overflow-y: auto;
}

.header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 24px 32px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h1 {
    color: #2d3748;
    font-size: 1.8rem;
    font-weight: 700;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #718096;
    font-weight: 500;
}

.user-info i {
    font-size: 2rem;
    color: #667eea;
}

/* Cards Grid */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 28px;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 16px;
    background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
    color: #667eea;
}

.card h3 {
    color: #718096;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.card .value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2d3748;
    line-height: 1;
}

/* Section Containers */
.section {
    display: none;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.content-box {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 32px;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.content-box h2 {
    color: #2d3748;
    margin-bottom: 24px;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Map Styling */
.map-container {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

#map {
    width: 100%;
    height: 600px;
    z-index: 1;
}

.map-legend {
    position: absolute;
    bottom: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1000;
    font-size: 0.9rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

/* ENHANCED SENSOR MONITORING STYLES */
.sensor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.sensor-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border: 2px solid transparent;
}

.sensor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.sensor-card.alert {
    border-color: #fc8181;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(252, 129, 129, 0.4); }
    50% { box-shadow: 0 0 0 10px rgba(252, 129, 129, 0); }
}

.sensor-card.inactive {
    opacity: 0.7;
    background: rgba(247, 250, 252, 0.95);
}

.sensor-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.sensor-id {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sensor-location {
    color: #718096;
    font-size: 0.9rem;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sensor-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sensor-status.active {
    background: #c6f6d5;
    color: #22543d;
}

.sensor-status.inactive {
    background: #fed7d7;
    color: #742a2a;
}

.sensor-metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.metric {
    background: #f7fafc;
    padding: 16px;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s ease;
}

.metric:hover {
    background: #edf2f7;
    transform: scale(1.02);
}

.metric-icon {
    font-size: 1.8rem;
    margin-bottom: 8px;
}

.metric-label {
    font-size: 0.75rem;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.metric-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2d3748;
}

.metric-value.detected {
    color: #48bb78;
}

.metric-value.not-detected {
    color: #a0aec0;
}

.sensor-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    font-size: 0.85rem;
    color: #718096;
}

.last-updated {
    display: flex;
    align-items: center;
    gap: 6px;
}

.sensor-actions {
    display: flex;
    gap: 8px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #edf2f7;
    color: #4a5568;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: #667eea;
    color: white;
    transform: scale(1.1);
}

/* Filter Bar */
.filter-bar {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 20px 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-group label {
    font-weight: 600;
    color: #4a5568;
    font-size: 0.9rem;
}

.filter-group select,
.filter-group input {
    padding: 10px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-family: inherit;
    font-size: 0.9rem;
    background: white;
    transition: all 0.2s;
    min-width: 150px;
}

.filter-group select:focus,
.filter-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.search-box input {
    width: 100%;
    padding: 10px 16px 10px 40px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-family: inherit;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
}

.search-box input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Alert Banner */
.alert-banner {
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
    color: white;
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 15px rgba(245, 101, 101, 0.3);
}

.alert-banner i {
    font-size: 1.5rem;
}

.alert-content {
    flex: 1;
    margin-left: 16px;
}

.alert-title {
    font-weight: 700;
    margin-bottom: 2px;
}

.alert-text {
    font-size: 0.9rem;
    opacity: 0.95;
}

/* View Toggle */
.view-toggle {
    display: flex;
    background: #edf2f7;
    padding: 4px;
    border-radius: 10px;
    gap: 4px;
}

.view-toggle button {
    padding: 8px 16px;
    border: none;
    background: transparent;
    color: #718096;
    font-weight: 600;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.view-toggle button.active {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Table View Styles */
.table-container {
    overflow-x: auto;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    background: white;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

th, td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    position: sticky;
    top: 0;
}

tr:hover {
    background: #f7fafc;
}

tr:last-child td {
    border-bottom: none;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.status-badge::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.status-badge.active {
    background: #c6f6d5;
    color: #22543d;
}

.status-badge.inactive {
    background: #fed7d7;
    color: #742a2a;
}

.sensor-indicator {
    font-size: 1.2rem;
}

/* Real-time indicator */
.live-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #48bb78;
    font-weight: 600;
}

.live-indicator::before {
    content: '';
    width: 8px;
    height: 8px;
    background: #48bb78;
    border-radius: 50%;
    animation: blink 1.5s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: relative;
        height: auto;
    }
    .main {
        margin-left: 0;
    }
    .sensor-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function showSection(id){
    let sections = document.querySelectorAll('.section');
    sections.forEach(function(section){
        section.style.display = 'none';
    });
    document.getElementById(id).style.display = 'block';
    
    // Update active state in sidebar
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.classList.remove('active');
    });
    event.target.closest('a').classList.add('active');
    
    // Trigger map resize if map section
    if(id === 'mapSection' && window.map) {
        setTimeout(() => {
            window.map.invalidateSize();
        }, 100);
    }
}

window.onload = function(){
    showSection('home');
    // Set first link as active
    document.querySelector('.sidebar a').classList.add('active');
    
    // Initialize auto-refresh for sensor data
    initSensorRefresh();
}

// Sensor View Toggle
function toggleSensorView(view) {
    document.querySelectorAll('.view-toggle button').forEach(btn => btn.classList.remove('active'));
    event.target.closest('button').classList.add('active');
    
    if(view === 'grid') {
        document.getElementById('sensorGridView').style.display = 'grid';
        document.getElementById('sensorTableView').style.display = 'none';
    } else {
        document.getElementById('sensorGridView').style.display = 'none';
        document.getElementById('sensorTableView').style.display = 'block';
    }
}

// Filter Sensors
function filterSensors() {
    const searchTerm = document.getElementById('sensorSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const envFilter = document.getElementById('envFilter').value;
    
    document.querySelectorAll('.sensor-card').forEach(card => {
        const location = card.dataset.location.toLowerCase();
        const status = card.dataset.status;
        const sunHeat = card.dataset.sun;
        const rain = card.dataset.rain;
        
        let show = location.includes(searchTerm);
        
        if(statusFilter !== 'all' && status !== statusFilter) show = false;
        if(envFilter === 'sun' && sunHeat !== 'yes') show = false;
        if(envFilter === 'rain' && rain !== 'yes') show = false;
        if(envFilter === 'clear' && (sunHeat === 'yes' || rain === 'yes')) show = false;
        
        card.style.display = show ? 'block' : 'none';
    });
}

// Simulate Real-time Updates
function initSensorRefresh() {
    setInterval(() => {
        // Update timestamps
        document.querySelectorAll('.timestamp').forEach(el => {
            el.textContent = 'Just now';
            el.style.color = '#48bb78';
            setTimeout(() => {
                el.style.color = '#718096';
            }, 2000);
        });
    }, 30000); // Every 30 seconds
}

// Export Sensor Data
function exportSensorData() {
    alert('Exporting sensor data to CSV...');
    // Implementation would generate CSV from table data
}

// Refresh Single Sensor
function refreshSensor(id) {
    const btn = event.target.closest('.btn-icon');
    btn.style.animation = 'spin 1s linear';
    setTimeout(() => {
        btn.style.animation = '';
        // Simulate data refresh
        alert('Sensor #' + id + ' data refreshed');
    }, 1000);
}

// View Sensor Details
function viewSensorDetails(id) {
    // Would open modal or navigate to detail page
    alert('Opening detailed view for Sensor #' + id);
}
</script>
</head>

<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <h2><i class="fas fa-wind"></i> AirVent Pro</h2>
    </div>
    
    <a href="#" onclick="showSection('home')">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="#" onclick="showSection('mapSection')">
        <i class="fas fa-map-marked-alt"></i> Sensor Map
    </a>
    <a href="#" onclick="showSection('sensor')">
        <i class="fas fa-broadcast-tower"></i> Monitor Sensors
    </a>
    <a href="#" onclick="showSection('users')">
        <i class="fas fa-users"></i> View Users
    </a>
    
    <div class="logout-btn">
        <a href="../../login/login.php" style="color: #e53e3e;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="main">

    <!-- Header -->
    <div class="header">
        <h1>Manager Dashboard</h1>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
    </div>

    <!-- HOME SECTION -->
    <div id="home" class="section">
        <div class="cards">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-broadcast-tower"></i>
                </div>
                <h3>Total Sensors</h3>
                <div class="value"><?php echo $total_sensors; ?></div>
            </div>
            
            <div class="card">
                <div class="card-icon" style="background: linear-gradient(135deg, #48bb7820 0%, #38a16920 100%); color: #48bb78;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Active Sensors</h3>
                <div class="value" style="color: #48bb78;"><?php echo $active_sensors; ?></div>
            </div>
            
            <div class="card">
                <div class="card-icon" style="background: linear-gradient(135deg, #ed893620 0%, #dd6b2020 100%); color: #ed8936;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Inactive Sensors</h3>
                <div class="value" style="color: #ed8936;"><?php echo $inactive_sensors; ?></div>
            </div>
            
            <div class="card">
                <div class="card-icon" style="background: linear-gradient(135deg, #4299e120 0%, #3182ce20 100%); color: #4299e1;">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Total Users</h3>
                <div class="value" style="color: #4299e1;"><?php echo $total_users; ?></div>
            </div>
        </div>
        
        <div class="content-box">
            <h2><i class="fas fa-chart-line"></i> System Overview</h2>
            <p style="color: #718096; line-height: 1.6;">
                Welcome to the Air Ventilation Management System. Use the sidebar to navigate through different sections. 
                The Sensor Map provides a visual representation of all sensor locations with coverage area polygon.
                Monitor real-time sensor data including sun heat and rain detection.
            </p>
        </div>
    </div>

    <!-- MAP SECTION WITH POLYGON -->
    <div id="mapSection" class="section">
        <div class="content-box">
            <h2><i class="fas fa-map-marked-alt"></i> Sensor Coverage Area</h2>
            <p style="color: #718096; margin-bottom: 20px;">
                Visualizing sensor locations with convex hull polygon showing the complete coverage boundary.
            </p>
        </div>
        
        <div class="map-container">
            <div id="map"></div>
            <div class="map-legend">
                <div class="legend-item">
                    <div class="legend-color" style="background: rgba(102, 126, 234, 0.3); border: 2px solid #667eea;"></div>
                    <span>Coverage Area</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #667eea; border-radius: 50%;"></div>
                    <span>Active Sensor</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #e53e3e; border-radius: 50%;"></div>
                    <span>Inactive Sensor</span>
                </div>
            </div>
        </div>
    </div>

    <!-- UPGRADED SENSOR MONITORING SECTION -->
    <div id="sensor" class="section">
        
        <!-- Alert Banner -->
        <?php if(count($alerts) > 0): ?>
        <div class="alert-banner">
            <i class="fas fa-bell"></i>
            <div class="alert-content">
                <div class="alert-title"><?php echo count($alerts); ?> Sensor(s) Require Attention</div>
                <div class="alert-text">Some sensors are inactive or haven't reported data recently.</div>
            </div>
            <button onclick="filterSensorsByAlert()" style="background: white; color: #f56565; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                View Alerts
            </button>
        </div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="sensorSearch" placeholder="Search by location..." onkeyup="filterSensors()">
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Status:</label>
                <select id="statusFilter" onchange="filterSensors()">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-cloud-sun"></i> Environment:</label>
                <select id="envFilter" onchange="filterSensors()">
                    <option value="all">All Conditions</option>
                    <option value="sun">Sun Heat Detected</option>
                    <option value="rain">Rain Detected</option>
                    <option value="clear">Clear Conditions</option>
                </select>
            </div>
            
            <div style="margin-left: auto; display: flex; gap: 12px;">
                <div class="view-toggle">
                    <button class="active" onclick="toggleSensorView('grid')">
                        <i class="fas fa-th-large"></i> Grid
                    </button>
                    <button onclick="toggleSensorView('table')">
                        <i class="fas fa-list"></i> Table
                    </button>
                </div>
                
                <button onclick="exportSensorData()" class="btn-icon" style="width: auto; padding: 0 16px; background: #667eea; color: white;">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Live Indicator -->
        <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
            <div class="live-indicator">
                <span>LIVE DATA</span>
            </div>
            <span style="color: #718096; font-size: 0.9rem;">
                Auto-refresh every 30s • Last updated: <span id="lastUpdate"><?php echo date('H:i:s'); ?></span>
            </span>
        </div>

        <!-- GRID VIEW -->
        <div id="sensorGridView" class="sensor-grid">
            <?php
            if($sensor_table && $sensor_table->num_rows > 0){
                mysqli_data_seek($sensor_table, 0); // Reset pointer
                while($sensor = $sensor_table->fetch_assoc()){
                    $sun_heat = isset($sensor['sun_heat']) ? $sensor['sun_heat'] : "no";
                    $rain = isset($sensor['rain']) ? $sensor['rain'] : "no";
                    
                    // Handle last_updated safely
                    if($has_last_updated && isset($sensor['last_updated'])) {
                        $last_updated = $sensor['last_updated'];
                        $is_stale = strtotime($last_updated) < strtotime('-1 hour');
                    } else {
                        $last_updated = "N/A";
                        $is_stale = false;
                    }
                    
                    $is_active = ($sensor['status'] === 'active');
                    $needs_attention = !$is_active || ($has_last_updated && $is_stale);
                    
                    $card_class = $needs_attention ? 'sensor-card alert' : 'sensor-card';
                    if(!$is_active) $card_class .= ' inactive';
            ?>
            <div class="<?php echo $card_class; ?>" 
                 data-location="<?php echo htmlspecialchars($sensor['location']); ?>"
                 data-status="<?php echo $sensor['status']; ?>"
                 data-sun="<?php echo $sun_heat; ?>"
                 data-rain="<?php echo $rain; ?>">
                
                <div class="sensor-header">
                    <div>
                        <div class="sensor-id">
                            <i class="fas fa-microchip"></i>
                            Sensor #<?php echo $sensor['sensor_id']; ?>
                        </div>
                        <div class="sensor-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($sensor['location']); ?>
                        </div>
                    </div>
                    <span class="sensor-status <?php echo $sensor['status']; ?>">
                        <?php echo $sensor['status']; ?>
                    </span>
                </div>

                <div class="sensor-metrics">
                    <div class="metric">
                        <div class="metric-icon">☀️</div>
                        <div class="metric-label">Sun Heat</div>
                        <div class="metric-value <?php echo ($sun_heat=='yes') ? 'detected' : 'not-detected'; ?>">
                            <?php echo ($sun_heat=="yes") ? "Detected" : "None"; ?>
                        </div>
                    </div>
                    
                    <div class="metric">
                        <div class="metric-icon">🌧️</div>
                        <div class="metric-label">Rain</div>
                        <div class="metric-value <?php echo ($rain=='yes') ? 'detected' : 'not-detected'; ?>">
                            <?php echo ($rain=="yes") ? "Detected" : "None"; ?>
                        </div>
                    </div>
                </div>

                <div class="sensor-footer">
                    <div class="last-updated">
                        <i class="fas fa-clock"></i>
                        <span class="timestamp"><?php echo $last_updated; ?></span>
                    </div>
                    
                    <div class="sensor-actions">
                        <button class="btn-icon" onclick="refreshSensor(<?php echo $sensor['sensor_id']; ?>)" title="Refresh Data">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="btn-icon" onclick="viewSensorDetails(<?php echo $sensor['sensor_id']; ?>)" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
            ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #a0aec0;">
                <i class="fas fa-satellite-dish" style="font-size: 4rem; margin-bottom: 20px; display: block;"></i>
                <h3>No Sensors Found</h3>
                <p>Add sensors to the system to begin monitoring.</p>
            </div>
            <?php } ?>
        </div>

        <!-- TABLE VIEW (Hidden by default) -->
        <div id="sensorTableView" style="display: none;">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Sun Heat</th>
                            <th>Rain</th>
                            <?php if($has_last_updated): ?>
                            <th>Last Updated</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if($sensor_table && $sensor_table->num_rows > 0){
                            mysqli_data_seek($sensor_table, 0); // Reset pointer
                            while($sensor = $sensor_table->fetch_assoc()){
                                $sun_heat = isset($sensor['sun_heat']) ? $sensor['sun_heat'] : "no";
                                $rain = isset($sensor['rain']) ? $sensor['rain'] : "no";
                                $status_class = ($sensor['status'] === 'active') ? 'active' : 'inactive';
                                $last_updated_val = ($has_last_updated && isset($sensor['last_updated'])) ? $sensor['last_updated'] : 'N/A';
                        ?>
                        <tr>
                            <td><strong>#<?php echo $sensor['sensor_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($sensor['location']); ?></td>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($sensor['status']); ?></span></td>
                            <td class="sensor-indicator"><?php echo ($sun_heat=="yes") ? "☀️ Detected" : "❌ None"; ?></td>
                            <td class="sensor-indicator"><?php echo ($rain=="yes") ? "🌧️ Detected" : "❌ None"; ?></td>
                            <?php if($has_last_updated): ?>
                            <td><?php echo $last_updated_val; ?></td>
                            <?php endif; ?>
                            <td>
                                <button class="btn-icon" onclick="refreshSensor(<?php echo $sensor['sensor_id']; ?>)" title="Refresh">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="btn-icon" onclick="viewSensorDetails(<?php echo $sensor['sensor_id']; ?>)" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- USERS SECTION -->
    <div id="users" class="section">
        <div class="content-box">
            <h2><i class="fas fa-users"></i> System Users</h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if($users_result && $users_result->num_rows > 0){
                            while($u = $users_result->fetch_assoc()){
                                $role_colors = [
                                    'manager' => '#667eea',
                                    'admin' => '#48bb78',
                                    'user' => '#ed8936'
                                ];
                                $role_color = $role_colors[$u['role']] ?? '#718096';
                        ?>
                        <tr>
                            <td><strong>#<?php echo $u['user_id']; ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: <?php echo $role_color; ?>20; color: <?php echo $role_color; ?>; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                        <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($u['username']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span style="padding: 6px 12px; border-radius: 20px; background: <?php echo $role_color; ?>20; color: <?php echo $role_color; ?>; font-weight: 600; font-size: 0.85rem; text-transform: capitalize;">
                                    <?php echo $u['role']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
// Convex Hull Algorithm (Graham Scan) to create polygon from sensor points
function convexHull(points) {
    if (points.length < 3) return points;
    
    let start = 0;
    for (let i = 1; i < points.length; i++) {
        if (points[i].lat < points[start].lat || 
            (points[i].lat === points[start].lat && points[i].lng < points[start].lng)) {
            start = i;
        }
    }
    
    [points[0], points[start]] = [points[start], points[0]];
    const pivot = points[0];
    
    const sorted = points.slice(1).sort((a, b) => {
        const angleA = Math.atan2(a.lat - pivot.lat, a.lng - pivot.lng);
        const angleB = Math.atan2(b.lat - pivot.lat, b.lng - pivot.lng);
        if (angleA === angleB) {
            const distA = Math.pow(a.lat - pivot.lat, 2) + Math.pow(a.lng - pivot.lng, 2);
            const distB = Math.pow(b.lat - pivot.lat, 2) + Math.pow(b.lng - pivot.lng, 2);
            return distA - distB;
        }
        return angleA - angleB;
    });
    
    const hull = [pivot];
    for (let point of sorted) {
        while (hull.length > 1 && !isLeftTurn(hull[hull.length - 2], hull[hull.length - 1], point)) {
            hull.pop();
        }
        hull.push(point);
    }
    
    return hull;
}

function isLeftTurn(p1, p2, p3) {
    return (p2.lng - p1.lng) * (p3.lat - p1.lat) - (p2.lat - p1.lat) * (p3.lng - p1.lng) > 0;
}

// Initialize Map
var sensors = <?php echo json_encode($map_sensors); ?>;

var validSensors = sensors.filter(function(sensor) {
    return sensor.latitude && sensor.longitude && 
           !isNaN(parseFloat(sensor.latitude)) && 
           !isNaN(parseFloat(sensor.longitude));
});

var centerLat = validSensors.length > 0 ? 
    validSensors.reduce((sum, s) => sum + parseFloat(s.latitude), 0) / validSensors.length : 7.0731;
var centerLng = validSensors.length > 0 ? 
    validSensors.reduce((sum, s) => sum + parseFloat(s.longitude), 0) / validSensors.length : 125.6128;

window.map = L.map('map').setView([centerLat, centerLng], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

var sensorPoints = [];
validSensors.forEach(function(sensor) {
    var lat = parseFloat(sensor.latitude);
    var lng = parseFloat(sensor.longitude);
    sensorPoints.push({lat: lat, lng: lng, sensor: sensor});
    
    var markerColor = sensor.status === 'active' ? '#667eea' : '#e53e3e';
    
    var customIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background-color: ' + markerColor + '; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    });
    
    var marker = L.marker([lat, lng], {icon: customIcon}).addTo(map);
    marker.bindPopup(
        '<div style="font-family: Inter, sans-serif; padding: 8px;">' +
        '<h3 style="margin: 0 0 8px 0; color: #2d3748; font-size: 1.1rem;">Sensor #' + sensor.sensor_id + '</h3>' +
        '<p style="margin: 4px 0; color: #718096; font-size: 0.9rem;"><strong>Location:</strong> ' + sensor.location + '</p>' +
        '<p style="margin: 4px 0; color: #718096; font-size: 0.9rem;"><strong>Status:</strong> <span style="color: ' + markerColor + '; font-weight: 600;">' + sensor.status + '</span></p>' +
        '<p style="margin: 4px 0; color: #718096; font-size: 0.9rem;"><strong>Coords:</strong> ' + lat.toFixed(4) + ', ' + lng.toFixed(4) + '</p>' +
        '</div>'
    );
});

if (sensorPoints.length >= 3) {
    var hullPoints = convexHull(sensorPoints.map(p => ({lat: p.lat, lng: p.lng})));
    var hullLatLngs = hullPoints.map(p => [p.lat, p.lng]);
    
    if (hullLatLngs.length > 0) {
        hullLatLngs.push(hullLatLngs[0]);
    }
    
    var polygon = L.polygon(hullLatLngs, {
        color: '#667eea',
        weight: 3,
        opacity: 0.8,
        fillColor: '#667eea',
        fillOpacity: 0.15,
        dashArray: '5, 10',
        lineCap: 'round'
    }).addTo(map);
    
    var group = new L.featureGroup(validSensors.map(s => 
        L.marker([parseFloat(s.latitude), parseFloat(s.longitude)])
    ));
    map.fitBounds(group.getBounds().pad(0.2));
} else if (sensorPoints.length > 0) {
    var group = new L.featureGroup(validSensors.map(s => 
        L.marker([parseFloat(s.latitude), parseFloat(s.longitude)])
    ));
    map.fitBounds(group.getBounds().pad(0.2));
}
</script>

</body>
</html>