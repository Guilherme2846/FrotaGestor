<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$nome = trim($_POST["nome"] ?? "");
$cliente = trim($_POST["cliente"] ?? "");
$status = $_POST["status"] ?? "ativa";
$observacao = trim($_POST["observacao"] ?? "");

if ($nome === "") {
    die("Nome da operação é obrigatório.");
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO operacoes
        (nome, cliente, status, observacao)
        VALUES
        (?, ?, ?, ?)
    ");

    $stmt->execute([
        $nome,
        $cliente,
        $status,
        $observacao
    ]);

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao salvar operação: " . $e->getMessage());
}