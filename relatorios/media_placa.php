<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Média por Placa";

$idVeiculo = $_GET["id_veiculo"] ?? "";
$dataInicio = $_GET["data_inicio"] ?? date("Y-m-01");
$dataFim = $_GET["data_fim"] ?? date("Y-m-t");

$veiculos = $pdo->query("
    SELECT id, placa, marca, modelo, ano, tipo, eixos, km_atual
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$veiculoSelecionado = null;
$abastecimentos = [];
$mediasMes = [];

$totalAbastecimentos = 0;
$totalLitros = 0;
$totalValorDiesel = 0;
$totalArlaLitros = 0;
$totalArlaValor = 0;
$totalKmRodado = 0;
$totalLitrosComMedia = 0;

$mediaKmL = 0;
$valorMedioDiesel = 0;
$valorMedioArla = 0;

if ($idVeiculo !== "") {
    $stmt = $pdo->prepare("
        SELECT id, placa, marca, modelo, ano, tipo, eixos, km_atual
        FROM veiculos
        WHERE id = ?
    ");
    $stmt->execute([(int)$idVeiculo]);
    $veiculoSelecionado = $stmt->fetch();

    if ($veiculoSelecionado) {
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
        $abastecimentos = $stmt->fetchAll();

        foreach ($abastecimentos as $a) {
            $totalAbastecimentos++;
            $totalLitros += (float)$a["litros"];
            $totalValorDiesel += (float)$a["valor_total"];
            $totalArlaLitros += (float)($a["arla_litros"] ?? 0);
            $totalArlaValor += (float)($a["arla_valor_total"] ?? 0);

            if ($a["km_rodado"] !== null && $a["km_rodado"] > 0) {
                $totalKmRodado += (float)$a["km_rodado"];
                $totalLitrosComMedia += (float)$a["litros"];
            }
        }

        $mediaKmL = $totalLitrosComMedia > 0 ? $totalKmRodado / $totalLitrosComMedia : 0;
        $valorMedioDiesel = $totalLitros > 0 ? $totalValorDiesel / $totalLitros : 0;
        $valorMedioArla = $totalArlaLitros > 0 ? $totalArlaValor / $totalArlaLitros : 0;

        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(data_abastecimento, '%Y-%m') AS mes,
                COUNT(id) AS total_abastecimentos,

                COALESCE(SUM(litros), 0) AS litros_totais,
                COALESCE(SUM(valor_total), 0) AS valor_diesel,

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
            GROUP BY DATE_FORMAT(data_abastecimento, '%Y-%m')
            ORDER BY mes ASC
        ");

        $stmt->execute([$idVeiculo, $dataInicio, $dataFim]);
        $mediasMes = $stmt->fetchAll();
    }
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Média por Placa</h3>

    <?php if ($idVeiculo !== "" && $veiculoSelecionado): ?>
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

        <div class="col-md-2 mb-2 d-flex align-items-end">
            <button class="btn btn-dark w-100">Gerar</button>
        </div>
    </div>
</form>

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
                    <small class="text-muted">Média da placa</small>
                    <h5><?= number_format($mediaKmL, 2, ",", ".") ?> km/L</h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">KM rodado calculado</small>
                    <h5><?= number_format($totalKmRodado, 0, ",", ".") ?> km</h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Litros usados na média</small>
                    <h5><?= number_format($totalLitrosComMedia, 2, ",", ".") ?> L</h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Abastecimentos</small>
                    <h5><?= $totalAbastecimentos ?></h5>
                </div>
            </div>
        </div>

    </div>

    <div class="row mb-4">

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Litros diesel total</small>
                    <h5><?= number_format($totalLitros, 2, ",", ".") ?> L</h5>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Gasto diesel</small>
                    <h5>R$ <?= number_format($totalValorDiesel, 2, ",", ".") ?></h5>
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
                    <small class="text-muted">Custo diesel por KM</small>
                    <h5>
                        R$
                        <?= $totalKmRodado > 0 ? number_format($totalValorDiesel / $totalKmRodado, 2, ",", ".") : "0,00" ?>
                    </h5>
                </div>
            </div>
        </div>

    </div>

    <div class="row mb-4">

        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Litros ARLA</small>
                    <h5><?= number_format($totalArlaLitros, 2, ",", ".") ?> L</h5>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Gasto ARLA</small>
                    <h5>R$ <?= number_format($totalArlaValor, 2, ",", ".") ?></h5>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted">Valor médio ARLA</small>
                    <h5>R$ <?= number_format($valorMedioArla, 3, ",", ".") ?></h5>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Média da placa por mês</h5>

            <?php if (count($mediasMes) === 0): ?>
                <p class="text-muted mb-0">Nenhum dado encontrado.</p>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Abast.</th>
                                <th>KM rodado</th>
                                <th>Litros usados na média</th>
                                <th>Média km/L</th>
                                <th>Litros totais</th>
                                <th>Valor diesel</th>
                                <th>R$/L diesel</th>
                                <th>ARLA</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($mediasMes as $linha): ?>
                                <?php
                                    $km = (float)$linha["km_rodado"];
                                    $litrosMedia = (float)$linha["litros_com_media"];
                                    $litrosTotais = (float)$linha["litros_totais"];
                                    $valorDiesel = (float)$linha["valor_diesel"];
                                    $arlaValor = (float)$linha["arla_valor"];

                                    $mediaMes = $litrosMedia > 0 ? $km / $litrosMedia : 0;
                                    $valorMedioMes = $litrosTotais > 0 ? $valorDiesel / $litrosTotais : 0;
                                ?>

                                <tr>
                                    <td>
                                        <strong><?= date("m/Y", strtotime($linha["mes"] . "-01")) ?></strong>
                                    </td>

                                    <td><?= (int)$linha["total_abastecimentos"] ?></td>

                                    <td><?= number_format($km, 0, ",", ".") ?> km</td>

                                    <td><?= number_format($litrosMedia, 2, ",", ".") ?> L</td>

                                    <td>
                                        <?php if ($mediaMes > 0): ?>
                                            <strong><?= number_format($mediaMes, 2, ",", ".") ?> km/L</strong>
                                        <?php else: ?>
                                            <span class="text-muted">Sem cálculo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= number_format($litrosTotais, 2, ",", ".") ?> L</td>

                                    <td>R$ <?= number_format($valorDiesel, 2, ",", ".") ?></td>

                                    <td>R$ <?= number_format($valorMedioMes, 3, ",", ".") ?></td>

                                    <td>R$ <?= number_format($arlaValor, 2, ",", ".") ?></td>
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
            <h5>Detalhe dos abastecimentos da placa</h5>

            <?php if (count($abastecimentos) === 0): ?>
                <p class="text-muted mb-0">Nenhum abastecimento encontrado no período.</p>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Motorista</th>
                                <th>Operação</th>
                                <th>Posto</th>
                                <th>Diesel</th>
                                <th>Litros</th>
                                <th>Valor diesel</th>
                                <th>R$/L</th>
                                <th>ARLA L</th>
                                <th>Valor ARLA</th>
                                <th>KM anterior</th>
                                <th>KM atual</th>
                                <th>KM rodado</th>
                                <th>Média</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($abastecimentos as $a): ?>
                                <tr>
                                    <td><?= date("d/m/Y", strtotime($a["data_abastecimento"])) ?></td>
                                    <td><?= htmlspecialchars($a["motorista"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($a["operacao"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($a["fornecedor"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($a["tipo_diesel"]) ?></td>

                                    <td><?= number_format((float)$a["litros"], 2, ",", ".") ?> L</td>

                                    <td>R$ <?= number_format((float)$a["valor_total"], 2, ",", ".") ?></td>

                                    <td>
                                        R$ <?= number_format((float)$a["valor_litro"], 3, ",", ".") ?>
                                    </td>

                                    <td>
                                        <?= number_format((float)($a["arla_litros"] ?? 0), 2, ",", ".") ?> L
                                    </td>

                                    <td>
                                        R$ <?= number_format((float)($a["arla_valor_total"] ?? 0), 2, ",", ".") ?>
                                    </td>

                                    <td>
                                        <?= $a["km_anterior"] !== null ? number_format((float)$a["km_anterior"], 0, ",", ".") : "-" ?>
                                    </td>

                                    <td>
                                        <?= number_format((float)$a["km_atual"], 0, ",", ".") ?>
                                    </td>

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

<?php endif; ?>

<?php require_once "../includes/footer.php"; ?>