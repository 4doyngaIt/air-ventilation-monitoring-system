<?php
require_once __DIR__.'/../../config/config.php';
auth_guard();
$me=$_SESSION['username']??'';
$pg=basename($_SERVER['PHP_SELF'],'.php');
$pt=$page_title??'Dashboard';
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pt)?> | AirVent Monitor</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Epilogue:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
    <a href="/hangin/app/user/dashboard.php" class="<?=$pg==='dashboard'?'active':''?>"><i class="fas fa-chart-line"></i> Dashboard</a>
    <div class="nav-label">Monitor</div>
    <a href="/hangin/app/user/weather.php" class="<?=$pg==='weather'?'active':''?>"><i class="fas fa-cloud-sun"></i> Weather Monitor</a>
    <div class="nav-label">Control</div>
    <a href="/hangin/app/user/ventilation.php" class="<?=$pg==='ventilation'?'active':''?>"><i class="fas fa-fan"></i> Vent Control</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-av"><?=strtoupper(substr($me,0,1))?></div>
      <div><div class="user-name"><?=e($me)?></div><div class="user-role">user</div></div>
    </div>
    <a href="/hangin/auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </div>
</aside>
<div class="main-wrap">
  <div class="topbar">
    <div class="topbar-title"><?=e($pt)?></div>
    <div class="topbar-right">
      <div class="live-dot">LIVE</div>
      <span class="role-pill user">User</span>
      <div class="topbar-av"><?=strtoupper(substr($me,0,1))?></div>
    </div>
  </div>
  <div class="content">
