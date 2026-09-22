<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lancamentos_repo.php';
exigir_login();

$uid = usuario_id();
$mes = mes_valido($_GET['mes'] ?? null);

$pagar   = listar_lancamentos($uid, 'pagar', ['mes' => $mes]);
$receber = listar_lancamentos($uid, 'receber', ['mes' => $mes]);

// ---------- Exportação CSV (abre direto no Excel) ----------
if (($_GET['exportar'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio-' . $mes . '.csv"');
    $saida = fopen('php://output', 'w');
    fwrite($saida, "\xEF\xBB\xBF");   // BOM: faz o Excel reconhecer acentos
    fputcsv($saida, ['Tipo', 'Descrição', 'Fornecedor/Cliente', 'Categoria', 'Vencimento', 'Valor', 'Situação', 'Data de quitação'], ';');
    foreach (array_merge($pagar, $receber) as $l) {
        $sit = situacao($l);
        fputcsv($saida, [
            $l['tipo'] === 'pagar' ? 'A pagar' : 'A receber',
            $l['descricao'],
            $l['pessoa'],
            $l['categoria'] ?? '',
            data_br($l['vencimento']),
            number_format((float) $l['valor'], 2, ',', ''),
            rotulo_situacao($sit, $l['tipo']),
            $l['data_quitacao'] ? data_br($l['data_quitacao']) : '',
        ], ';');
    }
    fclose($saida);
    exit;
}

// ---------- Resumo por categoria ----------
function resumo_por_categoria(array $lancamentos): array
{
    $grupos = [];
    foreach ($lancamentos as $l) {
        $nome = $l['categoria'] ?? 'Sem categoria';
        $grupos[$nome] = $grupos[$nome] ?? ['cor' => $l['categoria_cor'] ?? '#64748b', 'qtd' => 0, 'total' => 0, 'quitado' => 0];
        $grupos[$nome]['qtd']++;
        $grupos[$nome]['total'] += $l['valor'];
        if ($l['data_quitacao']) {
            $grupos[$nome]['quitado'] += $l['valor'];
        }
    }
    uasort($grupos, function ($a, $b) { return $b['total'] <=> $a['total']; });
    return $grupos;
}

$totPagar   = totais($pagar);
$totReceber = totais($receber);
$gruposPagar   = resumo_por_categoria($pagar);
$gruposReceber = resumo_por_categoria($receber);

$titulo = 'Relatório mensal';
$pagina = 'relatorio';
$acaoTopo = '<form method="get" class="seletor-relatorio">
  <input type="month" name="mes" value="' . e($mes) . '" onchange="this.form.submit()">
  <a href="?mes=' . e($mes) . '&exportar=csv" class="botao botao-contorno">⬇ Exportar CSV</a>
  <button type="button" class="botao botao-contorno" onclick="window.print()">🖨 Imprimir</button>
</form>';
require __DIR__ . '/includes/cabecalho.php';
?>

<p class="relatorio-titulo">Período: <strong><?= e(mes_extenso($mes)) ?></strong></p>

<section class="cards-resumo tres">
  <article class="card-resumo">
    <span class="card-rotulo">Total de despesas</span>
    <strong><?= moeda($totPagar['total']) ?></strong>
    <small>Pago: <?= moeda($totPagar['quitado']) ?> · Em aberto: <?= moeda($totPagar['pendente'] + $totPagar['atrasado']) ?></small>
  </article>
  <article class="card-resumo">
    <span class="card-rotulo">Total de receitas</span>
    <strong><?= moeda($totReceber['total']) ?></strong>
    <small>Recebido: <?= moeda($totReceber['quitado']) ?> · Em aberto: <?= moeda($totReceber['pendente'] + $totReceber['atrasado']) ?></small>
  </article>
  <article class="card-resumo destaque">
    <span class="card-rotulo">Resultado do mês</span>
    <?php $resultado = $totReceber['total'] - $totPagar['total']; ?>
    <strong class="<?= $resultado < 0 ? 'negativo' : 'positivo' ?>"><?= moeda($resultado) ?></strong>
    <small>Receitas − despesas previstas</small>
  </article>
</section>

<div class="relatorio-grade">
  <?php foreach ([['Despesas por categoria', $gruposPagar, $totPagar['total'], 'Pago'], ['Receitas por categoria', $gruposReceber, $totReceber['total'], 'Recebido']] as [$tituloBloco, $grupos, $totalBloco, $rotuloQuitado]): ?>
    <section class="cartao">
      <h2><?= $tituloBloco ?></h2>
      <?php if (!$grupos): ?>
        <p class="vazio-mini">Sem lançamentos neste mês.</p>
      <?php else: ?>
        <table class="tabela compacta">
          <thead><tr><th>Categoria</th><th class="num">Qtd.</th><th class="num">Total</th><th class="num"><?= $rotuloQuitado ?></th><th class="num">%</th></tr></thead>
          <tbody>
            <?php foreach ($grupos as $nome => $g): ?>
              <tr>
                <td><span class="etiqueta" style="--cor: <?= e($g['cor']) ?>"><?= e($nome) ?></span></td>
                <td class="num"><?= $g['qtd'] ?></td>
                <td class="num"><?= moeda($g['total']) ?></td>
                <td class="num"><?= moeda($g['quitado']) ?></td>
                <td class="num"><?= $totalBloco ? number_format($g['total'] / $totalBloco * 100, 1, ',', '') : '0' ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th>Total</th><th></th><th class="num"><?= moeda($totalBloco) ?></th><th></th><th class="num">100%</th></tr></tfoot>
        </table>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>

<section class="cartao">
  <h2>Todos os lançamentos do mês</h2>
  <?php $todos = array_merge($pagar, $receber); usort($todos, function ($a, $b) { return strcmp($a['vencimento'], $b['vencimento']); }); ?>
  <?php if (!$todos): ?>
    <p class="vazio-mini">Nenhum lançamento neste mês.</p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela compacta">
        <thead><tr><th>Vencimento</th><th>Tipo</th><th>Descrição</th><th>Categoria</th><th class="num">Valor</th><th>Situação</th></tr></thead>
        <tbody>
          <?php foreach ($todos as $l): $sit = situacao($l); ?>
            <tr>
              <td data-rotulo="Vencimento"><?= data_br($l['vencimento']) ?></td>
              <td data-rotulo="Tipo"><span class="tipo-bolinha <?= $l['tipo'] ?>"></span> <?= $l['tipo'] === 'pagar' ? 'A pagar' : 'A receber' ?></td>
              <td data-rotulo="Descrição"><?= e($l['descricao']) ?></td>
              <td data-rotulo="Categoria"><?= e($l['categoria'] ?? '—') ?></td>
              <td data-rotulo="Valor" class="num <?= $l['tipo'] ?>"><?= $l['tipo'] === 'pagar' ? '−' : '+' ?> <?= moeda($l['valor']) ?></td>
              <td data-rotulo="Situação"><span class="status status-<?= $sit ?>"><?= rotulo_situacao($sit, $l['tipo']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/rodape.php'; ?>
