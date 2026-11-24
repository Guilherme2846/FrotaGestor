<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Relatório de Pagamentos";

$dataInicio = $_GET["data_inicio"] ?? date("Y-m-01");
$dataFim = $_GET["data_fim"] ?? date("Y-m-t");

$idMotorista = $_GET["id_motorista"] ?? "";
$idVeiculo = $_GET["id_veiculo"] ?? "";
$idOperacao = $_GET["id_operacao"] ?? "";
$tipoPagamento = $_GET["tipo_pagamento"] ?? "";
$statusFiltro = $_GET["status"] ?? "";

function nomeTipoPagamentoRelatorio($tipo)
{
    $tipos = [
        "salario" => "Salário",
        "diaria" => "Diária",
        "comissao" => "Comissão",
        "bonus" => "Bônus",
        "extra" => "Extra",
        "ajuda_custo" => "Ajuda de custo",
        "adiantamento" => "Adiantamento",
        "desconto" => "Desconto",
        "outros" => "Outros"
    ];

    return $tipos[$tipo] ?? $tipo;
}

$motoristas = $pdo->query("
    SELECT id, nome
    FROM motoristas
    ORDER BY nome ASC
")->fetchAll();

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    ORDER BY nome ASC
")->fetchAll();

$where = [
    "COALESCE(p.mes_referencia, p.data_pagamento) >= ?",
    "COALESCE(p.mes_referencia, p.data_pagamento) <= ?"
];

$params = [$dataInicio, $dataFim];

if ($idMotorista !== "") {
    $where[] = "p.id_motorista = ?";
    $params[] = $idMotorista;
}

if ($idVeiculo !== "") {
    $where[] = "p.id_veiculo = ?";
    $params[] = $idVeiculo;
}

if ($idOperacao !== "") {
    $where[] = "p.id_operacao = ?";
    $params[] = $idOperacao;
}

if ($tipoPagamento !== "") {
    $where[] = "p.tipo_pagamento = ?";
    $params[] = $tipoPagamento;
}

if ($statusFiltro !== "") {
    $where[] = "p.status = ?";
    $params[] = $statusFiltro;
}

$sqlWhere = "WHERE " . implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT
        p.*,
        m.nome AS motorista,
        v.placa,
        v.modelo AS veiculo_modelo,
        o.nome AS operacao
    FROM pagamentos p
    INNER JOIN motoristas m ON m.id = p.id_motorista
    LEFT JOIN veiculos v ON v.id = p.id_veiculo
    LEFT JOIN operacoes o ON o.id = p.id_operacao
    $sqlWhere
    ORDER BY COALESCE(p.mes_referencia, p.data_pagamento) ASC, m.nome ASC, p.id ASC
");

$stmt->execute($params);
$pagamentos = $stmt->fetchAll();

$totalBruto = 0;
$totalDescontos = 0;
$totalLiquido = 0;

$totalSalario = 0;
$totalDiaria = 0;
$totalComissao = 0;
$totalBonus = 0;
$totalExtra = 0;
$totalAjudaCusto = 0;
$totalAdiantamento = 0;
$totalOutros = 0;

$totalCancelados = 0;
$totalLancados = 0;
$totalPagos = 0;

foreach ($pagamentos as $p) {
    if ($p["status"] === "cancelado") {
        $totalCancelados++;
        continue;
    }

    if ($p["status"] === "lancado") {
        $totalLancados++;
    } elseif ($p["status"] === "pago") {
        $totalPagos++;
    }

    $valor = (float)$p["valor"];

    if ($p["tipo_pagamento"] === "desconto") {
        $totalDescontos += $valor;
        $totalLiquido -= $valor;
        continue;
    }

    $totalBruto += $valor;
    $totalLiquido += $valor;

    if ($p["tipo_pagamento"] === "salario") {
        $totalSalario += $valor;
    } elseif ($p["tipo_pagamento"] === "diaria") {
        $totalDiaria += $valor;
    } elseif ($p["tipo_pagamento"] === "comissao") {
        $totalComissao += $valor;
    } elseif ($p["tipo_pagamento"] === "bonus") {
        $totalBonus += $valor;
    } elseif ($p["tipo_pagamento"] === "extra") {
        $totalExtra += $valor;
    } elseif ($p["tipo_pagamento"] === "ajuda_custo") {
        $totalAjudaCusto += $valor;
    } elseif ($p["tipo_pagamento"] === "adiantamento") {
        $totalAdiantamento += $valor;
    } elseif ($p["tipo_pagamento"] === "outros") {
        $totalOutros += $valor;
    }
}

$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.nome AS motorista,

        COUNT(p.id) AS qtd_pagamentos,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento <> 'desconto' THEN p.valor
                ELSE 0
            END
        ), 0) AS bruto,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento = 'diaria' THEN p.valor
                ELSE 0
            END
        ), 0) AS diaria,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento = 'comissao' THEN p.valor
                ELSE 0
            END
        ), 0) AS comissao,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento = 'desconto' THEN p.valor
                ELSE 0
            END
        ), 0) AS descontos,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento = 'desconto' THEN -p.valor
                WHEN p.status <> 'cancelado' THEN p.valor
                ELSE 0
            END
        ), 0) AS liquido

    FROM pagamentos p
    INNER JOIN motoristas m ON m.id = p.id_motorista
    LEFT JOIN veiculos v ON v.id = p.id_veiculo
    LEFT JOIN operacoes o ON o.id = p.id_operacao
    $sqlWhere
    GROUP BY m.id, m.nome
    ORDER BY liquido DESC
