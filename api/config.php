<?php
// api/config.php

// Start session
// Ensure session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // Set headers for CORS if needed later, but for same-domain sveltekit it is fine.
    // Ideally we return JSON for errors if this file is accessed directly? 
    // It's a config file, so it just defines things.
}

// Admin credentials
define('ADMIN_USER', 'adrian.larek@gritprojects.se');

// Hash for password
define('ADMIN_HASH', '$2a$12$A9ImaKd2ubq9fOh06cepJ.kqPf0qWcyXCD8suKcuZoO20em/17JCK'); 
