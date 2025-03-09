<?php
/**
 * Create new list page
 */

require_once '../../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/list_management.php';

// Require authentication
require_auth();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission, please try again';
    } else {
        $list_name = sanitize($_POST['list_name'] ?? '');
        
        // Validate list name
        if (empty($list_name)) {
            $error = 'List name is required';
        } elseif (!is_alphanumeric_with_spaces($list_name)) {
            $error = 'List name can only contain letters, numbers, and spaces';
        } else {
            // Create the list
            $result = create_list($list_name, $user_id);
            
            if ($result['success']) {
                $list_id = $result['list_id'];
                
                // Check if a file was uploaded
                if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
                    $file = $_FILES['csv_file'];
                    
                    // Validate file type
                    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if ($file_extension !== 'csv') {
                        $error = 'Only CSV files are allowed';
                    } else {
                        // Generate a unique filename
                        $upload_dir = UPLOAD_PATH;
                        $filename = generate_filename('csv');
                        $filepath = $upload_dir . '/' . $filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            // Parse CSV and add contacts
                            $parse_result = parse_contacts_csv($filepath);
                            
                            if ($parse_result['success']) {
                                $add_result = add_contacts_to_list($list_id, $parse_result['contacts']);
                                
                                if ($add_result['success']) {
                                    // Start validation process
                                    $validation_result = validate_list_emails($list_id);
                                    
                                    $success = 'List created successfully with ' . $add_result['count'] . ' contacts. ' . 
                                              $validation_result['message'];
                                } else {
                                    $error = 'Error adding contacts: ' . $add_result['message'];
                                }
                            } else {
                                $error = 'Error parsing CSV: ' . $parse_result['message'];
                            }
                            
                            // Delete temporary file
                            @unlink($filepath);
                        } else {
                            $error = 'Failed to upload file';
                        }
                    }
                } else {
                    $success = 'List created successfully. You can now add contacts.';
                }
                
                if (!$error) {
                    // Set flash message and redirect to the list view
                    set_flash_message('success', $success);
                    redirect('/lists/view.php?id=' . $list_id);
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}

$page_title = 'Create New List - ' . APP_NAME;
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
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Create New List</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php display_flash_messages(); ?>
                        
                        <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="form-group">
                                <label for="list_name">List Name <span class="text-danger">*</span></label>
                                <input type="text" name="list_name" id="list_name" class="form-control" required>
                                <small class="form-text text-muted">Use a descriptive name for your list (e.g., "Newsletter Subscribers July 2023")</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="csv_file">Upload CSV File</label>
                                <div class="custom-file">
                                    <input type="file" name="csv_file" id="csv_file" class="custom-file-input">
                                    <label class="custom-file-label" for="csv_file">Choose file</label>
                                </div>
                                <small class="form-text text-muted">
                                    CSV file must include columns for: First Name, Last Name, and Email
                                </small>
                            </div>
                            
                            <hr>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Create List
                                </button>
                                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">CSV Format Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <p>Your CSV file should have the following format:</p>
                        <pre class="bg-light p-3 border rounded">First Name,Last Name,Email
John,Doe,john.doe@example.com
Jane,Smith,jane.smith@example.com</pre>
                        
                        <div class="alert alert-warning">
                            <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Important Notes</h6>
                            <ul class="mb-0">
                                <li>The first row must contain the headers: <strong>First Name</strong>, <strong>Last Name</strong>, and <strong>Email</strong></li>
                                <li>Email addresses will be validated through Postmark's API</li>
                                <li>Maximum file size: 5MB</li>
                                <li>Make sure your CSV is properly formatted to avoid import errors</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Update file input label with selected filename
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Choose file';
            const label = this.nextElementSibling;
            label.textContent = fileName;
        });
    </script>
</body>
</html>