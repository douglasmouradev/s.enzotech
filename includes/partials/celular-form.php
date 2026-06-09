<?php
/**
 * Formulário compartilhado de celular
 * @var array<string, mixed> $celular
 * @var string $modo 'criar'|'editar'
 * @var bool $temVendaAtiva
 * @var string $cancelUrl
 * @var string $submitLabel
 */
declare(strict_types=1);

$modo = $modo ?? 'criar';
$temVendaAtiva = $temVendaAtiva ?? false;
$submitLabel = $submitLabel ?? ($modo === 'editar' ? 'Salvar Alterações' : 'Salvar');
$origens = ['fornecedor', 'pf', 'troca', 'outro'];

$valorCompra = $celular['valor_compra'] ?? '';
if ($valorCompra !== '' && is_numeric($valorCompra)) {
    $valorCompra = number_format((float) $valorCompra, 2, ',', '.');
}
$valorSinal = $celular['valor_sinal'] ?? '';
if ($valorSinal !== '' && is_numeric($valorSinal)) {
    $valorSinal = number_format((float) $valorSinal, 2, ',', '.');
}
?>
<form method="post" class="form-card">
    <?= csrfField() ?>

    <h2 class="form-section-title">Aparelho</h2>
    <div class="form-grid">
        <div class="form-group">
            <label for="marca">Marca *</label>
            <input type="text" id="marca" name="marca" class="form-control" required value="<?= e((string) ($celular['marca'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="modelo">Modelo *</label>
            <input type="text" id="modelo" name="modelo" class="form-control" required value="<?= e((string) ($celular['modelo'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="serie">Série</label>
            <input type="text" id="serie" name="serie" class="form-control" value="<?= e((string) ($celular['serie'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="imei">IMEI *</label>
            <input type="text" id="imei" name="imei" class="form-control" data-validate="imei" required maxlength="15" value="<?= e((string) ($celular['imei'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="imei2">IMEI 2</label>
            <input type="text" id="imei2" name="imei2" class="form-control" data-validate="imei" maxlength="15" value="<?= e((string) ($celular['imei2'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="cor">Cor</label>
            <input type="text" id="cor" name="cor" class="form-control" value="<?= e((string) ($celular['cor'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="capacidade">Capacidade</label>
            <input type="text" id="capacidade" name="capacidade" class="form-control" placeholder="Ex: 128GB" value="<?= e((string) ($celular['capacidade'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="condicao">Condição *</label>
            <select id="condicao" name="condicao" class="form-control" required>
                <?php foreach (['novo', 'seminovo', 'usado'] as $c): ?>
                    <option value="<?= $c ?>" <?= ($celular['condicao'] ?? 'novo') === $c ? 'selected' : '' ?>><?= labelCondicao($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status" class="form-control" required <?= $temVendaAtiva ? 'disabled' : '' ?>>
                <option value="disponivel" <?= ($celular['status'] ?? 'disponivel') === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
                <option value="reservado" <?= ($celular['status'] ?? '') === 'reservado' ? 'selected' : '' ?>>Reservado</option>
                <?php if ($modo === 'editar' && ($temVendaAtiva || ($celular['status'] ?? '') === 'vendido')): ?>
                <option value="vendido" <?= ($celular['status'] ?? '') === 'vendido' ? 'selected' : '' ?>>Vendido</option>
                <?php endif; ?>
            </select>
            <?php if ($temVendaAtiva): ?>
                <input type="hidden" name="status" value="vendido">
            <?php endif; ?>
        </div>
    </div>

    <h2 class="form-section-title">Aquisição</h2>
    <div class="form-grid">
        <div class="form-group">
            <label for="valor_compra">Valor de Compra</label>
            <input type="text" id="valor_compra" name="valor_compra" class="form-control" data-mask="moeda" value="<?= e((string) $valorCompra) ?>">
        </div>
        <div class="form-group">
            <label for="data_compra">Data de Compra</label>
            <input type="date" id="data_compra" name="data_compra" class="form-control" value="<?= e((string) ($celular['data_compra'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="fornecedor">Fornecedor</label>
            <input type="text" id="fornecedor" name="fornecedor" class="form-control" value="<?= e((string) ($celular['fornecedor'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="nota_fiscal_compra">Nota Fiscal</label>
            <input type="text" id="nota_fiscal_compra" name="nota_fiscal_compra" class="form-control" value="<?= e((string) ($celular['nota_fiscal_compra'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="origem">Origem</label>
            <select id="origem" name="origem" class="form-control">
                <?php foreach ($origens as $o): ?>
                    <option value="<?= $o ?>" <?= ($celular['origem'] ?? 'fornecedor') === $o ? 'selected' : '' ?>><?= labelOrigem($o) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" id="reserva-fields" style="display:none;">
            <label for="reservado_para">Reservado para</label>
            <input type="text" id="reservado_para" name="reservado_para" class="form-control" value="<?= e((string) ($celular['reservado_para'] ?? '')) ?>">
        </div>
        <div class="form-group" id="reserva-ate-field" style="display:none;">
            <label for="reservado_ate">Reservado até</label>
            <input type="date" id="reservado_ate" name="reservado_ate" class="form-control" value="<?= e((string) ($celular['reservado_ate'] ?? '')) ?>">
        </div>
        <div class="form-group" id="valor-sinal-field" style="display:none;">
            <label for="valor_sinal">Valor do Sinal</label>
            <input type="text" id="valor_sinal" name="valor_sinal" class="form-control" data-mask="moeda" value="<?= e((string) $valorSinal) ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" name="observacoes" class="form-control"><?= e((string) ($celular['observacoes'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= e($submitLabel) ?></button>
        <a href="<?= e($cancelUrl) ?>" class="btn btn-ghost">Cancelar</a>
    </div>
</form>
