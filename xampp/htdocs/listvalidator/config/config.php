<?php
/**
 * Application configuration
 */

// Application path settings
define('BASE_PATH', dirname(__DIR__));
define('INCLUDE_PATH', BASE_PATH . '/includes');
define('VIEW_PATH', BASE_PATH . '/views');
define('UPLOAD_PATH', BASE_PATH . '/public/uploads');

// URL settings
define('BASE_URL', 'https://localhost/listvalidator/public'); // Change according to your server setup
define('ASSETS_URL', BASE_URL . '/assets');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Application settings
define('APP_NAME', 'List Validator');
define('APP_VERSION', '1.0.0');

// Pagination settings
define('ITEMS_PER_PAGE', 20);

// Security settings
define('CSRF_TOKEN_SECRET', 'change-this-to-a-random-string');

// Timezone
date_default_timezone_set('UTC');

// Postmark API settings (defaults)
define('POSTMARK_API_URL', 'https://api.postmarkapp.com');
define('POSTMARK_API_KEY', 'your-postmark-api-key'); // Will be overridden by settings if available
define('POSTMARK_SENDER_EMAIL', 'noreply@yourdomain.com'); // Will be overridden by settings
define('POSTMARK_SENDER_NAME', 'Email Validator'); // Will be overridden by settings
define('POSTMARK_MESSAGE_STREAM', 'outbound'); // Optional, default is "outbound"

// Check if settings table exists and if so, override the defaults with database values
// First include required files
require_once __DIR__ . '/database.php';

// Function to safely check if settings table exists and has values
function table_exists($table_name) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if table exists
        $query = "SHOW TABLES LIKE :table_name";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Helper function to safely get settings without errors
function get_setting_safe($key, $default = null) {
    if (!function_exists('get_setting')) {
        // Load settings file if it exists
        $settings_file = INCLUDE_PATH . '/settings.php';
        if (file_exists($settings_file)) {
            require_once $settings_file;
            if (function_exists('get_setting')) {
                return get_setting($key, $default);
            }
        }
        return $default;
    }
    return get_setting($key, $default);
}

// Try to use database settings if available
if (table_exists('settings')) {
    // Postmark settings from database
    $db_api_key = get_setting_safe('postmark_api_key', '');
    $db_sender_email = get_setting_safe('postmark_sender_email', '');
    $db_sender_name = get_setting_safe('postmark_sender_name', '');
    
    // Override constants only if values exist in database
    if (!empty($db_api_key)) define('POSTMARK_API_KEY_DB', $db_api_key);
    if (!empty($db_sender_email)) define('POSTMARK_SENDER_EMAIL_DB', $db_sender_email);
    if (!empty($db_sender_name)) define('POSTMARK_SENDER_NAME_DB', $db_sender_name);
}