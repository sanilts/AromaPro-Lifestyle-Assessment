<?php
/**
 * Authentication functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Register a new user
 * 
 * @param string $username Username
 * @param string $email Email
 * @param string $password Password
 * @param string $role User role (admin or user)
 * @return array Response with status and message
 */
function register_user($username, $email, $password, $role = 'user') {
    $response = [
        'success' => false,
        'message' => '',
        'user_id' => null
    ];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $response['message'] = 'All fields are required';
        return $response;
    }
    
    if (!is_valid_email_format($email)) {
        $response['message'] = 'Invalid email format';
        return $response;
    }
    
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters';
        return $response;
    }
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if username or email already exists
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $response['message'] = 'Username or email already exists';
            return $response;
        }
        
        // Insert new user
        $query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':role', $role);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'User registered successfully';
            $response['user_id'] = $db->lastInsertId();
        } else {
            $response['message'] = 'Failed to register user';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Authenticate user
 * 
 * @param string $username Username
 * @param string $password Password
 * @return array Response with status and user data
 */
function login_user($username, $password) {
    $response = [
        'success' => false,
        'message' => '',
        'user' => null
    ];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $response['message'] = 'Username and password are required';
        return $response;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get user by username or email
        $query = "SELECT id, username, email, password, role FROM users WHERE username = :username OR email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                $response['success'] = true;
                $response['message'] = 'Login successful';
                $response['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
            } else {
                $response['message'] = 'Invalid password';
            }
        } else {
            $response['message'] = 'User not found';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Log out the current user
 * 
 * @return void
 */
function logout_user() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Get user by ID
 * 
 * @param int $user_id User ID
 * @return array|false User data or false if not found
 */
function get_user_by_id($user_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, email, role, created_at, updated_at FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
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
 * Get all users
 * 
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Array of users
 */
function get_all_users($limit = null, $offset = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, email, role, created_at, updated_at FROM users";
        
        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT :offset, :limit";
        }
        
        $stmt = $db->prepare($query);
        
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
 * Update user information
 * 
 * @param int $user_id User ID
 * @param array $data Data to update
 * @return boolean Success status
 */
function update_user($user_id, $data) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $fields = [];
        $values = [':id' => $user_id];
        
        // Build update fields
        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email', 'role'])) {
                $fields[] = "{$key} = :{$key}";
                $values[":{$key}"] = $value;
            }
        }
        
        // If no valid fields to update
        if (empty($fields)) {
            return false;
        }
        
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($query);
        
        foreach ($values as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Change user password
 * 
 * @param int $user_id User ID
 * @param string $current_password Current password
 * @param string $new_password New password
 * @return array Response with status and message
 */
function change_password($user_id, $current_password, $new_password) {
    $response = [
        'success' => false,
        'message' => ''
    ];
    
    if (empty($current_password) || empty($new_password)) {
        $response['message'] = 'Both current and new passwords are required';
        return $response;
    }
    
    if (strlen($new_password) < 8) {
        $response['message'] = 'New password must be at least 8 characters';
        return $response;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get current password hash
        $query = "SELECT password FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() != 1) {
            $response['message'] = 'User not found';
            return $response;
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $response['message'] = 'Current password is incorrect';
            return $response;
        }
        
        // Hash new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $update_query = "UPDATE users SET password = :password WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':password', $password_hash);
        $update_stmt->bindParam(':id', $user_id);
        
        if ($update_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Password changed successfully';
        } else {
            $response['message'] = 'Failed to change password';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Delete user
 * 
 * @param int $user_id User ID
 * @return boolean Success status
 */
function delete_user($user_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Count total users
 * 
 * @return int
 */
function count_users() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT COUNT(*) as total FROM users";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}