<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$amount = $_GET['amount'] ?? '';
$password = $_GET['password'] ?? '';
$public_address = $_GET['public_address'] ?? '';

if (!$from || !$to || !$amount || !$password || !$public_address) {
    http_response_code(400);
    echo "Missing one or more required parameters: from, to, amount, password, public_address.";
    exit;
}

$bsPath = __DIR__ . 'data/bs.json';
if (!file_exists($bsPath)) {
    http_response_code(500);
    echo "bs.json not found.";
    exit;
}

$bs = json_decode(file_get_contents($bsPath), true);

$b_id = null;
foreach ($bs as $id => $b) {
    if (isset($b['public_address']) && $b['public_address'] === $public_address) {
        $b_id = $id;
        break;
    }
}

if (!$b_id) {
    http_response_code(404);
    echo "b with the provided public address not found.";
    exit;
}

$target = "http://dobnet.infinityfreeapp.com/data/bs/$b_id/send_receive.php" .
          "?from=" . urlencode($from) .
          "&to=" . urlencode($to) .
          "&amount=" . urlencode($amount) .
          "&password=" . urlencode($password);

header("Location: $target");
exit;
