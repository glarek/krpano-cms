<?php
// api/upload.php
require_once 'auth_check.php';
require_once 'data_helper.php';

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

// Use raw filename as project name (no url encoding)
$projectName = $filename;

// Load existing projects data
$projectsData = loadProjects();

// Check existence
if (isset($projectsData['groups'][$group]['projects'][$projectName])) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Ett projekt med detta namn finns redan.']);
    exit;
}

// Get Group Info
if (!isset($projectsData['groups'][$group])) {
    // Group must exist (created via create_group)
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Gruppen finns inte.']);
    exit;
}

$groupId = $group;
$groupPath = __DIR__ . '/../projekt/' . $groupId;

// Generate unique folder ID for project
$newId = generateUniqueProjectId($projectsData);
$newToken = generateToken();

$targetDir = $groupPath . '/' . $newId;
// No need to check group dir creation, assume it exists or fail.
if (!is_dir($groupPath)) {
     // Wait, maybe we should create it if missing (recovery)?
     // The ID is in the DB.
     if (!mkdir($groupPath, 0755, true)) {
         http_response_code(500);
         echo json_encode(['success' => false, 'message' => 'Grupp-mappen saknas på disk och kunde inte skapas.']);
         exit;
     }
}

// Defensive check (should be unique)
if (is_dir($targetDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internt fel: ID-kollision. Försök igen.']);
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

    // Update data array
    if (!isset($projectsData['groups'][$group]['projects'])) {
        $projectsData['groups'][$group]['projects'] = [];
    }
    
    $projectsData['groups'][$group]['projects'][$projectName] = [
        'folder' => $newId,
        'token' => $newToken,
        'created' => date('Y-m-d H:i')
    ];
    
    saveProjects($projectsData);

    echo json_encode(['success' => true, 'message' => 'Projekt uppladdat och skapat!']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kunde inte öppna ZIP-filen.']);
}
