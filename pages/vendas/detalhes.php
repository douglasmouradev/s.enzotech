<?php
/**
 * Detalhes completos da venda
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT v.*, c.marca, c.modelo, c.imei, c.imei2, c.cor, c.capacidade, c.condicao AS celular_condicao,
           comp.id AS comprador_id, comp.nome_completo, comp.cpf, comp.rg, comp.telefone, comp.telefone2,
           comp.email, comp.endereco, comp.cidade, comp.estado, comp.cep
    FROM vendas v
    INNER JOIN celulares c ON c.id = v.celular_id
    INNER JOIN compradores comp ON comp.id = v.comprador_id
    WHERE v.id = :id
");
$stmt->execute(['id' => $id]);
$venda = $stmt->fetch();

if (!$venda) {
    setFlash('erro', 'Venda não encontrada.');
    header('Location: ' . baseUrl('pages/vendas/listar.php'));
    exit;
}

$stmtDocs = $pdo->prepare('SELECT * FROM documentos WHERE venda_id = :id ORDER BY created_at');
$stmtDocs->execute(['id' => $id]);
$documentos = $stmtDocs->fetchAll();

$pageTitle = 'Venda #' . $id;
$activeMenu = 'vendas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header no-print">
    <div>
        <h1 class="page-title">Venda #<?= $id ?></h1>
        <p class="page-subtitle"><?= formatData($venda['data_venda']) ?> — <?= e($venda['marca'] . ' ' . $venda['modelo']) ?></p>
    </div>
    <div class="form-actions" style="margin:0;">
        <a href="<?= e(baseUrl('pages/vendas/recibo.php?id=' . $id)) ?>" class="btn btn-ghost" target="_blank">
            <i class="bi bi-file-text"></i> Recibo
        </a>
        <button type="button" class="btn btn-ghost" id="btn-print">
            <i class="bi bi-printer"></i> Imprimir
        </button>
        <?php if (($venda['status_venda'] ?? 'ativa') === 'ativa' && temPermissao('vendedor')): ?>
        <button type="button" class="btn btn-danger btn-sm" id="btn-cancelar-venda" data-venda-id="<?= $id ?>">
            <i class="bi bi-x-circle"></i> Cancelar
        </button>
        <?php endif; ?>
        <a href="<?= e(baseUrl('pages/vendas/listar.php')) ?>" class="btn btn-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?= renderFlash() ?>

<?php if (($venda['status_venda'] ?? 'ativa') === 'cancelada'): ?>
    <div class="alert alert-error">Venda cancelada em <?= formatData($venda['cancelada_em'] ?? '') ?> — <?= e($venda['motivo_cancelamento'] ?? '') ?></div>
<?php endif; ?>

<div class="detail-section">
    <h2><i class="bi bi-cash-stack"></i> Dados Financeiros</h2>
    <div class="detail-grid">
        <div class="detail-item">
            <label>Data Compra (lojista)</label>
            <p><?= formatData($venda['data_compra']) ?></p>
        </div>
        <div class="detail-item">
            <label>Valor de Compra</label>
            <p><?= formatMoeda($venda['valor_compra']) ?></p>
        </div>
        <div class="detail-item">
            <label>Data da Venda</label>
            <p><?= formatData($venda['data_venda']) ?></p>
        </div>
        <div class="detail-item">
            <label>Valor de Venda</label>
            <p><?= formatMoeda($venda['valor_venda']) ?></p>
        </div>
        <div class="detail-item">
            <label>Lucro</label>
            <p><strong><?= formatMoeda($venda['lucro']) ?></strong></p>
        </div>
        <div class="detail-item">
            <label>Margem</label>
            <p>
                <span class="badge <?= badgeMargem((float) $venda['margem_pct']) ?>">
                    <?= number_format((float) $venda['margem_pct'], 1, ',', '.') ?>%
                </span>
            </p>
        </div>
        <div class="detail-item">
            <label>Forma de Pagamento</label>
            <p><?= labelFormaPagamento($venda['forma_pagamento']) ?><?= $venda['parcelas'] ? ' (' . $venda['parcelas'] . 'x)' : '' ?></p>
        </div>
        <div class="detail-item">
            <label>Registrado em</label>
            <p><?= formatData($venda['created_at']) ?></p>
        </div>
        <?php if (!empty($venda['garantia_ate'])): ?>
        <div class="detail-item">
            <label>Garantia</label>
            <p><?= (int) ($venda['garantia_dias'] ?? 90) ?> dias — até <?= formatData($venda['garantia_ate']) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($venda['observacoes']): ?>
        <div class="detail-item" style="margin-top:16px;">
            <label>Observações</label>
            <p><?= nl2br(e($venda['observacoes'])) ?></p>
        </div>
    <?php endif; ?>
</div>

<div class="detail-section">
    <h2><i class="bi bi-phone"></i> Celular</h2>
    <div class="detail-grid">
        <div class="detail-item">
            <label>Marca / Modelo</label>
            <p><?= e($venda['marca'] . ' ' . $venda['modelo']) ?></p>
        </div>
        <div class="detail-item">
            <label>IMEI</label>
            <p><?= e($venda['imei']) ?></p>
        </div>
        <div class="detail-item">
            <label>IMEI 2</label>
            <p><?= e($venda['imei2'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>Cor / Capacidade</label>
            <p><?= e(($venda['cor'] ?: '—') . ' / ' . ($venda['capacidade'] ?: '—')) ?></p>
        </div>
        <div class="detail-item">
            <label>Condição</label>
            <p><?= labelCondicao($venda['celular_condicao']) ?></p>
        </div>
    </div>
    <a href="<?= e(baseUrl('pages/celulares/detalhes.php?id=' . $venda['celular_id'])) ?>" class="text-link no-print" style="margin-top:12px;display:inline-block;">
        Ver detalhes do celular <i class="bi bi-arrow-right"></i>
    </a>
</div>

<div class="detail-section">
    <h2><i class="bi bi-person"></i> Comprador</h2>
    <div class="detail-grid">
        <div class="detail-item">
            <label>Nome</label>
            <p><?= e($venda['nome_completo']) ?></p>
        </div>
        <div class="detail-item">
            <label>CPF</label>
            <p><?php
                $compradorId = (int) $venda['comprador_id'];
                $cpf = (string) $venda['cpf'];
                $targetId = 'cpf-display';
                require __DIR__ . '/../../includes/partials/cpf-reveal.php';
            ?></p>
        </div>
        <div class="detail-item">
            <label>RG</label>
            <p><?= e($venda['rg'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>Telefone</label>
            <p><?= e($venda['telefone']) ?><?= $venda['telefone2'] ? ' / ' . e($venda['telefone2']) : '' ?></p>
        </div>
        <div class="detail-item">
            <label>E-mail</label>
            <p><?= e($venda['email'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>Endereço</label>
            <p>
                <?php if ($venda['endereco']): ?>
                    <?= e($venda['endereco']) ?><br>
                    <?= e($venda['cidade'] ?: '') ?><?= $venda['estado'] ? ' - ' . e($venda['estado']) : '' ?>
                    <?= $venda['cep'] ? ' — CEP ' . e($venda['cep']) : '' ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </p>
        </div>
    </div>
    <a href="<?= e(baseUrl('pages/compradores/detalhes.php?id=' . $venda['comprador_id'])) ?>" class="text-link no-print" style="margin-top:12px;display:inline-block;">
        Ver perfil completo do comprador <i class="bi bi-arrow-right"></i>
    </a>
</div>

<div class="detail-section">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h2 style="margin:0;"><i class="bi bi-folder"></i> Documentos</h2>
        <?php if (temPermissao('vendedor')): ?>
        <a href="<?= e(baseUrl('pages/documentos/upload.php?venda_id=' . $id)) ?>" class="btn btn-primary btn-sm no-print">
            <i class="bi bi-upload"></i> Adicionar
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($documentos)): ?>
        <div class="empty-state" style="padding:24px;">
            <i class="bi bi-folder"></i>
            <p>Nenhum documento anexado.</p>
        </div>
    <?php else: ?>
        <div class="doc-list">
            <?php foreach ($documentos as $doc): ?>
                <div class="doc-item">
                    <i class="bi <?= iconeDocumento($doc['tipo_arquivo']) ?> doc-icon"></i>
                    <div class="doc-info">
                        <div class="doc-name"><?= e($doc['nome_original']) ?></div>
                        <?php if ($doc['descricao']): ?>
                            <div class="doc-desc"><?= e($doc['descricao']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="doc-actions">
                        <a href="<?= e(baseUrl('pages/documentos/download.php?id=' . $doc['id'] . '&mode=view')) ?>"
                           class="btn btn-ghost btn-sm" target="_blank" title="Visualizar">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?= e(baseUrl('pages/documentos/download.php?id=' . $doc['id'] . '&mode=download')) ?>"
                           class="btn btn-ghost btn-sm" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                        <?php if (temPermissao('vendedor')): ?>
                        <form method="post" action="<?= e(baseUrl('pages/documentos/upload.php')) ?>" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="documento_id" value="<?= $doc['id'] ?>">
                            <input type="hidden" name="venda_id" value="<?= $id ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Excluir este documento permanentemente?" aria-label="Excluir documento">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<form method="post" action="<?= e(baseUrl('pages/vendas/cancelar.php')) ?>" id="form-cancelar-venda" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="venda_id" value="<?= $id ?>">
    <input type="hidden" name="motivo" id="cancelar-motivo">
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
