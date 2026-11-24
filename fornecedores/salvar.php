<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$nome = trim($_POST["nome"] ?? "");
$tipo = $_POST["tipo"] ?? "outro";
$cnpj = trim($_POST["cnpj"] ?? "");
$telefone = trim($_POST["telefone"] ?? "");
$cidade = trim($_POST["cidade"] ?? "");
$status = $_POST["status"] ?? "ativo";
$observacao = trim($_POST["observacao"] ?? "");

if ($nome === "") {
    die("Nome do fornecedor é obrigatório.");
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO fornecedores
        (nome, tipo, cnpj, telefone, cidade, status, observacao)
        VALUES
        (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nome,
        $tipo,
        $cnpj,
        $telefone,
        $cidade,
        $status,
        $observacao
    ]);

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao salvar fornecedor: " . $e->getMessage());
}