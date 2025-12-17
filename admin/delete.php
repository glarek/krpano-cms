<?php
require_once 'auth_check.php';

if (isset($_GET['project'])) {
    $project = $_GET['project'];
    
    // Security check: strictly alphanumeric+dashes only to prevent traversal
    // Although basename() is safe, strictly matching our naming convention is better.
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $project)) {
        die("Invalid project name.");
    }

    $dir = __DIR__ . '/../projekt/' . $project;

    if (is_dir($dir)) {
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

        rrmdir($dir);
    }
}

header('Location: index.php');
exit;
