<?php
// Cabeçalho + menu lateral das páginas internas.
// Antes de incluir, defina $titulo (texto da aba) e $pagina (item ativo do menu).
$usuario = usuario_atual();
$itens = [
    'painel'   => ['painel.php', '▦', 'Painel'],
    'pagar'    => ['lancamentos.php?tipo=pagar', '↑', 'Contas a pagar'],
    'receber'  => ['lancamentos.php?tipo=receber', '↓', 'A receber'],
    'relatorio'=> ['relatorio.php', '▤', 'Relatório mensal'],
    'categorias'=> ['categorias.php', '◉', 'Categorias'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo ?? 'Painel') ?> | <?= e($config['app_nome']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app">

  <aside class="lateral" id="lateral">
    <a href="painel.php" class="marca"><span class="marca-icone">R$</span> Controle<strong>Financeiro</strong></a>

    <nav class="nav-lateral">
      <?php foreach ($itens as $chave => [$link, $icone, $texto]): ?>
        <a href="<?= e($link) ?>" class="<?= ($pagina ?? '') === $chave ? 'ativo' : '' ?>">
          <span class="nav-icone"><?= $icone ?></span><?= e($texto) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="lateral-usuario">
      <span class="avatar-mini"><?= e(mb_strtoupper(mb_substr($usuario['nome'], 0, 1))) ?></span>
      <div>
        <strong><?= e($usuario['nome']) ?></strong>
        <a href="logout.php">Sair</a>
      </div>
    </div>
  </aside>

  <div class="conteudo">
    <header class="barra-topo">
      <button class="botao-menu" id="botaoMenu" aria-label="Abrir menu" aria-expanded="false">☰</button>
      <h1><?= e($titulo ?? '') ?></h1>
      <?php if (!empty($acaoTopo)) echo $acaoTopo; ?>
    </header>

    <main class="pagina">
      <?php if (function_exists('eh_usuario_demo') && eh_usuario_demo()): ?>
        <div class="faixa-demo">
          <span>👋 Você está na <strong>conta de demonstração</strong>: os dados são fictícios e podem ser alterados à vontade.</span>
          <form method="post" action="demo_restaurar.php">
            <?= campo_csrf() ?>
            <button class="botao-link" data-confirmar="Apagar as alterações e restaurar os dados de exemplo?">Restaurar dados de exemplo</button>
          </form>
        </div>
      <?php endif; ?>
      <?= mostrar_flash() ?>
