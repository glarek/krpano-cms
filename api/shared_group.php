<?php
// api/shared_group.php
require_once 'data_helper.php';

header('Content-Type: application/json');

$token = $_GET['token'] ?? '';
$groupId = $_GET['id'] ?? '';

// Load data
$projectsData = loadProjects();

// Find group
if (!$groupId || !isset($projectsData['groups'][$groupId])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Gruppen hittades inte.']);
    exit;
}

$groupInfo = $projectsData['groups'][$groupId];

// Validate Token
$validToken = $groupInfo['token'] ?? '';
if ($token !== $validToken) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ogiltig token.']);
    exit;
}

// Prepare Response
$projectsList = [];
if (isset($groupInfo['projects'])) {
    foreach ($groupInfo['projects'] as $pName => $pData) {
        if (isset($pData['folder'])) {
            $projectsList[] = [
                'name' => $pName,
                'folder' => $pData['folder']
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'group_name' => $groupInfo['name'] ?? $groupId,
    'group_id' => $groupId,
    'projects' => $projectsList
]);
