-- Imagem do produto
-- mysql -u root -p enzo_tech < scripts/migrate-produtos-imagem.sql

USE enzo_tech;

ALTER TABLE produtos ADD COLUMN IF NOT EXISTS imagem VARCHAR(255) NULL AFTER observacoes;
