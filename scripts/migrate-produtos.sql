-- Tabela de produtos (acessórios e itens de estoque)
-- Execute em instalações existentes:
-- mysql -u root -p enzo_tech < scripts/migrate-produtos.sql

USE enzo_tech;

CREATE TABLE IF NOT EXISTS produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  marca VARCHAR(100) NULL,
  categoria VARCHAR(80) NULL,
  sku VARCHAR(50) NULL,
  descricao TEXT NULL,
  preco_compra DECIMAL(10,2) NULL,
  preco_venda DECIMAL(10,2) NULL,
  quantidade INT NOT NULL DEFAULT 0,
  status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  observacoes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE INDEX idx_produtos_sku (sku),
  INDEX idx_produtos_status (status),
  INDEX idx_produtos_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
