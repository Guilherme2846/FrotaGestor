<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

try {
    $stmt = $pdo->prepare("DELETE FROM motoristas WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao excluir motorista: " . $e->getMessage());
}