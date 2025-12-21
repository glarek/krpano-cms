<?php
// api/update_profile.php
require_once 'config.php';

// Set JSON header immediately
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$newUser = trim($data['username'] ?? '');
$newPass = trim($data['password'] ?? '');

// Validation
if (empty($newUser)) {
    http_response_code(400);
    echo json_encode(['error' => 'Användarnamn krävs']);
    exit;
}

// Read current creds (or defaults) to initialize array
$credsFile = __DIR__ . '/admin_creds.php';
$currentCreds = [
    'username' => 'admin',
    'hash' => '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa' 
];

if (file_exists($credsFile)) {
    $loaded = include $credsFile;
    if (is_array($loaded)) {
        $currentCreds = array_merge($currentCreds, $loaded);
    }
}

// Update Username
$currentCreds['username'] = $newUser;

// Update Password if provided
if (!empty($newPass)) {
    $currentCreds['hash'] = password_hash($newPass, PASSWORD_BCRYPT);
}

// Write to file
try {
    $export = var_export($currentCreds, true);
    $content = "<?php\nreturn " . $export . ";\n";
    
    if (file_put_contents($credsFile, $content) === false) {
        throw new Exception("Failed to write to admin_creds.php");
    }
    
    // Success
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: Could not save credentials']);
    error_log("Update Profile Error: " . $e->getMessage());
}
