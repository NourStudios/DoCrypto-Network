<?php
$bPublicAddress = $_GET['b_address'] ?? '';
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

if (!$bPublicAddress || !$username || !$password) {
    echo "Incorrect parameters.";
    exit;
}

$globalbs = json_decode(file_get_contents("data/bs.json"), true);

$bId = null;
foreach ($globalbs as $id => $data) {
    if ($data['public_address'] === $bPublicAddress) {
        $bId = $id;
        break;
    }
}

if (!$bId) {
    echo "b is unavailable.";
    exit;
}

$accountsPath = "data/bs/$bId/accounts.json";
if (!file_exists($accountsPath)) {
    echo "There aren't any accounts in this b.";
    exit;
}

$accounts = json_decode(file_get_contents($accountsPath), true);

if (!isset($accounts[$username])) {
    echo "Account unavailable.";
    exit;
}

if (!password_verify($password, $accounts[$username]['password'])) {
    echo "Invalid password.";
    exit;
}

$walletData = $accounts[$username];

echo $walletData['balance'];
?>