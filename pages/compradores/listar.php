<?php
/**
 * Listagem de compradores
 */

declare(strict_types=1);

use EnzoTech\Services\CompradorService;

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();
$compradorService = new CompradorService($pdo);
$busca = trim($_GET['busca'] ?? '');
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 15;
$offset = ($pagina - 1) * $porPagina;

$where = ['anonimizado_em IS NULL'];
$params = [];

if ($busca !== '') {
    $where[] = '(nome_completo LIKE :b1 OR cpf LIKE :b2 OR telefone LIKE :b3 OR email LIKE :b4)';
    $params['b1'] = '%' . $busca . '%';
    $params['b2'] = '%' . $busca . '%';
    $params['b3'] = '%' . $busca . '%';
    $params['b4'] = '%' . $busca . '%';
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM compradores WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$stmt = $pdo->prepare("
    SELECT c.*, COALESCE(vc.total_compras, 0) AS total_compras
    FROM compradores c
    LEFT JOIN (
        SELECT comprador_id, COUNT(*) AS total_compras
        FROM vendas WHERE status_venda = 'ativa'
        GROUP BY comprador_id
    ) vc ON vc.comprador_id = c.id
    WHERE {$whereSql}
    ORDER BY c.nome_completo
    LIMIT {$porPagina} OFFSET {$offset}
");
$stmt->execute($params);
$compradores = $stmt->fetchAll();

$pageTitle = 'Compradores';
$activeMenu = 'compradores';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Compradores</h1>
        <p class="page-subtitle"><?= $total ?> cliente(s)</p>
    </div>
</div>

<form method="get" class="filters-bar">
    <div class="form-group">
        <label for="busca">Buscar</label>
        <input type="text" id="busca" name="busca" class="form-control" placeholder="Nome, CPF, telefone ou e-mail" value="<?= e($busca) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
</form>

<div class="table-wrap">
    <div class="table-scroll">
    <?php if (empty($compradores)): ?>
        <div class="empty-state"><i class="bi bi-people"></i><p>Nenhum comprador encontrado.</p></div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>Compras</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compradores as $c): ?>
                    <tr>
                        <td><strong><?= e($c['nome_completo']) ?></strong></td>
                        <td><?= e(mascararCpf($c['cpf'])) ?></td>
                        <td><?= e($c['telefone']) ?></td>
                        <td><?= (int) $c['total_compras'] ?></td>
                        <td>
                            <div class="actions">
                                <a href="<?= e(baseUrl('pages/compradores/detalhes.php?id=' . $c['id'])) ?>" class="btn btn-ghost btn-sm" aria-label="Ver comprador">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (temPermissao('vendedor') && !$compradorService->temVendas((int) $c['id'])): ?>
                                <form method="post" action="<?= e(baseUrl('pages/compradores/excluir.php')) ?>" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="comprador_id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Excluir comprador"
                                            data-confirm="Excluir <?= e($c['nome_completo']) ?> permanentemente?"
                                            aria-label="Excluir comprador">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </div>
    <?= renderPaginacao($pagina, $totalPaginas, array_filter(['busca' => $busca])) ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
