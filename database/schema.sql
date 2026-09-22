-- =========================================================
-- CONTROLE FINANCEIRO — estrutura do banco de dados (MySQL/MariaDB)
-- Execute este arquivo primeiro (phpMyAdmin → Importar).
-- =========================================================

SET NAMES utf8mb4;

-- Usuários do sistema
CREATE TABLE IF NOT EXISTS usuarios (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  senha_hash  VARCHAR(255) NOT NULL,          -- senha criptografada (password_hash)
  criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorias (cada usuário tem as suas)
CREATE TABLE IF NOT EXISTS categorias (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NOT NULL,
  nome        VARCHAR(60) NOT NULL,
  tipo        ENUM('pagar', 'receber') NOT NULL,
  cor         CHAR(7) NOT NULL DEFAULT '#8b5cf6',
  UNIQUE KEY uk_categoria (usuario_id, nome, tipo),
  CONSTRAINT fk_categoria_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lançamentos: contas a pagar e valores a receber na mesma tabela
CREATE TABLE IF NOT EXISTS lancamentos (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id     INT UNSIGNED NOT NULL,
  tipo           ENUM('pagar', 'receber') NOT NULL,
  descricao      VARCHAR(150) NOT NULL,
  pessoa         VARCHAR(120) NULL,            -- fornecedor (pagar) ou cliente (receber)
  categoria_id   INT UNSIGNED NULL,
  valor          DECIMAL(12, 2) NOT NULL,
  vencimento     DATE NOT NULL,
  data_quitacao  DATE NULL,                    -- preenchida quando pago/recebido
  observacao     TEXT NULL,
  criado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_usuario_vencimento (usuario_id, vencimento),
  INDEX idx_usuario_tipo (usuario_id, tipo),
  CONSTRAINT fk_lancamento_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_lancamento_categoria FOREIGN KEY (categoria_id)
    REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
