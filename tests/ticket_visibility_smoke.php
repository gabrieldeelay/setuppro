<?php
// Isolated rendering regression: no database, network, or production writes.
error_reporting(E_ALL & ~E_DEPRECATED);
class FixtureResult {
    public $num_rows;
    private $rows;
    function __construct($rows) { $this->rows = $rows; $this->num_rows = count($rows); }
    function fetch_assoc() { return array_shift($this->rows); }
    function fetch_array() { return $this->fetch_assoc(); }
}
class FixtureConnection {
    public $order;
    function query($sql) { return new FixtureResult(strpos($sql, 'FROM `customer_list`') !== false ? [] : [$this->order]); }
    function prepare($sql) { return $this; }
    function bind_param($types, &...$args) {}
    function execute() {}
    function get_result() { return new FixtureResult([$this->order]); }
    function close() {}
}
class FixtureSettings {
    public $hide;
    public $login = 2;
    function info($key) { return $key === 'enable_hide_numbers' ? $this->hide : 0; }
    function userdata($key) { return $key === 'login_type' ? $this->login : 123; }
}
function validate_image($v) { return $v; }
function drope_format_luck_numbers_dashboard($n, ...$args) { return $n; }
function leowp_format_luck_numbers($n, ...$args) { echo implode(',', $n); }
function check_visibility($html, $paid, $context) {
    if ((strpos($html, '1160820') !== false) !== $paid) {
        throw new RuntimeException('Ticket visibility failed: ' . $context);
    }
}
$root = dirname(__DIR__);
$source = file_get_contents($root . '/pages/orders/view_order.php');
$start = strpos($source, '<div class="item  align-items-baseline container">');
$end = strpos($source, '<div class="item d-flex align-items-baseline mb-1 pb-1 border-bottom-rgba border-1">', $start);
$detail = substr($source, $start, $end - $start);
$initStart = strpos($source, '$my_numbers = 0;');
$initEnd = strpos($source, '// Inicialize', $initStart);
$init = substr($source, $initStart, $initEnd - $initStart);
$_settings = new FixtureSettings;
$conn = new FixtureConnection;
$count = 0;
foreach ([1, 2, 3] as $state) {
    foreach ([1, 2, 3, 4] as $draw) {
        foreach ([0, 1] as $hide) {
            $_settings->hide = $hide;
            $conn->order = [
                'id'=>123, 'product_name'=>'Campaign', 'date_created'=>'2026-11-27 08:25:00',
                'status'=>$state, 'order_numbers'=>'1160820,1196619,', 'order_token'=>'fixture-token',
                'quantity'=>2, 'qty_numbers'=>2000000, 'image_path'=>'fixture.png', 'type_of_draw'=>$draw
            ];
            ob_start();
            include $root . '/pages/orders/index.php';
            check_visibility(ob_get_clean(), $state === 2, "history $state/$draw/$hide");
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = ['phone'=>'11999999999'];
            $_SESSION = [];
            ob_start();
            include $root . '/pages/my-numbers.php';
            check_visibility(ob_get_clean(), $state === 2, "search $state/$draw/$hide");
            $orderitem = $conn->order;
            eval($init);
            $sliced_numbers = $my_numbers;
            $status = $state;
            $type_of_draw = $draw;
            $row = $conn->order;
            $current_page = 1;
            $limit = 100;
            ob_start();
            eval('?>' . $detail);
            check_visibility(ob_get_clean(), $state === 2, "detail $state/$draw/$hide");
            $count += 3;
        }
    }
}
echo "OK: $count rendered visibility checks (pending, paid, cancelled; all draw types; both legacy settings).\n";
function jnsalles_format_ticket_list($n, $q) { return array_filter(explode(',', $n), 'strlen'); }
foreach (['class/Main.php', 'classes/Master.php'] as $implementation) {
    $source = file_get_contents($root . '/' . $implementation);
    $start = strpos($source, 'public function send_order_whatsapp(');
    $end = strpos($source, 'if (!empty($message))', $start);
    $prefix = substr($source, $start, $end - $start);
    $class = 'NotificationFixture' . $count;
    eval('class ' . $class . ' { public $conn; public $settings; ' . $prefix . 'return $cotas; }}');
    $object = new $class;
    $object->conn = $conn;
    $object->settings = $_settings;
    foreach ([1, 2, 3] as $state) {
        $conn->order['status'] = $state;
        $text = $object->send_order_whatsapp('', '', '', '1160820,', 0, '', '', 'fixture-token');
        check_visibility($text, $state === 2, 'WhatsApp ' . $implementation . '/' . $state);
        $count++;
    }
}
$source = file_get_contents($root . '/config.php');
$start = strpos($source, "$" . "order_numbers = (int) $" . "row['status'] === 2");
$end = strpos($source, "$" . "order_total =", $start);
foreach ([1, 2, 3] as $state) {
    $row = $conn->order;
    $row['status'] = $state;
    eval(substr($source, $start, $end - $start));
    check_visibility($order_numbers, $state === 2, 'email/' . $state);
    $count++;
}
$source = file_get_contents($root . '/class/Main.php');
$start = strpos($source, 'public function view_numbers()');
$end = strpos($source, "\n}", $start);
eval('class EndpointFixture { public $conn; public $settings; ' . substr($source, $start, $end - $start) . '}');
$object = new EndpointFixture;
$object->conn = $conn;
$object->settings = $_settings;
$_POST['id'] = 123;
foreach ([0, 2, 1] as $login) {
    $_settings->login = $login;
    $response = $object->view_numbers();
    check_visibility($response, $login === 1, 'direct endpoint/' . $login);
    $count++;
}
echo "OK: $count checks including notification templates and direct endpoint access.\n";
