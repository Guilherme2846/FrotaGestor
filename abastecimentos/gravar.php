<?php
if (!isset($editar)) {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoes.php';
exigirPostAbastecimento();

try {
    $id = (int)($_POST['id'] ?? 0);
    $veiculo = (int)($_POST['id_veiculo'] ?? 0);
    $data = $_POST['data_abastecimento'] ?? '';
    $dia = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    $tipo = $_POST['tipo_diesel'] ?? 'S500';
    $litros = numeroBanco($_POST['litros'] ?? '', 3);
    $total = numeroBanco($_POST['valor_total'] ?? '', 2);
    $atual = numeroBanco($_POST['km_atual'] ?? '', 2);
    $base = numeroBanco($_POST['km_anterior'] ?? '', 2);
    $arla = numeroBanco($_POST['arla_litros'] ?? '', 3) ?? 0;
    $valorArla = numeroBanco($_POST['arla_valor_total'] ?? '', 2) ?? 0;
    if (!$dia || $dia->format('Y-m-d') !== $data || $veiculo <= 0 || $litros <= 0 || $total <= 0
        || $atual === null || !in_array($tipo, ['S10', 'S500', 'Outro'], true)
        || ($base !== null && (float)$base > (float)$atual) || ($arla == 0 && $valorArla > 0)) {
        throw new InvalidArgumentException('Confira a data, a placa, os litros, os valores e o hodômetro.');
    }

    $dados = [
        'id_veiculo' => $veiculo, 'data_abastecimento' => $data, 'tipo_diesel' => $tipo,
        'litros' => $litros, 'valor_total' => $total, 'km_atual' => $atual, 'km_base_manual' => $base,
        'arla_litros' => $arla, 'arla_valor_total' => $valorArla,
        'arla_valor_litro' => $arla > 0 ? $valorArla / $arla : null,
        'observacao' => trim($_POST['observacao'] ?? '')
    ];
    foreach (['id_motorista', 'id_operacao', 'id_fornecedor'] as $campo) {
        $dados[$campo] = (int)($_POST[$campo] ?? 0) ?: null;
    }
    $pdo->beginTransaction();
    $afetados = [$veiculo];
    if ($editar) {
        $antes = $pdo->prepare('SELECT id_veiculo FROM abastecimentos WHERE id = ? FOR UPDATE');
        $antes->execute([$id]);
        $anterior = $antes->fetchColumn();
        if (!$anterior) {
            throw new InvalidArgumentException('Abastecimento não encontrado.');
        }
        $afetados[] = (int)$anterior;
    }
    $afetados = travarVeiculos($pdo, $afetados);
    $campos = array_keys($dados);
    if ($editar) {
        $sql = 'UPDATE abastecimentos SET ' . implode(' = ?, ', $campos) . ' = ? WHERE id = ?';
        $dados['id'] = $id;
    } else {
        $sql = 'INSERT INTO abastecimentos (' . implode(', ', $campos) . ') VALUES ('
            . implode(', ', array_fill(0, count($campos), '?')) . ')';
    }
    $pdo->prepare($sql)->execute(array_values($dados));
    foreach ($afetados as $placa) {
        recalcularPlaca($pdo, $placa);
    }
    $pdo->commit();
    header('Location: listar.php?sucesso=1', true, 303);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    $mensagem = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Não foi possível gravar. Confira os dados e tente novamente.';
    echo htmlspecialchars($mensagem), ' <a href="javascript:history.back()">Voltar ao lançamento</a>';
}
