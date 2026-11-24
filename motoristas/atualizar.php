<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$nome = trim($_POST["nome"] ?? "");
$cpf = trim($_POST["cpf"] ?? "");
$telefone = trim($_POST["telefone"] ?? "");
$cnh = trim($_POST["cnh"] ?? "");
$categoriaCnh = trim($_POST["categoria_cnh"] ?? "");
$tipoVinculo = trim($_POST["tipo_vinculo"] ?? "");
$status = $_POST["status"] ?? "ativo";
$observacao = trim($_POST["observacao"] ?? "");

if ($id <= 0 || $nome === "") {
    die("Dados inválidos.");
}

try {
    $stmt = $pdo->prepare("
        UPDATE motoristas
        SET
            nome = ?,
            cpf = ?,
            telefone = ?,
            cnh = ?,
            categoria_cnh = ?,
            tipo_vinculo = ?,
            status = ?,
            observacao = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $nome,
        $cpf,
        $telefone,
        $cnh,
        $categoriaCnh,
        $tipoVinculo,
        $status,
        $observacao,
        $id
    ]);

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao atualizar motorista: " . $e->getMessage());
}