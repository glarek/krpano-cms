<?php
require_once 'auth_check.php';

if (isset($_GET['project']) || isset($_GET['group'])) {
    $project = $_GET['project'] ?? null;
    $group = $_GET['group'] ?? null;
    
    $baseDir = __DIR__ . '/../projekt/';
    $targetDir = '';

    if ($group && $project) {
        // Delete Project inside Group
        // Sanitize
        if (!preg_match('/^[a-zA-Z0-9åäöÅÄÖ_-]+$/u', $project) || !preg_match('/^[a-zA-Z0-9åäöÅÄÖ_-]+$/u', $group)) {
             die("Ogiltiga namn.");
        }
        $targetDir = $baseDir . $group . '/' . $project;

    } elseif ($group && empty($project)) {
        // Delete Entire Group
        if (!preg_match('/^[a-zA-Z0-9åäöÅÄÖ_-]+$/u', $group)) {
             die("Ogiltigt gruppnamn.");
        }
        $targetDir = $baseDir . $group;
        
    } elseif ($project) {
        // Legacy Root Project Delete
        if (!preg_match('/^[a-zA-Z0-9åäöÅÄÖ_-]+$/u', $project)) {
            die("Ogiltigt projektnamn.");
        }
        $targetDir = $baseDir . $project;
    }

    if ($targetDir && is_dir($targetDir)) {
        // Recursive delete function
        function rrmdir($src) {
            $dir = opendir($src);
            while(false !== ( $file = readdir($dir)) ) {
                if (( $file != '.' ) && ( $file != '..' )) {
                    $full = $src . '/' . $file;
                    if ( is_dir($full) ) {
                        rrmdir($full);
                    } else {
                        unlink($full);
                    }
                }
            }
            closedir($dir);
            rmdir($src);
        }

        rrmdir($targetDir);
    }
}

header('Location: index.php');
exit;
