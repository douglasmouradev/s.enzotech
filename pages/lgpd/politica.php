<?php
/**
 * Política de Privacidade — LGPD
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$empresa = empresaConfig();
$logado = isLoggedIn();
$pageTitle = 'Política de Privacidade';
$activeMenu = 'lgpd';

if ($logado) {
    require __DIR__ . '/../../includes/header.php';
} else {
    ?><!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Política de Privacidade — Enzo Tech</title>
        <?php require __DIR__ . '/../../includes/partials/favicon.php'; ?>
        <link rel="stylesheet" href="<?= e(baseUrl('assets/css/style.css')) ?>">
    </head>
    <body style="background:var(--bg-page);">
    <div class="main" style="max-width:800px;margin:0 auto;">
    <?php
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Política de Privacidade</h1>
        <p class="page-subtitle">Lei nº 13.709/2018 (LGPD) — Enzo Tech</p>
    </div>
    <?php if ($logado): ?>
        <a href="<?= e(baseUrl('pages/dashboard.php')) ?>" class="btn btn-ghost btn-sm">Voltar</a>
    <?php else: ?>
        <a href="<?= e(baseUrl('index.php')) ?>" class="btn btn-ghost btn-sm">Login</a>
    <?php endif; ?>
</div>

<div class="detail-section lgpd-doc">
    <h2>1. Controlador dos dados</h2>
    <p><strong><?= e($empresa['razao_social']) ?></strong><?= $empresa['cnpj'] ? ' — CNPJ ' . e($empresa['cnpj']) : '' ?></p>
    <p>Encarregado (DPO): <?= e($empresa['encarregado']) ?> — <a href="mailto:<?= e($empresa['email_lgpd']) ?>" class="text-link"><?= e($empresa['email_lgpd']) ?></a></p>
    <p>Versão da política: <?= e($empresa['politica_versao']) ?></p>

    <h2>2. Dados pessoais tratados</h2>
    <ul>
        <li><strong>Compradores:</strong> nome, CPF, RG, telefone, e-mail, endereço</li>
        <li><strong>Documentos:</strong> notas fiscais, contratos, comprovantes e identificação vinculados às vendas</li>
        <li><strong>Operadores do sistema:</strong> credenciais de acesso e registros de auditoria (IP, data/hora, ações)</li>
    </ul>

    <h2>3. Finalidade e base legal</h2>
    <ul>
        <li>Execução de contrato de compra e venda de aparelhos</li>
        <li>Cumprimento de obrigação legal e fiscal (emissão de documentos, guarda de registros)</li>
        <li>Legítimo interesse para gestão comercial e prevenção a fraudes</li>
        <li>Consentimento do titular no momento do cadastro da venda</li>
    </ul>

    <h2>4. Compartilhamento</h2>
    <p>Os dados não são vendidos nem compartilhados com terceiros para marketing. O acesso é restrito ao operador autenticado do sistema.</p>

    <h2>5. Retenção e segurança</h2>
    <ul>
        <li>Dados mantidos pelo prazo necessário às obrigações legais e comerciais</li>
        <li>Senhas armazenadas com hash bcrypt</li>
        <li>Conexões via prepared statements (PDO)</li>
        <li>Tokens CSRF em formulários</li>
        <li>Upload com validação de MIME real</li>
        <li>Registro de auditoria das operações sensíveis</li>
        <li>Sessões com timeout por inatividade (2 horas)</li>
    </ul>

    <h2>6. Direitos do titular (art. 18 LGPD)</h2>
    <p>O titular pode solicitar ao operador do sistema:</p>
    <ul>
        <li>Confirmação e acesso aos dados</li>
        <li>Correção de dados incompletos ou desatualizados</li>
        <li>Anonimização ou eliminação de dados desnecessários</li>
        <li>Portabilidade dos dados (exportação)</li>
    </ul>
    <p>No sistema, o administrador pode <strong>exportar</strong> ou <strong>anonimizar</strong> dados do comprador na página de perfil do cliente.</p>

    <h2>7. Cookies e sessão</h2>
    <p>Utilizamos apenas cookie de sessão técnica (<code>ENZOTECHSESSID</code>) para autenticação. Não utilizamos cookies de rastreamento ou publicidade.</p>

    <h2>8. Atualizações</h2>
    <p>Esta política pode ser atualizada. Última revisão: <?= date('d/m/Y') ?>.</p>
</div>

<?php
if ($logado) {
    require __DIR__ . '/../../includes/footer.php';
} else {
    echo '</div></body></html>';
}
