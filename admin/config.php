<?php
// admin/config.php

// Start session
// Ensure session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin credentials
define('ADMIN_USER', 'admin');

// Hash for password 'password' (using BCRYPT)
// Generated using `password_hash('password', PASSWORD_BCRYPT)`
define('ADMIN_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); 
