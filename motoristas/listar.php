<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Motoristas";

$busca = trim($_GET["busca"] ?? "");

if ($busca !== "") {
    $stmt = $pdo->prepare("
        SELECT *
        FROM motoristas
        WHERE nome LIKE ?
           OR cpf LIKE ?
           OR telefone LIKE ?
           OR cnh LIKE ?
           OR tipo_vinculo LIKE ?
        ORDER BY nome ASC
    ");

    $termo = "%{$busca}%";
    $stmt->execute([$termo, $termo, $termo, $termo, $termo]);
} else {
    $stmt = $pdo->query("
        SELECT *
        FROM motoristas
        ORDER BY nome ASC
    ");
}

$motoristas = $stmt->fetchAll();

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Motoristas</h3>

    <a href="cadastrar.php" class="btn btn-primary">
        Novo motorista
    </a>
</div>

<form method="GET" class="row mb-3">
    <div class="col-md-10">
        <input
            type="text"
            name="busca"
            class="form-control"
            placeholder="Buscar por nome, CPF, telefone, CNH ou vínculo"
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

        <?php if (count($motoristas) === 0): ?>
            <p class="text-muted mb-0">Nenhum motorista cadastrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>CNH</th>
                            <th>Categoria</th>
                            <th>Vínculo</th>
                            <th>Status</th>
                            <th width="180">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($motoristas as $m): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($m["nome"]) ?></strong>
                                </td>

                                <td><?= htmlspecialchars($m["cpf"] ?? "") ?></td>
                                <td><?= htmlspecialchars($m["telefone"] ?? "") ?></td>
                                <td><?= htmlspecialchars($m["cnh"] ?? "") ?></td>
                                <td><?= htmlspecialchars($m["categoria_cnh"] ?? "") ?></td>
                                <td><?= htmlspecialchars($m["tipo_vinculo"] ?? "") ?></td>

                                <td>
                                    <?php if ($m["status"] === "ativo"): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="editar.php?id=<?= $m["id"] ?>" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <?php if ($m["status"] !== "inativo"): ?>
                                        <a
                                            href="inativar.php?id=<?= $m["id"] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Deseja inativar este motorista?')"
                                        >
                                            Inativar
                                        </a>
                                    <?php else: ?>
                                        <a
                                            href="reativar.php?id=<?= $m["id"] ?>"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Deseja reativar este motorista?')"
                                        >
                                            Reativar
                                        </a>
                                        <a
                                            href="excluir.php?id=<?= $m["id"] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Tem certeza que deseja EXCLUIR este motorista definitivamente?')"
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