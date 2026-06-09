<?php
/**
 * Exclusão permanente de venda (somente admin)
 */

declare(strict_types=1);

use EnzoTech\Services\VendaService;

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf()) {
    setFlash('erro', 'Requisição inválida.');
    header('Location: ' . baseUrl('pages/vendas/listar.php'));
    exit;
}

$id = (int) ($_POST['venda_id'] ?? 0);

try {
    $service = new VendaService(getPDO());
    $service->excluirPermanente($id);
    setFlash('sucesso', 'Venda excluída permanentemente.');
} catch (Throwable $e) {
    setFlash('erro', erroUsuario($e, $e->getMessage()));
}

header('Location: ' . baseUrl('pages/vendas/listar.php'));
exit;
