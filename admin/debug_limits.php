<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Server Limits Debug</title>
    <style>
        body { font-family: sans-serif; background: #0B0F19; color: white; padding: 2rem; }
        table { border-collapse: collapse; width: 100%; max-width: 600px; margin-top: 2rem; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { color: #888; }
        .highlight { color: #10b981; font-weight: bold; }
        .mismatch { color: #ef4444; }
    </style>
</head>
<body>
    <h1>Effective Server Limits</h1>
    <p>Compare "Master" (Hosting Provider) vs "Local" (Your .htaccess/.user.ini) settings.</p>

    <table>
        <thead>
            <tr>
                <th>Setting</th>
                <th>Local Value (Yours)</th>
                <th>Master Value (Provider)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $settings = [
                'upload_max_filesize',
                'post_max_size',
                'memory_limit',
                'max_execution_time',
                'max_input_time'
            ];

            foreach ($settings as $key) {
                $local = ini_get($key);
                $master = get_cfg_var($key); // Attempt to get master value, might be same as local depending on SAPI
                
                // If get_cfg_var is same as ini_get, it might not show the 'original' if overwritten at startup, 
                // but usually for .user.ini it shows the difference in phpinfo().
                // A better way is strictly relying on what PHP is actually using ($local).
                // If $local is 1024M, then OUR settings are working for PHP.
                
                echo "<tr>";
                echo "<td>$key</td>";
                echo "<td class='highlight'>$local</td>";
                echo "<td>$master</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <h3>Configuration Details</h3>
    <ul>
        <li><strong>Loaded Configuration File:</strong> <?= php_ini_loaded_file() ?: 'None' ?></li>
        <li><strong>Additional .ini files parsed:</strong> <?= php_ini_scanned_files() ?: 'None' ?></li>
        <li><strong>Server Software:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></li>
    </ul>

    <p style="margin-top: 2rem; padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 8px;">
        <strong>Interpretation:</strong><br>
        1. If "Local Value" says <strong>1024M</strong> (or your set value), then PHP is <strong>accepting</strong> your settings.<br>
        2. If uploads still fail with large files (Status 405/413), then <strong>Nginx</strong> (the web server) has a hidden limit (`client_max_body_size`) that is blocking the file <em>before</em> PHP sees it.<br>
        3. PHP config cannot see or change Nginx limits.
    </p>

    <a href="index.php" style="color: #3b82f6; display: inline-block; margin-top: 1rem;">&larr; Back to Dashboard</a>
</body>
</html>
