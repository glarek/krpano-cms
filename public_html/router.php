<?php
// public_html/router.php
if (php_sapi_name() !== 'cli-server') {
    die('This script is only for the PHP built-in web server.');
}

// Emulate Apache's mod_rewrite for local development
$root = __DIR__; // This is now public_html
// This is required because the server was started with 'router.php' as argument.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Simulate .htaccess rewrite rule: ^projekt/(.*)$ ../api/auth_proxy.php?file=$1
if (strpos($uri, '/projekt/') === 0) {
    // Extract the file path relative to /projekt/
    $file = substr($uri, strlen('/projekt/'));
    
    // Set GET parameter expected by auth_proxy
    $_GET['file'] = $file;
    
    // Include the auth proxy
    require __DIR__ . '/api/auth_proxy.php';
    exit;
}

// Default behavior: serve file as is
return false;
