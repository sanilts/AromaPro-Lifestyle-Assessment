<?php
/**
 * Sync email events from Postmark API - Fixed to handle array format
 * 
 * Replace this function in includes/validation.php
 * 
 * @param int $list_id List ID
 * @param string $status Filter by status (optional)
 * @param int $days Number of days to look back (default: 7)
 * @return array Result with counts
 */
function sync_postmark_events($list_id, $status = null, $days = 7) {
    $result = [
        'success' => false,
        'message' => '',
        'updated' => 0,
        'skipped' => 0,
        'events' => [
            'opened' => 0,
            'delivered' => 0,
            'sent' => 0,
            'bounced' => 0
        ]
    ];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get all contacts from the list based on status filter
        $emails_query = "SELECT id, email, validation_message FROM contacts WHERE list_id = :list_id";
        if ($status !== null) {
            $emails_query .= " AND email_status = :status";
        }
        
        $emails_stmt = $db->prepare($emails_query);
        $emails_stmt->bindParam(':list_id', $list_id);
        
        if ($status !== null) {
            $emails_stmt->bindParam(':status', $status);
        }
        
        $emails_stmt->execute();
        $contacts = $emails_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contacts)) {
            $result['message'] = 'No contacts found to update';
            return $result;
        }
        
        // Create logs directory if it doesn't exist
        $logs_dir = __DIR__ . '/../logs';
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0755, true);
        }
        
        // Add debug logging
        $log_file = __DIR__ . '/../logs/postmark_sync.log';
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Starting Postmark sync for list $list_id with " . count($contacts) . " contacts\n", FILE_APPEND);
        
        // Calculate date range
        $to_date = date('Y-m-d');
        $from_date = date('Y-m-d', strtotime("-$days days"));
        
        // API key and URL
        $api_key = POSTMARK_API_KEY;
        
        // Prepare update statement
        $update_query = "UPDATE contacts SET 
                        email_status = :status,
                        email_event = :event,
                        validation_message = :message_id,
                        validation_date = NOW()
                        WHERE id = :contact_id";
        $update_stmt = $db->prepare($update_query);
        
        // Begin transaction
        $db->beginTransaction();
        
        // Create lookup map of contacts by email for faster access
        $contacts_by_email = [];
        foreach ($contacts as $contact) {
            $email = strtolower($contact['email']);
            $contacts_by_email[$email] = $contact;
        }
        
        // Get all outbound messages from Postmark for the past X days
        $messages_url = POSTMARK_API_URL . '/messages/outbound?' . http_build_query([
            'count' => 500,
            'offset' => 0,
            'fromdate' => $from_date,
            'todate' => $to_date
        ]);
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Fetching all outbound messages: $messages_url\n", FILE_APPEND);
        
        $ch = curl_init($messages_url);
        $headers = [
            'Accept: application/json',
            'X-Postmark-Server-Token: ' . $api_key
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $messages_response = curl_exec($ch);
        $messages_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        // Skip if API error
        if ($messages_http_code !== 200 || !$messages_response || empty($messages_response)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - API error fetching messages: HTTP $messages_http_code\n", FILE_APPEND);
            $result['message'] = 'Error fetching messages from Postmark API';
            return $result;
        }
        
        $messages_data = json_decode($messages_response, true);
        
        if (!isset($messages_data['Messages']) || empty($messages_data['Messages'])) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - No messages found in Postmark\n", FILE_APPEND);
            $result['message'] = 'No messages found in Postmark for the past ' . $days . ' days';
            return $result;
        }
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Found " . count($messages_data['Messages']) . " messages\n", FILE_APPEND);
        
        // Process each message to find matching contacts
        foreach ($messages_data['Messages'] as $message) {
            // Handle "To" field which could be a string or an array
            $to_email = '';
            
            if (isset($message['To'])) {
                if (is_string($message['To'])) {
                    // If it's a string, extract the email part if it has a format like "Name <email@example.com>"
                    if (preg_match('/<([^>]+)>/', $message['To'], $matches)) {
                        $to_email = strtolower($matches[1]);
                    } else {
                        $to_email = strtolower($message['To']);
                    }
                } elseif (is_array($message['To']) && !empty($message['To'])) {
                    // If it's an array, take the first entry
                    $first_recipient = $message['To'][0];
                    if (is_array($first_recipient) && isset($first_recipient['Email'])) {
                        $to_email = strtolower($first_recipient['Email']);
                    } elseif (is_string($first_recipient)) {
                        if (preg_match('/<([^>]+)>/', $first_recipient, $matches)) {
                            $to_email = strtolower($matches[1]);
                        } else {
                            $to_email = strtolower($first_recipient);
                        }
                    }
                }
            }
            
            $message_id = isset($message['MessageID']) ? $message['MessageID'] : '';
            
            // Skip if no "To" email or message ID
            if (empty($to_email) || empty($message_id)) {
                continue;
            }
            
            // Skip if no matching contact
            if (!isset($contacts_by_email[$to_email])) {
                continue;
            }
            
            $contact = $contacts_by_email[$to_email];
            $contact_id = $contact['id'];
            
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Found matching email: $to_email (Contact ID: $contact_id)\n", FILE_APPEND);
            
            // Get message details
            $details_url = POSTMARK_API_URL . '/messages/outbound/' . $message_id . '/details';
            
            $ch = curl_init($details_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $details_response = curl_exec($ch);
            $details_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            // Skip if API error
            if ($details_http_code !== 200 || !$details_response || empty($details_response)) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error getting details for message $message_id\n", FILE_APPEND);
                continue;
            }
            
            $details = json_decode($details_response, true);
            
            // Check message status
            $is_opened = isset($details['Opens']) && !empty($details['Opens']);
            $is_delivered = isset($details['Delivered']) && $details['Delivered'];
            $is_bounced = isset($details['Bounced']) && $details['Bounced'];
            
            // Determine event and status
            $event = 'unknown';
            $validation_status = 'pending';
            
            if ($is_opened) {
                $event = 'opened';
                $validation_status = 'valid';
                $result['events']['opened']++;
            } elseif ($is_delivered) {
                $event = 'delivered';
                $validation_status = 'valid';
                $result['events']['delivered']++;
            } elseif (isset($details['Status']) && strtolower($details['Status']) === 'sent') {
                $event = 'sent';
                $validation_status = 'valid';
                $result['events']['sent']++;
            } elseif ($is_bounced) {
                $event = 'bounced';
                $validation_status = 'invalid';
                $result['events']['bounced']++;
            } else {
                $result['skipped']++;
                continue;
            }
            
            // Update contact
            $update_stmt->bindParam(':contact_id', $contact_id);
            $update_stmt->bindParam(':status', $validation_status);
            $update_stmt->bindParam(':event', $event);
            $update_stmt->bindParam(':message_id', $message_id);
            
            if ($update_stmt->execute()) {
                $result['updated']++;
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Updated contact {$contact['email']} to $event ($validation_status)\n", FILE_APPEND);
            } else {
                $result['skipped']++;
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to update contact {$contact['email']}\n", FILE_APPEND);
            }
        }
        
        // Commit transaction
        $db->commit();
        
        if ($result['updated'] > 0) {
            $result['success'] = true;
            $result['message'] = "Postmark sync completed: {$result['updated']} contacts updated, {$result['skipped']} skipped";
        } else {
            $result['success'] = true;
            $result['message'] = "No matching emails found in Postmark activity for the past $days days. Try sending validation emails first.";
        }
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Sync completed: {$result['message']}\n", FILE_APPEND);
        
    } catch (PDOException $e) {
        // Rollback on error
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        $result['message'] = 'Database error: ' . $e->getMessage();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Database error: " . $e->getMessage() . "\n", FILE_APPEND);
    } catch (Exception $e) {
        // Rollback on error
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        $result['message'] = 'Error: ' . $e->getMessage();
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    return $result;
}

