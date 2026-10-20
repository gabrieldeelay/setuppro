<?php
// Read-only installation check; this entry point is never available over HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/initialize.php';
require dirname(__DIR__) . '/classes/DBConnection.php';

$database = new DBConnection();
$identity = $database->conn->query('SELECT DATABASE() AS db, CURRENT_USER() AS db_user')->fetch_assoc();
if ($identity['db'] !== 'sallesano_setuppro' || strpos($identity['db_user'], 'setuppro_app@') !== 0) {
    fwrite(STDERR, "Unexpected database identity.\n");
    exit(1);
}
foreach (['users', 'system_info', 'product_list', 'customer_list', 'order_list'] as $table) {
    if (!$database->conn->query('SELECT 1 FROM `' . $table . '` LIMIT 1')) {
        fwrite(STDERR, "Required application table missing.\n");
        exit(1);
    }
}
$admin = $database->conn->query("SELECT password FROM users WHERE username = 'setuppro_admin' AND type = 1 LIMIT 1")->fetch_assoc();
if (!$admin || password_get_info($admin['password'])['algoName'] !== 'bcrypt') {
    fwrite(STDERR, "SetupPro administrator missing or password hash invalid.\n");
    exit(1);
}
fwrite(STDOUT, "SetupPro installation OK: dedicated database, restricted application user, required tables and bcrypt administrator verified.\n");
