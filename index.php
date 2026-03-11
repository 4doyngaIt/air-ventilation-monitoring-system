<?php
session_start();
include "db.php"; // database connection

// Check if user is logged in
if(isset($_SESSION['user_id']) && isset($_SESSION['role'])){
    // Redirect based on role
    if($_SESSION['role'] === 'admin'){
        header("Location: app/admin/dashboard.php");
        exit();
    } else {
        header("Location: app/user/dashboard.php");
        exit();
    }
} else {
    // Not logged in, redirect to login page
    header("Location: login/login.php");
    exit();
}
?>