<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoes.php';
exigirPostAbastecimento();
try {
    $pdo->beginTransaction();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id_veiculo FROM abastecimentos WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $veiculo = $stmt->fetchColumn();
    if ($veiculo) {
        travarVeiculos($pdo, [(int)$veiculo]);
        $pdo->prepare('DELETE FROM abastecimentos WHERE id = ?')->execute([$id]);
        recalcularPlaca($pdo, (int)$veiculo);
    }
    $pdo->commit();
    header('Location: listar.php?sucesso=1', true, 303);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(409);
    exit('Não foi possível excluir. Atualize a consulta e tente novamente.');
}
