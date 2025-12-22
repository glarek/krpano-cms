<?php
// api/auth_proxy.php
require_once 'data_helper.php';

// 1. Get requested file path relative to /projekt/
$file = $_GET['file'] ?? '';

// Security: Prevent directory traversal
if (strpos($file, '..') !== false) {
    http_response_code(403);
    exit('Access Denied');
}

$baseDir = __DIR__ . '/../projekt/';
$fullPath = $baseDir . $file;

// 2. Extract Group ID (first segment)
// Path: GROUP_ID/PROJECT_FOLDER/file.ext
$parts = explode('/', trim($file, '/'));
$groupId = $parts[0] ?? null;

if (!$groupId) {
    http_response_code(404);
    exit;
}

// 3. Load Project Data & Validate
$projectsData = loadProjects();
$groupData = $projectsData['groups'][$groupId] ?? null;

if (!$groupData) {
    http_response_code(404);
    exit('Group not found');
}

$validToken = $groupData['token'] ?? null;

// 4. Check Authorization
$authorized = false;

// If NO token is configured for the group, it is public
if (empty($validToken)) {
    $authorized = true;
} else {
    // Token exists, enforce checks
    $cookieName = 'krpano_access_' . $groupId;

    // A. Check URL Token
    if (isset($_GET['token']) && $_GET['token'] === $validToken) {
        $authorized = true;
        // Set Cookie (1 hour) path / to allow access to all project assets
        setcookie($cookieName, $validToken, time() + 3600, '/');
    } 
    // B. Check Cookie
    elseif (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === $validToken) {
        $authorized = true;
    }
}

if (!$authorized) {
    http_response_code(403);
    // Optional: Redirect to login or show simplified error
    echo "Access Denied. Invalid or missing token.";
    exit;
}

// 5. Serve File
if (file_exists($fullPath) && is_file($fullPath)) {
    // Determine MIME type
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'html' => 'text/html',
        'js'   => 'application/javascript',
        'css'  => 'text/css',
        'xml'  => 'application/xml',
        'json' => 'application/json',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
        'mp3'  => 'audio/mpeg',
        'mp4'  => 'video/mp4'
    ];
    
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullPath));
    
    // Disable caching for html/xml to ensure token checks are fresh? 
    // Actually assets should be cached efficiently.
    // HTML checks auth every time.
    if ($ext === 'html') {
        header("Cache-Control: no-cache, must-revalidate");
    }
    
    readfile($fullPath);
    exit;
} else {
    http_response_code(404);
    echo "File not found";
}
