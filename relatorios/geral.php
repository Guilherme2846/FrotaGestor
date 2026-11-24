<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Relatório Geral";

$dataInicio = $_GET["data_inicio"] ?? date("Y-m-01");
$dataFim = $_GET["data_fim"] ?? date("Y-m-t");
$idOperacao = $_GET["id_operacao"] ?? "";

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    ORDER BY nome ASC
")->fetchAll();

$veiculos = $pdo->query("
    SELECT id, placa, marca, modelo, tipo, eixos
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$dados = [];

foreach ($veiculos as $v) {
    $dados[$v["id"]] = [
        "id" => $v["id"],
        "placa" => $v["placa"],
        "marca" => $v["marca"],
        "modelo" => $v["modelo"],
        "tipo" => $v["tipo"],
        "eixos" => $v["eixos"],

        "fretes" => 0,

        "diesel_litros" => 0,
        "diesel_valor" => 0,

        "arla_litros" => 0,
        "arla_valor" => 0,

        "km_rodado" => 0,
        "litros_com_media" => 0,

        "manutencao_mao_obra" => 0,
        "manutencao_pecas" => 0,
        "manutencao_total" => 0,
        "manutencao_preventiva" => 0,
        "manutencao_corretiva" => 0,

        "pagamentos_bruto" => 0,
        "pagamentos_descontos" => 0,
        "pagamentos_liquido" => 0,
        "pagamentos_diaria" => 0,
        "pagamentos_comissao" => 0,
    ];
}

/* FRETES */
$whereFretes = [
    "status <> 'cancelado'",
    "COALESCE(data_frete, data_inicio) >= ?",
    "COALESCE(data_frete, data_inicio) <= ?"
];

$paramsFretes = [$dataInicio, $dataFim];

if ($idOperacao !== "") {
    $whereFretes[] = "id_operacao = ?";
    $paramsFretes[] = $idOperacao;
}

$stmt = $pdo->prepare("
    SELECT id_veiculo, COALESCE(SUM(valor), 0) AS total
    FROM fretes
    WHERE " . implode(" AND ", $whereFretes) . "
    GROUP BY id_veiculo
");

$stmt->execute($paramsFretes);

foreach ($stmt->fetchAll() as $linha) {
    if (isset($dados[$linha["id_veiculo"]])) {
        $dados[$linha["id_veiculo"]]["fretes"] = (float)$linha["total"];
    }
}

/* ABASTECIMENTOS */
$whereAbast = [
    "data_abastecimento >= ?",
    "data_abastecimento <= ?"
];

$paramsAbast = [$dataInicio, $dataFim];

if ($idOperacao !== "") {
    $whereAbast[] = "id_operacao = ?";
    $paramsAbast[] = $idOperacao;
}

$stmt = $pdo->prepare("
    SELECT
        id_veiculo,
        COALESCE(SUM(litros), 0) AS diesel_litros,
        COALESCE(SUM(valor_total), 0) AS diesel_valor,
        COALESCE(SUM(arla_litros), 0) AS arla_litros,
        COALESCE(SUM(arla_valor_total), 0) AS arla_valor,

        COALESCE(SUM(
            CASE
                WHEN km_rodado IS NOT NULL AND km_rodado > 0 THEN km_rodado
                ELSE 0
            END
        ), 0) AS km_rodado,

        COALESCE(SUM(
            CASE
                WHEN km_rodado IS NOT NULL AND km_rodado > 0 THEN litros
                ELSE 0
            END
        ), 0) AS litros_com_media

    FROM abastecimentos
    WHERE " . implode(" AND ", $whereAbast) . "
    GROUP BY id_veiculo
");

$stmt->execute($paramsAbast);

foreach ($stmt->fetchAll() as $linha) {
    if (isset($dados[$linha["id_veiculo"]])) {
        $dados[$linha["id_veiculo"]]["diesel_litros"] = (float)$linha["diesel_litros"];
        $dados[$linha["id_veiculo"]]["diesel_valor"] = (float)$linha["diesel_valor"];
        $dados[$linha["id_veiculo"]]["arla_litros"] = (float)$linha["arla_litros"];
        $dados[$linha["id_veiculo"]]["arla_valor"] = (float)$linha["arla_valor"];
        $dados[$linha["id_veiculo"]]["km_rodado"] = (float)$linha["km_rodado"];
        $dados[$linha["id_veiculo"]]["litros_com_media"] = (float)$linha["litros_com_media"];
    }
}

/* MANUTENÇÕES */
$whereManut = [
    "status <> 'cancelada'",
    "data_manutencao >= ?",
    "data_manutencao <= ?"
];

$paramsManut = [$dataInicio, $dataFim];

if ($idOperacao !== "") {
    $whereManut[] = "id_operacao = ?";
    $paramsManut[] = $idOperacao;
}

$stmt = $pdo->prepare("
    SELECT
        id_veiculo,
        COALESCE(SUM(valor_mao_obra), 0) AS mao_obra,
        COALESCE(SUM(valor_pecas), 0) AS pecas,
        COALESCE(SUM(valor_total), 0) AS total,

        COALESCE(SUM(
            CASE
                WHEN tipo_manutencao = 'preventiva' THEN valor_total
                ELSE 0
            END
        ), 0) AS preventiva,

        COALESCE(SUM(
            CASE
                WHEN tipo_manutencao = 'corretiva' THEN valor_total
                ELSE 0
            END
        ), 0) AS corretiva

    FROM manutencoes
    WHERE " . implode(" AND ", $whereManut) . "
    GROUP BY id_veiculo
");

$stmt->execute($paramsManut);

foreach ($stmt->fetchAll() as $linha) {
    if (isset($dados[$linha["id_veiculo"]])) {
        $dados[$linha["id_veiculo"]]["manutencao_mao_obra"] = (float)$linha["mao_obra"];
        $dados[$linha["id_veiculo"]]["manutencao_pecas"] = (float)$linha["pecas"];
        $dados[$linha["id_veiculo"]]["manutencao_total"] = (float)$linha["total"];
        $dados[$linha["id_veiculo"]]["manutencao_preventiva"] = (float)$linha["preventiva"];
        $dados[$linha["id_veiculo"]]["manutencao_corretiva"] = (float)$linha["corretiva"];
    }
}

/* PAGAMENTOS COM PLACA */
$wherePag = [
    "status <> 'cancelado'",
    "id_veiculo IS NOT NULL",
    "COALESCE(mes_referencia, data_pagamento) >= ?",
    "COALESCE(mes_referencia, data_pagamento) <= ?"
];

$paramsPag = [$dataInicio, $dataFim];

if ($idOperacao !== "") {
    $wherePag[] = "id_operacao = ?";
    $paramsPag[] = $idOperacao;
}

$stmt = $pdo->prepare("
    SELECT
        id_veiculo,

        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'desconto' THEN 0
                ELSE valor
            END
        ), 0) AS bruto,

        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'desconto' THEN valor
                ELSE 0
            END
        ), 0) AS descontos,

        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'desconto' THEN -valor
                ELSE valor
            END
        ), 0) AS liquido,

        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'diaria' THEN valor
                ELSE 0
            END
        ), 0) AS diaria,

        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'comissao' THEN valor
                ELSE 0
            END
        ), 0) AS comissao

    FROM pagamentos
    WHERE " . implode(" AND ", $wherePag) . "
    GROUP BY id_veiculo
