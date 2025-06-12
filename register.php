<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $username = trim($_GET['username']);
    $email = trim($_GET['email']);
    $password = $_GET['password'];

    $file = 'data/accounts.json';
    $accounts = [];

    if (file_exists($file)) {
        $json = file_get_contents($file);
        $accounts = json_decode($json, true) ?: [];

        foreach ($accounts as $acc) {
            if ($acc['username'] === $username) {
                die("Username already exists.");
            }
            if ($acc['email'] === $email) {
                die("Email already exists.");
            }
        }
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $account = [
        "username" => $username,
        "email" => $email,
        "password" => $hashed_password,
        "wallet_balance" => 0.00
    ];

    $accounts[] = $account;
    file_put_contents($file, json_encode($accounts, JSON_PRETTY_PRINT));

    echo "Account created successfully!";
}
?>
