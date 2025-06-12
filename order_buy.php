<?php

$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';
$amount = floatval($_GET['amount'] ?? 0);
$publicAddress = $_GET['public_address'] ?? '';
$walletUsername = $_GET['wallet_username'] ?? ''; 

if (!$username || !$password || $amount <= 0 || !$publicAddress || !$walletUsername) { 
    die("Missing or invalid parameters.");
}

$accountsFile = 'data/accounts.json';
if (!file_exists($accountsFile)) {
    die("Accounts file not found.");
}

$accounts = json_decode(file_get_contents($accountsFile), true);
$userAccount = null;

foreach ($accounts as &$account) {
    if ($account['username'] === $username && password_verify($password, $account['password'])) {
        $userAccount = &$account;
        break;
    }
}

if (!$userAccount) {
    die("Invalid username or password.");
}

if ($userAccount['wallet_balance'] < $amount) {
    die("Insufficient wallet balance.");
}

$sellOrdersFile = 'data/orders/sell_orders.json';
if (!file_exists($sellOrdersFile)) {
    die("Sell orders file not found.");
}

$sellOrders = json_decode(file_get_contents($sellOrdersFile), true);

$cheapestOrder = null;
$cheapestPricePerCoin = PHP_FLOAT_MAX;

foreach ($sellOrders as $key => $sellOrder) {
    if ($sellOrder['public_address'] === $publicAddress && $sellOrder['status'] === 'pending') {
        $pricePerCoin = $sellOrder['price'] / $sellOrder['amount'];
        if ($pricePerCoin < $cheapestPricePerCoin && $sellOrder['amount'] >= $amount) {
            $cheapestOrder = $sellOrder;
            $cheapestOrderKey = $key;
            $cheapestPricePerCoin = $pricePerCoin;
        }
    }
}

if (!$cheapestOrder) {
    die("No matching sell orders found.");
}

$startTime = time();
$customer = $username;
while (time() - $startTime < 5) {
    $customerCount = 0;
    foreach ($sellOrders as $sellOrder) {
        if (isset($sellOrder['customer']) && $sellOrder['customer'] !== null) {
            $customerCount++;
        }
    }
    if ($customerCount <= 1) {
        break; 
    }
    sleep(1); 
}

$userAccount['wallet_balance'] -= $amount;
file_put_contents($accountsFile, json_encode($accounts, JSON_PRETTY_PRINT));

$sellOrders[$cheapestOrderKey]['customer'] = $customer;
$sellOrders[$cheapestOrderKey]['customer_wallet'] = $walletUsername; 

if ($cheapestOrder['amount'] > $amount) {
    $sellOrders[$cheapestOrderKey]['amount'] -= $amount;
} else {
    $sellOrders[$cheapestOrderKey]['status'] = 'completed';
}

file_put_contents($sellOrdersFile, json_encode($sellOrders, JSON_PRETTY_PRINT));

echo "Buy order matched successfully with sell order ID: " . $cheapestOrder['order_id'];
?>