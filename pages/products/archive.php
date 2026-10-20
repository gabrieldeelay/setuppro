<?php $publicWinner = $archivedCampaign['winner']; ?>
<div class="container app-main mirror-archive">
    <span class="mirror-badge">Concluído</span>
    <img class="mirror-campaign-image" src="<?= setuppro_html(validate_image($image_path)) ?>" alt="<?= setuppro_html($name) ?>">
    <h1><?= setuppro_html($name) ?></h1>
    <p class="mirror-subtitle"><?= setuppro_html($subtitle) ?></p>
    <?php if (trim((string) $description) !== ''): ?>
        <details><summary><i class="bi bi-arrow-up-square-fill"></i> Descrição/Regulamento</summary><div><?= blockHTML($description) ?></div></details>
    <?php endif; ?>
    <div class="mirror-detail-winner">
        <img src="<?= setuppro_html(validate_image($publicWinner['image'])) ?>" alt="<?= setuppro_html($publicWinner['name']) ?>">
        <div><h2>1º - <?= setuppro_html($publicWinner['name']) ?> <i class="bi bi-check-circle"></i></h2><p>Ganhador(a) com a cota <?= setuppro_html($publicWinner['number']) ?></p></div>
    </div>
</div>
