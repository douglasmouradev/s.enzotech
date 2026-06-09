<?php
/**
 * Cancelamento de venda
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf()) {
    setFlash('erro', 'Requisição inválida.');
    header('Location: ' . baseUrl('pages/vendas/listar.php'));
    exit;
}

$id = (int) ($_POST['venda_id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');

if ($motivo === '') {
    setFlash('erro', 'Informe o motivo do cancelamento.');
    header('Location: ' . baseUrl('pages/vendas/detalhes.php?id=' . $id));
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();
    $service = new \EnzoTech\Services\VendaService($pdo);

    if ($service->cancelar($id, $motivo)) {
        $pdo->commit();
        registrarAuditoria('venda_cancelada', 'venda', $id, $motivo);
        setFlash('sucesso', 'Venda cancelada. Celular liberado no estoque.');
        header('Location: ' . baseUrl('pages/vendas/detalhes.php?id=' . $id));
    } else {
        $pdo->rollBack();
        setFlash('erro', 'Venda não encontrada ou já cancelada.');
        header('Location: ' . baseUrl('pages/vendas/listar.php'));
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    setFlash('erro', erroUsuario($e));
    header('Location: ' . baseUrl('pages/vendas/detalhes.php?id=' . $id));
}
exit;
