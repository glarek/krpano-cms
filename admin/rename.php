<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group = $_POST['group'] ?? '';
    $oldName = $_POST['old_name'] ?? '';
    $newName = $_POST['new_name'] ?? '';

    // Sanitize
    $group = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $group);
    $oldName = basename($oldName);
    
    $newName = trim($newName);
    $newName = str_replace(' ', '-', $newName);
    $newName = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $newName);

    if (empty($oldName) || empty($newName)) {
        die("Ogiltiga namn.");
    }

    $baseDir = __DIR__ . '/../projekt/';
    
    // Construct paths based on whether it's a Group or Project rename
    // Logic: If user is renaming a Project inside a Group
    if (!empty($group)) {
        $oldPath = $baseDir . $group . '/' . $oldName;
        $newPath = $baseDir . $group . '/' . $newName;
        
        // Auth is on Group level, so renaming a project Inside a group doesn't change Auth keys.
        $updateAuth = false;
    } else {
        // Fallback/Legacy or Group Rename (if enabled later)
        // If we want to support renaming Groups, we'd need a specific flag or detecting if it's a dir in root.
        // For now, let's assume this script is mostly for Projects as per UI.
        
        // If 'old_name' is a directory in 'projekt/', it's a Root Project OR a Group.
        $oldPath = $baseDir . $oldName;
        $newPath = $baseDir . $newName;
        
        // If it's a Group, we MIGHT need to update Auth keys.
        // Let's check if there is an auth entry for this oldName
        $updateAuth = true; 
    }

    if (!is_dir($oldPath)) {
        die("Mappen hittades inte: " . htmlspecialchars($oldPath));
    }

    if (is_dir($newPath) && $oldPath !== $newPath) {
        die("Ett projekt/grupp med det namnet finns redan.");
    }

    if (rename($oldPath, $newPath)) {
        // Update Auth Data only if we renamed a Group (or legacy root project)
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
        
        header('Location: index.php');
        exit;
    } else {
        die("Misslyckades att döpa om mappen.");
    }
}

header('Location: index.php');
exit;
