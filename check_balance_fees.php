<?php

header('Content-Type: application/json');

$bId = $_GET['b_id'] ?? '';
$passwordInput = $_GET['password'] ?? '';

if (!$bId || !$passwordInput) {
    echo json_encode(['error' => 'Missing b ID or password']);
    exit;
}

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    echo json_encode(['error' => 'b data not found']);
    exit;
}

$bs = json_decode(file_get_contents($bsFile), true);

if (!isset($bs[$bId])) {
    echo json_encode(['error' => 'b not found']);
    exit;
}

$b = $bs[$bId];

$storedPassword = $b['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid password for this b.']);
    exit;
}

$balanceFees = $b['balance_fees'] ?? 0;

echo json_encode(['balance_fees' => $balanceFees]);
?>