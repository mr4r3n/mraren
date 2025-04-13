<?php
/**
 * Application Configuration
 * 
 * Main configuration settings for the PHP Chat Application
 */

// Error reporting settings
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../utils/logs/error.log');

// Application paths
define('ROOT_PATH', realpath(dirname(__FILE__) . '/..'));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('MODELS_PATH', ROOT_PATH . '/models');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UTILS_PATH', ROOT_PATH . '/utils');
define('WEBSOCKET_PATH', ROOT_PATH . '/websocket');

// Application settings
define('APP_NAME', 'PHP Chat App');
define('APP_URL', 'http://localhost/php-chat-app');
define('APP_VERSION', '1.0.0');

// Session settings
define('SESSION_NAME', 'php_chat_session');
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false);
define('SESSION_HTTP_ONLY', true);

// Authentication settings
define('AUTH_TOKEN_EXPIRY', 3600); // 1 hour
define('AUTH_REFRESH_TOKEN_EXPIRY', 604800); // 1 week
define('AUTH_SECRET_KEY', 'change_this_to_a_secure_random_string');

// WebSocket settings
define('WEBSOCKET_HOST', '127.0.0.1');
define('WEBSOCKET_PORT', 8080);
define('WEBSOCKET_PATH', '/chat');

// File upload settings
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_MODERATOR', 'moderator');
define('ROLE_USER', 'user');
define('ROLE_GUEST', 'guest');

// Load other config files
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/constants.php';

// Load utility functions
require_once UTILS_PATH . '/helpers.php';
require_once UTILS_PATH . '/sanitizer.php';
require_once UTILS_PATH . '/logger.php';

// Load core classes
require_once CORE_PATH . '/Router.php';
require_once CORE_PATH . '/Session.php';
require_once CORE_PATH . '/Auth.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Validator.php';
require_once CORE_PATH . '/Security.php';
