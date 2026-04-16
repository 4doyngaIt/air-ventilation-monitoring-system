<?php
$page_title='User Management';
include 'header.php';
$msg='';$mtype='success';
$aid=$_SESSION['user_id'];$arole=$_SESSION['role'];

if(isset($_POST['add_user'])){
    $un=trim($_POST['username']);$em=trim($_POST['email']);$rl=$_POST['role'];$pw=password_hash($_POST['password'],PASSWORD_DEFAULT);
    $chk=db_one("SELECT user_id FROM users WHERE email=?","s",[$em]);
    if($chk){$msg="Email already exists!";$mtype='error';}
    else{db_run("INSERT INTO users(username,email,password,role)VALUES(?,?,?,?)","ssss",[$un,$em,$pw,$rl]);
         log_act($aid,'create_user',"Created $rl: $un");$msg="User <strong>$un</strong> created!";}
}
if(isset($_POST['edit_user'])){
    $id=intval($_POST['user_id']);$un=trim($_POST['username']);$em=trim($_POST['email']);$rl=$_POST['role'];
    $ok=true;
    if($arole==='manager'){$t=db_one("SELECT role FROM users WHERE user_id=?","i",[$id]);if($t&&$t['role']==='admin'){$msg="Cannot edit admin!";$mtype='error';$ok=false;}}
    if($ok){
        if(!empty($_POST['password'])){$h=password_hash($_POST['password'],PASSWORD_DEFAULT);db_run("UPDATE users SET username=?,email=?,role=?,password=? WHERE user_id=?","ssssi",[$un,$em,$rl,$h,$id]);}
        else{db_run("UPDATE users SET username=?,email=?,role=? WHERE user_id=?","sssi",[$un,$em,$rl,$id]);}
        log_act($aid,'edit_user',"Updated: $un");$msg="User <strong>$un</strong> updated!";
    }
}
if(isset($_GET['delete_user'])){
    $id=intval($_GET['delete_user']);
    if($id===$aid){$msg="Cannot delete yourself!";$mtype='error';}
    else{
        $ok=true;
        if($arole==='manager'){$t=db_one("SELECT role FROM users WHERE user_id=?","i",[$id]);if($t&&$t['role']==='admin'){$msg="Cannot delete admins!";$mtype='error';$ok=false;}}
        if($ok){$ui=db_one("SELECT username,role FROM users WHERE user_id=?","i",[$id]);
            db_run("DELETE FROM users WHERE user_id=?","i",[$id]);
            log_act($aid,'delete_user',"Deleted {$ui['role']}: {$ui['username']}");$msg="User deleted.";}
    }
}
$users=db_q("SELECT user_id,username,email,role,created_at FROM users ORDER BY FIELD(role,'admin','manager','user'),user_id DESC");
?>
<?php if($msg):?><div class="alert alert-<?=$mtype?>"><i class="fas fa-<?=$mtype==='success'?'check-circle':'exclamation-circle'?>"></i> <?=$msg?></div><?php endif;?>
<div class="card">
  <div class="card-head">
    <div class="card-title"><i class="fas fa-users" style="color:var(--clay)"></i> All Users (<?=count($users)?>)</div>
    <button class="btn btn-primary btn-sm" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add User</button>
  </div>
  <div class="card-body p0">
    <table class="data-table">
      <thead><tr><th>User</th><th>Role</th><th>Joined</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
      <?php foreach($users as $u):?>
      <tr>
        <td><div class="user-cell"><div class="user-av-sm"><?=strtoupper(substr($u['username'],0,1))?></div>
          <div><div style="font-weight:600"><?=e($u['username'])?></div>
          <div style="font-size:.8rem;color:var(--muted)"><?=e($u['email'])?></div></div></div></td>
        <td><span class="badge badge-<?=$u['role']?>"><?=ucfirst($u['role'])?></span></td>
        <td style="color:var(--muted);font-size:.83rem"><?=date('M d, Y',strtotime($u['created_at']))?></td>
        <td><div style="display:flex;justify-content:flex-end;gap:6px">
          <?php if($u['role']==='user'):?>
          <a href="/hangin/app/admin/view-user.php?view_user=<?=$u['user_id']?>" class="btn-icon bi-view" title="View Sensors"><i class="fas fa-eye"></i></a>
          <?php endif;?>
          <?php if($arole==='admin'||($arole==='manager'&&$u['role']!=='admin')):?>
          <button class="btn-icon bi-edit" onclick='editUser(<?=json_encode($u)?>')' title="Edit"><i class="fas fa-edit"></i></button>
          <?php endif;?>
          <?php if($arole==='admin'&&$u['user_id']!=$aid):?>
          <a href="?delete_user=<?=$u['user_id']?>" class="btn-icon bi-del" onclick="return confirm('Delete <?=e($u['username'])?>?')" title="Delete"><i class="fas fa-trash"></i></a>
          <?php endif;?>
        </div></td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
  </div>
</div>

<div id="addModal" class="modal"><div class="modal-box">
  <div class="modal-head"><h3 class="modal-title">Add New User</h3><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
  <form method="POST"><div class="modal-body">
    <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Role</label>
      <select name="role" class="form-control">
        <option value="user">User</option><option value="manager">Manager</option>
        <?php if($arole==='admin'):?><option value="admin">Admin</option><?php endif;?>
      </select></div>
  </div>
  <div class="modal-foot"><button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button><button type="submit" name="add_user" class="btn btn-primary">Create User</button></div>
  </form>
</div></div>

<div id="editModal" class="modal"><div class="modal-box">
  <div class="modal-head"><h3 class="modal-title">Edit User</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
  <form method="POST"><input type="hidden" name="user_id" id="eId"><div class="modal-body">
    <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" id="eUn" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="eEm" class="form-control" required></div>
    <div class="form-group"><label class="form-label">New Password <small style="font-weight:400;text-transform:none">(blank = keep)</small></label><input type="password" name="password" class="form-control"></div>
    <div class="form-group"><label class="form-label">Role</label>
      <select name="role" id="eRl" class="form-control">
        <option value="user">User</option><option value="manager">Manager</option>
        <?php if($arole==='admin'):?><option value="admin">Admin</option><?php endif;?>
      </select></div>
  </div>
  <div class="modal-foot"><button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button><button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button></div>
  </form>
</div></div>

<script>
function openModal(id){document.getElementById(id).classList.add('active')}
function closeModal(id){document.getElementById(id).classList.remove('active')}
function editUser(u){document.getElementById('eId').value=u.user_id;document.getElementById('eUn').value=u.username;document.getElementById('eEm').value=u.email;document.getElementById('eRl').value=u.role;openModal('editModal')}
window.onclick=e=>{if(e.target.classList.contains('modal'))closeModal(e.target.id)}
</script>
<?php include 'footer.php';?>
