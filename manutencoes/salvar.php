<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";
require_once "funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$idVeiculo = (int) ($_POST["id_veiculo"] ?? 0);
$idFornecedor = $_POST["id_fornecedor"] !== "" ? (int) $_POST["id_fornecedor"] : null;
$idOperacao = $_POST["id_operacao"] !== "" ? (int) $_POST["id_operacao"] : null;

$dataManutencao = $_POST["data_manutencao"] ?? "";
$tipoManutencao = $_POST["tipo_manutencao"] ?? "corretiva";
$servico = trim($_POST["servico"] ?? "");
$descricao = trim($_POST["descricao"] ?? "");

$valorMaoObra = numeroBanco($_POST["valor_mao_obra"] ?? "");
$valorPecas = numeroBanco($_POST["valor_pecas"] ?? "");
$valorTotal = $valorMaoObra + $valorPecas;

$kmManutencao = valorOuNull($_POST["km_manutencao"] ?? "");
$validadeKm = valorOuNull($_POST["validade_km"] ?? "");
$validadeDias = $_POST["validade_dias"] !== "" ? (int) $_POST["validade_dias"] : null;

$proximaTrocaKm = null;
$proximaTrocaData = null;

if ($kmManutencao !== null && $validadeKm !== null && $validadeKm > 0) {
    $proximaTrocaKm = $kmManutencao + $validadeKm;
}

if ($dataManutencao !== "" && $validadeDias !== null && $validadeDias > 0) {
    $proximaTrocaData = date("Y-m-d", strtotime($dataManutencao . " +{$validadeDias} days"));
}

$status = $_POST["status"] ?? "concluida";
$observacao = trim($_POST["observacao"] ?? "");

if ($idVeiculo <= 0 || $dataManutencao === "" || $servico === "") {
    die("Veículo, data e serviço são obrigatórios.");
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO manutencoes
        (
            id_veiculo,
            id_fornecedor,
            id_operacao,
            data_manutencao,
            tipo_manutencao,
            servico,
            descricao,
            valor_mao_obra,
            valor_pecas,
            valor_total,
            km_manutencao,
            validade_km,
            proxima_troca_km,
            validade_dias,
            proxima_troca_data,
            status,
            observacao
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $idVeiculo,
        $idFornecedor,
        $idOperacao,
        $dataManutencao,
        $tipoManutencao,
        $servico,
        $descricao,
        $valorMaoObra,
        $valorPecas,
        $valorTotal,
        $kmManutencao,
        $validadeKm,
        $proximaTrocaKm,
        $validadeDias,
        $proximaTrocaData,
        $status,
        $observacao
    ]);

    if ($kmManutencao !== null) {
        $stmt = $pdo->prepare("
            UPDATE veiculos
            SET km_atual = GREATEST(km_atual, ?)
            WHERE id = ?
        ");

        $stmt->execute([$kmManutencao, $idVeiculo]);
    }

    header("Location: listar.php?sucesso=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao salvar manutenção: " . $e->getMessage());
}