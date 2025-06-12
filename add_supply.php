<?php
$bId = $_GET['b'] ?? '';
$amount = intval($_GET['amount'] ?? 0);
$passwordInput = $_GET['password'] ?? '';

if (!$bId || $amount <= 0 || !$passwordInput) {
    die("Missing b ID, amount, or password.");
}

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    die("bs file not found.");
}

$bs = json_decode(file_get_contents($bsFile), true);

if (!isset($bs[$bId])) {
    die("b not found.");
}

$storedPassword = $bs[$bId]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    die("Invalid password for this b.");
}

$bs[$bId]['sold'] = $bs[$bId]['sold'] ?? 0;
$bs[$bId]['added_supply'] = $bs[$bId]['added_supply'] ?? 0;
$bs[$bId]['locked'] = $bs[$bId]['locked'] ?? false;
$bs[$bId]['lock_time'] = $bs[$bId]['lock_time'] ?? 0;

$now = time();
$lockDuration = 5; // seconds

if ($bs[$bId]['locked']) {
    $lockAge = $now - $bs[$bId]['lock_time'];
    if ($lockAge < $lockDuration) {
        die("b is currently locked. Please wait " . ($lockDuration - $lockAge) . " seconds.");
    } else {
        $bs[$bId]['locked'] = false;
        $bs[$bId]['lock_time'] = 0;
    }
}

$bs[$bId]['locked'] = true;
$bs[$bId]['lock_time'] = $now;
file_put_contents($bsFile, json_encode($bs, JSON_PRETTY_PRINT));

sleep($lockDuration);

$bs = json_decode(file_get_contents($bsFile), true);

// a logic
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
    "added_supply" => $amount,
    "timestamp" => time()
];

$nodeData[] = $transaction;
file_put_contents($nodeFile, json_encode($nodeData, JSON_PRETTY_PRINT));

$bs[$bId]['locked'] = false;
$bs[$bId]['lock_time'] = 0;
file_put_contents($bsFile, json_encode($bs, JSON_PRETTY_PRINT));

echo "Successfully added $amount to a.";
?>