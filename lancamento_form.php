<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lancamentos_repo.php';
exigir_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$editando = $id > 0;

if ($editando) {
    $lancamento = buscar_lancamento(usuario_id(), $id);
    if (!$lancamento) {
        flash('erro', 'Lançamento não encontrado.');
        redirecionar('painel.php');
    }
    $tipo = $lancamento['tipo'];
} else {
    $tipo = tipo_valido($_GET['tipo'] ?? $_POST['tipo'] ?? 'pagar');
    $lancamento = [
        'descricao' => '', 'pessoa' => '', 'categoria_id' => null, 'valor' => '',
        'vencimento' => date('Y-m-d'), 'data_quitacao' => null, 'observacao' => '',
    ];
}
$ehPagar = $tipo === 'pagar';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $dados = [
        'descricao'     => trim($_POST['descricao'] ?? ''),
        'pessoa'        => trim($_POST['pessoa'] ?? ''),
        'categoria_id'  => (int) ($_POST['categoria_id'] ?? 0) ?: null,
        'valor'         => valor_para_numero($_POST['valor'] ?? ''),
        'vencimento'    => $_POST['vencimento'] ?? '',
        'data_quitacao' => !empty($_POST['quitado']) ? ($_POST['data_quitacao'] ?: date('Y-m-d')) : null,
        'observacao'    => trim($_POST['observacao'] ?? ''),
    ];

    // Validações
    if ($dados['descricao'] === '' || mb_strlen($dados['descricao']) > 150) {
        $erros[] = 'Informe uma descrição (até 150 caracteres).';
    }
    if ($dados['valor'] === null || $dados['valor'] <= 0) {
        $erros[] = 'Informe um valor maior que zero.';
    }
    if (!DateTime::createFromFormat('Y-m-d', $dados['vencimento'])) {
        $erros[] = 'Informe uma data de vencimento válida.';
    }
    if ($dados['data_quitacao'] && !DateTime::createFromFormat('Y-m-d', $dados['data_quitacao'])) {
        $erros[] = 'Data de ' . ($ehPagar ? 'pagamento' : 'recebimento') . ' inválida.';
    }
    // A categoria precisa pertencer ao usuário e ao mesmo tipo
    if ($dados['categoria_id']) {
        $ok = array_filter(buscar_categorias(usuario_id(), $tipo), function ($c) use ($dados) {
            return (int) $c['id'] === $dados['categoria_id'];
        });
        if (!$ok) {
            $erros[] = 'Categoria inválida.';
        }
    }

    if (!$erros) {
        $valores = [
            $dados['descricao'], $dados['pessoa'] ?: null, $dados['categoria_id'], $dados['valor'],
            $dados['vencimento'], $dados['data_quitacao'], $dados['observacao'] ?: null,
        ];
        if ($editando) {
            $stmt = db()->prepare(
                'UPDATE lancamentos SET descricao=?, pessoa=?, categoria_id=?, valor=?, vencimento=?,
                        data_quitacao=?, observacao=? WHERE id=? AND usuario_id=?'
            );
            $stmt->execute(array_merge($valores, [$id, usuario_id()]));
            flash('sucesso', 'Lançamento atualizado.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO lancamentos (descricao, pessoa, categoria_id, valor, vencimento, data_quitacao,
                        observacao, usuario_id, tipo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute(array_merge($valores, [usuario_id(), $tipo]));
            flash('sucesso', ($ehPagar ? 'Conta' : 'Cobrança') . ' cadastrada.');
        }
        redirecionar('lancamentos.php?tipo=' . $tipo . '&mes=' . substr($dados['vencimento'], 0, 7));
    }

    // Se houve erro, mantém o que foi digitado no formulário
    $lancamento = array_merge($lancamento, $dados, ['valor' => $_POST['valor'] ?? '']);
}

$categorias = buscar_categorias(usuario_id(), $tipo);
$valorCampo = is_numeric($lancamento['valor']) ? number_format((float) $lancamento['valor'], 2, ',', '.') : $lancamento['valor'];

$titulo = ($editando ? 'Editar ' : 'Nova ') . ($ehPagar ? 'conta a pagar' : 'cobrança');
$pagina = $tipo;
require __DIR__ . '/includes/cabecalho.php';
?>

<section class="cartao formulario-cartao">
  <?php foreach ($erros as $erro): ?>
    <div class="alerta alerta-erro"><?= e($erro) ?></div>
  <?php endforeach; ?>

  <form method="post" class="formulario" novalidate>
    <?= campo_csrf() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

    <label>Descrição *
      <input type="text" name="descricao" value="<?= e($lancamento['descricao']) ?>" required maxlength="150"
             placeholder="<?= $ehPagar ? 'Ex.: Conta de energia' : 'Ex.: Honorários de setembro' ?>" autofocus>
    </label>

    <div class="linha-2">
      <label><?= $ehPagar ? 'Fornecedor' : 'Cliente' ?>
        <input type="text" name="pessoa" value="<?= e($lancamento['pessoa']) ?>" maxlength="120">
      </label>
      <label>Categoria
        <select name="categoria_id">
          <option value="">Sem categoria</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (int) $lancamento['categoria_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <div class="linha-2">
      <label>Valor (R$) *
        <input type="text" name="valor" value="<?= e($valorCampo) ?>" required inputmode="decimal"
               placeholder="0,00" data-moeda>
      </label>
      <label>Vencimento *
        <input type="date" name="vencimento" value="<?= e($lancamento['vencimento']) ?>" required>
      </label>
    </div>

    <div class="caixa-quitacao">
      <label class="checkbox">
        <input type="checkbox" name="quitado" value="1" id="quitado" <?= $lancamento['data_quitacao'] ? 'checked' : '' ?>>
        Já foi <?= $ehPagar ? 'pago' : 'recebido' ?>
      </label>
      <label id="campoQuitacao" <?= $lancamento['data_quitacao'] ? '' : 'hidden' ?>>Data do <?= $ehPagar ? 'pagamento' : 'recebimento' ?>
        <input type="date" name="data_quitacao" value="<?= e($lancamento['data_quitacao'] ?? date('Y-m-d')) ?>">
      </label>
    </div>

    <label>Observação
      <textarea name="observacao" rows="3" maxlength="1000"><?= e($lancamento['observacao']) ?></textarea>
    </label>

    <div class="formulario-botoes">
      <a href="lancamentos.php?tipo=<?= e($tipo) ?>" class="botao botao-contorno">Cancelar</a>
      <button type="submit" class="botao botao-principal"><?= $editando ? 'Salvar alterações' : 'Cadastrar' ?></button>
    </div>
  </form>
</section>

<?php require __DIR__ . '/includes/rodape.php'; ?>
