<?php
$bId = $_GET['b'] ?? '';
$feePercentage = floatval($_GET['fee'] ?? 0);
$passwordInput = $_GET['password'] ?? '';

if (!$bId || $feePercentage < 0 || $feePercentage > 1 || !$passwordInput) {
    die("Missing b ID, invalid fee percentage, or missing password.");
}

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    die("bs.json not found.");
}

$bs = json_decode(file_get_contents($bsFile), true);

if (!isset($bs[$bId])) {
    die("b not found.");
}

$storedPassword = $bs[$bId]['password'] ?? null;
if (!$storedPassword || $storedPassword !== $passwordInput) {
    die("Invalid password for this b.");
}

$bs[$bId]['buy_fees'] = $feePercentage;

file_put_contents($bsFile, json_encode($bs, JSON_PRETTY_PRINT));

echo "Successfully set buy fees to " . ($feePercentage * 100) . "%.";
?>