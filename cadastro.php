<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_atual()) {
    redirecionar('painel.php');
}

$dados = ['nome' => '', 'email' => ''];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $dados['nome']  = trim($_POST['nome'] ?? '');
    $dados['email'] = trim($_POST['email'] ?? '');
    $senha          = $_POST['senha'] ?? '';
    $confirmacao    = $_POST['confirmacao'] ?? '';

    // Validações
    if (mb_strlen($dados['nome']) < 2) {
        $erros[] = 'Informe seu nome.';
    }
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (strlen($senha) < 6) {
        $erros[] = 'A senha precisa ter pelo menos 6 caracteres.';
    }
    if ($senha !== $confirmacao) {
        $erros[] = 'As senhas não conferem.';
    }

    if (!$erros) {
        $existe = db()->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $existe->execute([$dados['email']]);
        if ($existe->fetch()) {
            $erros[] = 'Este e-mail já está cadastrado.';
        }
    }

    if (!$erros) {
        // Transação: cria o usuário e as categorias padrão juntos (tudo ou nada)
        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
        $stmt->execute([$dados['nome'], $dados['email'], password_hash($senha, PASSWORD_DEFAULT)]);
        criar_categorias_padrao((int) $pdo->lastInsertId());
        $pdo->commit();

        fazer_login($dados['email'], $senha);
        flash('sucesso', 'Conta criada! Comece cadastrando sua primeira conta a pagar.');
        redirecionar('painel.php');
    }
}

$titulo = 'Criar conta';
ob_start();
?>
<?php foreach ($erros as $erro): ?>
  <div class="alerta alerta-erro"><?= e($erro) ?></div>
<?php endforeach; ?>

<form method="post" class="formulario" novalidate>
  <?= campo_csrf() ?>
  <label>Nome
    <input type="text" name="nome" value="<?= e($dados['nome']) ?>" required maxlength="100" autofocus>
  </label>
  <label>E-mail
    <input type="email" name="email" value="<?= e($dados['email']) ?>" required maxlength="150" autocomplete="email">
  </label>
  <div class="linha-2">
    <label>Senha
      <input type="password" name="senha" required minlength="6" autocomplete="new-password">
    </label>
    <label>Confirmar senha
      <input type="password" name="confirmacao" required minlength="6" autocomplete="new-password">
    </label>
  </div>
  <button type="submit" class="botao botao-principal largo">Criar conta</button>
</form>
<p class="acesso-rodape">Já tem conta? <a href="login.php">Entrar</a></p>
<?php
$conteudoFormulario = ob_get_clean();
require __DIR__ . '/includes/tela_acesso.php';
