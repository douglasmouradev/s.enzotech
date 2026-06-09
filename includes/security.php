<?php
/**
 * Segurança, sessão e conformidade LGPD — Enzo Tech
 */

declare(strict_types=1);

/** Tempo máximo de inatividade da sessão (segundos) */
const SESSION_TIMEOUT = 7200;

/** Máximo de tentativas de login antes do bloqueio */
const LOGIN_MAX_ATTEMPTS = 5;

/** Janela de bloqueio após tentativas falhas (segundos) */
const LOGIN_LOCKOUT_SECONDS = 900;

/**
 * Indica se o ambiente está em modo debug
 */
function isDebugMode(): bool
{
    return defined('APP_DEBUG') && APP_DEBUG === true;
}

/**
 * Inicializa sessão com parâmetros seguros
 */
function initSecureSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_name('ENZOTECHSESSID');
    session_start();
}

/**
 * Envia headers de segurança HTTP
 */
function sendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
}

/**
 * Configura tratamento de erros para produção
 */
function initErrorHandling(): void
{
    if (isDebugMode()) {
        return;
    }

    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);

    set_exception_handler(static function (Throwable $e): void {
        error_log('Enzo Tech: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Erro</title></head>';
        echo '<body style="font-family:sans-serif;padding:40px;text-align:center;">';
        echo '<h1>Erro interno</h1><p>Ocorreu um problema. Tente novamente mais tarde.</p></body></html>';
        exit;
    });
}

/**
 * IP do cliente (considera proxy local)
 */
function clientIp(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP)) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($parts[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }
    return substr($ip, 0, 45);
}

/**
 * Verifica timeout de sessão
 */
function checkSessionTimeout(): void
{
    if (!isLoggedIn()) {
        return;
    }

    $now = time();
    if (!empty($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        registrarAuditoria('sessao_expirada', 'usuario', null, 'Sessão encerrada por inatividade');
        $_SESSION = [];
        session_destroy();
        header('Location: ' . baseUrl('index.php?expirado=1'));
        exit;
    }

    $_SESSION['last_activity'] = $now;
}

/**
 * Arquivo de controle de tentativas de login
 */
function loginAttemptsFile(): string
{
    $dir = basePath('logs/rate_limit');
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    return $dir . '/' . hash('sha256', clientIp()) . '.json';
}

/**
 * Verifica se o IP está bloqueado por brute force
 */
function isLoginBlocked(): bool
{
    $file = loginAttemptsFile();
    if (!is_file($file)) {
        return false;
    }

    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        return false;
    }

    if (($data['count'] ?? 0) >= LOGIN_MAX_ATTEMPTS) {
        $lockedUntil = (int) ($data['locked_until'] ?? 0);
        if (time() < $lockedUntil) {
            return true;
        }
        @unlink($file);
    }

    return false;
}

/**
 * Registra tentativa de login falha
 */
function recordFailedLogin(): void
{
    $file = loginAttemptsFile();
    $data = ['count' => 0, 'locked_until' => 0];

    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    $data['count'] = (int) ($data['count'] ?? 0) + 1;
    if ($data['count'] >= LOGIN_MAX_ATTEMPTS) {
        $data['locked_until'] = time() + LOGIN_LOCKOUT_SECONDS;
    }

    file_put_contents($file, json_encode($data), LOCK_EX);
    registrarAuditoria('login_falha', 'usuario', null, 'Tentativa inválida — IP ' . clientIp());
}

/**
 * Limpa tentativas após login bem-sucedido
 */
function clearLoginAttempts(): void
{
    $file = loginAttemptsFile();
    if (is_file($file)) {
        @unlink($file);
    }
}

/**
 * Autentica usuário com hash bcrypt
 */
function authenticateUser(string $usuario, string $senha): bool
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE username = :u AND ativo = 1 LIMIT 1');
        $stmt->execute(['u' => $usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['password_hash'])) {
            $_SESSION['usuario_id'] = (int) $user['id'];
            $_SESSION['usuario_role'] = $user['role'];
            $_SESSION['usuario_nome'] = $user['nome'];
            return true;
        }
    } catch (Throwable) {
        // Tabela usuarios pode não existir ainda — fallback abaixo
    }

    if (!defined('AUTH_USER') || !defined('AUTH_PASS_HASH') || AUTH_PASS_HASH === '') {
        return false;
    }

    if (!hash_equals(AUTH_USER, $usuario)) {
        return false;
    }

    if (password_verify($senha, AUTH_PASS_HASH)) {
        $_SESSION['usuario_id'] = 0;
        $_SESSION['usuario_role'] = 'admin';
        $_SESSION['usuario_nome'] = 'Administrador';
        return true;
    }

    return false;
}

