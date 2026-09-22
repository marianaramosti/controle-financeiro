<?php
// =========================================================
// CONEXÃO COM O BANCO (PDO)
// PDO + consultas preparadas protegem contra SQL Injection.
// =========================================================

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;          // cria a conexão uma única vez por requisição
    global $config;

    if ($pdo === null) {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_nome']};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $config['db_usuario'], $config['db_senha'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            // Mensagem amigável; o detalhe técnico fica só no log do servidor
            error_log('Erro de conexão: ' . $e->getMessage());
            exit('Não foi possível conectar ao banco de dados. Verifique o arquivo includes/config.php.');
        }
    }
    return $pdo;
}
