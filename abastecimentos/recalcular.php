<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoes.php';
exigirPostAbastecimento();
try {
    $pdo->beginTransaction();
    $id = (int)($_POST['id_veiculo'] ?? 0);
    $ids = $id ? [$id] : $pdo->query('SELECT id FROM veiculos ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    foreach (travarVeiculos($pdo, $ids) as $veiculo) {
        recalcularPlaca($pdo, (int)$veiculo);
    }
    $pdo->commit();
    header('Location: listar.php?sucesso=1', true, 303);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(409);
    exit('Não foi possível recalcular. Atualize a consulta e tente novamente.');
}
