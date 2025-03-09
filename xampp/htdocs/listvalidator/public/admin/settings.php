<?php
/**
 * Admin settings page
 */

// Define the correct path to config
$config_path = '../../config/config.php';
if (!file_exists($config_path)) {
    die("Error: Config file not found. Please check your installation.");
}

// Required includes with error handling
require_once $config_path;
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/list_management.php'; // Added this line to include list_management.php

// Check if settings.php exists
$settings_path = INCLUDE_PATH . '/settings.php';
if (!file_exists($settings_path)) {
    die("Error: Settings file not found. Please ensure includes/settings.php exists.");
}
require_once $settings_path;

// Require admin authentication
require_auth(true);

// Set user_id for the sidebar
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission, please try again';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_postmark') {
            $api_key = $_POST['postmark_api_key'] ?? '';
            $sender_email = sanitize($_POST['postmark_sender_email'] ?? '');
            $sender_name = sanitize($_POST['postmark_sender_name'] ?? '');
            
            // Validate input
            $errors = [];
            
            if (empty($sender_email)) {
                $errors[] = 'Sender email is required';
            } elseif (!is_valid_email_format($sender_email)) {
                $errors[] = 'Invalid sender email format';
            }
            
            if (empty($sender_name)) {
                $errors[] = 'Sender name is required';
            }
            
            if (!empty($api_key)) {
                // Verify the API key
                $api_verification = verify_postmark_api_key($api_key);
                
                if (!$api_verification['success']) {
                    $errors[] = 'Invalid Postmark API key: ' . $api_verification['message'];
                }
            }
            
            if (!empty($errors)) {
                $error = implode('<br>', $errors);
            } else {
                // Update settings
                $updated = true;
                
                if (!empty($api_key)) {
                    $updated = update_setting('postmark_api_key', $api_key, 'api', true) && $updated;
                }
                
                $updated = update_setting('postmark_sender_email', $sender_email, 'api', false) && $updated;
                $updated = update_setting('postmark_sender_name', $sender_name, 'api', false) && $updated;
                
                if ($updated) {
                    $success = 'Postmark settings updated successfully';
                    
                    if (!empty($api_key) && $api_verification['success']) {
                        $success .= '<br>API key verified successfully. Connected to server: ' . 
                                  ($api_verification['server_name'] ?? 'Unknown');
                    }
                } else {
                    $error = 'Failed to update settings';
                }
            }
        }
    }
}

// Get current settings - with error handling
try {
    $postmark_api_key = get_setting('postmark_api_key', '');
    $postmark_sender_email = get_setting('postmark_sender_email', 'noreply@yourdomain.com');
    $postmark_sender_name = get_setting('postmark_sender_name', 'Email Validator');
} catch (Exception $e) {
    $error = 'Error retrieving settings: ' . $e->getMessage();
    $postmark_api_key = '';
    $postmark_sender_email = 'noreply@yourdomain.com';
    $postmark_sender_name = 'Email Validator';
}

