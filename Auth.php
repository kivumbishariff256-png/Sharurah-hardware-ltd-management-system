<?php
/**
 * Authentication Class for Sharurah Hardware Ltd Management System
 * File: classes/Auth.php
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Login user
    public function login($username, $password) {
        $query = "SELECT user_id, username, password_hash, email, full_name, phone_number, role, is_active 
                  FROM users 
                  WHERE username = :username OR email = :username";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is inactive'];
            }
            
            // Update last login
            $this->updateLastLogin($user['user_id']);
            
            // Create session
            $this->createSession($user['user_id']);
            
            // Log activity
            $this->logActivity($user['user_id'], 'login', 'authentication', 'User logged in successfully');
            
            return [
                'success' => true,
                'user' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'phone_number' => $user['phone_number'],
                    'role' => $user['role']
                ]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    // Logout user
    public function logout($user_id) {
        $query = "UPDATE user_sessions 
                  SET logout_time = NOW(), is_active = FALSE 
                  WHERE user_id = :user_id AND is_active = TRUE";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        if ($stmt->execute()) {
            $this->logActivity($user_id, 'logout', 'authentication', 'User logged out');
            return ['success' => true, 'message' => 'Logged out successfully'];
        }
        
        return ['success' => false, 'message' => 'Logout failed'];
    }
    
    // Create user session
    private function createSession($user_id) {
        $session_token = bin2hex(random_bytes(32));
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $query = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) 
                  VALUES (:user_id, :session_token, :ip_address, :user_agent)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":session_token", $session_token);
        $stmt->bindParam(":ip_address", $ip_address);
        $stmt->bindParam(":user_agent", $user_agent);
        
        $stmt->execute();
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['session_token'] = $session_token;
    }
    
    // Verify session
    public function verifySession($user_id, $session_token) {
        $query = "SELECT * FROM user_sessions 
                  WHERE user_id = :user_id AND session_token = :session_token AND is_active = TRUE";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":session_token", $session_token);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    // Update last login
    private function updateLastLogin($user_id) {
        $query = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
    }
    
    // Log activity
    private function logActivity($user_id, $action, $module, $description) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $query = "INSERT INTO activity_logs (user_id, action, module, description, ip_address, user_agent) 
                  VALUES (:user_id, :action, :module, :description, :ip_address, :user_agent)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":module", $module);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":ip_address", $ip_address);
        $stmt->bindParam(":user_agent", $user_agent);
        
        $stmt->execute();
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
    }
    
    // Get current user
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $query = "SELECT user_id, username, email, full_name, phone_number, role 
                  FROM users 
                  WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Check user permission
    public function hasPermission($required_role) {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        $roles = ['admin', 'manager', 'cashier', 'accountant', 'inventory_manager'];
        $user_role_index = array_search($user['role'], $roles);
        $required_role_index = array_search($required_role, $roles);
        
        return $user_role_index <= $required_role_index;
    }
}
?>