");

$stmt->execute($paramsPag);

foreach ($stmt->fetchAll() as $linha) {
    if (isset($dados[$linha["id_veiculo"]])) {
        $dados[$linha["id_veiculo"]]["pagamentos_bruto"] = (float)$linha["bruto"];
        $dados[$linha["id_veiculo"]]["pagamentos_descontos"] = (float)$linha["descontos"];
        $dados[$linha["id_veiculo"]]["pagamentos_liquido"] = (float)$linha["liquido"];
        $dados[$linha["id_veiculo"]]["pagamentos_diaria"] = (float)$linha["diaria"];
        $dados[$linha["id_veiculo"]]["pagamentos_comissao"] = (float)$linha["comissao"];
    }
}

/* PAGAMENTOS SEM PLACA */
$wherePagSemPlaca = [
    "status <> 'cancelado'",
    "id_veiculo IS NULL",
    "COALESCE(mes_referencia, data_pagamento) >= ?",
    "COALESCE(mes_referencia, data_pagamento) <= ?"
];

$paramsPagSemPlaca = [$dataInicio, $dataFim];

if ($idOperacao !== "") {
    $wherePagSemPlaca[] = "id_operacao = ?";
    $paramsPagSemPlaca[] = $idOperacao;
}

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'desconto' THEN 0
                ELSE valor
            END
        ), 0) AS bruto,

        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'desconto' THEN valor
                ELSE 0
            END
        ), 0) AS descontos,

        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'desconto' THEN -valor
                ELSE valor
            END
        ), 0) AS liquido,

        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'diaria' THEN valor
                ELSE 0
            END
        ), 0) AS diaria,

        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'comissao' THEN valor
                ELSE 0
            END
        ), 0) AS comissao

    FROM pagamentos
    WHERE " . implode(" AND ", $wherePagSemPlaca)
);

