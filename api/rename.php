<?php
// api/rename.php
require_once 'auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $group = $input['group'] ?? $_POST['group'] ?? '';
    $oldName = $input['old_name'] ?? $_POST['old_name'] ?? '';
    $newName = $input['new_name'] ?? $_POST['new_name'] ?? '';

    // Verify that group and oldName are valid basenames (prevent directory traversal)
    if ($group !== basename($group)) $group = '';
    if ($oldName !== basename($oldName)) $oldName = '';
    
    $newName = trim($newName);
    // Encode the new name for filesystem safety
    $newName = rawurlencode($newName);
    
    if (empty($oldName) || empty($newName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Ogiltiga namn."]);
        exit;
    }

    $baseDir = __DIR__ . '/../projekt/';
    
    if (!empty($group)) {
        $oldPath = $baseDir . $group . '/' . $oldName;
        $newPath = $baseDir . $group . '/' . $newName;
        $updateAuth = false;
    } else {
        $oldPath = $baseDir . $oldName;
        $newPath = $baseDir . $newName;
        $updateAuth = true; 
    }

    if (!is_dir($oldPath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Mappen hittades inte."]);
        exit;
    }

    if (is_dir($newPath) && $oldPath !== $newPath) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "Ett projekt/grupp med det namnet finns redan."]);
        exit;
    }

    if (rename($oldPath, $newPath)) {
        if ($updateAuth) {
            $authFile = __DIR__ . '/project_auth_data.php';
            if (file_exists($authFile)) {
                $authData = require $authFile;
                if (is_array($authData) && isset($authData[$oldName])) {
                    $authData[$newName] = $authData[$oldName];
                    unset($authData[$oldName]);
                    file_put_contents($authFile, "<?php\nreturn " . var_export($authData, true) . ";\n");
                }
            }
        }
        echo json_encode(['success' => true]);
    } else {
        $error = error_get_last();
        http_response_code(500);
        // We can expose the error message here since it's an admin tool
        echo json_encode(['success' => false, 'message' => "Misslyckades att döpa om mappen. (" . ($error['message'] ?? 'Okänt fel') . ")"]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
}
