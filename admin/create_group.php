<?php
// admin/create_group.php
// Handles creating a new group folder

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupName = $_POST['group_name'] ?? '';

    // Basic sanitization
    // 1. Replace spaces with hyphens
    $groupName = trim($groupName);
    $groupName = str_replace(' ', '-', $groupName);
    // 2. Remove other invalid characters (keep alphanumeric, hyphens, underscores, Swedish chars)
    $groupName = preg_replace('/[^a-zA-Z0-9_\-åäöÅÄÖ]/u', '', $groupName);

    if (empty($groupName)) {
        echo json_encode(['success' => false, 'message' => 'Ogiltigt gruppnamn.']);
        exit;
    }

    $baseDir = '../projekt/';
    $targetDir = $baseDir . $groupName;

    if (is_dir($targetDir)) {
        echo json_encode(['success' => false, 'message' => 'En grupp med det namnet finns redan.']);
        exit;
    }

    // Try to create directory
    if (mkdir($targetDir, 0755, true)) {
        echo json_encode(['success' => true, 'message' => 'Gruppen skapades!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kunde inte skapa mappen. Kontrollera rättigheter.']);
    }
    exit;
}
?>
