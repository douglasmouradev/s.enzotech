<?php
/**
 * Exibição da imagem do produto (requer login)
 */

declare(strict_types=1);

use EnzoTech\Services\ProdutoStorage;

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT imagem FROM produtos WHERE id = :id');
$stmt->execute(['id' => $id]);
$produto = $stmt->fetch();

if (!$produto || empty($produto['imagem'])) {
    http_response_code(404);
    exit('Imagem não encontrada.');
}

$arquivo = ProdutoStorage::resolverArquivo((string) $produto['imagem']);
if ($arquivo === null) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($arquivo));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($arquivo);
exit;
