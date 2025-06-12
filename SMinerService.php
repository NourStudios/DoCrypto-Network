<?php

header('Content-Type: application/json');

$minerUsername = $_GET['miner_username'] ?? '';
$minerPassword = $_GET['miner_password'] ?? '';
$walletUsername = $_GET['wallet_username'] ?? ''; 

if (empty($minerUsername) || empty($minerPassword) || empty($walletUsername)) { 
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

if ($bs[$bId]['buy_order_locked'] === false || $bs[$bId]['sell_order_locked'] === false) {
    die(json_encode(['error' => 'Cannot process market action until both buy and sell orders are locked.']));
}

$supplyFolder = "data/bs/$bId/s";
if (is_dir($supplyFolder)) {
    $files = scandir($supplyFolder);
    foreach ($files as $file) {
        if (strpos($file, 'node_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $filePath = $supplyFolder . '/' . $file;
            $solvedNodeFile = $supplyFolder . '/solved_' . str_replace('node_', '', $file); 

            if (file_exists($solvedNodeFile)) {
                continue; 
            }
            $nodeData = json_decode(file_get_contents($filePath), true);
            if (!isset($nodeData['solved']) || !$nodeData['solved']) {

                $nodeSupply = 0;
                $nodeStakeSupply = 0;
                foreach ($nodeData as &$tx) { 
                    if (!is_array($tx) || !isset($tx['type'])) continue;
                    switch($tx['type']) {
                        case 'added_supply':
                            $nodeSupply += floatval($tx['amount']);
                            break;
                        case 'removed_supply':
                            $nodeSupply -= floatval($tx['amount']);
                            break;
                        case 'added_stake_supply':
                            $nodeStakeSupply += floatval($tx['amount']);
                            break;
                        case 'transaction_buy':
                            $nodeSupply += floatval($tx['amount']);
                            break;
                        case 'transaction_sell':
                            $nodeSupply -= floatval($tx['amount']);
                            break;
                    }

                    if (isset($tx['network_share_amount_coins'])) {
                        $nodeSupply += floatval($tx['network_share_amount_coins']);
                    }
                }

                if (!isset($nodeData['solvers'])) {
                    $nodeData['solvers'] = [];
                }
                if (!in_array($walletUsername, $nodeData['solvers'])) {
                    $nodeData['solvers'][] = $walletUsername;
                }
                $nodeData['solved'] = true;

                $nodeData['supply_change'] = $nodeSupply + $nodeStakeSupply;
                $nodeData['stake_supply_change'] = $nodeStakeSupply;

                $solvedNodeFile = $supplyFolder . '/solved_' . str_replace('node_', '', $file); 
                file_put_contents($solvedNodeFile, json_encode($nodeData, JSON_PRETTY_PRINT));
                unlink($filePath);
            }
        }
    }
}

$balanceFolder = "data/bs/$bId/a";
if (is_dir($balanceFolder)) {
    $files = scandir($balanceFolder);
    foreach ($files as $file) {
        if (strpos($file, 'node_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $filePath = $balanceFolder . '/' . $file;
            $solvedNodeFile = $balanceFolder . '/solved_' . str_replace('node_', '', $file); 

            if (file_exists($solvedNodeFile)) {
                continue; 
            }
            $nodeData = json_decode(file_get_contents($filePath), true);
            if (!isset($nodeData['solved']) || !$nodeData['solved']) {

                $nodeBalance = 0;
                $nodeFees = 0;
                $totalNetworkShareAmountCoins = 0; 

                foreach ($nodeData as &$tx) { 
                    if (!is_array($tx) || !isset($tx['type'])) continue;
                    switch($tx['type']) {
                        case 'transaction_buy':
                        case 'transaction_sell':
                            $nodeBalance += floatval($tx['amount']);
                            break;
                        case 'transfer':
                            $nodeBalance -= floatval($tx['amount']); 
                            break;
                        case 'transfer_fees':
                            $nodeBalance += floatval($tx['amount']); 
                            break;
                    }
                    if($tx['type'] == 'transaction_buy' || $tx['type'] == 'transaction_sell'){
                        $nodeFees += floatval($tx['fee_money'] ?? 0);
                        $totalNetworkShareAmountCoins += floatval($tx['network_share_amount_coins'] ?? 0); 
                        $tx['network_share_fee'] = floatval($tx['network_share_fee'] ?? 0); 
                        $tx['network_share_amount_coins'] = floatval($tx['network_share_amount_coins'] ?? 0); 
                    }
                }

                if (!isset($nodeData['solvers'])) {
                    $nodeData['solvers'] = [];
                }
                if (!in_array($walletUsername, $nodeData['solvers'])) {
                    $nodeData['solvers'][] = $walletUsername;
                }
                $nodeData['solved'] = true;
                $nodeData['amount'] = $nodeBalance;
                $nodeData['fee_money'] = $nodeFees;
                $nodeData['total_network_share_amount_coins'] = $totalNetworkShareAmountCoins;

                $solvedNodeFile = $balanceFolder . '/solved_' . str_replace('node_', '', $file);
                file_put_contents($solvedNodeFile, json_encode($nodeData, JSON_PRETTY_PRINT));
                unlink($filePath);

                $numSolvers = count($nodeData['solvers']);
                if ($numSolvers > 0) {
                    $rewardPerSolver = $totalNetworkShareAmountCoins / $numSolvers;

                    $bAccPath = "data/bs/$bId/accounts.json";
                    $bAccounts = json_decode(file_get_contents($bAccPath), true);

                    foreach ($nodeData['solvers'] as $solverUsername) {
                        foreach ($bAccounts as &$account) {
                            if ($account['username'] === $solverUsername) {
                                $account['balance'] = isset($account['balance']) ?
                                    $account['balance'] + $rewardPerSolver :
                                    $rewardPerSolver;
                                break;
                            }
                        }
                    }
                    file_put_contents($bAccPath, json_encode($bAccounts, JSON_PRETTY_PRINT));
                }
            }
        }
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Nodes solved and rewards distributed successfully.'
]);
?>