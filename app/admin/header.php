<?php
require_once __DIR__.'/../../config/config.php';
auth_guard('admin','manager');
$me=$_SESSION['username']??'';
$role=$_SESSION['role']??'';
$pg=basename($_SERVER['PHP_SELF'],'.php');
$pt=$page_title??'Dashboard';
$conn->query("CREATE TABLE IF NOT EXISTS activity_logs(log_id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,action_type VARCHAR(50) NOT NULL,description TEXT,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pt)?> | AirVent Monitor</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Epilogue:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="/hangin/assets/style.css">
</head><body>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo"><div class="brand-icon">💨</div>
      <div><div class="brand-name">AirVent</div><div class="brand-sub">Monitor v<?=APP_VER?></div></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Overview</div>
    <a href="/hangin/app/admin/dashboard.php" class="<?=$pg==='dashboard'?'active':''?>"><i class="fas fa-chart-line"></i> Dashboard</a>
    <div class="nav-label">Management</div>
    <a href="/hangin/app/admin/user-management.php" class="<?=in_array($pg,['user-management','view-user'])?'active':''?>"><i class="fas fa-users"></i> User Management</a>
    <div class="nav-label">Logs</div>
    <a href="/hangin/app/admin/activity-logs.php" class="<?=$pg==='activity-logs'?'active':''?>"><i class="fas fa-history"></i> Activity Logs</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-av"><?=strtoupper(substr($me,0,1))?></div>
      <div><div class="user-name"><?=e($me)?></div><div class="user-role"><?=$role?></div></div>
    </div>
    <a href="/hangin/auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </div>
</aside>
<div class="main-wrap">
  <div class="topbar">
    <div class="topbar-title"><?=e($pt)?></div>
    <div class="topbar-right">
      <span class="role-pill <?=$role?>"><?=ucfirst($role)?></span>
      <div class="topbar-av"><?=strtoupper(substr($me,0,1))?></div>
    </div>
  </div>
  <div class="content">
