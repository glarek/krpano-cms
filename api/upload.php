<?php
// api/upload.php
require_once 'auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$group = $_POST['group'] ?? '';
if (empty($group)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ingen grupp angiven.']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $msg = 'Ingen fil uppladdad eller fel vid uppladdning.';
    // Add specific error messages if needed based on error code
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$file = $_FILES['file'];
$filename = pathinfo($file['name'], PATHINFO_FILENAME);
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($ext !== 'zip') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Endast ZIP-filer är tillåtna.']);
    exit;
}

// Sanitize project name using rawurlencode
$projectName = rawurlencode($filename);
// Encode group name to match filesystem
$group = rawurlencode($group);

$targetDir = __DIR__ . '/../projekt/' . $group . '/' . $projectName;

if (is_dir($targetDir)) {
    http_response_code(409); // Conflict
    echo json_encode(['success' => false, 'message' => 'Ett projekt med detta namn finns redan.']);
    exit;
}

$zip = new ZipArchive;
if ($zip->open($file['tmp_name']) === TRUE) {
    if (!mkdir($targetDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Kunde inte skapa projektmappen.']);
        $zip->close();
        exit;
    }

    $zip->extractTo($targetDir);
    $zip->close();

    echo json_encode(['success' => true, 'message' => 'Projekt uppladdat och skapat!']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kunde inte öppna ZIP-filen.']);
}
