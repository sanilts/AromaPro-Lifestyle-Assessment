<?php
/**
 * View list details page
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

// Check if list exists and user has access
if (!$list || ($list['user_id'] != $user_id && !is_admin())) {
    set_flash_message('danger', 'List not found or access denied');
    redirect('/dashboard.php');
}

// Process contact validation request
if (isset($_GET['validate']) && $_GET['validate'] == '1') {
    $validation_result = validate_list_emails($list_id);
    set_flash_message($validation_result['success'] ? 'success' : 'danger', $validation_result['message']);
    redirect('/lists/view.php?id=' . $list_id);
}

// Process reset validation request
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $status_filter = isset($_GET['status']) ? $_GET['status'] : null;
    $reset_result = reset_validation_status($list_id, $status_filter);
    set_flash_message($reset_result['success'] ? 'success' : 'danger', $reset_result['message']);
    redirect('/lists/view.php?id=' . $list_id);
}

// Get list contacts with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Filter by status
$status_filter = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'valid', 'invalid']) ? $_GET['status'] : null;

// Filter by event
$event_filter = isset($_GET['event']) && in_array($_GET['event'], ['opened', 'delivered', 'sent', 'bounced']) ? $_GET['event'] : null;

// Get contacts based on filters
if ($event_filter) {
    $contacts = get_list_contacts_by_event($list_id, $event_filter, $limit, $offset);
    $total_contacts = count_list_contacts_by_event($list_id, $event_filter);
} else {
    $contacts = get_list_contacts($list_id, $limit, $offset, $status_filter);
    $total_contacts = count_list_contacts($list_id, $status_filter);
}

$total_pages = ceil($total_contacts / $limit);

// Get download history
$download_history = get_list_download_history($list_id);

// Get event counts for display
$opened_count = count_list_contacts_by_event($list_id, 'opened');
$delivered_count = count_list_contacts_by_event($list_id, 'delivered');
$sent_count = count_list_contacts_by_event($list_id, 'sent');
$bounced_count = count_list_contacts_by_event($list_id, 'bounced');

$page_title = 'View List: ' . htmlspecialchars($list['list_name']) . ' - ' . APP_NAME;
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
                        <h5 class="mb-0">List: <?php echo htmlspecialchars($list['list_name']); ?></h5>
                        <div>
                            <a href="<?php echo BASE_URL; ?>/lists/download.php?id=<?php echo $list_id; ?>" class="btn btn-sm btn-light mr-1">
                                <i class="fas fa-download mr-1"></i> Download
                            </a>
                            <a href="<?php echo BASE_URL; ?>/lists/validate.php?id=<?php echo $list_id; ?>" class="btn btn-sm btn-light mr-1">
                                <i class="fas fa-cog mr-1"></i> Validation Options
                            </a>
                            <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&validate=1" class="btn btn-sm btn-light mr-1">
                                <i class="fas fa-check-circle mr-1"></i> Validate
                            </a>
                            <button type="button" class="btn btn-sm btn-light" data-toggle="modal" data-target="#resetModal">
                                <i class="fas fa-undo mr-1"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php display_flash_messages(); ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h1 class="display-4 mb-0"><?php echo $list['contact_count']; ?></h1>
                                        <p class="text-muted mb-0">Total Contacts</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h1 class="display-4 mb-0"><?php echo $list['valid_count']; ?></h1>
                                        <p class="mb-0">Valid Emails</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h1 class="display-4 mb-0"><?php echo $list['invalid_count']; ?></h1>
                                        <p class="mb-0">Invalid Emails</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Events Summary -->
                        <?php if ($opened_count > 0 || $delivered_count > 0 || $sent_count > 0 || $bounced_count > 0): ?>
                        <div class="mb-4">
                            <h6 class="mb-2">Email Events</h6>
                            <div class="row">
                                <?php if ($opened_count > 0): ?>
                                <div class="col-md-3 mb-2">
                                    <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&event=opened" class="btn btn-outline-info btn-sm btn-block">
                                        <i class="fas fa-envelope-open mr-1"></i> Opened: <?php echo $opened_count; ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($delivered_count > 0): ?>
                                <div class="col-md-3 mb-2">
                                    <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&event=delivered" class="btn btn-outline-success btn-sm btn-block">
                                        <i class="fas fa-check mr-1"></i> Delivered: <?php echo $delivered_count; ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($sent_count > 0): ?>
                                <div class="col-md-3 mb-2">
                                    <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&event=sent" class="btn btn-outline-primary btn-sm btn-block">
                                        <i class="fas fa-paper-plane mr-1"></i> Sent: <?php echo $sent_count; ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($bounced_count > 0): ?>
                                <div class="col-md-3 mb-2">
                                    <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&event=bounced" class="btn btn-outline-danger btn-sm btn-block">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Bounced: <?php echo $bounced_count; ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                Contact Details
                                <?php if ($event_filter): ?>
                                <span class="badge badge-info ml-2">
                                    Filtered by: <?php echo ucfirst($event_filter); ?> events
                                    <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>" class="text-white ml-1">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                                <?php endif; ?>
                            </h6>
                            
                            <?php if (!$event_filter): ?>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>" class="btn <?php echo $status_filter === null ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                                <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&status=valid" class="btn <?php echo $status_filter === 'valid' ? 'btn-success' : 'btn-outline-success'; ?>">Valid</a>
                                <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&status=invalid" class="btn <?php echo $status_filter === 'invalid' ? 'btn-danger' : 'btn-outline-danger'; ?>">Invalid</a>
                                <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($contacts)): ?>
                            <div class="alert alert-info">
                                No contacts found<?php echo $status_filter ? ' with ' . $status_filter . ' status' : ''; ?>
                                <?php echo $event_filter ? ' with ' . $event_filter . ' event' : ''; ?>.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Event</th>
                                            <th>Validated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $count = $offset + 1;
                                        foreach ($contacts as $contact): 
                                            $status_class = '';
                                            $status_icon = '';
                                            
                                            switch ($contact['email_status']) {
                                                case 'valid':
                                                    $status_class = 'success';
                                                    $status_icon = 'check-circle';
                                                    break;
                                                case 'invalid':
                                                    $status_class = 'danger';
                                                    $status_icon = 'times-circle';
                                                    break;
                                                default:
                                                    $status_class = 'warning';
                                                    $status_icon = 'clock';
                                            }
                                            
                                            // Set event badge color and icon
                                            $event_class = 'secondary';
                                            $event_icon = 'info-circle';
                                            
                                            if (!empty($contact['email_event'])) {
                                                switch ($contact['email_event']) {
                                                    case 'opened':
                                                        $event_class = 'info';
                                                        $event_icon = 'envelope-open';
                                                        break;
                                                    case 'delivered':
                                                        $event_class = 'success';
                                                        $event_icon = 'check';
                                                        break;
                                                    case 'sent':
                                                        $event_class = 'primary';
                                                        $event_icon = 'paper-plane';
                                                        break;
                                                    case 'bounced':
                                                        $event_class = 'danger';
                                                        $event_icon = 'exclamation-circle';
                                                        break;
                                                }
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $status_class; ?>">
                                                        <i class="fas fa-<?php echo $status_icon; ?> mr-1"></i>
                                                        <?php echo ucfirst($contact['email_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($contact['email_event'])): ?>
                                                    <span class="badge badge-<?php echo $event_class; ?>">
                                                        <i class="fas fa-<?php echo $event_icon; ?> mr-1"></i>
                                                        <?php echo ucfirst($contact['email_event']); ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($contact['validation_date']) {
                                                        echo format_datetime($contact['validation_date'], 'M d, Y H:i');
                                                    } else {
                                                        echo '<span class="text-muted">Not validated</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?id=<?php echo $list_id; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $event_filter ? '&event=' . $event_filter : ''; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">Previous</span>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $list_id; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $event_filter ? '&event=' . $event_filter : ''; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?id=<?php echo $list_id; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $event_filter ? '&event=' . $event_filter : ''; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">Next</span>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Download History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($download_history)): ?>
                            <div class="alert alert-info">
                                No download history found for this list.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Downloaded By</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($download_history as $download): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($download['username']); ?></td>
                                                <td><?php echo format_datetime($download['download_date'], 'M d, Y H:i:s'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&reset=1" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">All Emails</h5>
                                <span class="badge badge-primary badge-pill"><?php echo $list['contact_count']; ?></span>
                            </div>
                            <p class="mb-1">Reset validation status for all emails in this list</p>
                        </a>
                        
                        <?php if ($list['valid_count'] > 0): ?>
                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&reset=1&status=valid" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Valid Emails Only</h5>
                                <span class="badge badge-success badge-pill"><?php echo $list['valid_count']; ?></span>
                            </div>
                            <p class="mb-1">Reset only emails currently marked as valid</p>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($list['invalid_count'] > 0): ?>
                        <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list_id; ?>&reset=1&status=invalid" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Invalid Emails Only</h5>
                                <span class="badge badge-danger badge-pill"><?php echo $list['invalid_count']; ?></span>
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
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>