-- Remove forma de pagamento "transferencia"
-- mysql -u root -p enzo_tech < scripts/migrate-remover-transferencia.sql

USE enzo_tech;

UPDATE vendas SET forma_pagamento = 'pix' WHERE forma_pagamento = 'transferencia';

ALTER TABLE vendas
  MODIFY forma_pagamento ENUM('dinheiro','pix','cartao_credito','cartao_debito','parcelado') NOT NULL;
