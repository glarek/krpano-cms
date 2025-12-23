<?php
// api/data_helper.php

// Define the path to the projects data file
if (!defined('PROJECTS_DATA_FILE')) {
    // Use getStoragePath() but define it later? 
    // Problem: getStoragePath is defined below. 
    // PHP functions are hoisted, but constants? 
    // Constants are defined at runtime. 
    // We should probably move the define INSIDE loadProjects or just use a function.
    // Or just call getStoragePath() here? Yes, functions are hoisted.
    define('PROJECTS_DATA_FILE', getConfigPath() . '/projects_data.php');
}

/**
 * Loads the projects data array.
 * Structure: ['GroupName' => ['projects' => ['ProjectName' => ['folder' => 'p_xyz', ...]]]]
 * Or flat? The user example implied simple slug keys, but hierarchy exists.
 * We'll use: ['GroupName' => ['ProjectName' => [...data...]]] to keep it organized by group.
 */
function loadProjects() {
    if (file_exists(PROJECTS_DATA_FILE)) {
        return include PROJECTS_DATA_FILE;
    }
    return [];
}

/**
 * Saves the projects data array to the file.
 */
function saveProjects($data) {
    $content = "<?php\nreturn " . var_export($data, true) . ";\n";
    if (file_put_contents(PROJECTS_DATA_FILE, $content, LOCK_EX) === false) {
        // Error handling if needed?
    }
    // Invalidate OpCache to ensure next read gets fresh data
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate(PROJECTS_DATA_FILE, true);
    }
}

/**
 * Generates a unique folder ID (p_ + 4 random bytes hex).
 * Checks against existing folder values in the data.
 */
function generateUniqueProjectId($data) {
    // Structure: ['groups' => ['g_xyz' => ['name' => 'Name', 'projects' => ['PName' => ['folder' => 'p_x']]]]]
    $usedFolders = [];
    if (isset($data['groups'])) {
        foreach ($data['groups'] as $groupId => $gData) {
            if (isset($gData['projects'])) {
                foreach ($gData['projects'] as $p) {
                    if (isset($p['folder'])) $usedFolders[] = $p['folder'];
                }
            }
        }
    }

    do {
        $newId = "p_" . bin2hex(random_bytes(4));
    } while (in_array($newId, $usedFolders));

    return $newId;
}

/**
 * Generates a unique group ID (g_ + 4 random bytes hex).
 */
function generateUniqueGroupId($data) {
    // Keys in 'groups' ARE the IDs now.
    $usedIds = [];
    if (isset($data['groups'])) {
        $usedIds = array_keys($data['groups']);
    }

    do {
        $newId = "g_" . bin2hex(random_bytes(4));
    } while (in_array($newId, $usedIds));

    return $newId;
}

/**
 * Generates a random token (8 bytes hex).
 */
function generateToken() {
    return bin2hex(random_bytes(8));
}

/**
 * Resolves the secure storage path dynamically.
 * Checks:
 * 1. ../secure_projects (Local Development)
 * 2. ../../secure_projects (Production - Sibling to public_html)
 */
function getStoragePath() {
    // In the new structure, api is in public_html/api
    // secure_projects is in root/secure_projects
    // So distinct path is ../../secure_projects
    return realpath(__DIR__ . '/../../secure_projects');
}

/**
 * Resolves the secure config path dynamically.
 * Checks:
 * 1. ../secure_config (Local Development)
 * 2. ../../secure_config (Production - Sibling to public_html)
 */
function getConfigPath() {
    // In the new structure, api is in public_html/api
    // secure_config is in root/secure_config
    // So distinct path is ../../secure_config
    return realpath(__DIR__ . '/../../secure_config');
}
