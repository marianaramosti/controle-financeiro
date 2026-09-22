<?php
// =========================================================
// CONSULTAS DE LANÇAMENTOS E CATEGORIAS
// Todas filtram por usuario_id: cada pessoa só vê os próprios dados.
// =========================================================

require_once __DIR__ . '/db.php';

function tipo_valido(?string $tipo): string
{
    return $tipo === 'receber' ? 'receber' : 'pagar';
}

function buscar_categorias(int $usuarioId, ?string $tipo = null): array
{
    $sql = 'SELECT id, nome, tipo, cor FROM categorias WHERE usuario_id = ?';
    $params = [$usuarioId];
    if ($tipo) {
        $sql .= ' AND tipo = ?';
        $params[] = $tipo;
    }
    $stmt = db()->prepare($sql . ' ORDER BY nome');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function buscar_lancamento(int $usuarioId, int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM lancamentos WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$id, $usuarioId]);
    return $stmt->fetch() ?: null;
}

/**
 * Lista lançamentos com filtros opcionais:
 * mes (AAAA-MM), situacao (pendente|atrasado|quitado), categoria (id), busca (texto).
 */
function listar_lancamentos(int $usuarioId, string $tipo, array $filtros = []): array
{
    $sql = 'SELECT l.*, c.nome AS categoria, c.cor AS categoria_cor
              FROM lancamentos l
              LEFT JOIN categorias c ON c.id = l.categoria_id
             WHERE l.usuario_id = ? AND l.tipo = ?';
    $params = [$usuarioId, $tipo];

    if (!empty($filtros['mes'])) {
        $sql .= ' AND DATE_FORMAT(l.vencimento, "%Y-%m") = ?';
        $params[] = $filtros['mes'];
    }

    $hoje = date('Y-m-d');
    switch ($filtros['situacao'] ?? '') {
        case 'quitado':
            $sql .= ' AND l.data_quitacao IS NOT NULL';
            break;
        case 'atrasado':
            $sql .= ' AND l.data_quitacao IS NULL AND l.vencimento < ?';
            $params[] = $hoje;
            break;
        case 'pendente':
            $sql .= ' AND l.data_quitacao IS NULL AND l.vencimento >= ?';
            $params[] = $hoje;
            break;
    }

    if (!empty($filtros['categoria'])) {
        $sql .= ' AND l.categoria_id = ?';
        $params[] = (int) $filtros['categoria'];
    }

    if (!empty($filtros['busca'])) {
        $sql .= ' AND (l.descricao LIKE ? OR l.pessoa LIKE ?)';
        $termo = '%' . $filtros['busca'] . '%';
        $params[] = $termo;
        $params[] = $termo;
    }

    $sql .= ' ORDER BY (l.data_quitacao IS NOT NULL), l.vencimento, l.id';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Soma os valores por situação (usado nos totais das listas). */
function totais(array $lancamentos): array
{
    $t = ['total' => 0, 'quitado' => 0, 'pendente' => 0, 'atrasado' => 0];
    foreach ($lancamentos as $l) {
        $t['total'] += $l['valor'];
        $t[situacao($l)] += $l['valor'];
    }
    return $t;
}
