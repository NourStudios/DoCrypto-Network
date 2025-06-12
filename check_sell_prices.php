<?php

$publicAddress = $_GET['public_address'] ?? '';

if (!$publicAddress) {
    die("Missing public address.");
}

$sellOrdersFile = 'data/orders/sell_orders.json';
if (!file_exists($sellOrdersFile)) {
    die("Sell orders file not found.");
}

$sellOrders = json_decode(file_get_contents($sellOrdersFile), true);

$cheapestOrder = null;
$cheapestPricePerCoin = PHP_FLOAT_MAX;

foreach ($sellOrders as $sellOrder) {
    if ($sellOrder['public_address'] === $publicAddress && $sellOrder['status'] === 'pending') {
        $pricePerCoin = $sellOrder['price'] / $sellOrder['amount'];
        if ($pricePerCoin < $cheapestPricePerCoin) {
            $cheapestOrder = $sellOrder;
            $cheapestPricePerCoin = $pricePerCoin;
        }
    }
}

if ($cheapestOrder) {
    echo "Cheapest sell order: " . json_encode($cheapestOrder);
} else {
    echo "No sell orders found for this b.";
}
?>