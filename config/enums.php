<?php
/**
 * Constantes e enums do domínio — Enzo Tech
 */

declare(strict_types=1);

return [
    'roles' => ['admin', 'vendedor', 'leitura'],
    'condicoes_celular' => ['novo', 'seminovo', 'usado'],
    'status_celular' => ['disponivel', 'vendido', 'reservado'],
    'status_celular_cadastro' => ['disponivel', 'reservado'],
    'origens_celular' => ['fornecedor', 'pf', 'troca', 'outro'],
    'formas_pagamento' => ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'parcelado'],
    'status_venda' => ['ativa', 'cancelada'],
    'status_produto' => ['ativo', 'inativo'],
];
