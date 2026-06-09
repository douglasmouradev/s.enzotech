<?php
/**
 * Detalhes do produto
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id');
$stmt->execute(['id' => $id]);
$produto = $stmt->fetch();

if (!$produto) {
    setFlash('erro', 'Produto não encontrado.');
    header('Location: ' . baseUrl('pages/produtos/listar.php'));
    exit;
}

$pageTitle = $produto['nome'];
$activeMenu = 'produtos';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($produto['nome']) ?></h1>
        <p class="page-subtitle">
            <span class="badge <?= badgeStatusProduto($produto['status']) ?>"><?= labelStatusProduto($produto['status']) ?></span>
        </p>
    </div>
    <div class="form-actions" style="margin:0;">
        <?php if (temPermissao('vendedor')): ?>
        <a href="<?= e(baseUrl('pages/produtos/editar.php?id=' . $id)) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <form method="post" action="<?= e(baseUrl('pages/produtos/excluir.php')) ?>" style="display:inline;">
            <?= csrfField() ?>
            <input type="hidden" name="produto_id" value="<?= $id ?>">
            <input type="hidden" name="retorno" value="detalhes">
            <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Excluir este produto permanentemente?"
                    aria-label="Excluir produto">
                <i class="bi bi-trash"></i> Excluir
            </button>
        </form>
        <?php endif; ?>
        <a href="<?= e(baseUrl('pages/produtos/listar.php')) ?>" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?= renderFlash() ?>

<div class="detail-section">
    <h2><i class="bi bi-box-seam"></i> Informações do Produto</h2>
    <div class="detail-grid">
        <div class="detail-item">
            <label>Nome</label>
            <p><?= e($produto['nome']) ?></p>
        </div>
        <div class="detail-item">
            <label>Marca</label>
            <p><?= e($produto['marca'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>Categoria</label>
            <p><?= e($produto['categoria'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>SKU / Código</label>
            <p><?= e($produto['sku'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>Quantidade</label>
            <p><?= (int) $produto['quantidade'] ?></p>
        </div>
        <div class="detail-item">
            <label>Cadastrado em</label>
            <p><?= formatData($produto['created_at']) ?></p>
        </div>
    </div>
    <?php if ($produto['descricao']): ?>
        <div class="detail-item" style="margin-top:16px;">
            <label>Descrição</label>
            <p><?= nl2br(e($produto['descricao'])) ?></p>
        </div>
    <?php endif; ?>
</div>

<div class="detail-section">
    <h2><i class="bi bi-currency-dollar"></i> Preços</h2>
    <div class="detail-grid">
        <div class="detail-item">
            <label>Preço de Compra</label>
            <p><?= $produto['preco_compra'] !== null ? formatMoeda((float) $produto['preco_compra']) : '—' ?></p>
        </div>
        <div class="detail-item">
            <label>Preço de Venda</label>
            <p class="text-orange" style="font-weight:600;"><?= $produto['preco_venda'] !== null ? formatMoeda((float) $produto['preco_venda']) : '—' ?></p>
        </div>
    </div>
</div>

<?php if ($produto['observacoes']): ?>
<div class="detail-section">
    <h2><i class="bi bi-chat-left-text"></i> Observações</h2>
    <p><?= nl2br(e($produto['observacoes'])) ?></p>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
