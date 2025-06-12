<?php
$bid = $_GET['bid'] ?? '';
$passwordInput = $_GET['password'] ?? '';

if (!$bid || !$passwordInput) {
    die("No bid or password provided.");
}

$bsFile = 'data/bs.json';

if (!file_exists($bsFile)) {
    die("bs.json not found.");
}

$bs = json_decode(file_get_contents($bsFile), true);

if (!isset($bs[$bid])) {
    die("b not found.");
}

$storedPassword = $bs[$bid]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    die("Invalid password for this b.");
}

if (isset($bs[$bid])) {
    echo "b '$bid' exists.";
} else {
    echo "b '$bid' does NOT exist.";
}
?>