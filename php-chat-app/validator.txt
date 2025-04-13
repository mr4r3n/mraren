<?php
/**
 * Validator Class
 * 
 * Validates input data with customizable rules
 */

class Validator {
    private $data;
    private $errors = [];
    private $rules = [];
    
    /**
     * Constructor
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     */
    public function __construct($data = [], $rules = []) {
        $this->data = $data;
        $this->rules = $rules;
    }
    
    /**
     * Set data to validate
     * 
     * @param array $data Data to validate
     * @return Validator
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }
    
    /**
     * Set validation rules
     * 
     * @param array $rules Validation rules
     * @return Validator
     */
    public function setRules($rules) {
        $this->rules = $rules;
        return $this;
    }
    
    /**
     * Clear errors
     * 
     * @return Validator
     */
    public function clearErrors() {
        $this->errors = [];
        return $this;
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if validation failed
     * 
     * @return bool
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Check if validation passed
     * 
     * @return bool
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Validate data against rules
     * 
     * @return bool True if validation passed
     */
    public function validate() {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            // Split rules by pipe character
            $rulesList = explode('|', $rules);
            
            foreach ($rulesList as $rule) {
                // Split rule name and parameters
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParams = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : [];
                
                // Check if field exists in data
                if (!isset($this->data[$field]) && $ruleName !== 'required') {
                    continue;
                }
                
                // Get field value
                $value = isset($this->data[$field]) ? $this->data[$field] : null;
                
                // Check if validation method exists
                $method = 'validate' . ucfirst($ruleName);
                if (method_exists($this, $method)) {
                    $valid = $this->$method($field, $value, $ruleParams);
                    
                    if (!$valid) {
                        // Field validation failed
                        $this->addError($field, $ruleName, $ruleParams);
                    }
                }
            }
        }
        
        return $this->passes();
    }
    
    /**
     * Add validation error
     * 
     * @param string $field Field name
     * @param string $rule Rule name
     * @param array $params Rule parameters
     */
    private function addError($field, $rule, $params = []) {
        $message = $this->getErrorMessage($field, $rule, $params);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get error message for rule
     * 
     * @param string $field Field name
     * @param string $rule Rule name
     * @param array $params Rule parameters
     * @return string Error message
     */
    private function getErrorMessage($field, $rule, $params) {
        $fieldName = ucfirst(str_replace('_', ' ', $field));
        
        switch ($rule) {
            case 'required':
                return "{$fieldName} is required.";
                
            case 'email':
                return "{$fieldName} must be a valid email address.";
                
            case 'min':
                return "{$fieldName} must be at least {$params[0]} characters.";
                
            case 'max':
                return "{$fieldName} must not exceed {$params[0]} characters.";
                
            case 'between':
                return "{$fieldName} must be between {$params[0]} and {$params[1]} characters.";
                
            case 'numeric':
                return "{$fieldName} must be a number.";
                
            case 'integer':
                return "{$fieldName} must be an integer.";
                
            case 'alpha':
                return "{$fieldName} must contain only letters.";
                
            case 'alphanumeric':
                return "{$fieldName} must contain only letters and numbers.";
                
            case 'url':
                return "{$fieldName} must be a valid URL.";
                
            case 'ip':
                return "{$fieldName} must be a valid IP address.";
                
            case 'date':
                return "{$fieldName} must be a valid date.";
                
            case 'confirmed':
                return "{$fieldName} confirmation does not match.";
                
            case 'regex':
                return "{$fieldName} format is invalid.";
                
            case 'in':
                return "{$fieldName} must be one of: " . implode(', ', $params) . ".";
                
            case 'not_in':
                return "{$fieldName} must not be one of: " . implode(', ', $params) . ".";
                
            case 'unique':
                return "{$fieldName} already exists.";
                
            default:
                return "{$fieldName} is invalid.";
        }
    }
    
    /**
     * Validate required field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateRequired($field, $value, $params) {
        if (is_null($value)) {
            return false;
        } else if (is_string($value) && trim($value) === '') {
            return false;
        } else if (is_array($value) && count($value) < 1) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate email
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateEmail($field, $value, $params) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateMin($field, $value, $params) {
        $min = (int)$params[0];
        
        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        } else if (is_numeric($value)) {
            return $value >= $min;
        } else if (is_array($value)) {
            return count($value) >= $min;
        }
        
        return false;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateMax($field, $value, $params) {
        $max = (int)$params[0];
        
        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        } else if (is_numeric($value)) {
            return $value <= $max;
        } else if (is_array($value)) {
            return count($value) <= $max;
        }
        
        return false;
    }
    
    /**
     * Validate between
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateBetween($field, $value, $params) {
        $min = (int)$params[0];
        $max = (int)$params[1];
        
        return $this->validateMin($field, $value, [$min]) && $this->validateMax($field, $value, [$max]);
    }
    
    /**
     * Validate numeric
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateNumeric($field, $value, $params) {
        return is_numeric($value);
    }
    
    /**
     * Validate integer
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateInteger($field, $value, $params) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Validate alpha
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateAlpha($field, $value, $params) {
        return ctype_alpha($value);
    }
    
    /**
     * Validate alphanumeric
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateAlphanumeric($field, $value, $params) {
        return ctype_alnum($value);
    }
    
    /**
     * Validate URL
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateUrl($field, $value, $params) {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate IP address
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateIp($field, $value, $params) {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Validate date
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateDate($field, $value, $params) {
        $format = isset($params[0]) ? $params[0] : 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }
    
    /**
     * Validate confirmed
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @return bool
     */
    private function validateConfirmed($field, $value, $params) {
        $confirmField = $field . '_confirmation';
        return isset($this->data[$confirmField]) && $value === $this->data[$confirmField];
    }
    
    /**
     * Validate regex
     * 
     * @param string $field Field name
     * @