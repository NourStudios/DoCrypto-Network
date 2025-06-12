<?php
$publicAddress = $_GET['public_address'] ?? '';
$wallet_username = $_GET['wallet_username'] ?? '';
$wallet_password = $_GET['wallet_password'] ?? '';
$action = $_GET['action'] ?? '';
$amount = floatval($_GET['amount'] ?? 0);
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

if (!$publicAddress || !$wallet_username || !$wallet_password || !$username || !$password || !$action || $amount <= 0) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

$bs = json_decode(file_get_contents('data/bs.json'), true);
$bId = null;
$bData = null;

foreach ($bs as $id => $b) {
    if ($b['public_address'] === $publicAddress) {
        $bId = $id;
        $bData = $b;
        break;
    }
}

if (!$bId || !$bData) {
    http_response_code(404);
    echo "b not found.";
    exit;
}

$globalAccounts = json_decode(file_get_contents('data/accounts.json'), true);
$globalUpdated = false;
$gAcc = null;

foreach ($globalAccounts as &$acc) {
    if ($acc['username'] === $username && $acc['password'] === $password) {
        $gAcc = &$acc;
        break;
    }
}

if (!$gAcc) {
    echo "Invalid username or password.";
    exit;
}

$bAccPath = "data/bs/$bId/accounts.json";
$bAccounts = file_exists($bAccPath) ? json_decode(file_get_contents($bAccPath), true) : [];

$accountId = null;
foreach ($bAccounts as $id => $wallet) {
    if ($wallet['username'] === $wallet_username && $wallet['password'] === $wallet_password) {
        $accountId = $id;
        break;
    }
}

if (!$accountId) {
    echo "Invalid wallet credentials.";
    exit;
}

$aFolder = "data/bs/$bId/a";
if (is_dir($aFolder)) {
    foreach (glob("$aFolder/node_*.json") as $nodeFile) {
        $nodeData = json_decode(file_get_contents($nodeFile), true);
        if (!isset($nodeData['solved']) || !$nodeData['solved']) {
            echo "Cannot perform transaction: There are unsolved nodes in the blockchain. Please solve all nodes first.";
            exit;
        }
    }
}

$cAcc = &$bAccounts[$accountId];
$price = floatval($bData['price']);

if (!isset($cAcc['sell_amount'])) $cAcc['sell_amount'] = 0.0;
if (!isset($cAcc['buy_amount'])) $cAcc['buy_amount'] = 0.0;

$buyLocked = isset($bData['buy_locked']) && $bData['buy_locked'] === true;
$sellLocked = isset($bData['sell_locked']) && $bData['sell_locked'] === true;

if ($action === 'buy' && $buyLocked) {
    $cAcc['buy_amount'] += $amount;
    file_put_contents($bAccPath, json_encode($bAccounts, JSON_PRETTY_PRINT));
    echo "Buying is currently unavailable. Order has been placed for €$amount.";
    exit;
}

if ($action === 'sell' && $sellLocked) {
    $cAcc['sell_amount'] += $amount;
    file_put_contents($bAccPath, json_encode($bAccounts, JSON_PRETTY_PRINT));
    echo "Selling is currently unavailable. Order has been placed for $amount coins.";
    exit;
}

$money = 0;
$feeAmountMoney = 0;
$coins = 0;
$feeAmountCoins = 0;

if (!isset($bData['balance_fees'])) {
    $bData['balance_fees'] = 0;
}
if (!isset($bData['supply_fees'])) {
    $bData['supply_fees'] = 0;
}

