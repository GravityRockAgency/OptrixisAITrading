<?php
/**
 * Faiza Kids Concierge – Configuration
 * Copy to config.php and fill in your values.
 * NEVER commit config.php to version control.
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'faiza_kids');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL without trailing slash
// Example: 'https://baby-sitting.faizamultiservice.com'
define('BASE_URL', 'http://localhost/faiza-kids');

// Secret key for tokens/CSRF — change to a long random string (32+ chars)
define('APP_SECRET', 'change-this-to-a-long-random-secret-key-minimum-32-chars');

// 'production' or 'development'
define('APP_ENV', 'production');

define('APP_VERSION', '2.0.0');

// Max upload size in bytes (default: 8 MB)
define('UPLOAD_MAX_SIZE', 8388608);

// Absolute path to uploads directory (must be writable)
define('UPLOAD_PATH', __DIR__ . '/uploads');

// Public URL to uploads directory
define('UPLOAD_URL', BASE_URL . '/uploads');
