<?php
/**
 * Postmark API Test Script
 * 
 * This script tests both email sending and validation through the Postmark API.
 * Run this script to verify your API key is working correctly.
 */

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Set test email addresses
$test_email = isset($_GET['email']) ? $_GET['email'] : 'test@example.com';
$test_action = isset($_GET['action']) ? $_GET['action'] : 'validate';

echo '<!DOCTYPE html>
<html>
<head>
    <title>Postmark API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #007bff; }
        .success { color: green; }
        .error { color: red; }
        .container { max-width: 800px; margin: 0 auto; }
        pre { background: #f8f9fa; padding: 10px; overflow: auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; }
        button:hover { background: #0069d9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Postmark API Test</h1>
        <p>This script tests your Postmark API integration for both email sending and validation.</p>
        
        <form method="get" action="">
            <div class="form-group">
                <label for="email">Test Email Address:</label>
                <input type="email" id="email" name="email" value="' . htmlspecialchars($test_email) . '" required>
            </div>
            
            <div class="form-group">
                <label>Action:</label>
                <label>
                    <input type="radio" name="action" value="validate" ' . ($test_action == 'validate' ? 'checked' : '') . '> 
                    Validate Email
                </label>
                <label>
                    <input type="radio" name="action" value="send" ' . ($test_action == 'send' ? 'checked' : '') . '> 
                    Send Test Email
                </label>
            </div>
            
            <button type="submit">Run Test</button>
        </form>
        
        <hr>';

// Display configuration
echo '<h2>Configuration</h2>';
echo '<p>Postmark API Key: ' . (defined('POSTMARK_API_KEY') ? substr(POSTMARK_API_KEY, 0, 5) . '...' . substr(POSTMARK_API_KEY, -5) : 'Not defined') . '</p>';
echo '<p>Postmark Sender Email: ' . (defined('POSTMARK_SENDER_EMAIL') ? POSTMARK_SENDER_EMAIL : 'Not defined') . '</p>';

// Run tests
if ($test_action == 'validate') {
    echo '<h2>Email Validation Test</h2>';
    testEmailValidation($test_email);
} else if ($test_action == 'send') {
    echo '<h2>Email Sending Test</h2>';
    testEmailSending($test_email);
}

echo '</div></body></html>';

/**
 * Test email validation
 */
function testEmailValidation($email) {
    $api_key = POSTMARK_API_KEY;
    
    // Validate email
    $result = validateEmail($api_key, $email);
    
    echo '<h3>Validation Results for: ' . htmlspecialchars($email) . '</h3>';
    
    if ($result) {
        echo '<p>API Response:</p>';
        echo '<pre>' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . '</pre>';
        
        // Display summary
        $is_valid = isset($result['DeliveryStatus']) && $result['DeliveryStatus'] === 'Deliverable';
        echo '<p>Email is <strong class="' . ($is_valid ? 'success' : 'error') . '">' . 
             ($is_valid ? 'valid' : 'invalid') . '</strong></p>';
        
        if (isset($result['SuggestedCorrection']) && $result['SuggestedCorrection']) {
            echo '<p>Suggested correction: <strong>' . htmlspecialchars($result['SuggestedCorrection']) . '</strong></p>';
        }
    } else {
        echo '<p class="error">API call failed. Check your API key and try again.</p>';
    }
}

/**
 * Test email sending
 */
function testEmailSending($email) {
    $api_key = POSTMARK_API_KEY;
    $from_email = defined('POSTMARK_SENDER_EMAIL') ? POSTMARK_SENDER_EMAIL : 'noreply@example.com';
    $subject = "Test Email from List Validator";
    $body = "Hello,\n\nThis is a test email sent from the List Validator application to verify the Postmark API integration.\n\nTime: " . date('Y-m-d H:i:s');
    
    // Send email
    $result = sendEmail($api_key, $from_email, $email, $subject, $body);
    
    echo '<h3>Sending Results to: ' . htmlspecialchars($email) . '</h3>';
    
    if ($result) {
        echo '<p>API Response:</p>';
        echo '<pre>' . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . '</pre>';
        
        if (isset($result['MessageID'])) {
            echo '<p class="success">Email sent successfully!</p>';
            echo '<p>Message ID: ' . htmlspecialchars($result['MessageID']) . '</p>';
            
            // Check status after a brief delay
            sleep(2);
            echo '<h3>Checking Delivery Status</h3>';
            $status = checkEmailStatus($api_key, $result['MessageID']);
            
            if ($status) {
                echo '<pre>' . htmlspecialchars(json_encode($status, JSON_PRETTY_PRINT)) . '</pre>';
            } else {
                echo '<p class="error">Could not retrieve email status.</p>';
            }
        } else {
            echo '<p class="error">Failed to send email.</p>';
        }
    } else {
        echo '<p class="error">API call failed. Check your API key and try again.</p>';
    }
}

/**
 * Validate an email using Postmark API
 */
function validateEmail($api_key, $email) {
    $url = "https://api.postmarkapp.com/email/validate";
    
    $data = json_encode([
        'Email' => $email
    ]);
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Postmark-Server-Token: ' . $api_key
    ];
    
    return makePostmarkRequest($url, $headers, $data);
}

/**
 * Send an email using Postmark API
 */
function sendEmail($api_key, $from, $to, $subject, $body) {
    $url = "https://api.postmarkapp.com/email";
    
    $data = json_encode([
        'From' => $from,
        'To' => $to,
        'Subject' => $subject,
        'TextBody' => $body,
        'MessageStream' => defined('POSTMARK_MESSAGE_STREAM') ? POSTMARK_MESSAGE_STREAM : 'outbound'
    ]);
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Postmark-Server-Token: ' . $api_key
    ];
    
    return makePostmarkRequest($url, $headers, $data);
}

/**
 * Check email status using Postmark API
 */
function checkEmailStatus($api_key, $message_id) {
    $url = "https://api.postmarkapp.com/messages/outbound/$message_id/details";
    
    $headers = [
        'Accept: application/json',
        'X-Postmark-Server-Token: ' . $api_key
    ];
    
    return makePostmarkRequest($url, $headers);
}

/**
 * Make a generic request to the Postmark API
 */
function makePostmarkRequest($url, $headers, $postData = null) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code >= 200 && $http_code < 300 && $response) {
        return json_decode($response, true);
    } else {
        echo '<p class="error">API Error: HTTP Code ' . $http_code . '</p>';
        if ($curl_error) {
            echo '<p class="error">cURL Error: ' . htmlspecialchars($curl_error) . '</p>';
        }
        if ($response) {
            echo '<p>Response:</p>';
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
        }
        return null;
    }
}

function checkEmailStatusByRecipient($api_key, $email) {
    $url = "https://api.postmarkapp.com/messages/outbound?recipient=" . urlencode($email) . "&count=5";
    
    $headers = [
        'Accept: application/json',
        'X-Postmark-Server-Token: ' . $api_key
    ];
    
    return makePostmarkRequest($url, $headers);
}