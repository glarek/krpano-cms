<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project = trim($_POST['project'] ?? '');
    $baseGroup = trim($_POST['current_group'] ?? '');
    $targetGroup = trim($_POST['target_group'] ?? '');
    
    // Validate Inputs
    if (empty($project) || empty($baseGroup) || empty($targetGroup)) {
        die("Ogiltiga parametrar.");
    }

    $baseDir = __DIR__ . '/../projekt/';
    
    // Sanitize
    $project = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $project);
    $baseGroup = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $baseGroup);

    // Solve Target Group Name
    if ($targetGroup === 'NEW') {
        $newGroup = trim($_POST['new_group_name'] ?? '');
        $newGroup = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $newGroup);
        if (empty($newGroup)) {
            die("Ogiltigt namn för ny grupp.");
        }
        $targetGroup = $newGroup;
        
        // Create if needed
        if (!is_dir($baseDir . $targetGroup)) {
            mkdir($baseDir . $targetGroup, 0755, true);
        }
    } else {
        $targetGroup = preg_replace('/[^a-zA-Z0-9åäöÅÄÖ_-]/u', '', $targetGroup);
        if (!is_dir($baseDir . $targetGroup)) {
            die("Målgruppen finns inte.");
        }
    }

    $sourcePath = $baseDir . $baseGroup . '/' . $project;
    $destPath = $baseDir . $targetGroup . '/' . $project;

    if (!is_dir($sourcePath)) {
        die("Källprojektet hittades inte.");
    }
    
    if (file_exists($destPath)) {
        die("Ett projekt med samma namn finns redan i målgruppen.");
    }

    // Perform Move
    if (rename($sourcePath, $destPath)) {
        // Optional: Check if source group is empty and remove it?
        // Let's keep empty groups for now to avoid confusion.
        
        header('Location: index.php');
        exit;
    } else {
        die("Misslyckades att flytta mappen.");
    }

} else {
    // If accessed directly
    header('Location: index.php');
    exit;
}
