<?php
/**
 * Cadastro de venda — wizard em 4 etapas
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
requirePermissao('vendedor');

$pdo = getPDO();

// Endpoint AJAX — busca comprador por CPF (autenticado)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'buscar_cpf') {
    requireAjaxAuth();
    header('Content-Type: application/json; charset=utf-8');

    $cpf = formatCpf(limparCpf($_GET['cpf'] ?? ''));
    if (!validarCpf($cpf)) {
        echo json_encode(['encontrado' => false, 'erro' => 'CPF inválido']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM compradores WHERE cpf = :cpf AND anonimizado_em IS NULL LIMIT 1');
    $stmt->execute(['cpf' => $cpf]);
    $comprador = $stmt->fetch();

    if ($comprador) {
        registrarAuditoria('consulta_cpf', 'comprador', (int) $comprador['id']);
        echo json_encode([
            'encontrado' => true,
            'id' => $comprador['id'],
            'nome_completo' => $comprador['nome_completo'],
            'rg' => $comprador['rg'],
            'telefone' => $comprador['telefone'],
            'telefone2' => $comprador['telefone2'],
            'email' => $comprador['email'],
            'endereco' => $comprador['endereco'],
            'cidade' => $comprador['cidade'],
            'estado' => $comprador['estado'],
            'cep' => $comprador['cep'],
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }
    exit;
}

$erros = [];

// Celulares disponíveis para seleção
$celularesDisp = $pdo->query("
    SELECT id, marca, modelo, imei, cor, capacidade, condicao, valor_compra, data_compra
    FROM celulares
    WHERE status IN ('disponivel', 'reservado')
    ORDER BY marca, modelo
")->fetchAll();

$empresa = empresaConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $erros[] = 'Token de segurança inválido.';
    } else {
        $celularId = (int) ($_POST['celular_id'] ?? 0);
        $dataCompra = $_POST['data_compra'] ?? '';
        $valorCompra = parseMoeda($_POST['valor_compra'] ?? '0');
        $dataVenda = $_POST['data_venda'] ?? '';
        $valorVenda = parseMoeda($_POST['valor_venda'] ?? '0');
        $formaPagamento = $_POST['forma_pagamento'] ?? '';
        $parcelas = !empty($_POST['parcelas']) ? (int) $_POST['parcelas'] : null;
        $observacoes = trim($_POST['observacoes'] ?? '') ?: null;

        $nomeCompleto = trim($_POST['nome_completo'] ?? '');
        $cpf = formatCpf(limparCpf($_POST['cpf'] ?? ''));
        $rg = trim($_POST['rg'] ?? '') ?: null;
        $telefone = trim($_POST['telefone'] ?? '');
        $telefone2 = trim($_POST['telefone2'] ?? '') ?: null;
        $email = trim($_POST['email'] ?? '') ?: null;
        $endereco = trim($_POST['endereco'] ?? '') ?: null;
        $cidade = trim($_POST['cidade'] ?? '') ?: null;
        $estado = trim($_POST['estado'] ?? '') ?: null;
        $cep = trim($_POST['cep'] ?? '') ?: null;
        $compradorId = (int) ($_POST['comprador_id'] ?? 0);

        $formasValidas = ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'transferencia', 'parcelado'];

        if ($celularId <= 0) $erros[] = 'Selecione um celular.';
        if ($dataCompra === '') $erros[] = 'Informe a data de compra.';
        if ($valorCompra <= 0) $erros[] = 'Informe o valor de compra.';
        if ($dataVenda === '') $erros[] = 'Informe a data de venda.';
        if ($valorVenda <= 0) $erros[] = 'Informe o valor de venda.';
        if (!in_array($formaPagamento, $formasValidas, true)) $erros[] = 'Forma de pagamento inválida.';
        if ($formaPagamento === 'parcelado' && (!$parcelas || $parcelas < 2)) $erros[] = 'Informe o número de parcelas.';
        if ($nomeCompleto === '') $erros[] = 'Informe o nome do comprador.';
        if (!validarCpf($cpf)) $erros[] = 'CPF inválido.';
        if ($telefone === '') $erros[] = 'Informe o telefone do comprador.';
        if (empty($_POST['consentimento_lgpd'])) {
            $erros[] = 'É necessário o consentimento do titular para tratamento dos dados pessoais (LGPD).';
        }

        $garantiaDias = max(0, (int) ($_POST['garantia_dias'] ?? 90));

        if (empty($erros)) {
            try {
                $pdo->beginTransaction();
                $vendaService = new \EnzoTech\Services\VendaService($pdo);

                if (!$vendaService->celularDisponivelParaVenda($celularId)) {
                    throw new RuntimeException('Celular indisponível ou já possui venda ativa.');
                }

                $consentIp = clientIp();
                $politicaVersao = $empresa['politica_versao'] ?? '1.0';

                if ($compradorId > 0) {
                    $stmtComp = $pdo->prepare("
                        UPDATE compradores SET nome_completo = :nome, rg = :rg, telefone = :tel,
                            telefone2 = :tel2, email = :email, endereco = :endereco, cidade = :cidade,
                            estado = :estado, cep = :cep, consentimento_lgpd = 1, consentimento_em = NOW(),
                            consentimento_ip = :consent_ip, consentimento_politica_versao = :versao
                        WHERE id = :id AND anonimizado_em IS NULL
                    ");
                    $stmtComp->execute([
                        'nome' => $nomeCompleto, 'rg' => $rg, 'tel' => $telefone, 'tel2' => $telefone2,
                        'email' => $email, 'endereco' => $endereco, 'cidade' => $cidade, 'estado' => $estado,
                        'cep' => $cep, 'consent_ip' => $consentIp, 'versao' => $politicaVersao, 'id' => $compradorId,
                    ]);
                } else {
                    $stmtComp = $pdo->prepare("
                        INSERT INTO compradores (nome_completo, cpf, rg, telefone, telefone2, email, endereco,
                            cidade, estado, cep, consentimento_lgpd, consentimento_em, consentimento_ip, consentimento_politica_versao)
                        VALUES (:nome, :cpf, :rg, :tel, :tel2, :email, :endereco, :cidade, :estado, :cep, 1, NOW(), :consent_ip, :versao)
                    ");
                    $stmtComp->execute([
                        'nome' => $nomeCompleto, 'cpf' => $cpf, 'rg' => $rg, 'tel' => $telefone,
                        'tel2' => $telefone2, 'email' => $email, 'endereco' => $endereco, 'cidade' => $cidade,
                        'estado' => $estado, 'cep' => $cep, 'consent_ip' => $consentIp, 'versao' => $politicaVersao,
                    ]);
                    $compradorId = (int) $pdo->lastInsertId();
                }

                $garantiaAte = \EnzoTech\Services\VendaService::calcularGarantiaAte($dataVenda, $garantiaDias);

                $stmtVenda = $pdo->prepare("
                    INSERT INTO vendas (celular_id, comprador_id, data_compra, valor_compra, data_venda, valor_venda,
                        forma_pagamento, parcelas, observacoes, garantia_dias, garantia_ate, status_venda)
                    VALUES (:celular_id, :comprador_id, :data_compra, :valor_compra, :data_venda, :valor_venda,
                        :forma_pagamento, :parcelas, :observacoes, :garantia_dias, :garantia_ate, 'ativa')
                ");
                $stmtVenda->execute([
                    'celular_id' => $celularId, 'comprador_id' => $compradorId,
                    'data_compra' => $dataCompra, 'valor_compra' => $valorCompra,
                    'data_venda' => $dataVenda, 'valor_venda' => $valorVenda,
                    'forma_pagamento' => $formaPagamento, 'parcelas' => $parcelas,
                    'observacoes' => $observacoes, 'garantia_dias' => $garantiaDias, 'garantia_ate' => $garantiaAte,
                ]);
                $vendaId = (int) $pdo->lastInsertId();

                $pdo->prepare("UPDATE celulares SET status = 'vendido', valor_compra = :vc, data_compra = :dc WHERE id = :id")
                    ->execute(['vc' => $valorCompra, 'dc' => $dataCompra, 'id' => $celularId]);

                $uploadService = new \EnzoTech\Services\UploadService($pdo);
                $uploadResult = $uploadService->processar($vendaId, $_FILES['documentos'] ?? [], $_POST['descricoes'] ?? []);
                foreach ($uploadResult['erros'] as $ue) {
                    $erros[] = $ue;
                }

                $pdo->commit();
                registrarAuditoria('venda_criada', 'venda', $vendaId, 'Comprador #' . $compradorId);
                setFlash('sucesso', 'Venda registrada com sucesso!');
                header('Location: ' . baseUrl('pages/vendas/detalhes.php?id=' . $vendaId));
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $erros[] = erroUsuario($e, 'Erro ao registrar venda. Verifique os dados e tente novamente.');
            }
        }
    }
}

$pageTitle = 'Nova Venda';
$activeMenu = 'vendas-cadastrar';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Nova Venda</h1>
        <p class="page-subtitle">Registre uma venda em 4 etapas</p>
    </div>
</div>

<?php if (!empty($erros)): ?>
    <div class="alert alert-error">
        <?php foreach ($erros as $erro): ?>
            <div><?= e($erro) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" id="venda-wizard" class="form-card">
    <?= csrfField() ?>
    <input type="hidden" name="comprador_id" id="comprador_id" value="">

    <div class="wizard-steps">
        <div class="wizard-step active" data-step="1"><span class="step-num">1</span> Celular</div>
        <div class="wizard-step" data-step="2"><span class="step-num">2</span> Financeiro</div>
        <div class="wizard-step" data-step="3"><span class="step-num">3</span> Comprador</div>
        <div class="wizard-step" data-step="4"><span class="step-num">4</span> Documentos</div>
    </div>

    <!-- Etapa 1: Celular -->
    <div class="wizard-panel active" data-step="1">
        <div class="search-select-wrap" id="celular-search-wrap">
            <label for="celular_search">Selecionar Celular *</label>
            <input type="text" id="celular_search" class="form-control search-select-input" placeholder="Buscar por marca, modelo ou IMEI..." autocomplete="off">
            <input type="hidden" name="celular_id" id="celular_id" required>
            <div class="search-select-list" id="celular-search-list">
                <?php foreach ($celularesDisp as $c): ?>
                    <div class="search-select-option"
                         data-id="<?= $c['id'] ?>"
                         data-label="<?= e($c['marca'] . ' ' . $c['modelo'] . ' — ' . $c['imei']) ?>"
                         data-marca="<?= e($c['marca']) ?>"
                         data-modelo="<?= e($c['modelo']) ?>"
                         data-imei="<?= e($c['imei']) ?>"
                         data-cor="<?= e($c['cor'] ?? '') ?>"
                         data-capacidade="<?= e($c['capacidade'] ?? '') ?>"
                         data-condicao="<?= labelCondicao($c['condicao']) ?>"
                         data-valor-compra="<?= e($c['valor_compra'] ? number_format((float)$c['valor_compra'], 2, ',', '.') : '') ?>"
                         data-data-compra="<?= e($c['data_compra'] ?? '') ?>">
                        <?= e($c['marca'] . ' ' . $c['modelo']) ?> — <?= e($c['imei']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($celularesDisp)): ?>
            <div class="alert alert-error" style="margin-top:16px;">Nenhum celular disponível no estoque.</div>
        <?php endif; ?>

        <div class="celular-resumo" id="celular-resumo">
            <h3>Resumo do Aparelho</h3>
            <div class="celular-resumo-grid">
                <div><span>Marca: </span><strong data-r="marca"></strong></div>
                <div><span>Modelo: </span><strong data-r="modelo"></strong></div>
                <div><span>IMEI: </span><strong data-r="imei"></strong></div>
                <div><span>Cor: </span><strong data-r="cor"></strong></div>
                <div><span>Capacidade: </span><strong data-r="capacidade"></strong></div>
                <div><span>Condição: </span><strong data-r="condicao"></strong></div>
            </div>
        </div>
    </div>

    <!-- Etapa 2: Financeiro -->
    <div class="wizard-panel" data-step="2">
        <div class="form-grid">
            <div class="form-group">
                <label for="data_compra">Data da Compra (lojista) *</label>
                <input type="date" id="data_compra" name="data_compra" class="form-control" required value="<?= e($_POST['data_compra'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label for="valor_compra">Valor de Compra *</label>
                <input type="text" id="valor_compra" name="valor_compra" class="form-control" data-mask="moeda" required placeholder="0,00" value="<?= e($_POST['valor_compra'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="data_venda">Data da Venda *</label>
                <input type="date" id="data_venda" name="data_venda" class="form-control" required value="<?= e($_POST['data_venda'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label for="valor_venda">Valor de Venda *</label>
                <input type="text" id="valor_venda" name="valor_venda" class="form-control" data-mask="moeda" required placeholder="0,00" value="<?= e($_POST['valor_venda'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="forma_pagamento">Forma de Pagamento *</label>
                <select id="forma_pagamento" name="forma_pagamento" class="form-control" required>
                    <option value="pix">PIX</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="cartao_credito">Cartão de Crédito</option>
                    <option value="cartao_debito">Cartão de Débito</option>
                    <option value="transferencia">Transferência</option>
                    <option value="parcelado">Parcelado</option>
                </select>
            </div>
            <div class="form-group" id="parcelas-group" style="display:none;">
                <label for="parcelas">Parcelas</label>
                <input type="number" id="parcelas" name="parcelas" class="form-control" min="2" max="24" value="<?= e($_POST['parcelas'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="garantia_dias">Garantia (dias)</label>
                <input type="number" id="garantia_dias" name="garantia_dias" class="form-control" min="0" max="365" value="<?= e($_POST['garantia_dias'] ?? '90') ?>">
            </div>
        </div>

        <div class="margin-panel" id="margin-panel">
            <div>
                <div class="mp-label">Lucro</div>
                <div class="mp-value" data-mp="lucro">R$ 0,00</div>
            </div>
            <div>
                <div class="mp-label">Margem</div>
                <div class="mp-value" data-mp="margem">0,0%</div>
            </div>
            <div>
                <div class="mp-label">Classificação</div>
                <div style="margin-top:8px;"><span class="badge badge-gray" data-mp="badge">—</span></div>
            </div>
        </div>

        <div class="form-group" style="margin-top:16px;">
            <label for="observacoes">Observações</label>
            <textarea id="observacoes" name="observacoes" class="form-control"><?= e($_POST['observacoes'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- Etapa 3: Comprador -->
    <div class="wizard-panel" data-step="3">
        <div class="form-grid">
            <div class="form-group">
                <label for="cpf">CPF *</label>
                <input type="text" id="cpf" name="cpf" class="form-control" data-mask="cpf" required placeholder="000.000.000-00">
            </div>
            <div class="form-group">
                <label for="nome_completo">Nome Completo *</label>
                <input type="text" id="nome_completo" name="nome_completo" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="rg">RG</label>
                <input type="text" id="rg" name="rg" class="form-control">
            </div>
            <div class="form-group">
                <label for="telefone">Telefone *</label>
                <input type="text" id="telefone" name="telefone" class="form-control" data-mask="telefone" required>
            </div>
            <div class="form-group">
                <label for="telefone2">Telefone 2</label>
                <input type="text" id="telefone2" name="telefone2" class="form-control" data-mask="telefone">
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control">
            </div>
            <div class="form-group">
                <label for="endereco">Endereço</label>
                <input type="text" id="endereco" name="endereco" class="form-control">
            </div>
            <div class="form-group">
                <label for="cidade">Cidade</label>
                <input type="text" id="cidade" name="cidade" class="form-control">
            </div>
            <div class="form-group">
                <label for="estado">Estado</label>
                <input type="text" id="estado" name="estado" class="form-control" maxlength="2" placeholder="UF">
            </div>
            <div class="form-group">
                <label for="cep">CEP</label>
                <input type="text" id="cep" name="cep" class="form-control" data-mask="cep">
            </div>
        </div>

        <div class="lgpd-consent-box">
            <label class="lgpd-consent-label">
                <input type="checkbox" name="consentimento_lgpd" id="consentimento_lgpd" value="1" required>
                <span>Declaro que o comprador foi informado e consentiu com o tratamento dos seus dados pessoais para registro da venda, conforme a <a href="<?= e(baseUrl('pages/lgpd/politica.php')) ?>" target="_blank" class="text-link">Política de Privacidade (LGPD)</a>.</span>
            </label>
        </div>
    </div>

    <!-- Etapa 4: Documentos -->
    <div class="wizard-panel" data-step="4">
        <div class="dropzone" id="dropzone">
            <i class="bi bi-cloud-upload"></i>
            <p>Arraste arquivos ou clique para selecionar<br><small class="text-muted">PDF, JPG, PNG, DOCX — máx. 10MB cada, até 10 arquivos</small></p>
            <input type="file" id="documentos" name="documentos[]" multiple accept=".pdf,.jpg,.jpeg,.png,.docx,.doc">
        </div>
        <div class="file-list" id="file-list"></div>
    </div>

    <div class="wizard-nav">
        <button type="button" class="btn btn-ghost" id="wizard-prev" style="display:none;">
            <i class="bi bi-arrow-left"></i> Anterior
        </button>
        <div style="flex:1;"></div>
        <button type="button" class="btn btn-primary" id="wizard-next">
            Próximo <i class="bi bi-arrow-right"></i>
        </button>
        <button type="submit" class="btn btn-primary" id="wizard-submit" style="display:none;">
            <i class="bi bi-check-lg"></i> Finalizar Venda
        </button>
    </div>
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
