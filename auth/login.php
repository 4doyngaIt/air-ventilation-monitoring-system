<?php
session_start();
if(!empty($_SESSION['user_id'])){
    $m=['admin'=>'/hangin/app/admin/dashboard.php','manager'=>'/hangin/app/manager/dashboard.php'];
    header('Location:'.($m[$_SESSION['role']]??'/hangin/app/user/dashboard.php'));exit;
}
require_once __DIR__.'/../config/config.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=trim($_POST['email']??'');
    $pass=trim($_POST['password']??'');
    $user=db_one("SELECT * FROM users WHERE email=? LIMIT 1","s",[$email]);
    if($user&&($pass===$user['password']||password_verify($pass,$user['password']))){
        session_regenerate_id(true);
        $_SESSION['user_id']=$user['user_id'];
        $_SESSION['username']=$user['username'];
        $_SESSION['role']=$user['role'];
        log_act($user['user_id'],'login',$user['username'].' logged in');
        $m=['admin'=>'/hangin/app/admin/dashboard.php','manager'=>'/hangin/app/manager/dashboard.php'];
        header('Location:'.($m[$user['role']]??'/hangin/app/user/dashboard.php'));exit;
    }
    $err='Invalid email or password.';
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — AirVent Monitor</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Epilogue:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="/hangin/assets/style.css">
</head><body>
<div class="login-wrap">
  <div class="login-left">
    <div class="login-left-inner">
      <div style="font-size:3rem;margin-bottom:20px">🌿</div>
      <h2>Farm <em>Air Ventilation</em> Monitoring</h2>
      <p>Automatically monitor and control ventilation across your farm. When temperature rises, vents open. When rain is detected, they close — keeping your crops safe.</p>
      <div class="login-features stagger">
        <div class="login-feat fade-up">
          <div class="login-feat-icon" style="background:rgba(196,98,45,.25)">🌡️</div>
          <div class="login-feat-text"><strong>Heat Detection</strong><p>Auto-opens vents when temperature exceeds threshold</p></div>
        </div>
        <div class="login-feat fade-up">
          <div class="login-feat-icon" style="background:rgba(59,110,165,.25)">🌧️</div>
          <div class="login-feat-text"><strong>Rain Detection</strong><p>Auto-closes vents when rainfall is detected</p></div>
        </div>
        <div class="login-feat fade-up">
          <div class="login-feat-icon" style="background:rgba(74,124,40,.25)">📊</div>
          <div class="login-feat-text"><strong>Live Monitoring</strong><p>Real-time sensor readings across all locations</p></div>
        </div>
      </div>
    </div>
  </div>
  <div class="login-panel">
    <div class="login-card">
      <div class="login-brand-row">
        <div class="login-brand-icon">💨</div>
        <div>
          <div class="login-brand-name">AirVent Monitor</div>
          <div class="login-brand-ver">v<?=APP_VER?> · Farm Ventilation System</div>
        </div>
      </div>
      <h2>Welcome back</h2>
      <p class="sub">Sign in to your monitoring dashboard.</p>
      <?php if($err):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?=e($err)?></div><?php endif;?>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" required
                 value="<?=e($_POST['email']??'')?>">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" id="pw" name="password" class="form-control" placeholder="••••••••" required>
          <div class="show-pass">
            <input type="checkbox" id="sp" onchange="document.getElementById('pw').type=this.checked?'text':'password'">
            <label for="sp">Show password</label>
          </div>
        </div>
        <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> Sign In</button>
      </form>
      <p class="login-hint">Demo: <code>admin@gmail.com</code> / <code>admin123</code></p>
    </div>
  </div>
</div>
</body></html>
