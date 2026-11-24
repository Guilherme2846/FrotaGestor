<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Manutenções";

$idVeiculo = $_GET["id_veiculo"] ?? "";
$tipo = $_GET["tipo_manutencao"] ?? "";
$statusFiltro = $_GET["status"] ?? "";
$dataInicio = $_GET["data_inicio"] ?? "";
$dataFim = $_GET["data_fim"] ?? "";

$where = [];
$params = [];

if ($idVeiculo !== "") {
    $where[] = "m.id_veiculo = ?";
    $params[] = $idVeiculo;
}

if ($tipo !== "") {
    $where[] = "m.tipo_manutencao = ?";
    $params[] = $tipo;
}

if ($statusFiltro !== "") {
    $where[] = "m.status = ?";
    $params[] = $statusFiltro;
}

if ($dataInicio !== "") {
    $where[] = "m.data_manutencao >= ?";
    $params[] = $dataInicio;
}

if ($dataFim !== "") {
    $where[] = "m.data_manutencao <= ?";
    $params[] = $dataFim;
}

$sqlWhere = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT
        m.*,
        v.placa,
        v.modelo,
        f.nome AS fornecedor,
        o.nome AS operacao
    FROM manutencoes m
    INNER JOIN veiculos v ON v.id = m.id_veiculo
    LEFT JOIN fornecedores f ON f.id = m.id_fornecedor
    LEFT JOIN operacoes o ON o.id = m.id_operacao
    $sqlWhere
    ORDER BY m.data_manutencao DESC, m.id DESC
");

$stmt->execute($params);
$manutencoes = $stmt->fetchAll();

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$totalMaoObra = 0;
$totalPecas = 0;
$totalGeral = 0;
$totalPreventiva = 0;
$totalCorretiva = 0;

foreach ($manutencoes as $m) {
    if ($m["status"] === "cancelada") {
        continue;
    }

    $totalMaoObra += (float) $m["valor_mao_obra"];
    $totalPecas += (float) $m["valor_pecas"];
    $totalGeral += (float) $m["valor_total"];

    if ($m["tipo_manutencao"] === "preventiva") {
        $totalPreventiva += (float) $m["valor_total"];
    } else {
        $totalCorretiva += (float) $m["valor_total"];
    }
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Manutenções</h3>

    <a href="cadastrar.php" class="btn btn-primary">
        Nova manutenção
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

        <div class="col-md-2 mb-2">
            <label class="form-label">Tipo</label>
            <select name="tipo_manutencao" class="form-select">
                <option value="">Todos</option>
                <option value="preventiva" <?= $tipo === "preventiva" ? "selected" : "" ?>>Preventiva</option>
                <option value="corretiva" <?= $tipo === "corretiva" ? "selected" : "" ?>>Corretiva</option>
            </select>
        </div>

        <div class="col-md-2 mb-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <option value="aberta" <?= $statusFiltro === "aberta" ? "selected" : "" ?>>Aberta</option>
                <option value="andamento" <?= $statusFiltro === "andamento" ? "selected" : "" ?>>Andamento</option>
                <option value="concluida" <?= $statusFiltro === "concluida" ? "selected" : "" ?>>Concluída</option>
                <option value="cancelada" <?= $statusFiltro === "cancelada" ? "selected" : "" ?>>Cancelada</option>
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

        <div class="col-md-1 mb-2 d-flex align-items-end">
            <button class="btn btn-dark w-100">Filtrar</button>
        </div>
    </div>
</form>

<div class="row mb-3">
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total geral</small>
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
                <small class="text-muted">Preventiva / Corretiva</small>
                <h5>
                    R$ <?= number_format($totalPreventiva, 2, ",", ".") ?>
                    /
                    R$ <?= number_format($totalCorretiva, 2, ",", ".") ?>
                </h5>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET["sucesso"])): ?>
    <div class="alert alert-success">Operação realizada com sucesso.</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (count($manutencoes) === 0): ?>
            <p class="text-muted mb-0">Nenhuma manutenção encontrada.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Placa</th>
                            <th>Tipo</th>
                            <th>Serviço</th>
                            <th>Fornecedor</th>
                            <th>KM</th>
                            <th>Mão obra</th>
                            <th>Peças</th>
                            <th>Total</th>
                            <th>Próx. KM</th>
                            <th>Próx. data</th>
                            <th>Status</th>
                            <th width="230">Ações</th>
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

                                <td>
                                    <?= $m["km_manutencao"] !== null ? number_format((float)$m["km_manutencao"], 0, ",", ".") : "-" ?>
                                </td>

                                <td>R$ <?= number_format((float)$m["valor_mao_obra"], 2, ",", ".") ?></td>
                                <td>R$ <?= number_format((float)$m["valor_pecas"], 2, ",", ".") ?></td>
                                <td><strong>R$ <?= number_format((float)$m["valor_total"], 2, ",", ".") ?></strong></td>

                                <td>
                                    <?= $m["proxima_troca_km"] !== null ? number_format((float)$m["proxima_troca_km"], 0, ",", ".") : "-" ?>
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

                                <td>
                                    <a href="visualizar.php?id=<?= $m["id"] ?>" class="btn btn-sm btn-info text-white">
                                        Ver
                                    </a>

                                    <a href="editar.php?id=<?= $m["id"] ?>" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <a
                                        href="excluir.php?id=<?= $m["id"] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Tem certeza que deseja excluir esta manutenção?')"
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