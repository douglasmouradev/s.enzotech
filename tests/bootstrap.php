<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

require_once __DIR__ . '/../includes/helpers.php';

if (!function_exists('limparCpf')) {
    function limparCpf(string $cpf): string
    {
        return limparDigitos($cpf);
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return !empty($_SESSION['usuario_logado']);
    }
}

if (!function_exists('baseUrl')) {
    function baseUrl(string $path = ''): string
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('basePath')) {
    function basePath(string $path = ''): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('getPDO')) {
    function getPDO(): PDO
    {
        throw new RuntimeException('Banco indisponível em testes unitários.');
    }
}

require_once __DIR__ . '/../includes/security.php';
