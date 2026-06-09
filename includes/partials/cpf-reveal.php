<?php
/**
 * CPF mascarado com revelação auditada (sem expor no HTML)
 * @var int $compradorId
 * @var string $cpf
 * @var string $targetId
 */
declare(strict_types=1);

if (!temPermissao('vendedor')) {
    echo e(mascararCpf($cpf));
    return;
}
?>
<span class="cpf-reveal-wrap">
    <span id="<?= e($targetId) ?>"><?= e(mascararCpf($cpf)) ?></span>
    <button type="button" class="btn btn-ghost btn-sm"
            data-reveal-cpf="<?= e($targetId) ?>"
            data-comprador-id="<?= (int) $compradorId ?>"
            aria-label="Revelar CPF completo">Revelar</button>
</span>
