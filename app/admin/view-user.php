<?php
$page_title = 'View User Sensors';
include 'header.php';

$viewing_user_id = isset($_GET['view_user']) ? intval($_GET['view_user']) : null;

if(!$viewing_user_id) {
    header("Location: user-management.php");
    exit();
}

$view_user = $conn->query("SELECT * FROM users WHERE user_id = $viewing_user_id")->fetch_assoc();

if(!$view_user) {
    header("Location: user-management.php");
    exit();
}

// Get all sensors with latest readings
$user_sensors = $conn->query("
    SELECT DISTINCT s.*, 
           sr.temperature, sr.humidity, sr.weather_condition, sr.vent_state, sr.mode,
           sr.rainfall, sr.wind_speed, sr.sunlight_intensity
    FROM sensors s
    LEFT JOIN (
        SELECT sr1.* FROM sensor_readings sr1
        INNER JOIN (
            SELECT sensor_id, MAX(created_at) as max_date 
            FROM sensor_readings 
            GROUP BY sensor_id
        ) sr2 ON sr1.sensor_id = sr2.sensor_id AND sr1.created_at = sr2.max_date
    ) sr ON s.sensor_id = sr.sensor_id
    ORDER BY s.sensor_id ASC
");

// 24h history for charts
$user_history = [];
$uh_result = $conn->query("
    SELECT DATE_FORMAT(created_at, '%H:00') as hour, 
           AVG(temperature) as temp, 
           AVG(humidity) as humidity
    FROM sensor_readings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY hour ORDER BY created_at ASC
");
while($row = $uh_result->fetch_assoc()) $user_history[] = $row;
?>

<h3 style="margin-bottom: 1.5rem; color: var(--text);">
    <i class="fas fa-satellite-dish" style="color: var(--primary);"></i> 
    Sensor Monitoring for <?php echo htmlspecialchars($view_user['username']); ?>
</h3>

<div class="sensor-grid">
    <?php while($sensor = $user_sensors->fetch_assoc()): ?>
    <div class="sensor-card">
        <div class="sensor-header">
            <div>
                <div class="sensor-name">Sensor <?php echo $sensor['sensor_id']; ?> — <?php echo htmlspecialchars($sensor['location']); ?></div>
            </div>
            <div class="sensor-status <?php echo $sensor['vent_state']; ?>">
                <?php echo strtoupper($sensor['vent_state']); ?> (<?php echo strtoupper($sensor['mode']); ?>)
            </div>
        </div>
        
        <div class="sensor-data">
            <div class="sensor-data-item">
                <i class="fas fa-thermometer-half"></i>
                <strong>Temperature:</strong> <?php echo $sensor['temperature'] ? round($sensor['temperature'], 1) : '--'; ?> °C
            </div>
            <div class="sensor-data-item">
                <i class="fas fa-tint"></i>
                <strong>Humidity:</strong> <?php echo $sensor['humidity'] ? round($sensor['humidity'], 0) : '--'; ?> %
            </div>
            <div class="sensor-data-item">
                <i class="fas fa-cloud-rain"></i>
                <strong>Rainfall:</strong> <?php echo $sensor['rainfall'] ?? '0'; ?> mm
            </div>
            <div class="sensor-data-item">
                <i class="fas fa-wind"></i>
                <strong>Wind:</strong> <?php echo $sensor['wind_speed'] ?? '0'; ?> m/s
            </div>
            <div class="sensor-data-item">
                <i class="fas fa-sun"></i>
                <strong>Sunlight:</strong> <?php echo $sensor['sunlight_intensity'] ?? '0'; ?>
            </div>
            <div class="sensor-data-item">
                <i class="fas fa-cloud"></i>
                <strong>Condition:</strong> <?php echo $sensor['weather_condition'] ?? 'N/A'; ?>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-chart-area" style="color: var(--primary);"></i>
            24-Hour Environment History
        </div>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="userChart"></canvas>
        </div>
    </div>
</div>

<?php if(!empty($user_history)): ?>
<script>
new Chart(document.getElementById('userChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($user_history, 'hour')); ?>,
        datasets: [{
            label: 'Temperature °C',
            data: <?php echo json_encode(array_column($user_history, 'temp')); ?>,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            fill: true,
            tension: 0.4
        }, {
            label: 'Humidity %',
            data: <?php echo json_encode(array_column($user_history, 'humidity')); ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { position: 'top', align: 'end' } 
        },
        scales: {
            y: { 
                position: 'left', 
                title: { display: true, text: '°C' },
                grid: { color: '#f1f5f9' }
            },
            y1: { 
                position: 'right', 
                title: { display: true, text: '%' }, 
                grid: { display: false } 
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>