<?php
/**
 * Recibo / Termo de venda — impressão
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$pdo = getPDO();
$empresa = empresaConfig();

$stmt = $pdo->prepare("
    SELECT v.*, c.marca, c.modelo, c.imei, c.imei2, c.cor, c.capacidade,
           comp.nome_completo, comp.cpf, comp.telefone
    FROM vendas v
    INNER JOIN celulares c ON c.id = v.celular_id
    INNER JOIN compradores comp ON comp.id = v.comprador_id
    WHERE v.id = :id
");
$stmt->execute(['id' => $id]);
$v = $stmt->fetch();

if (!$v) {
    exit('Venda não encontrada.');
}

registrarAuditoria('recibo_gerado', 'venda', $id);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo — Venda #<?= $id ?></title>
    <link rel="stylesheet" href="<?= e(baseUrl('assets/css/style.css')) ?>">
    <style>
        body { max-width: 700px; margin: 40px auto; padding: 20px; background: #fff; }
        .recibo-header { text-align: center; border-bottom: 2px solid #E8510A; padding-bottom: 16px; margin-bottom: 24px; }
        .recibo-header h1 { font-family: 'Space Grotesk', sans-serif; color: #0F1923; margin: 0; }
        .recibo-section { margin-bottom: 20px; }
        .recibo-section h2 { font-size: 13px; text-transform: uppercase; color: #6B7280; margin: 0 0 8px; }
        .recibo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 14px; }
        .assinatura { margin-top: 48px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .assinatura-line { border-top: 1px solid #333; padding-top: 8px; text-align: center; font-size: 12px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
<div class="recibo-header">
    <h1><?= e($empresa['nome_fantasia']) ?></h1>
    <p style="color:#6B7280;font-size:13px;">Termo de Venda de Aparelho Celular</p>
</div>

<div class="recibo-section">
    <h2>Dados da Venda #<?= $id ?></h2>
    <div class="recibo-grid">
        <div><strong>Data:</strong> <?= formatData($v['data_venda']) ?></div>
        <div><strong>Valor:</strong> <?= formatMoeda($v['valor_venda']) ?></div>
        <div><strong>Pagamento:</strong> <?= labelFormaPagamento($v['forma_pagamento']) ?></div>
        <?php if ($v['garantia_ate']): ?>
        <div><strong>Garantia até:</strong> <?= formatData($v['garantia_ate']) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="recibo-section">
    <h2>Aparelho</h2>
    <div class="recibo-grid">
        <div><strong>Marca/Modelo:</strong> <?= e($v['marca'] . ' ' . $v['modelo']) ?></div>
        <div><strong>IMEI:</strong> <?= e($v['imei']) ?></div>
        <div><strong>Cor:</strong> <?= e($v['cor'] ?: '—') ?></div>
        <div><strong>Capacidade:</strong> <?= e($v['capacidade'] ?: '—') ?></div>
    </div>
</div>

<div class="recibo-section">
    <h2>Comprador</h2>
    <div class="recibo-grid">
        <div><strong>Nome:</strong> <?= e($v['nome_completo']) ?></div>
        <div><strong>CPF:</strong> <?= e($v['cpf']) ?></div>
        <div><strong>Telefone:</strong> <?= e($v['telefone']) ?></div>
    </div>
</div>

<p style="font-size:12px;color:#6B7280;margin-top:24px;">
    Declaro ter recebido o aparelho acima em perfeitas condições de uso, conforme descrito.
    <?= e($empresa['nome_fantasia']) ?> — <?= e($empresa['cnpj'] ?: '') ?>
</p>

<div class="assinatura">
    <div class="assinatura-line">Assinatura do Comprador</div>
    <div class="assinatura-line">Assinatura do Vendedor</div>
</div>

<p class="no-print" style="margin-top:32px;text-align:center;">
    <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
</p>
</body>
</html>
