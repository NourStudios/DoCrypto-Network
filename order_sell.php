<?php
$walletUsername = $_GET['wallet_username'] ?? '';
$walletPassword = $_GET['wallet_password'] ?? '';
$publicAddress = $_GET['public_address'] ?? '';
$isStake = isset($_GET['stake']) && $_GET['stake'] === 'true';
$amount = floatval($_GET['amount'] ?? 0); 

if (!$walletUsername || !$walletPassword || !$publicAddress) {
    die("Missing or invalid parameters.");
}

$bAccPath = "data/bs/$publicAddress/accounts.json";
$bsFile = 'data/bs.json';

if (!file_exists($bAccPath) || !file_exists($bsFile)) {
    die("Required files not found.");
}

$bAccounts = json_decode(file_get_contents($bAccPath), true);
$bs = json_decode(file_get_contents($bsFile), true);

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

$totalStakedAmount = 0;
if (isset($bAccount['stake'])) {
    foreach ($bAccount['stake'] as $stake) {
        $totalStakedAmount += $stake['amount'];
    }
}

if ($isStake) {
    if (!isset($bAccount['stake']) || empty($bAccount['stake'])) {
        die("No staked coins found.");
    }

    $amount = $totalStakedAmount; 
    $price = floatval($_GET['price'] ?? 0); 

    $bId = array_search($publicAddress, array_column($bs, 'public_address', 'id'));
    if ($bId === false || !isset($bs[$bId]['apy'])) {
        die("Could not find APY information.");
    }

    $apy = $bs[$bId]['apy'];
    $price = $price * (1 + ($apy / 100));

    $remainingAmount = $amount;
    if (isset($bAccount['stake'])) {
        foreach ($bAccount['stake'] as &$stake) {
            if ($remainingAmount <= 0) break;

            $stakeAmount = min($stake['amount'], $remainingAmount);
            $stake['amount'] -= $stakeAmount;
            $remainingAmount -= $stakeAmount;
        }

        $bAccount['stake'] = array_filter($bAccount['stake'], function ($stake) {
            return $stake['amount'] > 0;
        });
    }
} else {

    if ($amount <= 0) {
        die("Invalid amount to sell.");
    }

    if (!isset($bAccount['balance']) || $bAccount['balance'] < $amount) {
        die("Insufficient balance to sell.");
    }

    $bAccount['balance'] -= $amount;
    $price = floatval($_GET['price'] ?? 0); 
}

file_put_contents($bAccPath, json_encode($bAccounts, JSON_PRETTY_PRINT));

$sellOrdersFile = 'data/orders/sell_orders.json';
$sellOrders = file_exists($sellOrdersFile) ? json_decode(file_get_contents($sellOrdersFile), true) : [];

$orderId = uniqid('sell_', true);
$sellOrder = [
    'order_id' => $orderId,
    'wallet_username' => $walletUsername,
    'amount' => $amount,
    'price' => $price,
    'public_address' => $publicAddress,
    'is_stake' => $isStake,
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

$sellOrders[] = $sellOrder;
file_put_contents($sellOrdersFile, json_encode($sellOrders, JSON_PRETTY_PRINT));

echo json_encode([
    'status' => 'success',
    'order_id' => $orderId,
    'price' => $price,
    'is_stake' => $isStake,
    'amount' => $amount 
]);
?>