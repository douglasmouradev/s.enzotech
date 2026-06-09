<?php
/**
 * Exclusão permanente de comprador (sem vendas vinculadas)
 */

declare(strict_types=1);

use EnzoTech\Services\CompradorService;

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf()) {
    setFlash('erro', 'Requisição inválida.');
    header('Location: ' . baseUrl('pages/compradores/listar.php'));
    exit;
}

$id = (int) ($_POST['comprador_id'] ?? 0);
$retorno = $_POST['retorno'] ?? 'listar';

try {
    $service = new CompradorService(getPDO());
    $service->excluir($id);
    setFlash('sucesso', 'Comprador excluído com sucesso.');
    header('Location: ' . baseUrl('pages/compradores/listar.php'));
    exit;
} catch (Throwable $e) {
    setFlash('erro', erroUsuario($e, $e->getMessage()));
    $destino = $retorno === 'detalhes'
        ? baseUrl('pages/compradores/detalhes.php?id=' . $id)
        : baseUrl('pages/compradores/listar.php');
    header('Location: ' . $destino);
    exit;
}
