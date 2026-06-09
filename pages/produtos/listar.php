<?php
/**
 * Listagem de produtos
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();

$busca = trim($_GET['busca'] ?? '');
$status = $_GET['status'] ?? '';
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 15;
$offset = ($pagina - 1) * $porPagina;

$where = ['1=1'];
$params = [];

if ($busca !== '') {
    $where[] = '(nome LIKE :b1 OR marca LIKE :b2 OR categoria LIKE :b3 OR sku LIKE :b4)';
    $params['b1'] = '%' . $busca . '%';
    $params['b2'] = '%' . $busca . '%';
    $params['b3'] = '%' . $busca . '%';
    $params['b4'] = '%' . $busca . '%';
}

if ($status !== '' && in_array($status, ['ativo', 'inativo'], true)) {
    $where[] = 'status = :status';
    $params['status'] = $status;
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$stmt = $pdo->prepare("
    SELECT * FROM produtos
    WHERE {$whereSql}
    ORDER BY nome ASC
    LIMIT {$porPagina} OFFSET {$offset}
");
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$queryParams = array_filter(['busca' => $busca, 'status' => $status]);

$pageTitle = 'Produtos';
$activeMenu = 'produtos';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Produtos</h1>
        <p class="page-subtitle"><?= $total ?> produto(s) encontrado(s)</p>
    </div>
    <?php if (temPermissao('vendedor')): ?>
    <a href="<?= e(baseUrl('pages/produtos/cadastrar.php')) ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Novo Produto
    </a>
    <?php endif; ?>
</div>

<?= renderFlash() ?>

<form method="get" class="filters-bar">
    <div class="form-group">
        <label for="busca">Buscar</label>
        <input type="text" id="busca" name="busca" class="form-control" placeholder="Nome, marca, categoria ou SKU"
               value="<?= e($busca) ?>">
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status" class="form-control">
            <option value="">Todos</option>
            <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
            <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
    <a href="<?= e(baseUrl('pages/produtos/listar.php')) ?>" class="btn btn-ghost">Limpar</a>
</form>

<div class="table-wrap">
    <div class="table-scroll">
    <?php if (empty($produtos)): ?>
        <div class="empty-state">
            <i class="bi bi-box-seam"></i>
            <p>Nenhum produto encontrado.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>SKU</th>
                    <th class="text-right">Qtd</th>
                    <th class="text-right">Venda</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $p): ?>
                    <tr>
                        <td class="produto-thumb-cell">
                            <?php if (!empty($p['imagem'])): ?>
                                <img src="<?= e(produtoImagemUrl((int) $p['id'])) ?>" alt="" class="produto-thumb">
                            <?php else: ?>
                                <span class="produto-thumb-placeholder"><i class="bi bi-image"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($p['nome']) ?></strong>
                            <?php if ($p['marca']): ?>
                                <br><span class="text-muted"><?= e($p['marca']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($p['categoria'] ?: '—') ?></td>
                        <td><?= e($p['sku'] ?: '—') ?></td>
                        <td class="text-right"><?= (int) $p['quantidade'] ?></td>
                        <td class="text-right"><?= $p['preco_venda'] !== null ? formatMoeda((float) $p['preco_venda']) : '—' ?></td>
                        <td>
                            <span class="badge <?= badgeStatusProduto($p['status']) ?>">
                                <?= labelStatusProduto($p['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="<?= e(baseUrl('pages/produtos/detalhes.php?id=' . $p['id'])) ?>" class="btn btn-ghost btn-sm" title="Detalhes">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (temPermissao('vendedor')): ?>
                                <a href="<?= e(baseUrl('pages/produtos/editar.php?id=' . $p['id'])) ?>" class="btn btn-ghost btn-sm" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= e(baseUrl('pages/produtos/excluir.php')) ?>" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="produto_id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Excluir"
                                            data-confirm="Excluir <?= e($p['nome']) ?> permanentemente?"
                                            aria-label="Excluir produto">
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
        <?= renderPaginacao($pagina, $totalPaginas, $queryParams) ?>
    <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
