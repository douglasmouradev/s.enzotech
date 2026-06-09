<?php
/**
 * Configuração de banco de dados e credenciais do sistema Enzo Tech
 */

declare(strict_types=1);

// Autenticação — hash bcrypt em auth.local.php (nunca texto puro)
$authConfig = ['user' => 'admin', 'pass_hash' => ''];
$authFile = __DIR__ . '/auth.local.php';
if (is_file($authFile)) {
    $authLocal = require $authFile;
    if (is_array($authLocal)) {
        $authConfig = array_merge($authConfig, $authLocal);
    }
}
define('AUTH_USER', $authConfig['user']);
define('AUTH_PASS_HASH', $authConfig['pass_hash']);

// Conexão MySQL — env vars, database.local.php ou fallback
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'name' => getenv('DB_NAME') ?: 'enzo_tech',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
];

$localFile = __DIR__ . '/database.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $dbConfig = array_merge($dbConfig, $local);
    }
}

define('DB_HOST', $dbConfig['host']);
define('DB_NAME', $dbConfig['name']);
define('DB_USER', $dbConfig['user']);
define('DB_PASS', $dbConfig['pass']);
define('DB_CHARSET', 'utf8mb4');
define('APP_DEBUG', (bool) ($dbConfig['debug'] ?? false));

/**
 * Retorna instância PDO (singleton)
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
