<?php
// api/dashboard.php
require_once 'auth_check.php';
require_once 'data_helper.php';

header('Content-Type: application/json');

$projectDir = __DIR__ . '/../projekt/';
if (!is_dir($projectDir)) {
    mkdir($projectDir, 0755, true);
}

// Load Projects Data
$projectsData = loadProjects();

// Iterate over 'groups' in projectsData (Key is ID now)
$groups = [];
if (isset($projectsData['groups'])) {
    foreach ($projectsData['groups'] as $groupId => $groupInfo) {
        $pjs = [];
        if (isset($groupInfo['projects'])) {
            foreach ($groupInfo['projects'] as $pName => $pData) {
                if (isset($pData['folder'])) {
                    $pjs[] = [
                        'name' => $pName,
                        'groupId' => $groupId, // Use key as ID
                        'folder' => $pData['folder'],
                        'token' => $pData['token'] ?? null,
                        'created' => $pData['created'] ?? null
                    ];
                }
            }
        }
        
        // New Response Structure: Key = ID. Value = Object with name & projects.
        $groups[$groupId] = [
            'name' => $groupInfo['name'] ?? $groupId, // Fallback if name missing
            'projects' => $pjs,
            'token' => $groupInfo['token'] ?? null,
            'created' => $groupInfo['created'] ?? null
        ];
    }
}

// Polyfill 'authData'
$authData = [];
if (isset($projectsData['groups'])) {
    foreach ($projectsData['groups'] as $gId => $gInfo) {
        if (isset($gInfo['token'])) {
            $authData[$gId] = [
                'token' => $gInfo['token'],
                'created' => $gInfo['created'] ?? ''
            ];
             // Register Name too for safety
             if (isset($gInfo['name'])) {
                 $authData[$gInfo['name']] = $authData[$gId];
             }
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

echo json_encode([
    'success' => true,
    'groups' => $groups,
    'rootProjects' => [], // Empty/Deprecated
    'authData' => $authData,
    'stats' => [
        'maxUploadMb' => $maxMb,
        'phpVersion' => PHP_VERSION,
        'memoryLimit' => ini_get('memory_limit'),
        'maxExecutionTime' => ini_get('max_execution_time')
    ]
]);
