<?php
// api/move.php
require_once 'auth_check.php';
require_once 'data_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$project = trim($input['project'] ?? $_POST['project'] ?? '');
$baseGroup = trim($input['current_group'] ?? $_POST['current_group'] ?? '');
$targetGroup = trim($input['target_group'] ?? $_POST['target_group'] ?? '');

if (empty($project) || empty($baseGroup) || empty($targetGroup)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Ogiltiga parametrar."]);
    exit;
}

$projectsData = loadProjects();
$baseDir = getStoragePath() . '/';

function findPathForMove($base, $name) {
    if (is_dir($base . $name)) return $base . $name;
    if (is_dir($base . rawurlencode($name))) return $base . rawurlencode($name);
    return null;
}

// 1. Resolve Source Path and Info
$sourcePath = null;
$projectInfo = null;

if (isset($projectsData['groups'][$baseGroup]['projects'][$project])) {
    $info = $projectsData['groups'][$baseGroup]['projects'][$project];
    $baseGroupId = $baseGroup;
    
    $sourcePath = $baseDir . $baseGroupId . '/' . $info['folder'];
    $projectInfo = $info;
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Källprojektet hittades inte i registret (kan inte flytta legacy-projekt)."]);
    exit;
}

if (!is_dir($sourcePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Källprojektets mapp saknas på disk."]);
    exit;
}

// 2. Resolve Target Group
$targetGroupId = '';
$targetGroupPath = '';

if ($targetGroup === 'NEW') {
    $newGroup = trim($input['new_group_name'] ?? $_POST['new_group_name'] ?? '');
    
    if (empty($newGroup)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Ogiltigt namn för ny grupp."]);
        exit;
    }
    
    // Check if new group name exists in array
    if (isset($projectsData['groups'][$newGroup])) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "Gruppen finns redan."]);
        exit;
    }

    require_once 'data_helper.php'; // Ensure helper is loaded
    $newGroupIdVal = generateUniqueGroupId($projectsData);
    $newGroupToken = generateToken(); // Generate token for new group?
    
    $targetGroupId = $newGroupIdVal;
    $targetGroupPath = $baseDir . $targetGroupId;
    
    // Update Array for new group
    if (!isset($projectsData['groups'])) $projectsData['groups'] = [];
    $projectsData['groups'][$newGroup] = [
        'id' => $newGroupIdVal,
        'token' => $newGroupToken,
        'projects' => []
    ];
    
    $targetGroup = $newGroup; // Update variable to actual name

    if (!is_dir($targetGroupPath)) {
        if (!mkdir($targetGroupPath, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Kunde inte skapa ny grupp-mapp."]);
            exit;
        }
    }
} else {
    // Existing group
    if (isset($projectsData['groups'][$targetGroup])) {
        $targetGroupId = $targetGroup;
        $targetGroupPath = $baseDir . $targetGroupId;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Målgruppen finns inte."]);
        exit;
    }
}

// 3. Resolve Destination Path
// Use project folder ID (preserved)
$folderId = $projectInfo['folder'];
$destPath = $targetGroupPath . '/' . $folderId;

// 4. Check Conflicts
// Array Conflict
if (isset($projectsData['groups'][$targetGroup]['projects'][$project])) {
     http_response_code(409);
     echo json_encode(['success' => false, 'message' => "Ett projekt med samma namn finns redan i målgruppen."]);
     exit;
}
// Disk Conflict (Folder ID collision? Should be unique globally, but check anyway)
if (is_dir($destPath)) {
     http_response_code(409);
     echo json_encode(['success' => false, 'message' => "Internt fel: ID-kollision i målgruppen."]);
     exit;
}

// 5. Move
if (rename($sourcePath, $destPath)) {
    // 6. Update Data
    // Add to new group projects
    if (!isset($projectsData['groups'][$targetGroup]['projects'])) {
        $projectsData['groups'][$targetGroup]['projects'] = [];
    }
    $projectsData['groups'][$targetGroup]['projects'][$project] = $projectInfo;

    // Remove from old group
    unset($projectsData['groups'][$baseGroup]['projects'][$project]);
    saveProjects($projectsData);
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Misslyckades att flytta mappen."]);
}
