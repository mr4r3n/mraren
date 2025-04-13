<?php
/**
 * Security Class
 * 
 * Handles various security features like CSRF protection, XSS prevention, etc.
 */

class Security {
    private static $instance = null;
    private $session;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->session = Session::getInstance();
    }
    
    /**
     * Get Security instance (Singleton pattern)
     * 
     * @return Security
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf_token', $token);
        return $token;
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Provided token
     * @return bool True if token is valid
     */
    public function verifyCSRFToken($token) {
        $storedToken = $this->session->get('csrf_token');
        
        if (!$storedToken) {
            return false;
        }
        
        // Use timing-safe comparison
        return hash_equals($storedToken, $token);
    }
    
    /**
     * Generate CSRF form field
     * 
     * @return string HTML input field with CSRF token
     */
    public function csrfField() {
        $token = $this->generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
    
    /**
     * Sanitize input to prevent XSS
     * 
     * @param string $input Input to sanitize
     * @return string Sanitized input
     */
    public function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->sanitize($value);
            }
            
            return $input;
        }
        
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Escape HTML entities
     * 
     * @param string $input Input to escape
     * @return string Escaped input
     */
    public function escape($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->escape($value);
            }
            
            return $input;
        }
        
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Prevent cross-site scripting in output
     * 
     * @param string $output Output to sanitize
     * @return string Sanitized output
     */
    public function xssClean($output) {
        // Remove null bytes
        $output = str_replace("\0", '', $output);
        
        // Remove JavaScript event handlers
        $output = preg_replace('#<([a-z][a-z0-9]*)\s*[^>]*on[a-z][a-z0-9]*\s*=\s*["\'][^"\']*["\'][^>]*>#i', '<$1>', $output);
        
        // Remove JavaScript in href or src
        $output = preg_replace('#<([a-z][a-z0-9]*)\s*[^>]*href\s*=\s*["\']javascript:[^"\']*["\'][^>]*>#i', '<$1>', $output);
        
        // Remove inline JavaScript
        $output = preg_replace('#<script[^>]*>.*?</script>#is', '', $output);
        
        // Remove inline CSS
        $output = preg_replace('#<style[^>]*>.*?</style>#is', '', $output);
        
        // Remove meta refresh
        $output = preg_replace('#<meta[^>]*refresh[^>]*>#i', '', $output);
        
        // Remove iframes
        $output = preg_replace('#<iframe[^>]*>.*?</iframe>#is', '', $output);
        
        // Remove objects
        $output = preg_replace('#<object[^>]*>.*?</object>#is', '', $output);
        
        // Remove embed tags
        $output = preg_replace('#<embed[^>]*>.*?</embed>#is', '', $output);
        
        return $output;
    }
    
    /**
     * Validate input
     * 
     * @param string $input Input to validate
     * @param string $type Validation type (email, url, ip, etc.)
     * @return bool True if input is valid
     */
    public function validate($input, $type) {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
                
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) !== false;
                
            case 'ip':
                return filter_var($input, FILTER_VALIDATE_IP) !== false;
                
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT) !== false;
                
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT) !== false;
                
            case 'bool':
                return filter_var($input, FILTER_VALIDATE_BOOLEAN) !== false;
                
            case 'alpha':
                return ctype_alpha($input);
                
            case 'alphanumeric':
                return ctype_alnum($input);
                
            case 'numeric':
                return ctype_digit($input);
                
            default:
                return false;
        }
    }
    
    /**
     * Generate a random token
     * 
     * @param int $length Token length
     * @return string Random token
     */
    public function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Check password strength
     * 
     * @param string $password Password to check
     * @return array Array with status and message
     */
    public function checkPasswordStrength($password) {
        $length = strlen($password);
        
        // Check password length
        if ($length < 8) {
            return [
                'status' => 'weak',
                'message' => 'Password is too short. It should be at least 8 characters.'
            ];
        }
        
        // Check for mixed case
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
            return [
                'status' => 'medium',
                'message' => 'Password should include both uppercase and lowercase letters.'
            ];
        }
        
        // Check for numbers
        if (!preg_match('/[0-9]/', $password)) {
            return [
                'status' => 'medium',
                'message' => 'Password should include at least one number.'
            ];
        }
        
        // Check for special characters
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return [
                'status' => 'medium',
                'message' => 'Password should include at least one special character.'
            ];
        }
        
        return [
            'status' => 'strong',
            'message' => 'Password is strong.'
        ];
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    public function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if request is over HTTPS
     * 
     * @return bool
     */
    public function isHttps() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    public function getClientIp() {
        $ipAddress = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP address
        if (filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return $ipAddress;
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Create a rate limiter for a specific action
     * 
     * @param string $key Action key
     * @param int $maxAttempts Maximum number of attempts
     * @param int $decayMinutes Decay time in minutes
     * @return bool True if not rate limited
     */
    public function rateLimiter($key, $maxAttempts = 5, $decayMinutes = 1) {
        $ip = $this->getClientIp();
        $limiterKey = "rate_limiter:{$key}:{$ip}";
        
        // Get current attempts
        $attempts = $this->session->get($limiterKey, ['attempts' => 0, 'last_attempt' => 0]);
        
        // Check if we need to reset the counter
        if (time() - $attempts['last_attempt'] > $decayMinutes * 60) {
            $attempts = ['attempts' => 0, 'last_attempt' => time()];
        }
        
        // Increment attempts
        $attempts['attempts']++;
        $attempts['last_attempt'] = time();
        
        // Save to session
        $this->session->set($limiterKey, $attempts);
        
        // Check if rate limited
        return $attempts['attempts'] <= $maxAttempts;
    }
}