/**
 * Valida dígitos verificadores do CPF
 */
function validarCpf(string $cpf): bool
{
    $cpf = limparCpf($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$t] !== $digito) {
            return false;
        }
    }

    return true;
}

/**
 * Mascara CPF para exibição em listagens (LGPD — minimização)
 */
function mascararCpf(?string $cpf): string
{
    $cpf = limparCpf((string) $cpf);
    if (strlen($cpf) !== 11) {
        return '—';
    }
    return '***.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-**';
}

/**
 * Verifica se comprador foi anonimizado
 */
function compradorAnonimizado(array $comprador): bool
{
    return !empty($comprador['anonimizado_em']);
}

/**
 * Registra evento na trilha de auditoria (LGPD art. 37)
 */
function registrarAuditoria(string $acao, ?string $entidade = null, ?int $entidadeId = null, ?string $detalhes = null): void
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (usuario, acao, entidade, entidade_id, detalhes, ip, user_agent)
            VALUES (:usuario, :acao, :entidade, :entidade_id, :detalhes, :ip, :user_agent)
        ");
        $stmt->execute([
            'usuario'     => $_SESSION['usuario_nome'] ?? 'sistema',
            'acao'        => substr($acao, 0, 100),
            'entidade'    => $entidade ? substr($entidade, 0, 50) : null,
            'entidade_id' => $entidadeId,
            'detalhes'    => $detalhes ? substr($detalhes, 0, 2000) : null,
            'ip'          => clientIp(),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        error_log('Auditoria indisponível: ' . $e->getMessage());
    }
}

/**
 * Anonimiza dados pessoais do comprador (direito ao esquecimento)
 */
function anonimizarComprador(int $id): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, anonimizado_em FROM compradores WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $comprador = $stmt->fetch();

    if (!$comprador || !empty($comprador['anonimizado_em'])) {
        return false;
    }

    $stmt = $pdo->prepare("
        UPDATE compradores SET
            nome_completo = :nome,
            cpf = :cpf,
            rg = NULL,
            telefone = :tel,
            telefone2 = NULL,
            email = NULL,
            endereco = NULL,
            cidade = NULL,
            estado = NULL,
            cep = NULL,
            consentimento_lgpd = 0,
            anonimizado_em = NOW()
        WHERE id = :id
    ");
    // CPF sintético único para não violar UNIQUE
    $cpfAnon = sprintf('%03d.%03d.%03d-%02d', 900, $id % 1000, ($id * 7) % 1000, $id % 100);

    $stmt->execute([
        'nome' => 'Titular Anonimizado #' . $id,
        'cpf'  => $cpfAnon,
        'tel'  => '(00) 00000-0000',
        'id'   => $id,
    ]);

    $stmtDocs = $pdo->prepare("
        SELECT d.id, d.venda_id, d.nome_arquivo
        FROM documentos d
        INNER JOIN vendas v ON v.id = d.venda_id
        WHERE v.comprador_id = :id
    ");
    $stmtDocs->execute(['id' => $id]);
    foreach ($stmtDocs->fetchAll() as $doc) {
        \EnzoTech\Services\DocumentStorage::excluirArquivo((int) $doc['venda_id'], (string) $doc['nome_arquivo']);
        $pdo->prepare('DELETE FROM documentos WHERE id = :id')->execute(['id' => $doc['id']]);
    }

    registrarAuditoria('anonimizacao', 'comprador', $id, 'Dados pessoais e documentos removidos conforme LGPD');
    return true;
}

/**
 * Valida requisição AJAX autenticada
 */
function requireAjaxAuth(): void
{
    requireLogin();

    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    if (strtolower($requestedWith) !== 'xmlhttprequest') {
        http_response_code(403);
        exit(json_encode(['erro' => 'Requisição não autorizada']));
    }
}
