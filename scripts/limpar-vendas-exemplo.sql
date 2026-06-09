-- Remove dados de exemplo (vendas, documentos, compradores)
-- Execute na VPS após instalação para começar do zero:
-- mysql -u root -p enzo_tech < scripts/limpar-vendas-exemplo.sql

DELETE FROM documentos;
DELETE FROM vendas;
DELETE FROM compradores;

UPDATE celulares
SET status = 'disponivel',
    reservado_para = NULL,
    reservado_ate = NULL,
    valor_sinal = NULL
WHERE status IN ('vendido', 'reservado');
