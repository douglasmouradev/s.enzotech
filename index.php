<?php
/**
 * Login e logout — Enzo Tech
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

// Logout seguro
if (isset($_GET['logout'])) {
    if (isLoggedIn()) {
        registrarAuditoria('logout', 'usuario', null);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: ' . baseUrl('index.php'));
    exit;
}

if (isLoggedIn()) {
    header('Location: ' . baseUrl('pages/dashboard.php'));
    exit;
}

$erro = '';
$aviso = isset($_GET['expirado']) ? 'Sua sessão expirou por inatividade. Faça login novamente.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $erro = 'Token de segurança inválido. Tente novamente.';
    } elseif (isLoginBlocked()) {
        $erro = 'Muitas tentativas inválidas. Aguarde 15 minutos e tente novamente.';
    } else {
        $usuario = trim($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (authenticateUser($usuario, $senha)) {
            session_regenerate_id(true);
            clearLoginAttempts();
            $_SESSION['usuario_logado'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['login_ip'] = clientIp();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            registrarAuditoria('login_sucesso', 'usuario', null);
            header('Location: ' . baseUrl('pages/dashboard.php'));
            exit;
        }

        recordFailedLogin();
        $erro = 'Usuário ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Enzo Tech</title>
    <?php require __DIR__ . '/includes/partials/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(baseUrl('assets/css/style.css')) ?>">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand">
            <img src="<?= e(baseUrl('assets/img/logo.jpg')) ?>" alt="Enzo Tech" class="login-logo-img">
            <p class="login-sub">Sistema de controle de vendas de celulares</p>
        </div>

        <?php if ($aviso !== ''): ?>
            <div class="alert alert-error"><?= e($aviso) ?></div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-error"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="post" action="" autocomplete="off">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" class="form-control" placeholder="Digite seu usuário" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" placeholder="Digite sua senha" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
        </form>

        <p class="login-privacy">
            <a href="<?= e(baseUrl('pages/lgpd/politica.php')) ?>">Política de Privacidade (LGPD)</a>
        </p>
    </div>
</div>
</body>
</html>
