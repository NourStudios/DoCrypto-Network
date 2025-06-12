<?php

$walletUsername = $_GET['wallet_username'] ?? '';
$walletPassword = $_GET['wallet_password'] ?? '';
$amount = floatval($_GET['amount'] ?? 0);
$publicAddress = $_GET['public_address'] ?? '';

if (!$walletUsername || !$walletPassword || $amount <= 0 || !$publicAddress) {
    die("Missing or invalid parameters.");
}

$bAccPath = "data/bs/$publicAddress/accounts.json";
if (!file_exists($bAccPath)) {
    die("b account file not found.");
}

$bAccounts = json_decode(file_get_contents($bAccPath), true);
$bAccount = null;

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    die("b data file not found.");
}

$bs = json_decode(file_get_contents($bsFile), true);
$bId = array_search($publicAddress, array_column($bs, 'public_address', 'id'));
if ($bId === false || !isset($bs[$bId]['apy'])) {
    die("Could not find APY information.");
}

$apy = $bs[$bId]['apy'];
$stakeMax = isset($bs[$bId]['stake_max']) ? floatval($bs[$bId]['stake_max']) : 0;

$totalStaked = 0;
foreach ($bAccounts as $acc) {
    if (isset($acc['stake']) && is_array($acc['stake'])) {
        foreach ($acc['stake'] as $stake) {
            $totalStaked += floatval($stake['amount']);
        }
    }
}
if ($stakeMax > 0 && ($totalStaked + $amount) > $stakeMax) {
    die("Staking would exceed the maximum allowed stake for this crypto. Try a smaller amount.");
}

foreach ($bAccounts as &$bAccount) {
    if ($bAccount['username'] === $walletUsername && $bAccount['password'] === $walletPassword) {
        $bAccount = &$bAccount;
        break;
    }
}

if (!$bAccount) {
    die("Invalid wallet credentials.");
}

if ($bAccount['balance'] < $amount) {
    die("Insufficient coin balance.");
}

$bAccount['balance'] -= $amount;

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    die("b data file not found.");
}

$bs = json_decode(file_get_contents($bsFile), true);
$bId = array_search($publicAddress, array_column($bs, 'public_address', 'id'));
if ($bId === false || !isset($bs[$bId]['apy'])) {
    die("Could not find APY information.");
}

$apy = $bs[$bId]['apy'];

if(!isset($bAccount['stake'])){
    $bAccount['stake'] = [];
}

$expectedReward = $amount * ($apy / 100);
$stake = [
    'amount' => $amount,
    'stake_date' => date('Y-m-d H:i:s'),
    'expectation_result' => $amount + $expectedReward
];

$bAccount['stake'][] = $stake;

file_put_contents($bAccPath, json_encode($bAccounts, JSON_PRETTY_PRINT));

echo "Stake set successfully for " . $amount . " coins.";
?>