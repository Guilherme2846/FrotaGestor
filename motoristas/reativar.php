<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

$stmt = $pdo->prepare("
    UPDATE motoristas
    SET status = 'ativo'
    WHERE id = ?
");

$stmt->execute([$id]);

header("Location: listar.php?sucesso=1");
exit;