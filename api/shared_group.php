<?php
// api/shared_group.php
header('Content-Type: application/json');

$token = $_GET['token'] ?? '';
$groupName = $_GET['group'] ?? $_GET['id'] ?? '';

// If only token provided, try to find group by token (legacy support)
// If group provided, check valid auth

// Load Auth Data
$authFile = __DIR__ . '/project_auth_data.php';
$authData = [];
if (file_exists($authFile)) {
    $authData = require $authFile;
}

if (empty($groupName) && !empty($token)) {
    // Reverse lookup by token
    foreach ($authData as $g => $data) {
        if (isset($data['token']) && $data['token'] === $token) {
            $groupName = $g;
            break;
        }
    }
}

if (empty($groupName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ingen grupp eller giltig token angiven.']);
    exit;
}

// Check if group is protected
$isProtected = isset($authData[$groupName]);

if ($isProtected) {
    // Verify token
    $validToken = $authData[$groupName]['token'] ?? '';
    if ($token !== $validToken) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Behörighet saknas (ogiltig token).']);
        exit;
    }
}

// If not protected, or token matched above, proceed to list projects.

// Scan projects for this group
$projectDir = __DIR__ . '/../projekt/';
// Always encode group name to match file system (created via web interface)
$encodedName = rawurlencode($groupName);
$groupPath = $projectDir . $encodedName;

if (is_dir($groupPath)) {
    $items = scandir($groupPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir($groupPath . '/' . $item)) {
            $projects[] = $item;
        }
    }
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Gruppen hittades inte.']);
    exit;
}

echo json_encode([
    'success' => true,
    'group_name' => $groupName,
    'group_id' => $encodedName, // The encoded FS name for URL generation
    'projects' => $projects,
    'is_protected' => $isProtected
]);
