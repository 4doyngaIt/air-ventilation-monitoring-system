<?php
session_start();
require_once __DIR__.'/config/config.php';
if(!empty($_SESSION['user_id'])&&!empty($_SESSION['role'])){
    $map=['admin'=>'/hangin/app/admin/dashboard.php','manager'=>'/hangin/app/manager/dashboard.php'];
    header('Location:'.($map[$_SESSION['role']]??'/hangin/app/user/dashboard.php'));exit;
}
header('Location:/hangin/auth/login.php');exit;
