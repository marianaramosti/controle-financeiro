<?php
// Layout compartilhado pelas telas de login e cadastro.
// Antes de incluir, defina $titulo e $conteudoFormulario (HTML do formulário).
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> | <?= e($config['app_nome']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="acesso">
  <div class="acesso-grade">
    <section class="acesso-apresentacao">
      <span class="marca-icone grande">R$</span>
      <h1>Controle <span class="gradiente">Financeiro</span></h1>
      <p>Organize contas a pagar e valores a receber, acompanhe vencimentos e veja para onde vai o dinheiro de cada mês.</p>
      <ul class="acesso-recursos">
        <li>✓ Contas a pagar e a receber com status automático</li>
        <li>✓ Painel com totais, alertas de vencimento e gráficos</li>
        <li>✓ Relatório mensal com exportação para Excel (CSV)</li>
      </ul>
    </section>

    <section class="acesso-cartao">
      <h2><?= e($titulo) ?></h2>
      <?= mostrar_flash() ?>
      <?= $conteudoFormulario ?>
    </section>
  </div>
  <script src="assets/js/app.js"></script>
</body>
</html>
