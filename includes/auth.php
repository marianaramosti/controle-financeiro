<?php
// =========================================================
// AUTENTICAÇÃO (sessão, login, logout e proteção de páginas)
// =========================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/demo.php';

// Cookie de sessão mais seguro
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,                                     // JavaScript não lê o cookie
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/** Usuário logado (ou null). */
function usuario_atual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function usuario_id(): int
{
    return (int) ($_SESSION['usuario']['id'] ?? 0);
}

/** Coloque no topo das páginas que exigem login. */
function exigir_login(): void
{
    if (!usuario_atual()) {
        flash('aviso', 'Faça login para continuar.');
        redirecionar('login.php');
    }
}

/** Tenta logar; retorna true se e-mail e senha conferem. */
function fazer_login(string $email, string $senha): bool
{
    $stmt = db()->prepare('SELECT id, nome, email, senha_hash FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }

    session_regenerate_id(true);   // evita "fixação de sessão"
    $_SESSION['usuario'] = [
        'id'    => (int) $usuario['id'],
        'nome'  => $usuario['nome'],
        'email' => $usuario['email'],
    ];
    return true;
}

function fazer_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Categorias padrão criadas para cada novo usuário. */
function criar_categorias_padrao(int $usuarioId): void
{
    $padrao = [
        ['Aluguel', 'pagar', '#8b5cf6'],
        ['Fornecedores', 'pagar', '#22d3ee'],
        ['Energia e água', 'pagar', '#f59e0b'],
        ['Internet e telefone', 'pagar', '#34d399'],
        ['Impostos', 'pagar', '#f472b6'],
        ['Outras despesas', 'pagar', '#94a3b8'],
        ['Vendas e serviços', 'receber', '#34d399'],
        ['Outros recebimentos', 'receber', '#a78bfa'],
    ];
    $stmt = db()->prepare('INSERT INTO categorias (usuario_id, nome, tipo, cor) VALUES (?, ?, ?, ?)');
    foreach ($padrao as [$nome, $tipo, $cor]) {
        $stmt->execute([$usuarioId, $nome, $tipo, $cor]);
    }
}
