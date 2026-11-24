<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Fretes";

$idVeiculo = $_GET["id_veiculo"] ?? "";
$idOperacao = $_GET["id_operacao"] ?? "";
$dataInicio = $_GET["data_inicio"] ?? "";
$dataFim = $_GET["data_fim"] ?? "";

$where = [];
$params = [];

if ($idVeiculo !== "") {
    $where[] = "f.id_veiculo = ?";
    $params[] = $idVeiculo;
}

if ($idOperacao !== "") {
    $where[] = "f.id_operacao = ?";
    $params[] = $idOperacao;
}

if ($dataInicio !== "") {
    $where[] = "COALESCE(f.data_frete, f.data_inicio) >= ?";
    $params[] = $dataInicio;
}

if ($dataFim !== "") {
    $where[] = "COALESCE(f.data_frete, f.data_fim, f.data_inicio) <= ?";
    $params[] = $dataFim;
}

$sqlWhere = "";

if (count($where) > 0) {
    $sqlWhere = "WHERE " . implode(" AND ", $where);
}

$stmt = $pdo->prepare("
    SELECT
        f.*,
        v.placa,
        v.modelo,
        m.nome AS motorista,
        o.nome AS operacao
    FROM fretes f
    INNER JOIN veiculos v ON v.id = f.id_veiculo
    LEFT JOIN motoristas m ON m.id = f.id_motorista
    LEFT JOIN operacoes o ON o.id = f.id_operacao
    $sqlWhere
    ORDER BY COALESCE(f.data_frete, f.data_inicio) DESC, f.id DESC
");

$stmt->execute($params);
$fretes = $stmt->fetchAll();

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

$totalFretes = 0;
$totalResumido = 0;
$totalDetalhado = 0;

foreach ($fretes as $f) {
    if ($f["status"] !== "cancelado") {
        $totalFretes += (float) $f["valor"];

        if ($f["tipo_lancamento"] === "resumido") {
            $totalResumido += (float) $f["valor"];
        } else {
            $totalDetalhado += (float) $f["valor"];
        }
    }
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Fretes</h3>

    <a href="cadastrar.php" class="btn btn-primary">
        Novo frete
    </a>
</div>

<form method="GET" class="card card-body shadow-sm mb-3">
    <div class="row">
        <div class="col-md-3 mb-2">
            <label class="form-label">Veículo</label>
            <select name="id_veiculo" class="form-select">
                <option value="">Todos</option>

                <?php foreach ($veiculos as $v): ?>
                    <option value="<?= $v["id"] ?>" <?= (string)$idVeiculo === (string)$v["id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($v["placa"] . " - " . $v["modelo"]) ?>
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
            <label class="form-label">Data inicial</label>
            <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($dataInicio) ?>">
        </div>

        <div class="col-md-2 mb-2">
            <label class="form-label">Data final</label>
            <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($dataFim) ?>">
        </div>

        <div class="col-md-2 mb-2 d-flex align-items-end">
            <button class="btn btn-dark w-100">
                Filtrar
            </button>
        </div>
    </div>
</form>

<div class="row mb-3">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total fretes</small>
                <h5>R$ <?= number_format($totalFretes, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total resumido</small>
                <h5>R$ <?= number_format($totalResumido, 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total detalhado</small>
                <h5>R$ <?= number_format($totalDetalhado, 2, ",", ".") ?></h5>
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

        <?php if (count($fretes) === 0): ?>
            <p class="text-muted mb-0">Nenhum frete encontrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Data/Período</th>
                            <th>Placa</th>
                            <th>Motorista</th>
                            <th>Operação</th>
                            <th>Cliente</th>
                            <th>Origem</th>
                            <th>Destino</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th width="150">Ações</th>
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
                                        <?= $f["data_inicio"] ? date("d/m/Y", strtotime($f["data_inicio"])) : "" ?>
                                        até
                                        <?= $f["data_fim"] ? date("d/m/Y", strtotime($f["data_fim"])) : "" ?>
                                    <?php else: ?>
                                        <?= $f["data_frete"] ? date("d/m/Y", strtotime($f["data_frete"])) : "" ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong><?= htmlspecialchars($f["placa"]) ?></strong><br>
                                    <small><?= htmlspecialchars($f["modelo"]) ?></small>
                                </td>

                                <td><?= htmlspecialchars($f["motorista"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["operacao"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["cliente"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["origem"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["destino"] ?? "") ?></td>

                                <td>
                                    R$ <?= number_format((float)$f["valor"], 2, ",", ".") ?>
                                </td>

                                <td>
                                    <?php if ($f["status"] === "lancado"): ?>
                                        <span class="badge bg-secondary">Lançado</span>
                                    <?php elseif ($f["status"] === "faturado"): ?>
                                        <span class="badge bg-warning text-dark">Faturado</span>
                                    <?php elseif ($f["status"] === "recebido"): ?>
                                        <span class="badge bg-success">Recebido</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="editar.php?id=<?= $f["id"] ?>" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <a
                                        href="excluir.php?id=<?= $f["id"] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Tem certeza que deseja excluir este frete?')"
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