<?php
session_start();
include __DIR__ . "/../../config/db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../../login/login.php");
    exit();
}

$message = "";

/* EDIT USER */
if(isset($_POST['edit_user'])){
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE user_id=?");
    $stmt->bind_param("sssi",$username,$email,$role,$id);

    if($stmt->execute()){
        $message = "User updated successfully!";
    }else{
        $message = "Failed to update user.";
    }
}

/* DELETE USER */
if(isset($_GET['delete_user'])){
    $id = $_GET['delete_user'];

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i",$id);

    if($stmt->execute()){
        $message = "User deleted successfully!";
    }else{
        $message = "Failed to delete user.";
    }
}

/* FETCH USERS */
$users_result = $conn->query("SELECT * FROM users ORDER BY user_id ASC");

/* FETCH SENSORS */
$sensors_result = $conn->query("SELECT * FROM sensors ORDER BY sensor_id ASC");

$total_users = $users_result->num_rows;
$total_sensors = $sensors_result->num_rows;
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<style>

body{
font-family:Arial;
margin:0;
display:flex;
background:#f4f7f9;
}

/* SIDEBAR */

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

/* MAIN */

.main{
flex:1;
padding:25px;
}

h2{
color:#0077cc;
}

/* DASHBOARD CARDS */

.cards{
display:flex;
gap:20px;
margin-top:20px;
}

.card{
flex:1;
background:#fff;
padding:20px;
border-radius:8px;
box-shadow:0 0 10px #ccc;
text-align:center;
}

.card-icon{
font-size:35px;
display:block;
margin-bottom:10px;
}

.card h3{
margin:0;
color:#555;
}

.card p{
font-size:28px;
font-weight:bold;
margin-top:10px;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
margin-top:20px;
background:#fff;
box-shadow:0 0 8px #ccc;
}

th,td{
border:1px solid #ddd;
padding:10px;
text-align:center;
}

th{
background:#0077cc;
color:#fff;
}

/* BUTTONS */

button{
padding:6px 12px;
border:none;
border-radius:4px;
cursor:pointer;
}

.edit-btn{
background:#17a2b8;
color:#fff;
}

.delete-btn{
background:#dc3545;
color:#fff;
}

/* FORM */

form{
background:#fff;
padding:15px;
margin-top:10px;
border-radius:6px;
box-shadow:0 0 8px #ccc;
}

input,select{
width:100%;
padding:8px;
margin:5px 0 10px 0;
border:1px solid #ccc;
border-radius:4px;
}

input[type=submit]{
background:#0077cc;
color:white;
border:none;
cursor:pointer;
}

.section{
display:none;
}

.message{
background:#d4edda;
padding:10px;
border-radius:5px;
margin-bottom:10px;
}

.icon{
font-size:20px;
margin-right:8px;
}

</style>

<script>

function showSection(id){

let sections=document.querySelectorAll('.section');

sections.forEach(s=>{
s.style.display='none';
});

document.getElementById(id).style.display='block';

}

function showEditForm(id){

let form=document.getElementById("edit-form-"+id);

if(form.style.display==="none"){
form.style.display="block";
}else{
form.style.display="none";
}

}

window.onload=function(){
showSection('home');
}

</script>

</head>

<body>

<div class="sidebar">
<a href="#" onclick="showSection('home')">HOME</a>
<a href="#" onclick="showSection('sensor')">Monitor Sensor</a>
<a href="#" onclick="showSection('users')">Manage Users</a>
<a href="../../login/logout.php">Logout</a>
</div>

<div class="main">

<!-- HOME -->

<div id="home" class="section">

<h2>Welcome, Admin!</h2>

<div class="cards">

<div class="card">
<span class="card-icon">📡</span>
<h3>Total Sensors Installed</h3>
<p><?php echo $total_sensors; ?></p>
</div>

<div class="card">
<span class="card-icon">👥</span>
<h3>Number of Users</h3>
<p><?php echo $total_users; ?></p>
</div>

</div>

</div>

<!-- SENSOR -->

<div id="sensor" class="section">

<h2><span class="icon">📡</span>Sensor Monitoring</h2>

<table>

<tr>
<th>ID</th>
<th>Location</th>
<th>Status</th>
<th>Temperature</th>
<th>Humidity</th>
<th>Last Updated</th>
</tr>

<?php while($sensor=$sensors_result->fetch_assoc()): ?>

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

<h2><span class="icon">👤</span>User Management</h2>

<?php if($message){ ?>
<div class="message"><?php echo $message; ?></div>
<?php } ?>

<table>

<tr>
<th>ID</th>
<th>Username</th>
<th>Email</th>
<th>Role</th>
<th>Actions</th>
</tr>

<?php while($user=$users_result->fetch_assoc()): ?>

<tr>

<td><?php echo $user['user_id']; ?></td>
<td><?php echo htmlspecialchars($user['username']); ?></td>
<td><?php echo htmlspecialchars($user['email']); ?></td>
<td><?php echo $user['role']; ?></td>

<td>

<button class="edit-btn" onclick="showEditForm(<?php echo $user['user_id']; ?>)">Edit</button>

<a href="?delete_user=<?php echo $user['user_id']; ?>" onclick="return confirm('Delete this user?');">
<button class="delete-btn">Delete</button>
</a>

</td>

</tr>

<tr>
<td colspan="5">

<form method="POST" id="edit-form-<?php echo $user['user_id']; ?>" style="display:none;">

<input type="hidden" name="id" value="<?php echo $user['user_id']; ?>">

<input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

<input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

<select name="role">

<option value="user" <?php if($user['role']=="user") echo "selected"; ?>>User</option>
<option value="manager" <?php if($user['role']=="manager") echo "selected"; ?>>Manager</option>
<option value="admin" <?php if($user['role']=="admin") echo "selected"; ?>>Admin</option>

</select>

<input type="submit" name="edit_user" value="Save Changes">

</form>

</td>
</tr>

<?php endwhile; ?>

</table>

</div>

</div>

</body>
</html>