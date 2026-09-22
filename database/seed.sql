-- =========================================================
-- USUÁRIO DE DEMONSTRAÇÃO (opcional)
--   E-mail: demo@controlefinanceiro.com
--   Senha:  demo123
-- Execute depois do schema.sql.
-- Os lançamentos de exemplo são criados automaticamente no
-- primeiro login do usuário demo (veja includes/demo.php).
-- =========================================================

SET NAMES utf8mb4;

INSERT INTO usuarios (nome, email, senha_hash) VALUES
('Usuário Demo', 'demo@controlefinanceiro.com',
 '$2y$12$7M/WQJw1S9p03CsDu/il1.1CH6nXHzZhtT7G5MRgmsldn7MsWllFq');
