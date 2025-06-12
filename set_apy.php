<?php

$bId = $_GET['b_id'] ?? '';
$password = $_GET['password'] ?? '';
$apy = floatval($_GET['apy'] ?? 0);
$days = intval($_GET['days'] ?? 0);
$stakeMax = isset($_GET['stake_max']) ? floatval($_GET['stake_max']) : 0;

if (!$bId || !$password || $apy <= 0 || $days <= 0) {
    die("Missing or invalid parameters.");
}

$bsFile = 'data/bs.json';
if (!file_exists($bsFile)) {
    die("b data file not found.");
}

$bs = json_decode(file_get_contents($bsFile), true);
if (!isset($bs[$bId])) {
    die("Invalid b ID.");
}

if ($bs[$bId]['password'] !== $password) {
    die("Invalid b password.");
}

$bs[$bId]['apy'] = $apy;
$bs[$bId]['apy_days'] = $days;
if ($stakeMax > 0) {
    $bs[$bId]['stake_max'] = $stakeMax;
}

file_put_contents($bsFile, json_encode($bs, JSON_PRETTY_PRINT));

echo "APY set successfully to " . $apy . "% for " . $days . " days for b ID: " . $bId;
if ($stakeMax > 0) {
    echo " Stake maximum set to $stakeMax.";
}
?>