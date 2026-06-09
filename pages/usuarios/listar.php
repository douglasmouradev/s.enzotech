<?php
/**
 * Gerenciamento de usuários
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('admin');

$pdo = getPDO();
$erros = [];
$rolesValidos = appEnums('roles');
$usuarioLogadoId = (int) ($_SESSION['usuario_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $username = trim($_POST['username'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $role = $_POST['role'] ?? 'vendedor';

        if ($username === '' || $nome === '' || strlen($senha) < 6) {
            $erros[] = 'Preencha usuário, nome e senha (mín. 6 caracteres).';
        } elseif (!in_array($role, $rolesValidos, true)) {
            $erros[] = 'Perfil inválido.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO usuarios (username, nome, password_hash, role) VALUES (:u, :n, :p, :r)');
                $stmt->execute(['u' => $username, 'n' => $nome, 'p' => password_hash($senha, PASSWORD_BCRYPT), 'r' => $role]);
                registrarAuditoria('usuario_criado', 'usuario', (int) $pdo->lastInsertId());
                setFlash('sucesso', 'Usuário criado.');
                header('Location: ' . baseUrl('pages/usuarios/listar.php'));
                exit;
            } catch (PDOException $e) {
                $erros[] = erroUsuario($e, 'Erro ao criar usuário. Nome pode já existir.');
            }
        }
    }

    if ($acao === 'toggle_ativo') {
        $id = (int) ($_POST['usuario_id'] ?? 0);
        if ($id === $usuarioLogadoId) {
            $erros[] = 'Você não pode desativar sua própria conta.';
        } elseif ($id > 0) {
            $stmt = $pdo->prepare('UPDATE usuarios SET ativo = NOT ativo WHERE id = :id');
            $stmt->execute(['id' => $id]);
            registrarAuditoria('usuario_toggle_ativo', 'usuario', $id);
            setFlash('sucesso', 'Status do usuário atualizado.');
            header('Location: ' . baseUrl('pages/usuarios/listar.php'));
            exit;
        }
    }

    if ($acao === 'alterar_role') {
        $id = (int) ($_POST['usuario_id'] ?? 0);
        $role = $_POST['role'] ?? '';
        if ($id === $usuarioLogadoId) {
            $erros[] = 'Você não pode alterar seu próprio perfil aqui.';
        } elseif ($id > 0 && in_array($role, $rolesValidos, true)) {
            $stmt = $pdo->prepare('UPDATE usuarios SET role = :role WHERE id = :id');
            $stmt->execute(['role' => $role, 'id' => $id]);
            registrarAuditoria('usuario_alterar_role', 'usuario', $id, 'Novo role: ' . $role);
            setFlash('sucesso', 'Perfil atualizado.');
            header('Location: ' . baseUrl('pages/usuarios/listar.php'));
            exit;
        } else {
            $erros[] = 'Perfil inválido.';
        }
    }
}

$usuarios = $pdo->query('SELECT id, username, nome, role, ativo, created_at FROM usuarios ORDER BY nome')->fetchAll();

$pageTitle = 'Usuários';
$activeMenu = 'usuarios';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Usuários</h1>
        <p class="page-subtitle">Controle de acesso ao sistema</p>
    </div>
</div>

<?php foreach ($erros as $erro): ?><div class="alert alert-error"><?= e($erro) ?></div><?php endforeach; ?>
<?= renderFlash() ?>

<div class="form-card" style="margin-bottom:20px;">
    <h2 style="font-family:'Space Grotesk',sans-serif;font-size:15px;margin:0 0 16px;">Novo Usuário</h2>
    <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="acao" value="criar">
        <div class="form-grid">
            <div class="form-group">
                <label>Usuário</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" class="form-control" required minlength="6">
            </div>
            <div class="form-group">
                <label>Perfil</label>
                <select name="role" class="form-control">
                    <option value="vendedor">Vendedor</option>
                    <option value="leitura">Somente leitura</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus"></i> Criar</button>
    </form>
</div>

<div class="table-wrap">
    <?php if (empty($usuarios)): ?>
        <div class="empty-state">
            <i class="bi bi-person-gear"></i>
            <p>Nenhum usuário cadastrado.</p>
        </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Nome</th><th>Usuário</th><th>Perfil</th><th>Status</th><th>Desde</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= e($u['nome']) ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td>
                        <?php if ((int) $u['id'] !== $usuarioLogadoId): ?>
                        <form method="post" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="acao" value="alterar_role">
                            <input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>">
                            <select name="role" class="form-control" style="width:auto;display:inline-block;padding:4px 8px;font-size:12px;" onchange="this.form.submit()">
                                <?php foreach ($rolesValidos as $r): ?>
                                    <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <?php else: ?>
                            <span class="badge badge-gray"><?= e($u['role']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['ativo'] ? '<span class="badge badge-green">Ativo</span>' : '<span class="badge badge-red">Inativo</span>' ?></td>
                    <td><?= formatData($u['created_at']) ?></td>
                    <td>
                        <?php if ((int) $u['id'] !== $usuarioLogadoId): ?>
                        <form method="post" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="acao" value="toggle_ativo">
                            <input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" aria-label="<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?> usuário">
                                <i class="bi bi-<?= $u['ativo'] ? 'person-dash' : 'person-check' ?>"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