$stmt->execute($paramsPagSemPlaca);
$pagSemPlaca = $stmt->fetch();

$pagamentosSemPlacaBruto = (float)$pagSemPlaca["bruto"];
$pagamentosSemPlacaDescontos = (float)$pagSemPlaca["descontos"];
$pagamentosSemPlacaLiquido = (float)$pagSemPlaca["liquido"];
$pagamentosSemPlacaDiaria = (float)$pagSemPlaca["diaria"];
$pagamentosSemPlacaComissao = (float)$pagSemPlaca["comissao"];

/* TOTAIS GERAIS */
$totalFretes = 0;
$totalDieselLitros = 0;
$totalDieselValor = 0;
$totalArlaLitros = 0;
$totalArlaValor = 0;
$totalKmRodado = 0;
$totalLitrosComMedia = 0;

$totalManutencao = 0;
$totalManutencaoMaoObra = 0;
$totalManutencaoPecas = 0;
$totalPreventiva = 0;
$totalCorretiva = 0;

$totalPagamentosBruto = $pagamentosSemPlacaBruto;
$totalPagamentosDescontos = $pagamentosSemPlacaDescontos;
$totalPagamentosLiquido = $pagamentosSemPlacaLiquido;
$totalDiarias = $pagamentosSemPlacaDiaria;
$totalComissoes = $pagamentosSemPlacaComissao;

$linhasPlacas = [];

foreach ($dados as $d) {
    $custoPlaca =
        $d["diesel_valor"] +
        $d["arla_valor"] +
        $d["manutencao_total"] +
        $d["pagamentos_liquido"];

    $resultadoPlaca = $d["fretes"] - $custoPlaca;

    $mediaPlaca = $d["litros_com_media"] > 0
        ? $d["km_rodado"] / $d["litros_com_media"]
        : 0;

    $valorMedioDieselPlaca = $d["diesel_litros"] > 0
        ? $d["diesel_valor"] / $d["diesel_litros"]
        : 0;

    $temMovimento =
        $d["fretes"] > 0 ||
        $d["diesel_valor"] > 0 ||
        $d["arla_valor"] > 0 ||
        $d["manutencao_total"] > 0 ||
        $d["pagamentos_liquido"] != 0;

    if ($temMovimento) {
        $custoKmPlaca = $d["km_rodado"] > 0
            ? $custoPlaca / $d["km_rodado"]
            : 0;

        $freteKmPlaca = $d["km_rodado"] > 0
            ? $d["fretes"] / $d["km_rodado"]
            : 0;

        $lucroKmPlaca = $d["km_rodado"] > 0
            ? $resultadoPlaca / $d["km_rodado"]
            : 0;

        $d["custo_total"] = $custoPlaca;
        $d["resultado"] = $resultadoPlaca;
        $d["media"] = $mediaPlaca;
        $d["valor_medio_diesel"] = $valorMedioDieselPlaca;

        $d["custo_km"] = $custoKmPlaca;
        $d["frete_km"] = $freteKmPlaca;
        $d["lucro_km"] = $lucroKmPlaca;

        $linhasPlacas[] = $d;
    }

    $totalFretes += $d["fretes"];
    $totalDieselLitros += $d["diesel_litros"];
    $totalDieselValor += $d["diesel_valor"];
    $totalArlaLitros += $d["arla_litros"];
    $totalArlaValor += $d["arla_valor"];
    $totalKmRodado += $d["km_rodado"];
    $totalLitrosComMedia += $d["litros_com_media"];

    $totalManutencao += $d["manutencao_total"];
    $totalManutencaoMaoObra += $d["manutencao_mao_obra"];
    $totalManutencaoPecas += $d["manutencao_pecas"];
    $totalPreventiva += $d["manutencao_preventiva"];
    $totalCorretiva += $d["manutencao_corretiva"];

    $totalPagamentosBruto += $d["pagamentos_bruto"];
    $totalPagamentosDescontos += $d["pagamentos_descontos"];
    $totalPagamentosLiquido += $d["pagamentos_liquido"];
    $totalDiarias += $d["pagamentos_diaria"];
    $totalComissoes += $d["pagamentos_comissao"];
}

