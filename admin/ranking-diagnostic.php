<?php
require_once __DIR__ . '/../settings.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (int) $_settings->userdata('login_type') !== 1 || (int) $_settings->userdata('type') !== 1 || DB_NAME !== 'sallesano_setuppro') {
    http_response_code(403);
    exit(json_encode(['status'=>'denied']));
}
define('SETUPPRO_AUTHORIZED_RANKING_TEST',true);
require_once __DIR__ . '/../tests/campaign_ranking_isolation.php';
try {
    $checks=setuppro_test_campaign_ranking($conn);
    echo json_encode(['status'=>'success','checks'=>$checks,'persistent_records_written'=>0]);
} catch (Throwable $error) {
    error_log('[setuppro-ranking-test] '.$error->getMessage());
    http_response_code(500);
    echo json_encode(['status'=>'failed','message'=>$error->getMessage()]);
}
