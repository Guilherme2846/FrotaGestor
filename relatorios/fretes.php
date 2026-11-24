<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Relatório de Fretes";

$dataInicio = $_GET["data_inicio"] ?? date("Y-m-01");
$dataFim = $_GET["data_fim"] ?? date("Y-m-t");

$idVeiculo = $_GET["id_veiculo"] ?? "";
$idMotorista = $_GET["id_motorista"] ?? "";
$idOperacao = $_GET["id_operacao"] ?? "";
$clienteFiltro = trim($_GET["cliente"] ?? "");
$tipoLancamento = $_GET["tipo_lancamento"] ?? "";
$statusFiltro = $_GET["status"] ?? "";

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$motoristas = $pdo->query("
    SELECT id, nome
    FROM motoristas
    ORDER BY nome ASC
")->fetchAll();

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    ORDER BY nome ASC
")->fetchAll();

$where = [
    "COALESCE(fr.data_frete, fr.data_inicio) >= ?",
    "COALESCE(fr.data_frete, fr.data_inicio) <= ?"
];

$params = [$dataInicio, $dataFim];

if ($idVeiculo !== "") {
    $where[] = "fr.id_veiculo = ?";
    $params[] = $idVeiculo;
}

if ($idMotorista !== "") {
    $where[] = "fr.id_motorista = ?";
    $params[] = $idMotorista;
}

if ($idOperacao !== "") {
    $where[] = "fr.id_operacao = ?";
    $params[] = $idOperacao;
}

if ($clienteFiltro !== "") {
    $where[] = "fr.cliente LIKE ?";
    $params[] = "%" . $clienteFiltro . "%";
}

if ($tipoLancamento !== "") {
    $where[] = "fr.tipo_lancamento = ?";
    $params[] = $tipoLancamento;
}

if ($statusFiltro !== "") {
    $where[] = "fr.status = ?";
    $params[] = $statusFiltro;
}

$sqlWhere = "WHERE " . implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT
        fr.*,
        v.placa,
        v.modelo AS veiculo_modelo,
        m.nome AS motorista,
        o.nome AS operacao
    FROM fretes fr
    INNER JOIN veiculos v ON v.id = fr.id_veiculo
    LEFT JOIN motoristas m ON m.id = fr.id_motorista
    LEFT JOIN operacoes o ON o.id = fr.id_operacao
    $sqlWhere
    ORDER BY COALESCE(fr.data_frete, fr.data_inicio) ASC, fr.id ASC
");

$stmt->execute($params);
$fretes = $stmt->fetchAll();

$totalGeral = 0;
$totalLancado = 0;
$totalFaturado = 0;
$totalRecebido = 0;
$totalCancelado = 0;

$qtdLancado = 0;
$qtdFaturado = 0;
$qtdRecebido = 0;
$qtdCancelado = 0;

$totalResumido = 0;
$totalDetalhado = 0;

foreach ($fretes as $f) {
    $valor = (float)$f["valor"];

    if ($f["status"] === "cancelado") {
        $totalCancelado += $valor;
        $qtdCancelado++;
        continue;
    }

    $totalGeral += $valor;

    if ($f["status"] === "lancado") {
        $totalLancado += $valor;
        $qtdLancado++;
    } elseif ($f["status"] === "faturado") {
        $totalFaturado += $valor;
        $qtdFaturado++;
    } elseif ($f["status"] === "recebido") {
        $totalRecebido += $valor;
        $qtdRecebido++;
    }

    if ($f["tipo_lancamento"] === "resumido") {
        $totalResumido += $valor;
    } else {
        $totalDetalhado += $valor;
    }
}

$stmt = $pdo->prepare("
    SELECT
        v.id,
        v.placa,
        v.modelo,

        COUNT(fr.id) AS qtd_fretes,

        COALESCE(SUM(
            CASE
                WHEN fr.status <> 'cancelado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS total,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'lancado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS lancado,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'faturado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS faturado,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'recebido' THEN fr.valor
                ELSE 0
            END
        ), 0) AS recebido

    FROM fretes fr
    INNER JOIN veiculos v ON v.id = fr.id_veiculo
    LEFT JOIN motoristas m ON m.id = fr.id_motorista
    LEFT JOIN operacoes o ON o.id = fr.id_operacao
    $sqlWhere
    GROUP BY v.id, v.placa, v.modelo
    ORDER BY total DESC
");

$stmt->execute($params);
$resumoPlacas = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.nome AS motorista,

        COUNT(fr.id) AS qtd_fretes,

        COALESCE(SUM(
            CASE
                WHEN fr.status <> 'cancelado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS total,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'lancado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS lancado,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'faturado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS faturado,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'recebido' THEN fr.valor
                ELSE 0
            END
        ), 0) AS recebido

    FROM fretes fr
    INNER JOIN veiculos v ON v.id = fr.id_veiculo
    LEFT JOIN motoristas m ON m.id = fr.id_motorista
    LEFT JOIN operacoes o ON o.id = fr.id_operacao
    $sqlWhere
    GROUP BY m.id, m.nome
    ORDER BY total DESC
");

$stmt->execute($params);
$resumoMotoristas = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.nome AS operacao,

        COUNT(fr.id) AS qtd_fretes,

        COALESCE(SUM(
            CASE
                WHEN fr.status <> 'cancelado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS total,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'lancado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS lancado,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'faturado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS faturado,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'recebido' THEN fr.valor
                ELSE 0
            END
        ), 0) AS recebido

    FROM fretes fr
    INNER JOIN veiculos v ON v.id = fr.id_veiculo
    LEFT JOIN motoristas m ON m.id = fr.id_motorista
    LEFT JOIN operacoes o ON o.id = fr.id_operacao
    $sqlWhere
    GROUP BY o.id, o.nome
    ORDER BY total DESC
");

$stmt->execute($params);
$resumoOperacoes = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT
        COALESCE(NULLIF(fr.cliente, ''), 'Não informado') AS cliente,

        COUNT(fr.id) AS qtd_fretes,

        COALESCE(SUM(
            CASE
                WHEN fr.status <> 'cancelado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS total,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'lancado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS lancado,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'faturado' THEN fr.valor
                ELSE 0
            END
        ), 0) AS faturado,

        COALESCE(SUM(
            CASE
                WHEN fr.status = 'recebido' THEN fr.valor
                ELSE 0
            END
        ), 0) AS recebido

    FROM fretes fr
    INNER JOIN veiculos v ON v.id = fr.id_veiculo
    LEFT JOIN motoristas m ON m.id = fr.id_motorista
    LEFT JOIN operacoes o ON o.id = fr.id_operacao
    $sqlWhere
    GROUP BY COALESCE(NULLIF(fr.cliente, ''), 'Não informado')
    ORDER BY total DESC
");

$stmt->execute($params);
$resumoClientes = $stmt->fetchAll();

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Relatório de Fretes</h3>

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
            <label class="form-label">Motorista</label>
            <select name="id_motorista" class="form-select">
                <option value="">Todos</option>

                <?php foreach ($motoristas as $m): ?>
                    <option value="<?= $m["id"] ?>" <?= (string)$idMotorista === (string)$m["id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($m["nome"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 mb-2">
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

        <div class="col-md-2 mb-2">
            <label class="form-label">Cliente</label>
            <input
                type="text"
                name="cliente"
                class="form-control"
                value="<?= htmlspecialchars($clienteFiltro) ?>"
                placeholder="Nome cliente"
            >
        </div>

        <div class="col-md-2 mb-2">
            <label class="form-label">Tipo</label>
            <select name="tipo_lancamento" class="form-select">
                <option value="">Todos</option>
                <option value="resumido" <?= $tipoLancamento === "resumido" ? "selected" : "" ?>>Resumido</option>
                <option value="detalhado" <?= $tipoLancamento === "detalhado" ? "selected" : "" ?>>Detalhado</option>
            </select>
        </div>

        <div class="col-md-2 mb-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <option value="lancado" <?= $statusFiltro === "lancado" ? "selected" : "" ?>>Lançado</option>
                <option value="faturado" <?= $statusFiltro === "faturado" ? "selected" : "" ?>>Faturado</option>
                <option value="recebido" <?= $statusFiltro === "recebido" ? "selected" : "" ?>>Recebido</option>
                <option value="cancelado" <?= $statusFiltro === "cancelado" ? "selected" : "" ?>>Cancelado</option>
            </select>
        </div>

        <div class="col-md-2 mb-2 d-flex align-items-end">
            <button class="btn btn-dark w-100">
                Gerar relatório
            </button>
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
                <small class="text-muted">Total válido</small>
                <h5>R$ <?= number_format($totalGeral, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Lançado</small>
                <h5>R$ <?= number_format($totalLancado, 2, ",", ".") ?></h5>
                <small><?= $qtdLancado ?> fretes</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Faturado</small>
                <h5>R$ <?= number_format($totalFaturado, 2, ",", ".") ?></h5>
                <small><?= $qtdFaturado ?> fretes</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Recebido</small>
                <h5>R$ <?= number_format($totalRecebido, 2, ",", ".") ?></h5>
                <small><?= $qtdRecebido ?> fretes</small>
            </div>
        </div>
    </div>

</div>

<div class="row mb-4">

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Cancelado</small>
                <h5>R$ <?= number_format($totalCancelado, 2, ",", ".") ?></h5>
                <small><?= $qtdCancelado ?> fretes</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Resumido</small>
                <h5>R$ <?= number_format($totalResumido, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Detalhado</small>
                <h5>R$ <?= number_format($totalDetalhado, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Quantidade total</small>
                <h5><?= count($fretes) ?> lançamentos</h5>
            </div>
        </div>
    </div>

</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Resumo por placa</h5>

        <?php if (count($resumoPlacas) === 0): ?>
            <p class="text-muted mb-0">Nenhum frete encontrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Qtd.</th>
                            <th>Total</th>
                            <th>Lançado</th>
                            <th>Faturado</th>
                            <th>Recebido</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($resumoPlacas as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($r["placa"]) ?></strong><br>
                                    <small><?= htmlspecialchars($r["modelo"]) ?></small>
                                </td>

                                <td><?= (int)$r["qtd_fretes"] ?></td>
                                <td><strong>R$ <?= number_format((float)$r["total"], 2, ",", ".") ?></strong></td>
                                <td>R$ <?= number_format((float)$r["lancado"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["faturado"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["recebido"], 2, ",", ".") ?></td>
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
        <h5>Resumo por motorista</h5>

        <?php if (count($resumoMotoristas) === 0): ?>
            <p class="text-muted mb-0">Nenhum frete encontrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Motorista</th>
                            <th>Qtd.</th>
                            <th>Total</th>
                            <th>Lançado</th>
                            <th>Faturado</th>
                            <th>Recebido</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($resumoMotoristas as $r): ?>
                            <tr>
                                <td>
                                    <?php if ($r["motorista"]): ?>
                                        <strong><?= htmlspecialchars($r["motorista"]) ?></strong>
                                    <?php else: ?>
                                        <strong class="text-warning">Sem motorista</strong>
                                    <?php endif; ?>
                                </td>

                                <td><?= (int)$r["qtd_fretes"] ?></td>
                                <td><strong>R$ <?= number_format((float)$r["total"], 2, ",", ".") ?></strong></td>
                                <td>R$ <?= number_format((float)$r["lancado"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["faturado"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["recebido"], 2, ",", ".") ?></td>
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
        <h5>Resumo por operação</h5>

        <?php if (count($resumoOperacoes) === 0): ?>
            <p class="text-muted mb-0">Nenhum frete encontrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Operação</th>
                            <th>Qtd.</th>
                            <th>Total</th>
                            <th>Lançado</th>
                            <th>Faturado</th>
                            <th>Recebido</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($resumoOperacoes as $r): ?>
                            <tr>
                                <td>
                                    <?php if ($r["operacao"]): ?>
                                        <strong><?= htmlspecialchars($r["operacao"]) ?></strong>
                                    <?php else: ?>
                                        <strong class="text-warning">Sem operação</strong>
                                    <?php endif; ?>
                                </td>

                                <td><?= (int)$r["qtd_fretes"] ?></td>
                                <td><strong>R$ <?= number_format((float)$r["total"], 2, ",", ".") ?></strong></td>
                                <td>R$ <?= number_format((float)$r["lancado"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["faturado"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["recebido"], 2, ",", ".") ?></td>
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
        <h5>Resumo por cliente</h5>

        <?php if (count($resumoClientes) === 0): ?>
            <p class="text-muted mb-0">Nenhum frete encontrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Qtd.</th>
                            <th>Total</th>
                            <th>Lançado</th>
                            <th>Faturado</th>
                            <th>Recebido</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($resumoClientes as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r["cliente"]) ?></strong></td>
                                <td><?= (int)$r["qtd_fretes"] ?></td>
                                <td><strong>R$ <?= number_format((float)$r["total"], 2, ",", ".") ?></strong></td>
                                <td>R$ <?= number_format((float)$r["lancado"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["faturado"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["recebido"], 2, ",", ".") ?></td>
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
        <h5>Detalhe dos fretes</h5>

        <?php if (count($fretes) === 0): ?>
            <p class="text-muted mb-0">Nenhum frete encontrado no período.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Data / Período</th>
                            <th>Placa</th>
                            <th>Motorista</th>
                            <th>Operação</th>
                            <th>Cliente</th>
                            <th>Origem</th>
                            <th>Destino</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Observação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($fretes as $f): ?>
                            <tr>
                                <td>
                                    <?php if ($f["tipo_lancamento"] === "resumido"): ?>
                                        <span class="badge bg-primary">Resumido</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Detalhado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($f["tipo_lancamento"] === "resumido"): ?>
                                        <?= $f["data_inicio"] ? date("d/m/Y", strtotime($f["data_inicio"])) : "-" ?>
                                        até
                                        <?= $f["data_fim"] ? date("d/m/Y", strtotime($f["data_fim"])) : "-" ?>
                                    <?php else: ?>
                                        <?= $f["data_frete"] ? date("d/m/Y", strtotime($f["data_frete"])) : "-" ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong><?= htmlspecialchars($f["placa"]) ?></strong><br>
                                    <small><?= htmlspecialchars($f["veiculo_modelo"]) ?></small>
                                </td>

                                <td><?= htmlspecialchars($f["motorista"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["operacao"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["cliente"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["origem"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["destino"] ?? "") ?></td>

                                <td>
                                    <?php if ($f["status"] === "recebido"): ?>
                                        <span class="badge bg-success">Recebido</span>
                                    <?php elseif ($f["status"] === "faturado"): ?>
                                        <span class="badge bg-primary">Faturado</span>
                                    <?php elseif ($f["status"] === "lancado"): ?>
                                        <span class="badge bg-secondary">Lançado</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>R$ <?= number_format((float)$f["valor"], 2, ",", ".") ?></strong>
                                </td>

                                <td><?= nl2br(htmlspecialchars($f["observacao"] ?? "")) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>