<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Relatório de Manutenções";

$dataInicio = $_GET["data_inicio"] ?? date("Y-m-01");
$dataFim = $_GET["data_fim"] ?? date("Y-m-t");
$idVeiculo = $_GET["id_veiculo"] ?? "";
$idFornecedor = $_GET["id_fornecedor"] ?? "";
$tipoManutencao = $_GET["tipo_manutencao"] ?? "";
$statusFiltro = $_GET["status"] ?? "";

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$fornecedores = $pdo->query("
    SELECT id, nome, tipo
    FROM fornecedores
    WHERE tipo IN ('oficina', 'pecas', 'borracharia', 'outro')
    ORDER BY nome ASC
")->fetchAll();

$where = [
    "m.data_manutencao >= ?",
    "m.data_manutencao <= ?"
];

$params = [$dataInicio, $dataFim];

if ($idVeiculo !== "") {
    $where[] = "m.id_veiculo = ?";
    $params[] = $idVeiculo;
}

if ($idFornecedor !== "") {
    $where[] = "m.id_fornecedor = ?";
    $params[] = $idFornecedor;
}

if ($tipoManutencao !== "") {
    $where[] = "m.tipo_manutencao = ?";
    $params[] = $tipoManutencao;
}

if ($statusFiltro !== "") {
    $where[] = "m.status = ?";
    $params[] = $statusFiltro;
}

$sqlWhere = "WHERE " . implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT
        m.*,
        v.placa,
        v.modelo,
        v.marca,
        v.km_atual,
        f.nome AS fornecedor,
        o.nome AS operacao
    FROM manutencoes m
    INNER JOIN veiculos v ON v.id = m.id_veiculo
    LEFT JOIN fornecedores f ON f.id = m.id_fornecedor
    LEFT JOIN operacoes o ON o.id = m.id_operacao
    $sqlWhere
    ORDER BY m.data_manutencao ASC, m.id ASC
");

$stmt->execute($params);
$manutencoes = $stmt->fetchAll();

$totalGeral = 0;
$totalMaoObra = 0;
$totalPecas = 0;
$totalPreventiva = 0;
$totalCorretiva = 0;
$totalAbertas = 0;
$totalAndamento = 0;
$totalConcluidas = 0;
$totalCanceladas = 0;

foreach ($manutencoes as $m) {
    if ($m["status"] === "cancelada") {
        $totalCanceladas++;
        continue;
    }

    $totalGeral += (float)$m["valor_total"];
    $totalMaoObra += (float)$m["valor_mao_obra"];
    $totalPecas += (float)$m["valor_pecas"];

    if ($m["tipo_manutencao"] === "preventiva") {
        $totalPreventiva += (float)$m["valor_total"];
    } else {
        $totalCorretiva += (float)$m["valor_total"];
    }

    if ($m["status"] === "aberta") {
        $totalAbertas++;
    } elseif ($m["status"] === "andamento") {
        $totalAndamento++;
    } elseif ($m["status"] === "concluida") {
        $totalConcluidas++;
    }
}

$stmt = $pdo->prepare("
    SELECT
        v.id,
        v.placa,
        v.marca,
        v.modelo,

        COUNT(m.id) AS qtd_manutencoes,

        COALESCE(SUM(
            CASE
                WHEN m.status <> 'cancelada' THEN m.valor_mao_obra
                ELSE 0
            END
        ), 0) AS mao_obra,

        COALESCE(SUM(
            CASE
                WHEN m.status <> 'cancelada' THEN m.valor_pecas
                ELSE 0
            END
        ), 0) AS pecas,

        COALESCE(SUM(
            CASE
                WHEN m.status <> 'cancelada' THEN m.valor_total
                ELSE 0
            END
        ), 0) AS total,

        COALESCE(SUM(
            CASE
                WHEN m.status <> 'cancelada' AND m.tipo_manutencao = 'preventiva' THEN m.valor_total
                ELSE 0
            END
        ), 0) AS preventiva,

        COALESCE(SUM(
            CASE
                WHEN m.status <> 'cancelada' AND m.tipo_manutencao = 'corretiva' THEN m.valor_total
                ELSE 0
            END
        ), 0) AS corretiva

    FROM manutencoes m
    INNER JOIN veiculos v ON v.id = m.id_veiculo
    $sqlWhere
    GROUP BY v.id, v.placa, v.marca, v.modelo
    ORDER BY total DESC
");

$stmt->execute($params);
$resumoPlacas = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT
        m.*,
        v.placa,
        v.modelo,
        v.marca,
        v.km_atual,
        f.nome AS fornecedor
    FROM manutencoes m
    INNER JOIN veiculos v ON v.id = m.id_veiculo
    LEFT JOIN fornecedores f ON f.id = m.id_fornecedor
    WHERE m.status <> 'cancelada'
      AND m.tipo_manutencao = 'preventiva'
      AND (
            (m.proxima_troca_km IS NOT NULL AND m.proxima_troca_km <= v.km_atual + 1000)
            OR
            (m.proxima_troca_data IS NOT NULL AND m.proxima_troca_data <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY))
      )
    ORDER BY
        m.proxima_troca_data ASC,
        m.proxima_troca_km ASC
");

$alertasPreventiva = $stmt->fetchAll();

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Relatório de Manutenções</h3>

    <button type="button" class="btn btn-outline-dark no-print" onclick="window.print()">
        Imprimir relatório
    </button>
</div>

<form method="GET" class="card card-body shadow-sm mb-4">
    <div class="row">
        <div class="col-md-2 mb-2">
            <label class="form-label">Data inicial</label>
            <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($dataInicio) ?>">
        </div>

        <div class="col-md-2 mb-2">
            <label class="form-label">Data final</label>
            <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($dataFim) ?>">
        </div>

        <div class="col-md-2 mb-2">
            <label class="form-label">Placa</label>
            <select name="id_veiculo" class="form-select">
                <option value="">Todas</option>

                <?php foreach ($veiculos as $v): ?>
                    <option value="<?= $v["id"] ?>" <?= (string)$idVeiculo === (string)$v["id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($v["placa"] . " - " . $v["modelo"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 mb-2">
            <label class="form-label">Fornecedor</label>
            <select name="id_fornecedor" class="form-select">
                <option value="">Todos</option>

                <?php foreach ($fornecedores as $f): ?>
                    <option value="<?= $f["id"] ?>" <?= (string)$idFornecedor === (string)$f["id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($f["nome"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-1 mb-2">
            <label class="form-label">Tipo</label>
            <select name="tipo_manutencao" class="form-select">
                <option value="">Todos</option>
                <option value="preventiva" <?= $tipoManutencao === "preventiva" ? "selected" : "" ?>>Preventiva</option>
                <option value="corretiva" <?= $tipoManutencao === "corretiva" ? "selected" : "" ?>>Corretiva</option>
            </select>
        </div>

        <div class="col-md-1 mb-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <option value="aberta" <?= $statusFiltro === "aberta" ? "selected" : "" ?>>Aberta</option>
                <option value="andamento" <?= $statusFiltro === "andamento" ? "selected" : "" ?>>Andamento</option>
                <option value="concluida" <?= $statusFiltro === "concluida" ? "selected" : "" ?>>Concluída</option>
                <option value="cancelada" <?= $statusFiltro === "cancelada" ? "selected" : "" ?>>Cancelada</option>
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
                <small class="text-muted">Total manutenção</small>
                <h5>R$ <?= number_format($totalGeral, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Mão de obra</small>
                <h5>R$ <?= number_format($totalMaoObra, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Peças</small>
                <h5>R$ <?= number_format($totalPecas, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Quantidade</small>
                <h5><?= count($manutencoes) ?> lançamentos</h5>
            </div>
        </div>
    </div>

</div>

<div class="row mb-4">

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Preventivas</small>
                <h5>R$ <?= number_format($totalPreventiva, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Corretivas</small>
                <h5>R$ <?= number_format($totalCorretiva, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Abertas / Andamento</small>
                <h5><?= $totalAbertas ?> / <?= $totalAndamento ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Concluídas / Canceladas</small>
                <h5><?= $totalConcluidas ?> / <?= $totalCanceladas ?></h5>
            </div>
        </div>
    </div>

</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Resumo por placa</h5>

        <?php if (count($resumoPlacas) === 0): ?>
            <p class="text-muted mb-0">Nenhuma manutenção encontrada no período.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Qtd.</th>
                            <th>Mão de obra</th>
                            <th>Peças</th>
                            <th>Total</th>
                            <th>Preventiva</th>
                            <th>Corretiva</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($resumoPlacas as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r["placa"]) ?></strong></td>
                                <td><?= htmlspecialchars(($r["marca"] ?? "") . " " . $r["modelo"]) ?></td>
                                <td><?= (int)$r["qtd_manutencoes"] ?></td>
                                <td>R$ <?= number_format((float)$r["mao_obra"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["pecas"], 2, ",", ".") ?></td>
                                <td><strong>R$ <?= number_format((float)$r["total"], 2, ",", ".") ?></strong></td>
                                <td>R$ <?= number_format((float)$r["preventiva"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["corretiva"], 2, ",", ".") ?></td>
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
        <h5>Preventivas vencidas ou próximas</h5>

        <p class="text-muted">
            Considera preventiva vencida ou próxima quando faltar até 1.000 km ou até 30 dias.
        </p>

        <?php if (count($alertasPreventiva) === 0): ?>
            <p class="text-muted mb-0">Nenhuma preventiva vencida ou próxima encontrada.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Serviço</th>
                            <th>KM atual</th>
                            <th>Próx. KM</th>
                            <th>Falta KM</th>
                            <th>Próx. data</th>
                            <th>Status alerta</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($alertasPreventiva as $a): ?>
                            <?php
                                $kmAtual = (float)$a["km_atual"];
                                $proxKm = $a["proxima_troca_km"] !== null ? (float)$a["proxima_troca_km"] : null;
                                $faltaKm = $proxKm !== null ? $proxKm - $kmAtual : null;

                                $dataVencida = false;
                                $kmVencido = false;

                                if ($a["proxima_troca_data"] !== null && $a["proxima_troca_data"] <= date("Y-m-d")) {
                                    $dataVencida = true;
                                }

                                if ($proxKm !== null && $proxKm <= $kmAtual) {
                                    $kmVencido = true;
                                }

                                $vencido = $dataVencida || $kmVencido;
                            ?>

                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($a["placa"]) ?></strong><br>
                                    <small><?= htmlspecialchars($a["modelo"]) ?></small>
                                </td>

                                <td><?= htmlspecialchars($a["servico"]) ?></td>

                                <td><?= number_format($kmAtual, 0, ",", ".") ?> km</td>

                                <td>
                                    <?= $proxKm !== null ? number_format($proxKm, 0, ",", ".") . " km" : "-" ?>
                                </td>

                                <td>
                                    <?php if ($faltaKm !== null): ?>
                                        <?php if ($faltaKm <= 0): ?>
                                            <span class="text-danger">
                                                Venceu <?= number_format(abs($faltaKm), 0, ",", ".") ?> km
                                            </span>
                                        <?php else: ?>
                                            <?= number_format($faltaKm, 0, ",", ".") ?> km
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= $a["proxima_troca_data"] ? date("d/m/Y", strtotime($a["proxima_troca_data"])) : "-" ?>
                                </td>

                                <td>
                                    <?php if ($vencido): ?>
                                        <span class="badge bg-danger">Vencida</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Próxima</span>
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

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Detalhe das manutenções</h5>

        <?php if (count($manutencoes) === 0): ?>
            <p class="text-muted mb-0">Nenhuma manutenção encontrada no período.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Placa</th>
                            <th>Tipo</th>
                            <th>Serviço</th>
                            <th>Fornecedor</th>
                            <th>Operação</th>
                            <th>KM</th>
                            <th>Mão obra</th>
                            <th>Peças</th>
                            <th>Total</th>
                            <th>Próx. KM</th>
                            <th>Próx. data</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($manutencoes as $m): ?>
                            <tr>
                                <td><?= date("d/m/Y", strtotime($m["data_manutencao"])) ?></td>

                                <td>
                                    <strong><?= htmlspecialchars($m["placa"]) ?></strong><br>
                                    <small><?= htmlspecialchars($m["modelo"]) ?></small>
                                </td>

                                <td>
                                    <?php if ($m["tipo_manutencao"] === "preventiva"): ?>
                                        <span class="badge bg-primary">Preventiva</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Corretiva</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($m["servico"]) ?></td>
                                <td><?= htmlspecialchars($m["fornecedor"] ?? "") ?></td>
                                <td><?= htmlspecialchars($m["operacao"] ?? "") ?></td>

                                <td>
                                    <?= $m["km_manutencao"] !== null ? number_format((float)$m["km_manutencao"], 0, ",", ".") . " km" : "-" ?>
                                </td>

                                <td>R$ <?= number_format((float)$m["valor_mao_obra"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$m["valor_pecas"], 2, ",", ".") ?></td>

                                <td>
                                    <strong>R$ <?= number_format((float)$m["valor_total"], 2, ",", ".") ?></strong>
                                </td>

                                <td>
                                    <?= $m["proxima_troca_km"] !== null ? number_format((float)$m["proxima_troca_km"], 0, ",", ".") . " km" : "-" ?>
                                </td>

                                <td>
                                    <?= $m["proxima_troca_data"] ? date("d/m/Y", strtotime($m["proxima_troca_data"])) : "-" ?>
                                </td>

                                <td>
                                    <?php if ($m["status"] === "concluida"): ?>
                                        <span class="badge bg-success">Concluída</span>
                                    <?php elseif ($m["status"] === "andamento"): ?>
                                        <span class="badge bg-info text-dark">Andamento</span>
                                    <?php elseif ($m["status"] === "aberta"): ?>
                                        <span class="badge bg-secondary">Aberta</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelada</span>
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