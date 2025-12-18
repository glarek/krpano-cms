<?php
require_once 'auth_check.php';

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
        // Check if this is a "Root Project" (Legacy) - contains tour.html directly
        // Or if it is a "Group" - contains sub-folders
        
        $isProject = false;
        if (file_exists($path . '/tour.html') || file_exists($path . '/tour.xml')) {
            $isProject = true;
        }

        if ($isProject) {
            $rootProjects[] = $item;
        } else {
            // Treat as Group, scan for sub-projects
            $subItems = scandir($path);
            $groupProjects = [];
            foreach ($subItems as $sub) {
                if ($sub === '.' || $sub === '..') continue;
                if (is_dir($path . '/' . $sub)) {
                    $groupProjects[] = $sub;
                }
            }
            // Even empty folders are groups
            $groups[$item] = $groupProjects;
        }
    }
}

// Helper to parse PHP size shorthand (e.g., '10M')
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

$memoryLimit = ini_get('memory_limit');
$maxExecutionTime = ini_get('max_execution_time');
$maxInputTime = ini_get('max_input_time');

// Load Auth Data for Tokens in Links
$authFile = __DIR__ . '/project_auth_data.php';
$authData = [];
if (file_exists($authFile)) {
    $authData = require $authFile;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krpano CMS Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: dark; 
            --primary: oklch(0.6 0.2 255.45);
            --primary-hover: oklch(0.55 0.2 255.45);
            --bg-color: #0B0F19;

            --card-bg: rgba(255, 255, 255, 0.03);
            --text-color: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.5);
            --border-color: rgba(255, 255, 255, 0.1);
            --danger-color: #ef4444;
            --success-color: #10b981;
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-border: rgba(255, 255, 255, 0.1);
            --input-focus: rgba(255, 255, 255, 0.15);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 2rem;
            line-height: 1.5;
            min-height: 100vh;
            position: relative;
        }

        /* Ambient Glow */
        .glow {
            position: absolute;
            width: 800px;
            height: 800px;
            background: var(--primary);
            opacity: 0.06;
            filter: blur(120px);
            border-radius: 50%;
            z-index: -1;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        h1 { font-weight: 600; margin: 0; font-size: 1.5rem; letter-spacing: -0.02em; }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover { background-color: var(--primary-hover); }
        
        .btn-danger { background-color: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-danger:hover { background-color: rgba(239, 68, 68, 0.3); color: white; }
        
        .btn-success { background-color: var(--success-color); color: white; }
        
        .btn-outline { border: 1px solid var(--border-color); color: var(--text-color); background: transparent; }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.3); }
        
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Stats Grid - Concentrated */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Upload Section - Concentrated & Clean */
        .upload-section {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            backdrop-filter: blur(5px);
        }
        
        .upload-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .upload-header h3 { margin: 0; font-size: 1.1rem; font-weight: 500; }

        .info-box {
            background: rgba(59, 130, 246, 0.1); 
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #93c5fd; 
            padding: 0.75rem; 
            border-radius: 8px; 
            font-size: 0.85rem;
            line-height: 1.4;
        }
        .info-box code { background: rgba(0,0,0,0.3); padding: 2px 4px; border-radius: 4px; color: white; }

        form#uploadForm {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        input[type="file"] {
            background: var(--input-bg);
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            flex-grow: 1;
            font-size: 0.9rem;
            cursor: pointer;
        }
        input[type="file"]::file-selector-button {
            display: none; /* Hide default ugly button */
        }
        /* Custom file input text logic would be cleaner but let's keep it simple style */
        input[type="file"]:hover {
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
        }

        /* Progress & Spinner */
        .progress-wrapper {
            margin-top: 1rem;
            display: none;
            background: rgba(0,0,0,0.2);
            padding: 1rem;
            border-radius: 12px;
        }
        .progress-bar {
            width: 100%;
            height: 6px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        .progress-fill {
            height: 100%;
            background-color: var(--primary);
            width: 0%;
            transition: width 0.2s ease;
        }
        .progress-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: right;
        }
        
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        #status-message {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Table */
        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0 0.5rem; 
        }
        
        thead th {
            text-align: left;
            padding: 0.75rem 1rem;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
        }
        
        tbody tr {
            background: var(--card-bg);
            transition: background 0.2s;
        }
        tbody tr:hover {
            background: rgba(255, 255, 255, 0.06);
        }
        
        td { 
            padding: 1rem; 
            vertical-align: middle;
            color: white;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }
        td:first-child { border-left: 1px solid var(--border-color); border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        td:last-child { border-right: 1px solid var(--border-color); border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

        .actions { display: flex; gap: 0.5rem; justify-content: flex-end; }
        .empty-state { text-align: center; padding: 4rem; color: var(--text-muted); font-style: italic; background: transparent !important; }

        /* Modals */
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter: blur(5px); align-items:center; justify-content:center; z-index: 100; }
        .modal.active { display: flex; }
        .modal-content { 
            background: #111827; 
            padding: 2rem; 
            border-radius: 16px; 
            width: 400px; 
            border: 1px solid var(--border-color); 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .modal-title { margin-top:0; margin-bottom: 1.5rem; font-weight: 500; font-size: 1.25rem; }
        
        .modal input[type="text"] {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: white;
            font-family: inherit;
            box-sizing: border-box;
        }
        .modal input[type="text"]:focus { outline: none; border-color: var(--primary); }
        
        /* Select Dropdown Styling */
        select option {
            background-color: #0B0F19; /* Hardcoded dark hex for safety */
            color: white;
        }
        
        .custom-select {
            width: 100%;
            padding: 0.5rem;
            border-radius: 8px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: white;
            color-scheme: dark; /* CRITICAL: Forces the dropdown menu to be dark */
        }
        .custom-select:focus { outline: none; border-color: var(--primary); }

        /* Group Styling */
        .group-section { margin-bottom: 1.5rem; }

        .group-header {
            background: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 8px; /* Simple radius, simpler is cleaner */
            /* margin-bottom removed to connect with content */
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }
        .group-header:hover { background: rgba(255,255,255,0.08); }
        .group-title { font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .group-content { 
            margin-left: 1.5rem; 
            border-left: 2px solid var(--border-color); 
            padding-left: 1rem;
            padding-top: 0.5rem; 
            /* margin-bottom handled by group-section */
            display: none; 
        }
        .group-content.open { display: block; }
        .project-row {
            background: var(--card-bg);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>

<div class="glow"></div>

<header>
    <h1>Krpano CMS</h1>
    <div style="display:flex; gap:0.5rem;">
        <a href="logout.php" class="btn btn-outline" style="font-size: 0.8rem;">Log Out</a>
    </div>
</header>

<div class="container">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $maxMb ?> MB</div>
            <div class="stat-label">Max Upload</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $memoryLimit ?></div>
            <div class="stat-label">Memory</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $maxExecutionTime ?>s</div>
            <div class="stat-label">Timeout</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= PHP_VERSION ?></div>
            <div class="stat-label">PHP</div>
        </div>
    </div>

    <div class="upload-section">
        <div class="upload-header">
            <h3>Ladda upp nytt projekt</h3>
        </div>
        
        <div class="info-box">
             ZIP-filen ska innehålla <code>tour.html</code>. Mappen får ZIP-filens namn.
        </div>

        <form id="uploadForm" enctype="multipart/form-data" style="flex-direction:column; align-items:stretch;">
            
            <div style="display:flex; gap:1rem; margin-bottom:1rem; align-items:flex-end;">
                <div style="flex:1;" id="group_select_container">
                    <label style="display:block; font-size:0.8rem; margin-bottom:0.3rem; color:var(--text-muted);">Välj Projektgrupp</label>
                    <select name="group_select" id="group_select" class="custom-select">
                        <option value="" disabled selected>-- Välj Grupp --</option>
                        <?php foreach ($groups as $gName => $gProjects): ?>
                            <option value="<?= htmlspecialchars($gName) ?>"><?= htmlspecialchars($gName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button type="button" class="btn btn-outline" style="padding: 0.5rem 1rem;" onclick="openCreateGroupModal()">+ Ny Grupp</button>
                </div>
            </div>

            <div style="display:flex; gap:1rem;">
                <input type="file" name="zipfile" id="zipfile" required accept=".zip">
                <button type="submit" class="btn btn-primary" id="uploadBtn" style="padding: 0.5rem 1.5rem;">Ladda upp</button>
            </div>
        </form>
        
        <div class="progress-wrapper" id="progressWrapper">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <div class="spinner" id="loadingSpinner"></div>
                    <div id="status-message"></div>
                </div>
                <div class="progress-text" id="progressText">0%</div>
            </div>
        </div>
    </div>

    <!-- Groups List -->
    <div id="project-list">
        <?php if (empty($groups) && empty($rootProjects)): ?>
            <div class="empty-state">Inga projekt eller grupper uppladdade än.</div>
        <?php else: ?>
            
            <!-- Groups -->
            <?php foreach ($groups as $groupName => $projects): ?>
            <div class="group-section">
                <!-- Group Header -->
                <?php 
                    // Check token availability
                    $token = isset($authData[$groupName]['token']) ? $authData[$groupName]['token'] : null;
                    
                    // Prepare Group Open Link
                    $groupOpenLink = "../projekt/" . htmlspecialchars($groupName) . "/";
                    if ($token) {
                        $groupOpenLink .= "?token=" . $token;
                    }
                ?>
                <div class="group-header" onclick="toggleGroup('<?= htmlspecialchars($groupName) ?>')">
                    <div class="group-title">
                        <span style="font-size:1.2rem;">
                            <?= $token ? '🔒' : '📁' ?>
                        </span> 
                        <?= htmlspecialchars($groupName) ?> 
                        <span style="font-size:0.8rem; color:var(--text-muted); font-weight:normal; margin-left:0.5rem; background:rgba(255,255,255,0.1); padding:2px 8px; border-radius:12px;"><?= count($projects) ?> projekt</span>
                    </div>
                    <div class="actions" onclick="event.stopPropagation()" style="display:flex; align-items:center;">
                        <a href="<?= $groupOpenLink ?>" target="_blank" class="btn btn-outline" style="font-size:0.8rem; padding: 0.3rem 0.8rem; margin-right:5px; text-decoration:none;">Öppna</a>
                        <button onclick="openShareModal('<?= htmlspecialchars($groupName) ?>')" class="btn btn-outline" style="font-size:0.8rem; padding: 0.3rem 1rem; margin-right:5px;">🔒 Hantera Åtkomst</button>
                        <a href="delete.php?group=<?= urlencode($groupName) ?>" onclick="return confirm('VARNING: Detta raderar HELA gruppen och ALLA dess projekt.\n\nÄr du helt säker?')" class="btn btn-danger" style="font-size:0.8rem; padding: 0.3rem 0.8rem; text-decoration:none;">Radera</a>
                    </div>
                </div>

                        <!-- Projects in Group -->
                        <div class="group-content" id="group-<?= htmlspecialchars($groupName) ?>">
                            <?php if (empty($projects)): ?>
                                <div style="padding:1rem; color:var(--text-muted); font-style:italic;">Inga projekt i denna grupp än.</div>
                            <?php else: ?>
                                <?php foreach ($projects as $proj): ?>
                                <?php 
                                    $openLink = "../projekt/" . htmlspecialchars($groupName) . "/" . htmlspecialchars($proj) . "/tour.html";
                                    if ($token) {
                                        $openLink .= "?token=" . $token;
                                    }
                                ?>
                                <div class="project-row">
                                    <div style="font-weight:500;">
                                        📄 <?= htmlspecialchars($proj) ?>
                                    </div>
                                    <div class="actions">
                                        <a href="<?= $openLink ?>" target="_blank" class="btn btn-primary" style="font-size:0.8rem;">Öppna</a>
                                        
                                        <button onclick="openMoveModal('<?= htmlspecialchars($groupName) ?>', '<?= htmlspecialchars($proj) ?>')" class="btn btn-outline" style="font-size:0.8rem;">Flytta</button>
                                        <button onclick="openRenameModal('<?= htmlspecialchars($groupName) ?>', '<?= htmlspecialchars($proj) ?>')" class="btn btn-outline" style="font-size:0.8rem;">Byt namn</button>
                                        
                                        <a href="delete.php?group=<?= urlencode($groupName) ?>&project=<?= urlencode($proj) ?>" onclick="return confirm('Är du säker på att du vill radera <?= htmlspecialchars($proj) ?>?')" class="btn btn-danger" style="font-size:0.8rem;">Radera</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
    </div>
</div>

    <!-- Rename Modal -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Byt namn</h3>
            <form action="rename.php" method="POST">
                <input type="hidden" name="group" id="rename_group_input">
                <input type="hidden" name="old_name" id="rename_old_name_input">
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Nytt namn:</label>
                    <input type="text" name="new_name" id="rename_new_name_input" style="width:100%; padding:0.5rem;" required>
                </div>
                <div style="text-align:right; gap:0.5rem; display:flex; justify-content:flex-end;">
                    <button type="button" onclick="closeRenameModal()" class="btn btn-outline">Avbryt</button>
                    <button type="submit" class="btn btn-primary">Spara</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Move Modal -->
    <div id="moveModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Flytta Projekt</h3>
            <form action="move.php" method="POST">
                <input type="hidden" name="project" id="move_project_input">
                <input type="hidden" name="current_group" id="move_current_group_input">
                
                <p id="move-project-display" style="color:var(--text-muted); margin-bottom:1rem;"></p>

                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Välj Ny Grupp:</label>
                    <select name="target_group" style="width:100%; padding:0.75rem; border-radius:8px; background:var(--input-bg); border:1px solid var(--border-color); color:white;">
                        <?php foreach ($groups as $gName => $p): ?>
                            <option value="<?= htmlspecialchars($gName) ?>"><?= htmlspecialchars($gName) ?></option>
                        <?php endforeach; ?>
                        <option value="NEW">+ Skapa Ny Grupp</option>
                    </select>
                </div>

                 <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem; color:var(--text-muted); font-size:0.85rem;">Eller skriv namn för ny grupp:</label>
                    <input type="text" name="new_group_name" placeholder="T.ex. Arkiv" style="width:100%;">
                </div>

                <div style="text-align:right; gap:0.5rem; display:flex; justify-content:flex-end;">
                    <button type="button" onclick="closeMoveModal()" class="btn btn-outline">Avbryt</button>
                    <button type="submit" class="btn btn-primary">Flytta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div id="createGroupModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Skapa Ny Projektgrupp</h3>
            <div>
                <label style="display:block; margin-bottom:0.5rem;">Gruppnamn:</label>
                <input type="text" id="create_group_name_input" placeholder="T.ex. Fastigheter 2024" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
                <div id="create-group-msg" style="margin-bottom:1rem;"></div>
            </div>
            <div style="text-align:right; gap:0.5rem; display:flex; justify-content:flex-end;">
                <button onclick="closeCreateGroupModal()" class="btn btn-outline">Avbryt</button>
                <button onclick="createGroup()" class="btn btn-primary">Spara</button>
            </div>
        </div>
    </div>

    <!-- Share/Security Modal -->
    <div id="shareModal" class="modal">
        <div class="modal-content" style="width: 500px;">
            <h3 class="modal-title">Dela Projektgrupp</h3>
            <p id="share-project-name" style="color:#666; margin-bottom:1.5rem; font-weight:bold;"></p>
            <p style="font-size:0.85rem; color: #888; margin-bottom:1rem;">Obs: Detta skyddar <strong>hela gruppen</strong>. Alla projekt i denna mapp kommer kräva samma länk.</p>
            
            <div id="share-loading" style="text-align:center; padding:1rem;">Laddar...</div>
            
            <!-- State: Not Protected -->
            <div id="share-unprotected" style="display:none; text-align:center; padding:1rem 0;">
                <p>Denna grupp är öppen för alla.</p>
                <button onclick="generateToken()" class="btn btn-success" style="padding: 10px 20px;">Generera säker länk</button>
            </div>

            <!-- State: Protected -->
            <div id="share-protected" style="display:none;">
                <div style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:4px; margin-bottom:1.5rem; border:1px solid var(--border-color);">
                    <label style="display:block; font-size:0.8rem; color:var(--primary); font-weight:600; margin-bottom:0.5rem;">UNIK LÄNK TILL GRUPPEN</label>
                    <div style="display:flex; gap:0.5rem;">
                        <input type="text" id="share-link-input" readonly style="width:100%; padding:0.5rem; background:rgba(0,0,0,0.3); border:1px solid var(--border-color); font-family:monospace; color:var(--success-color);">
                        <button onclick="copyLink()" class="btn btn-primary">Kopiera</button>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; border-top:1px solid var(--border-color); padding-top:1rem;">
                    <button onclick="generateToken()" class="btn btn-outline" style="font-size:0.8rem;">Generera ny länk (Ogiltigförklara gammal)</button>
                    <button onclick="deleteToken()" class="btn btn-danger" style="font-size:0.8rem;">Ta bort skydd</button>
                </div>
            </div>

            <div style="text-align:right; margin-top:1.5rem;">
                <button onclick="closeShareModal()" class="btn btn-outline">Stäng</button>
            </div>
        </div>
    </div>

    <script>
        // Group Logic
        function toggleGroup(groupName) {
            const content = document.getElementById('group-' + groupName);
            content.classList.toggle('open');
        }

        // Create Group Modal Logic
        const createGroupModal = document.getElementById('createGroupModal');
        
        function openCreateGroupModal() {
            document.getElementById('create_group_name_input').value = '';
            document.getElementById('create-group-msg').innerText = '';
            createGroupModal.classList.add('active');
            setTimeout(() => document.getElementById('create_group_name_input').focus(), 100);
        }

        function closeCreateGroupModal() {
            createGroupModal.classList.remove('active');
        }

        function createGroup() {
            const nameInput = document.getElementById('create_group_name_input');
            const msgDiv = document.getElementById('create-group-msg');
            const groupName = nameInput.value;

            if(!groupName.trim()) {
                msgDiv.innerText = "Ange ett namn.";
                msgDiv.style.color = "var(--danger-color)";
                return;
            }

            msgDiv.innerText = "Skapar...";
            msgDiv.style.color = "var(--text-muted)";

            const formData = new FormData();
            formData.append('group_name', groupName);

            fetch('create_group.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        msgDiv.innerText = "Klart! Laddar om...";
                        msgDiv.style.color = "var(--success-color)";
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        msgDiv.innerText = "Fel: " + data.message;
                        msgDiv.style.color = "var(--danger-color)";
                    }
                })
                .catch(err => {
                    msgDiv.innerText = "Nätverksfel.";
                    msgDiv.style.color = "var(--danger-color)";
                });
        }
        
        window.onclick = function(event) {
            if (event.target == renameModal) closeRenameModal();
            if (event.target == shareModal) closeShareModal();
            if (event.target == moveModal) closeMoveModal();
            if (event.target == createGroupModal) closeCreateGroupModal();
        }

        // Modal Logic
        const renameModal = document.getElementById('renameModal');
        const shareModal = document.getElementById('shareModal');
        const moveModal = document.getElementById('moveModal');
        let currentProject = ''; // Effectively "Current Group" for Auth

        function openRenameModal(group, name) {
            document.getElementById('rename_group_input').value = group;
            document.getElementById('rename_old_name_input').value = name;
            document.getElementById('rename_new_name_input').value = name;
            renameModal.classList.add('active');
        }
        function closeRenameModal() {
            renameModal.classList.remove('active');
        }

        function openMoveModal(group, project) {
            document.getElementById('move_project_input').value = project;
            document.getElementById('move_current_group_input').value = group;
            document.getElementById('move-project-display').innerText = `Flytta "${project}" från "${group}" till...`;
            moveModal.classList.add('active');
        }
        function closeMoveModal() {
            moveModal.classList.remove('active');
        }
        
        // Share Modal Functions (Now for Groups)
        function openShareModal(groupName) {
            currentProject = groupName; // We treat Group Name as the "Project" key in auth_data
            document.getElementById('share-project-name').innerText = groupName;
            shareModal.classList.add('active');
            fetchTokenStatus();
        }

        function closeShareModal() {
            shareModal.classList.remove('active');
        }

        function fetchTokenStatus() {
            document.getElementById('share-loading').style.display = 'block';
            document.getElementById('share-unprotected').style.display = 'none';
            document.getElementById('share-protected').style.display = 'none';

            const formData = new FormData();
            formData.append('project_name', currentProject);
            formData.append('action', 'get');

            fetch('generate_token.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('share-loading').style.display = 'none';
                    if (data.token) {
                        showProtected(data.token, data.created);
                    } else {
                        document.getElementById('share-unprotected').style.display = 'block';
                    }
                })
                .catch(err => {
                    document.getElementById('share-loading').style.display = 'none';
                    console.error("Fetch Error:", err);
                });
        }

        function showProtected(token, created) {
            // Group Link: /projekt/GroupName/
            const baseUrl = window.location.href.split('/admin/')[0] + '/projekt/';
            const fullLink = baseUrl + encodeURIComponent(currentProject) + '/?token=' + token;
            
            document.getElementById('share-link-input').value = fullLink;
            // document.getElementById('share-created-at').innerText = created;
            document.getElementById('share-protected').style.display = 'block';
            document.getElementById('share-unprotected').style.display = 'none';
        }

        function generateToken() {
            if(!confirm("Vill du generera en ny länk för hela gruppen?")) return;
            performTokenAction('generate');
        }
        function deleteToken() {
            if(!confirm("Vill du ta bort skyddet för hela gruppen?")) return;
            performTokenAction('delete');
        }

        function performTokenAction(action) {
            const formData = new FormData();
            formData.append('project_name', currentProject);
            formData.append('action', action);

            // Debug feedback
            const btn = document.activeElement;
            if(btn) {
                btn.disabled = true;
                btn.innerText = "Bearbetar...";
            }

            fetch('generate_token.php', { method: 'POST', body: formData })
                .then(response => response.text()) // Get text first to debug JSON errors
                .then(text => {
                    console.log("Server Response:", text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error("Ogiltigt serversvar: " + text.substring(0, 100));
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Success - Reload page to update all "Open" links
                        // Add delay to ensure filesystem catches up
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    } else {
                        alert("Fel: " + data.message);
                        if(btn) {
                            btn.disabled = false;
                            btn.innerText = "Försök igen";
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Ett fel inträffade: " + err.message);
                    if(btn) {
                        btn.disabled = false;
                        btn.innerText = "Försök igen";
                    }
                });
        }

        function copyGroupLink(groupName, token) {
            const baseUrl = window.location.href.split('/admin/')[0] + '/projekt/';
            let fullLink = baseUrl + encodeURIComponent(groupName) + '/';
            
            if (token && token.length > 0) {
                fullLink += '?token=' + token;
            }

            navigator.clipboard.writeText(fullLink).then(() => {
                const btn = event.target.closest('button'); 
                const originalText = btn.innerText;
                
                btn.innerText = '✅';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline');
                
                setTimeout(() => {
                    btn.innerText = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline');
                }, 2000);
            }).catch(err => {
                alert("Kunde inte kopiera länk automatically. \n" + fullLink);
            });
        }

        function copyLink() {
            const copyText = document.getElementById("share-link-input");
            copyText.select();
            copyText.setSelectionRange(0, 99999); 
            navigator.clipboard.writeText(copyText.value).then(() => {
                alert("Länk kopierad!");
            });
        }

        window.onclick = function(event) {
            if (event.target == renameModal) closeRenameModal();
            if (event.target == shareModal) closeShareModal();
            if (event.target == moveModal) closeMoveModal();
        }

        // Upload Logic
        const form = document.getElementById('uploadForm');
        const fileInput = document.getElementById('zipfile');
        const uploadBtn = document.getElementById('uploadBtn');
        const progressWrapper = document.getElementById('progressWrapper');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        const statusMessage = document.getElementById('status-message');
        const loadingSpinner = document.getElementById('loadingSpinner');

        // Server limits from PHP
        const MAX_BYTES = <?= $maxBytes ?>;
        const MAX_MB = <?= $maxMb ?>;

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > MAX_BYTES) {
                    alert(`Filen är för stor! \nDin fil: ${(file.size / 1024 / 1024).toFixed(2)} MB\nMax tillåtet: ${MAX_MB} MB\n\nKontrollera 'upload_max_filesize' i din serverkonfiguration.`);
                    this.value = ''; // Clear input
                    uploadBtn.disabled = true;
                    statusMessage.innerText = '';
                    return;
                } else {
                    uploadBtn.disabled = false;
                }
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const file = fileInput.files[0];
            if (!file) return;

            if (file.size > MAX_BYTES) {
                alert(`Filen är för stor (${(file.size / 1024 / 1024).toFixed(2)} MB). Max är ${MAX_MB} MB.`);
                return;
            }

            // Check group selection
            const groupSelect = document.getElementById('group_select');
            const targetGroup = groupSelect.value;
            
            if (!targetGroup) {
                alert("Välj en grupp.");
                return;
            }

            // Reset UI
            uploadBtn.disabled = true;
            progressWrapper.style.display = 'block';
            loadingSpinner.style.display = 'none';
            progressFill.style.width = '0%';
            progressText.innerText = '0%';
            statusMessage.innerText = 'Laddar upp...';
            statusMessage.style.color = 'var(--text-muted)';

            const formData = new FormData();
            formData.append('zipfile', file);
            formData.append('group_mode', 'EXISTING');
            formData.append('target_group', targetGroup);

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressFill.style.width = percent + '%';
                    progressText.innerText = percent + '%';
                    
                    if (percent === 100) {
                        statusMessage.innerText = 'Packar upp filer... (Detta kan ta en stund)';
                        loadingSpinner.style.display = 'block';
                    }
                }
            });

            xhr.addEventListener('load', function() {
                uploadBtn.disabled = false;
                loadingSpinner.style.display = 'none';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        statusMessage.innerText = response.message;
                        statusMessage.style.color = 'var(--success-color)';
                        setTimeout(() => window.location.reload(), 1000); 
                    } else {
                        statusMessage.innerText = 'Fel: ' + response.message;
                        statusMessage.style.color = 'var(--danger-color)';
                    }
                } catch (e) {
                   console.error(e);
                   statusMessage.innerText = 'Serverfel (JSON)';
                   statusMessage.style.color = 'var(--danger-color)';
                }
            });

            xhr.addEventListener('error', function() {
                uploadBtn.disabled = false;
                statusMessage.innerText = 'Nätverksfel';
            });

            xhr.open('POST', 'upload.php', true);
            xhr.send(formData);
        });
    </script>


</body>
</html>
