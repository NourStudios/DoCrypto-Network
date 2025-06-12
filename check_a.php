<?php
$bId = $_GET['b_id'] ?? '';
$passwordInput = $_GET['password'] ?? '';

if (!$bId || !$passwordInput) {
    http_response_code(400);
    echo "Missing b_id or password.";
    exit;
}

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    http_response_code(500);
    echo "ERR04";
    exit;
}

$bs = json_decode(file_get_contents($bsFile), true);
if (!isset($bs[$bId])) {
    http_response_code(404);
    echo "ERR05";
    exit;
}

$storedPassword = $bs[$bId]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    echo "Invalid password for this b.";
    exit;
}

$aPath = "data/bs/$bId/a.json";

if (!file_exists($aPath)) {
    http_response_code(404);
    echo "ERRO2";
    exit;
}

$a = json_decode(file_get_contents($aPath), true);

if (!is_array($a)) {
    echo "ERR03";
    exit;
}

$totalTransactions = count($a);

echo $totalTransactions;
?>