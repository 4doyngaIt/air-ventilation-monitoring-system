<?php
$page_title = 'Dashboard';
include 'header.php';

// System stats
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_sensors = $conn->query("SELECT COUNT(*) FROM sensors")->fetch_row()[0];
$active_sensors = $conn->query("SELECT COUNT(*) FROM sensors WHERE status='Active'")->fetch_row()[0];
$inactive_sensors = $conn->query("SELECT COUNT(*) FROM sensors WHERE status='Inactive'")->fetch_row()[0];
$maintenance_sensors = $conn->query("SELECT COUNT(*) FROM sensors WHERE status='maintenance'")->fetch_row()[0];

$vents_on_result = $conn->query("
    SELECT COUNT(DISTINCT sr.sensor_id) as vent_count
    FROM sensor_readings sr
    INNER JOIN (
        SELECT sensor_id, MAX(created_at) as max_date
        FROM sensor_readings
        GROUP BY sensor_id
    ) latest ON sr.sensor_id = latest.sensor_id AND sr.created_at = latest.max_date
    WHERE sr.vent_state = 'on'
");
$vents_on = $vents_on_result->fetch_row()[0];

// System activity for chart
$system_history = [];
$activity_logs_result = $conn->query("SHOW TABLES LIKE 'activity_logs'");
if($activity_logs_result->num_rows > 0) {
    $sys_result = $conn->query("
        SELECT DATE_FORMAT(created_at, '%H:00') as hour, COUNT(*) as actions
        FROM activity_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY hour ORDER BY created_at ASC
    ");
    while($row = $sys_result->fetch_assoc()) $system_history[] = $row;
}

// Calculate sensors with rain
$rain_sensors = $conn->query("
    SELECT COUNT(DISTINCT sr.sensor_id) as rain_count
    FROM sensor_readings sr
    INNER JOIN (
        SELECT sensor_id, MAX(created_at) as max_date
        FROM sensor_readings
        GROUP BY sensor_id
    ) latest ON sr.sensor_id = latest.sensor_id AND sr.created_at = latest.max_date
    WHERE sr.rainfall > 0
")->fetch_row()[0];

// Average temperature
$avg_temp = $conn->query("
    SELECT AVG(temperature) as avg_temp
    FROM sensor_readings sr
    INNER JOIN (
        SELECT sensor_id, MAX(created_at) as max_date
        FROM sensor_readings
        GROUP BY sensor_id
    ) latest ON sr.sensor_id = latest.sensor_id AND sr.created_at = latest.max_date
")->fetch_assoc()['avg_temp'] ?? 0;
?>

<?php if(isset($_GET['msg'])): ?>
<div class="alert alert-<?php echo $_GET['type'] ?? 'success'; ?>">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
</div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Active Sensors</div>
        <div class="stat-value"><?php echo $active_sensors; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sensors Running</div>
        <div class="stat-value"><?php echo $vents_on; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Avg Temperature</div>
        <div class="stat-value"><?php echo number_format($avg_temp, 1); ?>°C</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sensors with Rain</div>
        <div class="stat-value"><?php echo $rain_sensors; ?></div>
    </div>
</div>

<!-- Charts -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-chart-pie" style="color: var(--primary);"></i>
                Sensor Status Distribution
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="sensorStatusPieChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-chart-bar" style="color: var(--primary);"></i>
                System Activity (24 Hours)
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="systemChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Pie Chart
new Chart(document.getElementById('sensorStatusPieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Inactive', 'Maintenance'],
        datasets: [{
            data: [<?php echo "$active_sensors, $inactive_sensors, $maintenance_sensors"; ?>],
            backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
            borderColor: ['#ffffff', '#ffffff', '#ffffff'],
            borderWidth: 2,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                position: 'bottom', 
                labels: { padding: 20, usePointStyle: true } 
            }
        },
        cutout: '60%'
    }
});

// Bar Chart
<?php if(!empty($system_history)): ?>
new Chart(document.getElementById('systemChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($system_history, 'hour')); ?>,
        datasets: [{
            label: 'Admin Actions',
            data: <?php echo json_encode(array_column($system_history, 'actions')); ?>,
            backgroundColor: '#10b981',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});
<?php else: ?>
new Chart(document.getElementById('systemChart'), {
    type: 'bar',
    data: {
        labels: ['No Data'],
        datasets: [{
            label: 'Admin Actions',
            data: [0],
            backgroundColor: '#e2e8f0',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { display: false },
            title: { display: true, text: 'No activity in the last 24 hours' }
        },
        scales: { 
            y: { beginAtZero: true, max: 1 }, 
            x: { grid: { display: false } } 
        }
    }
});
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>