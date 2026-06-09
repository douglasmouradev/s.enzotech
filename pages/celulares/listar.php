<?php
/**
 * Listagem de celulares com busca, filtros e paginação
 */

declare(strict_types=1);

use EnzoTech\Services\CelularService;

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();
$celularService = new CelularService($pdo);

$busca = trim($_GET['busca'] ?? '');
$status = $_GET['status'] ?? '';
$condicao = $_GET['condicao'] ?? '';
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 12;
$offset = ($pagina - 1) * $porPagina;

$where = ['1=1'];
$params = [];

if ($busca !== '') {
    $where[] = '(marca LIKE :busca OR modelo LIKE :busca2 OR imei LIKE :busca3)';
    $params['busca'] = '%' . $busca . '%';
    $params['busca2'] = '%' . $busca . '%';
    $params['busca3'] = '%' . $busca . '%';
}

if ($status !== '' && in_array($status, ['disponivel', 'vendido', 'reservado'], true)) {
    $where[] = 'status = :status';
    $params['status'] = $status;
}

if ($condicao !== '' && in_array($condicao, ['novo', 'seminovo', 'usado'], true)) {
    $where[] = 'condicao = :condicao';
    $params['condicao'] = $condicao;
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM celulares WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$stmt = $pdo->prepare("
    SELECT * FROM celulares
    WHERE {$whereSql}
    ORDER BY created_at DESC
    LIMIT {$porPagina} OFFSET {$offset}
");
$stmt->execute($params);
$celulares = $stmt->fetchAll();

$queryParams = array_filter([
    'busca' => $busca,
    'status' => $status,
    'condicao' => $condicao,
]);

$pageTitle = 'Celulares';
$activeMenu = 'celulares';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Celulares</h1>
        <p class="page-subtitle"><?= $total ?> aparelho(s) encontrado(s)</p>
    </div>
    <?php if (temPermissao('vendedor')): ?>
    <a href="<?= e(baseUrl('pages/celulares/cadastrar.php')) ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Novo Celular
    </a>
    <?php endif; ?>
</div>

<?= renderFlash() ?>

<form method="get" class="filters-bar">
    <div class="form-group">
        <label for="busca">Buscar</label>
        <input type="text" id="busca" name="busca" class="form-control" placeholder="Marca, modelo ou IMEI" value="<?= e($busca) ?>">
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status" class="form-control">
            <option value="">Todos</option>
            <option value="disponivel" <?= $status === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
            <option value="vendido" <?= $status === 'vendido' ? 'selected' : '' ?>>Vendido</option>
            <option value="reservado" <?= $status === 'reservado' ? 'selected' : '' ?>>Reservado</option>
        </select>
    </div>
    <div class="form-group">
        <label for="condicao">Condição</label>
        <select id="condicao" name="condicao" class="form-control">
            <option value="">Todas</option>
            <option value="novo" <?= $condicao === 'novo' ? 'selected' : '' ?>>Novo</option>
            <option value="seminovo" <?= $condicao === 'seminovo' ? 'selected' : '' ?>>Seminovo</option>
            <option value="usado" <?= $condicao === 'usado' ? 'selected' : '' ?>>Usado</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
    <a href="<?= e(baseUrl('pages/celulares/listar.php')) ?>" class="btn btn-ghost">Limpar</a>
</form>

<div class="table-wrap">
    <?php if (empty($celulares)): ?>
        <div class="empty-state">
            <i class="bi bi-phone"></i>
            <p>Nenhum celular encontrado.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Marca / Modelo</th>
                    <th>IMEI</th>
                    <th>Cor</th>
                    <th>Capacidade</th>
                    <th>Condição</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($celulares as $c): ?>
                    <tr>
                        <td>
                            <strong><?= e($c['marca']) ?></strong><br>
                            <span class="text-muted"><?= e($c['modelo']) ?></span>
                        </td>
                        <td><?= e($c['imei']) ?></td>
                        <td><?= e($c['cor'] ?: '—') ?></td>
                        <td><?= e($c['capacidade'] ?: '—') ?></td>
                        <td><?= labelCondicao($c['condicao']) ?></td>
                        <td>
                            <span class="badge <?= badgeStatusCelular($c['status']) ?>">
                                <?= labelStatusCelular($c['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="<?= e(baseUrl('pages/celulares/detalhes.php?id=' . $c['id'])) ?>" class="btn btn-ghost btn-sm" title="Detalhes">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (temPermissao('vendedor')): ?>
                                <a href="<?= e(baseUrl('pages/celulares/editar.php?id=' . $c['id'])) ?>" class="btn btn-ghost btn-sm" title="Editar" aria-label="Editar celular">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if (!$celularService->temVendas((int) $c['id'])): ?>
                                <form method="post" action="<?= e(baseUrl('pages/celulares/excluir.php')) ?>" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="celular_id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Excluir"
                                            data-confirm="Excluir <?= e($c['marca'] . ' ' . $c['modelo']) ?> permanentemente?"
                                            aria-label="Excluir celular">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= renderPaginacao($pagina, $totalPaginas, $queryParams) ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
