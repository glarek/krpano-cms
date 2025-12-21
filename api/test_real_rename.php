<?php
// test_real_rename.php

$baseDir = __DIR__ . '/../projekt/';
$target = $baseDir . 'Drive';

if (!is_dir($target)) {
    echo "Target folder '$target' does not exist.\n";
    exit(1);
}

echo "Attempting to rename '$target'...\n";

$tempName = $baseDir . 'Drive_TestLock';

if (rename($target, $tempName)) {
    echo "SUCCESS: Renamed 'Drive' to 'Drive_TestLock'.\n";
    // Rename back
    if (rename($tempName, $target)) {
        echo "SUCCESS: Renamed back to 'Drive'.\n";
    } else {
        echo "CRITICAL: Could not rename back! Folder is now 'Drive_TestLock'\n";
    }
} else {
    $err = error_get_last();
    echo "FAILED: Could not rename 'Drive'. Error: " . ($err['message'] ?? 'Unknown') . "\n";
    echo "This indicates the folder is LOCKED by another process (Explorer, VS Code, Browser, etc).\n";
}
