<?php
// This script was written by ChatGPT, because I didn't know how to make download requests
// Set the latest version available on the server
$latest_version = "1.0";

// File to download if update is available
$file_to_download = "dob.zip";

// Get the user's version from the query string
$user_version = isset($_GET['version']) ? $_GET['version'] : null;

// If version not provided
if (!$user_version) {
    echo "Not specified.";
    exit;
}

// Compare versions
if (version_compare($user_version, $latest_version, '<')) {
    // Force download of the file
    $filepath = __DIR__ . '/' . $file_to_download;

    if (file_exists($filepath)) {
        // Set headers to force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_to_download) . '"');
        header('Content-Length: ' . filesize($filepath));
        flush();
        readfile($filepath);
        exit;
    } else {
        echo "ERR01";
    }
} else {
    echo "You're using the latest version.";
}
?>
