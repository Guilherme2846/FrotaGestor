<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Operações";

$busca = trim($_GET["busca"] ?? "");

if ($busca !== "") {
    $stmt = $pdo->prepare("
        SELECT *
        FROM operacoes
        WHERE nome LIKE ?
           OR cliente LIKE ?
           OR status LIKE ?
        ORDER BY nome ASC
    ");

    $termo = "%{$busca}%";
    $stmt->execute([$termo, $termo, $termo]);
} else {
    $stmt = $pdo->query("
        SELECT *
        FROM operacoes
        ORDER BY nome ASC
    ");
}

$operacoes = $stmt->fetchAll();

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Operações</h3>

    <a href="cadastrar.php" class="btn btn-primary">
        Nova operação
    </a>
</div>

<form method="GET" class="row mb-3">
    <div class="col-md-10">
        <input
            type="text"
            name="busca"
            class="form-control"
            placeholder="Buscar por operação, cliente ou status"
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

        <?php if (count($operacoes) === 0): ?>
            <p class="text-muted mb-0">Nenhuma operação cadastrada.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Operação</th>
                            <th>Cliente</th>
                            <th>Status</th>
                            <th>Observação</th>
                            <th width="260">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($operacoes as $op): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($op["nome"]) ?></strong>
                                </td>

                                <td><?= htmlspecialchars($op["cliente"] ?? "") ?></td>

                                <td>
                                    <?php if ($op["status"] === "ativa"): ?>
                                        <span class="badge bg-success">Ativa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativa</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($op["observacao"] ?? "") ?></td>

                                <td>
                                    <a href="editar.php?id=<?= $op["id"] ?>" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <?php if ($op["status"] !== "inativa"): ?>
                                        <a
                                            href="inativar.php?id=<?= $op["id"] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Deseja inativar esta operação?')"
                                        >
                                            Inativar
                                        </a>
                                    <?php else: ?>
                                        <a
                                            href="reativar.php?id=<?= $op["id"] ?>"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Deseja reativar esta operação?')"
                                        >
                                            Reativar
                                        </a>
                                    <?php endif; ?>

                                    <a
                                        href="excluir.php?id=<?= $op["id"] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Tem certeza que deseja EXCLUIR esta operação definitivamente?')"
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