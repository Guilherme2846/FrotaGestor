<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";
require_once "funcoes.php";

$tituloPagina = "Pagamentos";

$idMotorista = $_GET["id_motorista"] ?? "";
$idOperacao = $_GET["id_operacao"] ?? "";
$mesReferencia = $_GET["mes_referencia"] ?? "";
$statusFiltro = $_GET["status"] ?? "";

$where = [];
$params = [];

if ($idMotorista !== "") {
    $where[] = "p.id_motorista = ?";
    $params[] = $idMotorista;
}

if ($idOperacao !== "") {
    $where[] = "p.id_operacao = ?";
    $params[] = $idOperacao;
}

if ($mesReferencia !== "") {
    $where[] = "DATE_FORMAT(p.mes_referencia, '%Y-%m') = ?";
    $params[] = $mesReferencia;
}

if ($statusFiltro !== "") {
    $where[] = "p.status = ?";
    $params[] = $statusFiltro;
}

$sqlWhere = "";

if (count($where) > 0) {
    $sqlWhere = "WHERE " . implode(" AND ", $where);
}

$stmt = $pdo->prepare("
    SELECT
        p.*,
        m.nome AS motorista,
        v.placa,
        v.modelo,
        o.nome AS operacao
    FROM pagamentos p
    INNER JOIN motoristas m ON m.id = p.id_motorista
    LEFT JOIN veiculos v ON v.id = p.id_veiculo
    LEFT JOIN operacoes o ON o.id = p.id_operacao
    $sqlWhere
    ORDER BY COALESCE(p.mes_referencia, p.data_pagamento) DESC, p.id DESC
");

$stmt->execute($params);
$pagamentos = $stmt->fetchAll();

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

$totalPagamentos = 0;
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

foreach ($pagamentos as $p) {
    if ($p["status"] === "cancelado") {
        continue;
    }

    $valor = (float) $p["valor"];

    if ($p["tipo_pagamento"] === "desconto") {
        $totalDescontos += $valor;
        $totalLiquido -= $valor;
        continue;
    }

    $totalPagamentos += $valor;
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

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Pagamentos</h3>

    <a href="cadastrar.php" class="btn btn-primary">
        Novo pagamento
    </a>
</div>

<form method="GET" class="card card-body shadow-sm mb-3">
    <div class="row">
        <div class="col-md-3 mb-2">
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

        <div class="col-md-3 mb-2">
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
            <label class="form-label">Mês</label>
            <input type="month" name="mes_referencia" class="form-control" value="<?= htmlspecialchars($mesReferencia) ?>">
        </div>

        <div class="col-md-2 mb-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <option value="lancado" <?= $statusFiltro === "lancado" ? "selected" : "" ?>>Lançado</option>
                <option value="pago" <?= $statusFiltro === "pago" ? "selected" : "" ?>>Pago</option>
                <option value="cancelado" <?= $statusFiltro === "cancelado" ? "selected" : "" ?>>Cancelado</option>
            </select>
        </div>

        <div class="col-md-2 mb-2 d-flex align-items-end">
            <button class="btn btn-dark w-100">
                Filtrar
            </button>
        </div>
    </div>
</form>

<div class="row mb-3">

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total bruto</small>
                <h5>R$ <?= number_format($totalPagamentos, 2, ",", ".") ?></h5>
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
                <small class="text-muted">Total comissão</small>
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

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total bônus</small>
                <h5>R$ <?= number_format($totalBonus, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total líquido</small>
                <h5>R$ <?= number_format($totalLiquido, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

</div>
<?php if (isset($_GET["sucesso"])): ?>
    <div class="alert alert-success">
        Operação realizada com sucesso.
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">

        <?php if (count($pagamentos) === 0): ?>
            <p class="text-muted mb-0">Nenhum pagamento encontrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Referência/Data</th>
                            <th>Motorista</th>
                            <th>Placa</th>
                            <th>Operação</th>
                            <th>Tipo pagamento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Observação</th>
                            <th width="150">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($pagamentos as $p): ?>
                            <tr>
                                <td>
                                    <?php if ($p["tipo_lancamento"] === "mensal"): ?>
                                        <span class="badge bg-primary">Mensal</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Detalhado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($p["tipo_lancamento"] === "mensal"): ?>
                                        <?= $p["mes_referencia"] ? date("m/Y", strtotime($p["mes_referencia"])) : "" ?>
                                    <?php else: ?>
                                        <?= $p["data_pagamento"] ? date("d/m/Y", strtotime($p["data_pagamento"])) : "" ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong><?= htmlspecialchars($p["motorista"]) ?></strong>
                                </td>

                                <td>
                                    <?php if ($p["placa"]): ?>
                                        <strong><?= htmlspecialchars($p["placa"]) ?></strong><br>
                                        <small><?= htmlspecialchars($p["modelo"]) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($p["operacao"] ?? "") ?></td>

                                <td>
                                    <?= htmlspecialchars(nomeTipoPagamento($p["tipo_pagamento"])) ?>
                                </td>

                                <td>
                                    <?php if ($p["tipo_pagamento"] === "desconto"): ?>
                                        <span class="text-danger">
                                            - R$ <?= number_format((float)$p["valor"], 2, ",", ".") ?>
                                        </span>
                                    <?php else: ?>
                                        R$ <?= number_format((float)$p["valor"], 2, ",", ".") ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($p["status"] === "lancado"): ?>
                                        <span class="badge bg-secondary">Lançado</span>
                                    <?php elseif ($p["status"] === "pago"): ?>
                                        <span class="badge bg-success">Pago</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelado</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($p["observacao"] ?? "") ?></td>

                                <td>
                                    <a href="editar.php?id=<?= $p["id"] ?>" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <a
                                        href="excluir.php?id=<?= $p["id"] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Tem certeza que deseja excluir este pagamento?')"
                                    >
                                        Excluir
                                    </a>
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