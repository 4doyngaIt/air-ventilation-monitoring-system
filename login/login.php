```php
<?php
session_start();
include "../config/db.php";

$message = "";

// Handle login
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

            if($user['role'] === 'admin'){
                header("Location: ../app/admin/dashboard.php");
            }else{
                header("Location: ../app/user/dashboard.php");
            }
            exit();

        }else{
            $message = "Invalid email or password.";
        }

    }else{
        $message = "Invalid email or password.";
    }

}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login - Air Ventilation System</title>

<style>

body{
font-family: Arial;
margin:0;
height:100vh;
display:flex;
justify-content:center;
align-items:center;

/* GRADIENT BACKGROUND */
background: linear-gradient(135deg,#667eea,#764ba2);
}

/* LOGIN CARD */

.container{
background:white;
padding:35px;
width:350px;
border-radius:12px;
box-shadow:0 15px 35px rgba(0,0,0,0.2);
}

h2{
text-align:center;
margin-bottom:20px;
color:#333;
}

input{
width:100%;
padding:12px;
margin:10px 0;
border:1px solid #77adeb;
border-radius:6px;
font-size:14px;
}

button{
width:100%;
padding:12px;
border:none;
border-radius:6px;
background:#667eea;
color:white;
font-size:15px;
cursor:pointer;
}

button:hover{
background:#5563c1;
}

.message{
color:red;
text-align:center;
margin-bottom:10px;
}

.showpass{
font-size:14px;
margin-bottom:10px;
}

</style>

</head>

<body>

<div class="container">

<h2>Air Ventilation Login</h2>

<?php if(!empty($message)) echo "<div class='message'>$message</div>"; ?>

<form method="POST">

<input type="email" name="email" placeholder="Email Address" required>

<input type="password" name="password" id="password" placeholder="Password" required>

<div class="showpass">
<label>
<input type="checkbox" onclick="togglePassword()"> Show Password
</label>
</div>

<button type="submit">Login</button>

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
```
