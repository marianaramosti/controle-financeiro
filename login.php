<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_atual()) {
    redirecionar('painel.php');
}

$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (fazer_login($email, $senha)) {
        if (eh_usuario_demo()) {
            preparar_demo_se_vazio(usuario_id());   // conta demo sempre com dados de exemplo
        }
        flash('sucesso', 'Bem-vinda(o), ' . usuario_atual()['nome'] . '!');
        redirecionar('painel.php');
    }
    flash('erro', 'E-mail ou senha incorretos.');
    redirecionar('login.php');   // redireciona para não reenviar o formulário ao atualizar
}

$titulo = 'Entrar';
ob_start();
?>
<form method="post" class="formulario" novalidate>
  <?= campo_csrf() ?>
  <label>E-mail
    <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email" autofocus>
  </label>
  <label>Senha
    <input type="password" name="senha" required autocomplete="current-password">
  </label>
  <button type="submit" class="botao botao-principal largo">Entrar</button>
</form>

<div class="demo-aviso">
  <strong>Quer só testar?</strong> Use a conta de demonstração:<br>
  <code><?= e($config['demo_email']) ?></code> · senha <code>demo123</code>
  <button type="button" class="botao-link" id="preencherDemo"
          data-email="<?= e($config['demo_email']) ?>">Preencher automaticamente</button>
</div>

<p class="acesso-rodape">Não tem conta? <a href="cadastro.php">Criar conta</a></p>
<?php
$conteudoFormulario = ob_get_clean();
require __DIR__ . '/includes/tela_acesso.php';
