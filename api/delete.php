<?php
// api/delete.php
require_once 'auth_check.php';
require_once 'data_helper.php';

header('Content-Type: application/json');

$project = null;
$group = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $project = $input['project'] ?? null;
        $group = $input['group'] ?? null;
    } else {
        $project = $_POST['project'] ?? null;
        $group = $_POST['group'] ?? null;
    }
} else {
     http_response_code(405);
     echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use POST.']);
     exit;
}

$baseDir = __DIR__ . '/../projekt/';
$projectsData = loadProjects();

// Helper for finding path
function findPathToDelete($base, $name) {
    if (is_dir($base . $name)) return $base . $name;
    if (is_dir($base . rawurlencode($name))) return $base . rawurlencode($name);
    return null;
}

function forceDelete($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) {
        @chmod($dir, 0777);
        return @unlink($dir);
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        $path = $file->getRealPath();
        @chmod($path, 0777);
        if ($file->isDir()) {
            if (!@rmdir($path)) return false;
        } else {
            if (!@unlink($path)) return false;
        }
    }
    @chmod($dir, 0777);
    return @rmdir($dir);
}

$targetDir = null;

if ($group && $project) {
    // Delete Project
    if (isset($projectsData['groups'][$group]['projects'][$project])) {
        // Modern Project
        $info = $projectsData['groups'][$group]['projects'][$project];
        $groupId = $group;
        
        $targetDir = __DIR__ . '/../projekt/' . $groupId . '/' . $info['folder'];
        
        // Remove from array immediately? Or after delete success?
        // Let's assume delete success logic below handles array cleanup via flags or we do it after.
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Projektet hittades inte i registret."]);
        exit;
    }

} elseif ($group && empty($project)) {
    // Delete Group
    if (isset($projectsData['groups'][$group])) {
        $groupId = $group;
        $targetDir = __DIR__ . '/../projekt/' . $groupId;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Gruppen hittades inte i registret."]);
        exit;
    }

} elseif ($project) {
     http_response_code(400);
     echo json_encode(['success' => false, 'message' => "Operation 'Root Project Delete' is no longer supported."]);
     exit;

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Missing 'group' or 'project' parameter."]);
    exit;
}

if ($targetDir) {
    // Check if exists
    if (!file_exists($targetDir)) {
          // If array said it exists but disk says no, we should clean array?
          // For now let's just proceed to clean array.
          // But strict mode might want to report error?
          // Let's try to delete if exists.
    }
}

// Perform Delete
$deleted = false;
if ($targetDir && file_exists($targetDir)) {
    for ($i = 0; $i < 3; $i++) {
        if (forceDelete($targetDir)) {
            $deleted = true;
            break;
        }
        usleep(100000);
    }
} else {
    // If not found on disk, assume deleted or inconsistent.
    $deleted = true; 
}

if ($deleted) {
    // Clean up Data Files
    if ($group && $project) {
        if (isset($projectsData['groups'][$group]['projects'][$project])) {
            unset($projectsData['groups'][$group]['projects'][$project]);
            saveProjects($projectsData);
        }
    } elseif ($group && empty($project)) {
        if (isset($projectsData['groups'][$group])) {
            unset($projectsData['groups'][$group]);
            saveProjects($projectsData);
        }
        // Legacy auth file (if exists, kept just in case, but likely obsolete)
    }
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Kunde inte ta bort mappen."]);
}
