<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lancamentos_repo.php';
exigir_login();

$uid = usuario_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    if (($_POST['acao'] ?? '') === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $tipo = tipo_valido($_POST['tipo'] ?? '');
        $cor  = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['cor'] ?? '') ? $_POST['cor'] : '#8b5cf6';

        if ($nome === '' || mb_strlen($nome) > 60) {
            flash('erro', 'Informe um nome de até 60 caracteres.');
        } else {
            try {
                db()->prepare('INSERT INTO categorias (usuario_id, nome, tipo, cor) VALUES (?, ?, ?, ?)')
                    ->execute([$uid, $nome, $tipo, $cor]);
                flash('sucesso', 'Categoria “' . $nome . '” criada.');
            } catch (PDOException $e) {
                flash('erro', 'Você já tem uma categoria com esse nome.');   // chave única no banco
            }
        }
    }

    if (($_POST['acao'] ?? '') === 'excluir') {
        // Os lançamentos da categoria ficam "Sem categoria" (ON DELETE SET NULL)
        db()->prepare('DELETE FROM categorias WHERE id = ? AND usuario_id = ?')
            ->execute([(int) ($_POST['id'] ?? 0), $uid]);
        flash('sucesso', 'Categoria excluída.');
    }
    redirecionar('categorias.php');
}

// Categorias com a quantidade de lançamentos de cada uma
$stmt = db()->prepare(
    'SELECT c.*, COUNT(l.id) AS uso
       FROM categorias c LEFT JOIN lancamentos l ON l.categoria_id = c.id
      WHERE c.usuario_id = ?
      GROUP BY c.id ORDER BY c.tipo, c.nome'
);
$stmt->execute([$uid]);
$lista = ['pagar' => [], 'receber' => []];
foreach ($stmt as $c) {
    $lista[$c['tipo']][] = $c;
}

$titulo = 'Categorias';
$pagina = 'categorias';
require __DIR__ . '/includes/cabecalho.php';
?>

<section class="cartao">
  <h2>Nova categoria</h2>
  <form method="post" class="formulario linha-form">
    <?= campo_csrf() ?>
    <input type="hidden" name="acao" value="criar">
    <label class="cresce">Nome
      <input type="text" name="nome" required maxlength="60" placeholder="Ex.: Marketing">
    </label>
    <label>Tipo
      <select name="tipo">
        <option value="pagar">Despesa (a pagar)</option>
        <option value="receber">Receita (a receber)</option>
      </select>
    </label>
    <label>Cor
      <input type="color" name="cor" value="#8b5cf6">
    </label>
    <button type="submit" class="botao botao-principal">Adicionar</button>
  </form>
</section>

<div class="relatorio-grade">
  <?php foreach (['pagar' => 'Despesas', 'receber' => 'Receitas'] as $tipo => $rotulo): ?>
    <section class="cartao">
      <h2><?= $rotulo ?></h2>
      <?php if (!$lista[$tipo]): ?>
        <p class="vazio-mini">Nenhuma categoria.</p>
      <?php else: ?>
        <ul class="lista-categorias">
          <?php foreach ($lista[$tipo] as $c): ?>
            <li>
              <span class="etiqueta" style="--cor: <?= e($c['cor']) ?>"><?= e($c['nome']) ?></span>
              <small><?= $c['uso'] ?> lançamento(s)</small>
              <form method="post">
                <?= campo_csrf() ?>
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button class="botao-icone vermelho" title="Excluir"
                        data-confirmar="Excluir a categoria “<?= e($c['nome']) ?>”? Os lançamentos dela ficarão sem categoria.">🗑</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