");

$stmt->execute($params);
$resumoMotoristas = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT
        p.id_veiculo,
        v.placa,
        v.modelo,

        COUNT(p.id) AS qtd_pagamentos,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento <> 'desconto' THEN p.valor
                ELSE 0
            END
        ), 0) AS bruto,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento = 'diaria' THEN p.valor
                ELSE 0
            END
        ), 0) AS diaria,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento = 'comissao' THEN p.valor
                ELSE 0
            END
        ), 0) AS comissao,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento = 'desconto' THEN p.valor
                ELSE 0
            END
        ), 0) AS descontos,

        COALESCE(SUM(
            CASE
                WHEN p.status <> 'cancelado' AND p.tipo_pagamento = 'desconto' THEN -p.valor
                WHEN p.status <> 'cancelado' THEN p.valor
                ELSE 0
            END
        ), 0) AS liquido

    FROM pagamentos p
    INNER JOIN motoristas m ON m.id = p.id_motorista
    LEFT JOIN veiculos v ON v.id = p.id_veiculo
    LEFT JOIN operacoes o ON o.id = p.id_operacao
    $sqlWhere
    GROUP BY p.id_veiculo, v.placa, v.modelo
    ORDER BY liquido DESC
");

$stmt->execute($params);
$resumoPlacas = $stmt->fetchAll();

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Relatório de Pagamentos</h3>

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

        <div class="col-md-1 mb-2">
            <label class="form-label">Tipo</label>
            <select name="tipo_pagamento" class="form-select">
                <option value="">Todos</option>
                <option value="salario" <?= $tipoPagamento === "salario" ? "selected" : "" ?>>Salário</option>
                <option value="diaria" <?= $tipoPagamento === "diaria" ? "selected" : "" ?>>Diária</option>
                <option value="comissao" <?= $tipoPagamento === "comissao" ? "selected" : "" ?>>Comissão</option>
                <option value="bonus" <?= $tipoPagamento === "bonus" ? "selected" : "" ?>>Bônus</option>
                <option value="extra" <?= $tipoPagamento === "extra" ? "selected" : "" ?>>Extra</option>
                <option value="ajuda_custo" <?= $tipoPagamento === "ajuda_custo" ? "selected" : "" ?>>Ajuda</option>
                <option value="adiantamento" <?= $tipoPagamento === "adiantamento" ? "selected" : "" ?>>Adiant.</option>
                <option value="desconto" <?= $tipoPagamento === "desconto" ? "selected" : "" ?>>Desc.</option>
                <option value="outros" <?= $tipoPagamento === "outros" ? "selected" : "" ?>>Outros</option>
            </select>
        </div>

        <div class="col-md-1 mb-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <option value="lancado" <?= $statusFiltro === "lancado" ? "selected" : "" ?>>Lançado</option>
                <option value="pago" <?= $statusFiltro === "pago" ? "selected" : "" ?>>Pago</option>
                <option value="cancelado" <?= $statusFiltro === "cancelado" ? "selected" : "" ?>>Cancel.</option>
            </select>
        </div>

        <div class="col-md-12 mt-2">
            <button class="btn btn-dark">
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
                <small class="text-muted">Total bruto</small>
                <h5>R$ <?= number_format($totalBruto, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total diárias</small>
                <h5>R$ <?= number_format($totalDiaria, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total comissões</small>
                <h5>R$ <?= number_format($totalComissao, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total descontos</small>
                <h5>R$ <?= number_format($totalDescontos, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

</div>

<div class="row mb-3">



    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Bônus / Extra</small>
                <h5>
                    R$ <?= number_format($totalBonus, 2, ",", ".") ?>
                    /
                    R$ <?= number_format($totalExtra, 2, ",", ".") ?>
                </h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Ajuda / Adiantamento</small>
                <h5>
                    R$ <?= number_format($totalAjudaCusto, 2, ",", ".") ?>
                    /
                    R$ <?= number_format($totalAdiantamento, 2, ",", ".") ?>
                </h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total líquido</small>
                <h5 class="<?= $totalLiquido >= 0 ? 'text-success' : 'text-danger' ?>">
                    R$ <?= number_format($totalLiquido, 2, ",", ".") ?>
                </h5>
            </div>
        </div>
    </div>

</div>

<div class="row mb-4">

   

</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Resumo por motorista</h5>

        <?php if (count($resumoMotoristas) === 0): ?>
            <p class="text-muted mb-0">Nenhum pagamento encontrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Motorista</th>
                            <th>Qtd.</th>
                            <th>Bruto</th>
                            <th>Diárias</th>
                            <th>Comissões</th>
                            <th>Descontos</th>
                            <th>Líquido</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($resumoMotoristas as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r["motorista"]) ?></strong></td>
                                <td><?= (int)$r["qtd_pagamentos"] ?></td>
                                <td>R$ <?= number_format((float)$r["bruto"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["diaria"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["comissao"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["descontos"], 2, ",", ".") ?></td>

                                <td>
                                    <strong>
                                        R$ <?= number_format((float)$r["liquido"], 2, ",", ".") ?>
                                    </strong>
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
        <h5>Resumo por placa</h5>

        <?php if (count($resumoPlacas) === 0): ?>
            <p class="text-muted mb-0">Nenhum pagamento encontrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Qtd.</th>
                            <th>Bruto</th>
                            <th>Diárias</th>
                            <th>Comissões</th>
                            <th>Descontos</th>
                            <th>Líquido</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($resumoPlacas as $r): ?>
                            <tr>
                                <td>
                                    <?php if ($r["id_veiculo"]): ?>
                                        <strong><?= htmlspecialchars($r["placa"]) ?></strong><br>
                                        <small><?= htmlspecialchars($r["modelo"]) ?></small>
                                    <?php else: ?>
                                        <strong class="text-warning">Sem placa</strong>
                                    <?php endif; ?>
                                </td>

                                <td><?= (int)$r["qtd_pagamentos"] ?></td>
                                <td>R$ <?= number_format((float)$r["bruto"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["diaria"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["comissao"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$r["descontos"], 2, ",", ".") ?></td>

                                <td>
                                    <strong>
                                        R$ <?= number_format((float)$r["liquido"], 2, ",", ".") ?>
                                    </strong>
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
        <h5>Detalhe dos pagamentos</h5>

        <?php if (count($pagamentos) === 0): ?>
            <p class="text-muted mb-0">Nenhum pagamento encontrado no período.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle tabela-relatorio">
                    <thead>
                        <tr>
                            <th>Referência/Data</th>
                            <th>Motorista</th>
                            <th>Placa</th>
                            <th>Operação</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Observação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($pagamentos as $p): ?>
                            <tr>
                                <td>
                                    <?php if ($p["tipo_lancamento"] === "mensal"): ?>
                                        <?= $p["mes_referencia"] ? date("m/Y", strtotime($p["mes_referencia"])) : "-" ?>
                                    <?php else: ?>
                                        <?= $p["data_pagamento"] ? date("d/m/Y", strtotime($p["data_pagamento"])) : "-" ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong><?= htmlspecialchars($p["motorista"]) ?></strong>
                                </td>

                                <td>
                                    <?php if ($p["placa"]): ?>
                                        <?= htmlspecialchars($p["placa"]) ?><br>
                                        <small><?= htmlspecialchars($p["veiculo_modelo"]) ?></small>
                                    <?php else: ?>
                                        <span class="text-warning">Sem placa</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($p["operacao"] ?? "") ?></td>

                                <td><?= nomeTipoPagamentoRelatorio($p["tipo_pagamento"]) ?></td>

                                <td>
                                    <?php if ($p["status"] === "pago"): ?>
                                        <span class="badge bg-success">Pago</span>
                                    <?php elseif ($p["status"] === "lancado"): ?>
                                        <span class="badge bg-secondary">Lançado</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($p["tipo_pagamento"] === "desconto"): ?>
                                        <strong class="text-danger">
                                            - R$ <?= number_format((float)$p["valor"], 2, ",", ".") ?>
                                        </strong>
                                    <?php else: ?>
                                        <strong>
                                            R$ <?= number_format((float)$p["valor"], 2, ",", ".") ?>
                                        </strong>
                                    <?php endif; ?>
                                </td>

                                <td><?= nl2br(htmlspecialchars($p["observacao"] ?? "")) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>