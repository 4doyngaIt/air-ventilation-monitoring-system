<?php
session_start();
session_unset();
session_destroy();
header('Location:/hangin/auth/login.php');
exit;
