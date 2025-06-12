<?php
$bId = $_GET['b_id'] ?? '';
$marketAction = $_GET['market_action'] ?? '';
$userParam = $_GET['users'] ?? [];
$passwordInput = $_GET['password'] ?? ''; 

if (!$bId || !$passwordInput) { 
    http_response_code(400);
    echo "Missing b ID or password.";
    exit;
}

$validActions = [
    'open_buy', 'open_sell', 'open_both',
    'close_buy', 'close_sell', 'close_both',
    'close',
    'open_buy_order', 'open_sell_order', 'open_both_order',
    'close_buy_order', 'close_sell_order', 'close_both_orders'
];

if (!in_array($marketAction, $validActions)) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

$bs = json_decode(file_get_contents('data/bs.json'), true);
if (!isset($bs[$bId])) {
    http_response_code(404);
    echo "b not found.";
    exit;
}

$storedPassword = $bs[$bId]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    echo "Invalid password for this b.";
    exit;
}

$bData = &$bs[$bId]; 
$publicAddress = $bData['public_address'] ?? '';

$globalAccounts = json_decode(file_get_contents('data/accounts.json'), true);
$bAccPath = "data/bs/$bId/accounts.json";
$bAccounts = file_exists($bAccPath) ? json_decode(file_get_contents($bAccPath), true) : [];

$price = floatval($bData['price']);

$nodeDir = "data/bs/$bId/a";
$nodeTxCount = 0;
if (is_dir($nodeDir)) {
    foreach (glob("$nodeDir/node_*.json") as $nodeFile) {
        $nodeData = json_decode(file_get_contents($nodeFile), true);

        if (is_array($nodeData) && (!isset($nodeData['solved']) || !$nodeData['solved'])) {
            $nodeTxCount += count($nodeData);
        }
    }
}

$aPath = "data/bs/$bId/a.json";
$aBlocks = file_exists($aPath) ? json_decode(file_get_contents($aPath), true) : [];
$blockTxCount = count($aBlocks);

if (
    ($marketAction === 'open_buy' || $marketAction === 'open_sell' || $marketAction === 'open_both' ||
     $marketAction === 'open_buy_order' || $marketAction === 'open_sell_order' || $marketAction === 'open_both_order')
    && $nodeTxCount > 0
) {
    http_response_code(409);
    echo "Cannot open market: There are $nodeTxCount unsolved node transactions. Please solve all nodes first.";
    exit;
}

switch ($marketAction) {
    case 'open_buy':
        $bData['buy_locked'] = false;
        $bData['buy_order_locked'] = false;
        break;
    case 'open_sell':
        $bData['sell_locked'] = false;
        $bData['sell_order_locked'] = false;
        break;
    case 'open_both':
        $bData['buy_locked'] = false;
        $bData['sell_locked'] = false;
        $bData['buy_order_locked'] = false;
        $bData['sell_order_locked'] = false;
        break;
    case 'close_buy':
        $bData['buy_locked'] = true;
        break;
    case 'close_sell':
        $bData['sell_locked'] = true;
        break;
    case 'close_both':
        $bData['buy_locked'] = true;
        $bData['sell_locked'] = true;
        break;
    case 'close':
        $bData['locked'] = true;
        $bData['unlock'] = [];
        break;
    case 'open_buy_order':
        $bData['buy_order_locked'] = false;
        break;
    case 'open_sell_order':
        $bData['sell_order_locked'] = false;
        break;
    case 'open_both_order':
        $bData['buy_order_locked'] = false;
        $bData['sell_order_locked'] = false;
        break;
    case 'close_buy_order':
        $bData['buy_order_locked'] = true;
        break;
    case 'close_sell_order':
        $bData['sell_order_locked'] = true;
        break;
    case 'close_both_orders':
        $bData['buy_order_locked'] = true;
        $bData['sell_order_locked'] = true;
        break;
}

if ($userParam === 'everyone') {
    $bData['locked'] = false;
    $bData['unlock'] = [];
} elseif (!empty($userParam)) {
    $userList = is_array($userParam) ? $userParam : [$userParam];
    $bData['unlock'] = array_unique(array_merge($bData['unlock'] ?? [], $userList));
    $bData['locked'] = true;
} else {
    $bData['locked'] = false;
    $bData['unlock'] = [];
}

