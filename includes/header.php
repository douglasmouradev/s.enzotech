<?php
/**
 * Cabeçalho HTML e topbar — Enzo Tech
 */

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Enzo Tech';
$activeMenu = $activeMenu ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (function_exists('csrfToken') && isLoggedIn()): ?>
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <?php endif; ?>
    <title><?= e($pageTitle) ?> — Enzo Tech</title>
    <?php require __DIR__ . '/partials/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(baseUrl('assets/css/style.css')) ?>">
</head>
<body>
<a href="#main-content" class="skip-link">Ir para o conteúdo</a>
<div class="app">
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="btn-menu-mobile" id="btn-menu-mobile" aria-label="Abrir menu de navegação">
                <i class="bi bi-list"></i>
            </button>
            <a class="logo" href="<?= e(baseUrl('pages/dashboard.php')) ?>">
                <img src="<?= e(baseUrl('assets/img/logo.jpg')) ?>" alt="Enzo Tech" class="logo-img" onerror="this.style.display='none';this.nextElementSibling.style.display='inline';">
                <span class="logo-fallback" style="display:none;">Enzo <span>Tech</span></span>
            </a>
        </div>
        <div class="topbar-right">
            <div class="topbar-user" title="<?= e($_SESSION['usuario_nome'] ?? 'Admin') ?>">
                <?= e(strtoupper(substr($_SESSION['usuario_nome'] ?? 'A', 0, 1))) ?>
            </div>
            <a class="topbar-logout" href="<?= e(baseUrl('pages/usuarios/alterar-senha.php')) ?>" title="Alterar senha" aria-label="Alterar senha">
                <i class="bi bi-key"></i>
            </a>
            <a class="topbar-logout" href="<?= e(baseUrl('index.php?logout=1')) ?>">
                <i class="bi bi-box-arrow-right"></i> Sair
            </a>
        </div>
    </header>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="body-area">
        <?php require __DIR__ . '/sidebar.php'; ?>
        <main class="main" id="main-content">
