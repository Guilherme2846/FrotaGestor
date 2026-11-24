<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";
require_once "funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$tipoLancamento = $_POST["tipo_lancamento"] ?? "mensal";

$idMotorista = (int) ($_POST["id_motorista"] ?? 0);
$idVeiculo = $_POST["id_veiculo"] !== "" ? (int) $_POST["id_veiculo"] : null;
$idOperacao = $_POST["id_operacao"] !== "" ? (int) $_POST["id_operacao"] : null;

$mesReferencia = null;
$dataPagamento = null;

if ($tipoLancamento === "mensal") {
    $mes = $_POST["mes_referencia"] ?? "";

    if ($mes === "") {
        die("Informe o mês de referência.");
    }

    $mesReferencia = $mes . "-01";
}

if ($tipoLancamento === "detalhado") {
    $dataPagamento = $_POST["data_pagamento"] !== "" ? $_POST["data_pagamento"] : null;

    if ($dataPagamento === null) {
        die("Informe a data do pagamento.");
    }
}

$tipoPagamento = $_POST["tipo_pagamento"] ?? "salario";
$valor = numeroBanco($_POST["valor"] ?? "");
$status = $_POST["status"] ?? "lancado";
$observacao = trim($_POST["observacao"] ?? "");

if ($idMotorista <= 0 || $valor <= 0) {
    die("Motorista e valor são obrigatórios.");
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO pagamentos
        (
            tipo_lancamento,
            id_motorista,
            id_veiculo,
            id_operacao,
            mes_referencia,
            data_pagamento,
            tipo_pagamento,
            valor,
            status,
            observacao
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $tipoLancamento,
        $idMotorista,
        $idVeiculo,
        $idOperacao,
        $mesReferencia,
        $dataPagamento,
        $tipoPagamento,
        $valor,
        $status,
        $observacao
    ]);

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao salvar pagamento: " . $e->getMessage());
}