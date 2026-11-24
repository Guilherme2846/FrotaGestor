<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Editar Frete";

$id = (int) ($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM fretes WHERE id = ?");
$stmt->execute([$id]);
$frete = $stmt->fetch();

if (!$frete) {
    die("Frete não encontrado.");
}

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$motoristas = $pdo->query("
    SELECT id, nome
    FROM motoristas
    ORDER BY nome ASC
")->fetchAll();

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    ORDER BY nome ASC
")->fetchAll();

require_once "../includes/header.php";
?>

<h3>Editar frete</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="atualizar.php">
            <input type="hidden" name="id" value="<?= $frete["id"] ?>">

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de lançamento *</label>
                    <select name="tipo_lancamento" id="tipo_lancamento" class="form-select" required>
                        <option value="resumido" <?= $frete["tipo_lancamento"] === "resumido" ? "selected" : "" ?>>
                            Resumido por período
                        </option>

                        <option value="detalhado" <?= $frete["tipo_lancamento"] === "detalhado" ? "selected" : "" ?>>
                            Detalhado por frete
                        </option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Veículo *</label>
                    <select name="id_veiculo" class="form-select" required>
                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= $v["id"] ?>" <?= $frete["id_veiculo"] == $v["id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($v["placa"] . " - " . $v["modelo"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5 mb-3">
                    <label class="form-label">Motorista</label>
                    <select name="id_motorista" class="form-select">
                        <option value="">Não informado</option>

                        <?php foreach ($motoristas as $m): ?>
                            <option value="<?= $m["id"] ?>" <?= $frete["id_motorista"] == $m["id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($m["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Operação</label>
                    <select name="id_operacao" class="form-select">
                        <option value="">Não informado</option>

                        <?php foreach ($operacoes as $op): ?>
                            <option value="<?= $op["id"] ?>" <?= $frete["id_operacao"] == $op["id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($op["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Cliente</label>
                    <input
                        type="text"
                        name="cliente"
                        class="form-control"
                        value="<?= htmlspecialchars($frete["cliente"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Valor *</label>
                    <input
                        type="text"
                        name="valor"
                        class="form-control"
                        required
                        value="<?= number_format((float)$frete["valor"], 2, ",", ".") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3 campo-resumido">
                    <label class="form-label">Data inicial *</label>
                    <input
                        type="date"
                        name="data_inicio"
                        class="form-control"
                        value="<?= htmlspecialchars($frete["data_inicio"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3 campo-resumido">
                    <label class="form-label">Data final *</label>
                    <input
                        type="date"
                        name="data_fim"
                        class="form-control"
                        value="<?= htmlspecialchars($frete["data_fim"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3 campo-detalhado">
                    <label class="form-label">Data do frete *</label>
                    <input
                        type="date"
                        name="data_frete"
                        class="form-control"
                        value="<?= htmlspecialchars($frete["data_frete"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3 campo-detalhado">
                    <label class="form-label">Origem</label>
                    <input
                        type="text"
                        name="origem"
                        class="form-control"
                        value="<?= htmlspecialchars($frete["origem"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3 campo-detalhado">
                    <label class="form-label">Destino</label>
                    <input
                        type="text"
                        name="destino"
                        class="form-control"
                        value="<?= htmlspecialchars($frete["destino"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="lancado" <?= $frete["status"] === "lancado" ? "selected" : "" ?>>Lançado</option>
                        <option value="faturado" <?= $frete["status"] === "faturado" ? "selected" : "" ?>>Faturado</option>
                        <option value="recebido" <?= $frete["status"] === "recebido" ? "selected" : "" ?>>Recebido</option>
                        <option value="cancelado" <?= $frete["status"] === "cancelado" ? "selected" : "" ?>>Cancelado</option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"><?= htmlspecialchars($frete["observacao"] ?? "") ?></textarea>
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

<script>
function atualizarCamposFrete() {
    const tipo = document.getElementById("tipo_lancamento").value;

    document.querySelectorAll(".campo-resumido").forEach(campo => {
        campo.classList.toggle("d-none", tipo !== "resumido");
    });

    document.querySelectorAll(".campo-detalhado").forEach(campo => {
        campo.classList.toggle("d-none", tipo !== "detalhado");
    });
}

document.getElementById("tipo_lancamento").addEventListener("change", atualizarCamposFrete);
atualizarCamposFrete();
</script>

<?php require_once "../includes/footer.php"; ?>