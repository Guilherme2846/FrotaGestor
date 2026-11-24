<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$nome = trim($_POST["nome"] ?? "");
$cliente = trim($_POST["cliente"] ?? "");
$status = $_POST["status"] ?? "ativa";
$observacao = trim($_POST["observacao"] ?? "");

if ($id <= 0 || $nome === "") {
    die("Dados inválidos.");
}

try {
    $stmt = $pdo->prepare("
        UPDATE operacoes
        SET
            nome = ?,
            cliente = ?,
            status = ?,
            observacao = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $nome,
        $cliente,
        $status,
        $observacao,
        $id
    ]);

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao atualizar operação: " . $e->getMessage());
}