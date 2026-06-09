<?php
/**
 * Edição de celular existente
 */

declare(strict_types=1);

use EnzoTech\Services\CelularService;

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

$pdo = getPDO();
$service = new CelularService($pdo);
$id = (int) ($_GET['id'] ?? 0);
$erros = [];

$stmt = $pdo->prepare('SELECT * FROM celulares WHERE id = :id');
$stmt->execute(['id' => $id]);
$celular = $stmt->fetch();

if (!$celular) {
    setFlash('erro', 'Celular não encontrado.');
    header('Location: ' . baseUrl('pages/celulares/listar.php'));
    exit;
}

$temVendaAtiva = $service->temVendaAtiva($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $erros[] = 'Token de segurança inválido.';
    } else {
        $dados = $service->parsePost($_POST);
        $erros = $service->validar($dados, true, $temVendaAtiva);

        if (empty($erros)) {
            try {
                $service->atualizar($id, $dados);
                setFlash('sucesso', 'Celular atualizado com sucesso!');
                header('Location: ' . baseUrl('pages/celulares/detalhes.php?id=' . $id));
                exit;
            } catch (PDOException $e) {
                $erros[] = $service->mensagemErroDuplicidade($e);
            }
        }
        $celular = array_merge($celular, $_POST);
    }
}

if ($temVendaAtiva) {
    $celular['status'] = 'vendido';
}

$pageTitle = 'Editar Celular';
$activeMenu = 'celulares';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar Celular</h1>
        <p class="page-subtitle"><?= e($celular['marca'] . ' ' . $celular['modelo']) ?></p>
    </div>
    <a href="<?= e(baseUrl('pages/celulares/detalhes.php?id=' . $id)) ?>" class="btn btn-ghost">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php require __DIR__ . '/../../includes/partials/alert-errors.php'; ?>

<?php if ($temVendaAtiva): ?>
    <div class="alert alert-error">
        Este aparelho possui venda ativa. O status não pode ser alterado até o cancelamento da venda.
    </div>
<?php endif; ?>

<?php
$modo = 'editar';
$cancelUrl = baseUrl('pages/celulares/detalhes.php?id=' . $id);
require __DIR__ . '/../../includes/partials/celular-form.php';
?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
