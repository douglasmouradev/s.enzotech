<?php
/**
 * Exclusão de produto
 */

declare(strict_types=1);

use EnzoTech\Services\ProdutoService;

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf()) {
    setFlash('erro', 'Requisição inválida.');
    header('Location: ' . baseUrl('pages/produtos/listar.php'));
    exit;
}

$id = (int) ($_POST['produto_id'] ?? 0);
$retorno = $_POST['retorno'] ?? 'listar';

try {
    $service = new ProdutoService(getPDO());
    $service->excluir($id);
    setFlash('sucesso', 'Produto excluído com sucesso.');
    header('Location: ' . baseUrl('pages/produtos/listar.php'));
    exit;
} catch (Throwable $e) {
    setFlash('erro', erroUsuario($e, $e->getMessage()));
    $destino = $retorno === 'detalhes'
        ? baseUrl('pages/produtos/detalhes.php?id=' . $id)
        : baseUrl('pages/produtos/listar.php');
    header('Location: ' . $destino);
    exit;
}
