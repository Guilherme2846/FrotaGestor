<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Editar Veículo";

$id = (int) ($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM veiculos WHERE id = ?");
$stmt->execute([$id]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    die("Veículo não encontrado.");
}

require_once "../includes/header.php";
?>

<h3>Editar veículo</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="atualizar.php">
            <input type="hidden" name="id" value="<?= $veiculo["id"] ?>">

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Placa *</label>
                    <input
                        type="text"
                        name="placa"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($veiculo["placa"]) ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Marca</label>
                    <input
                        type="text"
                        name="marca"
                        class="form-control"
                        value="<?= htmlspecialchars($veiculo["marca"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Modelo *</label>
                    <input
                        type="text"
                        name="modelo"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($veiculo["modelo"]) ?>"
                    >
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Ano</label>
                    <input
                        type="number"
                        name="ano"
                        class="form-control"
                        value="<?= htmlspecialchars($veiculo["ano"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo</label>
                    <input
                        type="text"
                        name="tipo"
                        class="form-control"
                        value="<?= htmlspecialchars($veiculo["tipo"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Eixos</label>
                    <input
                        type="text"
                        name="eixos"
                        class="form-control"
                        value="<?= htmlspecialchars($veiculo["eixos"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">KM atual</label>
                    <input
                        type="number"
                        step="0.01"
                        name="km_atual"
                        class="form-control"
                        value="<?= htmlspecialchars($veiculo["km_atual"]) ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativo" <?= $veiculo["status"] === "ativo" ? "selected" : "" ?>>
                            Ativo
                        </option>

                        <option value="manutencao" <?= $veiculo["status"] === "manutencao" ? "selected" : "" ?>>
                            Manutenção
                        </option>

                        <option value="inativo" <?= $veiculo["status"] === "inativo" ? "selected" : "" ?>>
                            Inativo
                        </option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"><?= htmlspecialchars($veiculo["observacao"] ?? "") ?></textarea>
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