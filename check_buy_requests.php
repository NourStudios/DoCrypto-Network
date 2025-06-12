<?php

$bId = $_GET['b_id'] ?? '';
$passwordInput = $_GET['password'] ?? '';

if (!$bId || !$passwordInput) {
    http_response_code(400);
    echo "Invalid or missing b ID or password.";
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

$accountsPath = "data/bs/$bId/accounts.json";

if (!file_exists($accountsPath)) {
    http_response_code(404);
    echo "Accounts file not found.";
    exit;
}

$accounts = json_decode(file_get_contents($accountsPath), true);
$pendingBuys = [];

foreach ($accounts as $username => $account) {
    $buyAmount = isset($account['buy_amount']) ? floatval($account['buy_amount']) : 0.0;
    if ($buyAmount > 0) {
        $pendingBuys[] = [
            'username' => $username,
            'buy_amount' => $buyAmount
        ];
    }
}

if (empty($pendingBuys)) {
    echo "No pending buy orders found for b ID: $bId.";
} else {
    foreach ($pendingBuys as $order) {
        echo "Username: " . $order['username'] . "\n";
        echo "Buy Amount: " . $order['buy_amount'] . "\n\n";
    }
}
?>