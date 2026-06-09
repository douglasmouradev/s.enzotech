<?php
/**
 * Alterar senha do usuário logado
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$pdo = getPDO();
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $atual = $_POST['senha_atual'] ?? '';
    $nova = $_POST['senha_nova'] ?? '';
    $confirma = $_POST['senha_confirma'] ?? '';

    if (strlen($nova) < 6) {
        $erro = 'Nova senha deve ter no mínimo 6 caracteres.';
    } elseif ($nova !== $confirma) {
        $erro = 'Confirmação de senha não confere.';
    } else {
        $userId = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($userId > 0) {
            $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = :id AND ativo = 1');
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            if ($user && password_verify($atual, $user['password_hash'])) {
                $pdo->prepare('UPDATE usuarios SET password_hash = :h WHERE id = :id')
                    ->execute(['h' => password_hash($nova, PASSWORD_BCRYPT), 'id' => $userId]);
                registrarAuditoria('senha_alterada', 'usuario', $userId);
                $sucesso = 'Senha alterada com sucesso.';
            } else {
                $erro = 'Senha atual incorreta.';
            }
        } else {
            $erro = 'Alteração disponível apenas para usuários do banco de dados.';
        }
    }
}

$pageTitle = 'Alterar Senha';
$activeMenu = 'usuarios';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div><h1 class="page-title">Alterar Senha</h1></div>
</div>

<?php if ($erro): ?><div class="alert alert-error"><?= e($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert alert-success"><?= e($sucesso) ?></div><?php endif; ?>

<form method="post" class="form-card" style="max-width:400px;">
    <?= csrfField() ?>
    <div class="form-group">
        <label>Senha atual</label>
        <input type="password" name="senha_atual" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Nova senha</label>
        <input type="password" name="senha_nova" class="form-control" required minlength="6">
    </div>
    <div class="form-group">
        <label>Confirmar nova senha</label>
        <input type="password" name="senha_confirma" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-key"></i> Salvar</button>
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
