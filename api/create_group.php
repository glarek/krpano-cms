<?php
// api/create_group.php
require_once 'auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $groupName = $input['group_name'] ?? $_POST['group_name'] ?? '';

    $groupName = trim($groupName);
    // Use rawurlencode to make the name filesystem safe while preserving the original name
    $groupName = rawurlencode($groupName);

    if (empty($groupName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ogiltigt gruppnamn.']);
        exit;
    }

    $baseDir = __DIR__ . '/../projekt/';
    $targetDir = $baseDir . $groupName;

    if (is_dir($targetDir)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'En grupp med det namnet finns redan.']);
        exit;
    }

    if (mkdir($targetDir, 0755, true)) {
        echo json_encode(['success' => true, 'message' => 'Gruppen skapades!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Kunde inte skapa mappen. Kontrollera rättigheter.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
}
