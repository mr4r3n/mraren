<?php
/**
 * Session Management Class
 * 
 * Handles secure session management for the application
 */

class Session {
    private static $instance = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Configure session settings for security
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_trans_sid', 0);
        ini_set('session.cookie_httponly', 1);
        
        // Set session cookie parameters
        session_set_cookie_params(
            SESSION_LIFETIME,
            SESSION_PATH,
            SESSION_DOMAIN,
            SESSION_SECURE,
            SESSION_HTTP_ONLY
        );
        
        // Set session name
        session_name(SESSION_NAME);
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            
            // Regenerate session ID if needed
            if (!isset($_SESSION['created'])) {
                $this->regenerateId(true);
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                // Regenerate session ID every 30 minutes
                $this->regenerateId(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Get Session instance (Singleton pattern)
     * 
     * @return Session
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Regenerate session ID
     * 
     * @param bool $deleteOldSession Whether to delete the old session data
     * @return bool
     */
    public function regenerateId($deleteOldSession = false) {
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Set a session variable
     * 
     * @param string $key Session key
     * @param mixed $value Session value
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get a session variable
     * 
     * @param string $key Session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Session value or default
     */
    public function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    /**
     * Check if a session variable exists
     * 
     * @param string $key Session key
     * @return bool
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove a session variable
     * 
     * @param string $key Session key
     */
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Clear all session data
     */
    public function clear() {
        session_unset();
    }
    
    /**
     * Destroy the session
     */
    public function destroy() {
        // Clear all session data
        $this->clear();
        
        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    /**
     * Set flash message (available only for the next request)
     * 
     * @param string $key Flash key
     * @param mixed $value Flash value
     */
    public function setFlash($key, $value) {
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get flash message and remove it
     * 
     * @param string $key Flash key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Flash value or default
     */
    public function getFlash($key, $default = null) {
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Check if a flash message exists
     * 
     * @param string $key Flash key
     * @return bool
     */
    public function hasFlash($key) {
        return isset($_SESSION['_flash'][$key]);
    }
}
