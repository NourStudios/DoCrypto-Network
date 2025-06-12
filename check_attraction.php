<?php

$bId = $_GET['b_id'] ?? '';
$passwordInput = $_GET['password'] ?? '';

if (!$bId || !$passwordInput) {
    http_response_code(400);
    echo "Missing b_id or password.";
    exit;
}

$bs = json_decode(file_get_contents('data/bs.json'), true);

if (!isset($bs[$bId])) {
    http_response_code(404);
    echo "b ID not found.";
    exit;
}

$storedPassword = $bs[$bId]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    echo "Invalid password for this b.";
    exit;
}

$b = $bs[$bId];

$accountsPath = "data/bs/$bId/accounts.json";

if (!file_exists($accountsPath)) {
    echo "No accounts file found for this b.";
    exit;
}

$accounts = json_decode(file_get_contents($accountsPath), true);

$totalAccounts = count($accounts);

echo $totalAccounts;
?>