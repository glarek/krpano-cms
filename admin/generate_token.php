<?php
require_once 'auth_check.php';

$dataFile = __DIR__ . '/project_auth_data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project = trim($_POST['project_name'] ?? '');
    $action = $_POST['action'] ?? ''; // 'generate' or 'delete'

    if (empty($project)) {
        echo json_encode(['success' => false, 'message' => 'Ogiltigt projektnamn.']);
        exit;
    }

    $currentData = [];
    if (file_exists($dataFile)) {
        $currentData = require $dataFile;
        if (!is_array($currentData)) $currentData = [];
    }

    if ($action === 'delete') {
        unset($currentData[$project]);
        $msg = "Skydd borttaget. Länken är nu ogiltig.";
        $token = null;
        $created = null;
    } elseif ($action === 'generate') {
        // Generate a secure random token
        try {
            $token = bin2hex(random_bytes(16)); // 32 chars
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
         // Just retrieve current info
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
        echo json_encode(['success' => false, 'message' => 'Okänd åtgärd.']);
        exit;
    }

    // Save back to file
    $export = var_export($currentData, true);
    $content = "<?php\nreturn " . $export . ";\n";
    
    if (file_put_contents($dataFile, $content, LOCK_EX)) {
        // Try to clear OPcache immediately
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
        echo json_encode(['success' => false, 'message' => 'Kunde inte spara till fil.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Ogiltig förfrågan.']);
}
