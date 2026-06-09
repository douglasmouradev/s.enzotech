-- Enzo Tech — Sistema de Controle de Vendas de Celulares
-- MySQL 8.0+ | charset utf8mb4

CREATE DATABASE IF NOT EXISTS enzo_tech
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE enzo_tech;

-- ---------------------------------------------------------------------------
-- Tabela: celulares
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS celulares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  marca VARCHAR(100) NOT NULL,
  modelo VARCHAR(100) NOT NULL,
  serie VARCHAR(100) NULL,
  imei VARCHAR(20) NOT NULL UNIQUE,
  imei2 VARCHAR(20) NULL,
  cor VARCHAR(50) NULL,
  capacidade VARCHAR(20) NULL,
  condicao ENUM('novo','seminovo','usado') NOT NULL DEFAULT 'novo',
  observacoes TEXT NULL,
  status ENUM('disponivel','vendido','reservado') NOT NULL DEFAULT 'disponivel',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_celulares_status (status),
  INDEX idx_celulares_condicao (condicao),
  INDEX idx_celulares_marca_modelo (marca, modelo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Tabela: compradores
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS compradores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_completo VARCHAR(150) NOT NULL,
  cpf VARCHAR(14) NOT NULL UNIQUE,
  rg VARCHAR(20) NULL,
  telefone VARCHAR(20) NOT NULL,
  telefone2 VARCHAR(20) NULL,
  email VARCHAR(100) NULL,
  endereco VARCHAR(200) NULL,
  cidade VARCHAR(100) NULL,
  estado CHAR(2) NULL,
  cep VARCHAR(10) NULL,
  consentimento_lgpd TINYINT(1) NOT NULL DEFAULT 0,
  consentimento_em DATETIME NULL,
  consentimento_ip VARCHAR(45) NULL,
  anonimizado_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_compradores_cpf (cpf),
  INDEX idx_compradores_nome (nome_completo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Tabela: audit_logs (LGPD art. 37 — registro de operações)
-- ---------------------------------------------------------------------------
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

-- ---------------------------------------------------------------------------
-- Tabela: vendas
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vendas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  celular_id INT NOT NULL,
  comprador_id INT NOT NULL,
  data_compra DATE NOT NULL,
  valor_compra DECIMAL(10,2) NOT NULL,
  data_venda DATE NOT NULL,
  valor_venda DECIMAL(10,2) NOT NULL,
  lucro DECIMAL(10,2) GENERATED ALWAYS AS (valor_venda - valor_compra) STORED,
  margem_pct DECIMAL(5,2) GENERATED ALWAYS AS (
    CASE WHEN valor_compra > 0
      THEN ((valor_venda - valor_compra) / valor_compra) * 100
      ELSE 0
    END
  ) STORED,
  forma_pagamento ENUM('dinheiro','pix','cartao_credito','cartao_debito','transferencia','parcelado') NOT NULL,
  parcelas INT NULL,
  observacoes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vendas_celular FOREIGN KEY (celular_id) REFERENCES celulares(id) ON DELETE RESTRICT,
  CONSTRAINT fk_vendas_comprador FOREIGN KEY (comprador_id) REFERENCES compradores(id) ON DELETE RESTRICT,
  INDEX idx_vendas_data_venda (data_venda),
  INDEX idx_vendas_forma_pagamento (forma_pagamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Tabela: documentos
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venda_id INT NOT NULL,
  nome_original VARCHAR(255) NOT NULL,
  nome_arquivo VARCHAR(255) NOT NULL,
  tipo_arquivo VARCHAR(100) NOT NULL,
  tamanho_bytes INT NOT NULL,
  descricao VARCHAR(200) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_documentos_venda FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
  INDEX idx_documentos_venda (venda_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Dados de exemplo: celulares
-- ---------------------------------------------------------------------------
INSERT INTO celulares (marca, modelo, serie, imei, imei2, cor, capacidade, condicao, observacoes, status) VALUES
('Samsung', 'Galaxy S23', 'SM-S911B', '356789012345671', '356789012345679', 'Preto', '256GB', 'seminovo', 'Sem arranhões na tela', 'disponivel'),
('Apple', 'iPhone 14', 'A2882', '356789012345672', NULL, 'Azul', '128GB', 'seminovo', 'Bateria 92%', 'disponivel'),
('Xiaomi', 'Redmi Note 13', '23021RAAEG', '356789012345673', '356789012345680', 'Verde', '128GB', 'novo', 'Lacrado', 'disponivel'),
('Motorola', 'Moto G84', 'XT2347-1', '356789012345674', NULL, 'Grafite', '256GB', 'seminovo', NULL, 'disponivel'),
('Apple', 'iPhone 15 Pro', 'A3101', '356789012345675', '356789012345681', 'Titânio Natural', '256GB', 'novo', 'Com nota fiscal', 'reservado'),
('Samsung', 'Galaxy A54', 'SM-A546B', '356789012345676', NULL, 'Branco', '128GB', 'usado', 'Pequeno risco na lateral', 'disponivel'),
('Motorola', 'Edge 40', 'XT2303-2', '356789012345677', NULL, 'Preto', '256GB', 'seminovo', NULL, 'disponivel'),
('Xiaomi', 'Poco X6 Pro', '23113RKC6G', '356789012345678', '356789012345682', 'Amarelo', '512GB', 'novo', NULL, 'disponivel');
