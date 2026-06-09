<?php
/**
 * Formulário compartilhado de produto
 * @var array<string, mixed> $produto
 * @var string $modo 'criar'|'editar'
 * @var string $cancelUrl
 * @var string $submitLabel
 */
declare(strict_types=1);

$modo = $modo ?? 'criar';
$submitLabel = $submitLabel ?? ($modo === 'editar' ? 'Salvar Alterações' : 'Salvar');

$precoCompra = $produto['preco_compra'] ?? '';
if ($precoCompra !== '' && is_numeric($precoCompra)) {
    $precoCompra = number_format((float) $precoCompra, 2, ',', '.');
}
$precoVenda = $produto['preco_venda'] ?? '';
if ($precoVenda !== '' && is_numeric($precoVenda)) {
    $precoVenda = number_format((float) $precoVenda, 2, ',', '.');
}
?>
<form method="post" class="form-card">
    <?= csrfField() ?>

    <h2 class="form-section-title">Produto</h2>
    <div class="form-grid">
        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" class="form-control" required maxlength="150"
                   placeholder="Ex: Capa iPhone 14" value="<?= e((string) ($produto['nome'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="marca">Marca</label>
            <input type="text" id="marca" name="marca" class="form-control" maxlength="100"
                   value="<?= e((string) ($produto['marca'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="categoria">Categoria</label>
            <input type="text" id="categoria" name="categoria" class="form-control" maxlength="80"
                   placeholder="Ex: Capa, Película, Carregador" value="<?= e((string) ($produto['categoria'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="sku">SKU / Código</label>
            <input type="text" id="sku" name="sku" class="form-control" maxlength="50"
                   value="<?= e((string) ($produto['sku'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="quantidade">Quantidade em estoque</label>
            <input type="number" id="quantidade" name="quantidade" class="form-control" min="0" step="1"
                   value="<?= e((string) ($produto['quantidade'] ?? '0')) ?>">
        </div>
        <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status" class="form-control" required>
                <option value="ativo" <?= ($produto['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= ($produto['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" class="form-control" rows="2"><?= e((string) ($produto['descricao'] ?? '')) ?></textarea>
    </div>

    <h2 class="form-section-title">Preços</h2>
    <div class="form-grid">
        <div class="form-group">
            <label for="preco_compra">Preço de Compra</label>
            <input type="text" id="preco_compra" name="preco_compra" class="form-control" data-mask="moeda"
                   value="<?= e((string) $precoCompra) ?>">
        </div>
        <div class="form-group">
            <label for="preco_venda">Preço de Venda</label>
            <input type="text" id="preco_venda" name="preco_venda" class="form-control" data-mask="moeda"
                   value="<?= e((string) $precoVenda) ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" name="observacoes" class="form-control"><?= e((string) ($produto['observacoes'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= e($submitLabel) ?></button>
        <a href="<?= e($cancelUrl) ?>" class="btn btn-ghost">Cancelar</a>
    </div>
</form>
