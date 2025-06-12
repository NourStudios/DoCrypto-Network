<?php

header('Content-Type: application/json');

$bId = $_GET['b_id'] ?? null;
$walletUsername = $_GET['wallet_username'] ?? null;
$passwordInput = $_GET['password'] ?? null;

$response = ['buy_amount' => 'N/A', 'sell_amount' => 'N/A', 'error' => null];

if (!$bId || !$walletUsername || !$passwordInput) {
    $response['error'] = 'Missing b_id, wallet_username, or password.';
    echo json_encode($response);
    exit;
}

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    $response['error'] = "bs.json not found.";
    echo json_encode($response);
    exit;
}

$bs = json_decode(file_get_contents($bsFile), true);
if (!isset($bs[$bId])) {
    $response['error'] = "b not found.";
    echo json_encode($response);
    exit;
}

$storedPassword = $bs[$bId]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    $response['error'] = "Invalid password for this b.";
    echo json_encode($response);
    exit;
}

$accountsFilePath = "bs/" . basename($bId) . "data/accounts.json";

if (!file_exists($accountsFilePath)) {
    $response['error'] = "Accounts file not found for bID: " . htmlspecialchars($bId);
    echo json_encode($response);
    exit;
}

$accountsJson = file_get_contents($accountsFilePath);
$accountsData = json_decode($accountsJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $response['error'] = "Could not parse JSON from accounts file. Error: " . json_last_error_msg();
    echo json_encode($response);
    exit;
}

if (isset($accountsData[$walletUsername])) {
    $userbAccount = $accountsData[$walletUsername];
    $response['buy_amount'] = $userbAccount['buy_amount'] ?? 'N/A';
    $response['sell_amount'] = $userbAccount['sell_amount'] ?? 'N/A';
} else {
    $response['error'] = "Wallet username " . htmlspecialchars($walletUsername) . " not found in accounts for bID " . htmlspecialchars($bId);
}

echo json_encode($response);
?>