<?php
/**
 * Postmark webhook handler
 * 
 * This file receives webhook notifications from Postmark
 * and updates contact status based on events
 * 
 * Place this file at public/webhooks/postmark.php
 */

require_once '../../config/config.php';
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/list_management.php';

// Get webhook request body
$json_data = file_get_contents('php://input');
$log_file = __DIR__ . '/../../logs/webhook.log';

// Create logs directory if it doesn't exist
$logs_dir = __DIR__ . '/../../logs';
if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

// Log webhook request
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Webhook received: " . $json_data . "\n", FILE_APPEND);

if (empty($json_data)) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit;
}

// Parse JSON data
$data = json_decode($json_data, true);

if (!$data || !isset($data['RecordType'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data format']);
    exit;
}

try {
    // Initialize database
    $database = new Database();
    $db = $database->getConnection();
    
    // Process webhook based on RecordType
    $record_type = $data['RecordType'];
    $email = isset($data['Recipient']) ? $data['Recipient'] : '';
    $message_id = isset($data['MessageID']) ? $data['MessageID'] : '';
    
    // Skip if email or message ID is missing
    if (empty($email) || empty($message_id)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Missing email or message ID\n", FILE_APPEND);
        http_response_code(200); // Acknowledge receipt even though we can't process it
        echo json_encode(['status' => 'skipped']);
        exit;
    }
    
    // Map event type to our system
    $event = 'unknown';
    $status = 'pending';
    
    switch ($record_type) {
        case 'Open':
            $event = 'opened';
            $status = 'valid';
            break;
        case 'Delivery':
            $event = 'delivered';
            $status = 'valid';
            break;
        case 'Bounce':
            $event = 'bounced';
            $status = 'invalid';
            break;
        case 'SpamComplaint':
            $event = 'complained';
            $status = 'invalid';
            break;
        default:
            // Other event types like Click, Subscription, etc.
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Unsupported record type: $record_type\n", FILE_APPEND);
            http_response_code(200);
            echo json_encode(['status' => 'skipped']);
            exit;
    }
    
    // Find the contact with this email
    $query = "SELECT id, list_id FROM contacts WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Contact not found
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Contact not found for email: $email\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode(['status' => 'contact_not_found']);
        exit;
    }
    
    // Get contact info
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    $contact_id = $contact['id'];
    $list_id = $contact['list_id'];
    
    // Update contact status
    $update_query = "UPDATE contacts SET 
                    email_status = :status, 
                    email_event = :event,
                    validation_message = :message_id,
                    validation_date = NOW()
                    WHERE id = :contact_id";
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':status', $status);
    $update_stmt->bindParam(':event', $event);
    $update_stmt->bindParam(':message_id', $message_id);
    $update_stmt->bindParam(':contact_id', $contact_id);
    
    if ($update_stmt->execute()) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Updated contact $contact_id with event: $event, status: $status\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => "Contact updated with $event event"
        ]);
    } else {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to update contact\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update contact']);
    }
    
} catch (Exception $e) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}