<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Veículos";

$busca = trim($_GET["busca"] ?? "");

if ($busca !== "") {
    $stmt = $pdo->prepare("
        SELECT *
        FROM veiculos
        WHERE placa LIKE ?
           OR modelo LIKE ?
           OR marca LIKE ?
           OR tipo LIKE ?
        ORDER BY placa ASC
    ");

    $termo = "%{$busca}%";
    $stmt->execute([$termo, $termo, $termo, $termo]);
} else {
    $stmt = $pdo->query("
        SELECT *
        FROM veiculos
        ORDER BY placa ASC
    ");
}

$veiculos = $stmt->fetchAll();

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Veículos</h3>

    <a href="cadastrar.php" class="btn btn-primary">
        Novo veículo
    </a>
</div>

<form method="GET" class="row mb-3">
    <div class="col-md-10">
        <input
            type="text"
            name="busca"
            class="form-control"
            placeholder="Buscar por placa, modelo, marca ou tipo"
            value="<?= htmlspecialchars($busca) ?>"
        >
    </div>

    <div class="col-md-2">
        <button class="btn btn-dark w-100">Buscar</button>
    </div>
</form>

<?php if (isset($_GET["sucesso"])): ?>
    <div class="alert alert-success">
        Operação realizada com sucesso.
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">

        <?php if (count($veiculos) === 0): ?>
            <p class="text-muted mb-0">Nenhum veículo cadastrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Ano</th>
                            <th>Tipo</th>
                            <th>Eixos</th>
                            <th>KM Atual</th>
                            <th>Status</th>
                            <th width="180">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($veiculos as $v): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($v["placa"]) ?></strong>
                                </td>

                                <td><?= htmlspecialchars($v["marca"] ?? "") ?></td>
                                <td><?= htmlspecialchars($v["modelo"]) ?></td>
                                <td><?= htmlspecialchars($v["ano"] ?? "") ?></td>
                                <td><?= htmlspecialchars($v["tipo"] ?? "") ?></td>
                                <td><?= htmlspecialchars($v["eixos"] ?? "") ?></td>

                                <td>
                                    <?= number_format((float)$v["km_atual"], 0, ",", ".") ?>
                                </td>

                                <td>
                                    <?php if ($v["status"] === "ativo"): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php elseif ($v["status"] === "manutencao"): ?>
                                        <span class="badge bg-warning text-dark">Manutenção</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="editar.php?id=<?= $v["id"] ?>" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <?php if ($v["status"] !== "inativo"): ?>
                                        <a
                                            href="inativar.php?id=<?= $v["id"] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Deseja inativar este veículo?')"
                                        >
                                            Inativar
                                        </a>
                                    <?php else: ?>
                                        <a
                                            href="reativar.php?id=<?= $v["id"] ?>"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Deseja reativar este veículo?')"
                                        >
                                            Reativar
                                        </a>
                                        <a
                                            href="excluir.php?id=<?= $v["id"] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Tem certeza que deseja EXCLUIR este veículo definitivamente?')"
                                        >
                                            Excluir
                                        </a>
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