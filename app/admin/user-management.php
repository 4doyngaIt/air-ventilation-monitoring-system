<?php
$page_title = 'User Management';
include 'header.php';

$message = "";
$messageType = "success";

/* CRUD OPERATIONS */
if(in_array($current_role, ['admin', 'manager'])){

    // ADD USER
    if(isset($_POST['add_user'])){
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if($check->num_rows > 0){
            $message = "Email already exists!";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password, $role);
            
            if($stmt->execute()){
                $new_user_id = $stmt->insert_id;
                $stmt->close();
                $conn->query("INSERT INTO activity_logs (user_id, action_type, description) VALUES ($current_admin_id, 'create_user', 'Created $role: $username')");
                $message = "User created successfully!";
            } else {
                $message = "Error: " . $stmt->error;
                $messageType = "error";
            }
        }
        $check->close();
    }

    // EDIT USER
    if(isset($_POST['edit_user'])){
        $id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];

        if($current_role === 'manager'){
            $check = $conn->query("SELECT role FROM users WHERE user_id = $id")->fetch_assoc();
            if($check['role'] === 'admin'){
                $message = "You cannot edit admin users!";
                $messageType = "error";
            } else {
                goto do_edit;
            }
        } else {
            do_edit:
            if(!empty($_POST['password'])){
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE user_id=?");
                $stmt->bind_param("ssssi", $username, $email, $role, $password, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE user_id=?");
                $stmt->bind_param("sssi", $username, $email, $role, $id);
            }
            $stmt->execute();
            $stmt->close();

            $conn->query("INSERT INTO activity_logs (user_id, action_type, description) VALUES ($current_admin_id, 'edit_user', 'Updated: $username')");
            $message = "User updated!";
        }
    }

    // DELETE USER
    if(isset($_GET['delete_user'])){
        $id = intval($_GET['delete_user']);
        if($id == $current_admin_id){
            $message = "Cannot delete yourself!";
            $messageType = "error";
        } else {
            if($current_role === 'manager'){
                $check = $conn->query("SELECT role FROM users WHERE user_id = $id")->fetch_assoc();
                if($check['role'] === 'admin'){
                    $message = "You cannot delete admin users!";
                    $messageType = "error";
                    goto skip_delete;
                }
            }

            $user_info = $conn->query("SELECT username, role FROM users WHERE user_id = $id")->fetch_assoc();
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $conn->query("INSERT INTO activity_logs (user_id, action_type, description) VALUES ($current_admin_id, 'delete_user', 'Deleted {$user_info['role']}: {$user_info['username']}')");
            $message = "User deleted!";
        }
        skip_delete:
    }
}

// Fetch all users
$all_users = $conn->query("SELECT user_id, username, email, role, created_at FROM users ORDER BY FIELD(role, 'admin', 'manager', 'user'), user_id DESC");
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
?>

<?php if($message): ?>
<div class="alert alert-<?php echo $messageType; ?>">
    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo $message; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-users" style="color: var(--primary);"></i>
            Manage All Users
        </div>
        <button class="btn btn-primary" onclick="openModal('addUserModal')">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $all_users->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-cell-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                            <div>
                                <div style="font-weight: 600; color: var(--text);"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="role-badge <?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                    </td>
                    <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <div class="action-btns" style="justify-content: flex-end; display: flex; gap: 0.5rem;">
                            <?php if($user['role'] === 'user'): ?>
                            <a href="view-user.php?view_user=<?php echo $user['user_id']; ?>" class="btn-icon btn-view" title="Monitor Sensors">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>

                            <?php if($current_role === 'admin' || ($current_role === 'manager' && $user['role'] !== 'admin')): ?>
                            <button class="btn-icon btn-edit" onclick='editUser(<?php echo json_encode($user); ?>)' title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>

                            <?php if($current_role === 'admin' && $user['user_id'] != $current_admin_id): ?>
                            <a href="?delete_user=<?php echo $user['user_id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Delete this user?')" title="Delete User">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>