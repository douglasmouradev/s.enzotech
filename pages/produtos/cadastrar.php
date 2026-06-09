<?php
/**
 * Cadastro de novo produto
 */

declare(strict_types=1);

use EnzoTech\Services\ProdutoService;

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

$pdo = getPDO();
$service = new ProdutoService($pdo);
$erros = [];
$produto = ['quantidade' => 0, 'status' => 'ativo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $erros[] = 'Token de segurança inválido.';
    } else {
        $dados = $service->parsePost($_POST);
        $erros = $service->validar($dados);

        if (empty($erros)) {
            try {
                $id = $service->criar($dados);
                registrarAuditoria('produto_criado', 'produto', $id);
                setFlash('sucesso', 'Produto cadastrado com sucesso!');
                header('Location: ' . baseUrl('pages/produtos/listar.php'));
                exit;
            } catch (PDOException $e) {
                $erros[] = $service->mensagemErroDuplicidade($e);
            }
        }
        $produto = $_POST;
    }
}

$pageTitle = 'Novo Produto';
$activeMenu = 'produtos-cadastrar';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Novo Produto</h1>
        <p class="page-subtitle">Cadastre um item no estoque de produtos</p>
    </div>
</div>

<?php require __DIR__ . '/../../includes/partials/alert-errors.php'; ?>

<?php
$modo = 'criar';
$cancelUrl = baseUrl('pages/produtos/listar.php');
require __DIR__ . '/../../includes/partials/produto-form.php';
?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
