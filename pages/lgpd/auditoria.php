<?php
/**
 * Registro de auditoria — LGPD art. 37
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('admin');

$pdo = getPDO();
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 25;
$offset = ($pagina - 1) * $porPagina;

$acao = trim($_GET['acao'] ?? '');
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

$where = [];
$params = [];

if ($acao !== '') {
    $where[] = 'acao LIKE :acao';
    $params['acao'] = '%' . $acao . '%';
}
if ($dataInicio !== '') {
    $where[] = 'created_at >= :data_inicio';
    $params['data_inicio'] = $dataInicio . ' 00:00:00';
}
if ($dataFim !== '') {
    $where[] = 'created_at <= :data_fim';
    $params['data_fim'] = $dataFim . ' 23:59:59';
}

$whereSql = $where ? implode(' AND ', $where) : '1=1';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$stmt = $pdo->prepare("
    SELECT * FROM audit_logs
    WHERE {$whereSql}
    ORDER BY created_at DESC
    LIMIT {$porPagina} OFFSET {$offset}
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$queryParams = array_filter([
    'acao' => $acao,
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim,
]);

$pageTitle = 'Auditoria';
$activeMenu = 'lgpd-audit';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Registro de Auditoria</h1>
        <p class="page-subtitle">Trilha de operações sensíveis — LGPD art. 37</p>
    </div>
    <a href="<?= e(baseUrl('pages/lgpd/politica.php')) ?>" class="btn btn-ghost btn-sm">
        <i class="bi bi-shield-check"></i> Política de Privacidade
    </a>
</div>

<form method="get" class="filters-bar">
    <div class="form-group">
        <label for="acao">Ação</label>
        <input type="text" id="acao" name="acao" class="form-control" placeholder="Ex: login, exportacao" value="<?= e($acao) ?>">
    </div>
    <div class="form-group">
        <label for="data_inicio">De</label>
        <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= e($dataInicio) ?>">
    </div>
    <div class="form-group">
        <label for="data_fim">Até</label>
        <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= e($dataFim) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
    <a href="<?= e(baseUrl('pages/lgpd/auditoria.php')) ?>" class="btn btn-ghost">Limpar</a>
</form>

<div class="table-wrap">
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <i class="bi bi-journal-text"></i>
            <p>Nenhum registro de auditoria.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Entidade</th>
                    <th>IP</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= e(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
                        <td><?= e($log['usuario'] ?? '—') ?></td>
                        <td><span class="badge badge-gray"><?= e($log['acao']) ?></span></td>
                        <td>
                            <?php if ($log['entidade']): ?>
                                <?= e($log['entidade']) ?><?= $log['entidade_id'] ? ' #' . (int) $log['entidade_id'] : '' ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= e($log['ip'] ?? '—') ?></td>
                        <td class="text-muted" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($log['detalhes'] ?? '') ?>">
                            <?= e($log['detalhes'] ?? '—') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= renderPaginacao($pagina, $totalPaginas, $queryParams) ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