foreach ($globalAccounts as &$gAcc) {
    $username = $gAcc['username'];
    $wallets = $gAcc['wallets'] ?? [];
    if (!isset($wallets[$publicAddress])) continue;

    $accId = $wallets[$publicAddress];
    if (!isset($bAccounts[$accId])) continue;

    $cAcc = &$bAccounts[$accId];
    if (!isset($cAcc['buy_amount'])) $cAcc['buy_amount'] = 0.0;
    if (!isset($cAcc['sell_amount'])) $cAcc['sell_amount'] = 0.0;

    if (!$bData['buy_locked'] && !$bData['buy_order_locked']) { 
        $buyAmount = floatval($cAcc['buy_amount']);
        if ($buyAmount > 0) {
            $money = $buyAmount;
            $networkSharePct = 0.025; 
            $buyFeePct = isset($bData['buy_fees']) ? floatval($bData['buy_fees']) : 0.0; 
            $feePct = $networkSharePct + $buyFeePct; 
            $feeAmountMoney = $money * $feePct;
            $totalMoney = $money + $feeAmountMoney;

            if ($gAcc['wallet_balance'] >= $totalMoney) {
                $coins = $money / $price;
                $feeAmountCoins = $coins * $feePct; 
                $coinsAfterFees = $coins - $feeAmountCoins; 

                $networkShareAmountCoins = $coins * $networkSharePct;

                $networkShareFile = 'data/networkshare.json';
                $networkShareData = file_exists($networkShareFile) ? json_decode(file_get_contents($networkShareFile), true) : ['total' => 0];
                $networkShareData['total'] += $money * $networkSharePct;
                file_put_contents($networkShareFile, json_encode($networkShareData, JSON_PRETTY_PRINT));

                $bData['balance_fees'] += $money * $buyFeePct;

                $gAcc['wallet_balance'] -= $totalMoney;
                $cAcc['balance'] += $coinsAfterFees;
                $bData['sold'] += $coinsAfterFees;

                $aPath = "data/bs/$bId/a.json";
                $a = file_exists($aPath) ? json_decode(file_get_contents($aPath), true) : [];

                $tx = [
                    'type' => 'transaction_buy',
                    'amount' => $coinsAfterFees,
                    'money' => $money,
                    'fee_money' => $feeAmountMoney,
                    'fee_coins' => $feeAmountCoins,
                    'network_share_fee' => $money * $networkSharePct, 
                    'network_share_amount_coins' => $networkShareAmountCoins, 
                    'owner' => $username,
                    'wallet_account' => $accId,
                    'timestamp' => date('Y-m-d H:i:s')
                ];

                $a[] = $tx;
                file_put_contents($aPath, json_encode($a, JSON_PRETTY_PRINT));
            }
            $cAcc['buy_amount'] = 0.0;
        }
    }

    if (!$bData['sell_locked'] && !$bData['sell_order_locked']) { 
        $sellAmount = floatval($cAcc['sell_amount']);
        if ($sellAmount > 0) {
            $coins = $sellAmount;
            if ($cAcc['balance'] >= $coins) {
                $sellFeePct = isset($bData['sell_fees']) ? floatval($bData['sell_fees']) : 0.0; 
                $money = $coins * $price;
                $feeAmountMoney = $money * $sellFeePct;
                $netMoney = $money - $feeAmountMoney;

                $ownerUsername = $bData['owner'];
                foreach ($globalAccounts as &$ownerAcc) {
                    if ($ownerAcc['username'] === $ownerUsername) {
                        $ownerAcc['wallet_balance'] += $feeAmountMoney;
                        break;
                    }
                }

                $cAcc['balance'] -= $coins;
                $gAcc['wallet_balance'] += $netMoney;
                $bData['sold'] -= $coins;

                $aPath = "data/bs/$bId/a.json";
                $a = file_exists($aPath) ? json_decode(file_get_contents($aPath), true) : [];

                $tx = [
                    'type' => 'transaction_sell',
                    'amount' => $coins,
                    'money' => $netMoney,
                    'fee_money' => $feeAmountMoney,
                    'owner' => $username,
                    'wallet_account' => $accId,
                    'timestamp' => date('Y-m-d H:i:s')
                ];

                $a[] = $tx;
                file_put_contents($aPath, json_encode($a, JSON_PRETTY_PRINT));
            }
            $cAcc['sell_amount'] = 0.0;
        }
    }
}

$bs[$bId] = $bData;
file_put_contents('data/bs.json', json_encode($bs, JSON_PRETTY_PRINT));

echo "Market action '$marketAction' completed.";
?>