<?php
/**
 * Helpers globais adicionais
 */

declare(strict_types=1);

/**
 * Remove caracteres não numéricos (CPF, IMEI, telefone)
 */
function limparDigitos(string $valor): string
{
    return preg_replace('/\D/', '', $valor) ?? '';
}

/**
 * Valida IMEI (15 dígitos + Luhn)
 */
function validarImei(string $imei): bool
{
    return validarImeiLuhn($imei);
}

/**
 * Enums de domínio (config/enums.php)
 *
 * @return array<string, mixed>|list<string>
 */
function appEnums(?string $chave = null): array
{
    static $enums = null;
    if ($enums === null) {
        $enums = require basePath('config/enums.php');
    }
    if ($chave === null) {
        return $enums;
    }
    return $enums[$chave] ?? [];
}

/**
 * Valida IMEI com algoritmo Luhn (validação estrita)
 */
function validarImeiLuhn(string $imei): bool
{
    $imei = limparDigitos($imei);
    if (strlen($imei) !== 15) {
        return false;
    }
    $soma = 0;
    for ($i = 0; $i < 14; $i++) {
        $dig = (int) $imei[$i];
        if ($i % 2 === 1) {
            $dig *= 2;
            if ($dig > 9) {
                $dig -= 9;
            }
        }
        $soma += $dig;
    }
    return ((10 - ($soma % 10)) % 10) === (int) $imei[14];
}

/**
 * Retorna configuração da empresa
 */
function empresaConfig(): array
{
    static $config = null;
    if ($config === null) {
        $config = require basePath('config/empresa.php');
    }
    return $config;
}

/**
 * Mensagem de erro segura para o usuário
 */
function erroUsuario(Throwable $e, string $fallback = 'Ocorreu um erro. Tente novamente.'): string
{
    error_log('Enzo Tech: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    return isDebugMode() ? $e->getMessage() : $fallback;
}

/**
 * Garante role válido na sessão (sessões legadas ou login antigo)
 */
function sincronizarRoleSessao(): void
{
    if (!isLoggedIn()) {
        return;
    }

    $rolesValidos = appEnums('roles');
    $roleAtual = $_SESSION['usuario_role'] ?? '';

    if (in_array($roleAtual, $rolesValidos, true)) {
        return;
    }

    $id = (int) ($_SESSION['usuario_id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = getPDO()->prepare('SELECT role FROM usuarios WHERE id = :id AND ativo = 1 LIMIT 1');
            $stmt->execute(['id' => $id]);
            $role = $stmt->fetchColumn();
            if (is_string($role) && in_array($role, $rolesValidos, true)) {
                $_SESSION['usuario_role'] = $role;
                return;
            }
        } catch (Throwable) {
            // tabela usuarios pode não existir ainda
        }
    }

    // Sessão legada sem usuario_id (auth.local antigo): admin implícito
    if ($id === 0 && empty($_SESSION['usuario_id'])) {
        $_SESSION['usuario_role'] = 'admin';
        return;
    }

    registrarAuditoria('sessao_role_invalida', 'usuario', $id > 0 ? $id : null, 'Role inválida — sessão encerrada');
    $_SESSION = [];
    session_destroy();
    header('Location: ' . baseUrl('index.php?expirado=1'));
    exit;
}

/**
 * Verifica permissão do usuário logado
 */
function temPermissao(string $nivelMinimo): bool
{
    if (isLoggedIn()) {
        sincronizarRoleSessao();
    }

    $roles = ['leitura' => 1, 'vendedor' => 2, 'admin' => 3];
    $role = $_SESSION['usuario_role'] ?? 'leitura';
    return ($roles[$role] ?? 0) >= ($roles[$nivelMinimo] ?? 99);
}

/**
 * Exige permissão mínima
 */
function requirePermissao(string $nivelMinimo): void
{
    requireLogin();
    if (!temPermissao($nivelMinimo)) {
        setFlash('erro', 'Você não tem permissão para esta ação.');
        header('Location: ' . baseUrl('pages/dashboard.php'));
        exit;
    }
}

/**
 * Valida IP da sessão contra hijacking
 */
function validarSessaoIp(): void
{
    if (!isLoggedIn()) {
        return;
    }
    $ipAtual = clientIp();
    if (!empty($_SESSION['login_ip']) && $_SESSION['login_ip'] !== $ipAtual) {
        registrarAuditoria('sessao_ip_invalido', 'usuario', null, 'IP mudou durante sessão');
        $_SESSION = [];
        session_destroy();
        header('Location: ' . baseUrl('index.php?expirado=1'));
        exit;
    }
}

