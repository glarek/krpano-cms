<?php
// api/delete.php
require_once 'auth_check.php';

header('Content-Type: application/json');

// Read JSON input if sent as JSON, or use $_POST/$_GET
// Delete usually implies DELETE method, but often passed as POST with _method or just POST.
// Let's support POST with body params or GET parameters for simplicity in testing, 
// though POST is safer for state change.
// The original used GET. Let's switch to POST for better practice, but support GET for easy migration/testing if needed?
// No, let's stick to POST for mutating actions in an API.

$project = null;
$group = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $project = $input['project'] ?? null;
        $group = $input['group'] ?? null;
    } else {
        $project = $_POST['project'] ?? null;
        $group = $_POST['group'] ?? null;
    }
} else {
     // Allow GET for now? Or strict? 
     // Strict JSON API usually implies POST/DELETE.
     // Let's accept GET for now to make it easy to call from simple links if frontend isn't ready,
     // BUT the goal is an API for SvelteKit. SvelteKit can send POSTs easily.
     // Let's stick to POST.
     http_response_code(405);
     echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use POST.']);
     exit;
}

$baseDir = __DIR__ . '/../projekt/';
$targetDir = '';

if ($group && $project) {
    // Delete Project inside Group
    // Sanitize - allow encoded chars (%, .)
    // Note: We should encode the inputs to match filesystem if they are not already
    // The inputs are likely decoded JSON/POST. 
    // Regexp check might fail if we expect raw chars but get special chars. 
    // Let's rely on rawurlencode to sanitize/standardize for FS.
    
    $group = rawurlencode($group);
    $project = rawurlencode($project);

    $targetDir = $baseDir . $group . '/' . $project;

} elseif ($group && empty($project)) {
    // Delete Entire Group
    $group = rawurlencode($group);
    $targetDir = $baseDir . $group;
    
} elseif ($project) {
    // Legacy Root Project Delete
    $project = rawurlencode($project);
    $targetDir = $baseDir . $project;
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Missing 'group' or 'project' parameter."]);
    exit;
}

if ($targetDir && is_dir($targetDir)) {
    // Recursive delete function
    function rrmdir($src) {
        $dir = opendir($src);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if ( is_dir($full) ) {
                    rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }

    rrmdir($targetDir);
    echo json_encode(['success' => true]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Directory not found."]);
}
