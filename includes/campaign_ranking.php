<?php
/** Paid quantities only; totals stay isolated by campaign and customer ID. */
function setuppro_campaign_ranking($connection, array $product, array $timer, $now = null)
{
    if (empty($product['enable_ranking'])) {
        return [];
    }
    $now = $now instanceof DateTimeImmutable ? $now : new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
    $conditions = ['o.product_id = ' . (int) $product['id'], 'o.status = 2'];
    if (!empty($timer['enabled']) && !empty($timer['configured'])) {
        $conditions = array_merge($conditions, ranking_timer_sql_conditions('o', $timer, $connection));
    } elseif (!empty($product['enable_ranking_definido']) && !empty($product['ranking_ini']) && !empty($product['ranking_fim'])) {
        $dateSql = payment_ranking_datetime_sql('o');
        $conditions[] = $dateSql . " >= '" . $connection->real_escape_string($product['ranking_ini']) . "'";
        $conditions[] = $dateSql . " <= '" . $connection->real_escape_string($product['ranking_fim']) . "'";
    } elseif ((int) ($product['ranking_type'] ?? 1) === 2) {
        $dateSql = payment_ranking_datetime_sql('o');
        $conditions[] = $dateSql . " >= '" . $now->format('Y-m-d 00:00:00') . "'";
        $conditions[] = $dateSql . " < '" . $now->modify('+1 day')->format('Y-m-d 00:00:00') . "'";
    }
    $limit = max(1, min(100, (int) ($product['ranking_qty'] ?? 3) ?: 3));
    $result = $connection->query('SELECT c.id, c.firstname, c.lastname, SUM(o.quantity) AS total_quantity FROM order_list o INNER JOIN customer_list c ON c.id = o.customer_id WHERE ' . implode(' AND ', $conditions) . ' GROUP BY c.id, c.firstname, c.lastname ORDER BY total_quantity DESC, c.firstname ASC, c.lastname ASC, c.id ASC LIMIT ' . $limit);
    if (!$result) {
        throw new RuntimeException('Ranking query failed.');
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}
