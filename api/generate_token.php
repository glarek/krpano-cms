<?php
// api/generate_token.php
require_once 'auth_check.php';
require_once 'data_helper.php'; // Use helper

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    // Frontend sends 'project_name' but it refers to Group Name
    $groupName = trim($input['project_name'] ?? $_POST['project_name'] ?? '');
    $action = $input['action'] ?? $_POST['action'] ?? ''; // 'generate', 'delete', 'get'

    if (empty($groupName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ogiltigt gruppnamn.']);
        exit;
    }
    
    $projectsData = loadProjects();
    
    // Check if group exists
    if (!isset($projectsData['groups'][$groupName])) {
        // Group not found in new system
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Gruppen hittades inte.']);
        exit;
    }

    $token = null;
    $created = null;
    $msg = "";

    if ($action === 'delete') {
        // Remove token
        // In new structure, should we set it to null or empty string?
        // Or keep it but frontend treats it as "unprotected"?
        // But logic says "isProtected" if token exists.
        // Actually earlier assumption: All groups have tokens.
        // If user wants to "remove protection", maybe we just delete the token key?
        // But shared_group might fail if it expects token?
        // Let's assume protection is optional now.
        $projectsData['groups'][$groupName]['token'] = null; // or unset
        unset($projectsData['groups'][$groupName]['token']);
        
        $msg = "Skydd borttaget. Länken är nu ogiltig.";
    } elseif ($action === 'generate') {
        $token = generateToken(); // Use helper
        $created = date('Y-m-d H:i');
        
        $projectsData['groups'][$groupName]['token'] = $token;
        // Maybe update 'created' date of token too? Or group?
        // Let's just update token.
        
        // If we want to track when token was created, we might need a separate field like 'token_created'
        // Legacy returned 'created'.
        // Let's assume we can add 'token_created' if needed, or just return group created time.
        // For now, let's keep it simple.
        
        $msg = "Ny länk genererad!";
    } elseif ($action === 'get') {
        $gInfo = $projectsData['groups'][$groupName];
        echo json_encode([
            'success' => true, 
            'token' => $gInfo['token'] ?? null, 
            'created' => $gInfo['created'] ?? null // This is group created time usually
        ]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Okänd åtgärd.']);
        exit;
    }

    // Save
    saveProjects($projectsData);

    echo json_encode([
        'success' => true, 
        'message' => $msg,
        'token' => $token,
        'created' => $created
    ]);

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
}
