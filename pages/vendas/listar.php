<?php
/**
 * Listagem de vendas com filtros, busca, paginação e exportação CSV
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();

$in = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$busca = trim($in['busca'] ?? '');
$dataInicio = $in['data_inicio'] ?? '';
$dataFim = $in['data_fim'] ?? '';
$marca = $in['marca'] ?? '';
$formaPagamento = $in['forma_pagamento'] ?? '';
$statusVenda = $in['status_venda'] ?? 'ativa';
$pagina = max(1, (int) ($in['pagina'] ?? $_GET['pagina'] ?? 1));
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

$where = [];
$params = [];

if ($statusVenda === 'cancelada') {
    $where[] = "v.status_venda = 'cancelada'";
} elseif ($statusVenda === 'todos') {
    // sem filtro de status
} else {
    $where[] = "v.status_venda = 'ativa'";
}

if ($busca !== '') {
    $where[] = '(comp.nome_completo LIKE :busca OR c.imei LIKE :busca2 OR c.modelo LIKE :busca3 OR c.marca LIKE :busca4)';
    $params['busca'] = '%' . $busca . '%';
    $params['busca2'] = '%' . $busca . '%';
    $params['busca3'] = '%' . $busca . '%';
    $params['busca4'] = '%' . $busca . '%';
}

if ($dataInicio !== '') {
    $where[] = 'v.data_venda >= :data_inicio';
    $params['data_inicio'] = $dataInicio;
}

if ($dataFim !== '') {
    $where[] = 'v.data_venda <= :data_fim';
    $params['data_fim'] = $dataFim;
}

if ($marca !== '') {
    $where[] = 'c.marca = :marca';
    $params['marca'] = $marca;
}

$formasValidas = appEnums('formas_pagamento');
if ($formaPagamento !== '' && in_array($formaPagamento, $formasValidas, true)) {
    $where[] = 'v.forma_pagamento = :forma_pagamento';
    $params['forma_pagamento'] = $formaPagamento;
}

$whereSql = $where ? implode(' AND ', $where) : '1=1';

$baseSql = "
    FROM vendas v
    INNER JOIN celulares c ON c.id = v.celular_id
    INNER JOIN compradores comp ON comp.id = v.comprador_id
    WHERE {$whereSql}
";

// Exportação CSV (POST seguro)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'export_csv' && validateCsrf() && temPermissao('vendedor')) {
    registrarAuditoria('exportacao_csv', 'vendas', null, 'Exportação de vendas');
    $stmt = $pdo->prepare("
        SELECT v.data_venda, c.marca, c.modelo, c.imei, comp.nome_completo,
               v.valor_compra, v.valor_venda, v.lucro, v.margem_pct, v.forma_pagamento
        {$baseSql}
        ORDER BY v.data_venda DESC, v.id DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="vendas_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['Data', 'Marca', 'Modelo', 'IMEI', 'Comprador', 'Valor Compra', 'Valor Venda', 'Lucro', 'Margem %', 'Forma Pagamento'], ';');

    foreach ($rows as $row) {
        fputcsv($out, [
            formatData($row['data_venda']),
            $row['marca'],
            $row['modelo'],
            $row['imei'],
            $row['nome_completo'],
            number_format((float) $row['valor_compra'], 2, ',', '.'),
            number_format((float) $row['valor_venda'], 2, ',', '.'),
            number_format((float) $row['lucro'], 2, ',', '.'),
            number_format((float) $row['margem_pct'], 1, ',', '.'),
            labelFormaPagamento($row['forma_pagamento']),
        ], ';');
    }
    fclose($out);
    exit;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) {$baseSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$stmt = $pdo->prepare("
    SELECT v.*, c.marca, c.modelo, c.imei,
           comp.id AS comprador_id, comp.nome_completo AS comprador_nome,
           comp.cpf AS comprador_cpf, comp.telefone AS comprador_telefone
    {$baseSql}
    ORDER BY v.data_venda DESC, v.id DESC
    LIMIT {$porPagina} OFFSET {$offset}
");
$stmt->execute($params);
$vendas = $stmt->fetchAll();

$marcas = $pdo->query('SELECT DISTINCT marca FROM celulares ORDER BY marca')->fetchAll(PDO::FETCH_COLUMN);

$queryParams = array_filter([
    'busca' => $busca,
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim,
    'marca' => $marca,
    'forma_pagamento' => $formaPagamento,
    'status_venda' => $statusVenda !== 'ativa' ? $statusVenda : '',
]);

$pageTitle = 'Vendas';
$activeMenu = 'vendas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Vendas</h1>
        <p class="page-subtitle"><?= $total ?> venda(s) encontrada(s)</p>
    </div>
    <div class="form-actions" style="margin:0;">
        <?php if (temPermissao('vendedor')): ?>
        <form method="post" style="display:inline;">
            <?= csrfField() ?>
            <input type="hidden" name="acao" value="export_csv">
            <?php foreach ($queryParams as $k => $v): ?>
                <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-ghost"><i class="bi bi-download"></i> Exportar CSV</button>
        </form>
        <?php endif; ?>
        <?php if (temPermissao('vendedor')): ?>
        <a href="<?= e(baseUrl('pages/vendas/cadastrar.php')) ?>" class="btn btn-primary">
            <i class="bi bi-cart-plus"></i> Nova Venda
        </a>
        <?php endif; ?>
    </div>
</div>

<?= renderFlash() ?>

<form method="get" class="filters-bar">
    <div class="form-group">
        <label for="busca">Buscar</label>
        <input type="text" id="busca" name="busca" class="form-control" placeholder="Comprador, IMEI ou modelo" value="<?= e($busca) ?>">
    </div>
    <div class="form-group">
        <label for="data_inicio">Data início</label>
        <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= e($dataInicio) ?>">
    </div>
    <div class="form-group">
        <label for="data_fim">Data fim</label>
        <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= e($dataFim) ?>">
    </div>
    <div class="form-group">
        <label for="marca">Marca</label>
        <select id="marca" name="marca" class="form-control">
            <option value="">Todas</option>
            <?php foreach ($marcas as $m): ?>
                <option value="<?= e($m) ?>" <?= $marca === $m ? 'selected' : '' ?>><?= e($m) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="forma_pagamento">Pagamento</label>
        <select id="forma_pagamento" name="forma_pagamento" class="form-control">
            <option value="">Todas</option>
            <?php foreach ($formasValidas as $f): ?>
                <option value="<?= e($f) ?>" <?= $formaPagamento === $f ? 'selected' : '' ?>><?= labelFormaPagamento($f) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="status_venda">Status</label>
        <select id="status_venda" name="status_venda" class="form-control">
            <option value="ativa" <?= $statusVenda === 'ativa' ? 'selected' : '' ?>>Ativas</option>
            <option value="cancelada" <?= $statusVenda === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
            <option value="todos" <?= $statusVenda === 'todos' ? 'selected' : '' ?>>Todas</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
    <a href="<?= e(baseUrl('pages/vendas/listar.php')) ?>" class="btn btn-ghost">Limpar</a>
</form>

<div class="table-wrap">
    <div class="table-scroll">
    <?php if (empty($vendas)): ?>
        <div class="empty-state">
            <i class="bi bi-receipt"></i>
            <p>Nenhuma venda encontrada.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Celular</th>
                    <th>Comprador</th>
                    <th class="text-right">Compra</th>
                    <th class="text-right">Venda</th>
                    <th class="text-right">Lucro</th>
                    <th>Margem</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendas as $v): ?>
                    <tr<?= ($v['status_venda'] ?? 'ativa') === 'cancelada' ? ' class="row-muted"' : '' ?>>
                        <td>
                            <?= formatData($v['data_venda']) ?>
                            <?php if (($v['status_venda'] ?? 'ativa') === 'cancelada'): ?>
                                <br><span class="badge badge-red">Cancelada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($v['marca'] . ' ' . $v['modelo']) ?></strong><br>
                            <span class="text-muted"><?= e($v['imei']) ?></span>
                        </td>
                        <td>
                            <a href="<?= e(baseUrl('pages/compradores/detalhes.php?id=' . $v['comprador_id'])) ?>" class="text-link">
                                <strong><?= e($v['comprador_nome']) ?></strong>
                            </a><br>
                            <span class="text-muted"><?= e(mascararCpf($v['comprador_cpf'])) ?></span>
                            <?php if ($v['comprador_telefone']): ?>
                                <br><span class="text-muted"><i class="bi bi-telephone"></i> <?= e($v['comprador_telefone']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?= formatMoeda($v['valor_compra']) ?></td>
                        <td class="text-right"><?= formatMoeda($v['valor_venda']) ?></td>
                        <td class="text-right"><?= formatMoeda($v['lucro']) ?></td>
                        <td>
                            <span class="badge <?= badgeMargem((float) $v['margem_pct']) ?>">
                                <?= number_format((float) $v['margem_pct'], 1, ',', '.') ?>%
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="<?= e(baseUrl('pages/compradores/detalhes.php?id=' . $v['comprador_id'])) ?>" class="btn btn-ghost btn-sm" title="Ver comprador">
                                    <i class="bi bi-person"></i>
                                </a>
                                <a href="<?= e(baseUrl('pages/vendas/detalhes.php?id=' . $v['id'])) ?>" class="btn btn-ghost btn-sm" title="Ver venda">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= renderPaginacao($pagina, $totalPaginas, $queryParams) ?>
    <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
