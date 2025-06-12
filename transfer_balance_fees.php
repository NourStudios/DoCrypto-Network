<?php
$bId = $_GET['b_id'] ?? '';
$amount = floatval($_GET['amount'] ?? 0);
$passwordInput = $_GET['password'] ?? ''; 

if (!$bId || $amount <= 0 || !$passwordInput) {
    echo "Missing or invalid parameters.";
    exit;
}

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    echo "ERR01";
    exit;
}
$bs = json_decode(file_get_contents($bsFile), true);

if (!isset($bs[$bId])) {
    echo "b not found.";
    exit;
}

$storedPassword = $bs[$bId]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    http_response_code(401);
    echo "Invalid password for this b.";
    exit;
}

$aFolder = "data/bs/$bId/a";
if (is_dir($aFolder)) {
    foreach (glob("$aFolder/node_*.json") as $nodeFile) {
        $nodeData = json_decode(file_get_contents($nodeFile), true);
        if (!isset($nodeData['solved']) || !$nodeData['solved']) {
            echo "Cannot transfer: There are unsolved nodes in the blockchain. Please solve all nodes first.";
            exit;
        }
    }
}

$b = &$bs[$bId];

if (!isset($b['balance_fees']) || $b['balance_fees'] < $amount) {
    echo "Insufficient b balance fees.";
    exit;
}

$b['balance_fees'] -= $amount;
$b['balance'] += $amount;

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
    "type" => "transfer_fees", 
    "amount" => $amount, 
    "timestamp" => time()
];

$nodeData[] = $transaction;
file_put_contents($nodeFile, json_encode($nodeData, JSON_PRETTY_PRINT));

file_put_contents($bsFile, json_encode($bs, JSON_PRETTY_PRINT));

echo "Successful.";
?>