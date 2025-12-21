<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON input or POST form data
    // SvelteKit/Fetch might send JSON body. Let's support both or just JSON.
    // Standard fetch with JSON body:
    $input = json_decode(file_get_contents('php://input'), true);
    
    $user = $input['username'] ?? $_POST['username'] ?? '';
    $pass = $input['password'] ?? $_POST['password'] ?? '';

    if ($user === ADMIN_USER && password_verify($pass, ADMIN_HASH)) {
        $_SESSION['logged_in'] = true;
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
