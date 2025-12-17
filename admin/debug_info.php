<?php
require_once 'auth_check.php';
echo "Upload Max: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max: " . ini_get('post_max_size') . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution: " . ini_get('max_execution_time') . "<br>";
