<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Editar Fornecedor";

$id = (int) ($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
$stmt->execute([$id]);
$fornecedor = $stmt->fetch();

if (!$fornecedor) {
    die("Fornecedor não encontrado.");
}

require_once "../includes/header.php";
?>

<h3>Editar fornecedor</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="atualizar.php">
            <input type="hidden" name="id" value="<?= $fornecedor["id"] ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome *</label>
                    <input
                        type="text"
                        name="nome"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($fornecedor["nome"]) ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" class="form-select" required>
                        <option value="posto" <?= $fornecedor["tipo"] === "posto" ? "selected" : "" ?>>
                            Posto
                        </option>

                        <option value="oficina" <?= $fornecedor["tipo"] === "oficina" ? "selected" : "" ?>>
                            Oficina
                        </option>

                        <option value="borracharia" <?= $fornecedor["tipo"] === "borracharia" ? "selected" : "" ?>>
                            Borracharia
                        </option>

                        <option value="pecas" <?= $fornecedor["tipo"] === "pecas" ? "selected" : "" ?>>
                            Peças
                        </option>

                        <option value="outro" <?= $fornecedor["tipo"] === "outro" ? "selected" : "" ?>>
                            Outro
                        </option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">CNPJ</label>
                    <input
                        type="text"
                        name="cnpj"
                        class="form-control"
                        value="<?= htmlspecialchars($fornecedor["cnpj"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Telefone</label>
                    <input
                        type="text"
                        name="telefone"
                        class="form-control"
                        value="<?= htmlspecialchars($fornecedor["telefone"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-5 mb-3">
                    <label class="form-label">Cidade</label>
                    <input
                        type="text"
                        name="cidade"
                        class="form-control"
                        value="<?= htmlspecialchars($fornecedor["cidade"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativo" <?= $fornecedor["status"] === "ativo" ? "selected" : "" ?>>
                            Ativo
                        </option>

                        <option value="inativo" <?= $fornecedor["status"] === "inativo" ? "selected" : "" ?>>
                            Inativo
                        </option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"><?= htmlspecialchars($fornecedor["observacao"] ?? "") ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Atualizar
            </button>

            <a href="listar.php" class="btn btn-secondary">
                Voltar
            </a>
        </form>

    </div>
</div>

<?php require_once "../includes/footer.php"; ?>