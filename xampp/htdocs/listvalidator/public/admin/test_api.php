<?php
/**
 * API test endpoint with email testing
 */

require_once '../../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/settings.php';
require_once INCLUDE_PATH . '/validation.php';

// Require admin authentication
require_auth(true);

// Set JSON response headers
header('Content-Type: application/json');

// Check CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

// Get action and parameters from request
$action = $_POST['action'] ?? 'verify';
$api_key = $_POST['api_key'] ?? '';
$test_email = $_POST['test_email'] ?? '';

if (empty($api_key)) {
    echo json_encode([
        'success' => false,
        'message' => 'API key is required'
    ]);
    exit;
}

if ($action === 'send_test' && empty($test_email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Test email address is required'
    ]);
    exit;
}

// Process based on action
if ($action === 'send_test') {
    // Send test email
    $subject = 'Postmark API Test - ' . date('Y-m-d H:i:s');
    $html_body = '
    <html>
    <body>
        <h2>Postmark API Test</h2>
        <p>This is a test email sent from your List Validator application to verify the Postmark API integration.</p>
        <p>If you received this email, your Postmark API configuration is working correctly!</p>
        <hr>
        <p><small>Sent at: ' . date('Y-m-d H:i:s') . '</small></p>
    </body>
    </html>';
    
    $text_body = "Postmark API Test\n\nThis is a test email sent from your List Validator application to verify the Postmark API integration.\n\nIf you received this email, your Postmark API configuration is working correctly!\n\nSent at: " . date('Y-m-d H:i:s');
    
    // Use the provided API key for this test
    $result = send_email_with_postmark(
        $test_email, 
        $subject, 
        $html_body, 
        $text_body, 
        '', // Use default sender email from settings
        '', // Use default sender name from settings
        $api_key // Pass the API key explicitly
    );
    
    if ($result['success']) {
        // Update the API key in the database if the test is successful
        update_setting('postmark_api_key', $api_key, 'api', true);
        
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully! Check your inbox at ' . $test_email,
            'details' => isset($result['data']['MessageID']) ? 'Message ID: ' . $result['data']['MessageID'] : ''
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send test email: ' . $result['message'],
            'details' => $result['data'] ?? ''
        ]);
    }
} else {
    // Just verify the API key
    $result = verify_postmark_api_key($api_key);
    echo json_encode($result);
}
exit;