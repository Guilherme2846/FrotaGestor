<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Editar Operação";

$id = (int) ($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM operacoes WHERE id = ?");
$stmt->execute([$id]);
$operacao = $stmt->fetch();

if (!$operacao) {
    die("Operação não encontrada.");
}

require_once "../includes/header.php";
?>

<h3>Editar operação</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="atualizar.php">
            <input type="hidden" name="id" value="<?= $operacao["id"] ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome da operação *</label>
                    <input
                        type="text"
                        name="nome"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($operacao["nome"]) ?>"
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Cliente</label>
                    <input
                        type="text"
                        name="cliente"
                        class="form-control"
                        value="<?= htmlspecialchars($operacao["cliente"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativa" <?= $operacao["status"] === "ativa" ? "selected" : "" ?>>
                            Ativa
                        </option>

                        <option value="inativa" <?= $operacao["status"] === "inativa" ? "selected" : "" ?>>
                            Inativa
                        </option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"><?= htmlspecialchars($operacao["observacao"] ?? "") ?></textarea>
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