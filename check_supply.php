<?php

$bid = $_GET['bid'] ?? '';
$passwordInput = $_GET['password'] ?? '';

if (!$bid || !$passwordInput) {
    die("No bid or password provided.");
}

$file = 'data/bs.json';

if (!file_exists($file)) {
    die("bs.json not found.");
}

$bs = json_decode(file_get_contents($file), true);

if (!isset($bs[$bid])) {
    die("b '$bid' does NOT exist.");
}

$storedPassword = $bs[$bid]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    die("Invalid password for this b.");
}

$sold = $bs[$bid]['sold'] ?? 0;

echo "$sold";