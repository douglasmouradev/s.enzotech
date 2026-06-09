-- Índices adicionais — Enzo Tech (executar uma vez)
USE enzo_tech;

ALTER TABLE vendas ADD INDEX idx_vendas_celular (celular_id);
ALTER TABLE vendas ADD INDEX idx_vendas_comprador (comprador_id);
ALTER TABLE vendas ADD INDEX idx_vendas_status_data (status_venda, data_venda);
