<?php
/**
 * Delete list page
 */

require_once '../../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/list_management.php';

// Require authentication
require_auth();

$user_id = $_SESSION['user_id'];
$list_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get list details
$list = get_list_by_id($list_id);

// Check if list exists and user has permission to delete
if (!$list) {
    set_flash_message('danger', 'List not found');
    redirect('/lists/index.php');
}

// Check if user has permission to delete (admin or list owner)
if (!is_admin() && $list['user_id'] != $user_id) {
    set_flash_message('danger', 'You do not have permission to delete this list');
    redirect('/lists/index.php');
}

// Handle confirmation via GET parameter
if (isset($_GET['confirm']) && $_GET['confirm'] == 1) {
    // Delete the list
    if (delete_list($list_id)) {
        set_flash_message('success', 'List "' . htmlspecialchars($list['list_name']) . '" deleted successfully');
    } else {
        set_flash_message('danger', 'Failed to delete list');
    }
    
    redirect('/lists/index.php');
}

// Show confirmation page
$page_title = 'Delete List - ' . APP_NAME;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
</head>
<body>
    <?php include_once VIEW_PATH . '/layout/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include_once VIEW_PATH . '/layout/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Delete List</h5>
                    </div>
                    <div class="card-body">
                        <?php display_flash_messages(); ?>
                        
                        <div class="alert alert-warning">
                            <h4 class="alert-heading">Warning!</h4>
                            <p>You are about to delete the list "<strong><?php echo htmlspecialchars($list['list_name']); ?></strong>".</p>
                            <p>This will permanently delete the list and all <?php echo $list['contact_count']; ?> contacts associated with it. This action cannot be undone.</p>
                            <hr>
                            <p class="mb-0">Are you sure you want to proceed?</p>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/lists/index.php" class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </a>
                            <a href="<?php echo BASE_URL; ?>/lists/delete.php?id=<?php echo $list_id; ?>&confirm=1" class="btn btn-danger">
                                <i class="fas fa-trash mr-1"></i> Yes, Delete List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>