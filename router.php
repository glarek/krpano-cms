<?php
// router.php

// 1. Handling static assets directly if they exist and are NOT in /projekt/
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);

// Protect /projekt/ directory
$requestUri = $_SERVER["REQUEST_URI"];
$pos = strpos($requestUri, '/projekt/');

if ($pos !== false) {
    session_start();
    
    // Normalize path to start with /projekt/
    $fullPath = substr($requestUri, $pos);
    // Remove query string if present
    $path = parse_url($fullPath, PHP_URL_PATH);
    
    // Security: Prevent Directory Traversal
    if (strpos($path, '..') !== false) {
        http_response_code(403);
        exit('Access Denied');
    }
    
    // Extract project folder name
    // path structure: /projekt/PROJECT_NAME/file.ext
    $parts = explode('/', trim($path, '/'));
    // parts[0] = projekt, parts[1] = PROJECT_NAME
    $projectName = $parts[1] ?? null;

    if (!$projectName) {
        return false; 
    }

    $authFile = __DIR__ . '/admin/project_auth_data.php';
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
            ?>
            <!DOCTYPE html>
            <html lang="sv">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Project Access Denied</title>
                <style>
                     body { font-family: 'Segoe UI', sans-serif; background: #eee; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                     .box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 340px; text-align: center; }
                     h2 { margin-top: 0; color: #333; }
                     p { color: #666; }
                </style>
            </head>
            <body>
                <div class="box">
                    <h2>Åtkomst Nekad</h2>
                    <p>Detta projekt är skyddat.</p>
                    <p>Vänligen använd den unika länken du har fått för att se detta projekt.</p>
                </div>
            </body>
            </html>
            <?php
            exit; // Stop serving file
        }
    } // End of else (Token Check)
    } // End of if (isset($authData))
    
    // Access Granted (either protected-and-logged-in, or unprotected)
    // Inject Branding for HTML files
    if (preg_match('/\.html$/i', $path) && file_exists(__DIR__ . $path)) {
        $content = file_get_contents(__DIR__ . $path);
        
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
            <img src="../../img/GRIT_LOGO.svg" alt="GRIT Projects" style="height: 20px; filter: brightness(0) invert(1);">
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
