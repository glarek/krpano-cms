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
define('ADMIN_HASH', '$2y$12$tQkB43lEiNrDg5a8cjH9s.gQ8OJLbxygNNLDgedpajk2vz3bDWy/.'); 
