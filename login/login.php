<?php
session_start();
include "../config/db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE email = ?");
$stmt->bind_param("s",$email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1){

$user = $result->fetch_assoc();

if($password === $user['password']){

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'admin') {
header("Location: ../app/admin/dashboard.php");
} elseif ($user['role'] === 'manager') {
header("Location: ../app/manager/dashboard.php");
} else {
header("Location: ../app/user/dashboard.php");
}

exit();

}else{
$error = "Invalid password";
}

}else{
$error = "User not found";
}

}
?>

<!DOCTYPE html>
<html>
<head>

<title>AirVent Monitor</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
height:100vh;
display:flex;
justify-content:center;
align-items:center;

/* BACKGROUND IMAGE + GRADIENT */
background:
linear-gradient(rgba(15,32,39,0.7), rgba(44,83,100,0.7)),
url('../images/bg.png') no-repeat center center/cover;
}

/* CARD (UPDATED TRANSPARENT GLASS EFFECT) */

.card{
width:380px;
padding:40px;

/* GLASS EFFECT */
background:rgba(255,255,255,0.08);
border-radius:20px;
backdrop-filter:blur(25px);
-webkit-backdrop-filter:blur(25px);

border:1px solid rgba(255,255,255,0.2);
box-shadow:0 20px 40px rgba(0,0,0,0.4);

text-align:center;
color:white;
}

/* LOGO */

.logo img{
width:70px;
height:70px;
margin-bottom:10px;
}

/* TITLE */

h1{
font-size:32px;
font-weight:600;
margin-bottom:5px;
}

.subtitle{
font-size:14px;
opacity:0.7;
margin-bottom:30px;
}

/* INPUT */

.input-group{
margin-bottom:15px;
}

input{
width:100%;
padding:14px;
border:none;
border-radius:10px;
background:rgba(255,255,255,0.12);
color:white;
font-size:14px;
}

input::placeholder{
color:#ccc;
}

/* SHOW PASSWORD */

.show-pass{
font-size:13px;
margin-top:5px;
display:flex;
align-items:center;
gap:5px;
color:#ccc;
}

/* BUTTON */

button{
width:100%;
padding:14px;
margin-top:15px;
border:none;
border-radius:12px;
background:linear-gradient(90deg,#00c6ff,#0072ff);
color:white;
font-size:16px;
cursor:pointer;
transition:0.3s;
}

button:hover{
transform:scale(1.03);
}

/* ERROR */

.error{
color:#ff7b7b;
margin-bottom:10px;
font-size:14px;
}

</style>

</head>

<body>

<div class="card">

<div class="logo">
<img src="../images/logo.png" alt="AirVent Logo">
</div>

<h1>Air Ventilation Monitoring System</h1>
<div class="subtitle">Air Ventilation Monitoring System</div>

<?php if(!empty($error)) echo "<div class='error'>$error</div>"; ?>

<form method="POST">

<div class="input-group">
<input type="email" name="email" placeholder="Enter username or email" required>
</div>

<div class="input-group">
<input type="password" id="password" name="password" placeholder="Enter password" required>

<div class="show-pass">
<input type="checkbox" onclick="togglePassword()"> Show Password
</div>

</div>

<button type="submit">Sign In →</button>

</form>

</div>

<script>

function togglePassword(){

var pass = document.getElementById("password");

if(pass.type === "password"){
pass.type = "text";
}else{
pass.type = "password";
}

}

</script>

</body>
</html>