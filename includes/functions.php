<?php
/**
 * Funções auxiliares globais — Enzo Tech
 */

declare(strict_types=1);

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/helpers.php';

if (!class_exists(\Composer\Autoload\ClassLoader::class)) {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'EnzoTech\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = __DIR__ . '/' . $relative . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
}

initErrorHandling();
sendSecurityHeaders();
initSecureSession();

/**
 * Calcula a URL base da aplicação
 */
function baseUrl(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $root = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');

        if ($docRoot !== '' && str_starts_with($root, $docRoot)) {
            $base = substr($root, strlen($docRoot));
        } else {
            $base = '';
        }

        $base = rtrim($base, '/');
    }

    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

/**
 * Caminho absoluto no filesystem
 */
function basePath(string $path = ''): string
{
    return rtrim(__DIR__ . '/..' . ($path !== '' ? '/' . ltrim($path, '/') : ''), '/');
}

/**
 * Escapa saída HTML
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Verifica se o usuário está autenticado
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['usuario_logado']);
}

/**
 * Redireciona para login se não autenticado
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl('index.php'));
        exit;
    }
    sincronizarRoleSessao();
    validarSessaoIp();
    checkSessionTimeout();
}

/**
 * Gera ou retorna token CSRF da sessão
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Campo hidden com token CSRF
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Valida token CSRF em requisições POST
 */
function validateCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals(csrfToken(), $token);
}

/**
 * Formata valor monetário em Real brasileiro
 */
function formatMoeda(float|string|null $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/**
 * Formata data para exibição (dd/mm/aaaa)
 */
function formatData(?string $data): string
{
    if ($data === null || $data === '') {
        return '—';
    }
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : e($data);
}

/**
 * Converte string monetária BR para float
 */
function parseMoeda(string $valor): float
{
    $valor = preg_replace('/[^\d,]/', '', $valor) ?? '0';
    $valor = str_replace(',', '.', $valor);
    return (float) $valor;
}

/**
 * Remove máscara do CPF
 */
function limparCpf(string $cpf): string
{
    return limparDigitos($cpf);
}

/**
 * Formata CPF para exibição
 */
function formatCpf(string $cpf): string
{
    $cpf = limparCpf($cpf);
    if (strlen($cpf) !== 11) {
        return $cpf;
    }
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

/**
 * Retorna classe CSS do badge conforme margem percentual
 */
function badgeMargem(float $margem): string
{
    if ($margem >= 20) {
        return 'badge-green';
    }
    if ($margem >= 10) {
        return 'badge-amber';
    }
    return 'badge-red';
}

/**
 * Retorna classe CSS do badge de status do celular
 */
function badgeStatusCelular(string $status): string
{
    return match ($status) {
        'disponivel' => 'badge-green',
        'vendido'    => 'badge-gray',
        'reservado'  => 'badge-amber',
        default      => 'badge-gray',
    };
}

/**
 * Label legível do status do celular
 */
function labelStatusCelular(string $status): string
{
    return match ($status) {
        'disponivel' => 'Disponível',
        'vendido'    => 'Vendido',
        'reservado'  => 'Reservado',
        default      => ucfirst($status),
    };
}

/**
 * Retorna classe CSS do badge de status do produto
 */
function badgeStatusProduto(string $status): string
{
    return match ($status) {
        'ativo'   => 'badge-green',
        'inativo' => 'badge-gray',
        default   => 'badge-gray',
    };
}

/**
 * Label legível do status do produto
 */
function labelStatusProduto(string $status): string
{
    return match ($status) {
        'ativo'   => 'Ativo',
        'inativo' => 'Inativo',
        default   => ucfirst($status),
    };
}

/**
 * Label legível da condição do celular
 */
function labelCondicao(string $condicao): string
{
    return match ($condicao) {
        'novo'     => 'Novo',
        'seminovo' => 'Seminovo',
        'usado'    => 'Usado',
        default    => ucfirst($condicao),
    };
}

/**
 * Label legível da origem do aparelho
 */
function labelOrigem(string $origem): string
{
    return match ($origem) {
        'fornecedor' => 'Fornecedor',
        'pf'         => 'Pessoa física',
        'troca'      => 'Troca',
        'outro'      => 'Outro',
        default      => ucfirst($origem),
    };
}

/**
 * Label legível da forma de pagamento
 */
function labelFormaPagamento(string $forma): string
{
    return match ($forma) {
        'dinheiro'       => 'Dinheiro',
        'pix'            => 'PIX',
        'cartao_credito' => 'Cartão de Crédito',
        'cartao_debito'  => 'Cartão de Débito',
        'transferencia'  => 'Transferência',
        'parcelado'      => 'Parcelado',
        default          => ucfirst(str_replace('_', ' ', $forma)),
    };
}

/**
 * Monta URL de paginação preservando query string
 */
function paginacaoUrl(int $pagina, array $params = []): string
{
    $params['pagina'] = $pagina;
    $query = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = strtok($uri, '?') ?: '';
    return $path . ($query !== '' ? '?' . $query : '');
}

/**
 * Renderiza controles de paginação
 */
function renderPaginacao(int $paginaAtual, int $totalPaginas, array $params = []): string
{
    if ($totalPaginas <= 1) {
        return '';
    }

    $html = '<div class="pagination">';

    if ($paginaAtual > 1) {
        $html .= '<a class="page-btn" href="' . e(paginacaoUrl($paginaAtual - 1, $params)) . '"><i class="bi bi-chevron-left"></i></a>';
    }

    $inicio = max(1, $paginaAtual - 2);
    $fim = min($totalPaginas, $paginaAtual + 2);

    for ($i = $inicio; $i <= $fim; $i++) {
        $active = $i === $paginaAtual ? ' active' : '';
        $html .= '<a class="page-btn' . $active . '" href="' . e(paginacaoUrl($i, $params)) . '">' . $i . '</a>';
    }

    if ($paginaAtual < $totalPaginas) {
        $html .= '<a class="page-btn" href="' . e(paginacaoUrl($paginaAtual + 1, $params)) . '"><i class="bi bi-chevron-right"></i></a>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * Define mensagem flash na sessão
 */
function setFlash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/**
 * Exibe e limpa mensagem flash
 */
function renderFlash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $classe = $flash['tipo'] === 'erro' ? 'alert-error' : 'alert-success';
    return '<div class="alert ' . $classe . '">' . e($flash['mensagem']) . '</div>';
}

/**
 * Ícone Bootstrap Icons por tipo MIME
 */
function iconeDocumento(string $mime): string
{
    if (str_starts_with($mime, 'image/')) {
        return 'bi-file-image';
    }
    if ($mime === 'application/pdf') {
        return 'bi-file-pdf';
    }
    if (str_contains($mime, 'word') || str_contains($mime, 'document')) {
        return 'bi-file-word';
    }
    return 'bi-file-earmark';
}

/**
 * Tipos MIME permitidos para upload
 */
function tiposMimePermitidos(): array
{
    return [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
    ];
}

/**
 * Extensões permitidas para upload
 */
function extensoesPermitidas(): array
{
    return ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'doc'];
}
