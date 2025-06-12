<?php
$bId = $_GET['b'] ?? '';
$amount = intval($_GET['amount'] ?? 0);
$passwordInput = $_GET['password'] ?? '';

if (!$bId || $amount <= 0 || !$passwordInput) {
    die("Missing b ID, invalid amount, or missing password.");
}

$bsFile = 'data/bs.json';
$accountsFile = 'data/accounts_dev.json';

if (!file_exists($bsFile) || !file_exists($accountsFile)) {
    die("Required data file(s) missing.");
}

$bs = json_decode(file_get_contents($bsFile), true);
$accounts = json_decode(file_get_contents($accountsFile), true);

if (!isset($bs[$bId])) {
    die("b not found.");
}

$b = &$bs[$bId];

$storedPassword = $b['password'] ?? '';
if ($storedPassword !== $passwordInput) {
    die("Invalid password.");
}

$aFolder = "data/bs/$bId/a";
if (is_dir($aFolder)) {
    foreach (glob("$aFolder/node_*.json") as $nodeFile) {
        $nodeData = json_decode(file_get_contents($nodeFile), true);

        if (!isset($nodeData['solved']) || !$nodeData['solved']) {
            die("Cannot remove supply: There are unsolved nodes in the blockchain. Please solve all nodes first.");
        }
    }
}

$b['sold'] = $b['sold'] ?? 0;
$b['added_supply'] = $b['added_supply'] ?? 0;
$b['locked'] = $b['locked'] ?? false;
$b['lock_time'] = $b['lock_time'] ?? 0;

$now = time();
$lockDuration = 5;

if ($b['locked']) {
    $lockAge = $now - $b['lock_time'];
    if ($lockAge < $lockDuration) {
        die("b is currently locked. Please wait " . ($lockDuration - $lockAge) . " seconds.");
    } else {
        $b['locked'] = false;
        $b['lock_time'] = 0;
    }
}

if ($b['added_supply'] < $amount) {
    die("Not enough added supply to remove.");
}

$b['locked'] = true;
$b['lock_time'] = $now;
file_put_contents($bsFile, json_encode($bs, JSON_PRETTY_PRINT));

sleep($lockDuration);

$bs = json_decode(file_get_contents($bsFile), true);
$b = &$bs[$bId];

$b['added_supply'] -= $amount;

$soldReduction = round($amount * 0.95);
$b['sold'] = max(0, $b['sold'] - $soldReduction);

$sold = $b['sold'];
$balance = $b['balance'];
$b['price'] = ($sold > 0) ? $balance / $sold : 0;

$b['locked'] = false;
$b['lock_time'] = 0;

file_put_contents($bsFile, json_encode($bs, JSON_PRETTY_PRINT));
file_put_contents($accountsFile, json_encode($accounts, JSON_PRETTY_PRINT));

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

$transaction = [
    "removed_supply" => $amount,
    "timestamp" => time()
];

$nodeData[] = $transaction;
file_put_contents($nodeFile, json_encode($nodeData, JSON_PRETTY_PRINT));

echo "Removed $amount supply.<br>";
?>