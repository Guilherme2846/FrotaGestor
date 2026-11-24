<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Fornecedores";

$busca = trim($_GET["busca"] ?? "");

if ($busca !== "") {
    $stmt = $pdo->prepare("
        SELECT *
        FROM fornecedores
        WHERE nome LIKE ?
           OR tipo LIKE ?
           OR cnpj LIKE ?
           OR telefone LIKE ?
           OR cidade LIKE ?
        ORDER BY nome ASC
    ");

    $termo = "%{$busca}%";
    $stmt->execute([$termo, $termo, $termo, $termo, $termo]);
} else {
    $stmt = $pdo->query("
        SELECT *
        FROM fornecedores
        ORDER BY nome ASC
    ");
}

$fornecedores = $stmt->fetchAll();

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Fornecedores</h3>

    <a href="cadastrar.php" class="btn btn-primary">
        Novo fornecedor
    </a>
</div>

<form method="GET" class="row mb-3">
    <div class="col-md-10">
        <input
            type="text"
            name="busca"
            class="form-control"
            placeholder="Buscar por nome, tipo, CNPJ, telefone ou cidade"
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

        <?php if (count($fornecedores) === 0): ?>
            <p class="text-muted mb-0">Nenhum fornecedor cadastrado.</p>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>CNPJ</th>
                            <th>Telefone</th>
                            <th>Cidade</th>
                            <th>Status</th>
                            <th width="270">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($fornecedores as $f): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($f["nome"]) ?></strong>
                                </td>

                                <td>
                                    <?php
                                        $tipos = [
                                            "posto" => "Posto",
                                            "oficina" => "Oficina",
                                            "borracharia" => "Borracharia",
                                            "pecas" => "Peças",
                                            "outro" => "Outro"
                                        ];

                                        echo htmlspecialchars($tipos[$f["tipo"]] ?? $f["tipo"]);
                                    ?>
                                </td>

                                <td><?= htmlspecialchars($f["cnpj"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["telefone"] ?? "") ?></td>
                                <td><?= htmlspecialchars($f["cidade"] ?? "") ?></td>

                                <td>
                                    <?php if ($f["status"] === "ativo"): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="editar.php?id=<?= $f["id"] ?>" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <?php if ($f["status"] !== "inativo"): ?>
                                        <a
                                            href="inativar.php?id=<?= $f["id"] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Deseja inativar este fornecedor?')"
                                        >
                                            Inativar
                                        </a>
                                    <?php else: ?>
                                        <a
                                            href="reativar.php?id=<?= $f["id"] ?>"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Deseja reativar este fornecedor?')"
                                        >
                                            Reativar
                                        </a>
                                    <?php endif; ?>

                                    <a
                                        href="excluir.php?id=<?= $f["id"] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Tem certeza que deseja EXCLUIR este fornecedor definitivamente?')"
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