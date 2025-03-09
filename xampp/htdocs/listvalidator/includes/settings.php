<?php
/**
 * Settings management functions
 */

// Include the Database class
require_once __DIR__ . '/../config/database.php';

/**
 * Get a setting value
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function get_setting($key, $default = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT setting_value, is_encrypted FROM settings WHERE setting_key = :key";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $value = $row['setting_value'];
            
            // Decrypt if needed
            if ($row['is_encrypted'] && !empty($value)) {
                $value = decrypt_setting($value);
            }
            
            return $value;
        }
        
        return $default;
    } catch (Exception $e) {
        error_log('Error getting setting: ' . $e->getMessage());
        return $default;
    }
}

/**
 * Update a setting
 * 
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $group Setting group
 * @param bool $encrypt Whether to encrypt the value
 * @return bool Success
 */
function update_setting($key, $value, $group = 'general', $encrypt = false) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Encrypt value if needed
        $stored_value = $value;
        if ($encrypt && !empty($value)) {
            $stored_value = encrypt_setting($value);
        }
        
        // Check if setting exists
        $check_query = "SELECT id FROM settings WHERE setting_key = :key";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':key', $key);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing setting
            $query = "UPDATE settings SET setting_value = :value, setting_group = :group, is_encrypted = :encrypt 
                     WHERE setting_key = :key";
        } else {
            // Insert new setting
            $query = "INSERT INTO settings (setting_key, setting_value, setting_group, is_encrypted) 
                     VALUES (:key, :value, :group, :encrypt)";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':key', $key);
        $stmt->bindParam(':value', $stored_value);
        $stmt->bindParam(':group', $group);
        $encrypt_int = $encrypt ? 1 : 0;
        $stmt->bindParam(':encrypt', $encrypt_int);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log('Error updating setting: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a setting
 * 
 * @param string $key Setting key
 * @return bool Success
 */
function delete_setting($key) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "DELETE FROM settings WHERE setting_key = :key";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':key', $key);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log('Error deleting setting: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get all settings in a group
 * 
 * @param string $group Setting group
 * @return array Settings
 */
function get_settings_by_group($group) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT setting_key, setting_value, is_encrypted FROM settings WHERE setting_group = :group";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':group', $group);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $value = $row['setting_value'];
            
            // Decrypt if needed
            if ($row['is_encrypted'] && !empty($value)) {
                $value = decrypt_setting($value);
            }
            
            $settings[$row['setting_key']] = $value;
        }
        
        return $settings;
    } catch (Exception $e) {
        error_log('Error getting settings by group: ' . $e->getMessage());
        return [];
    }
}

/**
 * Basic encryption function for settings
 * 
 * @param string $value Value to encrypt
 * @return string Encrypted value
 */
function encrypt_setting($value) {
    // Generate a secure encryption key derived from the CSRF token secret
    $key = hash('sha256', CSRF_TOKEN_SECRET, true);
    
    // Create an initialization vector
    $iv_size = openssl_cipher_iv_length('AES-256-CBC');
    $iv = openssl_random_pseudo_bytes($iv_size);
    
    // Encrypt the data
    $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
    
    // Combine the IV and encrypted data
    $result = base64_encode($iv . $encrypted);
    
    return $result;
}

/**
 * Basic decryption function for settings
 * 
 * @param string $encrypted_value Encrypted value
 * @return string Decrypted value
 */
function decrypt_setting($encrypted_value) {
    // Generate the same key used for encryption
    $key = hash('sha256', CSRF_TOKEN_SECRET, true);
    
    // Get the IV size
    $iv_size = openssl_cipher_iv_length('AES-256-CBC');
    
    // Decode the combined string
    $decoded = base64_decode($encrypted_value);
    
    // Extract the IV and encrypted data
    $iv = substr($decoded, 0, $iv_size);
    $encrypted_data = substr($decoded, $iv_size);
    
    // Decrypt the data
    $decrypted = openssl_decrypt($encrypted_data, 'AES-256-CBC', $key, 0, $iv);
    
    return $decrypted;
}

/**
 * Verify Postmark API key
 * 
 * @param string $api_key API key to verify
 * @return array Response with status and message
 */
function verify_postmark_api_key($api_key) {
    $result = [
        'success' => false,
        'message' => ''
    ];
    
    // Test API endpoint
    $api_url = 'https://api.postmarkapp.com/server';
    
    $ch = curl_init($api_url);
    
    $headers = [
        'Accept: application/json',
        'X-Postmark-Server-Token: ' . $api_key
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($http_code === 200) {
        $result['success'] = true;
        $result['message'] = 'API key is valid';
        
        // Parse server information
        $server_info = json_decode($response, true);
        if ($server_info && isset($server_info['Name'])) {
            $result['server_name'] = $server_info['Name'];
        }
    } else {
        $result['message'] = 'Invalid API key or connection error';
    }
    
    return $result;
}