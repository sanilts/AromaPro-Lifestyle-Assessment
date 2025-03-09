<?php
/**
 * Admin edit user page
 */

require_once '../../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';

// Require admin authentication
require_auth(true);

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_user = get_user_by_id($user_id);

// Check if user exists
if (!$edit_user) {
    set_flash_message('danger', 'User not found');
    redirect('/admin/users.php');
}

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission, please try again';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $role = sanitize($_POST['role'] ?? 'user');
        $new_password = $_POST['new_password'] ?? '';
        
        // Validate input
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Username is required';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!is_valid_email_format($email)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!in_array($role, ['admin', 'user'])) {
            $errors[] = 'Invalid role';
        }
        
        if (!empty($new_password) && strlen($new_password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            // Update user
            $update_data = [
                'username' => $username,
                'email' => $email,
                'role' => $role
            ];
            
            $result = update_user($user_id, $update_data);
            
            // Change password if provided
            if (!empty($new_password)) {
                // For admin, we'll bypass the current password check and directly set the new password
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                try {
                    $database = new Database();
                    $db = $database->getConnection();
                    
                    $query = "UPDATE users SET password = :password WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $password_hash);
                    $stmt->bindParam(':id', $user_id);
                    $stmt->execute();
                    
                    $password_updated = true;
                } catch (PDOException $e) {
                    $password_updated = false;
                }
            }
            
            if ($result) {
                $success = 'User updated successfully';
                if (!empty($new_password) && $password_updated) {
                    $success .= ' with new password';
                }
                
                // Refresh user data
                $edit_user = get_user_by_id($user_id);
            } else {
                $error = 'Failed to update user';
            }
        }
    }
}

$page_title = 'Edit User - ' . APP_NAME;
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
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Edit User: <?php echo htmlspecialchars($edit_user['username']); ?></h5>
                        <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Users
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php display_flash_messages(); ?>
                        
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="form-group">
                                <label for="username">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Role <span class="text-danger">*</span></label>
                                <select name="role" id="role" class="form-control" required>
                                    <option value="user" <?php echo $edit_user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $edit_user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control">
                                <small class="form-text text-muted">Leave blank to keep current password. New password must be at least 8 characters.</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Account Created</label>
                                <input type="text" class="form-control" value="<?php echo format_datetime($edit_user['created_at'], 'F d, Y H:i:s'); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Update User
                                </button>
                                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-secondary">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </a>
                            </div>
                        </form>
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