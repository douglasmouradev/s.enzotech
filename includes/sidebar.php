<?php
/**
 * Navegação lateral — Enzo Tech
 */

declare(strict_types=1);
?>
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
    <a class="nav-item<?= $activeMenu === 'dashboard' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/dashboard.php')) ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="sidebar-section-label">Estoque</div>
    <a class="nav-item<?= $activeMenu === 'celulares' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/celulares/listar.php')) ?>">
        <i class="bi bi-phone"></i> Celulares
    </a>
    <?php if (temPermissao('vendedor')): ?>
    <a class="nav-item<?= $activeMenu === 'celulares-cadastrar' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/celulares/cadastrar.php')) ?>">
        <i class="bi bi-plus-circle"></i> Novo Celular
    </a>
    <?php endif; ?>

    <div class="sidebar-section-label">Vendas</div>
    <a class="nav-item<?= $activeMenu === 'vendas' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/vendas/listar.php')) ?>">
        <i class="bi bi-receipt"></i> Vendas
    </a>
    <?php if (temPermissao('vendedor')): ?>
    <a class="nav-item<?= $activeMenu === 'vendas-cadastrar' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/vendas/cadastrar.php')) ?>">
        <i class="bi bi-cart-plus"></i> Nova Venda
    </a>
    <?php endif; ?>

    <div class="sidebar-section-label">Clientes</div>
    <a class="nav-item<?= $activeMenu === 'compradores' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/compradores/listar.php')) ?>">
        <i class="bi bi-people"></i> Compradores
    </a>

    <div class="sidebar-section-label">Privacidade</div>
    <a class="nav-item<?= $activeMenu === 'lgpd' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/lgpd/politica.php')) ?>">
        <i class="bi bi-shield-check"></i> LGPD
    </a>
    <a class="nav-item<?= $activeMenu === 'lgpd-ropa' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/lgpd/ropa.php')) ?>">
        <i class="bi bi-list-check"></i> ROPA
    </a>
    <a class="nav-item<?= $activeMenu === 'lgpd-audit' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/lgpd/auditoria.php')) ?>">
        <i class="bi bi-journal-text"></i> Auditoria
    </a>
    <?php if (temPermissao('admin')): ?>
    <a class="nav-item<?= $activeMenu === 'usuarios' ? ' active' : '' ?>" href="<?= e(baseUrl('pages/usuarios/listar.php')) ?>">
        <i class="bi bi-person-gear"></i> Usuários
    </a>
    <?php endif; ?>
</aside>
