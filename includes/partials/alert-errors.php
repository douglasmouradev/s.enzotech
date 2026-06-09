<?php
/**
 * Bloco de erros de validação
 * @var string[] $erros
 */
declare(strict_types=1);

if (empty($erros)) {
    return;
}
?>
<div class="alert alert-error">
    <?php foreach ($erros as $erro): ?>
        <div><?= e($erro) ?></div>
    <?php endforeach; ?>
</div>
