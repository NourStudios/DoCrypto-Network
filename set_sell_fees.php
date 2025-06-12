<?php
$filename = 'data/bs.json';

if (!file_exists($filename)) {
    echo "File not found: $filename";
    exit;
}

$jsonData = file_get_contents($filename);
$bs = json_decode($jsonData, true);

$bId = $_GET['id'] ?? null;
$fee = $_GET['fee'] ?? null;
$passwordInput = $_GET['password'] ?? ''; // Get password input

if (!$bId || !is_numeric($fee) || !$passwordInput) { // Require b_id, fee and password
    echo "Missing or invalid 'id', 'fee' or 'password'.";
    exit;
}

if (!isset($bs[$bId])) {
    echo "b ID $bId not found.";
    exit;
}

// Check password
$storedPassword = $bs[$bId]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    echo "Invalid password for this b.";
    exit;
}

$bs[$bId]['sell_fees'] = floatval($fee);

file_put_contents($filename, json_encode($bs, JSON_PRETTY_PRINT));

echo "Successful.";
?>