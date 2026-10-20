<?php
require_once __DIR__ . '/../includes/setuppro_archive.php';
$campaigns = $conn->query("SELECT * FROM product_list WHERE delete_flag = 0 AND private_draw = 0 ORDER BY CASE WHEN status = 1 THEN 0 WHEN status = 2 THEN 1 ELSE 2 END, date_created DESC, id DESC LIMIT 10");
?>
<div class="container app-main mirror-home">
    <div class="mirror-title"><h1>⚡ Sorteios</h1><span>Escolha sua sorte</span></div>
    <?php while ($campaign = $campaigns->fetch_assoc()): ?>
        <a class="mirror-campaign<?= (int) $campaign['status'] === 1 ? ' mirror-campaign-active' : ((int) $campaign['status'] === 3 ? ' mirror-campaign-closed' : '') ?>" href="<?= BASE_URL ?>campanha/<?= rawurlencode($campaign['slug']) ?>">
            <img src="<?= setuppro_html(validate_image($campaign['image_path'])) ?>" alt="<?= setuppro_html($campaign['name']) ?>">
            <div class="mirror-campaign-copy">
                <h2><?= setuppro_html($campaign['name']) ?></h2>
                <p><?= setuppro_html($campaign['subtitle']) ?></p>
                <span class="mirror-badge"><?= (int) $campaign['status'] === 3 ? 'Concluído' : ((int) $campaign['status'] === 2 ? 'Em breve' : 'Ativa • Adquira já!') ?></span>
                <?php if ($campaign['date_of_draw']): ?><time><i class="bi bi-calendar-check"></i> <?= date('d/m', strtotime($campaign['date_of_draw'])) ?></time><?php endif; ?>
            </div>
        </a>
    <?php endwhile; ?>
    <a class="mirror-help" href="<?= BASE_URL ?>contato"><span>🤷</span><div><h3>Dúvidas</h3><p>Fale conosco</p></div></a>
    <?php $completed = $conn->query("SELECT * FROM product_list WHERE delete_flag = 0 AND private_draw = 0 AND draw_number <> '' ORDER BY date_of_draw DESC LIMIT 5"); ?>
    <?php if ($completed->num_rows): ?>
    <section class="mirror-winners">
        <div class="mirror-title"><h1>🎉 Premiados</h1><span>do sorteios</span></div>
        <?php while ($product = $completed->fetch_assoc()): ?>
            <?php foreach (setuppro_public_winners($conn, $product) as $winner) { setuppro_render_winner($winner); } ?>
        <?php endwhile; ?>
    </section>
    <?php endif; ?>
    <section class="mirror-faq">
        <div class="mirror-title"><h1>🙋🏼 Perguntas frequentes</h1></div>
        <?php foreach (setuppro_archive_data()['faq'] ?? [] as $faq): ?>
            <details><summary><?= setuppro_html($faq['question']) ?></summary><p><?= setuppro_html($faq['answer']) ?></p></details>
        <?php endforeach; ?>
    </section>
</div>
