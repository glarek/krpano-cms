<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zipfile'])) {
    $file = $_FILES['zipfile'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = "Uppladdningsfel källkod: " . $file['error'];
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE: $msg = "Filen är för stor (överskrider upload_max_filesize i php.ini/htaccess)."; break;
            case UPLOAD_ERR_FORM_SIZE: $msg = "Filen är för stor (överskrider formulärets maxstorlek)."; break;
            case UPLOAD_ERR_PARTIAL: $msg = "Filen laddades bara upp delvis."; break;
            case UPLOAD_ERR_NO_FILE: $msg = "Ingen fil laddades upp."; break;
            case UPLOAD_ERR_NO_TMP_DIR: $msg = "Saknar temporär mapp på servern."; break;
            case UPLOAD_ERR_CANT_WRITE: $msg = "Misslyckades att skriva fil till disk."; break;
            case UPLOAD_ERR_EXTENSION: $msg = "En PHP-tillägg stoppade uppladdningen."; break;
        }
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    // Check if it's a zip
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'zip') {
        echo json_encode(['success' => false, 'message' => "Endast ZIP-filer är tillåtna (fick .$ext)."]);
        exit;
    }

    // Prepare target name
    $rawName = pathinfo($file['name'], PATHINFO_FILENAME);
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $rawName);
    
    if (empty($safeName)) $safeName = "project";

    $targetDirBase = __DIR__ . '/../projekt/';
    if (!is_dir($targetDirBase)) {
        if (!mkdir($targetDirBase, 0755, true)) {
            $err = error_get_last();
            echo json_encode(['success' => false, 'message' => "Kunde inte skapa huvudmappen '../projekt/'. " . ($err['message'] ?? '')]);
            exit;
        }
    }

    // Unique name logic
    $targetName = $safeName;
    $counter = 1;
    while (is_dir($targetDirBase . $targetName)) {
        $targetName = $safeName . '_' . $counter;
        $counter++;
    }

    $finalDir = $targetDirBase . $targetName;
    
    // Create directory
    if (!mkdir($finalDir, 0755)) {
        $err = error_get_last();
        echo json_encode(['success' => false, 'message' => "Kunde inte skapa projektmapp '$finalDir'. " . ($err['message'] ?? '')]);
        exit;
    }

    // Extract ZIP
    $zip = new ZipArchive;
    $res = $zip->open($file['tmp_name']);
    if ($res === TRUE) {
        if (!$zip->extractTo($finalDir)) {
             echo json_encode(['success' => false, 'message' => "Kunde inte extrahera filer till '$finalDir'."]);
             rmdir($finalDir);
             exit;
        }
        $zip->close();
        
        echo json_encode(['success' => true, 'message' => "Uppladdning klar!", 'project' => $targetName]);
        exit;
    } else {
        rmdir($finalDir);
        echo json_encode(['success' => false, 'message' => "Kunde inte öppna ZIP-filen. Felkod: $res"]);
        exit;
    }

} else {
    echo json_encode(['success' => false, 'message' => "Ingen fil mottagen via POST."]);
    exit;
}
