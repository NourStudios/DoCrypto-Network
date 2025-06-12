<?php

$bId = $_GET['id'] ?? '';
$passwordInput = $_GET['password'] ?? '';

if (!$bId || !$passwordInput) {
    echo "Missing b ID or password.";
    exit;
}

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    echo "ERR04";
    exit;
}

$bs = json_decode(file_get_contents($bsFile), true);
if (!isset($bs[$bId])) {
    echo "ERR05";
    exit;
}

$storedPassword = $bs[$bId]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    echo "Invalid password for this b.";
    exit;
}

$accountsPath = "data/bs/$bId/accounts.json";

if (!file_exists($accountsPath)) {
    echo "accounts.json not found for b ID: $bId";
    exit;
}

$accounts = json_decode(file_get_contents($accountsPath), true);

if (!is_array($accounts)) {
    echo "Invalid accounts.json format.";
    exit;
}
echo "<ul>";
foreach ($accounts as $username => $data) {
    echo "<li>$username</li>";
}
echo "</ul>";
?>