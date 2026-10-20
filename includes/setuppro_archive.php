<?php

function setuppro_archive_data()
{
    static $data;
    if ($data === null) {
        $data = json_decode((string) file_get_contents(__DIR__ . '/setuppro_archive.json'), true) ?: [];
    }
    return $data;
}

function setuppro_archived_campaign($slug)
{
    foreach (setuppro_archive_data()['campaigns'] ?? [] as $campaign) {
        if ($campaign['slug'] === $slug) {
            return $campaign;
        }
    }
    return null;
}

function setuppro_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setuppro_public_winners($connection, $product)
{
    $numbers = json_decode((string) ($product['draw_number'] ?? ''), true) ?: [];
    $identities = json_decode((string) ($product['draw_winner'] ?? ''), true) ?: [];
    $winners = [];
    foreach ($identities as $index => $identity) {
        if (!is_string($identity) || !isset($numbers[$index])) {
            continue;
        }
        if (strpos($identity, 'archive:') === 0) {
            $archive = setuppro_archived_campaign(substr($identity, 8));
            if (!$archive) {
                continue;
            }
            $winner = $archive['winner'];
        } else {
            $query = $connection->prepare("SELECT CONCAT(firstname, ' ', lastname) AS name, avatar FROM customer_list WHERE phone = ? LIMIT 1");
            $query->bind_param('s', $identity);
            $query->execute();
            $customer = $query->get_result()->fetch_assoc();
            $query->close();
            if (!$customer) {
                continue;
            }
            $winner = ['name' => $customer['name'], 'image' => $customer['avatar'] ?: 'assets/img/avatar.png'];
        }
        $winner['number'] = (string) $numbers[$index];
        $winner['product'] = $product['name'];
        $winner['slug'] = $product['slug'];
        $winner['date'] = $product['date_of_draw'];
        $winners[] = $winner;
    }
    return $winners;
}

function setuppro_render_winner($winner)
{
    ?>
    <a class="mirror-winner" href="<?= BASE_URL ?>campanha/<?= rawurlencode($winner['slug']) ?>">
        <img src="<?= setuppro_html(validate_image($winner['image'])) ?>" alt="<?= setuppro_html($winner['name']) ?>" loading="lazy">
        <div>
            <h3><?= setuppro_html($winner['name']) ?></h3>
            <p><?= setuppro_html($winner['product']) ?></p>
            <p>Número da sorte <strong><?= setuppro_html($winner['number']) ?></strong></p>
            <p>Data da premiação <strong><?= date('d/m/y', strtotime($winner['date'])) ?></strong></p>
        </div>
    </a>
    <?php
}
