<?php
/**
 * Utility functions
 */

/**
 * Redirect to a specific page
 * 
 * @param string $path The path to redirect to
 * @return void
 */
// Add this line to include validation.php
require_once __DIR__ . '/validation.php';

/**
 * Redirect to a specific page
 * 
 * @param string $path The path to redirect to
 * @return void
 */
function redirect($path) {
    // Remove leading slash if present for consistency
    $path = ltrim($path, '/');
    header("Location: " . BASE_URL . "/" . $path);
    exit;
}


/**
 * Generate CSRF token
 * 
 * @return string
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token The token to verify
 * @return boolean
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    return true;
}

/**
 * Display flash messages
 * 
 * @return void
 */
function display_flash_messages() {
    if (isset($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $type => $message) {
            echo "<div class='alert alert-{$type}'>{$message}</div>";
        }
        unset($_SESSION['flash']);
    }
}

/**
 * Set flash message
 * 
 * @param string $type Message type (success, info, warning, danger)
 * @param string $message Message content
 * @return void
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Sanitize input data
 * 
 * @param mixed $data The data to sanitize
 * @return mixed
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
    } else {
        $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return boolean
 */
function is_valid_email_format($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if user is logged in
 * 
 * @return boolean
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if current user is admin
 * 
 * @return boolean
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require authentication to access a page
 * 
 * @param boolean $require_admin Whether admin privileges are required
 * @return void
 */
function require_auth($require_admin = false) {
    if (!is_logged_in()) {
        set_flash_message('danger', 'You must log in to access this page');
        redirect('/login.php');
    }
    
    if ($require_admin && !is_admin()) {
        set_flash_message('danger', 'You do not have permission to access this page');
        redirect('/dashboard.php');
    }
}

/**
 * Format date/time
 * 
 * @param string $datetime The date/time to format
 * @param string $format The format to use
 * @return string
 */
function format_datetime($datetime, $format = 'Y-m-d H:i:s') {
    $dt = new DateTime($datetime);
    return $dt->format($format);
}

/**
 * Parse CSV file
 * 
 * @param string $file_path Path to the CSV file
 * @return array|false
 */
function parse_csv($file_path) {
    $rows = [];
    
    if (($handle = fopen($file_path, "r")) !== false) {
        // Try to detect the delimiter
        $sample = fread($handle, 1024);
        rewind($handle);
        
        // Count occurrences of common delimiters
        $delimiters = [',', ';', "\t", '|'];
        $delimiter = ','; // Default
        $max_count = 0;
        
        foreach ($delimiters as $d) {
            $count = substr_count($sample, $d);
            if ($count > $max_count) {
                $max_count = $count;
                $delimiter = $d;
            }
        }
        
        // Parse the CSV
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Trim all values to remove leading/trailing whitespace
            $data = array_map('trim', $data);
            
            // Skip completely empty rows
            if (count(array_filter($data, 'strlen')) > 0) {
                $rows[] = $data;
            }
        }
        fclose($handle);
        return $rows;
    }
    
    return false;
}

/**
 * Generate a random filename
 * 
 * @param string $extension File extension
 * @return string
 */
function generate_filename($extension = '') {
    return uniqid() . '_' . time() . ($extension ? '.' . $extension : '');
}

/**
 * Check if string contains only alphanumeric characters and spaces
 * 
 * @param string $str String to check
 * @return boolean
 */
function is_alphanumeric_with_spaces($str) {
    return preg_match('/^[a-zA-Z0-9 ]+$/', $str);
}

/**
 * Validate that required fields are present in data
 * 
 * @param array $data Data to check
 * @param array $required_fields Required field names
 * @return array Empty if valid, otherwise contains error messages
 */
function validate_required_fields($data, $required_fields) {
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    return $errors;
}


/**
 * Send validation email to contacts and track delivery
 * 
 * @param int $list_id List ID
 * @return array Response with status and counts
 */
function send_validation_emails($list_id) {
    $response = [
        'success' => false,
        'message' => '',
        'sent_count' => 0,
        'failed_count' => 0
    ];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get list details
        $list = get_list_by_id($list_id);
        if (!$list) {
            $response['message'] = 'List not found';
            return $response;
        }
        
        // Get contacts to validate
        $contacts = get_list_contacts($list_id, null, null, 'pending');
        
        if (empty($contacts)) {
            $response['message'] = 'No pending contacts to validate';
            return $response;
        }
        
        // Prepare email template
        $subject = 'Email Validation for ' . $list['list_name'];
        $html_template = '
        <html>
        <body>
            <h2>Email Validation</h2>
            <p>Hello {first_name},</p>
            <p>This email is being sent to validate your email address for our list: <strong>{list_name}</strong>.</p>
            <p>If you received this email, your email address is valid.</p>
            <p>Thank you!</p>
        </body>
        </html>';
        
        $text_template = "Hello {first_name},\n\nThis email is being sent to validate your email address for our list: {list_name}.\n\nIf you received this email, your email address is valid.\n\nThank you!";
        
        // Send emails to each contact
        foreach ($contacts as $contact) {
            // Replace placeholders
            $html_body = str_replace(
                ['{first_name}', '{list_name}'],
                [htmlspecialchars($contact['first_name']), htmlspecialchars($list['list_name'])],
                $html_template
            );
            
            $text_body = str_replace(
                ['{first_name}', '{list_name}'],
                [$contact['first_name'], $list['list_name']],
                $text_template
            );
            
            // Send email
            $result = send_email_with_postmark(
                $contact['email'],
                $subject,
                $html_body,
                $text_body
            );
            
            if ($result['success']) {
                // Update contact with message ID for tracking
                $message_id = $result['data']['MessageID'];
                
                $update_query = "UPDATE contacts SET 
                                validation_message = :message_id
                                WHERE id = :contact_id";
                
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':message_id', $message_id);
                $update_stmt->bindParam(':contact_id', $contact['id']);
                $update_stmt->execute();
                
                $response['sent_count']++;
            } else {
                $response['failed_count']++;
            }
            
            // Add a small delay to avoid rate limits
            usleep(200000); // 200ms
        }
        
        $response['success'] = true;
        $response['message'] = 'Validation emails sent: ' . 
                              $response['sent_count'] . ' sent, ' . 
                              $response['failed_count'] . ' failed';
        
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    return $response;
}

