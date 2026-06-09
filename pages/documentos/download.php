<?php
/**
 * Download e visualização inline de documentos
 */

declare(strict_types=1);

use EnzoTech\Services\DocumentStorage;

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();

$id = (int) ($_GET['id'] ?? 0);
$mode = $_GET['mode'] ?? 'download';

if (!in_array($mode, ['view', 'download'], true)) {
    http_response_code(400);
    exit('Modo inválido.');
}

$stmt = $pdo->prepare('
    SELECT d.*, v.id AS venda_existe
    FROM documentos d
    INNER JOIN vendas v ON v.id = d.venda_id
    WHERE d.id = :id
');
$stmt->execute(['id' => $id]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    exit('Documento não encontrado.');
}

$arquivoReal = DocumentStorage::resolverArquivo((int) $doc['venda_id'], (string) $doc['nome_arquivo']);

if ($arquivoReal === null) {
    http_response_code(404);
    exit('Arquivo não encontrado no servidor.');
}

registrarAuditoria('documento_' . $mode, 'documento', $id, 'Venda #' . $doc['venda_id']);

$mime = $doc['tipo_arquivo'] ?: 'application/octet-stream';
$nomeDownload = $doc['nome_original'];

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($arquivoReal));
header('X-Content-Type-Options: nosniff');

if ($mode === 'download') {
    header('Content-Disposition: attachment; filename="' . rawurlencode($nomeDownload) . '"');
} else {
    header('Content-Disposition: inline; filename="' . rawurlencode($nomeDownload) . '"');
}

readfile($arquivoReal);
exit;
