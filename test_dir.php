<?php
// test_dir.php
$name = 'Bostäder'; // What PHP gets from $_GET['id'] when passed Bost%C3%A4der
$encoded = 'Bost%C3%A4der'; // The literal name on disk

echo "Checking 'projekt/$name': " . (is_dir(__DIR__ . '/projekt/' . $name) ? 'EXISTS' : 'MISSING') . "\n";
echo "Checking 'projekt/$encoded': " . (is_dir(__DIR__ . '/projekt/' . $encoded) ? 'EXISTS' : 'MISSING') . "\n";

// Check re-encoding
$reEncoded = rawurlencode($name);
echo "Re-encoded '$name' -> '$reEncoded': " . (is_dir(__DIR__ . '/projekt/' . $reEncoded) ? 'MATCH' : 'NO MATCH') . "\n";
