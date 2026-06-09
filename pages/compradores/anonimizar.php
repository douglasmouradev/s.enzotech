<?php
/**
 * Anonimização de dados do titular — LGPD art. 18
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . baseUrl('pages/vendas/listar.php'));
    exit;
}

if (!validateCsrf()) {
    setFlash('erro', 'Token de segurança inválido.');
    header('Location: ' . baseUrl('pages/vendas/listar.php'));
    exit;
}

$id = (int) ($_POST['comprador_id'] ?? 0);

if (anonimizarComprador($id)) {
    setFlash('sucesso', 'Dados pessoais anonimizados com sucesso. Registros de venda foram mantidos sem identificação.');
} else {
    setFlash('erro', 'Não foi possível anonimizar. O titular pode já estar anonimizado ou não existir.');
}

header('Location: ' . baseUrl('pages/compradores/detalhes.php?id=' . $id));
exit;
