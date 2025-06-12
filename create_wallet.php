<?php
$public_address = $_GET['public_address'] ?? null;

if (!$public_address) {
    echo "Missing public address.";
    exit;
}

$bs = json_decode(file_get_contents('data/bs.json'), true);
$b_id = null;

foreach ($bs as $b) {
    if ($b['public_address'] === $public_address) {
        $b_id = $b['id'];
        break;
    }
}

if (!$b_id) {
    echo "b not found.";
    exit;
}

$url = "http://dobnet.infinityfreeapp.com/data/bs/$b_id/create_account.php";
$postData = http_build_query([
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
$response = curl_exec($ch);
curl_close($ch);

echo "Username: $username\nPassword: $password_plain";
?>
