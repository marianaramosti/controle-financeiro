<?php
// Restaura os dados fictícios da conta de demonstração.
require_once __DIR__ . '/includes/auth.php';
exigir_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && eh_usuario_demo()) {
    validar_csrf();
    recriar_dados_demo(usuario_id());
    flash('sucesso', 'Dados de exemplo restaurados.');
}
redirecionar('painel.php');