$page_title = 'Settings - ' . APP_NAME;
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
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Application Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php display_flash_messages(); ?>
                        
                        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="postmark-tab" data-toggle="tab" href="#postmark" role="tab">
                                    <i class="fas fa-envelope mr-1"></i> Postmark
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="general-tab" data-toggle="tab" href="#general" role="tab">
                                    <i class="fas fa-cogs mr-1"></i> General
                                </a>
                            </li>
                        </ul>
                        
                        <div class="tab-content p-3 border border-top-0 rounded-bottom">
                            <!-- Postmark Settings -->
                            <div class="tab-pane fade show active" id="postmark" role="tabpanel">
                                <form method="post" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="update_postmark">
                                    
                                    <div class="form-group">
                                        <label for="postmark_api_key">Postmark API Key</label>
                                        <div class="input-group">
                                            <input type="password" name="postmark_api_key" id="postmark_api_key" class="form-control" 
                                                value="<?php echo htmlspecialchars($postmark_api_key); ?>" autocomplete="off">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="toggleApiKey">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Your Postmark Server API Token. Leave blank to keep the current value.
                                        </small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="postmark_sender_email">Sender Email</label>
                                        <input type="email" name="postmark_sender_email" id="postmark_sender_email" class="form-control" 
                                            value="<?php echo htmlspecialchars($postmark_sender_email); ?>" required>
                                        <small class="form-text text-muted">
                                            This email address must be verified in your Postmark account.
                                        </small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="postmark_sender_name">Sender Name</label>
                                        <input type="text" name="postmark_sender_name" id="postmark_sender_name" class="form-control" 
                                            value="<?php echo htmlspecialchars($postmark_sender_name); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save mr-1"></i> Save Postmark Settings
                                        </button>
                                        <button type="button" class="btn btn-info" id="testApiButton">
                                            <i class="fas fa-vial mr-1"></i> Test API Connection
                                        </button>
                                    </div>
                                </form>
                                
                                <div id="apiTestResult" class="mt-3" style="display: none;"></div>
                            </div>
                            
                            <!-- General Settings -->
                            <div class="tab-pane fade" id="general" role="tabpanel">
                                <p>General application settings will be added here in a future update.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        // Toggle API key visibility
        $('#toggleApiKey').click(function() {
            var apiKeyInput = $('#postmark_api_key');
            var icon = $(this).find('i');
            
            if (apiKeyInput.attr('type') === 'password') {
                apiKeyInput.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                apiKeyInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Test API connection
        $('#testApiButton').click(function() {
            var apiKey = $('#postmark_api_key').val();
            
            if (!apiKey) {
                $('#apiTestResult').html('<div class="alert alert-warning">Please enter an API key to test.</div>').show();
                return;
            }
            
            $('#apiTestResult').html('<div class="alert alert-info">Testing connection...</div>').show();
            
            $.ajax({
                url: '<?php echo BASE_URL; ?>/admin/test_api.php',
                type: 'POST',
                data: {
                    csrf_token: '<?php echo generate_csrf_token(); ?>',
                    api_key: apiKey,
                    action: 'verify'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#apiTestResult').html('<div class="alert alert-success">' + 
                            response.message + 
                            '<br><br>Would you like to send a test email to verify email sending works?' +
                            '<div class="input-group mt-2">' +
                            '<input type="email" id="testEmailAddress" class="form-control" placeholder="Enter your email address" value="' + ($('#postmark_sender_email').val() || '') + '">' +
                            '<div class="input-group-append">' +
                            '<button type="button" class="btn btn-success" id="sendTestEmail">Send Test Email</button>' +
                            '</div></div></div>');
                        
                        // Bind event for the send test email button
                        $('#sendTestEmail').off('click').on('click', function() {
                            sendTestEmail(apiKey);
                        });
                    } else {
                        $('#apiTestResult').html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#apiTestResult').html('<div class="alert alert-danger">Error testing API connection.</div>');
                }
            });
        });
        
        // Function to send test email
        function sendTestEmail(apiKey) {
            var testEmail = $('#testEmailAddress').val();
            
            if (!testEmail) {
                $('#apiTestResult').html('<div class="alert alert-warning">Please enter a valid email address for testing.</div>').show();
                return;
            }
            
            $('#apiTestResult').html('<div class="alert alert-info">Sending test email to ' + testEmail + '...</div>').show();
            
            $.ajax({
                url: '<?php echo BASE_URL; ?>/admin/test_api.php',
                type: 'POST',
                data: {
                    csrf_token: '<?php echo generate_csrf_token(); ?>',
                    api_key: apiKey,
                    test_email: testEmail,
                    action: 'send_test'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#apiTestResult').html('<div class="alert alert-success">' + 
                            response.message + 
                            '<br>' + (response.details || '') + 
                            '<br><br>If the API key works correctly, it has been saved to your settings.</div>');
                    } else {
                        $('#apiTestResult').html('<div class="alert alert-danger">' + 
                            response.message + 
                            '<br>' + (response.details || '') + '</div>');
                    }
                },
                error: function() {
                    $('#apiTestResult').html('<div class="alert alert-danger">Error sending test email.</div>');
                }
            });
        }
    });
</script>
</body>
</html>