<?php
// =========================================================
// CONFIGURAÇÃO
// Os valores abaixo funcionam no XAMPP (padrão).
// Para a hospedagem online, NÃO altere este arquivo:
// crie "config.local.php" nesta mesma pasta (ele não vai
// para o GitHub, veja o .gitignore) com os dados do servidor.
// =========================================================

$config = [
    'db_host'    => 'localhost',
    'db_nome'    => 'controle_financeiro',
    'db_usuario' => 'root',
    'db_senha'   => '',
    'app_nome'   => 'Controle Financeiro',
    'fuso'       => 'America/Fortaleza',
    'demo_email' => 'demo@controlefinanceiro.com',   // usado para exibir o aviso de login demo
];

// Sobrescreve com as configurações locais, se existirem
if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

date_default_timezone_set($config['fuso']);
