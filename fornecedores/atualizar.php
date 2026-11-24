<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$nome = trim($_POST["nome"] ?? "");
$tipo = $_POST["tipo"] ?? "outro";
$cnpj = trim($_POST["cnpj"] ?? "");
$telefone = trim($_POST["telefone"] ?? "");
$cidade = trim($_POST["cidade"] ?? "");
$status = $_POST["status"] ?? "ativo";
$observacao = trim($_POST["observacao"] ?? "");

if ($id <= 0 || $nome === "") {
    die("Dados inválidos.");
}

try {
    $stmt = $pdo->prepare("
        UPDATE fornecedores
        SET
            nome = ?,
            tipo = ?,
            cnpj = ?,
            telefone = ?,
            cidade = ?,
            status = ?,
            observacao = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $nome,
        $tipo,
        $cnpj,
        $telefone,
        $cidade,
        $status,
        $observacao,
        $id
    ]);

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao atualizar fornecedor: " . $e->getMessage());
}