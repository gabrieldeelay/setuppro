<?php
require_once __DIR__ . '/../settings.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (int) $_settings->userdata('login_type') !== 1 || (int) $_settings->userdata('type') !== 1 || DB_NAME !== 'sallesano_setuppro' || DB_USER !== 'setuppro_app') {
    http_response_code(403);exit(json_encode(['status'=>'denied']));
}
$source=json_decode(file_get_contents(__DIR__.'/../includes/setuppro_active_campaign.json'),true);
$slug='setup-gamer-completo-kit-trader-ou-iphone-18-pro-max-escolha-a-cor';
$existing=$conn->prepare('SELECT id FROM product_list WHERE slug = ?');
$existing->bind_param('s',$slug);$existing->execute();$row=$existing->get_result()->fetch_assoc();
if ($row) {exit(json_encode(['status'=>'exists','id'=>(int)$row['id']]));}
$image='uploads/setuppro-active/setup-gamer-kit-trader-iphone.png';
if (!hash_equals($source['image_sha256'],hash_file('sha256',__DIR__.'/../'.$image))) {http_response_code(500);exit(json_encode(['status'=>'image_mismatch']));}
$data=[
    'name'=>$source['name'],'description'=>$source['description'],'subtitle'=>$source['subtitle'],
    'price'=>'0.20','image_path'=>$image,'slug'=>$slug,'status'=>1,'status_display'=>1,
    'type_of_draw'=>1,'qty_numbers'=>1000000,'min_purchase'=>20,'max_purchase'=>4999,
    'ranking_qty'=>3,'enable_ranking'=>1,'ranking_type'=>2,'enable_ranking_show'=>1,
    'featured_draw'=>1,'private_draw'=>0,'paid_numbers'=>0,'pending_numbers'=>0,
    'cotas_premiadas'=>$source['cotas_premiadas'],'cotas_premiadas_premios'=>$source['cotas_premiadas_premios'],
    'cotas_premiadas_descricao'=>'Além do prêmio principal, esta campanha possui cotas premiadas. Consulte os números e os prêmios disponíveis abaixo.',
    'limit_order_remove'=>30,'discount_qty'=>'[]','discount_amount'=>'[]','draw_number'=>'','draw_winner'=>'',
];
for ($i=1;$i<=6;$i++) {$data['qty_select_'.$i]=$source['qty_select_'.$i];}
$columns=implode(',',array_map(static function($key){return '`'.$key.'`';},array_keys($data)));
$stmt=$conn->prepare('INSERT INTO product_list ('.$columns.') VALUES ('.implode(',',array_fill(0,count($data),'?')).')');
$values=array_values($data);$types=str_repeat('s',count($values));
$stmt->bind_param($types,...$values);$stmt->execute();
echo json_encode(['status'=>'success','id'=>(int)$conn->insert_id,'stock'=>1000000,'ranking'=>'daily']);
