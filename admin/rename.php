<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldName = $_POST['old_name'] ?? '';
    $newName = $_POST['new_name'] ?? '';

    // Sanitize input
    // Allow basic chars
    $oldName = basename($oldName); // Preventing traversal
    $newName = preg_replace('/[^a-zA-Z0-9_-]/', '', $newName);

    if (empty($oldName) || empty($newName)) {
        die("Invalid names.");
    }

    $baseDir = __DIR__ . '/../projekt/';
    $oldPath = $baseDir . $oldName;
    $newPath = $baseDir . $newName;

    if (!is_dir($oldPath)) {
        die("Project not found.");
    }

    if (is_dir($newPath) && $oldPath !== $newPath) {
        die("A project with that name already exists.");
    }

    if (rename($oldPath, $newPath)) {
        // Update Auth Data if exists
        $authFile = __DIR__ . '/project_auth_data.php';
        if (file_exists($authFile)) {
            $authData = require $authFile;
            if (is_array($authData) && isset($authData[$oldName])) {
                $authData[$newName] = $authData[$oldName];
                unset($authData[$oldName]);
                file_put_contents($authFile, "<?php\nreturn " . var_export($authData, true) . ";\n");
            }
        }
        
        header('Location: index.php');
        exit;
    } else {
        die("Error renaming folder.");
    }
}

header('Location: index.php');
exit;
