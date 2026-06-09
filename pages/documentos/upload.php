<?php
/**
 * Upload e exclusão de documentos vinculados a vendas
 */

declare(strict_types=1);

use EnzoTech\Services\UploadService;

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

$pdo = getPDO();
$uploadService = new UploadService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    if (!validateCsrf()) {
        setFlash('erro', 'Token de segurança inválido.');
        header('Location: ' . baseUrl('pages/vendas/listar.php'));
        exit;
    }

    $documentoId = (int) ($_POST['documento_id'] ?? 0);
    $vendaId = (int) ($_POST['venda_id'] ?? 0);

    if ($uploadService->excluir($documentoId, $vendaId)) {
        setFlash('sucesso', 'Documento excluído com sucesso.');
    } else {
        setFlash('erro', 'Documento não encontrado.');
    }

    header('Location: ' . baseUrl('pages/vendas/detalhes.php?id=' . $vendaId));
    exit;
}

$vendaId = (int) ($_GET['venda_id'] ?? $_POST['venda_id'] ?? 0);
$erros = [];

$stmtVenda = $pdo->prepare('SELECT id FROM vendas WHERE id = :id');
$stmtVenda->execute(['id' => $vendaId]);
if (!$stmtVenda->fetch()) {
    setFlash('erro', 'Venda não encontrada.');
    header('Location: ' . baseUrl('pages/vendas/listar.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') !== 'excluir') {
    if (!validateCsrf()) {
        $erros[] = 'Token de segurança inválido.';
    } elseif (empty($_FILES['documentos']['name'][0])) {
        $erros[] = 'Selecione pelo menos um arquivo.';
    } else {
        $descricoes = $_POST['descricoes'] ?? [];
        $resultado = $uploadService->processar($vendaId, $_FILES['documentos'], $descricoes);
        $erros = array_merge($erros, $resultado['erros']);

        if ($resultado['enviados'] > 0 && empty($erros)) {
            setFlash('sucesso', $resultado['enviados'] . ' documento(s) enviado(s) com sucesso!');
            header('Location: ' . baseUrl('pages/vendas/detalhes.php?id=' . $vendaId));
            exit;
        }
        if ($resultado['enviados'] === 0 && empty($erros)) {
            $erros[] = 'Nenhum arquivo válido foi enviado.';
        }
    }
}

$pageTitle = 'Upload de Documentos';
$activeMenu = 'vendas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Adicionar Documentos</h1>
        <p class="page-subtitle">Venda #<?= $vendaId ?></p>
    </div>
    <a href="<?= e(baseUrl('pages/vendas/detalhes.php?id=' . $vendaId)) ?>" class="btn btn-ghost">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php if (!empty($erros)): ?>
    <div class="alert alert-error">
        <?php foreach ($erros as $erro): ?>
            <div><?= e($erro) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-card">
    <?= csrfField() ?>
    <input type="hidden" name="venda_id" value="<?= $vendaId ?>">

    <div class="dropzone" id="dropzone">
        <i class="bi bi-cloud-upload"></i>
        <p>Arraste arquivos ou clique para selecionar<br><small class="text-muted">PDF, JPG, PNG, DOCX — máx. 10MB cada</small></p>
        <input type="file" id="documentos" name="documentos[]" multiple accept=".pdf,.jpg,.jpeg,.png,.docx,.doc">
    </div>
    <div class="file-list" id="file-list"></div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Enviar Documentos</button>
        <a href="<?= e(baseUrl('pages/vendas/detalhes.php?id=' . $vendaId)) ?>" class="btn btn-ghost">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
