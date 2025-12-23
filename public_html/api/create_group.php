<?php
// api/create_group.php
require_once 'auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $groupName = $input['group_name'] ?? $_POST['group_name'] ?? '';

    $groupName = trim($groupName);
    // Use basename to sanitize but NOT urlencode
    $groupName = basename($groupName);

    if (empty($groupName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ogiltigt gruppnamn.']);
        exit;
    }

    // Verify not already existing in Array
    require_once 'data_helper.php';
    $projectsData = loadProjects();
    
    // Check duplication by name (iterate)
    if (isset($projectsData['groups'])) {
        foreach ($projectsData['groups'] as $gid => $gData) {
            if (isset($gData['name']) && strcasecmp($gData['name'], $groupName) === 0) {
                 http_response_code(409);
                 echo json_encode(['success' => false, 'message' => 'En grupp med det namnet finns redan.']);
                 exit;
            }
        }
    }

    $baseDir = __DIR__ . '/../projekt/';
    
    // Generate new ID and Token
    $newGroupId = generateUniqueGroupId($projectsData);
    $newToken = generateToken();
    
    $targetDir = $baseDir . $newGroupId;

    if (mkdir($targetDir, 0755, true)) {
        // Register in projectsData with new structure
        if (!isset($projectsData['groups'])) $projectsData['groups'] = [];
        
        // Key is ID now!
        $projectsData['groups'][$newGroupId] = [
            'name' => $groupName, // Store human name here
            'token' => $newToken,
            'created' => date('Y-m-d H:i'),
            'projects' => []
        ];
        saveProjects($projectsData);

        echo json_encode(['success' => true, 'message' => 'Gruppen skapades!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Kunde inte skapa mappen. Kontrollera rättigheter.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
}
