<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lancamentos_repo.php';
exigir_login();

$uid = usuario_id();
$mes = mes_valido($_GET['mes'] ?? null);
$hoje = date('Y-m-d');

// ---------- Totais do mês escolhido ----------
$stmt = db()->prepare(
    'SELECT tipo,
            SUM(valor) AS total,
            SUM(CASE WHEN data_quitacao IS NOT NULL THEN valor ELSE 0 END) AS quitado
       FROM lancamentos
      WHERE usuario_id = ? AND DATE_FORMAT(vencimento, "%Y-%m") = ?
      GROUP BY tipo'
);
$stmt->execute([$uid, $mes]);
$mesTotais = ['pagar' => ['total' => 0, 'quitado' => 0], 'receber' => ['total' => 0, 'quitado' => 0]];
foreach ($stmt as $linha) {
    $mesTotais[$linha['tipo']] = ['total' => (float) $linha['total'], 'quitado' => (float) $linha['quitado']];
}
$saldoPrevisto  = $mesTotais['receber']['total'] - $mesTotais['pagar']['total'];
$saldoRealizado = $mesTotais['receber']['quitado'] - $mesTotais['pagar']['quitado'];

// ---------- Atrasados (qualquer mês) ----------
$stmt = db()->prepare(
    'SELECT tipo, COUNT(*) AS qtd, SUM(valor) AS total
       FROM lancamentos
      WHERE usuario_id = ? AND data_quitacao IS NULL AND vencimento < ?
      GROUP BY tipo'
);
$stmt->execute([$uid, $hoje]);
$atrasados = ['pagar' => ['qtd' => 0, 'total' => 0], 'receber' => ['qtd' => 0, 'total' => 0]];
foreach ($stmt as $linha) {
    $atrasados[$linha['tipo']] = ['qtd' => (int) $linha['qtd'], 'total' => (float) $linha['total']];
}

// ---------- Próximos vencimentos (hoje + 7 dias) ----------
$stmt = db()->prepare(
    'SELECT l.*, c.nome AS categoria
       FROM lancamentos l LEFT JOIN categorias c ON c.id = l.categoria_id
      WHERE l.usuario_id = ? AND l.data_quitacao IS NULL
        AND l.vencimento BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY)
      ORDER BY l.vencimento LIMIT 8'
);
$stmt->execute([$uid, $hoje, $hoje]);
$proximos = $stmt->fetchAll();

// ---------- Gráfico 1: despesas do mês por categoria ----------
$stmt = db()->prepare(
    'SELECT COALESCE(c.nome, "Sem categoria") AS nome, COALESCE(c.cor, "#64748b") AS cor, SUM(l.valor) AS total
       FROM lancamentos l LEFT JOIN categorias c ON c.id = l.categoria_id
      WHERE l.usuario_id = ? AND l.tipo = "pagar" AND DATE_FORMAT(l.vencimento, "%Y-%m") = ?
      GROUP BY nome, cor ORDER BY total DESC'
);
$stmt->execute([$uid, $mes]);
$porCategoria = $stmt->fetchAll();

