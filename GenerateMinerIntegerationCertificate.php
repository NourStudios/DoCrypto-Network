<?php
$bId = $_GET['b_id'] ?? '';
$passwordInput = $_GET['password'] ?? '';

if (empty($bId) || empty($passwordInput)) {
    http_response_code(400);
    echo "Error: b ID and password are required.";
    exit;
}

$bsFile = "data/bs.json";
if (!file_exists($bsFile)) {
    http_response_code(500);
    echo "Error: bs.json not found.";
    exit;
}
$bs = json_decode(file_get_contents($bsFile), true);

if (!isset($bs[$bId])) {
    http_response_code(404);
    echo "Error: b ID not found.";
    exit;
}

$storedPassword = $bs[$bId]['password'] ?? null;
if ($storedPassword === null || $storedPassword !== $passwordInput) {
    http_response_code(401);
    echo "Error: Invalid password.";
    exit;
}

$username = bin2hex(random_bytes(16));

$password = bin2hex(random_bytes(32));

$certificateData = [
    "b_id" => $bId,
    "username" => $username,
    "password" => $password,
    "created_at" => date("Y-m-d H:i:s")
];

$certificatesFile = "data/miners/certificates.json";
if (file_exists($certificatesFile)) {
    $certificates = json_decode(file_get_contents($certificatesFile), true);
} else {
    $certificates = [];
}

$certificates[] = $certificateData;

if (!is_dir(dirname($certificatesFile))) {
    mkdir(dirname($certificatesFile), 0777, true);
}
if (file_put_contents($certificatesFile, json_encode($certificates, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500); 
    echo "Error: Failed to save certificate data.";
    exit;
}

header('Content-Type: application/json');
echo json_encode($certificateData);
?>