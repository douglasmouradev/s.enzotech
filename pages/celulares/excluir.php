<?php
/**
 * Exclusão de celular (sem vendas vinculadas)
 */

declare(strict_types=1);

use EnzoTech\Services\CelularService;

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf()) {
    setFlash('erro', 'Requisição inválida.');
    header('Location: ' . baseUrl('pages/celulares/listar.php'));
    exit;
}

$id = (int) ($_POST['celular_id'] ?? 0);
$retorno = $_POST['retorno'] ?? 'listar';

try {
    $service = new CelularService(getPDO());
    $service->excluir($id);
    setFlash('sucesso', 'Celular excluído com sucesso.');
    header('Location: ' . baseUrl('pages/celulares/listar.php'));
    exit;
} catch (Throwable $e) {
    setFlash('erro', erroUsuario($e, $e->getMessage()));
    $destino = $retorno === 'detalhes'
        ? baseUrl('pages/celulares/detalhes.php?id=' . $id)
        : baseUrl('pages/celulares/listar.php');
    header('Location: ' . $destino);
    exit;
}
