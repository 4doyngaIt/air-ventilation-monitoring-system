<?php
session_start();
include "../db.php"; // Adjust path to your db.php

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../user/login.php");
    exit();
}

$message = "";

// ── Handle Edit User ───────────────────────────────
if(isset($_POST['edit_user'])){
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE user_id=?");
    $stmt->bind_param("sssi", $username, $email, $role, $id);
    if($stmt->execute()){
        $message = "User updated successfully!";
    } else {
        $message = "Failed to update user!";
    }
}

// ── Handle Delete User ─────────────────────────────
if(isset($_GET['delete_user'])){
    $id = $_GET['delete_user'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        $message = "User deleted successfully!";
    } else {
        $message = "Failed to delete user!";
    }
}

// ── Fetch Users from DB ────────────────────────────
$users = [];
$result = $conn->query("SELECT user_id, username, email, role FROM users");
while($row = $result->fetch_assoc()){
    $users[] = $row;
}

// ── Fetch Sensors from DB ──────────────────────────
$sensors = [];
$sql = "SELECT s.sensor_id, s.location, s.status, r.temperature, r.humidity, r.created_at 
        FROM sensors s 
        LEFT JOIN sensor_readings r ON s.sensor_id = r.sensor_id
        AND r.created_at = (SELECT MAX(created_at) FROM sensor_readings WHERE sensor_id = s.sensor_id)
        ORDER BY s.sensor_id ASC";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
    $row['temperature'] = isset($row['temperature']) ? $row['temperature'] . '°C' : 'N/A';
    $row['humidity'] = isset($row['humidity']) ? $row['humidity'] . '%' : 'N/A';
    $row['last_updated'] = isset($row['created_at']) ? $row['created_at'] : 'N/A';
    $sensors[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Air Ventilation System</title>
<style>
body { font-family: Arial, sans-serif; margin:0; padding:0; display:flex; background:#f4f7f9; }
.sidebar { width: 180px; background: #0077cc; color: #fff; height: 100vh; display: flex; flex-direction: column; padding-top: 20px; }
.sidebar a { color:#fff; padding:15px 20px; text-decoration:none; display:block; font-weight:bold; }
.sidebar a:hover { background:#005fa3; }
.main { flex:1; padding: 20px; }
h2 { color: #0077cc; }
table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
th { background: #0077cc; color: #fff; }
button { padding: 6px 12px; margin: 2px; border: none; border-radius: 4px; cursor: pointer; }
.edit-btn { background: #17a2b8; color: #fff; }
.delete-btn { background: #dc3545; color: #fff; }
form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; width: 400px; margin: 20px auto; }
input, select { width: 100%; padding: 8px; margin: 5px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; }
input[type="submit"] { background: #0077cc; color: #fff; border: none; cursor: pointer; }
input[type="submit"]:hover { background: #005fa3; }
.message { width: 400px; margin: 10px auto; padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; }
.section { display:none; }
</style>
<script>
function showSection(id){
    let sections = document.querySelectorAll('.section');
    sections.forEach(s => s.style.display = 'none');
    document.getElementById(id).style.display = 'block';
}
function showEditForm(id){
    document.getElementById('edit-form-'+id).style.display = 'block';
}
window.onload = function(){ showSection('home'); };
</script>
</head>
<body>

<div class="sidebar">
    <a href="#" onclick="showSection('home')">HOME</a>
    <a href="#" onclick="showSection('sensor')">Monitor Sensor</a>
    <a href="#" onclick="showSection('users')">Manage Users</a>
    <a href="../user/logout.php">Logout</a>
</div>

<div class="main">

    <!-- HOME Section -->
    <div id="home" class="section">
        <h2>Welcome, Admin!</h2>
        <p>Total Sensors Installed: <strong><?= count($sensors) ?></strong></p>
        <p>Number of Users: <strong><?= count($users) ?></strong></p>
    </div>

    <!-- Monitor Sensor Section -->
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
            <?php foreach($sensors as $sensor): ?>
            <tr>
                <td><?= $sensor['sensor_id'] ?></td>
                <td><?= htmlspecialchars($sensor['location']) ?></td>
                <td><?= $sensor['status'] ?></td>
                <td><?= $sensor['temperature'] ?></td>
                <td><?= $sensor['humidity'] ?></td>
                <td><?= $sensor['last_updated'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Manage Users Section -->
    <div id="users" class="section">
        <h2>User Management</h2>
        <?php if($message) echo "<div class='message'>$message</div>"; ?>

        <table>
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr>
            <?php foreach($users as $user): ?>
            <tr>
                <td><?= $user['user_id'] ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['role'] ?></td>
                <td>
                    <button class="edit-btn" onclick="showEditForm(<?= $user['user_id'] ?>)">Edit</button>
                    <a href="?delete_user=<?= $user['user_id'] ?>" onclick="return confirm('Delete this user?');"><button class="delete-btn">Delete</button></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <?php foreach($users as $user): ?>
        <form method="POST" id="edit-form-<?= $user['user_id'] ?>" style="display:none;">
            <h3>Edit User: <?= htmlspecialchars($user['username']) ?></h3>
            <input type="hidden" name="id" value="<?= $user['user_id'] ?>">
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <select name="role" required>
                <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
                <option value="manager" <?= $user['role']=='manager'?'selected':'' ?>>Manager</option>
                <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
            </select>
            <input type="submit" name="edit_user" value="Save Changes">
        </form>
        <?php endforeach; ?>
    </div>

</div>

</body>
</html>