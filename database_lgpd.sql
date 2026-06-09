-- Enzo Tech — Migração segurança e LGPD
-- Execute: mysql -u root -p enzo_tech < database_lgpd.sql

USE enzo_tech;

-- Campos LGPD em compradores (ignore erro se colunas já existirem)
ALTER TABLE compradores ADD COLUMN consentimento_lgpd TINYINT(1) NOT NULL DEFAULT 0 AFTER cep;
ALTER TABLE compradores ADD COLUMN consentimento_em DATETIME NULL AFTER consentimento_lgpd;
ALTER TABLE compradores ADD COLUMN consentimento_ip VARCHAR(45) NULL AFTER consentimento_em;
ALTER TABLE compradores ADD COLUMN anonimizado_em DATETIME NULL AFTER consentimento_ip;

-- Trilha de auditoria (LGPD art. 37)
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NULL,
    acao VARCHAR(100) NOT NULL,
    entidade VARCHAR(50) NULL,
    entidade_id INT NULL,
    detalhes TEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_acao (acao),
    INDEX idx_audit_entidade (entidade, entidade_id),
    INDEX idx_audit_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consentimento nos registros de exemplo existentes
UPDATE compradores SET consentimento_lgpd = 1, consentimento_em = created_at WHERE consentimento_lgpd = 0;
