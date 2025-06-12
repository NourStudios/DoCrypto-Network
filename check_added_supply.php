<?php
if (!isset($_GET['b_id']) || !isset($_GET['password'])) {
    die("No b_id or password specified.");
}

$b_id = $_GET['b_id'];
$passwordInput = $_GET['password'];

$filename = 'data/bs.json';
if (!file_exists($filename)) {
    die("bs.json not found.");
}

$jsonData = file_get_contents($filename);
$bs = json_decode($jsonData, true);

if ($bs === null) {
    die("Failed to decode JSON.");
}

if (!isset($bs[$b_id])) {
    die("b ID '$b_id' not found.");
}

$storedPassword = $bs[$b_id]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    die("Invalid password for this b.");
}

$b = $bs[$b_id];
$added_supply = $b['added_supply'];

echo $added_supply;
?>