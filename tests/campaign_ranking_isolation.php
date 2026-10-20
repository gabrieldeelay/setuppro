<?php
// Called by the authenticated diagnostic, or CLI with an existing $conn.
if (PHP_SAPI !== 'cli' && !defined('SETUPPRO_AUTHORIZED_RANKING_TEST')) {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/campaign_ranking.php';
function setuppro_test_campaign_ranking($connection)
{
    $checks = [];
    $check = static function ($name, $actual, $expected) use (&$checks) {
        if ($actual !== $expected) {
            throw new RuntimeException('Ranking check failed: ' . $name);
        }
        $checks[] = $name;
    };
    // Connection-scoped temporary tables shadow permanent tables only here.
    // No customer, order, ticket or financial record is written to live tables.
    $connection->query('CREATE TEMPORARY TABLE customer_list (id INT PRIMARY KEY, firstname VARCHAR(100), lastname VARCHAR(100))');
    try {
        $connection->query('CREATE TEMPORARY TABLE order_list (id INT PRIMARY KEY, customer_id INT, product_id INT, quantity INT, status INT, payment_method VARCHAR(100), date_created DATETIME, date_updated DATETIME)');
        $connection->query("INSERT INTO customer_list VALUES (1,'Ana','Teste'),(2,'Bruno','Teste'),(3,'Carla','Teste'),(4,'Ana','Outra')");
        $connection->query("INSERT INTO order_list VALUES
            (1,1,5,20,2,'Manual','2026-10-20 10:00:00','2026-10-20 10:00:00'),
            (2,1,5,10,2,'Manual','2026-10-20 11:00:00','2026-10-20 11:00:00'),
            (3,2,5,25,2,'Manual','2026-10-20 10:00:00','2026-10-20 10:00:00'),
            (4,3,5,999,1,'Manual','2026-10-20 10:00:00','2026-10-20 10:00:00'),
            (5,3,5,888,3,'Manual','2026-10-20 10:00:00','2026-10-20 10:00:00'),
            (6,2,5,100,2,'Manual','2026-10-19 10:00:00','2026-10-19 10:00:00'),
            (7,1,99,999,2,'Manual','2026-10-20 10:00:00','2026-10-20 10:00:00'),
            (8,3,5,40,2,'VenoPag','2026-10-20 09:00:00','2026-10-21 00:00:00'),
            (9,4,5,5,2,'Manual','2026-10-20 11:30:00','2026-10-20 11:30:00'),
            (10,1,5,7,2,'Manual','2026-10-20 12:00:00','2026-10-20 12:00:00')");
        $now = new DateTimeImmutable('2026-10-20 12:30:00', new DateTimeZone('America/Sao_Paulo'));
        $product = ['id'=>5,'enable_ranking'=>1,'ranking_type'=>2,'ranking_qty'=>10];
        $sum = static function ($rows) { $totals=[]; foreach ($rows as $r) {$totals[(int)$r['id']] = (int)$r['total_quantity'];} return $totals; };
        $daily = setuppro_campaign_ranking($connection,$product,[],$now);
        $check('daily_paid_quantities_and_order', $sum($daily), [3=>40,1=>37,2=>25,4=>5]);
        $check('pending_and_cancelled_excluded',count($daily),4);
        $check('duplicate_first_names_kept_separate',count(array_filter($daily,static function($r){return $r['firstname']==='Ana';})),2);
        $product['ranking_type']=1;
        $check('total_mode_includes_previous_days',$sum(setuppro_campaign_ranking($connection,$product,[],$now)),[2=>125,3=>40,1=>37,4=>5]);
        $product['ranking_qty']=2;
        $check('configured_buyer_limit',count(setuppro_campaign_ranking($connection,$product,[],$now)),2);
        $product['enable_ranking']=0;
        $check('disabled_ranking',setuppro_campaign_ranking($connection,$product,[],$now),[]);
        $product['enable_ranking']=1;$product['ranking_qty']=10;
        $timer=['enabled'=>true,'configured'=>true,'window_start'=>'2026-10-20 11:00:00','window_end'=>'2026-10-20 12:30:00','uses_reset'=>true,'pause_intervals'=>[]];
        $check('reset_excludes_previous_quantities',$sum(setuppro_campaign_ranking($connection,$product,$timer,$now)),[1=>7,4=>5]);
        $timer['pause_intervals']=[['start'=>'2026-10-20 11:45:00','end'=>'2026-10-20 12:15:00']];
        $check('pause_intervals_excluded',$sum(setuppro_campaign_ranking($connection,$product,$timer,$now)),[4=>5]);
        $timer['pause_intervals']=[];$timer['window_end']='2026-10-20 11:45:00';
        $check('paused_or_ended_window_frozen',$sum(setuppro_campaign_ranking($connection,$product,$timer,$now)),[4=>5]);
        $product['enable_ranking_definido']=1;$product['ranking_ini']='2026-10-20 10:00:00';$product['ranking_fim']='2026-10-20 11:59:59';
        $check('custom_campaign_period',$sum(setuppro_campaign_ranking($connection,$product,[],$now)),[1=>30,2=>25,4=>5]);
        return $checks;
    } finally {
        $connection->query('DROP TEMPORARY TABLE IF EXISTS order_list');
        $connection->query('DROP TEMPORARY TABLE IF EXISTS customer_list');
    }
}
