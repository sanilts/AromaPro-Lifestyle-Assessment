<?php
// Make sure the user_id variable is available
if (!isset($user_id) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}
?>

<div class="list-group mb-4">
    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
    </a>
    <a href="<?php echo BASE_URL; ?>/lists/index.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/lists/') !== false && basename($_SERVER['PHP_SELF']) != 'create.php' ? 'active' : ''; ?>">
        <i class="fas fa-list mr-2"></i> My Lists
    </a>
    <a href="<?php echo BASE_URL; ?>/lists/create.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'create.php' ? 'active' : ''; ?>">
        <i class="fas fa-plus mr-2"></i> New List
    </a>
    <a href="<?php echo BASE_URL; ?>/profile.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
        <i class="fas fa-user mr-2"></i> My Profile
    </a>
    
    <?php if (is_admin()): ?>
    <div class="dropdown-divider"></div>
    <div class="list-group-item text-muted small text-uppercase font-weight-bold">Admin</div>
    <a href="<?php echo BASE_URL; ?>/admin/users.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/admin/users') !== false || basename($_SERVER['PHP_SELF']) == 'edit_user.php' || basename($_SERVER['PHP_SELF']) == 'create_user.php' ? 'active' : ''; ?>">
        <i class="fas fa-users mr-2"></i> Manage Users
    </a>
    <a href="<?php echo BASE_URL; ?>/admin/settings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i class="fas fa-cogs mr-2"></i> Settings
    </a>
    <?php endif; ?>
</div>

<?php
// Only show stats if user_id is defined
if (isset($user_id) && function_exists('count_lists_by_user')):
    // Get quick stats
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Total lists
        $lists_count = count_lists_by_user($user_id);
        
        // Total contacts
        $contacts_query = "SELECT COUNT(*) as total FROM contacts c 
                          JOIN lists l ON c.list_id = l.id 
                          WHERE l.user_id = :user_id";
        $contacts_stmt = $db->prepare($contacts_query);
        $contacts_stmt->bindParam(':user_id', $user_id);
        $contacts_stmt->execute();
        $contacts_result = $contacts_stmt->fetch(PDO::FETCH_ASSOC);
        $contacts_count = $contacts_result['total'];
        
        // Valid emails
        $valid_query = "SELECT COUNT(*) as total FROM contacts c 
                       JOIN lists l ON c.list_id = l.id 
                       WHERE l.user_id = :user_id AND c.email_status = 'valid'";
        $valid_stmt = $db->prepare($valid_query);
        $valid_stmt->bindParam(':user_id', $user_id);
        $valid_stmt->execute();
        $valid_result = $valid_stmt->fetch(PDO::FETCH_ASSOC);
        $valid_count = $valid_result['total'];
        
        // Invalid emails
        $invalid_query = "SELECT COUNT(*) as total FROM contacts c 
                         JOIN lists l ON c.list_id = l.id 
                         WHERE l.user_id = :user_id AND c.email_status = 'invalid'";
        $invalid_stmt = $db->prepare($invalid_query);
        $invalid_stmt->bindParam(':user_id', $user_id);
        $invalid_stmt->execute();
        $invalid_result = $invalid_stmt->fetch(PDO::FETCH_ASSOC);
        $invalid_count = $invalid_result['total'];
        
        // Calculate percentages
        $valid_percent = $contacts_count > 0 ? round(($valid_count / $contacts_count) * 100) : 0;
        $invalid_percent = $contacts_count > 0 ? round(($invalid_count / $contacts_count) * 100) : 0;
        $pending_percent = 100 - $valid_percent - $invalid_percent;
        ?>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <small>Lists:</small>
                        <span class="badge badge-primary"><?php echo $lists_count; ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <small>Total Contacts:</small>
                        <span class="badge badge-secondary"><?php echo $contacts_count; ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Valid Emails:</small>
                        <span class="badge badge-success"><?php echo $valid_count; ?> (<?php echo $valid_percent; ?>%)</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $valid_percent; ?>%" aria-valuenow="<?php echo $valid_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Invalid Emails:</small>
                        <span class="badge badge-danger"><?php echo $invalid_count; ?> (<?php echo $invalid_percent; ?>%)</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $invalid_percent; ?>%" aria-valuenow="<?php echo $invalid_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <small>Pending Validation:</small>
                        <span class="badge badge-warning"><?php echo $contacts_count - $valid_count - $invalid_count; ?> (<?php echo $pending_percent; ?>%)</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pending_percent; ?>%" aria-valuenow="<?php echo $pending_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } catch (Exception $e) {
        // Silent failure - don't show stats if there's an error
    }
endif;
?>

<?php if (is_admin()): ?>
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Admin Tools</h5>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <a href="<?php echo BASE_URL; ?>/admin/settings.php#postmark" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="fas fa-key mr-2"></i> Postmark API</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/create_user.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-plus mr-2"></i> Add User</span>
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>