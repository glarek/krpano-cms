<?php
// api/move.php
require_once 'auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $project = trim($input['project'] ?? $_POST['project'] ?? '');
    $baseGroup = trim($input['current_group'] ?? $_POST['current_group'] ?? '');
    $targetGroup = trim($input['target_group'] ?? $_POST['target_group'] ?? '');
    
    if (empty($project) || empty($baseGroup) || empty($targetGroup)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Ogiltiga parametrar."]);
        exit;
    }

    $baseDir = __DIR__ . '/../projekt/';
    $project = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $project);
    $baseGroup = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $baseGroup);

    if ($targetGroup === 'NEW') {
        $newGroup = trim($input['new_group_name'] ?? $_POST['new_group_name'] ?? '');
        $newGroup = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $newGroup);
        if (empty($newGroup)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Ogiltigt namn för ny grupp."]);
            exit;
        }
        $targetGroup = $newGroup;
        if (!is_dir($baseDir . $targetGroup)) {
            mkdir($baseDir . $targetGroup, 0755, true);
        }
    } else {
        $targetGroup = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $targetGroup);
        if (!is_dir($baseDir . $targetGroup)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Målgruppen finns inte."]);
            exit;
        }
    }

    $sourcePath = $baseDir . $baseGroup . '/' . $project;
    $destPath = $baseDir . $targetGroup . '/' . $project;

    if (!is_dir($sourcePath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Källprojektet hittades inte."]);
        exit;
    }
    
    if (file_exists($destPath)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "Ett projekt med samma namn finns redan i målgruppen."]);
        exit;
    }

    if (rename($sourcePath, $destPath)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => "Misslyckades att flytta mappen."]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
}
