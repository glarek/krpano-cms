<?php
// api/update_tour_file.php
// Updates the content of a tour's HTML or XML file.
require_once 'auth_check.php';
require_once 'data_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit;
}

$groupId = $input['group'] ?? '';
$projectName = $input['project'] ?? '';
$type = $input['type'] ?? '';
$content = $input['content'] ?? null;

// Validate parameters
if (empty($groupId) || empty($projectName) || empty($type) || $content === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: group, project, type, content']);
    exit;
}

// Validate type
$allowedTypes = ['html', 'xml'];
if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid type. Allowed values: html, xml']);
    exit;
}

// Load project data
$projectsData = loadProjects();

// Validate group exists
if (!isset($projectsData['groups'][$groupId])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Group not found']);
    exit;
}

$groupData = $projectsData['groups'][$groupId];

// Validate project exists
if (!isset($groupData['projects'][$projectName])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

$projectData = $groupData['projects'][$projectName];
$projectFolder = $projectData['folder'] ?? '';

if (empty($projectFolder)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Project folder not configured']);
    exit;
}

// Resolve file path
$filename = 'tour.' . $type;
$filePath = getStoragePath() . '/' . $groupId . '/' . $projectFolder . '/' . $filename;

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found: ' . $filename]);
    exit;
}

// Create backup before overwriting
$backupPath = $filePath . '.bak.' . time();
if (!copy($filePath, $backupPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create backup']);
    exit;
}

// Write new content
$result = file_put_contents($filePath, $content, LOCK_EX);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to write file']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'File updated successfully.',
    'filename' => $filename,
    'backup' => basename($backupPath)
]);