/**
 * Validate a list of email addresses using Postmark API
 * 
 * @param array $emails Array of email addresses to validate
 * @return array Associative array with validation results
 */
function validate_emails_with_postmark($emails) {
    $api_key = POSTMARK_API_KEY;
    // Update the API URL to point to the correct email validation endpoint
    $api_url = POSTMARK_API_URL . '/email/batch/validate';
    
    // Add debug logging
    $log_file = __DIR__ . '/../logs/validation.log';
    
    // Create logs directory if it doesn't exist
    $logs_dir = __DIR__ . '/../logs';
    if (!is_dir($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Starting Postmark validation for " . count($emails) . " emails\n", FILE_APPEND);
    
    // If no emails to validate, return empty result
    if (empty($emails)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - No emails to validate\n", FILE_APPEND);
        return [];
    }
    
    // Prepare email batch in correct format for Postmark batch validation
    // Postmark expects an object with an "Emails" array
    $payload = ["Emails" => $emails];
    
    // Log the payload
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Request payload: " . json_encode($payload) . "\n", FILE_APPEND);
    
    // Prepare the request
    $ch = curl_init($api_url);
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Postmark-Server-Token: ' . $api_key
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // For debugging connection issues
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    // Execute the request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    // Get verbose information
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    fclose($verbose);
    
    curl_close($ch);
    
    // Log the response
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - HTTP Code: " . $http_code . "\n", FILE_APPEND);
    
    if (!empty($curl_error)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - cURL Error: " . $curl_error . "\n", FILE_APPEND);
    }
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Response: " . substr($response, 0, 1000) . "\n", FILE_APPEND);
    
    // As a fallback for troubleshooting, implement basic validation
    if ($http_code !== 200 || !$response || empty($response)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Using fallback validation\n", FILE_APPEND);
        
        $fallback_results = [];
        foreach ($emails as $email) {
            $is_valid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            $fallback_results[$email] = [
                'valid' => $is_valid,
                'suggested' => $email,
                'syntax_valid' => $is_valid,
                'smtp_valid' => false,
                'message' => 'Fallback validation used - Postmark API unavailable',
                'is_disposable' => false,
                'is_role_address' => false
            ];
        }
        
        return $fallback_results;
    }
    
    // Process the response
    $decoded_response = json_decode($response, true);
    
    // Check if we have the expected format with a Results array
    if (!isset($decoded_response['Results']) || !is_array($decoded_response['Results'])) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Invalid response format\n", FILE_APPEND);
        return [];
    }
    
    // Format the results
    $validation_results = [];
    foreach ($decoded_response['Results'] as $result) {
        if (!isset($result['Email'])) {
            continue;
        }
        
        $email = $result['Email'];
        // Postmark returns Success flag to indicate if email is valid
        $is_valid = (isset($result['Success']) && $result['Success'] === true);
        
        $validation_results[$email] = [
            'valid' => $is_valid,
            'suggested' => $result['SuggestedCorrection'] ?? $email,
            'syntax_valid' => $result['ValidSyntax'] ?? $is_valid,
            'smtp_valid' => isset($result['ValidSMTP']) && $result['ValidSMTP'] === true,
            'message' => json_encode($result),
            'is_disposable' => isset($result['DisposableAddress']) && $result['DisposableAddress'] === true,
            'is_role_address' => isset($result['RoleAddress']) && $result['RoleAddress'] === true
        ];
    }
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Processed " . count($validation_results) . " validation results\n", FILE_APPEND);
    
    return $validation_results;
}


/**
 * Send an email via Postmark and track its status
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $html_body HTML body content
 * @param string $text_body Plain text body content
 * @param string $from_email Sender email
 * @param string $from_name Sender name
 * @param string $custom_api_key Optional custom API key to use instead of the stored one
 * @return array Response with status and message
 */
function send_email_with_postmark($to, $subject, $html_body, $text_body = '', $from_email = '', $from_name = '', $custom_api_key = null) {
    // Use custom API key if provided, otherwise try to get from database first, then fallback to constant
    if ($custom_api_key) {
        $api_key = $custom_api_key;
    } else {
        // Try to get from database first
        $api_key = get_setting('postmark_api_key', '');
        
        // If empty, fallback to constant
        if (empty($api_key) && defined('POSTMARK_API_KEY')) {
            $api_key = POSTMARK_API_KEY;
        }
    }
    
    $api_url = 'https://api.postmarkapp.com/email';
    
    // Create logs directory if it doesn't exist
    $logs_dir = __DIR__ . '/../logs';
    if (!is_dir($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }
    
    // Add debug logging
    $log_file = __DIR__ . '/../logs/email_sending.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Attempting to send email to: $to\n", FILE_APPEND);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Using API key: " . substr($api_key, 0, 5) . '...' . substr($api_key, -5) . "\n", FILE_APPEND);
    
    // Get sender email from settings if not provided
    if (empty($from_email)) {
        $from_email = get_setting('postmark_sender_email', '');
        if (empty($from_email) && defined('POSTMARK_SENDER_EMAIL')) {
            $from_email = POSTMARK_SENDER_EMAIL;
        }
    }
    
    // Get sender name from settings if not provided
    if (empty($from_name)) {
        $from_name = get_setting('postmark_sender_name', '');
        if (empty($from_name) && defined('POSTMARK_SENDER_NAME')) {
            $from_name = POSTMARK_SENDER_NAME;
        }
    }
    
    $from = !empty($from_name) ? "{$from_name} <{$from_email}>" : $from_email;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - From: $from\n", FILE_APPEND);
    
    // Prepare the email payload
    $payload = [
        'From' => $from,
        'To' => $to,
        'Subject' => $subject,
        'HtmlBody' => $html_body,
        'TrackOpens' => true
    ];
    
    if (!empty($text_body)) {
        $payload['TextBody'] = $text_body;
    }
    
    // Add message stream if defined
    $message_stream = get_setting('postmark_message_stream', '');
    if (empty($message_stream) && defined('POSTMARK_MESSAGE_STREAM')) {
        $message_stream = POSTMARK_MESSAGE_STREAM;
    }
    
    if (!empty($message_stream)) {
        $payload['MessageStream'] = $message_stream;
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Using message stream: " . $message_stream . "\n", FILE_APPEND);
    }
    
    // Log the payload (truncated for security/size)
    $log_payload = $payload;
    if (isset($log_payload['HtmlBody']) && strlen($log_payload['HtmlBody']) > 100) {
        $log_payload['HtmlBody'] = substr($log_payload['HtmlBody'], 0, 100) . '...';
    }
    if (isset($log_payload['TextBody']) && strlen($log_payload['TextBody']) > 100) {
        $log_payload['TextBody'] = substr($log_payload['TextBody'], 0, 100) . '...';
    }
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Payload: " . json_encode($log_payload) . "\n", FILE_APPEND);
    
    // Prepare the request
    $ch = curl_init($api_url);
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Postmark-Server-Token: ' . $api_key
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // For detailed debugging
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    // Execute the request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    // Get verbose information
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    fclose($verbose);
    
    // Log the results
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - HTTP Code: $http_code\n", FILE_APPEND);
    if (!empty($curl_error)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - cURL Error: $curl_error\n", FILE_APPEND);
    }
    
    // Log verbose output for debugging
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Verbose: " . substr($verbose_log, 0, 500) . "\n", FILE_APPEND);
    
    curl_close($ch);
    
    $result = [
        'success' => false,
        'message' => '',
        'data' => null
    ];
    
    // Process the response
    if ($http_code == 200 && $response) {
        $decoded_response = json_decode($response, true);
        if (isset($decoded_response['MessageID'])) {
            $result['success'] = true;
            $result['message'] = 'Email sent successfully';
            $result['data'] = $decoded_response;
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Success: Email sent with MessageID " . $decoded_response['MessageID'] . "\n", FILE_APPEND);
        } else {
            $result['message'] = 'Unknown error sending email';
            $result['data'] = $decoded_response;
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Unknown error: " . json_encode($decoded_response) . "\n", FILE_APPEND);
        }
    } else {
        $error_message = 'API error: HTTP code ' . $http_code;
        if ($response) {
            $decoded_response = json_decode($response, true);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Response: " . $response . "\n", FILE_APPEND);
            if (isset($decoded_response['Message'])) {
                $error_message .= ' - ' . $decoded_response['Message'];
            }
            $result['data'] = $decoded_response;
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Empty response\n", FILE_APPEND);
        }
        $result['message'] = $error_message;
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: " . $error_message . "\n", FILE_APPEND);
    }
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - End of email sending attempt to $to\n", FILE_APPEND);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " -----------------------------\n", FILE_APPEND);
    
    return $result;
}