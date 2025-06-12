<?php
header('Content-Type: application/json');

$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['error' => 'Missing username or password']);
    exit;
}

$globalAccountsFile = 'data/accounts.json';
if (!file_exists($globalAccountsFile)) {
    echo json_encode(['error' => 'Accounts data not found']);
    exit;
}

$globalAccounts = json_decode(file_get_contents($globalAccountsFile), true);

$walletBalance = null;
foreach ($globalAccounts as $account) {
    if ($account['username'] === $username && $account['password'] === $password) {
        $walletBalance = $account['wallet_balance'] ?? 0;
        break;
    }
}

if ($walletBalance === null) {
    echo json_encode(['error' => 'Invalid username or password']);
    exit;
}

echo json_encode(['wallet_balance' => $walletBalance]);
?>