<?php
require_once dirname(__DIR__) . '/includes/setuppro_archive.php';
$products = $conn->query("SELECT * FROM product_list WHERE delete_flag = 0 AND private_draw = 0 AND draw_number <> '' ORDER BY date_of_draw DESC");
?>
<div class="container app-main mirror-home">
    <div class="mirror-title"><h1>🎉 Premiados</h1><span>do sorteios</span></div>
    <?php while ($product = $products->fetch_assoc()): ?>
        <?php foreach (setuppro_public_winners($conn, $product) as $winner) { setuppro_render_winner($winner); } ?>
    <?php endwhile; ?>
</div>
