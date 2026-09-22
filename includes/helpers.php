<?php
// =========================================================
// FUNÇÕES DE APOIO (usadas em várias páginas)
// =========================================================

/** Escapa texto antes de mostrar no HTML (evita XSS). */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Formata número como moeda brasileira: 1234.5 → R$ 1.234,50 */
function moeda($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/** Formata data do banco (2026-09-21) para o padrão brasileiro (21/09/2026). */
function data_br(?string $data): string
{
    if (!$data) {
        return '—';
    }
    $d = DateTime::createFromFormat('Y-m-d', substr($data, 0, 10));
    return $d ? $d->format('d/m/Y') : '—';
}

/** Converte "1.234,56" ou "1234.56" em número. Retorna null se inválido. */
function valor_para_numero(string $texto): ?float
{
    $texto = trim(str_replace(['R$', ' '], '', $texto));
    if (strpos($texto, ',') !== false) {               // formato brasileiro
        $texto = str_replace('.', '', $texto);
        $texto = str_replace(',', '.', $texto);
    }
    return is_numeric($texto) ? round((float) $texto, 2) : null;
}

/** Nome do mês em português a partir de "2026-09". */
function mes_extenso(string $anoMes): string
{
    $meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
              'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    [$ano, $mes] = explode('-', $anoMes);
    return $meses[(int) $mes] . ' de ' . $ano;
}

/** Valida "AAAA-MM"; se inválido, devolve o mês atual. */
function mes_valido(?string $anoMes): string
{
    return ($anoMes && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $anoMes)) ? $anoMes : date('Y-m');
}

/**
 * Situação de um lançamento:
 * quitado (pago/recebido), atrasado (venceu e não foi quitado) ou pendente.
 */
function situacao(array $l): string
{
    if (!empty($l['data_quitacao'])) {
        return 'quitado';
    }
    return $l['vencimento'] < date('Y-m-d') ? 'atrasado' : 'pendente';
}

/** Texto amigável da situação, conforme o tipo. */
function rotulo_situacao(string $situacao, string $tipo): string
{
    if ($situacao === 'quitado') {
        return $tipo === 'pagar' ? 'Pago' : 'Recebido';
    }
    return $situacao === 'atrasado' ? 'Atrasado' : 'Pendente';
}

// ---------------------------------------------------------
// MENSAGENS "FLASH" (aparecem uma vez após redirecionar)
// ---------------------------------------------------------
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function mostrar_flash(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alerta alerta-' . e($f['tipo']) . '" role="status">' . e($f['mensagem']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ---------------------------------------------------------
// PROTEÇÃO CSRF
// Todo formulário que altera dados envia um token secreto;
// assim outro site não consegue enviar ações em seu nome.
// ---------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function validar_csrf(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(400);
        exit('Sessão expirada ou requisição inválida. Volte e tente novamente.');
    }
}
