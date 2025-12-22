<?php
// api/rename.php
require_once 'auth_check.php';
require_once 'data_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
    // Validate input
    $parentGroup = trim($input['group'] ?? $_POST['group'] ?? ''); // Empty if renaming group
    $targetId = trim($input['target_id'] ?? $_POST['target_id'] ?? $input['old_name'] ?? $_POST['old_name'] ?? '');
    $newName = trim($input['new_name'] ?? $_POST['new_name'] ?? '');

    if (empty($targetId) || empty($newName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Ogiltiga parametrar."]);
        exit;
    }

    $projectsData = loadProjects();

    if (empty($parentGroup)) {
        // --- RENAME GROUP ---
        // $targetId is Authorization Group ID
        if (!isset($projectsData['groups'][$targetId])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Gruppen hittades inte."]);
            exit;
        }

        // Check if new name exists (Iterate all groups)
        foreach ($projectsData['groups'] as $gid => $gData) {
            if ($gid !== $targetId && isset($gData['name']) && strcasecmp($gData['name'], $newName) === 0) {
                 http_response_code(409);
                 echo json_encode(['success' => false, 'message' => "En grupp med namnet '$newName' finns redan."]);
                 exit;
            }
        }

        // Update Name
        $projectsData['groups'][$targetId]['name'] = $newName;
        saveProjects($projectsData);
        
        echo json_encode(['success' => true]);

    } else {
        // --- RENAME PROJECT ---
        // $parentGroup is Group ID
        // $targetId is Project Name (Key)
        
        if (!isset($projectsData['groups'][$parentGroup])) {
             http_response_code(404);
             echo json_encode(['success' => false, 'message' => "Gruppen hittades inte."]);
             exit;
        }

        if (!isset($projectsData['groups'][$parentGroup]['projects'][$targetId])) {
             http_response_code(404);
             echo json_encode(['success' => false, 'message' => "Projektet hittades inte."]);
             exit;
        }

        // Check duplications in this group
        if (isset($projectsData['groups'][$parentGroup]['projects'][$newName])) {
             http_response_code(409);
             echo json_encode(['success' => false, 'message' => "Ett projekt med namnet '$newName' finns redan i gruppen."]);
             exit;
        }

        // Rename Key
        $projectData = $projectsData['groups'][$parentGroup]['projects'][$targetId];
        $projectsData['groups'][$parentGroup]['projects'][$newName] = $projectData;
        unset($projectsData['groups'][$parentGroup]['projects'][$targetId]);
        
        saveProjects($projectsData);
        
        echo json_encode(['success' => true]);
    }
