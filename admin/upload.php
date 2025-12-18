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

    // Handle Group Logic
    $baseDir = __DIR__ . '/../projekt/';
    $groupName = '';

    if (isset($_POST['group_mode']) && $_POST['group_mode'] === 'NEW') {
        $newGroup = trim($_POST['new_group_name'] ?? '');
        // Sanitize group name
        $newGroup = str_replace(' ', '-', $newGroup);
        $newGroup = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $newGroup); // Allow Swedish chars
        if (empty($newGroup)) {
            echo json_encode(['success' => false, 'message' => "Ogiltigt namn på ny grupp."]);
            exit;
        }
        $groupName = $newGroup;
        if (!is_dir($baseDir . $groupName)) {
            if (!mkdir($baseDir . $groupName, 0755, true)) {
                 $err = error_get_last();
                 echo json_encode(['success' => false, 'message' => "Kunde inte skapa gruppmapp via mkdir. " . ($err['message'] ?? '')]);
                 exit;
            }
        }
    } else {
        $targetGroup = trim($_POST['target_group'] ?? '');
         // Basic sanity check
        if (empty($targetGroup) || strpos($targetGroup, '..') !== false || !is_dir($baseDir . $targetGroup)) {
            echo json_encode(['success' => false, 'message' => "Ogiltig eller saknad målgrupp."]);
            exit;
        }
        $groupName = $targetGroup;
    }

    // Prepare target project name
    $rawName = pathinfo($file['name'], PATHINFO_FILENAME);
    $safeName = trim($rawName);
    $safeName = str_replace(' ', '-', $safeName);
    $safeName = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $safeName);
    
    if (empty($safeName)) $safeName = "project";

    // Uniqueness within the GROUP
    $targetDir = $baseDir . $groupName . '/' . $safeName;
    
    // Check if project already exists in this group
    if (file_exists($targetDir)) {
         // Auto-increment? Or fail? Providing error is safer.
         // Let's increment for convenience as per previous logic
         $counter = 1;
         $originalName = $safeName;
         while (file_exists($baseDir . $groupName . '/' . $safeName)) {
             $safeName = $originalName . '_' . $counter;
             $counter++;
         }
         $targetDir = $baseDir . $groupName . '/' . $safeName;
    }

    // Create Project Directory
    if (!mkdir($targetDir, 0755)) {
        $err = error_get_last();
        echo json_encode(['success' => false, 'message' => "Kunde inte skapa projektmapp '$targetDir'. " . ($err['message'] ?? '')]);
        exit;
    }

    // Extract ZIP
    $zip = new ZipArchive;
    $res = $zip->open($file['tmp_name']);
    if ($res === TRUE) {
        if (!$zip->extractTo($targetDir)) {
             echo json_encode(['success' => false, 'message' => "Kunde inte extrahera filer till '$targetDir'."]);
             // Cleanup
             // recursive delete? for now just rmdir (won't work if files partially extracted)
             exit;
        }
        $zip->close();
        
        echo json_encode(['success' => true, 'message' => "Uppladdning klar!", 'group' => $groupName, 'project' => $safeName]);
        exit;
    } else {
        rmdir($targetDir); // remove empty dir
        echo json_encode(['success' => false, 'message' => "Kunde inte öppna ZIP-filen. Felkod: $res"]);
        exit;
    }

} else {
    echo json_encode(['success' => false, 'message' => "Ingen fil mottagen via POST."]);
    exit;
}
