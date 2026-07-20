<?php
/**
 * User Authentication Class
 */

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Register a new user
     */
    public function register($username, $email, $password, $fullName) {
        // Check if user exists
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$username, $email, $passwordHash, $fullName]);
            
            return ['success' => true, 'message' => 'User registered successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        $stmt = $this->pdo->prepare('SELECT id, username, email, full_name, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['last_activity'] = time();
        
        return ['success' => true, 'message' => 'Login successful'];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return ['success' => true];
    }
    
    /**
     * Get user info
     */
    public function getUserInfo($userId) {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, email, full_name, created_at FROM users WHERE id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}
