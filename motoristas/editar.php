<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Editar Motorista";

$id = (int) ($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM motoristas WHERE id = ?");
$stmt->execute([$id]);
$motorista = $stmt->fetch();

if (!$motorista) {
    die("Motorista não encontrado.");
}

require_once "../includes/header.php";
?>

<h3>Editar motorista</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="atualizar.php">
            <input type="hidden" name="id" value="<?= $motorista["id"] ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome *</label>
                    <input
                        type="text"
                        name="nome"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($motorista["nome"]) ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">CPF</label>
                    <input
                        type="text"
                        name="cpf"
                        class="form-control"
                        value="<?= htmlspecialchars($motorista["cpf"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Telefone</label>
                    <input
                        type="text"
                        name="telefone"
                        class="form-control"
                        value="<?= htmlspecialchars($motorista["telefone"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">CNH</label>
                    <input
                        type="text"
                        name="cnh"
                        class="form-control"
                        value="<?= htmlspecialchars($motorista["cnh"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Categoria CNH</label>
                    <select name="categoria_cnh" class="form-select">
                        <?php
                        $categorias = ["", "A", "B", "C", "D", "E", "AB", "AC", "AD", "AE"];
                        foreach ($categorias as $cat):
                        ?>
                            <option value="<?= $cat ?>" <?= $motorista["categoria_cnh"] === $cat ? "selected" : "" ?>>
                                <?= $cat === "" ? "Selecione" : $cat ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de vínculo</label>
                    <select name="tipo_vinculo" class="form-select">
                        <?php
                        $vinculos = ["", "Funcionário", "Agregado", "Terceiro", "Diarista", "Autônomo"];
                        foreach ($vinculos as $vinculo):
                        ?>
                            <option value="<?= $vinculo ?>" <?= $motorista["tipo_vinculo"] === $vinculo ? "selected" : "" ?>>
                                <?= $vinculo === "" ? "Selecione" : $vinculo ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativo" <?= $motorista["status"] === "ativo" ? "selected" : "" ?>>
                            Ativo
                        </option>

                        <option value="inativo" <?= $motorista["status"] === "inativo" ? "selected" : "" ?>>
                            Inativo
                        </option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"><?= htmlspecialchars($motorista["observacao"] ?? "") ?></textarea>
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