<?php

function env($key, $default = null) {
    if (getenv($key) !== false) {
        return getenv($key);
    }

    $envFile = dirname(__FILE__).'/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            [$name, $value] = array_map('trim', explode('=', $line, 2) + [1 => '']);
            if ($name === $key) {
                return $value;
            }
        }
    }

    return $default;
}

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', 'project_tracker'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('JWT_SECRET', env('JWT_SECRET', 'please-change-this-secret'));
define('JWT_EXPIRE', intval(env('JWT_EXPIRE', 28800)));
define('MAIL_FROM', env('MAIL_FROM', 'no-reply@localhost'));
define('FRONTEND_BASE_URL', env('FRONTEND_BASE_URL', 'http://localhost:5173'));
define('EMAIL_LOG_PATH', env('EMAIL_LOG_PATH', dirname(__DIR__) . '/email.log'));

function base_path($path = '') {
    return dirname(__FILE__) . ($path ? DIRECTORY_SEPARATOR . $path : '');
}