if ($action === 'buy') {
    if (isset($bData['buy_order_locked']) && $bData['buy_order_locked'] === false) {
        echo "Buy orders are disabled for this b.";
        exit;
    }

    $money = $amount;
    $networkSharePct = 0.025;
    $buyFeePct = isset($bData['buy_fees']) ? floatval($bData['buy_fees']) : 0.0;
    $feePct = $networkSharePct + $buyFeePct;
    $feeAmountMoney = $money * $feePct;
    $totalMoney = $money + $feeAmountMoney;

    if ($gAcc['wallet_balance'] < $totalMoney) {
        echo "Insufficient wallet balance to buy (including fees).";
        exit;
    }

    $coins = $money / $price;
    $feeAmountCoins = $coins * $feePct;
    $coinsAfterFees = $coins - $feeAmountCoins;

    $networkShareAmountCoins = $coins * $networkSharePct;

    $networkShareFile = 'data/networkshare.json';
    $networkShareData = file_exists($networkShareFile) ? json_decode(file_get_contents($networkShareFile), true) : ['total' => 0];
    $networkShareData['total'] += $money * $networkSharePct;
    file_put_contents($networkShareFile, json_encode($networkShareData, JSON_PRETTY_PRINT));

    $bData['balance_fees'] += $money * $buyFeePct;

} elseif ($action === 'sell') {
    if (isset($bData['sell_order_locked']) && $bData['sell_order_locked'] === false) {
        echo "Sell orders are disabled for this b.";
        exit;
    }

    $coins = $amount;
    if ($cAcc['balance'] < $coins) {
        echo "Insufficient b balance to sell.";
        exit;
    }

    $sellFeePct = isset($bData['sell_fees']) ? floatval($bData['sell_fees']) : 0.0;
    $feeAmountMoney = $coins * $price * $sellFeePct;
    $netMoney = $money - $feeAmountMoney;

    $ownerUsername = $bData['owner'];
    foreach ($globalAccounts as &$ownerAcc) {
        if ($ownerAcc['username'] === $ownerUsername) {
            $ownerAcc['wallet_balance'] += $feeAmountMoney;
            break;
        }
    }
} else {
    echo "Invalid action.";
    exit;
}

$aPath = "data/bs/$bId/a.json";
$a = file_exists($aPath) ? json_decode(file_get_contents($aPath), true) : [];

$tx = [
    'type' => $action === 'buy' ? 'transaction_buy' : 'transaction_sell',
    'amount' => $amount,
    'money' => $money,
    'fee_money' => $feeAmountMoney,
    'fee_coins' => $feeAmountCoins,
    'network_share_fee' => $money * $networkSharePct,
    'network_share_amount_coins' => $networkShareAmountCoins,
    'owner' => $username,
    'wallet_account' => $accountId,
    'timestamp' => date('Y-m-d H:i:s')
];

$a[] = $tx;
file_put_contents($aPath, json_encode($a, JSON_PRETTY_PRINT));

$cAcc['buy_amount'] = 0.0;
$cAcc['sell_amount'] = 0.0;

file_put_contents($bAccPath, json_encode($bAccounts, JSON_PRETTY_PRINT));
file_put_contents('data/accounts.json', json_encode($globalAccounts, JSON_PRETTY_PRINT));
$bs[$bId] = $bData;
file_put_contents('data/bs.json', json_encode($bs, JSON_PRETTY_PRINT));

$bFolder = "data/bs/$bId/a";
if (!is_dir($bFolder)) {
    mkdir($bFolder, 0777, true);
}

$nodeGeneratedFile = "data/bs/$bId/node_generated.json";
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
    'type' => $action === 'buy' ? 'transaction_buy' : 'transaction_sell',
    'amount' => $amount,
    'money' => $money,
    'fee_money' => $feeAmountMoney,
    'fee_coins' => $feeAmountCoins,
    'network_share_fee' => $money * $networkSharePct,
    'network_share_amount_coins' => $networkShareAmountCoins,
    'owner' => $username,
    'wallet_account' => $accountId,
    'timestamp' => date('Y-m-d H:i:s')
];

$nodeData[] = $aTransaction;
file_put_contents($nodeFile, json_encode($nodeData, JSON_PRETTY_PRINT));

echo "Transaction successful.";
?>