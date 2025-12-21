<?php
// api/dashboard.php
require_once 'auth_check.php';

header('Content-Type: application/json');

$projectDir = __DIR__ . '/../projekt/';
$groups = [];
$rootProjects = [];

if (!is_dir($projectDir)) {
    mkdir($projectDir, 0755, true);
}

$items = scandir($projectDir);
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = $projectDir . $item;
    
    if (is_dir($path)) {
        $isProject = false;
        if (file_exists($path . '/tour.html') || file_exists($path . '/tour.xml')) {
            $isProject = true;
        }

        if ($isProject) {
            $rootProjects[] = $item;
        } else {
            $subItems = scandir($path);
            $groupProjects = [];
            foreach ($subItems as $sub) {
                if ($sub === '.' || $sub === '..') continue;
                if (is_dir($path . '/' . $sub)) {
                    $groupProjects[] = $sub;
                }
            }
            $groups[$item] = $groupProjects;
        }
    }
}

// Helper to parse PHP size shorthand
function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

$maxUpload = parse_size(ini_get('upload_max_filesize'));
$maxPost = parse_size(ini_get('post_max_size'));
$maxBytes = min($maxUpload, $maxPost);
$maxMb = floor($maxBytes / 1024 / 1024);

// Load Auth Data
$authFile = __DIR__ . '/project_auth_data.php';
$authData = [];
if (file_exists($authFile)) {
    $authData = require $authFile;
}

echo json_encode([
    'success' => true,
    'groups' => $groups,
    'rootProjects' => $rootProjects,
    'authData' => $authData,
    'stats' => [
        'maxUploadMb' => $maxMb,
        'phpVersion' => PHP_VERSION,
        'memoryLimit' => ini_get('memory_limit'),
        'maxExecutionTime' => ini_get('max_execution_time')
    ]
]);
