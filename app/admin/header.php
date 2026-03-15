<?php
session_start();
include __DIR__ . "/../../config/db.php";

// Admin/Manager authentication
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])){
    header("Location: ../../login/login.php");
    exit();
}

$current_admin_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];
$viewing_user_id = isset($_GET['view_user']) ? intval($_GET['view_user']) : null;

// Check if activity_logs table exists
$table_check = $conn->query("SHOW TABLES LIKE 'activity_logs'");
if($table_check->num_rows == 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
}

$page_title = isset($page_title) ? $page_title : 'Admin Dashboard';

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | AirVent System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;       /* Emerald green - matching user */
            --primary-dark: #059669;
            --sidebar-bg: #1e293b;    /* Dark navy - matching user */
            --sidebar-text: #94a3b8;
            --sidebar-active: #10b981;
            --bg: #f1f5f9;           /* Light gray background */
            --surface: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #8b5cf6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Matching User Dashboard */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .brand h1 {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand h1 i {
            font-size: 1.75rem;
        }

        .nav-menu {
            list-style: none;
            padding: 1rem 0;
            flex: 1;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.05);
        }

        .nav-link.active {
            color: var(--sidebar-active);
            background: rgba(16, 185, 129, 0.1);
            border-left-color: var(--primary);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .logout-btn {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--sidebar-text);
            text-decoration: none;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .logout-btn a:hover {
            color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
        }

        /* Main Content */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        .top-header {
            background: var(--surface);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .role-badge {
            padding: 0.375rem 1rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-badge.admin { background: #dbeafe; color: #1e40af; }
        .role-badge.manager { background: #fed7aa; color: #92400e; }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }

        /* Content Area */
        .content {
            padding: 2rem;
            flex: 1;
        }

        /* View Banner (when viewing specific user) */
        .view-banner {
            background: linear-gradient(90deg, var(--purple), #a855f7);
            color: white;
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Stats Grid - Matching User Style */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text);
        }

        /* Cards */
        .card {
            background: var(--surface);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            text-align: left;
            padding: 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            background: #f8fafc;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        /* User Info in Table */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-cell-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            transition: opacity 0.2s;
        }

        .btn-icon:hover {
            opacity: 0.8;
        }

        .btn-view { background: var(--primary); }
        .btn-edit { background: var(--warning); }
        .btn-delete { background: var(--danger); }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Sensor Grid for View User */
        .sensor-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .sensor-card {
            background: var(--surface);
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .sensor-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .sensor-name {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .sensor-status {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .sensor-status.on {
            background: #d1fae5;
            color: #065f46;
        }

        .sensor-status.off {
            background: #fee2e2;
            color: #991b1b;
        }

        .sensor-data {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sensor-data-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .sensor-data-item i {
            width: 20px;
            color: var(--primary);
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--surface);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #f1f5f9;
            color: var(--text);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--text);
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Activity Items */
        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon.create { background: #d1fae5; color: #065f46; }
        .activity-icon.edit { background: #fed7aa; color: #92400e; }
        .activity-icon.delete { background: #fee2e2; color: #991b1b; }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-grid { grid-template-columns: 1fr; }
            .sensor-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-wrapper { margin-left: 0; }
            body { flex-direction: column; }
            .stats-grid { grid-template-columns: 1fr; }
            .sensor-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php if($viewing_user_id && isset($view_user)): ?>
    <div class="view-banner" style="position: fixed; top: 0; left: 260px; right: 0; z-index: 99;">
        <div>
            <i class="fas fa-eye"></i>
            <strong>Monitoring Mode:</strong> Viewing <?php echo htmlspecialchars($view_user['username']); ?>'s sensor data
        </div>
        <a href="user-management.php" style="color: white; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>
    <?php endif; ?>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <h1><i class="fas fa-wind"></i> AirVent System</h1>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="user-management.php" class="nav-link <?php echo in_array($current_page, ['user-management', 'view-user']) ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> User Management
                </a>
            </li>
            <li class="nav-item">
                <a href="activity-logs.php" class="nav-link <?php echo $current_page == 'activity-logs' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Activity Logs
                </a>
            </li>
        </ul>

        <div class="logout-btn">
            <a href="../../login/login.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Top Header -->
        <header class="top-header" <?php echo $viewing_user_id ? 'style="margin-top: 48px;"' : ''; ?>>
            <h2 class="page-title"><?php echo htmlspecialchars($page_title); ?></h2>
            <div class="user-info">
                <span class="role-badge <?php echo $current_role; ?>"><?php echo ucfirst($current_role); ?></span>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="content">