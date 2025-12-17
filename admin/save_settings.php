<?php
require_once 'auth_check.php';

$configFile = __DIR__ . '/viewer_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUser = trim($_POST['viewer_user'] ?? '');
    $newPass = $_POST['viewer_pass'] ?? '';
    
    if (!empty($newUser) && !empty($newPass)) {
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        
        $content = "<?php\nreturn [\n    'user' => '" . addslashes($newUser) . "',\n    'pass' => '$newHash'\n];\n";
        
        if (file_put_contents($configFile, $content)) {
            echo json_encode(['success' => true, 'message' => 'Inställningar sparade!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Kunde inte spara. Kontrollera skrivrättigheter.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Fyll i alla fält.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Ogiltig förfrågan.']);
}
