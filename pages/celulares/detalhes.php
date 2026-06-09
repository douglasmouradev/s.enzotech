<?php
/**
 * Detalhes do celular e vendas associadas
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM celulares WHERE id = :id');
$stmt->execute(['id' => $id]);
$celular = $stmt->fetch();

if (!$celular) {
    setFlash('erro', 'Celular não encontrado.');
    header('Location: ' . baseUrl('pages/celulares/listar.php'));
    exit;
}

$stmtVendas = $pdo->prepare("
    SELECT v.*, comp.nome_completo AS comprador_nome
    FROM vendas v
    INNER JOIN compradores comp ON comp.id = v.comprador_id
    WHERE v.celular_id = :id
    ORDER BY v.data_venda DESC
");
$stmtVendas->execute(['id' => $id]);
$vendas = $stmtVendas->fetchAll();

$pageTitle = $celular['marca'] . ' ' . $celular['modelo'];
$activeMenu = 'celulares';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($celular['marca'] . ' ' . $celular['modelo']) ?></h1>
        <p class="page-subtitle">
            <span class="badge <?= badgeStatusCelular($celular['status']) ?>"><?= labelStatusCelular($celular['status']) ?></span>
        </p>
    </div>
    <div class="form-actions" style="margin:0;">
        <?php if (temPermissao('vendedor')): ?>
        <a href="<?= e(baseUrl('pages/celulares/editar.php?id=' . $id)) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <?php endif; ?>
        <a href="<?= e(baseUrl('pages/celulares/listar.php')) ?>" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?= renderFlash() ?>

<div class="detail-section">
    <h2><i class="bi bi-phone"></i> Informações do Aparelho</h2>
    <div class="detail-grid">
        <div class="detail-item">
            <label>Marca</label>
            <p><?= e($celular['marca']) ?></p>
        </div>
        <div class="detail-item">
            <label>Modelo</label>
            <p><?= e($celular['modelo']) ?></p>
        </div>
        <div class="detail-item">
            <label>Série</label>
            <p><?= e($celular['serie'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>IMEI</label>
            <p><?= e($celular['imei']) ?></p>
        </div>
        <div class="detail-item">
            <label>IMEI 2</label>
            <p><?= e($celular['imei2'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>Cor</label>
            <p><?= e($celular['cor'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>Capacidade</label>
            <p><?= e($celular['capacidade'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>Condição</label>
            <p><?= labelCondicao($celular['condicao']) ?></p>
        </div>
        <div class="detail-item">
            <label>Cadastrado em</label>
            <p><?= formatData($celular['created_at']) ?></p>
        </div>
    </div>
    <?php if ($celular['observacoes']): ?>
        <div class="detail-item" style="margin-top:16px;">
            <label>Observações</label>
            <p><?= nl2br(e($celular['observacoes'])) ?></p>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($celular['valor_compra']) || !empty($celular['fornecedor']) || !empty($celular['reservado_para'])): ?>
<div class="detail-section">
    <h2><i class="bi bi-box-seam"></i> Aquisição e Reserva</h2>
    <div class="detail-grid">
        <?php if (!empty($celular['valor_compra'])): ?>
        <div class="detail-item">
            <label>Valor de Compra</label>
            <p><?= formatMoeda((float) $celular['valor_compra']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($celular['data_compra'])): ?>
        <div class="detail-item">
            <label>Data de Compra</label>
            <p><?= formatData($celular['data_compra']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($celular['fornecedor'])): ?>
        <div class="detail-item">
            <label>Fornecedor</label>
            <p><?= e($celular['fornecedor']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($celular['nota_fiscal_compra'])): ?>
        <div class="detail-item">
            <label>Nota Fiscal</label>
            <p><?= e($celular['nota_fiscal_compra']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($celular['origem'])): ?>
        <div class="detail-item">
            <label>Origem</label>
            <p><?= labelOrigem($celular['origem']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($celular['reservado_para'])): ?>
        <div class="detail-item">
            <label>Reservado para</label>
            <p><?= e($celular['reservado_para']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($celular['reservado_ate'])): ?>
        <div class="detail-item">
            <label>Reservado até</label>
            <p><?= formatData($celular['reservado_ate']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($celular['valor_sinal'])): ?>
        <div class="detail-item">
            <label>Valor do Sinal</label>
            <p><?= formatMoeda((float) $celular['valor_sinal']) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="detail-section">
    <h2><i class="bi bi-receipt"></i> Vendas Associadas</h2>
    <?php if (empty($vendas)): ?>
        <div class="empty-state" style="padding:24px;">
            <i class="bi bi-receipt"></i>
            <p>Nenhuma venda registrada para este aparelho.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="border:none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data Venda</th>
                        <th>Comprador</th>
                        <th class="text-right">Valor Compra</th>
                        <th class="text-right">Valor Venda</th>
                        <th class="text-right">Lucro</th>
                        <th>Margem</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $v): ?>
                        <tr>
                            <td><?= formatData($v['data_venda']) ?></td>
                            <td><?= e($v['comprador_nome']) ?></td>
                            <td class="text-right"><?= formatMoeda($v['valor_compra']) ?></td>
                            <td class="text-right"><?= formatMoeda($v['valor_venda']) ?></td>
                            <td class="text-right"><?= formatMoeda($v['lucro']) ?></td>
                            <td>
                                <span class="badge <?= badgeMargem((float) $v['margem_pct']) ?>">
                                    <?= number_format((float) $v['margem_pct'], 1, ',', '.') ?>%
                                </span>
                            </td>
                            <td>
                                <a href="<?= e(baseUrl('pages/vendas/detalhes.php?id=' . $v['id'])) ?>" class="btn btn-ghost btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
