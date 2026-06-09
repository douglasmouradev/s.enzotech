-- Enzo Tech v2 — Melhorias robustas
-- Execute: Get-Content database_v2.sql -Raw | mysql -u root -p enzo_tech

USE enzo_tech;

-- ---------------------------------------------------------------------------
-- Usuários do sistema (multi-usuário)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','vendedor','leitura') NOT NULL DEFAULT 'vendedor',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin padrão (senha: enzo@2025) — altere após instalar
INSERT IGNORE INTO usuarios (username, nome, password_hash, role) VALUES
('admin', 'Administrador', '$2y$10$2Bz8JC1p6pw3EHXzcE97LOjPOK/Se57iBOPMXV40tqW9Mr2Xa7Oqi', 'admin');

-- ---------------------------------------------------------------------------
-- Vendas — status, garantia, cancelamento
-- ---------------------------------------------------------------------------
ALTER TABLE vendas ADD COLUMN status_venda ENUM('ativa','cancelada') NOT NULL DEFAULT 'ativa' AFTER observacoes;
ALTER TABLE vendas ADD COLUMN cancelada_em DATETIME NULL AFTER status_venda;
ALTER TABLE vendas ADD COLUMN motivo_cancelamento VARCHAR(255) NULL AFTER cancelada_em;
ALTER TABLE vendas ADD COLUMN garantia_dias INT NULL DEFAULT 90 AFTER motivo_cancelamento;
ALTER TABLE vendas ADD COLUMN garantia_ate DATE NULL AFTER garantia_dias;

-- Corrige dados: mantém apenas a venda mais recente como ativa por celular
UPDATE vendas v
INNER JOIN (
    SELECT celular_id, MAX(id) AS max_id
    FROM vendas
    GROUP BY celular_id
    HAVING COUNT(*) > 1
) dup ON v.celular_id = dup.celular_id AND v.id < dup.max_id
SET v.status_venda = 'cancelada', v.cancelada_em = NOW(), v.motivo_cancelamento = 'Migração v2 — venda duplicada corrigida';

-- ---------------------------------------------------------------------------
-- Celulares — aquisição, reserva, fornecedor
-- ---------------------------------------------------------------------------
ALTER TABLE celulares ADD COLUMN valor_compra DECIMAL(10,2) NULL AFTER capacidade;
ALTER TABLE celulares ADD COLUMN data_compra DATE NULL AFTER valor_compra;
ALTER TABLE celulares ADD COLUMN fornecedor VARCHAR(150) NULL AFTER data_compra;
ALTER TABLE celulares ADD COLUMN nota_fiscal_compra VARCHAR(50) NULL AFTER fornecedor;
ALTER TABLE celulares ADD COLUMN origem ENUM('fornecedor','pf','troca','outro') NULL DEFAULT 'fornecedor' AFTER nota_fiscal_compra;
ALTER TABLE celulares ADD COLUMN reservado_para VARCHAR(150) NULL AFTER status;
ALTER TABLE celulares ADD COLUMN reservado_ate DATE NULL AFTER reservado_para;
ALTER TABLE celulares ADD COLUMN valor_sinal DECIMAL(10,2) NULL AFTER reservado_ate;

-- ---------------------------------------------------------------------------
-- Compradores — versão da política LGPD
-- ---------------------------------------------------------------------------
ALTER TABLE compradores ADD COLUMN consentimento_politica_versao VARCHAR(20) NULL AFTER consentimento_ip;

UPDATE compradores SET consentimento_politica_versao = '1.0' WHERE consentimento_lgpd = 1 AND consentimento_politica_versao IS NULL;

-- Índices de performance
CREATE INDEX idx_vendas_status ON vendas(status_venda);
CREATE INDEX idx_vendas_garantia ON vendas(garantia_ate);
CREATE INDEX idx_celulares_reserva ON celulares(reservado_ate);
