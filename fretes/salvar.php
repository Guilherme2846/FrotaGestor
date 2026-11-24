<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";
require_once "funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$tipoLancamento = $_POST["tipo_lancamento"] ?? "resumido";

$idVeiculo = (int) ($_POST["id_veiculo"] ?? 0);
$idMotorista = $_POST["id_motorista"] !== "" ? (int) $_POST["id_motorista"] : null;
$idOperacao = $_POST["id_operacao"] !== "" ? (int) $_POST["id_operacao"] : null;

$cliente = trim($_POST["cliente"] ?? "");

$dataInicio = $_POST["data_inicio"] !== "" ? $_POST["data_inicio"] : null;
$dataFim = $_POST["data_fim"] !== "" ? $_POST["data_fim"] : null;
$dataFrete = $_POST["data_frete"] !== "" ? $_POST["data_frete"] : null;

$origem = trim($_POST["origem"] ?? "");
$destino = trim($_POST["destino"] ?? "");

$valor = numeroBanco($_POST["valor"] ?? "");
$status = $_POST["status"] ?? "lancado";
$observacao = trim($_POST["observacao"] ?? "");

if ($idVeiculo <= 0 || $valor <= 0) {
    die("Veículo e valor são obrigatórios.");
}

if ($tipoLancamento === "resumido") {
    if ($dataInicio === null || $dataFim === null) {
        die("Para frete resumido, informe data inicial e data final.");
    }

    $dataFrete = null;
    $origem = "";
    $destino = "";
}

if ($tipoLancamento === "detalhado") {
    if ($dataFrete === null) {
        die("Para frete detalhado, informe a data do frete.");
    }

    $dataInicio = null;
    $dataFim = null;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO fretes
        (
            tipo_lancamento,
            id_veiculo,
            id_motorista,
            id_operacao,
            cliente,
            data_inicio,
            data_fim,
            data_frete,
            origem,
            destino,
            valor,
            status,
            observacao
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $tipoLancamento,
        $idVeiculo,
        $idMotorista,
        $idOperacao,
        $cliente,
        $dataInicio,
        $dataFim,
        $dataFrete,
        $origem,
        $destino,
        $valor,
        $status,
        $observacao
    ]);

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao salvar frete: " . $e->getMessage());
}