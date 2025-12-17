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
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krpano CMS Dashboard</title>
    <style>
        :root {
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-color: #333333;
            --border-color: #e9ecef;
            --accent-color: #007bff;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 2rem;
            line-height: 1.5;
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

        h1 { font-weight: 300; margin: 0; }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-primary { background-color: var(--accent-color); color: white; }
        .btn-danger { background-color: var(--danger-color); color: white; }
        .btn-success { background-color: var(--success-color); color: white; }
        .btn-outline { border: 1px solid #ccc; color: #555; background: transparent; }
        .btn:disabled { background-color: #ccc; cursor: not-allowed; }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 2rem;
        }

        .upload-section {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 2rem;
            margin-bottom: 2rem;
        }

        .progress-wrapper {
            margin-top: 1rem;
            display: none;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background-color: var(--success-color);
            width: 0%;
            transition: width 0.2s ease;
        }
        .progress-text {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #666;
            text-align: center;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: #666; }
        tr:last-child td { border-bottom: none; }
        
        .actions { display: flex; gap: 0.5rem; }
        .empty-state { text-align: center; padding: 3rem; color: #888; }

        /* Modal styling */
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 8px; width: 400px; }
        .modal-title { margin-top:0; }
        
        #status-message {
            margin-top: 1rem;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>
<body>

<header>
    <h1>Krpano CMS</h1>
    <div style="display:flex; gap:0.5rem;">
        <a href="logout.php" class="btn btn-outline">Log Out</a>
    </div>
</header>

<div class="container">
    <div class="upload-section">
        <h3>Ladda upp nytt projekt</h3>
        <div style="background: #e9f7fe; border-left: 4px solid #007bff; padding: 1rem; margin-bottom: 1rem; font-size: 0.9rem;">
            <strong>Hur ZIP-filen ska se ut:</strong><br>
            När du packar upp din ZIP ska mappen "inuti" innehålla <code>tour.html</code> direkt.<br>
            Det vill säga, ZIP-filen ska <em>inte</em> innehålla en övermapp (t.ex. <code>projekt/tour.html</code>) utan filerna ska ligga direkt i roten av arkivet (t.ex. <code>myproject.zip</code> innehåller <code>tour.html</code>, <code>tour.xml</code>, osv).<br>
            <br>
            Mappen som skapas på servern får samma namn som din ZIP-fil.
        </div>

        <form id="uploadForm" enctype="multipart/form-data" style="display:flex; gap:0.5rem; align-items:center;">
            <input type="file" name="zipfile" id="zipfile" required accept=".zip">
            <button type="submit" class="btn btn-success" id="uploadBtn">Ladda upp ZIP</button>
        </form>
        
        <div class="progress-wrapper" id="progressWrapper">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-text" id="progressText">0%</div>
            <div id="status-message"></div>
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
            const baseUrl = window.location.href.replace('admin/index.php', '') + 'projekt/';
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

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const file = fileInput.files[0];
        if (!file) return;

        // Reset UI
        uploadBtn.disabled = true;
        progressWrapper.style.display = 'block';
        progressFill.style.width = '0%';
        progressText.innerText = '0%';
        statusMessage.innerText = 'Laddar upp...';
        statusMessage.style.color = '#333';

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
                }
            }
        });

        // Load event (complete)
        xhr.addEventListener('load', function() {
            uploadBtn.disabled = false;
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    statusMessage.innerText = response.message;
                    statusMessage.style.color = 'green';
                    setTimeout(() => window.location.reload(), 1500); // Reload after success
                } else {
                    statusMessage.innerText = 'Fel: ' + response.message;
                    statusMessage.style.color = 'red';
                }
            } catch (e) {
                console.error("JSON Parse Error:", e);
                console.log("Raw Response:", xhr.responseText);
                // Extract a meaningful error if possible, otherwise show raw start
                let rawSnippet = xhr.responseText.substring(0, 200).replace(/</g, "&lt;");
                statusMessage.innerHTML = 'Kunde inte läsa svaret från servern. <br>Troligt fel: " ' + rawSnippet + '..."<br>Kontrollera konsolen (F12) för mer info.';
                statusMessage.style.color = 'red';
            }
        });

        // Error event
        xhr.addEventListener('error', function() {
            uploadBtn.disabled = false;
            statusMessage.innerText = 'Ett nätverksfel uppstod (XHR Error).';
            statusMessage.style.color = 'red';
        });

        xhr.open('POST', 'upload.php', true);
        xhr.send(formData);
    });
</script>

</body>
</html>
