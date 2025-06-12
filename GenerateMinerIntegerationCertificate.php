<?php
// filepath: c:\Users\abdul\Documents\Dob Network\mining\GenerateMinerIntegerationCertificate.php

// Get the b ID and password from the GET request
$bId = $_GET['b_id'] ?? '';
$passwordInput = $_GET['password'] ?? '';

// Validate the input
if (empty($bId) || empty($passwordInput)) {
    http_response_code(400); // Bad Request
    echo "Error: b ID and password are required.";
    exit;
}

// Load the bs data
$bsFile = "data/bs.json";
if (!file_exists($bsFile)) {
    http_response_code(500); // Internal Server Error
    echo "Error: bs.json not found.";
    exit;
}
$bs = json_decode(file_get_contents($bsFile), true);

// Check if the b ID exists
if (!isset($bs[$bId])) {
    http_response_code(404); // Not Found
    echo "Error: b ID not found.";
    exit;
}

// Verify the password
$storedPassword = $bs[$bId]['password'] ?? null;
if ($storedPassword === null || $storedPassword !== $passwordInput) {
    http_response_code(401); // Unauthorized
    echo "Error: Invalid password.";
    exit;
}

// Generate a 32-character username
$username = bin2hex(random_bytes(16));

// Generate a 64-character password
$password = bin2hex(random_bytes(32));

// Create the miner certificate data
$certificateData = [
    "b_id" => $bId,
    "username" => $username,
    "password" => $password,
    "created_at" => date("Y-m-d H:i:s")
];

// Load the existing certificates
$certificatesFile = "data/miners/certificates.json";
if (file_exists($certificatesFile)) {
    $certificates = json_decode(file_get_contents($certificatesFile), true);
} else {
    $certificates = [];
}

// Add the new certificate
$certificates[] = $certificateData;

// Save the certificates to the file
if (!is_dir(dirname($certificatesFile))) {
    mkdir(dirname($certificatesFile), 0777, true);
}
if (file_put_contents($certificatesFile, json_encode($certificates, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500); // Internal Server Error
    echo "Error: Failed to save certificate data.";
    exit;
}

// Output the certificate data as JSON
header('Content-Type: application/json');
echo json_encode($certificateData);
?>