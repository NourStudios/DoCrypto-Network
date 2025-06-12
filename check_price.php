<?php
$publicAddress = $_GET['public_address'] ?? '';

if (!$publicAddress || !$passwordInput) {
    die("No public address or password provided.");
}

$file = 'data/bs.json';

if (!file_exists($file)) {
    die("bs.json not found.");
}

$bs = json_decode(file_get_contents($file), true);

$bId = null;
foreach ($bs as $key => $b) {
    if ($b['public_address'] === $publicAddress) {
        $bId = $key;
        break;
    }
}

if ($bId === null) {
    die("b with public address '$publicAddress' does NOT exist.");
}

$price = $bs[$bId]['price'] ?? 0;

echo $price;