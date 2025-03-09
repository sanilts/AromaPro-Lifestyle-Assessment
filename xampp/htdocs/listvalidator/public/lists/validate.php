<?php
/**
 * Email validation page
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
    redirect('lists/index.php');
}

// Process action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;

if ($action === 'send') {
    // Send validation emails
    $result = send_validation_emails($list_id);
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', 'Error: ' . $result['message']);
    }
    
    redirect('lists/validate.php?id=' . $list_id);
} 
elseif ($action === 'check') {
    // Check email delivery status
    $result = check_validation_email_status($list_id);
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', 'Error: ' . $result['message']);
    }
    
    redirect('lists/validate.php?id=' . $list_id);
}
elseif ($action === 'api') {
    // Use Postmark API validation
    $result = validate_list_emails($list_id);
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', 'Error: ' . $result['message']);
    }
    
    redirect('lists/validate.php?id=' . $list_id);
}
elseif ($action === 'reset') {
    // Reset validation status
    $result = reset_validation_status($list_id, $status_filter);
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', 'Error: ' . $result['message']);
    }
    
    redirect('lists/validate.php?id=' . $list_id);
}
elseif ($action === 'sync') {
    // Quick sync with Postmark API (last 7 days)
    $result = sync_postmark_events($list_id, null, 7);
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', 'Error: ' . $result['message']);
    }
    
    redirect('lists/validate.php?id=' . $list_id);
}
elseif ($action === 'smtp') {
    // Verify SMTP servers
    $result = verify_smtp_servers($list_id, $status_filter);
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', 'Error: ' . $result['message']);
    }
    
    redirect('lists/validate.php?id=' . $list_id);
}

$page_title = 'Validate List: ' . htmlspecialchars($list['list_name']) . ' - ' . APP_NAME;

// Get validation statistics
$total_contacts = count_list_contacts($list_id);
$valid_contacts = count_list_contacts($list_id, 'valid');
$invalid_contacts = count_list_contacts($list_id, 'invalid');
$pending_contacts = count_list_contacts($list_id, 'pending');

// Get event statistics
$opened_count = count_list_contacts_by_event($list_id, 'opened');
$delivered_count = count_list_contacts_by_event($list_id, 'delivered');
$sent_count = count_list_contacts_by_event($list_id, 'sent');
$bounced_count = count_list_contacts_by_event($list_id, 'bounced');
$smtp_valid_count = count_list_contacts_by_event($list_id, 'smtp_valid');
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
                        <h5 class="mb-0">Validate List: <?php echo htmlspecialchars($list['list_name']); ?></h5>
                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left mr-1"></i> Back to List
                        </a>
                    </div>
                    <div class="card-body">
                        <?php display_flash_messages(); ?>
                        
                        <div class="alert alert-info">
                            <h5>Validation Options</h5>
                            <p>Choose a method to validate the emails in this list:</p>
                            <ol>
                                <li><strong>Postmark API Validation</strong> - Uses Postmark's Email Validation API to check email syntax, domain, and more.</li>
                                <li><strong>Send Validation Emails</strong> - Sends an actual email to each address to verify deliverability.</li>
                                <li><strong>Check Email Status</strong> - Checks the delivery status of previously sent validation emails.</li>
                                <li><strong>Verify SMTP Servers</strong> - Checks if the email domain has valid SMTP servers (MX records).</li>
                                <li><strong>Reset Validation</strong> - Resets validation status to allow revalidation of emails.</li>
                            </ol>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h2 class="mb-0"><?php echo $total_contacts; ?></h2>
                                        <p class="text-muted mb-0">Total</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h2 class="mb-0"><?php echo $valid_contacts; ?></h2>
                                        <p class="mb-0">Valid</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h2 class="mb-0"><?php echo $invalid_contacts; ?></h2>
                                        <p class="mb-0">Invalid</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning">
                                    <div class="card-body text-center">
                                        <h2 class="mb-0"><?php echo $pending_contacts; ?></h2>
                                        <p class="mb-0">Pending</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Email Events Breakdown -->
                            <div class="col-12 mt-4">
                                <h6 class="mb-2">Email Events Breakdown</h6>
                                <div class="progress" style="height: 30px;">
                                    <?php
                                    // Calculate percentages
                                    $total = $total_contacts > 0 ? $total_contacts : 1; // Avoid division by zero
                                    $opened_percent = round(($opened_count / $total) * 100);
                                    $delivered_percent = round(($delivered_count / $total) * 100);
                                    $sent_percent = round(($sent_count / $total) * 100);
                                    $bounced_percent = round(($bounced_count / $total) * 100);
                                    $smtp_percent = round(($smtp_valid_count / $total) * 100);
                                    $other_percent = 100 - $opened_percent - $delivered_percent - $sent_percent - $bounced_percent - $smtp_percent;
                                    ?>
                                    
                                    <?php if ($opened_percent > 0): ?>
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $opened_percent; ?>%" 
                                         title="Opened: <?php echo $opened_count; ?>">
                                        <i class="fas fa-envelope-open mr-1"></i> <?php echo $opened_count; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($delivered_percent > 0): ?>
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $delivered_percent; ?>%" 
                                         title="Delivered: <?php echo $delivered_count; ?>">
                                        <i class="fas fa-check mr-1"></i> <?php echo $delivered_count; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($sent_percent > 0): ?>
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $sent_percent; ?>%" 
                                         title="Sent: <?php echo $sent_count; ?>">
                                        <i class="fas fa-paper-plane mr-1"></i> <?php echo $sent_count; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($smtp_percent > 0): ?>
                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo $smtp_percent; ?>%" 
                                         title="SMTP Valid: <?php echo $smtp_valid_count; ?>">
                                        <i class="fas fa-server mr-1"></i> <?php echo $smtp_valid_count; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($bounced_percent > 0): ?>
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $bounced_percent; ?>%" 
                                         title="Bounced: <?php echo $bounced_count; ?>">
                                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $bounced_count; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($other_percent > 0): ?>
                                    <div class="progress-bar bg-light text-dark" role="progressbar" style="width: <?php echo $other_percent; ?>%" 
                                         title="Other/Pending: <?php echo ($total_contacts - $opened_count - $delivered_count - $sent_count - $bounced_count - $smtp_valid_count); ?>">
                                        <i class="fas fa-question-circle mr-1"></i> <?php echo ($total_contacts - $opened_count - $delivered_count - $sent_count - $bounced_count - $smtp_valid_count); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex justify-content-between text-muted small mt-1">
                                    <div>
                                        <i class="fas fa-envelope-open text-info"></i> Opened: <?php echo $opened_count; ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-check text-success"></i> Delivered: <?php echo $delivered_count; ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-paper-plane text-primary"></i> Sent: <?php echo $sent_count; ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-server text-secondary"></i> SMTP: <?php echo $smtp_valid_count; ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-exclamation-circle text-danger"></i> Bounced: <?php echo $bounced_count; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">API Validation</h5>
                                        <p class="card-text">Validate emails using Postmark's API without sending actual emails.</p>
                                        <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>&action=api" class="btn btn-primary">
                                            <i class="fas fa-check-circle mr-1"></i> Validate with API
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Send Emails</h5>
                                        <p class="card-text">Send actual emails to pending contacts to verify deliverability.</p>
                                        <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>&action=send" class="btn btn-success">
                                            <i class="fas fa-paper-plane mr-1"></i> Send Emails
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Check Status</h5>
                                        <p class="card-text">Check delivery status of previously sent validation emails.</p>
                                        <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>&action=check" class="btn btn-info">
                                            <i class="fas fa-sync-alt mr-1"></i> Check Status
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Verify SMTP</h5>
                                        <p class="card-text">Check if domain's SMTP servers exist for each email.</p>
                                        <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>&action=smtp" class="btn btn-secondary">
                                            <i class="fas fa-server mr-1"></i> Verify SMTP
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Reset Validation</h5>
                                        <p class="card-text">Reset validation status to allow revalidation of emails.</p>
                                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#resetModal">
                                            <i class="fas fa-undo mr-1"></i> Reset Status
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Postmark API Integration Section -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">Postmark API Integration</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="text-center">
                                                    <h6>Sync Events from Postmark</h6>
                                                    <p>Connect to Postmark API to get the latest email events</p>
                                                    <div class="btn-group">
                                                        <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>&action=sync" class="btn btn-primary">
                                                            <i class="fas fa-sync-alt mr-1"></i> Quick Sync (7 days)
                                                        </a>
                                                        <a href="<?php echo BASE_URL; ?>/lists/sync_events.php?id=<?php echo $list_id; ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-cog mr-1"></i> Advanced
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info mb-0">
                                                    <h6><i class="fas fa-lightbulb mr-1"></i> Real-Time Updates</h6>
                                                    <p class="mb-0">Set up Postmark webhooks to automatically update email status when events occur (opens, deliveries, bounces).</p>
                                                    <a href="<?php echo BASE_URL; ?>/lists/sync_events.php?id=<?php echo $list_id; ?>#webhook" class="btn btn-sm btn-outline-info mt-2">
                                                        <i class="fas fa-cog mr-1"></i> Webhook Setup
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Event Filtering Section -->
                        <div class="card mt-4">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Filter by Email Event</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if ($opened_count > 0): ?>
                                    <div class="col-md-3 mb-2">
                                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&event=opened" class="btn btn-outline-info btn-block">
                                            <i class="fas fa-envelope-open mr-1"></i> Opened (<?php echo $opened_count; ?>)
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($delivered_count > 0): ?>
                                    <div class="col-md-3 mb-2">
                                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&event=delivered" class="btn btn-outline-success btn-block">
                                            <i class="fas fa-check mr-1"></i> Delivered (<?php echo $delivered_count; ?>)
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($sent_count > 0): ?>
                                    <div class="col-md-3 mb-2">
                                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&event=sent" class="btn btn-outline-primary btn-block">
                                            <i class="fas fa-paper-plane mr-1"></i> Sent (<?php echo $sent_count; ?>)
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($smtp_valid_count > 0): ?>
                                    <div class="col-md-3 mb-2">
                                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&event=smtp_valid" class="btn btn-outline-secondary btn-block">
                                            <i class="fas fa-server mr-1"></i> SMTP Valid (<?php echo $smtp_valid_count; ?>)
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($bounced_count > 0): ?>
                                    <div class="col-md-3 mb-2">
                                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&event=bounced" class="btn btn-outline-danger btn-block">
                                            <i class="fas fa-exclamation-circle mr-1"></i> Bounced (<?php echo $bounced_count; ?>)
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($opened_count == 0 && $delivered_count == 0 && $sent_count == 0 && $bounced_count == 0 && $smtp_valid_count == 0): ?>
                                    <div class="col-12">
                                        <div class="alert alert-secondary">
                                            <i class="fas fa-info-circle mr-1"></i> No email events have been recorded yet. Use the options above to validate or sync with Postmark.
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Validation Tips Card -->
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Validation Tips</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-0">
                                    <h6><i class="fas fa-lightbulb mr-1"></i> Best Practices</h6>
                                    <ul class="mb-0">
                                        <li>Start with <strong>SMTP Verification</strong> for a quick check of domain validity</li>
                                        <li><strong>Postmark API</strong> validation is fast and doesn't require sending actual emails</li>
                                        <li>The most reliable method is <strong>sending actual emails</strong> and checking delivery</li>
                                        <li>Email events like "Opened" and "Delivered" provide proof of email validity</li>
                                        <li>For emails marked as invalid, consider checking manually before removing them</li>
                                        <li>Use the reset function if your Postmark API key or settings have changed</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reset Validation Modal -->
                        <div class="modal fade" id="resetModal" tabindex="-1" role="dialog" aria-labelledby="resetModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning">
                                        <h5 class="modal-title" id="resetModalLabel">Reset Validation Status</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Select which email statuses you want to reset to "pending":</p>
                                        
                                        <div class="list-group">
                                            <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>&action=reset" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h5 class="mb-1">All Emails</h5>
                                                    <span class="badge badge-primary badge-pill"><?php echo $total_contacts; ?></span>
                                                </div>
                                                <p class="mb-1">Reset validation status for all emails in this list</p>
                                            </a>
                                            
                                            <?php if ($valid_contacts > 0): ?>
                                            <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>&action=reset&status=valid" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h5 class="mb-1">Valid Emails Only</h5>
                                                    <span class="badge badge-success badge-pill"><?php echo $valid_contacts; ?></span>
                                                </div>
                                                <p class="mb-1">Reset only emails currently marked as valid</p>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($invalid_contacts > 0): ?>
                                            <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>&action=reset&status=invalid" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h5 class="mb-1">Invalid Emails Only</h5>
                                                    <span class="badge badge-danger badge-pill"><?php echo $invalid_contacts; ?></span>
                                                </div>
                                                <p class="mb-1">Reset only emails currently marked as invalid</p>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> This action will clear all validation results for the selected emails and set their status to "pending".
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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