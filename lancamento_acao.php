<?php
// Ações rápidas da lista: marcar como pago/recebido, desfazer e excluir.
// Só aceita POST com token CSRF.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lancamentos_repo.php';
exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('painel.php');
}
validar_csrf();

$id = (int) ($_POST['id'] ?? 0);
$lancamento = buscar_lancamento(usuario_id(), $id);
if (!$lancamento) {
    flash('erro', 'Lançamento não encontrado.');
    redirecionar('painel.php');
}
$ehPagar = $lancamento['tipo'] === 'pagar';

switch ($_POST['acao'] ?? '') {
    case 'quitar':
        db()->prepare('UPDATE lancamentos SET data_quitacao = CURDATE() WHERE id = ? AND usuario_id = ?')
            ->execute([$id, usuario_id()]);
        flash('sucesso', '“' . $lancamento['descricao'] . '” marcado como ' . ($ehPagar ? 'pago.' : 'recebido.'));
        break;

    case 'reabrir':
        db()->prepare('UPDATE lancamentos SET data_quitacao = NULL WHERE id = ? AND usuario_id = ?')
            ->execute([$id, usuario_id()]);
        flash('aviso', '“' . $lancamento['descricao'] . '” voltou a ficar em aberto.');
        break;

    case 'excluir':
        db()->prepare('DELETE FROM lancamentos WHERE id = ? AND usuario_id = ?')
            ->execute([$id, usuario_id()]);
        flash('sucesso', '“' . $lancamento['descricao'] . '” excluído.');
        break;
}

// Volta para a página de onde veio (se for deste site)
$voltar = $_SERVER['HTTP_REFERER'] ?? '';
$host = parse_url($voltar, PHP_URL_HOST);
$meuHost = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
redirecionar($voltar && $host === $meuHost ? $voltar : 'lancamentos.php?tipo=' . $lancamento['tipo']);
