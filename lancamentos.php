<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lancamentos_repo.php';
exigir_login();

$tipo = tipo_valido($_GET['tipo'] ?? 'pagar');
$ehPagar = $tipo === 'pagar';

$filtros = [
    'mes'       => mes_valido($_GET['mes'] ?? null),
    'situacao'  => in_array($_GET['situacao'] ?? '', ['pendente', 'atrasado', 'quitado'], true) ? $_GET['situacao'] : '',
    'categoria' => (int) ($_GET['categoria'] ?? 0),
    'busca'     => trim($_GET['busca'] ?? ''),
];
if (($_GET['mes'] ?? '') === 'todos') {
    $filtros['mes'] = '';        // permite ver todos os meses
}

$lancamentos = listar_lancamentos(usuario_id(), $tipo, $filtros);
$categorias  = buscar_categorias(usuario_id(), $tipo);
$soma        = totais($lancamentos);

$titulo   = $ehPagar ? 'Contas a pagar' : 'Valores a receber';
$pagina   = $tipo;
$acaoTopo = '<a href="lancamento_form.php?tipo=' . $tipo . '" class="botao botao-principal">+ Nova ' .
            ($ehPagar ? 'conta' : 'cobrança') . '</a>';
require __DIR__ . '/includes/cabecalho.php';
?>

<!-- Resumo do filtro atual -->
<section class="resumo-lista">
  <div class="mini-card"><span>Total</span><strong><?= moeda($soma['total']) ?></strong></div>
  <div class="mini-card verde"><span><?= $ehPagar ? 'Pago' : 'Recebido' ?></span><strong><?= moeda($soma['quitado']) ?></strong></div>
  <div class="mini-card amarelo"><span>Pendente</span><strong><?= moeda($soma['pendente']) ?></strong></div>
  <div class="mini-card vermelho"><span>Atrasado</span><strong><?= moeda($soma['atrasado']) ?></strong></div>
</section>

<!-- Filtros (enviados por GET, então o link pode ser salvo/compartilhado) -->
<form class="filtros" method="get">
  <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
  <label>Mês
    <input type="month" name="mes" value="<?= e($filtros['mes']) ?>">
  </label>
  <label>Situação
    <select name="situacao">
      <option value="">Todas</option>
      <option value="pendente" <?= $filtros['situacao'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
      <option value="atrasado" <?= $filtros['situacao'] === 'atrasado' ? 'selected' : '' ?>>Atrasado</option>
      <option value="quitado"  <?= $filtros['situacao'] === 'quitado' ? 'selected' : '' ?>><?= $ehPagar ? 'Pago' : 'Recebido' ?></option>
    </select>
  </label>
  <label>Categoria
    <select name="categoria">
      <option value="">Todas</option>
      <?php foreach ($categorias as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $filtros['categoria'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="cresce">Buscar
    <input type="search" name="busca" value="<?= e($filtros['busca']) ?>" placeholder="Descrição ou <?= $ehPagar ? 'fornecedor' : 'cliente' ?>">
  </label>
  <div class="filtros-botoes">
    <button type="submit" class="botao botao-principal">Filtrar</button>
    <a href="lancamentos.php?tipo=<?= $tipo ?>&mes=todos" class="botao botao-contorno">Todos os meses</a>
  </div>
</form>

<?php if (!$lancamentos): ?>
  <div class="vazio">
    <p>Nenhum lançamento encontrado com esses filtros.</p>
    <a href="lancamento_form.php?tipo=<?= $tipo ?>" class="botao botao-principal">Cadastrar o primeiro</a>
  </div>
<?php else: ?>
  <div class="tabela-rolagem">
    <table class="tabela">
      <thead>
        <tr>
          <th>Vencimento</th>
          <th>Descrição</th>
          <th><?= $ehPagar ? 'Fornecedor' : 'Cliente' ?></th>
          <th>Categoria</th>
          <th class="num">Valor</th>
          <th>Situação</th>
          <th class="acoes-col">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lancamentos as $l): $sit = situacao($l); ?>
          <tr class="linha-<?= $sit ?>">
            <td data-rotulo="Vencimento"><?= data_br($l['vencimento']) ?></td>
            <td data-rotulo="Descrição" class="quebra"><strong><?= e($l['descricao']) ?></strong></td>
            <td data-rotulo="<?= $ehPagar ? 'Fornecedor' : 'Cliente' ?>" class="quebra"><?= e($l['pessoa'] ?: '—') ?></td>
            <td data-rotulo="Categoria">
              <?php if ($l['categoria']): ?>
                <span class="etiqueta" style="--cor: <?= e($l['categoria_cor']) ?>"><?= e($l['categoria']) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td data-rotulo="Valor" class="num"><?= moeda($l['valor']) ?></td>
            <td data-rotulo="Situação">
              <span class="status status-<?= $sit ?>"><?= rotulo_situacao($sit, $tipo) ?></span>
              <?php if ($sit === 'quitado'): ?><small class="sub"><?= data_br($l['data_quitacao']) ?></small><?php endif; ?>
            </td>
            <td class="acoes">
              <form method="post" action="lancamento_acao.php">
                <?= campo_csrf() ?>
                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                <?php if ($sit === 'quitado'): ?>
                  <button name="acao" value="reabrir" class="botao-icone" title="Desfazer <?= $ehPagar ? 'pagamento' : 'recebimento' ?>">↺</button>
                <?php else: ?>
                  <button name="acao" value="quitar" class="botao-icone verde" title="Marcar como <?= $ehPagar ? 'pago' : 'recebido' ?>">✓</button>
                <?php endif; ?>
                <a href="lancamento_form.php?id=<?= $l['id'] ?>" class="botao-icone" title="Editar">✎</a>
                <button name="acao" value="excluir" class="botao-icone vermelho" title="Excluir"
                        data-confirmar="Excluir “<?= e($l['descricao']) ?>”? Esta ação não pode ser desfeita.">🗑</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="contagem"><?= count($lancamentos) ?> lançamento(s)<?= $filtros['mes'] ? ' em ' . e(mb_strtolower(mes_extenso($filtros['mes']))) : '' ?></p>
<?php endif; ?>

<?php require __DIR__ . '/includes/rodape.php'; ?>
