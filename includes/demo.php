<?php
// =========================================================
// DADOS DE DEMONSTRAÇÃO
// Cria categorias e lançamentos fictícios para o usuário demo,
// sempre com datas relativas a hoje (o painel parece atual).
// Também permite "restaurar" a demo se alguém apagar tudo.
// =========================================================

require_once __DIR__ . '/db.php';

function eh_usuario_demo(): bool
{
    global $config;
    return (usuario_atual()['email'] ?? '') === $config['demo_email'];
}

function recriar_dados_demo(int $uid): void
{
    $pdo = db();
    $pdo->beginTransaction();

    $pdo->prepare('DELETE FROM lancamentos WHERE usuario_id = ?')->execute([$uid]);
    $pdo->prepare('DELETE FROM categorias WHERE usuario_id = ?')->execute([$uid]);

    $categorias = [
        'aluguel'  => ['Aluguel', 'pagar', '#8b5cf6'],
        'forn'     => ['Fornecedores', 'pagar', '#22d3ee'],
        'energia'  => ['Energia e água', 'pagar', '#f59e0b'],
        'internet' => ['Internet e telefone', 'pagar', '#34d399'],
        'imposto'  => ['Impostos', 'pagar', '#f472b6'],
        'material' => ['Material de escritório', 'pagar', '#60a5fa'],
        'honor'    => ['Honorários', 'receber', '#34d399'],
        'consult'  => ['Consultoria', 'receber', '#22d3ee'],
        'outros'   => ['Outros recebimentos', 'receber', '#a78bfa'],
    ];
    $ids = [];
    $stmt = $pdo->prepare('INSERT INTO categorias (usuario_id, nome, tipo, cor) VALUES (?, ?, ?, ?)');
    foreach ($categorias as $chave => [$nome, $tipo, $cor]) {
        $stmt->execute([$uid, $nome, $tipo, $cor]);
        $ids[$chave] = (int) $pdo->lastInsertId();
    }

    // Datas relativas: "d:-3" = hoje − 3 dias; "m:5" = dia 5 do mês atual; "M:-2" = mesmo dia, 2 meses atrás
    $data = function (string $regra): string {
        [$modo, $n] = explode(':', $regra);
        $d = new DateTime('today');
        if ($modo === 'd') {
            $d->modify(($n >= 0 ? '+' : '') . $n . ' days');
        } elseif ($modo === 'm') {
            $d->setDate((int) $d->format('Y'), (int) $d->format('m'), min((int) $n, (int) $d->format('t')));
        } else {
            $d->modify('first day of this month')->modify($n . ' months');
            $d->setDate((int) $d->format('Y'), (int) $d->format('m'), min(15, (int) $d->format('t')));
        }
        return $d->format('Y-m-d');
    };

    // [tipo, descrição, pessoa, categoria, valor, vencimento, quitação (null = em aberto)]
    $lancamentos = [
        ['pagar', 'Aluguel da sala', 'Imobiliária Central', 'aluguel', 1800.00, 'm:5', 'm:4'],
        ['pagar', 'Conta de energia', 'Companhia de Energia', 'energia', 320.45, 'm:10', 'm:10'],
        ['pagar', 'Internet fibra', 'Provedor Net Rápida', 'internet', 149.90, 'd:-2', null],
        ['pagar', 'Resmas de papel e toner', 'Papelaria Modelo', 'material', 275.00, 'd:-5', null],
        ['pagar', 'Simples Nacional', 'Receita Federal', 'imposto', 890.12, 'd:0', null],
        ['pagar', 'Serviço de limpeza', 'Limpa Bem Serviços', 'forn', 600.00, 'd:4', null],
        ['pagar', 'Manutenção de computadores', 'TecInfo Assistência', 'forn', 450.00, 'd:12', null],
        ['receber', 'Honorários — cliente A', 'Cliente A Ltda.', 'honor', 3500.00, 'm:8', 'm:8'],
        ['receber', 'Honorários — cliente B', 'Cliente B ME', 'honor', 2200.00, 'd:-4', null],
        ['receber', 'Consultoria em processos', 'Associação Beta', 'consult', 1500.00, 'd:6', null],
        ['receber', 'Parecer técnico', 'Empresa Gama', 'outros', 800.00, 'd:15', null],
    ];
    // Meses anteriores (para o gráfico de evolução)
    $historico = [
        -1 => [['pagar', 'aluguel', 1800.00], ['pagar', 'energia', 298.70], ['pagar', 'forn', 210.00], ['pagar', 'imposto', 765.40], ['receber', 'honor', 3500.00], ['receber', 'consult', 1200.00]],
        -2 => [['pagar', 'aluguel', 1800.00], ['pagar', 'energia', 341.10], ['pagar', 'material', 180.00], ['receber', 'honor', 5700.00]],
        -3 => [['pagar', 'aluguel', 1800.00], ['pagar', 'internet', 149.90], ['pagar', 'imposto', 810.00], ['receber', 'consult', 2600.00]],
        -4 => [['pagar', 'aluguel', 1750.00], ['pagar', 'forn', 190.00], ['pagar', 'energia', 305.00], ['receber', 'honor', 3300.00], ['receber', 'outros', 650.00]],
        -5 => [['pagar', 'aluguel', 1750.00], ['pagar', 'imposto', 702.00], ['receber', 'honor', 3300.00]],
    ];
    $nomes = ['aluguel' => 'Aluguel da sala', 'energia' => 'Conta de energia', 'forn' => 'Fornecedores diversos',
              'imposto' => 'Simples Nacional', 'material' => 'Material de escritório', 'internet' => 'Internet fibra',
              'honor' => 'Honorários', 'consult' => 'Consultoria em processos', 'outros' => 'Parecer técnico'];
    foreach ($historico as $mes => $itens) {
        foreach ($itens as [$tipo, $cat, $valor]) {
            $lancamentos[] = [$tipo, $nomes[$cat], null, $cat, $valor, "M:$mes", "M:$mes"];
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO lancamentos (usuario_id, tipo, descricao, pessoa, categoria_id, valor, vencimento, data_quitacao)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($lancamentos as [$tipo, $desc, $pessoa, $cat, $valor, $venc, $quit]) {
        $stmt->execute([$uid, $tipo, $desc, $pessoa, $ids[$cat], $valor, $data($venc), $quit ? $data($quit) : null]);
    }

    $pdo->commit();
}

/** No login do usuário demo: cria os dados se ele ainda não tiver nenhum. */
function preparar_demo_se_vazio(int $uid): void
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM lancamentos WHERE usuario_id = ?');
    $stmt->execute([$uid]);
    if ((int) $stmt->fetchColumn() === 0) {
        recriar_dados_demo($uid);
    }
}
