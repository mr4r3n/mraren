<?php
/**
 * Authentication Class
 * 
 * Handles user authentication and authorization
 */

class Auth {
    private static $instance = null;
    private $session;
    private $db;
    private $user = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->session = Session::getInstance();
        $this->db = MongoDB_Connection::getInstance();
        
        // Load user from session if authenticated
        if ($this->session->has('user_id')) {
            $this->loadUser($this->session->get('user_id'));
        }
    }
    
    /**
     * Get Auth instance (Singleton pattern)
     * 
     * @return Auth
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Load user from database
     * 
     * @param string $id User ID
     */
    private function loadUser($id) {
        try {
            $user = $this->db->findById('users', $id);
            
            if ($user) {
                // Remove sensitive information
                unset($user->password);
                $this->user = $user;
            }
        } catch (Exception $e) {
            // Log error
            error_log('Failed to load user: ' . $e->getMessage());
        }
    }
    
    /**
     * Attempt to authenticate a user with username/email and password
     * 
     * @param string $username Username or email
     * @param string $password Password
     * @param bool $remember Whether to remember the user
     * @return bool True if authentication successful, false otherwise
     */
    public function attempt($username, $password, $remember = false) {
        try {
            // Check if username is an email
            $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
            
            // Query for user
            $filter = $isEmail 
                ? ['email' => $username] 
                : ['username' => $username];
            
            $result = $this->db->find('users', $filter);
            
            foreach ($result as $user) {
                // Verify password
                if (password_verify($password, $user->password)) {
                    // Update last login
                    $update = [
                        '$set' => [
                            'last_login' => new MongoDB\BSON\UTCDateTime()
                        ]
                    ];
                    
                    $this->db->update('users', ['_id' => $user->_id], $update);
                    
                    // Store user ID in session
                    $this->session->set('user_id', (string)$user->_id);
                    
                    // Set authentication timestamp
                    $this->session->set('auth_time', time());
                    
                    // Load user data
                    $this->loadUser((string)$user->_id);
                    
                    // Create remember token if requested
                    if ($remember) {
                        $this->createRememberToken($user);
                    }
                    
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            // Log error
            error_log('Authentication attempt failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a remember token for the user
     * 
     * @param object $user User object
     */
    private function createRememberToken($user) {
        // Generate a random token
        $token = bin2hex(random_bytes(32));
        
        // Create a hash of the token to store in the database
        $tokenHash = hash('sha256', $token);
        
        // Set expiration date (30 days)
        $expires = new MongoDB\BSON\UTCDateTime((time() + 30 * 24 * 60 * 60) * 1000);
        
        // Store token in database
        $document = [
            'user_id' => $user->_id,
            'token' => $tokenHash,
            'expires' => $expires,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $this->db->insert('remember_tokens', $document);
        
        // Set cookie
        $cookie_params = session_get_cookie_params();
        setcookie(
            'remember_token',
            $token,
            time() + 30 * 24 * 60 * 60,
            $cookie_params['path'],
            $cookie_params['domain'],
            $cookie_params['secure'],
            true // httponly
        );
    }
    
    /**
     * Check if the user is logged in
     * 
     * @return bool
     */
    public function check() {
        return $this->user !== null;
    }
    
    /**
     * Get the currently authenticated user
     * 
     * @return object|null
     */
    public function user() {
        return $this->user;
    }
    
    /**
     * Get the user ID
     * 
     * @return string|null
     */
    public function id() {
        return $this->user ? (string)$this->user->_id : null;
    }
    
    /**
     * Logout the current user
     */
    public function logout() {
        // Remove remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            $tokenHash = hash('sha256', $_COOKIE['remember_token']);
            $this->db->delete('remember_tokens', ['token' => $tokenHash]);
            
            // Delete the cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Clear user data
        $this->user = null;
        
        // Clear session
        $this->session->remove('user_id');
        $this->session->remove('auth_time');
    }
    
    /**
     * Check if the user has a specific role
     * 
     * @param string|array $roles Role(s) to check
     * @return bool
     */
    public function hasRole($roles) {
        if (!$this->check()) {
            return false;
        }
        
        // Convert single role to array
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return isset($this->user->role) && in_array($this->user->role, $roles);
    }
    
    /**
     * Check if the user has a specific permission
     * 
     * @param string|array $permissions Permission(s) to check
     * @return bool
     */
    public function hasPermission($permissions) {
        if (!$this->check()) {
            return false;
        }
        
        // Convert single permission to array
        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }
        
        // Check user permissions
        if (isset($this->user->permissions) && is_array($this->user->permissions)) {
            foreach ($permissions as $permission) {
                if (in_array($permission, $this->user->permissions)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if a user can access a resource
     * 
     * @param string $resource Resource identifier
     * @param string $action Action to perform (view, edit, delete, etc.)
     * @return bool
     */
    public function can($action, $resource) {
        // Admin can do anything
        if ($this->hasRole(ROLE_ADMIN)) {
            return true;
        }
        
        // Check specific permissions
        return $this->hasPermission($action . '_' . $resource);
    }
    
    /**
     * Register a new user
     * 
     * @param array $data User data
     * @return string|false User ID on success, false on failure
     */
    public function register($data) {
        try {
            // Check if username or email already exists
            $existingUser = $this->db->find('users', [
                '$or' => [
                    ['username' => $data['username']],
                    ['email' => $data['email']]
                ]
            ]);
            
            foreach ($existingUser as $user) {
                // User already exists
                return false;
            }
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Add additional fields
            $data['role'] = $data['role'] ?? ROLE_USER;
            $data['created_at'] = new MongoDB\BSON\UTCDateTime();
            $data['updated_at'] = new MongoDB\BSON\UTCDateTime();
            $data['active'] = true;
            
            // Insert new user
            $result = $this->db->insert('users', $data);
            
            // Return user ID
            return (string)$data['_id'];
        } catch (Exception $e) {
            // Log error
            error_log('User registration failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if username exists
     * 
     * @param string $username Username to check
     * @return bool
     */
    public function usernameExists($username) {
        $result = $this->db->find('users', ['username' => $username]);
        
        foreach ($result as $user) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if email exists
     * 
     * @param string $email Email to check
     * @return bool
     */
    public function emailExists($email) {
        $result = $this->db->find('users', ['email' => $email]);
        
        foreach ($result as $user) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate a JWT token for API authentication
     * 
     * @param array $payload Token payload
     * @param int $expiry Token expiry in seconds
     * @return string JWT token
     */
    public function generateJWT($payload, $expiry = 3600) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;
        
        $headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $payloadEncoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", AUTH_SECRET_KEY, true);
        $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Verify and decode a JWT token
     * 
     * @param string $token JWT token
     * @return array|false Decoded payload or false if invalid
     */
    public function verifyJWT($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", AUTH_SECRET_KEY, true);
        $expectedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        
        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return false;
        }
        
        // Decode payload
        $payload = json_decode(base64_decode(strtr($payloadEncoded, '-_', '+/')), true);
        
        // Check expiration
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
}