// ---------- Gráfico 2: últimos 6 meses ----------
$inicio = (new DateTime($mes . '-01'))->modify('-5 months')->format('Y-m-01');
$fim    = (new DateTime($mes . '-01'))->modify('last day of this month')->format('Y-m-d');
$stmt = db()->prepare(
    'SELECT DATE_FORMAT(vencimento, "%Y-%m") AS mes, tipo, SUM(valor) AS total
       FROM lancamentos
      WHERE usuario_id = ? AND vencimento BETWEEN ? AND ?
      GROUP BY mes, tipo'
);
$stmt->execute([$uid, $inicio, $fim]);
$evolucao = [];
$cursor = new DateTime($inicio);
for ($i = 0; $i < 6; $i++) {
    $evolucao[$cursor->format('Y-m')] = ['pagar' => 0, 'receber' => 0];
    $cursor->modify('+1 month');
}
foreach ($stmt as $linha) {
    $evolucao[$linha['mes']][$linha['tipo']] = (float) $linha['total'];
}
$abrev = ['', 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

$dadosGraficos = [
    'categorias' => [
        'rotulos' => array_column($porCategoria, 'nome'),
        'valores' => array_map('floatval', array_column($porCategoria, 'total')),
        'cores'   => array_column($porCategoria, 'cor'),
    ],
    'evolucao' => [
        'rotulos' => array_map(function ($m) use ($abrev) {
            return $abrev[(int) substr($m, 5, 2)] . '/' . substr($m, 2, 2);
        }, array_keys($evolucao)),
        'pagar'   => array_column($evolucao, 'pagar'),
        'receber' => array_column($evolucao, 'receber'),
    ],
];

$mesAnterior = (new DateTime($mes . '-01'))->modify('-1 month')->format('Y-m');
$mesSeguinte = (new DateTime($mes . '-01'))->modify('+1 month')->format('Y-m');

$titulo = 'Painel';
$pagina = 'painel';
$usarGraficos = true;
$acaoTopo = '<div class="seletor-mes">
  <a href="?mes=' . $mesAnterior . '" class="botao-icone" title="Mês anterior">‹</a>
  <span>' . e(mes_extenso($mes)) . '</span>
  <a href="?mes=' . $mesSeguinte . '" class="botao-icone" title="Próximo mês">›</a>
</div>';
require __DIR__ . '/includes/cabecalho.php';
?>

<!-- Cartões de resumo -->
<section class="cards-resumo">
  <article class="card-resumo">
    <span class="card-rotulo">A pagar no mês</span>
    <strong><?= moeda($mesTotais['pagar']['total']) ?></strong>
    <div class="barra"><span style="width: <?= $mesTotais['pagar']['total'] ? round($mesTotais['pagar']['quitado'] / $mesTotais['pagar']['total'] * 100) : 0 ?>%"></span></div>
    <small><?= moeda($mesTotais['pagar']['quitado']) ?> pagos · faltam <?= moeda($mesTotais['pagar']['total'] - $mesTotais['pagar']['quitado']) ?></small>
  </article>

  <article class="card-resumo">
    <span class="card-rotulo">A receber no mês</span>
    <strong><?= moeda($mesTotais['receber']['total']) ?></strong>
    <div class="barra verde"><span style="width: <?= $mesTotais['receber']['total'] ? round($mesTotais['receber']['quitado'] / $mesTotais['receber']['total'] * 100) : 0 ?>%"></span></div>
    <small><?= moeda($mesTotais['receber']['quitado']) ?> recebidos · faltam <?= moeda($mesTotais['receber']['total'] - $mesTotais['receber']['quitado']) ?></small>
  </article>

  <article class="card-resumo destaque">
    <span class="card-rotulo">Saldo previsto do mês</span>
    <strong class="<?= $saldoPrevisto < 0 ? 'negativo' : 'positivo' ?>"><?= moeda($saldoPrevisto) ?></strong>
    <small>Realizado até agora: <b class="<?= $saldoRealizado < 0 ? 'negativo' : 'positivo' ?>"><?= moeda($saldoRealizado) ?></b></small>
  </article>

  <article class="card-resumo <?= ($atrasados['pagar']['qtd'] + $atrasados['receber']['qtd']) ? 'alerta-card' : '' ?>">
    <span class="card-rotulo">Em atraso</span>
    <strong class="qtd-atraso"><?= $atrasados['pagar']['qtd'] + $atrasados['receber']['qtd'] ?> <small>lançamento(s)</small></strong>
    <small>
      <a href="lancamentos.php?tipo=pagar&mes=todos&situacao=atrasado"><?= $atrasados['pagar']['qtd'] ?> a pagar (<?= moeda($atrasados['pagar']['total']) ?>)</a><br>
      <a href="lancamentos.php?tipo=receber&mes=todos&situacao=atrasado"><?= $atrasados['receber']['qtd'] ?> a receber (<?= moeda($atrasados['receber']['total']) ?>)</a>
    </small>
  </article>
</section>

<!-- Gráficos -->
<section class="graficos" id="graficos" data-graficos='<?= e(json_encode($dadosGraficos)) ?>'>
  <article class="cartao">
    <h2>Despesas por categoria</h2>
    <?php if ($porCategoria): ?>
      <div class="grafico-caixa"><canvas id="graficoCategorias"></canvas></div>
    <?php else: ?>
      <p class="vazio-mini">Nenhuma despesa neste mês.</p>
    <?php endif; ?>
  </article>
  <article class="cartao">
    <h2>A pagar × a receber (6 meses)</h2>
    <div class="grafico-caixa"><canvas id="graficoEvolucao"></canvas></div>
  </article>
</section>

<!-- Próximos vencimentos -->
<section class="cartao">
  <div class="cartao-topo">
    <h2>Vencem nos próximos 7 dias</h2>
    <a href="lancamento_form.php?tipo=pagar" class="botao-link">+ Nova conta</a>
  </div>
  <?php if (!$proximos): ?>
    <p class="vazio-mini">Nada vencendo nos próximos dias. 🎉</p>
  <?php else: ?>
    <ul class="lista-proximos">
      <?php foreach ($proximos as $p): ?>
        <li>
          <span class="tipo-bolinha <?= $p['tipo'] ?>" title="<?= $p['tipo'] === 'pagar' ? 'A pagar' : 'A receber' ?>"></span>
          <div class="proximo-texto">
            <strong><?= e($p['descricao']) ?></strong>
            <small><?= e($p['pessoa'] ?: 'Sem ' . ($p['tipo'] === 'pagar' ? 'fornecedor' : 'cliente')) ?> · vence <?= $p['vencimento'] === $hoje ? '<b>hoje</b>' : 'em ' . data_br($p['vencimento']) ?></small>
          </div>
          <span class="proximo-valor <?= $p['tipo'] ?>"><?= $p['tipo'] === 'pagar' ? '−' : '+' ?> <?= moeda($p['valor']) ?></span>
          <form method="post" action="lancamento_acao.php">
            <?= campo_csrf() ?>
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button name="acao" value="quitar" class="botao-icone verde" title="Marcar como <?= $p['tipo'] === 'pagar' ? 'pago' : 'recebido' ?>">✓</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/rodape.php'; ?>