$custoTotalGeral =
    $totalDieselValor +
    $totalArlaValor +
    $totalManutencao +
    $totalPagamentosLiquido;

$resultadoGeral = $totalFretes - $custoTotalGeral;

$mediaGeral = $totalLitrosComMedia > 0
    ? $totalKmRodado / $totalLitrosComMedia
    : 0;

$valorMedioDiesel = $totalDieselLitros > 0
    ? $totalDieselValor / $totalDieselLitros
    : 0;

$valorMedioArla = $totalArlaLitros > 0
    ? $totalArlaValor / $totalArlaLitros
    : 0;

$custoPorKm = $totalKmRodado > 0
    ? $custoTotalGeral / $totalKmRodado
    : 0;

$receitaPorKm = $totalKmRodado > 0
    ? $totalFretes / $totalKmRodado
    : 0;

$resultadoPorKm = $totalKmRodado > 0
    ? $resultadoGeral / $totalKmRodado
    : 0;

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Relatório Geral</h3>

    <button type="button" class="btn btn-outline-dark no-print" onclick="window.print()">
        Imprimir relatório
    </button>
</div>

<form method="GET" class="card card-body shadow-sm mb-4">
    <div class="row">
        <div class="col-md-3 mb-2">
            <label class="form-label">Data inicial</label>
            <input
                type="date"
                name="data_inicio"
                class="form-control"
                value="<?= htmlspecialchars($dataInicio) ?>"
            >
        </div>

        <div class="col-md-3 mb-2">
            <label class="form-label">Data final</label>
            <input
                type="date"
                name="data_fim"
                class="form-control"
                value="<?= htmlspecialchars($dataFim) ?>"
            >
        </div>

        <div class="col-md-4 mb-2">
            <label class="form-label">Operação</label>
            <select name="id_operacao" class="form-select">
                <option value="">Todas</option>

                <?php foreach ($operacoes as $op): ?>
                    <option value="<?= $op["id"] ?>" <?= (string)$idOperacao === (string)$op["id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($op["nome"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 mb-2 d-flex align-items-end">
            <button class="btn btn-dark w-100">Gerar</button>
        </div>
    </div>
</form>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-1">Resumo do período</h5>

        <p class="text-muted mb-0">
            <?= date("d/m/Y", strtotime($dataInicio)) ?>
            até
            <?= date("d/m/Y", strtotime($dataFim)) ?>
        </p>
    </div>
</div>

<div class="row mb-3">

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Fretes / Receita</small>
                <h5>R$ <?= number_format($totalFretes, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Diesel</small>
                <h5>R$ <?= number_format($totalDieselValor, 2, ",", ".") ?></h5>
                <small><?= number_format($totalDieselLitros, 2, ",", ".") ?> L</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">ARLA</small>
                <h5>R$ <?= number_format($totalArlaValor, 2, ",", ".") ?></h5>
                <small><?= number_format($totalArlaLitros, 2, ",", ".") ?> L</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Manutenções</small>
                <h5>R$ <?= number_format($totalManutencao, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

</div>

<div class="row mb-3">

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Pagamentos líquidos</small>
                <h5>R$ <?= number_format($totalPagamentosLiquido, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Custo total</small>
                <h5>R$ <?= number_format($custoTotalGeral, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Resultado geral</small>
                <h5 class="<?= $resultadoGeral >= 0 ? 'text-success' : 'text-danger' ?>">
                    R$ <?= number_format($resultadoGeral, 2, ",", ".") ?>
                </h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Média geral frota</small>
                <h5><?= number_format($mediaGeral, 2, ",", ".") ?> km/L</h5>
            </div>
        </div>
    </div>

</div>

<div class="row mb-4">

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">KM rodado</small>
                <h5><?= number_format($totalKmRodado, 0, ",", ".") ?> km</h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Valor médio diesel</small>
                <h5>R$ <?= number_format($valorMedioDiesel, 3, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Custo por KM</small>
                <h5>R$ <?= number_format($custoPorKm, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Receita por KM</small>
                <h5>R$ <?= number_format($receitaPorKm, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Lucro por KM</small>
                <h5 class="<?= $resultadoPorKm >= 0 ? 'text-success' : 'text-danger' ?>">
                    R$ <?= number_format($resultadoPorKm, 2, ",", ".") ?>
                </h5>
            </div>
        </div>
    </div>

</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Resumo financeiro geral</h5>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <tbody>
                    <tr>
                        <th>Receita com fretes</th>
                        <td class="text-end">R$ <?= number_format($totalFretes, 2, ",", ".") ?></td>
                    </tr>

                    <tr>
                        <th>Diesel</th>
                        <td class="text-end">R$ <?= number_format($totalDieselValor, 2, ",", ".") ?></td>
                    </tr>

                    <tr>
                        <th>ARLA</th>
                        <td class="text-end">R$ <?= number_format($totalArlaValor, 2, ",", ".") ?></td>
                    </tr>

                    <tr>
                        <th>Manutenções</th>
                        <td class="text-end">R$ <?= number_format($totalManutencao, 2, ",", ".") ?></td>
                    </tr>

                    <tr>
                        <th>Pagamentos líquidos</th>
                        <td class="text-end">R$ <?= number_format($totalPagamentosLiquido, 2, ",", ".") ?></td>
                    </tr>

                    <tr>
                        <th>Custo total</th>
                        <td class="text-end">R$ <?= number_format($custoTotalGeral, 2, ",", ".") ?></td>
                    </tr>

                    <tr>
                        <th>Resultado geral</th>
                        <td class="text-end">
                            <strong class="<?= $resultadoGeral >= 0 ? 'text-success' : 'text-danger' ?>">
                                R$ <?= number_format($resultadoGeral, 2, ",", ".") ?>
                            </strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if ($pagamentosSemPlacaLiquido != 0): ?>
            <div class="alert alert-warning mb-0">
                Existem pagamentos sem placa vinculada no valor líquido de
                <strong>R$ <?= number_format($pagamentosSemPlacaLiquido, 2, ",", ".") ?></strong>.
                Eles entram no resultado geral, mas não aparecem distribuídos por placa.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Pagamentos por tipo</h5>

        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <small class="text-muted">Total bruto</small>
                        <h5>R$ <?= number_format($totalPagamentosBruto, 2, ",", ".") ?></h5>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <small class="text-muted">Diárias</small>
                        <h5>R$ <?= number_format($totalDiarias, 2, ",", ".") ?></h5>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <small class="text-muted">Comissões</small>
                        <h5>R$ <?= number_format($totalComissoes, 2, ",", ".") ?></h5>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <small class="text-muted">Descontos</small>
                        <h5>R$ <?= number_format($totalPagamentosDescontos, 2, ",", ".") ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Resultado por placa</h5>

        <?php if (count($linhasPlacas) === 0): ?>
            <p class="text-muted mb-0">Nenhum movimento encontrado no período.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Fretes</th>
                            <th>Diesel</th>
                            <th>ARLA</th>
                            <th>Manutenção</th>
                            <th>Pagamentos</th>
                            <th>Custo total</th>
                            <th>Resultado</th>
                            <th>KM</th>
                            <th>Média</th>
                            <th>R$/L Diesel</th>
                            <th>Custo/KM</th>
                            <th>Frete/KM</th>
                            <th>Lucro/KM</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($linhasPlacas as $d): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($d["placa"]) ?></strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(($d["marca"] ?? "") . " " . $d["modelo"]) ?>
                                </td>

                                <td>R$ <?= number_format($d["fretes"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format($d["diesel_valor"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format($d["arla_valor"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format($d["manutencao_total"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format($d["pagamentos_liquido"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format($d["custo_total"], 2, ",", ".") ?></td>

                                <td>
                                    <strong class="<?= $d["resultado"] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        R$ <?= number_format($d["resultado"], 2, ",", ".") ?>
                                    </strong>
                                </td>

                                <td><?= number_format($d["km_rodado"], 0, ",", ".") ?> km</td>

                                <td>
                                    <?php if ($d["media"] > 0): ?>
                                        <?= number_format($d["media"], 2, ",", ".") ?> km/L
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    R$ <?= number_format($d["valor_medio_diesel"], 3, ",", ".") ?>
                                </td>

                                <td>
                                    <?php if ($d["km_rodado"] > 0): ?>
                                        R$ <?= number_format($d["custo_km"], 2, ",", ".") ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($d["km_rodado"] > 0): ?>
                                        R$ <?= number_format($d["frete_km"], 2, ",", ".") ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($d["km_rodado"] > 0): ?>
                                        <strong class="<?= $d["lucro_km"] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            R$ <?= number_format($d["lucro_km"], 2, ",", ".") ?>
                                        </strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>