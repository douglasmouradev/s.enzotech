-- Bateria (%) e acessórios — celulares
-- Execute em instalações já existentes:
-- mysql -u root -p enzo_tech < scripts/migrate-celulares-bateria.sql

USE enzo_tech;

ALTER TABLE celulares ADD COLUMN IF NOT EXISTS bateria_pct TINYINT UNSIGNED NULL AFTER condicao;
ALTER TABLE celulares ADD COLUMN IF NOT EXISTS acessorios VARCHAR(500) NULL AFTER bateria_pct;
