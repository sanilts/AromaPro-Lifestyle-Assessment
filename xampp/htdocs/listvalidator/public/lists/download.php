<?php
/**
 * Download list as CSV
 */

require_once '../../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/list_management.php';

// Require authentication
require_auth();

$user_id = $_SESSION['user_id'];
$list_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) && in_array($_GET['status'], ['valid', 'invalid']) ? $_GET['status'] : null;

// Get list details
$list = get_list_by_id($list_id);

// Check if list exists and user has access
if (!$list || ($list['user_id'] != $user_id && !is_admin())) {
    set_flash_message('danger', 'List not found or access denied');
    redirect('/dashboard.php');
}

// Generate CSV content
$csv_content = generate_list_csv($list_id, $status);

if ($csv_content === false) {
    set_flash_message('danger', 'Failed to generate CSV file');
    redirect('/lists/view.php?id=' . $list_id);
}

// Record download
record_list_download($list_id, $user_id);

// Set headers for download
$filename = sanitize($list['list_name']);
if ($status) {
    $filename .= '_' . $status;
}
$filename .= '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV content
echo $csv_content;
exit;