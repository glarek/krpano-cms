<?php
// router.php

// 1. Handling static assets directly if they exist and are NOT in /projekt/
$path = urldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
$ext = pathinfo($path, PATHINFO_EXTENSION);

// Protect /projekt/ directory
$requestUri = $_SERVER["REQUEST_URI"];
$pos = strpos($requestUri, '/projekt/');

if ($pos !== false) {
    session_start();
    
    // Normalize path to start with /projekt/
    $fullPath = substr($requestUri, $pos);
    // Remove query string if present
    $path = urldecode(parse_url($fullPath, PHP_URL_PATH));
    
    // Security: Prevent Directory Traversal
    if (strpos($path, '..') !== false) {
        http_response_code(403);
        exit('Access Denied');
    }
    
    // Extract project folder name
    // path structure: /projekt/PROJECT_NAME/file.ext
    $parts = explode('/', trim($path, '/'));
    // parts[0] = projekt, parts[1] = PROJECT_NAME/GROUP_NAME
    $projectName = $parts[1] ?? null;

    if (!$projectName) {
        return false; 
    }

    // PREPARE LOGO (Shared Logic)
    // We expect logo at /img/GRIT_LOGO.svg relative to script root
    $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $realLogoPath = __DIR__ . '/img/GRIT_LOGO.svg';
    $logoSrc = '';
    
    if (file_exists($realLogoPath)) {
        $logoData = file_get_contents($realLogoPath);
        $logoBase64 = base64_encode($logoData);
        $logoSrc = 'data:image/svg+xml;base64,' . $logoBase64;
    } else {
        $logoSrc = $baseDir . '/img/GRIT_LOGO.svg';
    }

    // SHIM: Handle legacy image paths (e.g. logo)
    // Old structure: projekt/Tour/ -> ../../img/logo.svg -> /img/logo.svg (Correct)
    // New structure: projekt/Group/Tour/ -> ../../img/logo.svg -> /projekt/img/logo.svg (Incorrect location)
    // We catch requests to /projekt/img/... and serve from /img/...
    if ($projectName === 'img') {
        $relPath = implode('/', array_slice($parts, 2)); // everything after projekt/img/
        $realFile = __DIR__ . '/img/' . $relPath;
        
        if (file_exists($realFile)) {
             $ext = pathinfo($realFile, PATHINFO_EXTENSION);
             $mime = 'application/octet-stream';
             if($ext === 'svg') $mime = 'image/svg+xml';
             if($ext === 'png') $mime = 'image/png';
             if($ext === 'jpg') $mime = 'image/jpeg';
             
             header('Content-Type: ' . $mime);
             readfile($realFile);
             exit;
        }
    }


    $authFile = __DIR__ . '/api/project_auth_data.php';
    if (!file_exists($authFile)) {
        return false; // No auth data = Open access
    }

    $authData = require $authFile;
    
    // Check if this project has auth configured
    if (isset($authData[$projectName])) {
        // Auth required
        $sessionKey = 'auth_project_' . $projectName;
        $storedToken = $authData[$projectName]['token'] ?? null;
        
        if (!$storedToken) {
             // Invalid config (old schema?), treat as Access Denied or handle gracefully.
             // Let's treat as Access Denied to force them to "Generate New Token" in admin to fix it.
        } else {
             // 1. Check URL Token
        $urlToken = $_GET['token'] ?? null;
        if ($urlToken && $urlToken === $storedToken) {
            $_SESSION[$sessionKey] = $storedToken; // Store the actual token
        }

        // 2. Check Session (Must match currently active token)
        if (isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] === $storedToken) {
            // Authorized.
        } else {
            // 3. Access Denied
            // 3. Access Denied
            header('Location: /access-denied');
            exit;
        }
    } // End of else (Token Check)
    } // End of if (isset($authData))
    
    // Access Granted (either protected-and-logged-in, or unprotected)
    // Access Granted (either protected-and-logged-in, or unprotected)

    // HANDLE DIRECTORY REQUEST (Group Landing Page)
    $fullLocalPath = __DIR__ . $path;
    if (is_dir($fullLocalPath)) {
        // Ensure trailing slash for relative links to work comfortably, though strictly logic handles it
        if (substr($path, -1) !== '/') {
             header("Location: " . $requestUri . '/');
             exit;
        }

        // Scan for sub-projects
        $items = scandir($fullLocalPath);
        $projects = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            if (is_dir($fullLocalPath . '/' . $item)) {
                // Check if it's a valid project (has tour.html)
                if (file_exists($fullLocalPath . '/' . $item . '/tour.html')) {
                    $projects[] = $item;
                }
            }
        }

        // Render Landing Page
        ?>
        <!DOCTYPE html>
        <html lang="sv">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($projectName) ?> - Projekt</title>
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
            <style>
                :root { --primary: #007bff; --bg: #0B0F19; --card: rgba(255,255,255,0.05); --text: #fff; }
                body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 2rem; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
                .container { max-width: 600px; width: 100%; }
                h1 { font-weight: 500; margin-bottom: 2rem; text-align: center; letter-spacing: 1px; }
                .project-list { display: flex; flex-direction: column; gap: 1rem; }
                .project-card { 
                    background: var(--card); 
                    padding: 1.5rem; 
                    border-radius: 12px; 
                    text-decoration: none; 
                    color: white; 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center; 
                    transition: transform 0.2s, background 0.2s;
                    border: 1px solid rgba(255,255,255,0.1);
                }
                .project-card:hover { transform: translateY(-2px); background: rgba(255,255,255,0.1); }
                .project-name { font-size: 1.1rem; font-weight: 500; }
                .arrow { color: rgba(255,255,255,0.3); }
                .logo { margin-bottom: 2rem; }
            </style>
        </head>
        <body>
            <div class="logo">
                <?php 
                $baseDirRel = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $logoUrl = $baseDirRel . '/img/GRIT_LOGO.svg';
                ?>
                <img src="<?= $logoUrl ?>" alt="Logo" style="height: 30px; filter: brightness(0) invert(1);">
            </div>

            <div class="container">
                <h1><?= htmlspecialchars($projectName) ?></h1>
                
                <?php if (empty($projects)): ?>
                    <p style="text-align:center; color:#888;">Inga projekt hittades i denna mapp.</p>
                <?php else: ?>
                    <div class="project-list">
                        <?php foreach ($projects as $p): ?>
                            <?php 
                                // Append token if we have one in session or URL
                                $tokenParam = '';
                                if (isset($storedToken) && $storedToken) {
                                    $tokenParam = '?token=' . $storedToken;
                                }
                            ?>
                            <a href="<?= htmlspecialchars($p) ?>/tour.html<?= $tokenParam ?>" class="project-card">
                                <span class="project-name"><?= htmlspecialchars($p) ?></span>
                                <span class="arrow">→</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- QR Code Section -->

                <?php
                    // Determine current URL for the QR Code
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                    $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    
                    // The token is already in REQUEST_URI if it was passed via GET, 
                    // BUT if it was only in SESSION and we want to share, we should ensure it's in the link.
                    // However, for simplicity and security, we often just use the exact current browser URL or explicitly append the token if we know it's valid.
                    
                    // Let's explicitly rebuild it to be safe and clean
                    $publicUrl = $protocol . $_SERVER['HTTP_HOST'] . "/projekt/" . $projectName . "/";
                    if (isset($storedToken) && $storedToken) {
                        $publicUrl .= "?token=" . $storedToken;
                    }
                    
                    // SVG format, White color (ffffff) on Dark Blue background (0B0F19)
                    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&bgcolor=0B0F19&color=ffffff&format=svg&qzone=1&data=" . urlencode($publicUrl);
                ?>
                <div style="margin-top: 3rem; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem; width: 100%;">
                    <p style="margin-bottom: 1rem; color: rgba(255,255,255,0.5); font-size: 0.9rem;">Dela denna grupp</p>
                    <div style="display: inline-block;">
                        <img src="<?= $qrUrl ?>" alt="QR Code" style="display: block; width: 150px; height: 150px;">
                    </div>
                    <p style="margin-top: 1rem; font-size: 0.8rem; color: rgba(255,255,255,0.3);">Använd kameran för att öppna</p>
                </div>

            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // Inject Branding for HTML files
    if (preg_match('/\.html$/i', $path) && file_exists(__DIR__ . $path)) {
        header('X-Debug-Branding: 1');
        $content = file_get_contents(__DIR__ . $path);
        
        // Calculate dynamic base path for assets
        // Calculate dynamic base path for assets
        // If router.php is at /cms/router.php, dirname is /cms
        // Calculate dynamic base path for assets
        // If router.php is at /cms/router.php, dirname is /cms
        $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        
        // $logoSrc is already prepared at the top of the file

        
        $branding = '
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 30px;
            background: rgba(0,0,0,0.85);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: \'Segoe UI\', sans-serif;
            font-size: 13px;
            font-weight: 500;
            z-index: 2147483647;
            pointer-events: none;
            backdrop-filter: blur(4px);
            text-transform: uppercase;
            letter-spacing: 1px;
        ">
            <img src="' . $logoSrc . '" alt="GRIT Projects" style="height: 20px; filter: brightness(0) invert(1);">
        </div>
        ';
        
        $content = str_ireplace('</body>', $branding . '</body>', $content);
        
        // Disable caching slightly to ensure update
        header("Cache-Control: no-cache, must-revalidate");
        echo $content;
        exit;
    }

    // Serve other files normally
    if (file_exists(__DIR__ . $path)) {
        // Basic MIME type map to ensure correct serving without relying solely on server config
        $mimeTypes = [
            'css' => 'text/css',
            'js'  => 'application/javascript',
            'xml' => 'application/xml',
            'svg' => 'image/svg+xml',
            'jpg' => 'image/jpeg',
            'jpeg'=> 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp'=> 'image/webp',
            'json'=> 'application/json',
            'html'=> 'text/html',
            'htm' => 'text/html',
        ];

        // extensions are case-insensitive
        $extLower = strtolower($ext);
        
        if (isset($mimeTypes[$extLower])) {
            $mime = $mimeTypes[$extLower];
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type(__DIR__ . $path);
        } else {
            $mime = 'application/octet-stream';
        }
        
        header('Content-Type: ' . $mime);
        readfile(__DIR__ . $path);
        exit;
    }

    return false;
}

// 2. Allow admin and other files to be served normally
return false; 
