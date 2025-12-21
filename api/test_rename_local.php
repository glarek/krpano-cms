<?php
// test_rename_local.php

$baseDir = __DIR__ . '/../projekt/';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
}

// 1. Create a dummy folder
$testFolder = $baseDir . 'Debug_Test_' . time();
if (!mkdir($testFolder)) {
    echo "FAILED to create test folder: $testFolder\n";
    exit(1);
}
echo "Created test folder: $testFolder\n";

// 2. Try to rename it without encoding first
$newNameSimple = $testFolder . '_Renamed';
if (rename($testFolder, $newNameSimple)) {
    echo "SUCCESS: Simple rename worked.\n";
} else {
    $err = error_get_last();
    echo "FAILED: Simple rename failed. Error: " . ($err['message'] ?? 'Unknown') . "\n";
    // Clean up
    rmdir($testFolder);
    exit(1);
}

// 3. Try to rename it with Encoding (%)
$newNameEncoded = $testFolder . '_%20_Encoded';
if (rename($newNameSimple, $newNameEncoded)) {
    echo "SUCCESS: Encoded rename worked. New path: $newNameEncoded\n";
} else {
    $err = error_get_last();
    echo "FAILED: Encoded rename failed. Error: " . ($err['message'] ?? 'Unknown') . "\n";
    // Clean up
    rmdir($newNameSimple);
    exit(1);
}

// 4. Clean up
if (rmdir($newNameEncoded)) {
    echo "Cleaned up successfully.\n";
} else {
    echo "Warning: Could not remove test folder $newNameEncoded\n";
}

echo "Done.\n";
