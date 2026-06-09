<?php
/**
 * Dados da empresa — LGPD e documentos
 */

declare(strict_types=1);

$empresaConfig = [
    'razao_social'   => 'Enzo Tech',
    'nome_fantasia'  => 'Enzo Tech',
    'cnpj'           => '',
    'endereco'       => '',
    'cidade'         => '',
    'estado'         => '',
    'telefone'       => '',
    'email'          => '',
    'encarregado'    => 'Responsável pelo tratamento de dados',
    'email_lgpd'     => 'privacidade@enzotech.local',
    'politica_versao'=> '1.0',
    'retencao_anos'  => 5,
];

$localFile = __DIR__ . '/empresa.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $empresaConfig = array_merge($empresaConfig, $local);
    }
}

return $empresaConfig;
