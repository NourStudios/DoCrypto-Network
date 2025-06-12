<?php

$walletUsername = $_GET['wallet_username'] ?? '';
$walletPassword = $_GET['wallet_password'] ?? '';
$publicAddress = $_GET['public_address'] ?? '';

if (!$walletUsername || !$walletPassword || !$publicAddress) {
    die("Missing or invalid parameters.");
}

$bAccPath = "data/bs/$publicAddress/accounts.json";
if (!file_exists($bAccPath)) {
    die("b account file not found.");
}

$bAccounts = json_decode(file_get_contents($bAccPath), true);
$bAccount = null;

foreach ($bAccounts as &$bAccount) {
    if ($bAccount['username'] === $walletUsername && $bAccount['password'] === $walletPassword) {
        $bAccount = &$bAccount;
        break;
    }
}

if (!$bAccount) {
    die("Invalid wallet credentials.");
}

if (!isset($bAccount['stake']) || empty($bAccount['stake'])) {
    die("No stake found.");
}

$totalExpectationResult = 0;
$totalAmount = 0;

foreach ($bAccount['stake'] as $stake) {
    $totalExpectationResult += $stake['expectation_result'];
    $totalAmount += $stake['amount'];
}

$addedStakeSupply = $totalExpectationResult - $totalAmount;

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    die("b data file not found.");
}

$bs = json_decode(file_get_contents($bsFile), true);
$bId = array_search($publicAddress, array_column($bs, 'public_address', 'id'));
if ($bId === false) {
    die("Invalid b ID.");
}

if (!isset($bs[$bId]['added_stake_supply'])) {
    $bs[$bId]['added_stake_supply'] = 0;
}

$bs[$bId]['added_stake_supply'] += $addedStakeSupply;
file_put_contents($bsFile, json_encode($bs, JSON_PRETTY_PRINT));

$bFolder = "data/bs/$bId/s"; 
if (!is_dir($bFolder)) {
    mkdir($bFolder, 0777, true);
}

$nodeGeneratedFile = "data/bs/$bId/node_generated_s.json"; 
if (!file_exists($nodeGeneratedFile)) {
    file_put_contents($nodeGeneratedFile, json_encode(['count' => 0], JSON_PRETTY_PRINT));
}
$nodeGenerated = json_decode(file_get_contents($nodeGeneratedFile), true);
$nodeCount = $nodeGenerated['count'];

$nodeFile = "$bFolder/node_$nodeCount.json";

if (!file_exists($nodeFile)) {
    file_put_contents($nodeFile, json_encode([], JSON_PRETTY_PRINT));
}

$nodeData = json_decode(file_get_contents($nodeFile), true);

if (count($nodeData) >= 100) {
    $nodeCount++;
    $nodeFile = "$bFolder/node_$nodeCount.json";
    file_put_contents($nodeFile, json_encode([], JSON_PRETTY_PRINT));
    $nodeData = [];

    $nodeGenerated['count'] = $nodeCount;
    file_put_contents($nodeGeneratedFile, json_encode($nodeGenerated, JSON_PRETTY_PRINT));
}

$aTransaction = [
    'type' => 'added_stake_supply',
    'amount' => $addedStakeSupply,
    'wallet_username' => $walletUsername,
    'timestamp' => date('Y-m-d H:i:s')
];

$nodeData[] = $aTransaction;
file_put_contents($nodeFile, json_encode($nodeData, JSON_PRETTY_PRINT));

$bAccount['balance'] += $totalExpectationResult;

unset($bAccount['stake']);
file_put_contents($bAccPath, json_encode($bAccounts, JSON_PRETTY_PRINT));

echo "Stake finished successfully. Added " . $addedStakeSupply . " to added_stake_supply and " . $totalExpectationResult . " to wallet balance.";
?>