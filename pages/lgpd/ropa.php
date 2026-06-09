<?php
/**
 * ROPA — Registro de Operações de Tratamento (LGPD art. 37)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$empresa = empresaConfig();
$pageTitle = 'ROPA';
$activeMenu = 'lgpd-ropa';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">ROPA — Inventário de Tratamento</h1>
        <p class="page-subtitle">Registro de operações de dados pessoais</p>
    </div>
    <a href="<?= e(baseUrl('pages/lgpd/politica.php')) ?>" class="btn btn-ghost btn-sm">Política</a>
</div>

<div class="detail-section lgpd-doc">
    <p><strong>Controlador:</strong> <?= e($empresa['razao_social']) ?> <?= $empresa['cnpj'] ? '— CNPJ ' . e($empresa['cnpj']) : '' ?></p>
    <p><strong>Encarregado:</strong> <?= e($empresa['encarregado']) ?> — <?= e($empresa['email_lgpd']) ?></p>
    <p><strong>Retenção padrão:</strong> <?= (int) $empresa['retencao_anos'] ?> anos (obrigações legais/fiscais)</p>

    <h2>Operações registradas</h2>
    <table class="data-table">
        <thead>
            <tr><th>Dado</th><th>Finalidade</th><th>Base legal</th><th>Retenção</th></tr>
        </thead>
        <tbody>
            <tr><td>Nome, CPF, RG, contato, endereço</td><td>Registro de venda de aparelho</td><td>Contrato + Consentimento</td><td><?= (int) $empresa['retencao_anos'] ?> anos</td></tr>
            <tr><td>Documentos (NF, RG, contratos)</td><td>Comprovação fiscal e legal</td><td>Obrigação legal</td><td><?= (int) $empresa['retencao_anos'] ?> anos</td></tr>
            <tr><td>IMEI do aparelho</td><td>Controle de estoque e rastreabilidade</td><td>Legítimo interesse</td><td>Enquanto houver registro comercial</td></tr>
            <tr><td>Logs de auditoria (IP, ações)</td><td>Segurança e accountability</td><td>Legítimo interesse</td><td>2 anos</td></tr>
            <tr><td>Credenciais de operador</td><td>Acesso ao sistema</td><td>Contrato</td><td>Enquanto ativo + 6 meses</td></tr>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
