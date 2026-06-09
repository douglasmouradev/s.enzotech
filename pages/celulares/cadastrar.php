<?php
/**
 * Cadastro de novo celular
 */

declare(strict_types=1);

use EnzoTech\Services\CelularService;

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

$pdo = getPDO();
$service = new CelularService($pdo);
$erros = [];
$celular = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $erros[] = 'Token de segurança inválido.';
    } else {
        $dados = $service->parsePost($_POST);
        $erros = $service->validar($dados);

        if (empty($erros)) {
            try {
                $service->criar($dados);
                setFlash('sucesso', 'Celular cadastrado com sucesso!');
                header('Location: ' . baseUrl('pages/celulares/listar.php'));
                exit;
            } catch (PDOException $e) {
                $erros[] = $service->mensagemErroDuplicidade($e);
            }
        }
        $celular = $_POST;
    }
}

$pageTitle = 'Novo Celular';
$activeMenu = 'celulares-cadastrar';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Novo Celular</h1>
        <p class="page-subtitle">Cadastre um aparelho no estoque</p>
    </div>
</div>

<?php require __DIR__ . '/../../includes/partials/alert-errors.php'; ?>

<?php
$modo = 'criar';
$cancelUrl = baseUrl('pages/celulares/listar.php');
require __DIR__ . '/../../includes/partials/celular-form.php';
?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
