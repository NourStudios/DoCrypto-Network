<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['name'], $_GET['short'])) {
    die("Please provide 'name' and 'short'.");
}

$name = trim($_GET['name']);
$short = strtoupper(trim($_GET['short']));

$format_type = $_GET['format_type'] ?? 'hash';
$format_length = (int)($_GET['format_length'] ?? 32);

$global_bs_file = "data/bs.json";
$global_bs = file_exists($global_bs_file) ? json_decode(file_get_contents($global_bs_file), true) : [];
foreach ($global_bs as $b) {
    if (strcasecmp($b['name'], $name) === 0) {
        die("b with name '$name' already exists.");
    }
}

$b_id = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $short)) . "_" . substr(md5(uniqid(mt_rand(), true)), 0, 8);
$b_folder = "bs/$b_id";

if (!is_dir("data")) {
    mkdir("data", 0777, true);
}
if (!is_dir("data/bs")) {
    mkdir("data/bs", 0777, true);
}
if (!is_dir("data/$b_folder")) {
    mkdir("data/$b_folder", 0777, true);
}

$genesis_block = [
    "index" => 0,
    "timestamp" => time(),
    "transaction" => "Genesis Block",
    "previous_hash" => "0",
    "hash" => hash("sha256", "Genesis Block")
];
file_put_contents("data/$b_folder/a.json", json_encode([$genesis_block], JSON_PRETTY_PRINT));

$public_address = '0x' . substr(hash('sha256', $b_id . time()), 0, 40);
$password = bin2hex(random_bytes(32));

$b_data = [
    "id" => $b_id,
    "name" => $name,
    "short" => $short,
    "balance" => 0,
    "sold" => 0,
    "price" => 1.0,
    "owner_pct" => 0.15,
    "locked" => false,
    "busy" => false,
    "owner" => null,
    "public_address" => $public_address,
    "password" => $password,
    "wallet_format" => [
        "type" => $format_type,
        "length" => $format_length
    ]
];
file_put_contents("data/$b_folder/data.json", json_encode($b_data, JSON_PRETTY_PRINT));

$global_bs[$b_id] = $b_data;
file_put_contents($global_bs_file, json_encode($global_bs, JSON_PRETTY_PRINT));

$create_account_code = <<<PHP
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

\$b_id = basename(__DIR__);
\$data_path = __DIR__ . '/data.json';
if (!file_exists(\$data_path)) die("Missing data.json.");
\$b_data = json_decode(file_get_contents(\$data_path), true);
\$wallet_format = \$b_data['wallet_format'] ?? ['type' => 'hash', 'length' => 32];
\$type = \$wallet_format['type'] ?? 'hash';
\$length = (int)(\$wallet_format['length'] ?? 32);

\$password_plain = \$_GET['password'] ?? null;

function generateRandomString(\$length, \$type = 'hash') {
    if (\$type === 'hash') {
        return substr(bin2hex(random_bytes(ceil(\$length / 2))), 0, \$length);
    } else {
        \$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        \$output = '';
        for (\$i = 0; \$i < \$length; \$i++) {
            \$output .= \$chars[random_int(0, strlen(\$chars) - 1)];
        }
        return \$output;
    }
}

if (!\$password_plain) {
    \$password_plain = generateRandomString(\$length, \$type);
}

function generateUsername(\$type, \$length) {
    return generateRandomString(\$length, \$type);
}

\$accounts_file = __DIR__ . '/accounts.json';
\$accounts = file_exists(\$accounts_file) ? json_decode(file_get_contents(\$accounts_file), true) : [];

if (isset(\$accounts[0]) && is_array(\$accounts[0]) && isset(\$accounts[0]['balance'])) {
    \$acc_assoc = [];
    foreach (\$accounts as \$user => \$data) {
        if (is_array(\$data) && isset(\$data['username'])) {
            \$acc_assoc[\$data['username']] = \$data;
        }
    }
    \$accounts = \$acc_assoc;
}

do {
    \$username = generateUsername(\$type, \$length);
} while (isset(\$accounts[\$username]));

\$accounts[\$username] = [
    'password' => password_hash(\$password_plain, PASSWORD_DEFAULT),
    'balance' => 0,
    'created_at' => date('Y-m-d H:i:s')
];
file_put_contents(\$accounts_file, json_encode(\$accounts, JSON_PRETTY_PRINT));

if (isset(\$_GET['wallet_filename'])) {
    \$wallet_filename = basename(\$_GET['wallet_filename']);
    file_put_contents(__DIR__ . '/' . \$wallet_filename, json_encode(['username' => \$username], JSON_PRETTY_PRINT));
}

echo "\$username\\n\$password_plain";
?>
PHP;
file_put_contents("data/$b_folder/create_account.php", $create_account_code);

