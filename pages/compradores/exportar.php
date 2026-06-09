<?php
/**
 * Exportação de dados do titular — LGPD (somente POST + CSRF)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf()) {
    setFlash('erro', 'Requisição inválida.');
    header('Location: ' . baseUrl('pages/compradores/listar.php'));
    exit;
}

$id = (int) ($_POST['comprador_id'] ?? 0);

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM compradores WHERE id = :id');
$stmt->execute(['id' => $id]);
$comprador = $stmt->fetch();

if (!$comprador) {
    setFlash('erro', 'Comprador não encontrado.');
    header('Location: ' . baseUrl('pages/compradores/listar.php'));
    exit;
}

$stmtVendas = $pdo->prepare("
    SELECT v.id, v.data_venda, v.valor_venda, v.forma_pagamento, c.marca, c.modelo, c.imei
    FROM vendas v INNER JOIN celulares c ON c.id = v.celular_id
    WHERE v.comprador_id = :id ORDER BY v.data_venda DESC
");
$stmtVendas->execute(['id' => $id]);

registrarAuditoria('exportacao_dados', 'comprador', $id);

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="dados_titular_' . $id . '_' . date('Y-m-d') . '.json"');
echo json_encode([
    'titular' => $comprador,
    'compras' => $stmtVendas->fetchAll(),
    'exportado_em' => date('c'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
