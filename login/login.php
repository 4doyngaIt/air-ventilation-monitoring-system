<?php
session_start();
include "../db.php"; // correct path to db.php

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user from DB
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../app/admin/dashboard.php");
                exit();
            } else {
                header("Location: ../app/user/dashboard.php");
                exit();
            }
        } else {
            $message = "Invalid email or password.";
        }
    } else {
        $message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Air Ventilation System</title>
    <style>
        body { font-family: Arial; background: #f4f6f9; display:flex; justify-content:center; align-items:center; height:100vh; }
        .container { background:#fff; padding:30px; border-radius:10px; box-shadow:0 0 15px rgba(0,0,0,0.2); width:350px; }
        input { width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:5px; }
        button { width:100%; padding:10px; background:#0077cc; color:#fff; border:none; border-radius:5px; cursor:pointer; }
        button:hover { background:#005fa3; }
        .message { color:red; text-align:center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Login</h2>
    <?php if($message) echo "<div class='message'>$message</div>"; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>