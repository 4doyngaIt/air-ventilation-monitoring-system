<?php
$page_title = 'Activity Logs';
include 'header.php';

// Activity logs
$activity_logs_result = $conn->query("SHOW TABLES LIKE 'activity_logs'");
$activity_logs = false;
if($activity_logs_result->num_rows > 0) {
    $activity_logs = $conn->query("
        SELECT al.*, u.username, u.role
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
        LIMIT 50
    ");
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-history" style="color: var(--primary);"></i>
            Recent Activity Logs
        </div>
    </div>
    <div class="card-body">
        <?php if($activity_logs && $activity_logs->num_rows > 0): ?>
            <?php while($log = $activity_logs->fetch_assoc()): ?>
            <div class="activity-item">
                <div class="activity-icon <?php echo explode('_', $log['action_type'])[0]; ?>">
                    <i class="fas fa-<?php 
                        echo $log['action_type'] == 'create_user' ? 'user-plus' : 
                            ($log['action_type'] == 'edit_user' ? 'edit' : 'trash'); 
                    ?>"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: var(--text);">
                        <?php echo htmlspecialchars($log['username'] ?? 'System'); ?>
                        <span style="color: var(--text-muted); font-weight: 400;">(<?php echo $log['role'] ?? 'system'; ?>)</span>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                        <?php echo htmlspecialchars($log['description']); ?>
                    </div>
                </div>
                <div style="color: var(--text-muted); font-size: 0.85rem; white-space: nowrap;">
                    <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>No activity logs yet</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>