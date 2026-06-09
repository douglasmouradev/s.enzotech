<?php
/**
 * Detalhes do comprador e histórico de compras
 */

declare(strict_types=1);

use EnzoTech\Services\CompradorService;

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();
$compradorService = new CompradorService($pdo);
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM compradores WHERE id = :id');
$stmt->execute(['id' => $id]);
$comprador = $stmt->fetch();

if (!$comprador) {
    setFlash('erro', 'Comprador não encontrado.');
    header('Location: ' . baseUrl('pages/compradores/listar.php'));
    exit;
}

registrarAuditoria('acesso_dados_pessoais', 'comprador', $id);
$anonimizado = compradorAnonimizado($comprador);

$stmtVendas = $pdo->prepare("
    SELECT v.*, c.marca, c.modelo, c.imei
    FROM vendas v
    INNER JOIN celulares c ON c.id = v.celular_id
    WHERE v.comprador_id = :id
    ORDER BY v.data_venda DESC, v.id DESC
");
$stmtVendas->execute(['id' => $id]);
$vendas = $stmtVendas->fetchAll();

$totalGasto = array_sum(array_column($vendas, 'valor_venda'));

$pageTitle = $comprador['nome_completo'];
$activeMenu = 'compradores';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($comprador['nome_completo']) ?></h1>
        <p class="page-subtitle">Dados do comprador</p>
    </div>
    <div class="form-actions" style="margin:0;">
        <?php if (temPermissao('vendedor') && empty($vendas)): ?>
        <form method="post" action="<?= e(baseUrl('pages/compradores/excluir.php')) ?>" style="display:inline;">
            <?= csrfField() ?>
            <input type="hidden" name="comprador_id" value="<?= $id ?>">
            <input type="hidden" name="retorno" value="detalhes">
            <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Excluir permanentemente este comprador?"
                    aria-label="Excluir comprador">
                <i class="bi bi-trash"></i> Excluir
            </button>
        </form>
        <?php endif; ?>
        <a href="<?= e(baseUrl('pages/compradores/listar.php')) ?>" class="btn btn-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?= renderFlash() ?>

<?php if ($anonimizado): ?>
    <div class="alert alert-error">
        <i class="bi bi-shield-exclamation"></i> Este titular teve os dados pessoais anonimizados conforme LGPD em <?= formatData($comprador['anonimizado_em']) ?>.
    </div>
<?php endif; ?>

<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
    <div class="metric-card">
        <div class="metric-label">Compras</div>
        <div class="metric-value"><?= count($vendas) ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Total Gasto</div>
        <div class="metric-value orange"><?= formatMoeda($totalGasto) ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Cliente desde</div>
        <div class="metric-value" style="font-size:18px;"><?= formatData($comprador['created_at']) ?></div>
    </div>
</div>

<div class="detail-section">
    <h2><i class="bi bi-person"></i> Informações de Contato</h2>
    <div class="detail-grid">
        <div class="detail-item">
            <label>CPF</label>
            <p><?php
                if ($anonimizado) {
                    echo e(mascararCpf($comprador['cpf']));
                } else {
                    $compradorId = $id;
                    $cpf = (string) $comprador['cpf'];
                    $targetId = 'cpf-display';
                    require __DIR__ . '/../../includes/partials/cpf-reveal.php';
                }
            ?></p>
        </div>
        <div class="detail-item">
            <label>RG</label>
            <p><?= e($comprador['rg'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>Telefone</label>
            <p><?= e($comprador['telefone']) ?></p>
        </div>
        <div class="detail-item">
            <label>Telefone 2</label>
            <p><?= e($comprador['telefone2'] ?: '—') ?></p>
        </div>
        <div class="detail-item">
            <label>E-mail</label>
            <p>
                <?php if ($comprador['email']): ?>
                    <a href="mailto:<?= e($comprador['email']) ?>" class="text-link"><?= e($comprador['email']) ?></a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </p>
        </div>
        <div class="detail-item">
            <label>Endereço</label>
            <p>
                <?php if ($comprador['endereco']): ?>
                    <?= e($comprador['endereco']) ?><br>
                    <?= e($comprador['cidade'] ?: '') ?><?= $comprador['estado'] ? ' - ' . e($comprador['estado']) : '' ?>
                    <?= $comprador['cep'] ? ' — CEP ' . e($comprador['cep']) : '' ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </p>
        </div>
    </div>
    <?php if (!empty($comprador['consentimento_lgpd'])): ?>
        <p class="text-muted" style="margin-top:12px;font-size:12px;">
            <i class="bi bi-shield-check"></i> Consentimento LGPD registrado em <?= $comprador['consentimento_em'] ? e(date('d/m/Y H:i', strtotime($comprador['consentimento_em']))) : '—' ?>
        </p>
    <?php endif; ?>
</div>

<?php if (!$anonimizado && temPermissao('admin')): ?>
<div class="detail-section">
    <h2><i class="bi bi-shield-lock"></i> Direitos do Titular (LGPD)</h2>
    <p class="text-muted" style="margin-bottom:16px;">Exportação, correção via nova venda ou anonimização dos dados pessoais.</p>
    <div class="form-actions" style="margin:0;">
        <form method="post" action="<?= e(baseUrl('pages/compradores/exportar.php')) ?>" style="display:inline;">
            <?= csrfField() ?>
            <input type="hidden" name="comprador_id" value="<?= $id ?>">
            <button type="submit" class="btn btn-ghost btn-sm"><i class="bi bi-download"></i> Exportar Dados (JSON)</button>
        </form>
        <form method="post" action="<?= e(baseUrl('pages/compradores/anonimizar.php')) ?>" style="display:inline;">
            <?= csrfField() ?>
            <input type="hidden" name="comprador_id" value="<?= $id ?>">
            <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Anonimizar permanentemente os dados pessoais deste comprador? Os registros de venda serão mantidos sem identificação.">
                <i class="bi bi-person-x"></i> Anonimizar Dados
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="detail-section">
    <h2><i class="bi bi-receipt"></i> Histórico de Compras</h2>
    <?php if (empty($vendas)): ?>
        <p class="text-muted">Nenhuma compra registrada.</p>
    <?php else: ?>
        <div class="table-wrap" style="border:none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Celular</th>
                        <th class="text-right">Valor</th>
                        <th>Pagamento</th>
                        <th>Margem</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $v): ?>
                        <tr>
                            <td><?= formatData($v['data_venda']) ?></td>
                            <td>
                                <strong><?= e($v['marca'] . ' ' . $v['modelo']) ?></strong><br>
                                <span class="text-muted"><?= e($v['imei']) ?></span>
                            </td>
                            <td class="text-right"><?= formatMoeda($v['valor_venda']) ?></td>
                            <td><?= labelFormaPagamento($v['forma_pagamento']) ?></td>
                            <td>
                                <span class="badge <?= badgeMargem((float) $v['margem_pct']) ?>">
                                    <?= number_format((float) $v['margem_pct'], 1, ',', '.') ?>%
                                </span>
                            </td>
                            <td>
                                <a href="<?= e(baseUrl('pages/vendas/detalhes.php?id=' . $v['id'])) ?>" class="btn btn-ghost btn-sm" title="Ver venda">
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
