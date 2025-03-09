<?php
/**
 * Sync Postmark events page
 */

require_once '../../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/list_management.php';
require_once INCLUDE_PATH . '/validation.php';

// Require authentication
require_auth();

$user_id = $_SESSION['user_id'];
$list_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get list details
$list = get_list_by_id($list_id);

// Check if list exists and user has access
if (!$list || ($list['user_id'] != $user_id && !is_admin())) {
    set_flash_message('danger', 'List not found or access denied');
    redirect('/lists/index.php');
}

$error = '';
$success = '';
$sync_results = null;

// Process sync request
if (isset($_GET['sync']) && $_GET['sync'] == '1') {
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Limit days to a reasonable range
    if ($days < 1) $days = 1;
    if ($days > 30) $days = 30;
    
    // Sync events from Postmark
    $sync_results = sync_postmark_events($list_id, $status, $days);
    
    if ($sync_results['success']) {
        $success = $sync_results['message'];
    } else {
        $error = 'Error syncing events: ' . $sync_results['message'];
    }
}

$page_title = 'Sync Postmark Events - ' . APP_NAME;
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
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sync Postmark Events: <?php echo htmlspecialchars($list['list_name']); ?></h5>
                        <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Validation
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            
                            <?php if ($sync_results && $sync_results['success']): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Sync Results</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Summary</h6>
                                                <ul class="list-group mb-3">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Updated Contacts
                                                        <span class="badge badge-primary"><?php echo $sync_results['updated']; ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        Skipped Contacts
                                                        <span class="badge badge-secondary"><?php echo $sync_results['skipped']; ?></span>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Events</h6>
                                                <ul class="list-group">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <i class="fas fa-envelope-open text-info mr-1"></i> Opened
                                                        <span class="badge badge-info"><?php echo $sync_results['events']['opened']; ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <i class="fas fa-check text-success mr-1"></i> Delivered
                                                        <span class="badge badge-success"><?php echo $sync_results['events']['delivered']; ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <i class="fas fa-paper-plane text-primary mr-1"></i> Sent
                                                        <span class="badge badge-primary"><?php echo $sync_results['events']['sent']; ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <i class="fas fa-exclamation-circle text-danger mr-1"></i> Bounced
                                                        <span class="badge badge-danger"><?php echo $sync_results['events']['bounced']; ?></span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle mr-1"></i> Sync Postmark Events</h6>
                            <p>This tool will connect to Postmark's API and fetch the latest email events (Opened, Delivered, Sent, Bounced) for your contacts.</p>
                            <p>You can choose how many days of history to check and which contacts to update.</p>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Sync Options</h6>
                            </div>
                            <div class="card-body">
                                <form method="get" action="">
                                    <input type="hidden" name="id" value="<?php echo $list_id; ?>">
                                    <input type="hidden" name="sync" value="1">
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="days">Days of History</label>
                                            <select name="days" id="days" class="form-control">
                                                <option value="1">Last 24 hours</option>
                                                <option value="3">Last 3 days</option>
                                                <option value="7" selected>Last 7 days</option>
                                                <option value="14">Last 14 days</option>
                                                <option value="30">Last 30 days</option>
                                            </select>
                                            <small class="form-text text-muted">How far back to check for email events</small>
                                        </div>
                                        
                                        <div class="form-group col-md-6">
                                            <label for="status">Contact Status Filter</label>
                                            <select name="status" id="status" class="form-control">
                                                <option value="">All Contacts</option>
                                                <option value="pending">Pending Only</option>
                                                <option value="valid">Valid Only</option>
                                                <option value="invalid">Invalid Only</option>
                                            </select>
                                            <small class="form-text text-muted">Which contacts to update</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-sync-alt mr-1"></i> Sync with Postmark
                                        </button>
                                        <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>" class="btn btn-secondary">
                                            <i class="fas fa-times mr-1"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Automatic Event Tracking</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Set Up Postmark Webhooks</h6>
                                        <p>For real-time event tracking, set up a Postmark webhook:</p>
                                        <ol>
                                            <li>In Postmark, go to <strong>Servers</strong> > <strong>Your Server</strong> > <strong>Webhooks</strong></li>
                                            <li>Add a new webhook with this URL:<br>
                                                <code><?php echo BASE_URL; ?>/webhooks/postmark.php</code>
                                            </li>
                                            <li>Select events: <strong>Open</strong>, <strong>Delivery</strong>, <strong>Bounce</strong></li>
                                            <li>Save the webhook configuration</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-exclamation-triangle mr-1"></i> Important</h6>
                                            <p>The webhook will automatically update your contacts when:</p>
                                            <ul class="mb-0">
                                                <li>A recipient opens your email (Valid)</li>
                                                <li>An email is successfully delivered (Valid)</li>
                                                <li>An email bounces (Invalid)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
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