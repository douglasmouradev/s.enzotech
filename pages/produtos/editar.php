<?php
/**
 * Edição de produto
 */

declare(strict_types=1);

use EnzoTech\Services\ProdutoService;

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

$pdo = getPDO();
$service = new ProdutoService($pdo);
$id = (int) ($_GET['id'] ?? 0);
$erros = [];

$stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id');
$stmt->execute(['id' => $id]);
$produto = $stmt->fetch();

if (!$produto) {
    setFlash('erro', 'Produto não encontrado.');
    header('Location: ' . baseUrl('pages/produtos/listar.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $erros[] = 'Token de segurança inválido.';
    } else {
        $dados = $service->parsePost($_POST);
        $removerImagem = !empty($_POST['remover_imagem']);
        $erros = array_merge(
            $service->validar($dados),
            $removerImagem ? [] : $service->validarImagem($_FILES['imagem'] ?? null)
        );

        if (empty($erros)) {
            try {
                $imagemAtual = $produto['imagem'] ?? null;
                $service->atualizar($id, $dados);
                $errosImg = $service->atualizarImagem(
                    $id,
                    $_FILES['imagem'] ?? null,
                    $imagemAtual ? (string) $imagemAtual : null,
                    $removerImagem
                );
                registrarAuditoria('produto_atualizado', 'produto', $id);
                if (!empty($errosImg)) {
                    setFlash('sucesso', 'Produto atualizado, mas a imagem não foi salva: ' . implode(' ', $errosImg));
                } else {
                    setFlash('sucesso', 'Produto atualizado com sucesso!');
                }
                header('Location: ' . baseUrl('pages/produtos/detalhes.php?id=' . $id));
                exit;
            } catch (PDOException $e) {
                $erros[] = $service->mensagemErroDuplicidade($e);
            }
        }
        $produto = array_merge($produto, $_POST);
    }
}

$pageTitle = 'Editar Produto';
$activeMenu = 'produtos';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar Produto</h1>
        <p class="page-subtitle"><?= e($produto['nome']) ?></p>
    </div>
    <a href="<?= e(baseUrl('pages/produtos/detalhes.php?id=' . $id)) ?>" class="btn btn-ghost">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php require __DIR__ . '/../../includes/partials/alert-errors.php'; ?>

<?php
$modo = 'editar';
$cancelUrl = baseUrl('pages/produtos/detalhes.php?id=' . $id);
require __DIR__ . '/../../includes/partials/produto-form.php';
?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
