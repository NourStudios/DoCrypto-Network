<?php

header('Content-Type: application/json');

$minerUsername = $_GET['miner_username'] ?? '';
$minerPassword = $_GET['miner_password'] ?? '';

if (empty($minerUsername) || empty($minerPassword)) {
    die(json_encode(['error' => 'Missing required parameters']));
}

$certificatesFile = "data/miners/certificates.json";
if (!file_exists($certificatesFile)) {
    die(json_encode(['error' => 'Certificates file not found']));
}
$certificates = json_decode(file_get_contents($certificatesFile), true);
$minerCert = null;
foreach ($certificates as $cert) {
    if ($cert['username'] === $minerUsername && $cert['password'] === $minerPassword) {
        $minerCert = $cert;
        break;
    }
}
if (!$minerCert) {
    die(json_encode(['error' => 'Invalid miner credentials']));
}
$bId = $minerCert['b_id'];

$bsFile = "data/bs.json";
if (!file_exists($bsFile)) {
    die(json_encode(['error' => 'b data not found']));
}
$bs = json_decode(file_get_contents($bsFile), true);
if (!isset($bs[$bId])) {
    die(json_encode(['error' => 'Invalid b ID']));
}

$totalBalance = 0;
$totalBalanceFees = 0;
$totalSupply = 0;
$totalStakeSupply = 0;
$totalNetworkShareAmountCoins = 0;

$supplyFolder = "data/bs/$bId/s";
if (is_dir($supplyFolder)) {
    $files = scandir($supplyFolder);
    foreach ($files as $file) {
        if (strpos($file, 'solved_node_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $filePath = $supplyFolder . '/' . $file;
            $nodeData = json_decode(file_get_contents($filePath), true);

            $totalSupply += floatval($nodeData['supply_change'] ?? 0);
            $totalStakeSupply += floatval($nodeData['stake_supply_change'] ?? 0);
        }
    }
}

$balanceFolder = "data/bs/$bId/a";
if (is_dir($balanceFolder)) {
    $files = scandir($balanceFolder);
    foreach ($files as $file) {
        if (strpos($file, 'solved_node_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $filePath = $balanceFolder . '/' . $file;
            $nodeData = json_decode(file_get_contents($filePath), true);

            $totalBalance += floatval($nodeData['amount'] ?? 0);
            $totalBalanceFees += floatval($nodeData['fee_money'] ?? 0);
            $totalNetworkShareAmountCoins += floatval($nodeData['total_network_share_amount_coins'] ?? 0);
        }
    }
}

$bs[$bId]['balance'] = $totalBalance;
$bs[$bId]['balance_fees'] = $totalBalanceFees;
$bs[$bId]['supply'] = $totalSupply;
$bs[$bId]['total_network_share_amount_coins'] = $totalNetworkShareAmountCoins; 
file_put_contents($bsFile, json_encode($bs, JSON_PRETTY_PRINT));

echo json_encode([
    'status' => 'success',
    'balance' => $totalBalance,
    'balance_fees' => $totalBalanceFees,
    'supply' => $totalSupply,
    'stake_supply' => $totalStakeSupply,
    'total_network_share_amount_coins' => $totalNetworkShareAmountCoins 
]);
?>