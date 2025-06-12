<?php

$buyOrdersFile = 'data/orders/buy_orders.json';
if (!file_exists($buyOrdersFile)) {
    die("Buy orders file not found.");
}

$buyOrders = json_decode(file_get_contents($buyOrdersFile), true);

$sellOrdersFile = 'data/orders/sell_orders.json';
if (!file_exists($sellOrdersFile)) {
    die("Sell orders file not found.");
}

$sellOrders = json_decode(file_get_contents($sellOrdersFile), true);

$accountsFile = 'data/accounts.json';
if (!file_exists($accountsFile)) {
    die("Accounts file not found.");
}

$accounts = json_decode(file_get_contents($accountsFile), true);

foreach ($buyOrders as $buyKey => &$buyOrder) {
    if ($buyOrder['status'] !== 'pending') {
        continue; 
    }

    $username = $buyOrder['username'];
    $amount = $buyOrder['amount'];
    $publicAddress = $buyOrder['public_address'];

    $userAccount = null;
    foreach ($accounts as &$account) {
        if ($account['username'] === $username) {
            $userAccount = &$account;
            break;
        }
    }

    if (!$userAccount) {
        echo "Invalid username for buy order ID: " . $buyOrder['order_id'] . ". Skipping.\n";
        $buyOrder['status'] = 'failed';
        continue;
    }

    $cheapestOrder = null;
    $cheapestPricePerCoin = PHP_FLOAT_MAX;
    $cheapestOrderKey = null;

    foreach ($sellOrders as $sellKey => $sellOrder) {
        if ($sellOrder['public_address'] === $publicAddress && $sellOrder['status'] === 'pending') {
            $pricePerCoin = $sellOrder['price'] / $sellOrder['amount'];
            if ($pricePerCoin < $cheapestPricePerCoin && $sellOrder['amount'] >= $amount) {
                $cheapestOrder = $sellOrder;
                $cheapestPricePerCoin = $pricePerCoin;
                $cheapestOrderKey = $sellKey;
            }
        }
    }

    if (!$cheapestOrder) {
        echo "No matching sell orders found for buy order ID: " . $buyOrder['order_id'] . ". Skipping.\n";
        $buyOrder['status'] = 'unmatched';
        continue;
    }

    $userAccount['wallet_balance'] -= $amount;

    $sellOrders[$cheapestOrderKey]['customer'] = $username;
    if ($cheapestOrder['amount'] > $amount) {
        $sellOrders[$cheapestOrderKey]['amount'] -= $amount;
    } else {
        $sellOrders[$cheapestOrderKey]['status'] = 'completed';
    }

    $buyOrder['status'] = 'completed';
    echo "Buy order ID: " . $buyOrder['order_id'] . " matched successfully with sell order ID: " . $cheapestOrder['order_id'] . "\n";
}

file_put_contents($accountsFile, json_encode($accounts, JSON_PRETTY_PRINT));
file_put_contents($sellOrdersFile, json_encode($sellOrders, JSON_PRETTY_PRINT));
file_put_contents($buyOrdersFile, json_encode($buyOrders, JSON_PRETTY_PRINT));

echo "Order finishing process completed.\n";
?>