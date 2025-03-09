<?php
/**
 * Setup script to create initial admin user and set up the application
 */

require_once './config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';

// Check if we're in CLI mode or web mode
$is_cli = (php_sapi_name() === 'cli');

// Function to output messages
function output($message, $is_error = false) {
    global $is_cli;
    
    if ($is_cli) {
        if ($is_error) {
            echo "\033[31m" . $message . "\033[0m\n";
        } else {
            echo $message . "\n";
        }
    } else {
        if ($is_error) {
            echo '<div style="color: red; margin-bottom: 10px;">' . htmlspecialchars($message) . '</div>';
        } else {
            echo '<div style="margin-bottom: 10px;">' . htmlspecialchars($message) . '</div>';
        }
    }
}

// Check database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    output("✅ Database connection successful.");
} catch (PDOException $e) {
    output("❌ Database connection failed: " . $e->getMessage(), true);
    exit(1);
}

// Check if admin user already exists
$admin_exists = false;
try {
    $query = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        $admin_exists = true;
        output("ℹ️ Admin user already exists.");
    }
} catch (PDOException $e) {
    output("❌ Error checking for admin user: " . $e->getMessage(), true);
}

// Setup web form
if (!$is_cli && !$admin_exists) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
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
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                output($error, true);
            }
        } else {
            // Create admin user
            $result = register_user($username, $email, $password, 'admin');
            
            if ($result['success']) {
                output("✅ Admin user created successfully.");
                echo '<div style="margin-top: 20px;"><a href="public/login.php" style="background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">Go to Login Page</a></div>';
            } else {
                output("❌ Failed to create admin user: " . $result['message'], true);
            }
        }
    } else {
        // Display setup form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Setup - <?php echo APP_NAME; ?></title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    line-height: 1.5;
                    padding: 20px;
                    max-width: 600px;
                    margin: 0 auto;
                }
                h1 {
                    color: #007bff;
                }
                .form-group {
                    margin-bottom: 15px;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                }
                input[type="text"],
                input[type="email"],
                input[type="password"] {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .text-danger {
                    color: red;
                }
                .btn {
                    background-color: #007bff;
                    color: white;
                    border: none;
                    padding: 10px 15px;
                    border-radius: 4px;
                    cursor: pointer;
                }
                .btn:hover {
                    background-color: #0069d9;
                }
                .alert {
                    padding: 15px;
                    border-radius: 4px;
                    margin-bottom: 20px;
                }
                .alert-info {
                    background-color: #d1ecf1;
                    border: 1px solid #bee5eb;
                    color: #0c5460;
                }
            </style>
        </head>
        <body>
            <h1>Setup <?php echo APP_NAME; ?></h1>
            
            <div class="alert alert-info">
                Welcome to the setup wizard. This will help you set up the application and create an admin user.
            </div>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Admin Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" id="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Admin Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Admin Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" id="password" required>
                    <small>Password must be at least 8 characters long</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Create Admin & Complete Setup</button>
                </div>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
}

// CLI mode setup
if ($is_cli && !$admin_exists) {
    output("Creating admin user...");
    
    // Get admin details from CLI
    echo "Enter admin username: ";
    $username = trim(fgets(STDIN));
    
    echo "Enter admin email: ";
    $email = trim(fgets(STDIN));
    
    echo "Enter admin password (min 8 characters): ";
    $password = trim(fgets(STDIN));
    
    // Create admin user
    if (empty($username) || empty($email) || empty($password)) {
        output("❌ All fields are required", true);
        exit(1);
    }
    
    if (strlen($password) < 8) {
        output("❌ Password must be at least 8 characters", true);
        exit(1);
    }
    
    $result = register_user($username, $email, $password, 'admin');
    
    if ($result['success']) {
        output("✅ Admin user created successfully.");
    } else {
        output("❌ Failed to create admin user: " . $result['message'], true);
        exit(1);
    }
}

// Check upload directory
$upload_dir = UPLOAD_PATH;
if (!is_dir($upload_dir)) {
    output("Creating uploads directory...");
    if (mkdir($upload_dir, 0755, true)) {
        output("✅ Uploads directory created.");
    } else {
        output("❌ Failed to create uploads directory. Please create it manually at: " . $upload_dir, true);
    }
} else {
    if (is_writable($upload_dir)) {
        output("✅ Uploads directory exists and is writable.");
    } else {
        output("❌ Uploads directory exists but is not writable. Please set correct permissions.", true);
    }
}

// Check Postmark API key
if (defined('POSTMARK_API_KEY') && POSTMARK_API_KEY !== 'your-postmark-api-key') {
    output("✅ Postmark API key is configured.");
} else {
    output("⚠️ Postmark API key is not configured. Please update it in config/config.php.", true);
}

// Final message
if ($is_cli) {
    output("\n✅ Setup completed successfully.");
    output("You can now access the application at: " . BASE_URL);
} else if ($admin_exists) {
    output("✅ Setup has already been completed.");
    echo '<div style="margin-top: 20px;"><a href="public/login.php" style="background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">Go to Login Page</a></div>';
}