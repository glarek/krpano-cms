<?php
// api/get_tour_file.php
// Returns the content of a tour's HTML or XML file.
require_once 'auth_check.php';
require_once 'data_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$groupId = $_GET['group'] ?? '';
$projectName = $_GET['project'] ?? '';
$type = $_GET['type'] ?? '';

// Validate parameters
if (empty($groupId) || empty($projectName) || empty($type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: group, project, type']);
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

$content = file_get_contents($filePath);

if ($content === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to read file']);
    exit;
}

echo json_encode([
    'success' => true,
    'content' => $content,
    'filename' => $filename
]);
