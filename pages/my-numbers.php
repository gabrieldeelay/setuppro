<?php
$enable_cpf = (int) $_settings->info('enable_cpf') === 1;
$hide_numbers = (int) $_settings->info('enable_hide_numbers') === 1;
$campaign_id = filter_input(INPUT_GET, 'campanha', FILTER_VALIDATE_INT);
$campaign_id = $campaign_id && $campaign_id > 0 ? $campaign_id : null;
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$campaign_name = null;
if ($campaign_id !== null) {
    $campaign_query = $conn->prepare('SELECT name FROM product_list WHERE id = ? LIMIT 1');
    $campaign_query->bind_param('i', $campaign_id);
    $campaign_query->execute();
    $campaign = $campaign_query->get_result()->fetch_assoc();
    $campaign_query->close();
    if ($campaign) {
        $campaign_name = $campaign['name'];
    } else {
        $campaign_id = null;
    }
}

$identifier = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $enable_cpf ? (string) ($_POST['cpf'] ?? '') : (string) ($_POST['phone'] ?? '');
} elseif ($enable_cpf && isset($_SESSION['cpf'])) {
    $identifier = (string) $_SESSION['cpf'];
} elseif (!$enable_cpf && isset($_SESSION['phone'])) {
    $identifier = (string) $_SESSION['phone'];
}
unset($_SESSION['cpf'], $_SESSION['phone']);

$digits = preg_replace('/\D+/', '', $identifier);
$searched = $identifier !== '';
$orders = [];
$error = '';
if ($searched) {
    if (strlen($digits) !== 11 && ($enable_cpf || strlen($digits) !== 10)) {
        $error = $enable_cpf ? 'Informe um CPF com 11 dígitos.' : 'Informe um telefone com DDD.';
    } else {
        $field = $enable_cpf
            ? "REPLACE(REPLACE(c.cpf, '.', ''), '-', '')"
            : "REPLACE(REPLACE(REPLACE(REPLACE(c.phone, '(', ''), ')', ''), '-', ''), ' ', '')";
        $sql = "SELECT o.id, o.product_name, o.date_created, o.status, o.order_numbers,
                       o.order_token, o.quantity, p.image_path, p.qty_numbers, p.type_of_draw
                FROM order_list o
                INNER JOIN customer_list c ON c.id = o.customer_id
                INNER JOIN product_list p ON p.id = o.product_id
                WHERE $field = ?";
        if ($campaign_id !== null) {
            $sql .= ' AND o.product_id = ?';
        }
        $sql .= ' ORDER BY o.date_created DESC, o.id DESC LIMIT 100';
        $query = $conn->prepare($sql);
        if ($campaign_id !== null) {
            $query->bind_param('si', $digits, $campaign_id);
        } else {
            $query->bind_param('s', $digits);
        }
        $query->execute();
        $result = $query->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $query->close();
    }
}
?>
<div class="container app-main py-3">
    <div class="app-title mb-3">
        <h1>🛒 Meus títulos</h1>
        <?php if ($campaign_name !== null): ?>
            <p class="mb-0">Campanha: <?= $escape($campaign_name) ?></p>
        <?php endif; ?>
    </div>

    <form method="post" action="/meus-numeros<?= $campaign_id !== null ? '?campanha=' . $campaign_id : '' ?>" class="card app-card mb-3">
        <div class="card-body">
            <label class="form-label" for="buscar-titulos">
                <?= $enable_cpf ? 'Informe o CPF usado na compra' : 'Informe o telefone com DDD usado na compra' ?>
            </label>
            <div class="input-group">
                <input id="buscar-titulos" name="<?= $enable_cpf ? 'cpf' : 'phone' ?>" type="text"
                       class="form-control" inputmode="numeric" autocomplete="off" required
                       maxlength="<?= $enable_cpf ? '14' : '15' ?>"
                       placeholder="<?= $enable_cpf ? '000.000.000-00' : '(00) 00000-0000' ?>">
                <button type="submit" class="btn btn-primary">Buscar títulos</button>
            </div>
        </div>
    </form>

    <?php if ($error !== ''): ?>
        <div class="alert alert-warning"><?= $escape($error) ?></div>
    <?php elseif ($searched && !$orders): ?>
        <div class="alert alert-warning">Nenhuma compra encontrada<?= $campaign_name !== null ? ' nesta campanha' : '' ?> para os dados informados.</div>
    <?php elseif (!$searched): ?>
        <div class="alert alert-info">Busque pelo mesmo <?= $enable_cpf ? 'CPF' : 'telefone' ?> informado na compra para ver seus números.</div>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <?php
        $status = (int) $order['status'];
        $status_label = $status === 2 ? 'Pago' : ($status === 1 ? 'Aguardando pagamento' : 'Cancelado');
        $status_class = $status === 2 ? 'success' : ($status === 1 ? 'warning' : 'danger');
        $numbers = trim((string) $order['order_numbers']);
        $show_numbers = $numbers !== '' && !($hide_numbers && $status !== 2 && (int) $order['type_of_draw'] === 1);
        ?>
        <article class="card app-card mb-3 border-<?= $status_class ?>">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <img src="<?= $escape(validate_image($order['image_path'])) ?>" alt=""
                         style="width:60px;height:60px;object-fit:contain;border-radius:8px;background:#11141c">
                    <div>
                        <strong><?= $escape($order['product_name']) ?></strong><br>
                        <small><?= $escape(date('d/m/Y H:i', strtotime($order['date_created']))) ?> · <?= $escape($status_label) ?></small>
                    </div>
                </div>
                <div>
                    <strong>Números da compra:</strong>
                    <?php if ($show_numbers): ?>
                        <div class="mt-2" style="max-height:320px;overflow:auto">
                            <?= drope_format_luck_numbers_dashboard(
                                $numbers,
                                $order['qty_numbers'],
                                false,
                                true,
                                (int) $order['type_of_draw']
                            ) ?>
                        </div>
                    <?php else: ?>
                        <p class="mb-0">Os números serão exibidos após a confirmação do pagamento.</p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($order['order_token'])): ?>
                    <a class="btn btn-outline-primary btn-sm mt-3" href="/compra/<?= rawurlencode($order['order_token']) ?>">Ver compra</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
