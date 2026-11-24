<?php

require_once "../config/auth.php";
require_once __DIR__ . "/funcoes.php";
require_once "../config/conexao.php";

$tituloPagina = "Editar Abastecimento";

$id = (int) ($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM abastecimentos WHERE id = ?");
$stmt->execute([$id]);
$abastecimento = $stmt->fetch();

if (!$abastecimento) {
    die("Abastecimento não encontrado.");
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

$postos = $pdo->query("
    SELECT id, nome
    FROM fornecedores
    WHERE tipo = 'posto'
    ORDER BY nome ASC
")->fetchAll();

require_once "../includes/header.php";
?>

<h3>Editar abastecimento</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="atualizar.php">
            <input type="hidden" name="csrf" value="<?= tokenAbastecimento() ?>">
            <input type="hidden" name="id" value="<?= $abastecimento["id"] ?>">

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Data *</label>
                    <input
                        type="date"
                        name="data_abastecimento"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($abastecimento["data_abastecimento"]) ?>"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Veículo *</label>
                    <select name="id_veiculo" class="form-select" required>
                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= $v["id"] ?>" <?= $abastecimento["id_veiculo"] == $v["id"] ? "selected" : "" ?>>
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
                            <option value="<?= $m["id"] ?>" <?= $abastecimento["id_motorista"] == $m["id"] ? "selected" : "" ?>>
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
                            <option value="<?= $op["id"] ?>" <?= $abastecimento["id_operacao"] == $op["id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($op["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Posto</label>
                    <select name="id_fornecedor" class="form-select">
                        <option value="">Não informado</option>

                        <?php foreach ($postos as $p): ?>
                            <option value="<?= $p["id"] ?>" <?= $abastecimento["id_fornecedor"] == $p["id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($p["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipo de diesel</label>
                    <select name="tipo_diesel" class="form-select">
                        <option value="S500" <?= $abastecimento["tipo_diesel"] === "S500" ? "selected" : "" ?>>S500</option>
                        <option value="S10" <?= $abastecimento["tipo_diesel"] === "S10" ? "selected" : "" ?>>S10</option>
                        <option value="Outro" <?= $abastecimento["tipo_diesel"] === "Outro" ? "selected" : "" ?>>Outro</option>
                    </select>
                </div>

               <div class="col-md-3 mb-3">
                    <label class="form-label">Litros diesel *</label>
                    <input
                        type="text"
                        name="litros"
                        class="form-control"
                        required
                        value="<?= number_format((float)$abastecimento["litros"], 3, ",", ".") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Valor diesel *</label>
                    <input
                        type="text"
                        name="valor_total"
                        class="form-control"
                        required
                        value="<?= number_format((float)$abastecimento["valor_total"], 2, ",", ".") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Litros ARLA</label>
                    <input
                        type="text"
                        name="arla_litros"
                        class="form-control"
                        value="<?= number_format((float)($abastecimento["arla_litros"] ?? 0), 3, ",", ".") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Valor ARLA</label>
                    <input
                        type="text"
                        name="arla_valor_total"
                        class="form-control"
                        value="<?= number_format((float)($abastecimento["arla_valor_total"] ?? 0), 2, ",", ".") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">KM anterior</label>
                    <input
                        type="text"
                        name="km_anterior"
                        class="form-control"
                        value="<?= $abastecimento["km_base_manual"] !== null ? number_format((float)$abastecimento["km_base_manual"], 2, ",", ".") : "" ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">KM atual *</label>
                    <input
                        type="text"
                        name="km_atual"
                        class="form-control"
                        required
                        value="<?= number_format((float)$abastecimento["km_atual"], 2, ",", ".") ?>"
                    >
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"><?= htmlspecialchars($abastecimento["observacao"] ?? "") ?></textarea>
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