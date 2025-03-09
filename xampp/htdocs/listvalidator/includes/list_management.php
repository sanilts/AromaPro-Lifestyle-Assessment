<?php
/**
 * List management functions
 */

require_once __DIR__ . '/../config/database.php';
// At the top of includes/list_management.php
require_once __DIR__ . '/validation.php';

/**
 * Create a new list
 * 
 * @param string $list_name Name of the list
 * @param int $user_id ID of the user creating the list
 * @return array Response with status and list ID
 */
function create_list($list_name, $user_id) {
    $response = [
        'success' => false,
        'message' => '',
        'list_id' => null
    ];
    
    // Validate input
    if (empty($list_name)) {
        $response['message'] = 'List name is required';
        return $response;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if list name already exists for this user
        $check_query = "SELECT id FROM lists WHERE list_name = :list_name AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':list_name', $list_name);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $response['message'] = 'A list with this name already exists';
            return $response;
        }
        
        // Insert new list
        $query = "INSERT INTO lists (list_name, user_id) VALUES (:list_name, :user_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_name', $list_name);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'List created successfully';
            $response['list_id'] = $db->lastInsertId();
        } else {
            $response['message'] = 'Failed to create list';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Add contacts to a list
 * 
 * @param int $list_id List ID
 * @param array $contacts Array of contacts, each with first_name, last_name, and email
 * @return array Response with status and count
 */
function add_contacts_to_list($list_id, $contacts) {
    $response = [
        'success' => false,
        'message' => '',
        'count' => 0
    ];
    
    if (empty($contacts)) {
        $response['message'] = 'No contacts provided';
        return $response;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if list exists
        $check_query = "SELECT id FROM lists WHERE id = :list_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':list_id', $list_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            $response['message'] = 'List not found';
            return $response;
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        $query = "INSERT INTO contacts (list_id, first_name, last_name, email) 
                 VALUES (:list_id, :first_name, :last_name, :email)";
        $stmt = $db->prepare($query);
        
        $count = 0;
        foreach ($contacts as $contact) {
            if (empty($contact['email'])) {
                continue;
            }
            
            $stmt->bindParam(':list_id', $list_id);
            $stmt->bindParam(':first_name', $contact['first_name']);
            $stmt->bindParam(':last_name', $contact['last_name']);
            $stmt->bindParam(':email', $contact['email']);
            
            if ($stmt->execute()) {
                $count++;
            }
        }
        
        // Commit transaction
        $db->commit();
        
        if ($count > 0) {
            $response['success'] = true;
            $response['message'] = $count . ' contacts added successfully';
            $response['count'] = $count;
        } else {
            $response['message'] = 'No contacts were added';
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get a list by ID
 * 
 * @param int $list_id List ID
 * @return array|false List data or false if not found
 */
function get_list_by_id($list_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT l.*, u.username as created_by, 
                 (SELECT COUNT(*) FROM contacts WHERE list_id = l.id) as contact_count,
                 (SELECT COUNT(*) FROM contacts WHERE list_id = l.id AND email_status = 'valid') as valid_count,
                 (SELECT COUNT(*) FROM contacts WHERE list_id = l.id AND email_status = 'invalid') as invalid_count
                 FROM lists l
                 JOIN users u ON l.user_id = u.id
                 WHERE l.id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $list_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all lists with pagination
 * 
 * @param int $user_id User ID (null for admin to see all lists)
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Array of lists
 */
function get_all_lists($user_id = null, $limit = null, $offset = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT l.*, u.username as created_by, 
                 (SELECT COUNT(*) FROM contacts WHERE list_id = l.id) as contact_count
                 FROM lists l
                 JOIN users u ON l.user_id = u.id";
        
        if ($user_id !== null) {
            $query .= " WHERE l.user_id = :user_id";
        }
        
        $query .= " ORDER BY l.created_at DESC";
        
        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT :offset, :limit";
        }
        
        $stmt = $db->prepare($query);
        
        if ($user_id !== null) {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        if ($limit !== null && $offset !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get contacts from a list with pagination
 * 
 * @param int $list_id List ID
 * @param int $limit Limit
 * @param int $offset Offset
 * @param string $status Filter by status (pending, valid, invalid, or null for all)
 * @return array Array of contacts
 */
function get_list_contacts($list_id, $limit = null, $offset = null, $status = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM contacts WHERE list_id = :list_id";
        
        if ($status !== null) {
            $query .= " AND email_status = :status";
        }
        
        $query .= " ORDER BY id ASC";
        
        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT :offset, :limit";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        
        if ($status !== null) {
            $stmt->bindParam(':status', $status);
        }
        
        if ($limit !== null && $offset !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Count contacts in a list
 * 
 * @param int $list_id List ID
 * @param string $status Filter by status (pending, valid, invalid, or null for all)
 * @return int
 */
function count_list_contacts($list_id, $status = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT COUNT(*) as total FROM contacts WHERE list_id = :list_id";
        
        if ($status !== null) {
            $query .= " AND email_status = :status";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        
        if ($status !== null) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Update contact email validation status
 * 
 * @param int $list_id List ID
 * @param string $email Email address
 * @param string $status Validation status (valid, invalid)
 * @param string $message Validation message
 * @return boolean Success status
 */
function update_contact_validation_status($list_id, $email, $status, $message = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE contacts SET 
                 email_status = :status, 
                 validation_message = :message,
                 validation_date = NOW()
                 WHERE list_id = :list_id AND email = :email";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->bindParam(':email', $email);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Process validation for all pending contacts in a list
 * 
 * @param int $list_id List ID
 * @return array Response with status and counts
 */
/**
 * Process validation for all pending contacts in a list
 * 
 * Use this updated function in includes/list_management.php
 * @param int $list_id List ID
 * @return array Response with status and counts
 */
function validate_list_emails($list_id) {
    $response = [
        'success' => false,
        'message' => '',
        'valid_count' => 0,
        'invalid_count' => 0,
        'pending_count' => 0
    ];
    
    try {
        // Create logs directory if it doesn't exist
        $logs_dir = __DIR__ . '/../logs';
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0755, true);
        }
        
        // Add debug logging to a file
        $log_file = __DIR__ . '/../logs/validation.log';
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Starting validation for list ID: $list_id\n", FILE_APPEND);
        
        // Get pending contacts
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, email FROM contacts WHERE list_id = :list_id AND email_status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->execute();
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contacts)) {
            $response['message'] = 'No pending contacts to validate';
            $response['success'] = true;
            return $response;
        }
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Found " . count($contacts) . " contacts to validate\n", FILE_APPEND);
        
        // Extract emails for validation
        $emails = [];
        $email_to_id = []; // To map emails back to contact IDs
        
        foreach ($contacts as $contact) {
            $emails[] = $contact['email'];
            $email_to_id[$contact['email']] = $contact['id'];
        }
        
        // Validate emails
        $validation_results = validate_emails_with_postmark($emails);
        
        if (empty($validation_results)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Empty validation results\n", FILE_APPEND);
            $response['message'] = 'Failed to validate emails';
            return $response;
        }
        
        // Log successful validation
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Validation completed with " . count($validation_results) . " results\n", FILE_APPEND);
        
        // Update contact statuses based on validation results
        $valid_count = 0;
        $invalid_count = 0;
        
        // Begin transaction
        $db->beginTransaction();
        
        foreach ($validation_results as $email => $result) {
            if (!isset($email_to_id[$email])) {
                continue; // Skip if we can't map back to a contact
            }
            
            $contact_id = $email_to_id[$email];
            $status = $result['valid'] ? 'valid' : 'invalid';
            $message = $result['message'];
            
            // Update contact status
            $update_query = "UPDATE contacts SET 
                             email_status = :status, 
                             validation_message = :message,
                             validation_date = NOW()
                             WHERE id = :contact_id";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':status', $status);
            $update_stmt->bindParam(':message', $message);
            $update_stmt->bindParam(':contact_id', $contact_id);
            $update_stmt->execute();
            
            if ($status === 'valid') {
                $valid_count++;
            } else {
                $invalid_count++;
            }
        }
        
        // Commit transaction
        $db->commit();
        
        // Set response
        $response['success'] = true;
        $response['valid_count'] = $valid_count;
        $response['invalid_count'] = $invalid_count;
        $response['pending_count'] = count($contacts) - $valid_count - $invalid_count;
        $response['message'] = "Email validation completed: $valid_count valid, $invalid_count invalid";
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $response['message'] . "\n", FILE_APPEND);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Database error: " . $e->getMessage() . "\n", FILE_APPEND);
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - General error: " . $e->getMessage() . "\n", FILE_APPEND);
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Delete a list and all its contacts
 * 
 * @param int $list_id List ID
 * @return boolean Success status
 */
function delete_list($list_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Begin transaction
        $db->beginTransaction();
        
        // Delete all contacts in the list
        $contacts_query = "DELETE FROM contacts WHERE list_id = :list_id";
        $contacts_stmt = $db->prepare($contacts_query);
        $contacts_stmt->bindParam(':list_id', $list_id);
        $contacts_stmt->execute();
        
        // Delete download history
        $history_query = "DELETE FROM download_history WHERE list_id = :list_id";
        $history_stmt = $db->prepare($history_query);
        $history_stmt->bindParam(':list_id', $list_id);
        $history_stmt->execute();
        
        // Delete the list
        $list_query = "DELETE FROM lists WHERE id = :list_id";
        $list_stmt = $db->prepare($list_query);
        $list_stmt->bindParam(':list_id', $list_id);
        $result = $list_stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        return $result;
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return false;
    }
}

/**
 * Parse CSV file and extract contacts - simplified version
 * 
 * @param string $file_path Path to the CSV file
 * @return array Array of contacts or error
 */
function parse_contacts_csv($file_path) {
    $response = [
        'success' => false,
        'message' => '',
        'contacts' => []
    ];
    
    if (!file_exists($file_path)) {
        $response['message'] = 'File not found';
        return $response;
    }
    
    $rows = parse_csv($file_path);
    
    if ($rows === false || count($rows) <= 1) {
        $response['message'] = 'Failed to parse CSV file or file is empty';
        return $response;
    }
    
    // Find any column that might contain emails
    $headers = $rows[0];
    $email_idx = -1;
    
    foreach ($headers as $idx => $header) {
        if (stripos($header, 'email') !== false || stripos($header, 'e-mail') !== false) {
            $email_idx = $idx;
            break;
        }
    }
    
    // If no email column found, just use the last column
    if ($email_idx == -1) {
        $email_idx = count($headers) - 1;
    }
    
    // Extract contacts
    $contacts = [];
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        
        if (count($row) <= $email_idx || empty(trim($row[$email_idx]))) {
            continue;
        }
        
        $contacts[] = [
            'first_name' => $row[0] ?? 'Unknown',
            'last_name' => $row[1] ?? 'Unknown',
            'email' => trim($row[$email_idx])
        ];
    }
    
    $response['success'] = true;
    $response['message'] = count($contacts) . ' contacts found in CSV';
    $response['contacts'] = $contacts;
    
    return $response;
}

/**
 * Record a list download
 * 
 * @param int $list_id List ID
 * @param int $user_id User ID
 * @return boolean Success status
 */
function record_list_download($list_id, $user_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Insert download record
        $history_query = "INSERT INTO download_history (list_id, user_id) VALUES (:list_id, :user_id)";
        $history_stmt = $db->prepare($history_query);
        $history_stmt->bindParam(':list_id', $list_id);
        $history_stmt->bindParam(':user_id', $user_id);
        $history_stmt->execute();
        
        // Update download count
        $update_query = "UPDATE lists SET download_count = download_count + 1 WHERE id = :list_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':list_id', $list_id);
        return $update_stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get download history for a list
 * 
 * @param int $list_id List ID
 * @return array Array of download history
 */
function get_list_download_history($list_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT dh.*, u.username 
                 FROM download_history dh
                 JOIN users u ON dh.user_id = u.id
                 WHERE dh.list_id = :list_id
                 ORDER BY dh.download_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Generate CSV for a list
 * 
 * @param int $list_id List ID
 * @param string $status Filter by status (valid, invalid, or null for all)
 * @return string|false CSV content as string or false on error
 */
function generate_list_csv($list_id, $status = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get list info
        $list = get_list_by_id($list_id);
        if (!$list) {
            return false;
        }
        
        // Get contacts
        $query = "SELECT first_name, last_name, email, email_status, validation_date 
                 FROM contacts WHERE list_id = :list_id";
        
        if ($status !== null) {
            $query .= " AND email_status = :status";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        
        if ($status !== null) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contacts)) {
            return "First Name,Last Name,Email,Status,Validation Date\n";
        }
        
        // Start CSV output
        $output = fopen('php://temp', 'r+');
        
        // Add header row
        fputcsv($output, ['First Name', 'Last Name', 'Email', 'Status', 'Validation Date']);
        
        // Add contacts
        foreach ($contacts as $contact) {
            fputcsv($output, [
                $contact['first_name'],
                $contact['last_name'],
                $contact['email'],
                $contact['email_status'],
                $contact['validation_date']
            ]);
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Count all lists by user
 * 
 * @param int $user_id User ID
 * @return int Count of lists
 */
function count_lists_by_user($user_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT COUNT(*) as total FROM lists WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}


/**
 * Reset validation status for all contacts in a list
 * 
 * Add this function to includes/list_management.php
 * 
 * @param int $list_id List ID
 * @param string $status Optional filter by current status (valid, invalid, or null for all)
 * @return array Response with status and count
 */
function reset_validation_status($list_id, $status = null) {
    $response = [
        'success' => false,
        'message' => '',
        'reset_count' => 0
    ];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Begin transaction
        $db->beginTransaction();
        
        // Build query based on status filter
        $query = "UPDATE contacts SET 
                 email_status = 'pending', 
                 validation_message = NULL,
                 validation_date = NULL
                 WHERE list_id = :list_id";
                 
        if ($status !== null) {
            $query .= " AND email_status = :status";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        
        if ($status !== null) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        $reset_count = $stmt->rowCount();
        
        // Commit transaction
        $db->commit();
        
        $response['success'] = true;
        $response['reset_count'] = $reset_count;
        $response['message'] = "Validation reset for $reset_count contacts. You can now restart validation.";
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    return $response;
}


/**
 * Check delivery status of validation emails and update contact status
 * Enhanced to store the email event (opened, delivered, etc.)
 * 
 * Add/Update this function in includes/list_management.php
 * 
 * @param int $list_id List ID
 * @return array Response with status and counts
 */
function check_validation_email_status($list_id) {
    $response = [
        'success' => false,
        'message' => '',
        'valid_count' => 0,
        'invalid_count' => 0,
        'pending_count' => 0,
        'opened_count' => 0,
        'delivered_count' => 0
    ];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get contacts with message IDs
        $query = "SELECT id, email, validation_message FROM contacts 
                 WHERE list_id = :list_id AND email_status = 'pending' 
                 AND validation_message IS NOT NULL";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->execute();
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contacts)) {
            $response['message'] = 'No contacts with tracking IDs found';
            return $response;
        }
        
        foreach ($contacts as $contact) {
            $message_id = $contact['validation_message'];
            
            // Check email status
            $status_result = check_email_status($message_id);
            
            if ($status_result['success']) {
                $delivery_status = $status_result['status'];
                $event = $status_result['event'];
                
                // Determine validation status based on delivery
                $email_status = 'pending';
                $status_message = json_encode($status_result['details']);
                
                if (in_array($event, ['opened', 'delivered'])) {
                    $email_status = 'valid';
                    $response['valid_count']++;
                    
                    if ($event == 'opened') {
                        $response['opened_count']++;
                    } else if ($event == 'delivered') {
                        $response['delivered_count']++;
                    }
                } elseif (in_array($delivery_status, ['bounced', 'failed'])) {
                    $email_status = 'invalid';
                    $response['invalid_count']++;
                } else {
                    // Still pending for states like 'queued', 'sent', etc.
                    $response['pending_count']++;
                }
                
                // Update contact status with event
                if ($email_status !== 'pending') {
                    // Add the event column to the update
                    $update_query = "UPDATE contacts SET 
                                    email_status = :status,
                                    email_event = :event,
                                    validation_message = :message,
                                    validation_date = NOW()
                                    WHERE id = :contact_id";
                    
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(':status', $email_status);
                    $update_stmt->bindParam(':event', $event);
                    $update_stmt->bindParam(':message', $status_message);
                    $update_stmt->bindParam(':contact_id', $contact['id']);
                    $update_stmt->execute();
                }
            } else {
                $response['pending_count']++;
            }
            
            // Add a small delay to avoid rate limits
            usleep(200000); // 200ms
        }
        
        $response['success'] = true;
        $response['message'] = "Email status check completed: " . 
                              $response['valid_count'] . " valid (" . 
                              $response['opened_count'] . " opened, " . 
                              $response['delivered_count'] . " delivered), " . 
                              $response['invalid_count'] . " invalid, " . 
                              $response['pending_count'] . " pending";
        
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    return $response;
}




/**
 * Count contacts in a list by event type
 * 
 * Add this function to includes/list_management.php
 * 
 * @param int $list_id List ID
 * @param string $event Event type (opened, delivered, sent, bounced)
 * @return int
 */
function count_list_contacts_by_event($list_id, $event) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT COUNT(*) as total FROM contacts 
                 WHERE list_id = :list_id AND email_event = :event";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->bindParam(':event', $event);
        
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Filter contacts by event type
 * 
 * Add this function to includes/list_management.php
 * 
 * @param int $list_id List ID
 * @param string $event Event type (opened, delivered, sent, bounced)
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Array of contacts
 */
function get_list_contacts_by_event($list_id, $event, $limit = null, $offset = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM contacts 
                 WHERE list_id = :list_id AND email_event = :event
                 ORDER BY id ASC";
        
        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT :offset, :limit";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':list_id', $list_id);
        $stmt->bindParam(':event', $event);
        
        if ($limit !== null && $offset !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}