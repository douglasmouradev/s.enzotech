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
('Samsung', 'Galaxy S23', 'SM-S911B', '356789012345671', '356789012345679', 'Preto', '256GB', 'seminovo', 'Sem arranhões na tela', 'vendido'),
('Apple', 'iPhone 14', 'A2882', '356789012345672', NULL, 'Azul', '128GB', 'seminovo', 'Bateria 92%', 'vendido'),
('Xiaomi', 'Redmi Note 13', '23021RAAEG', '356789012345673', '356789012345680', 'Verde', '128GB', 'novo', 'Lacrado', 'disponivel'),
('Motorola', 'Moto G84', 'XT2347-1', '356789012345674', NULL, 'Grafite', '256GB', 'seminovo', NULL, 'disponivel'),
('Apple', 'iPhone 15 Pro', 'A3101', '356789012345675', '356789012345681', 'Titânio Natural', '256GB', 'novo', 'Com nota fiscal', 'reservado'),
('Samsung', 'Galaxy A54', 'SM-A546B', '356789012345676', NULL, 'Branco', '128GB', 'usado', 'Pequeno risco na lateral', 'vendido'),
('Motorola', 'Edge 40', 'XT2303-2', '356789012345677', NULL, 'Preto', '256GB', 'seminovo', NULL, 'disponivel'),
('Xiaomi', 'Poco X6 Pro', '23113RKC6G', '356789012345678', '356789012345682', 'Amarelo', '512GB', 'novo', NULL, 'disponivel');

-- ---------------------------------------------------------------------------
-- Dados de exemplo: compradores
-- ---------------------------------------------------------------------------
INSERT INTO compradores (nome_completo, cpf, rg, telefone, telefone2, email, endereco, cidade, estado, cep) VALUES
('Carlos Eduardo Silva', '123.456.789-01', '12.345.678-9', '(11) 98765-4321', NULL, 'carlos.silva@email.com', 'Rua das Flores, 123', 'São Paulo', 'SP', '01310-100'),
('Ana Paula Oliveira', '234.567.890-12', '23.456.789-0', '(21) 97654-3210', '(21) 3456-7890', 'ana.oliveira@email.com', 'Av. Atlântica, 456', 'Rio de Janeiro', 'RJ', '22010-000'),
('Roberto Mendes', '345.678.901-23', NULL, '(31) 96543-2109', NULL, 'roberto.m@email.com', 'Rua Bahia, 789', 'Belo Horizonte', 'MG', '30130-000'),
('Fernanda Costa', '456.789.012-34', '45.678.901-2', '(41) 95432-1098', NULL, 'fernanda.costa@email.com', 'Rua XV de Novembro, 100', 'Curitiba', 'PR', '80020-310'),
('Lucas Almeida', '567.890.123-45', '56.789.012-3', '(51) 94321-0987', '(51) 3333-4444', 'lucas.almeida@email.com', 'Av. Borges de Medeiros, 500', 'Porto Alegre', 'RS', '90020-025');

-- ---------------------------------------------------------------------------
-- Dados de exemplo: vendas
-- ---------------------------------------------------------------------------
INSERT INTO vendas (celular_id, comprador_id, data_compra, valor_compra, data_venda, valor_venda, forma_pagamento, parcelas, observacoes) VALUES
(1, 1, '2025-01-10', 2800.00, '2025-01-25', 3499.00, 'pix', NULL, 'Cliente pagou à vista via PIX'),
(2, 2, '2025-02-05', 3200.00, '2025-02-18', 3999.00, 'cartao_credito', 10, 'Parcelado em 10x sem juros'),
(6, 3, '2025-03-01', 900.00, '2025-03-15', 1199.00, 'dinheiro', NULL, NULL),
(1, 4, '2024-11-20', 2500.00, '2024-12-05', 2999.00, 'transferencia', NULL, 'Venda antiga de referência'),
(2, 5, '2025-04-01', 3100.00, '2025-04-10', 3799.00, 'parcelado', 6, 'Entrada + 5 parcelas');

-- ---------------------------------------------------------------------------
-- Dados de exemplo: documentos (arquivos fictícios para referência)
-- ---------------------------------------------------------------------------
INSERT INTO documentos (venda_id, nome_original, nome_arquivo, tipo_arquivo, tamanho_bytes, descricao) VALUES
(1, 'nota_fiscal_s23.pdf', 'doc_674a1b2c3d4e5.pdf', 'application/pdf', 245760, 'Nota fiscal de compra'),
(1, 'rg_comprador.jpg', 'doc_674a1b2c3d4e6.jpg', 'image/jpeg', 189440, 'RG do comprador'),
(2, 'contrato_venda.pdf', 'doc_674a1b2c3d4e7.pdf', 'application/pdf', 312000, 'Contrato de venda'),
(3, 'comprovante_pix.png', 'doc_674a1b2c3d4e8.png', 'image/png', 98432, 'Comprovante PIX'),
(4, 'termo_garantia.docx', 'doc_674a1b2c3d4e9.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 45678, 'Termo de garantia');
