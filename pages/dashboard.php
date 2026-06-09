<?php
/**
 * Dashboard — métricas, gráficos e alertas
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pdo = getPDO();

$periodo = $_GET['periodo'] ?? 'mes';
$wherePeriodo = match ($periodo) {
    'hoje'  => 'AND data_venda = CURDATE()',
    'semana'=> 'AND data_venda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
    'mes'   => 'AND data_venda >= DATE_FORMAT(CURDATE(), "%Y-%m-01")',
    'ano'   => 'AND data_venda >= DATE_FORMAT(CURDATE(), "%Y-01-01")',
    default => '',
};
$wherePeriodoV = str_replace('data_venda', 'v.data_venda', $wherePeriodo);

// Métricas gerais de estoque
$totalCelulares = (int) $pdo->query('SELECT COUNT(*) FROM celulares')->fetchColumn();
$disponiveis = (int) $pdo->query("SELECT COUNT(*) FROM celulares WHERE status = 'disponivel'")->fetchColumn();
$reservados = (int) $pdo->query("SELECT COUNT(*) FROM celulares WHERE status = 'reservado'")->fetchColumn();

// Financeiro do período (somente vendas ativas)
$stmtFin = $pdo->query("
    SELECT COUNT(*) AS total_vendas,
           COALESCE(SUM(valor_venda), 0) AS faturamento,
           COALESCE(SUM(lucro), 0) AS lucro_total,
           COALESCE(AVG(margem_pct), 0) AS margem_media
    FROM vendas
    WHERE status_venda = 'ativa' {$wherePeriodo}
");
$fin = $stmtFin->fetch();

// Período anterior para comparativo
$stmtAnt = $pdo->query("
    SELECT COALESCE(SUM(valor_venda), 0) AS faturamento
    FROM vendas v
    WHERE status_venda = 'ativa'
    AND data_venda >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
    AND data_venda < DATE_FORMAT(CURDATE(), '%Y-%m-01')
");
$fatAnterior = (float) ($periodo === 'mes' ? $stmtAnt->fetchColumn() : 0);

// Alertas operacionais
$alertas = [];

$reservasExp = $pdo->query("
    SELECT marca, modelo, reservado_para, reservado_ate FROM celulares
    WHERE status = 'reservado' AND reservado_ate IS NOT NULL AND reservado_ate <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    LIMIT 5
")->fetchAll();
foreach ($reservasExp as $r) {
    $alertas[] = ['tipo' => 'warn', 'msg' => 'Reserva expira: ' . $r['marca'] . ' ' . $r['modelo'] . ' — ' . ($r['reservado_para'] ?: 'cliente')];
}

$garantiasVenc = $pdo->query("
    SELECT COUNT(*) FROM vendas
    WHERE status_venda = 'ativa' AND garantia_ate IS NOT NULL
    AND garantia_ate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")->fetchColumn();
if ($garantiasVenc > 0) {
    $alertas[] = ['tipo' => 'info', 'msg' => $garantiasVenc . ' garantia(s) vencem nos próximos 7 dias'];
}

$estoqueParado = $pdo->query("
    SELECT COUNT(*) FROM celulares
    WHERE status = 'disponivel' AND created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetchColumn();
if ($estoqueParado > 0) {
    $alertas[] = ['tipo' => 'warn', 'msg' => $estoqueParado . ' aparelho(s) disponível(is) há mais de 30 dias'];
}

// Dados para gráficos (últimos 6 meses)
$chartVendas = $pdo->query("
    SELECT DATE_FORMAT(data_venda, '%Y-%m') AS mes,
           SUM(valor_venda) AS total,
           COUNT(*) AS qtd
    FROM vendas
    WHERE status_venda = 'ativa' AND data_venda >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes ORDER BY mes
")->fetchAll();

$chartMarcas = $pdo->query("
    SELECT c.marca, SUM(v.valor_venda) AS total
    FROM vendas v INNER JOIN celulares c ON c.id = v.celular_id
    WHERE v.status_venda = 'ativa' {$wherePeriodoV}
    GROUP BY c.marca ORDER BY total DESC LIMIT 5
")->fetchAll();

$chartPagamento = $pdo->query("
    SELECT forma_pagamento, COUNT(*) AS qtd
    FROM vendas WHERE status_venda = 'ativa' {$wherePeriodo}
    GROUP BY forma_pagamento
")->fetchAll();

// Últimas vendas ativas
$stmt = $pdo->query("
    SELECT v.*, c.marca, c.modelo, comp.nome_completo AS comprador_nome
    FROM vendas v
    INNER JOIN celulares c ON c.id = v.celular_id
    INNER JOIN compradores comp ON comp.id = v.comprador_id
    WHERE v.status_venda = 'ativa'
    ORDER BY v.data_venda DESC, v.id DESC
    LIMIT 8
");
$ultimasVendas = $stmt->fetchAll();

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
window.ENZO_CHARTS = ' . json_encode([
    'vendas' => $chartVendas,
    'marcas' => $chartMarcas,
    'pagamento' => $chartPagamento,
], JSON_UNESCAPED_UNICODE) . ';</script>';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Visão geral do negócio</p>
    </div>
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <div class="period-filter">
            <?php foreach (['hoje' => 'Hoje', 'semana' => '7 dias', 'mes' => 'Mês', 'ano' => 'Ano', 'tudo' => 'Tudo'] as $k => $label): ?>
                <a href="?periodo=<?= $k ?>" class="period-btn<?= $periodo === $k ? ' active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
        <?php if (temPermissao('vendedor')): ?>
        <a href="<?= e(baseUrl('pages/vendas/cadastrar.php')) ?>" class="btn btn-primary">
            <i class="bi bi-cart-plus"></i> Nova Venda
        </a>
        <?php endif; ?>
    </div>
</div>

<?= renderFlash() ?>

<?php if (!empty($alertas)): ?>
<div class="alerts-grid">
    <?php foreach ($alertas as $a): ?>
        <div class="alert-banner <?= e($a['tipo']) ?>">
            <i class="bi bi-<?= $a['tipo'] === 'warn' ? 'exclamation-triangle' : 'info-circle' ?>"></i>
            <?= e($a['msg']) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-label">Estoque</div>
        <div class="metric-value"><?= $totalCelulares ?></div>
        <div class="metric-sub"><?= $disponiveis ?> disp. · <?= $reservados ?> reserv.</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Vendas (período)</div>
        <div class="metric-value orange"><?= (int) $fin['total_vendas'] ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Faturamento</div>
        <div class="metric-value"><?= formatMoeda($fin['faturamento']) ?></div>
        <?php if ($fatAnterior > 0 && $periodo === 'mes'): ?>
            <?php $diff = (($fin['faturamento'] - $fatAnterior) / $fatAnterior) * 100; ?>
            <div class="metric-sub"><?= $diff >= 0 ? '+' : '' ?><?= number_format($diff, 1, ',', '.') ?>% vs mês anterior</div>
        <?php endif; ?>
    </div>
    <div class="metric-card">
        <div class="metric-label">Lucro</div>
        <div class="metric-value orange"><?= formatMoeda($fin['lucro_total']) ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Margem Média</div>
        <div class="metric-value">
            <span class="badge <?= badgeMargem((float) $fin['margem_media']) ?>">
                <?= number_format((float) $fin['margem_media'], 1, ',', '.') ?>%
            </span>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-card">
        <h3>Faturamento — últimos 6 meses</h3>
        <div class="chart-wrap"><canvas id="chart-vendas"></canvas></div>
    </div>
    <div class="chart-card">
        <h3>Top marcas (período)</h3>
        <div class="chart-wrap"><canvas id="chart-marcas"></canvas></div>
    </div>
    <div class="chart-card">
        <h3>Formas de pagamento</h3>
        <div class="chart-wrap"><canvas id="chart-pagamento"></canvas></div>
    </div>
</div>

<div class="table-wrap">
    <div class="table-header-bar">
        <h2>Últimas Vendas</h2>
        <a href="<?= e(baseUrl('pages/vendas/listar.php')) ?>" class="link-muted">Ver todas <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="table-scroll">
    <?php if (empty($ultimasVendas)): ?>
        <div class="empty-state"><i class="bi bi-receipt"></i><p>Nenhuma venda no período.</p></div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Celular</th>
                    <th>Comprador</th>
                    <th class="text-right">Valor</th>
                    <th>Margem</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimasVendas as $v): ?>
                    <tr>
                        <td><?= formatData($v['data_venda']) ?></td>
                        <td><?= e($v['marca'] . ' ' . $v['modelo']) ?></td>
                        <td><?= e($v['comprador_nome']) ?></td>
                        <td class="text-right"><?= formatMoeda($v['valor_venda']) ?></td>
                        <td>
                            <span class="badge <?= badgeMargem((float) $v['margem_pct']) ?>">
                                <?= number_format((float) $v['margem_pct'], 1, ',', '.') ?>%
                            </span>
                        </td>
                        <td>
                            <a href="<?= e(baseUrl('pages/vendas/detalhes.php?id=' . $v['id'])) ?>" class="btn btn-ghost btn-sm" aria-label="Ver venda">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
