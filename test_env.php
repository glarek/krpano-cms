<?php
// test_env.php
require 'api/config.php';

echo "Testing Config Loading...\n";
echo "ADMIN_USER: " . (ADMIN_USER ?: 'NOT SET') . "\n";
echo "ADMIN_HASH: " . (substr(ADMIN_HASH, 0, 10) . '...' ?: 'NOT SET') . "\n";

if (ADMIN_USER === 'adrian.larek@gritprojects.se') {
    echo "SUCCESS: ADMIN_USER matches.\n";
} else {
    echo "FAILURE: ADMIN_USER does not match.\n";
}
