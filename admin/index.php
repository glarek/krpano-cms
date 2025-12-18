<?php
require_once 'auth_check.php';

$projectDir = __DIR__ . '/../projekt/';
$projects = [];

if (is_dir($projectDir)) {
    $scanned = scandir($projectDir);
    foreach ($scanned as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir($projectDir . $item)) {
            $projects[] = $item;
        }
    }
} else {
    mkdir($projectDir, 0755, true);
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
             ZIP-filen ska innehålla <code>tour.html</code> m.fl. <strong>direkt</strong> i roten. Mappen på servern får ZIP-filens namn.
        </div>

        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" name="zipfile" id="zipfile" required accept=".zip">
            <button type="submit" class="btn btn-primary" id="uploadBtn" style="padding: 0.5rem 1.5rem;">Ladda upp</button>
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

    <table>
        <thead>
            <tr>
                <th>Projekt</th>
                <th style="width: 300px;">Åtgärder</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)): ?>
                <tr>
                    <td colspan="2" class="empty-state">Inga projekt uppladdade än.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($projects as $proj): ?>
                <tr>
                    <td><?= htmlspecialchars($proj) ?></td>
                    <td>
                        <div class="actions">
                            <a href="../projekt/<?= urlencode($proj) ?>/tour.html" target="_blank" class="btn btn-primary">Öppna</a>
                            <button data-project="<?= htmlspecialchars($proj) ?>" onclick="openShareModal(this.dataset.project)" class="btn btn-outline" style="min-width: 80px;">Dela</button>
                            <button data-project="<?= htmlspecialchars($proj) ?>" onclick="openRenameModal(this.dataset.project)" class="btn btn-outline">Byt namn</button>
                            <a href="delete.php?project=<?= urlencode($proj) ?>" onclick="return confirm('Är du säker på att du vill radera <?= htmlspecialchars($proj) ?>?')" class="btn btn-danger">Radera</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <!-- Rename Modal -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Byt namn på projekt</h3>
            <form action="rename.php" method="POST">
                <input type="hidden" name="old_name" id="old_name_input">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Nytt namn:</label>
                    <input type="text" name="new_name" id="new_name_input" style="width:100%; padding:0.5rem;" required>
                </div>
                <div style="text-align:right; gap:0.5rem; display:flex; justify-content:flex-end;">
                    <button type="button" onclick="closeRenameModal()" class="btn btn-outline">Avbryt</button>
                    <button type="submit" class="btn btn-primary">Spara</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Share/Security Modal -->
    <div id="shareModal" class="modal">
        <div class="modal-content" style="width: 500px;">
            <h3 class="modal-title">Dela Projekt</h3>
            <p id="share-project-name" style="color:#666; margin-bottom:1.5rem;"></p>
            
            <div id="share-loading" style="text-align:center; padding:1rem;">Laddar...</div>
            
            <!-- State: Not Protected -->
            <div id="share-unprotected" style="display:none; text-align:center; padding:1rem 0;">
                <p>Detta projekt är öppet för alla.</p>
                <button onclick="generateToken()" class="btn btn-success" style="padding: 10px 20px;">Generera säker länk</button>
                <p style="font-size:0.8rem; color:#999; margin-top:10px;">Detta gör att projektet endast kan ses via den nya länken.</p>
            </div>

            <!-- State: Protected -->
            <div id="share-protected" style="display:none;">
                <div style="background:#f1f9ff; padding:1rem; border-radius:4px; margin-bottom:1.5rem;">
                    <label style="display:block; font-size:0.8rem; color:#007bff; font-weight:600; margin-bottom:0.5rem;">UNIK LÄNK (Använd denna för att dela)</label>
                    <div style="display:flex; gap:0.5rem;">
                        <input type="text" id="share-link-input" readonly style="width:100%; padding:0.5rem; background:white; border:1px solid #ccc; font-family:monospace;">
                        <button onclick="copyLink()" class="btn btn-primary">Kopiera</button>
                    </div>
                    <div style="font-size:0.8rem; color:#666; margin-top:0.5rem;">
                        Skapad: <span id="share-created-at"></span>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; border-top:1px solid #eee; padding-top:1rem;">
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
        // Modal Logic
        const renameModal = document.getElementById('renameModal');
        const shareModal = document.getElementById('shareModal');
        let currentProject = '';

        function openRenameModal(name) {
            document.getElementById('old_name_input').value = name;
            document.getElementById('new_name_input').value = name;
            renameModal.classList.add('active');
        }
        function closeRenameModal() {
            renameModal.classList.remove('active');
        }
        
        // Share Modal Functions
        function openShareModal(projectName) {
            currentProject = projectName;
            document.getElementById('share-project-name').innerText = projectName;
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
                    alert("Ett fel uppstod vid hämtning av status. Kontrollera konsolen.");
                });
        }

        function showProtected(token, created) {
            // Robustly find root by splitting at /admin/
            const baseUrl = window.location.href.split('/admin/')[0] + '/projekt/';
            // Clean URL construction: Base + ProjectName + /tour.html?token=...
            // Note: Project name needs encoding in URL
            const fullLink = baseUrl + encodeURIComponent(currentProject) + '/tour.html?token=' + token;
            
            document.getElementById('share-link-input').value = fullLink;
            document.getElementById('share-created-at').innerText = created;
            document.getElementById('share-protected').style.display = 'block';
            document.getElementById('share-unprotected').style.display = 'none';
        }

        function generateToken() {
            if(!confirm("Vill du generera en ny länk? Om du gör detta kommer den gamla länken att sluta fungera.")) return;
            
            performTokenAction('generate');
        }

        function deleteToken() {
            if(!confirm("Är du säker på att du vill ta bort skyddet? Projektet blir då öppet för alla.")) return;
            
            performTokenAction('delete');
        }

        function performTokenAction(action) {
            const formData = new FormData();
            formData.append('project_name', currentProject);
            formData.append('action', action);

            fetch('generate_token.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (action === 'delete') {
                            document.getElementById('share-protected').style.display = 'none';
                            document.getElementById('share-unprotected').style.display = 'block';
                        } else {
                            showProtected(data.token, data.created);
                        }
                    } else {
                        alert("Fel: " + data.message);
                    }
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
        }

        // Removed Old Settings Save Listener setup safely by replacement


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

        const xhr = new XMLHttpRequest();

        // Progress event
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

        // Load event (complete)
        xhr.addEventListener('load', function() {
            uploadBtn.disabled = false;
            loadingSpinner.style.display = 'none';
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    statusMessage.innerText = response.message;
                    statusMessage.style.color = 'var(--success-color)';
                    setTimeout(() => window.location.reload(), 1500); // Reload after success
                } else {
                    statusMessage.innerText = 'Fel: ' + response.message;
                    statusMessage.style.color = 'var(--danger-color)';
                }
            } catch (e) {
                console.error("JSON Parse Error:", e);
                console.log("Raw Response:", xhr.responseText);
                // Extract a meaningful error if possible, otherwise show raw start
                let rawSnippet = xhr.responseText.substring(0, 200).replace(/</g, "&lt;");
                statusMessage.innerHTML = 'Kunde inte läsa svaret från servern. <br>Troligt fel: " ' + rawSnippet + '..."<br>Kontrollera konsolen (F12) för mer info.';
                statusMessage.style.color = 'var(--danger-color)';
            }
        });

        // Error event
        xhr.addEventListener('error', function() {
            uploadBtn.disabled = false;
            loadingSpinner.style.display = 'none';
            statusMessage.innerText = 'Ett nätverksfel uppstod (XHR Error).';
            statusMessage.style.color = 'var(--danger-color)';
        });

        xhr.open('POST', 'upload.php', true);
        xhr.send(formData);
    });
</script>

</body>
</html>
