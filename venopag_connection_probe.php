<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$expectedToken = 'c7bd8f63f2e142e2a9bb5849e7f31f74';
$providedToken = (string) ($_GET['token'] ?? '');
if (!hash_equals($expectedToken, $providedToken)) {
    http_response_code(404);
    echo json_encode(['ok' => false]);
    exit;
}

require __DIR__ . '/initialize.php';
require_once __DIR__ . '/includes/payment_core.php';

$result = payment_test_gateway('venopag');
echo json_encode([
    'status' => (string) ($result['status'] ?? 'failed'),
    'message' => (string) ($result['msg'] ?? 'Falha no teste de conexão.'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