$send_receive_code = <<<PHP
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset(\$_GET['from'], \$_GET['to'], \$_GET['amount'], \$_GET['password'], \$_GET['username'])) {
    die("Please provide 'from', 'to', 'amount', 'password', and 'username'.");
}

\$from = trim(\$_GET['from']);
\$to = trim(\$_GET['to']);
\$amount = floatval(\$_GET['amount']);
\$password = \$_GET['password'];
\$username = \$_GET['username'];

if (\$amount <= 0) die("Amount must be greater than 0.");

\$accounts_file = __DIR__ . '/accounts.json';
if (!file_exists(\$accounts_file)) die("Accounts file missing.");
\$accounts = json_decode(file_get_contents(\$accounts_file), true);

if (isset(\$accounts[0]) && is_array(\$accounts[0]) && isset(\$accounts[0]['balance'])) {
    \$acc_assoc = [];
    foreach (\$accounts as \$user => \$data) {
        if (is_array(\$data) && isset(\$data['username'])) {
            \$acc_assoc[\$data['username']] = \$data;
        }
    }
    \$accounts = \$acc_assoc;
}

if (!isset(\$accounts[\$from])) die("Sender '\$from' not found.");
if (!password_verify(\$password, \$accounts[\$from]['password'])) die("Invalid password.");
if (!isset(\$accounts[\$to])) die("Recipient '\$to' not found.");
if (\$accounts[\$from]['balance'] < \$amount) die("Insufficient balance.");

\$accounts[\$from]['balance'] -= \$amount;
\$accounts[\$to]['balance'] += \$amount;
file_put_contents(\$accounts_file, json_encode(\$accounts, JSON_PRETTY_PRINT));

\$bFolder = __DIR__ . '/a';
if (!is_dir(\$bFolder)) {
    mkdir(\$bFolder, 0777, true);
}

\$nodeGeneratedFile = __DIR__ . '/node_generated.json';
if (!file_exists(\$nodeGeneratedFile)) {
    file_put_contents(\$nodeGeneratedFile, json_encode(['count' => 0], JSON_PRETTY_PRINT));
}
\$nodeGenerated = json_decode(file_get_contents(\$nodeGeneratedFile), true);
\$nodeCount = \$nodeGenerated['count'];

\$nodeFile = "\$bFolder/node_\$nodeCount.json";

if (!file_exists(\$nodeFile)) {
    file_put_contents(\$nodeFile, json_encode([], JSON_PRETTY_PRINT));
}

\$nodeData = json_decode(file_get_contents(\$nodeFile), true);

if (count(\$nodeData) >= 100) {
    \$nodeCount++;
    \$nodeFile = "\$bFolder/node_\$nodeCount.json";
    file_put_contents(\$nodeFile, json_encode([], JSON_PRETTY_PRINT));
    \$nodeData = [];

    \$nodeGenerated['count'] = \$nodeCount;
    file_put_contents(\$nodeGeneratedFile, json_encode(\$nodeGenerated, JSON_PRETTY_PRINT));
}

\$transaction = [
    "from" => \$from,
    "to" => \$to,
    "amount" => \$amount,
    "timestamp" => time()
];

\$nodeData[] = \$transaction;
file_put_contents(\$nodeFile, json_encode(\$nodeData, JSON_PRETTY_PRINT));

\$a_file = __DIR__ . '/a.json';
\$a = file_exists(\$a_file) ? json_decode(file_get_contents(\$a_file), true) : [];
\$prev_hash = end(\$a)['hash'] ?? '0';
\$new_block = [
    "index" => count(\$a),
    "timestamp" => time(),
    "transaction" => "\$from sent \$amount to \$to",
    "previous_hash" => \$prev_hash,
    "hash" => hash("sha256", "\$from-\$to-\$amount-" . time())
];
\$a[] = \$new_block;
file_put_contents(\$a_file, json_encode(\$a, JSON_PRETTY_PRINT));
echo "Transaction successful: \$from sent \$amount to \$to.";
?>
PHP;
file_put_contents("data/$b_folder/send_receive.php", $send_receive_code);

$accounts_file = "data/$b_folder/accounts.json";

$username = 'temp_user_' . uniqid();

if (!isset($accounts[$username]['bs'])) {
    $accounts[$username]['bs'] = [];
}
if (!in_array($b_id, $accounts[$username]['bs'])) {
    $accounts[$username]['bs'][] = $b_id;
}
file_put_contents($accounts_file, json_encode($accounts, JSON_PRETTY_PRINT));

echo "b ID: $b_id<br>";
echo "Public Address: $public_address<br>";
echo "Password : $password<br>";
?>