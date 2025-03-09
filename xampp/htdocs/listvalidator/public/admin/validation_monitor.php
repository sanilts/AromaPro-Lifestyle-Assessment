<?php
/**
 * Email Validation Monitor Interface
 * 
 * This page allows admin users to monitor validation status and manually run checks
 */

require_once '../../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/list_management.php';

// Require admin authentication
require_auth(true);

// Get lists with pending validations
$database = new Database();
$db = $database->getConnection();

// Handle manual checking request
$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_validation'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid form submission, please try again';
        $status = 'danger';
    } else {
        $list_id = isset($_POST['list_id']) ? (int)$_POST['list_id'] : 0;
        $interval = isset($_POST['interval']) ? sanitize($_POST['interval']) : 'all';
        
        // Execute validation check
        $command = PHP_BINARY . ' ' . APP_ROOT . '/scheduled_validation_checker.php';
        if ($list_id) {
            $command .= ' ' . $list_id;
        } else {
            $command .= ' all';
        }
        $command .= ' ' . $interval . ' 2>&1';
        
        // Execute command and capture output
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            $message = 'Validation check completed. See logs for details.';
            $status = 'success';
        } else {
            $message = 'Error running validation check: ' . implode("\n", $output);
            $status = 'danger';
        }
    }
}

// Get recent validation logs
$logs_dir = APP_ROOT . '/logs';
$validation_logs = [];

if (is_dir($logs_dir)) {
    $log_files = glob($logs_dir . '/scheduled_validation_*.log');
    
    if (!empty($log_files)) {
        // Sort by modification time, newest first
        usort($log_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Get the most recent log file
        $recent_log = $log_files[0];
        
        // Read last 50 lines
        $file = new SplFileObject($recent_log);
        $file->seek(PHP_INT_MAX); // Seek to end of file
        $total_lines = $file->key(); // Get total line count
        
        $lines_to_read = min(50, $total_lines);
        $start_line = max(0, $total_lines - $lines_to_read);
        
        $validation_logs = [];
        $file->seek($start_line);
        
        while (!$file->eof()) {
            $line = $file->current();
            if (!empty(trim($line))) {
                $validation_logs[] = $line;
            }
            $file->next();
        }
        
        // Reverse to show newest first
        $validation_logs = array_reverse($validation_logs);
    }
}

// Get lists with pending validations
$lists_query = "SELECT l.id, l.list_name, COUNT(c.id) as pending_count
               FROM lists l
               JOIN contacts c ON l.id = c.list_id
               WHERE c.email_status = 'pending'
               AND c.validation_message IS NOT NULL
               GROUP BY l.id, l.list_name
               ORDER BY pending_count DESC";

$lists_stmt = $db->prepare($lists_query);
$lists_stmt->execute();
$lists = $lists_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get validation statistics
$stats_query = "SELECT 
               SUM(CASE WHEN email_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
               SUM(CASE WHEN email_status = 'valid' THEN 1 ELSE 0 END) as valid_count,
               SUM(CASE WHEN email_status = 'invalid' THEN 1 ELSE 0 END) as invalid_count,
               COUNT(*) as total_count
               FROM contacts
               WHERE validation_message IS NOT NULL";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Email Validation Monitor - ' . APP_NAME;
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
    <style>
        .log-container {
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.85rem;
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .log-entry {
            margin-bottom: 5px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .log-info { color: #0c5460; }
        .log-error { color: #721c24; }
    </style>
</head>
<body>
    <?php include_once VIEW_PATH . '/layout/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include_once VIEW_PATH . '/layout/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $status; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Email Validation Monitor</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h2 class="mb-0"><?php echo number_format($stats['total_count'] ?? 0); ?></h2>
                                        <p class="text-muted mb-0">Total Validations</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <h2 class="mb-0"><?php echo number_format($stats['pending_count'] ?? 0); ?></h2>
                                        <p class="mb-0">Pending</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h2 class="mb-0"><?php echo number_format($stats['valid_count'] ?? 0); ?></h2>
                                        <p class="mb-0">Valid</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h2 class="mb-0"><?php echo number_format($stats['invalid_count'] ?? 0); ?></h2>
                                        <p class="mb-0">Invalid</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="post" action="" class="mb-4">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="check_validation" value="1">
                            
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Run Manual Check</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label for="list_id">Select List</label>
                                                <select name="list_id" id="list_id" class="form-control">
                                                    <option value="">All Lists</option>
                                                    <?php foreach ($lists as $list): ?>
                                                    <option value="<?php echo $list['id']; ?>">
                                                        <?php echo htmlspecialchars($list['list_name']); ?> 
                                                        (<?php echo $list['pending_count']; ?> pending)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="interval">Check Interval</label>
                                                <select name="interval" id="interval" class="form-control">
                                                    <option value="all">All Intervals</option>
                                                    <option value="15m">15 Minutes</option>
                                                    <option value="30m">30 Minutes</option>
                                                    <option value="1h">1 Hour</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fas fa-sync-alt mr-1"></i> Run Check
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <h5 class="mb-3">Recent Validation Logs</h5>
                        <div class="log-container">
                            <?php if (empty($validation_logs)): ?>
                                <p class="text-muted">No recent logs found.</p>
                            <?php else: ?>
                                <?php foreach ($validation_logs as $log): ?>
                                    <?php 
                                    $log_class = (strpos($log, '[ERROR]') !== false) ? 'log-error' : 'log-info';
                                    ?>
                                    <div class="log-entry <?php echo $log_class; ?>"><?php echo htmlspecialchars($log); ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($lists)): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Lists with Pending Validations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>List Name</th>
                                        <th>Pending Validations</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lists as $list): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($list['list_name']); ?></td>
                                        <td><?php echo $list['pending_count']; ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/lists/view.php?id=<?php echo $list['id']; ?>&status=pending" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <form method="post" action="" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="check_validation" value="1">
                                                <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                                                <input type="hidden" name="interval" value="all">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-sync-alt"></i> Check Now
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Auto-scroll to bottom of log container
        document.addEventListener('DOMContentLoaded', function() {
            var logContainer = document.querySelector('.log-container');
            if (logContainer) {
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        });
    </script>
</body>
</html>