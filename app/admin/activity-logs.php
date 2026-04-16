<?php
$page_title='Activity Logs';
include 'header.php';
$logs=db_q("SELECT al.*,u.username,u.role FROM activity_logs al LEFT JOIN users u ON al.user_id=u.user_id ORDER BY al.created_at DESC LIMIT 100");
?>
<div class="card"><div class="card-head"><div class="card-title"><i class="fas fa-history" style="color:var(--clay)"></i> Recent Activity</div></div>
  <div class="card-body">
  <?php if($logs): foreach($logs as $log):
    $imap=['create_user'=>['user-plus','create'],'edit_user'=>['edit','edit'],'delete_user'=>['trash','delete'],'login'=>['sign-in-alt','login'],'sensor'=>['broadcast-tower','system']];
    $info=$imap[$log['action_type']]??['circle','system'];
  ?>
  <div class="act-item">
    <div class="act-icon <?=$info[1]?>"><i class="fas fa-<?=$info[0]?>"></i></div>
    <div style="flex:1">
      <div style="font-weight:600"><?=e($log['username']??'System')?>
        <span style="color:var(--muted);font-weight:400;font-size:.82rem">(<?=$log['role']??'system'?>)</span></div>
      <div style="color:var(--muted);font-size:.85rem;margin-top:2px"><?=e($log['description']??'')?></div>
    </div>
    <div style="color:var(--muted);font-size:.8rem;white-space:nowrap"><?=date('M d, H:i',strtotime($log['created_at']))?></div>
  </div>
  <?php endforeach; else:?>
  <div style="text-align:center;padding:48px;color:var(--muted)"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4"></i>No activity yet.</div>
  <?php endif;?>
  </div>
</div>
<?php include 'footer.php';?>
