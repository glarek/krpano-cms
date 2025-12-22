<?php
// api/config.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/data_helper.php';

/**
 * Load Mutable Admin Credentials
 */
$credsFile = getConfigPath() . '/admin_creds.php';
$username = 'admin';
$hash = '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa'; // default: password

if (file_exists($credsFile)) {
    $creds = include $credsFile;
    if (is_array($creds)) {
        $username = $creds['username'] ?? $username;
        $hash = $creds['hash'] ?? $hash;
    }
}

// Admin credentials
define('ADMIN_USER', $username);
define('ADMIN_HASH', $hash);
