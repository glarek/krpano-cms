<?php
// api/generate_token.php
require_once 'auth_check.php';

$dataFile = __DIR__ . '/project_auth_data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $project = trim($input['project_name'] ?? $_POST['project_name'] ?? '');
    $action = $input['action'] ?? $_POST['action'] ?? ''; // 'generate', 'delete', 'get'

    if (empty($project)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ogiltigt projektnamn.']);
        exit;
    }

    $currentData = [];
    if (file_exists($dataFile)) {
        $currentData = require $dataFile;
        if (!is_array($currentData)) $currentData = [];
    }

    $token = null;
    $created = null;
    $msg = "";

    if ($action === 'delete') {
        unset($currentData[$project]);
        $msg = "Skydd borttaget. Länken är nu ogiltig.";
    } elseif ($action === 'generate') {
        try {
            $token = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $token = bin2hex(openssl_random_pseudo_bytes(16));
        }
        $created = date('Y-m-d H:i');
        $currentData[$project] = [
            'token' => $token,
            'created' => $created
        ];
        $msg = "Ny länk genererad!";
    } elseif ($action === 'get') {
        if (isset($currentData[$project])) {
            echo json_encode([
                'success' => true, 
                'token' => $currentData[$project]['token'] ?? null, 
                'created' => $currentData[$project]['created'] ?? null
            ]);
        } else {
            echo json_encode(['success' => true, 'token' => null]);
        }
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Okänd åtgärd.']);
        exit;
    }

    // Save back to file
    $export = var_export($currentData, true);
    $content = "<?php\nreturn " . $export . ";\n";
    
    if (file_put_contents($dataFile, $content, LOCK_EX)) {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($dataFile, true);
        }
        echo json_encode([
            'success' => true, 
            'message' => $msg,
            'token' => $token,
            'created' => $created
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Kunde inte spara till fil.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
}
