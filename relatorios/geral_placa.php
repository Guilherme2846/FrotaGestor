<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Relatório Geral por Placa";

$idVeiculo = $_GET["id_veiculo"] ?? "";
$dataInicio = $_GET["data_inicio"] ?? date("Y-m-01");
$dataFim = $_GET["data_fim"] ?? date("Y-m-t");

$veiculos = $pdo->query("
    SELECT id, placa, marca, modelo, ano, tipo, eixos, km_atual
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$veiculoSelecionado = null;

$fretesTotal = 0;

$dieselLitros = 0;
$dieselValor = 0;
$valorMedioDiesel = 0;

$arlaLitros = 0;
$arlaValor = 0;
$valorMedioArla = 0;

$kmRodado = 0;
$litrosComMedia = 0;
$mediaKmL = 0;

$manutencaoMaoObra = 0;
$manutencaoPecas = 0;
$manutencaoTotal = 0;
$manutencaoPreventiva = 0;
$manutencaoCorretiva = 0;

$pagamentosBruto = 0;
$pagamentosDescontos = 0;
$pagamentosLiquido = 0;

$custoTotal = 0;
$resultadoEstimado = 0;
$custoPorKm = 0;
$receitaPorKm = 0;
$resultadoPorKm = 0;

$detalhesFretes = [];
$detalhesAbastecimentos = [];
$detalhesManutencoes = [];
$detalhesPagamentos = [];

if ($idVeiculo !== "") {
    $stmt = $pdo->prepare("
        SELECT id, placa, marca, modelo, ano, tipo, eixos, km_atual
        FROM veiculos
        WHERE id = ?
    ");
    $stmt->execute([(int)$idVeiculo]);
    $veiculoSelecionado = $stmt->fetch();

    if ($veiculoSelecionado) {
        // FRETES
        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(valor), 0) AS total
            FROM fretes
            WHERE id_veiculo = ?
              AND status <> 'cancelado'
              AND COALESCE(data_frete, data_inicio) >= ?
              AND COALESCE(data_frete, data_inicio) <= ?
        ");
        $stmt->execute([$idVeiculo, $dataInicio, $dataFim]);
        $fretesTotal = (float)$stmt->fetch()["total"];

        $stmt = $pdo->prepare("
            SELECT *
            FROM fretes
            WHERE id_veiculo = ?
              AND status <> 'cancelado'
              AND COALESCE(data_frete, data_inicio) >= ?
              AND COALESCE(data_frete, data_inicio) <= ?
            ORDER BY COALESCE(data_frete, data_inicio) ASC, id ASC
        ");
        $stmt->execute([$idVeiculo, $dataInicio, $dataFim]);
        $detalhesFretes = $stmt->fetchAll();

        // ABASTECIMENTOS
        $stmt = $pdo->prepare("
            SELECT
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
            WHERE id_veiculo = ?
              AND data_abastecimento >= ?
              AND data_abastecimento <= ?
        ");
        $stmt->execute([$idVeiculo, $dataInicio, $dataFim]);
        $abastResumo = $stmt->fetch();

        $dieselLitros = (float)$abastResumo["diesel_litros"];
        $dieselValor = (float)$abastResumo["diesel_valor"];
        $arlaLitros = (float)$abastResumo["arla_litros"];
        $arlaValor = (float)$abastResumo["arla_valor"];
        $kmRodado = (float)$abastResumo["km_rodado"];
        $litrosComMedia = (float)$abastResumo["litros_com_media"];

        $valorMedioDiesel = $dieselLitros > 0 ? $dieselValor / $dieselLitros : 0;
        $valorMedioArla = $arlaLitros > 0 ? $arlaValor / $arlaLitros : 0;
        $mediaKmL = $litrosComMedia > 0 ? $kmRodado / $litrosComMedia : 0;

        $stmt = $pdo->prepare("
            SELECT
                a.*,
                f.nome AS fornecedor,
                m.nome AS motorista,
                o.nome AS operacao
            FROM abastecimentos a
            LEFT JOIN fornecedores f ON f.id = a.id_fornecedor
            LEFT JOIN motoristas m ON m.id = a.id_motorista
            LEFT JOIN operacoes o ON o.id = a.id_operacao
            WHERE a.id_veiculo = ?
              AND a.data_abastecimento >= ?
              AND a.data_abastecimento <= ?
            ORDER BY a.data_abastecimento ASC, a.km_atual ASC, a.id ASC
        ");
        $stmt->execute([$idVeiculo, $dataInicio, $dataFim]);
        $detalhesAbastecimentos = $stmt->fetchAll();

        // MANUTENÇÕES
        $stmt = $pdo->prepare("
            SELECT
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
            WHERE id_veiculo = ?
              AND status <> 'cancelada'
              AND data_manutencao >= ?
              AND data_manutencao <= ?
        ");
        $stmt->execute([$idVeiculo, $dataInicio, $dataFim]);
        $manutResumo = $stmt->fetch();

        $manutencaoMaoObra = (float)$manutResumo["mao_obra"];
        $manutencaoPecas = (float)$manutResumo["pecas"];
        $manutencaoTotal = (float)$manutResumo["total"];
        $manutencaoPreventiva = (float)$manutResumo["preventiva"];
        $manutencaoCorretiva = (float)$manutResumo["corretiva"];

        $stmt = $pdo->prepare("
            SELECT
                m.*,
                f.nome AS fornecedor,
                o.nome AS operacao
            FROM manutencoes m
            LEFT JOIN fornecedores f ON f.id = m.id_fornecedor
            LEFT JOIN operacoes o ON o.id = m.id_operacao
            WHERE m.id_veiculo = ?
              AND m.status <> 'cancelada'
              AND m.data_manutencao >= ?
              AND m.data_manutencao <= ?
            ORDER BY m.data_manutencao ASC, m.id ASC
        ");
        $stmt->execute([$idVeiculo, $dataInicio, $dataFim]);
        $detalhesManutencoes = $stmt->fetchAll();

        // PAGAMENTOS
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
                ), 0) AS liquido
            FROM pagamentos
            WHERE id_veiculo = ?
              AND status <> 'cancelado'
              AND COALESCE(mes_referencia, data_pagamento) >= ?
              AND COALESCE(mes_referencia, data_pagamento) <= ?
        ");
        $stmt->execute([$idVeiculo, $dataInicio, $dataFim]);
        $pagResumo = $stmt->fetch();

        $pagamentosBruto = (float)$pagResumo["bruto"];
        $pagamentosDescontos = (float)$pagResumo["descontos"];
        $pagamentosLiquido = (float)$pagResumo["liquido"];

        $stmt = $pdo->prepare("
            SELECT
                p.*,
                m.nome AS motorista,
                o.nome AS operacao
            FROM pagamentos p
            INNER JOIN motoristas m ON m.id = p.id_motorista
            LEFT JOIN operacoes o ON o.id = p.id_operacao
            WHERE p.id_veiculo = ?
              AND p.status <> 'cancelado'
              AND COALESCE(p.mes_referencia, p.data_pagamento) >= ?
              AND COALESCE(p.mes_referencia, p.data_pagamento) <= ?
            ORDER BY COALESCE(p.mes_referencia, p.data_pagamento) ASC, p.id ASC
        ");
        $stmt->execute([$idVeiculo, $dataInicio, $dataFim]);
        $detalhesPagamentos = $stmt->fetchAll();

        $custoTotal = $dieselValor + $arlaValor + $manutencaoTotal + $pagamentosLiquido;
        $resultadoEstimado = $fretesTotal - $custoTotal;

        $custoPorKm = $kmRodado > 0 ? $custoTotal / $kmRodado : 0;
        $receitaPorKm = $kmRodado > 0 ? $fretesTotal / $kmRodado : 0;
        $resultadoPorKm = $kmRodado > 0 ? $resultadoEstimado / $kmRodado : 0;
    }
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Relatório Geral por Placa</h3>

    <?php if ($idVeiculo !== "" && $veiculoSelecionado): ?>
        <style>
    .d-print-block {
        display: none;
    }

    @media print {
        .d-print-block {
            display: block !important;
        }

        ...
    }
</style>
        <button type="button" class="btn btn-outline-dark no-print" onclick="window.print()">
            Imprimir relatório
        </button>
    <?php endif; ?>
</div>

<form method="GET" class="card card-body shadow-sm mb-4">
    <div class="row">
        <div class="col-md-4 mb-2">
            <label class="form-label">Placa</label>
            <select name="id_veiculo" class="form-select" required>
                <option value="">Selecione</option>

                <?php foreach ($veiculos as $v): ?>
                    <option value="<?= $v["id"] ?>" <?= (string)$idVeiculo === (string)$v["id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($v["placa"] . " - " . $v["modelo"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 mb-2">
            <label class="form-label">Data inicial</label>
            <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($dataInicio) ?>">
        </div>

        <div class="col-md-3 mb-2">
            <label class="form-label">Data final</label>
            <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($dataFim) ?>">
        </div>

        <div class="col-md-2 mb-2 d-flex align-items-end">
            <button class="btn btn-dark w-100">Gerar</button>
        </div>
    </div>
</form>

<?php if ($idVeiculo !== "" && $veiculoSelecionado): ?>
    <div class="d-none d-print-block mb-3">
        <h3>FrotaGestor - Relatório Geral por Placa</h3>
        <p>
            Gerado em: <?= date("d/m/Y H:i") ?>
        </p>
    </div>
<?php endif; ?>

<?php if ($idVeiculo === ""): ?>

    <div class="alert alert-info">
        Selecione uma placa e um período para gerar o relatório.
    </div>

<?php elseif (!$veiculoSelecionado): ?>

    <div class="alert alert-danger">
        Veículo não encontrado.
    </div>

<?php else: ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-1">
                <?= htmlspecialchars($veiculoSelecionado["placa"]) ?>
                -
                <?= htmlspecialchars($veiculoSelecionado["modelo"]) ?>
            </h5>

            <p class="text-muted mb-0">
                <?= htmlspecialchars($veiculoSelecionado["marca"] ?? "") ?>
                |
                <?= htmlspecialchars($veiculoSelecionado["tipo"] ?? "") ?>
                |
                Eixos: <?= htmlspecialchars($veiculoSelecionado["eixos"] ?? "") ?>
                |
                KM atual: <?= number_format((float)$veiculoSelecionado["km_atual"], 0, ",", ".") ?>
            </p>

            <p class="text-muted mb-0">
                Período:
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
                    <h5>R$ <?= number_format($fretesTotal, 2, ",", ".") ?></h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Diesel</small>
                    <h5>R$ <?= number_format($dieselValor, 2, ",", ".") ?></h5>
                    <small><?= number_format($dieselLitros, 2, ",", ".") ?> L</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">ARLA</small>
                    <h5>R$ <?= number_format($arlaValor, 2, ",", ".") ?></h5>
                    <small><?= number_format($arlaLitros, 2, ",", ".") ?> L</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Manutenção</small>
                    <h5>R$ <?= number_format($manutencaoTotal, 2, ",", ".") ?></h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Pagamentos</small>
                    <h5>R$ <?= number_format($pagamentosLiquido, 2, ",", ".") ?></h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Custo total</small>
                    <h5>R$ <?= number_format($custoTotal, 2, ",", ".") ?></h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Resultado estimado</small>
                    <h5 class="<?= $resultadoEstimado >= 0 ? 'text-success' : 'text-danger' ?>">
                        R$ <?= number_format($resultadoEstimado, 2, ",", ".") ?>
                    </h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Média km/L</small>
                    <h5><?= number_format($mediaKmL, 2, ",", ".") ?> km/L</h5>
                </div>
            </div>
        </div>

    </div>

    <div class="row mb-4">

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">KM rodado</small>
                    <h5><?= number_format($kmRodado, 0, ",", ".") ?> km</h5>
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

    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Resumo financeiro</h5>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <tbody>
                        <tr>
                            <th>Receita com fretes</th>
                            <td class="text-end">R$ <?= number_format($fretesTotal, 2, ",", ".") ?></td>
                        </tr>

                        <tr>
                            <th>Diesel</th>
                            <td class="text-end">R$ <?= number_format($dieselValor, 2, ",", ".") ?></td>
                        </tr>

                        <tr>
                            <th>ARLA</th>
                            <td class="text-end">R$ <?= number_format($arlaValor, 2, ",", ".") ?></td>
                        </tr>

                        <tr>
                            <th>Manutenções</th>
                            <td class="text-end">R$ <?= number_format($manutencaoTotal, 2, ",", ".") ?></td>
                        </tr>

                        <tr>
                            <th>Pagamentos</th>
                            <td class="text-end">R$ <?= number_format($pagamentosLiquido, 2, ",", ".") ?></td>
                        </tr>

                        <tr>
                            <th>Custo total</th>
                            <td class="text-end">R$ <?= number_format($custoTotal, 2, ",", ".") ?></td>
                        </tr>

                        <tr>
                            <th>Resultado estimado</th>
                            <td class="text-end">
                                <strong class="<?= $resultadoEstimado >= 0 ? 'text-success' : 'text-danger' ?>">
                                    R$ <?= number_format($resultadoEstimado, 2, ",", ".") ?>
                                </strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <small class="text-muted">
                Obs: pagamentos só entram neste relatório se estiverem vinculados à placa no lançamento.
            </small>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Detalhe dos fretes</h5>

            <?php if (count($detalhesFretes) === 0): ?>
                <p class="text-muted mb-0">Nenhum frete no período.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Data/Período</th>
                                <th>Cliente</th>
                                <th>Origem</th>
                                <th>Destino</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($detalhesFretes as $f): ?>
                                <tr>
                                    <td><?= htmlspecialchars($f["tipo_lancamento"]) ?></td>

                                    <td>
                                        <?php if ($f["tipo_lancamento"] === "resumido"): ?>
                                            <?= date("d/m/Y", strtotime($f["data_inicio"])) ?>
                                            até
                                            <?= date("d/m/Y", strtotime($f["data_fim"])) ?>
                                        <?php else: ?>
                                            <?= date("d/m/Y", strtotime($f["data_frete"])) ?>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= htmlspecialchars($f["cliente"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($f["origem"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($f["destino"] ?? "") ?></td>
                                    <td class="text-end">R$ <?= number_format((float)$f["valor"], 2, ",", ".") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Detalhe dos abastecimentos</h5>

            <?php if (count($detalhesAbastecimentos) === 0): ?>
                <p class="text-muted mb-0">Nenhum abastecimento no período.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Posto</th>
                                <th>Diesel L</th>
                                <th>Valor diesel</th>
                                <th>ARLA L</th>
                                <th>Valor ARLA</th>
                                <th>KM rodado</th>
                                <th>Média</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($detalhesAbastecimentos as $a): ?>
                                <tr>
                                    <td><?= date("d/m/Y", strtotime($a["data_abastecimento"])) ?></td>
                                    <td><?= htmlspecialchars($a["fornecedor"] ?? "") ?></td>
                                    <td><?= number_format((float)$a["litros"], 2, ",", ".") ?></td>
                                    <td>R$ <?= number_format((float)$a["valor_total"], 2, ",", ".") ?></td>
                                    <td><?= number_format((float)($a["arla_litros"] ?? 0), 2, ",", ".") ?></td>
                                    <td>R$ <?= number_format((float)($a["arla_valor_total"] ?? 0), 2, ",", ".") ?></td>

                                    <td>
                                        <?= $a["km_rodado"] !== null ? number_format((float)$a["km_rodado"], 0, ",", ".") . " km" : "-" ?>
                                    </td>

                                    <td>
                                        <?= $a["media_km_l"] !== null ? number_format((float)$a["media_km_l"], 2, ",", ".") . " km/L" : "-" ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Detalhe das manutenções</h5>

            <?php if (count($detalhesManutencoes) === 0): ?>
                <p class="text-muted mb-0">Nenhuma manutenção no período.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Serviço</th>
                                <th>Fornecedor</th>
                                <th>Mão obra</th>
                                <th>Peças</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($detalhesManutencoes as $m): ?>
                                <tr>
                                    <td><?= date("d/m/Y", strtotime($m["data_manutencao"])) ?></td>
                                    <td><?= htmlspecialchars($m["tipo_manutencao"]) ?></td>
                                    <td><?= htmlspecialchars($m["servico"]) ?></td>
                                    <td><?= htmlspecialchars($m["fornecedor"] ?? "") ?></td>
                                    <td>R$ <?= number_format((float)$m["valor_mao_obra"], 2, ",", ".") ?></td>
                                    <td>R$ <?= number_format((float)$m["valor_pecas"], 2, ",", ".") ?></td>
                                    <td><strong>R$ <?= number_format((float)$m["valor_total"], 2, ",", ".") ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Detalhe dos pagamentos vinculados à placa</h5>

            <?php if (count($detalhesPagamentos) === 0): ?>
                <p class="text-muted mb-0">Nenhum pagamento vinculado a esta placa no período.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Referência/Data</th>
                                <th>Motorista</th>
                                <th>Operação</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($detalhesPagamentos as $p): ?>
                                <tr>
                                    <td>
                                        <?php if ($p["tipo_lancamento"] === "mensal"): ?>
                                            <?= date("m/Y", strtotime($p["mes_referencia"])) ?>
                                        <?php else: ?>
                                            <?= date("d/m/Y", strtotime($p["data_pagamento"])) ?>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= htmlspecialchars($p["motorista"]) ?></td>
                                    <td><?= htmlspecialchars($p["operacao"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($p["tipo_pagamento"]) ?></td>
                                    <td><?= htmlspecialchars($p["status"]) ?></td>

                                    <td class="text-end">
                                        <?php if ($p["tipo_pagamento"] === "desconto"): ?>
                                            <span class="text-danger">
                                                - R$ <?= number_format((float)$p["valor"], 2, ",", ".") ?>
                                            </span>
                                        <?php else: ?>
                                            R$ <?= number_format((float)$p["valor"], 2, ",", ".") ?>
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

<?php endif; ?>

<?php require_once "../includes/footer.php"; ?>