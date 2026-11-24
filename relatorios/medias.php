<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Relatório de Médias";

$dataInicio = $_GET["data_inicio"] ?? date("Y-m-01");
$dataFim = $_GET["data_fim"] ?? date("Y-m-t");
$idOperacao = $_GET["id_operacao"] ?? "";

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    ORDER BY nome ASC
")->fetchAll();

$where = [
    "a.data_abastecimento >= ?",
    "a.data_abastecimento <= ?"
];

$params = [$dataInicio, $dataFim];

if ($idOperacao !== "") {
    $where[] = "a.id_operacao = ?";
    $params[] = $idOperacao;
}

$sqlWhere = "WHERE " . implode(" AND ", $where);

/*
    Média correta:
    KM rodado total / litros dos abastecimentos que possuem KM rodado calculado.
*/
$stmt = $pdo->prepare("
    SELECT
        v.id,
        v.placa,
        v.marca,
        v.modelo,

        COUNT(a.id) AS total_abastecimentos,

        COALESCE(SUM(a.litros), 0) AS litros_totais,
        COALESCE(SUM(a.valor_total), 0) AS valor_diesel,

        COALESCE(SUM(a.arla_litros), 0) AS arla_litros,
        COALESCE(SUM(a.arla_valor_total), 0) AS arla_valor,

        COALESCE(SUM(
            CASE
                WHEN a.km_rodado IS NOT NULL AND a.km_rodado > 0 THEN a.km_rodado
                ELSE 0
            END
        ), 0) AS km_rodado,

        COALESCE(SUM(
            CASE
                WHEN a.km_rodado IS NOT NULL AND a.km_rodado > 0 THEN a.litros
                ELSE 0
            END
        ), 0) AS litros_com_media

    FROM abastecimentos a
    INNER JOIN veiculos v ON v.id = a.id_veiculo
    $sqlWhere
    GROUP BY v.id, v.placa, v.marca, v.modelo
    ORDER BY v.placa ASC
");

$stmt->execute($params);
$mediasPlacas = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT
        v.marca,
        v.modelo,

        COUNT(DISTINCT v.id) AS qtd_veiculos,
        COUNT(a.id) AS total_abastecimentos,

        COALESCE(SUM(a.litros), 0) AS litros_totais,
        COALESCE(SUM(a.valor_total), 0) AS valor_diesel,

        COALESCE(SUM(
            CASE
                WHEN a.km_rodado IS NOT NULL AND a.km_rodado > 0 THEN a.km_rodado
                ELSE 0
            END
        ), 0) AS km_rodado,

        COALESCE(SUM(
            CASE
                WHEN a.km_rodado IS NOT NULL AND a.km_rodado > 0 THEN a.litros
                ELSE 0
            END
        ), 0) AS litros_com_media

    FROM abastecimentos a
    INNER JOIN veiculos v ON v.id = a.id_veiculo
    $sqlWhere
    GROUP BY v.marca, v.modelo
    ORDER BY v.modelo ASC
");

$stmt->execute($params);
$mediasModelos = $stmt->fetchAll();

$totalKm = 0;
$totalLitrosComMedia = 0;
$totalLitros = 0;
$totalValorDiesel = 0;
$totalArlaValor = 0;
$totalArlaLitros = 0;

foreach ($mediasPlacas as $linha) {
    $totalKm += (float) $linha["km_rodado"];
    $totalLitrosComMedia += (float) $linha["litros_com_media"];
    $totalLitros += (float) $linha["litros_totais"];
    $totalValorDiesel += (float) $linha["valor_diesel"];
    $totalArlaValor += (float) $linha["arla_valor"];
    $totalArlaLitros += (float) $linha["arla_litros"];
}

$mediaGeral = $totalLitrosComMedia > 0 ? $totalKm / $totalLitrosComMedia : 0;
$valorMedioDiesel = $totalLitros > 0 ? $totalValorDiesel / $totalLitros : 0;
$valorMedioArla = $totalArlaLitros > 0 ? $totalArlaValor / $totalArlaLitros : 0;

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Relatório de Médias</h3>

    <?php if (count($mediasPlacas) > 0): ?>
        <button type="button" class="btn btn-outline-dark no-print" onclick="window.print()">
            Imprimir relatório
        </button>
    <?php endif; ?>
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
        <h5 class="mb-1">Período analisado</h5>

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
                <small class="text-muted">Média geral</small>
                <h5><?= number_format($mediaGeral, 2, ",", ".") ?> km/L</h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">KM rodado calculado</small>
                <h5><?= number_format($totalKm, 0, ",", ".") ?> km</h5>
            </div>
        </div>
    </div>

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
                <small class="text-muted">Valor médio diesel</small>
                <h5>R$ <?= number_format($valorMedioDiesel, 3, ",", ".") ?></h5>
            </div>
        </div>
    </div>

</div>

<div class="row mb-4">

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
                <small class="text-muted">Litros ARLA</small>
                <h5><?= number_format($totalArlaLitros, 2, ",", ".") ?> L</h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Gasto ARLA</small>
                <h5>R$ <?= number_format($totalArlaValor, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
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
        <h5>Média por placa</h5>

        <?php if (count($mediasPlacas) === 0): ?>
            <p class="text-muted mb-0">Nenhum abastecimento encontrado no período.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
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
                        <?php foreach ($mediasPlacas as $linha): ?>
                            <?php
                                $kmRodado = (float) $linha["km_rodado"];
                                $litrosMedia = (float) $linha["litros_com_media"];
                                $litrosTotais = (float) $linha["litros_totais"];
                                $valorDiesel = (float) $linha["valor_diesel"];
                                $arlaValor = (float) $linha["arla_valor"];

                                $media = $litrosMedia > 0 ? $kmRodado / $litrosMedia : 0;
                                $valorMedio = $litrosTotais > 0 ? $valorDiesel / $litrosTotais : 0;
                            ?>

                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($linha["placa"]) ?></strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(($linha["marca"] ?? "") . " " . $linha["modelo"]) ?>
                                </td>

                                <td><?= (int) $linha["total_abastecimentos"] ?></td>

                                <td>
                                    <?= number_format($kmRodado, 0, ",", ".") ?> km
                                </td>

                                <td>
                                    <?= number_format($litrosMedia, 2, ",", ".") ?> L
                                </td>

                                <td>
                                    <?php if ($media > 0): ?>
                                        <strong><?= number_format($media, 2, ",", ".") ?> km/L</strong>
                                    <?php else: ?>
                                        <span class="text-muted">Sem cálculo</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= number_format($litrosTotais, 2, ",", ".") ?> L
                                </td>

                                <td>
                                    R$ <?= number_format($valorDiesel, 2, ",", ".") ?>
                                </td>

                                <td>
                                    R$ <?= number_format($valorMedio, 3, ",", ".") ?>
                                </td>

                                <td>
                                    R$ <?= number_format($arlaValor, 2, ",", ".") ?>
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
        <h5>Média por modelo</h5>

        <?php if (count($mediasModelos) === 0): ?>
            <p class="text-muted mb-0">Nenhum dado encontrado por modelo.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Modelo</th>
                            <th>Qtd. veículos</th>
                            <th>Abast.</th>
                            <th>KM rodado</th>
                            <th>Litros usados na média</th>
                            <th>Média km/L</th>
                            <th>Litros totais</th>
                            <th>Valor diesel</th>
                            <th>R$/L diesel</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($mediasModelos as $linha): ?>
                            <?php
                                $kmRodado = (float) $linha["km_rodado"];
                                $litrosMedia = (float) $linha["litros_com_media"];
                                $litrosTotais = (float) $linha["litros_totais"];
                                $valorDiesel = (float) $linha["valor_diesel"];

                                $media = $litrosMedia > 0 ? $kmRodado / $litrosMedia : 0;
                                $valorMedio = $litrosTotais > 0 ? $valorDiesel / $litrosTotais : 0;
                            ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars(($linha["marca"] ?? "") . " " . $linha["modelo"]) ?>
                                    </strong>
                                </td>

                                <td><?= (int) $linha["qtd_veiculos"] ?></td>
                                <td><?= (int) $linha["total_abastecimentos"] ?></td>

                                <td>
                                    <?= number_format($kmRodado, 0, ",", ".") ?> km
                                </td>

                                <td>
                                    <?= number_format($litrosMedia, 2, ",", ".") ?> L
                                </td>

                                <td>
                                    <?php if ($media > 0): ?>
                                        <strong><?= number_format($media, 2, ",", ".") ?> km/L</strong>
                                    <?php else: ?>
                                        <span class="text-muted">Sem cálculo</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= number_format($litrosTotais, 2, ",", ".") ?> L
                                </td>

                                <td>
                                    R$ <?= number_format($valorDiesel, 2, ",", ".") ?>
                                </td>

                                <td>
                                    R$ <?= number_format($valorMedio, 3, ",", ".") ?>
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