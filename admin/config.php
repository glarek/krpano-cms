<?php
// admin/config.php

// Start session
// Ensure session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin credentials
define('ADMIN_USER', 'adrian.larek@gritprojects.se');

// Hash for password
define('ADMIN_HASH', '$2a$12$A9ImaKd2ubq9fOh06cepJ.kqPf0qWcyXCD8suKcuZoO20em/17JCK'); 
