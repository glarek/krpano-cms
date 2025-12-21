<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo json_encode(['authenticated' => true, 'user' => ADMIN_USER]);
} else {
    // Not 401, just false, as we often want to check status without erroring
    echo json_encode(['authenticated' => false]);
}
