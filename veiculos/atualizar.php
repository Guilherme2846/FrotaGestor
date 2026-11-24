<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$placa = strtoupper(trim($_POST["placa"] ?? ""));
$marca = trim($_POST["marca"] ?? "");
$modelo = trim($_POST["modelo"] ?? "");
$ano = $_POST["ano"] !== "" ? (int) $_POST["ano"] : null;
$tipo = trim($_POST["tipo"] ?? "");
$eixos = trim($_POST["eixos"] ?? "");
$kmAtual = $_POST["km_atual"] !== "" ? (float) $_POST["km_atual"] : 0;
$status = $_POST["status"] ?? "ativo";
$observacao = trim($_POST["observacao"] ?? "");

if ($id <= 0 || $placa === "" || $modelo === "") {
    die("Dados inválidos.");
}

try {
    $stmt = $pdo->prepare("
        UPDATE veiculos
        SET
            placa = ?,
            marca = ?,
            modelo = ?,
            ano = ?,
            tipo = ?,
            eixos = ?,
            km_atual = ?,
            status = ?,
            observacao = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $placa,
        $marca,
        $modelo,
        $ano,
        $tipo,
        $eixos,
        $kmAtual,
        $status,
        $observacao,
        $id
    ]);

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        die("Erro: já existe outro veículo cadastrado com essa placa.");
    }

    die("Erro ao atualizar veículo: " . $e->getMessage());
}