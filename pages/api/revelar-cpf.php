<?php
/**
 * Registra auditoria ao revelar CPF na interface
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requireAjaxAuth();
requirePermissao('vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf()) {
    http_response_code(403);
    exit(json_encode(['erro' => 'Requisição inválida']));
}

$id = (int) ($_POST['comprador_id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit(json_encode(['erro' => 'ID inválido']));
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT cpf FROM compradores WHERE id = :id');
$stmt->execute(['id' => $id]);
$cpf = $stmt->fetchColumn();

if (!$cpf) {
    http_response_code(404);
    exit(json_encode(['erro' => 'Comprador não encontrado']));
}

registrarAuditoria('cpf_revelado', 'comprador', $id);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['cpf' => $cpf]);
