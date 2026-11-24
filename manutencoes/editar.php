<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Editar Manutenção";

$id = (int) ($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM manutencoes WHERE id = ?");
$stmt->execute([$id]);
$manutencao = $stmt->fetch();

if (!$manutencao) {
    die("Manutenção não encontrada.");
}

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$fornecedores = $pdo->query("
    SELECT id, nome, tipo
    FROM fornecedores
    WHERE tipo IN ('oficina', 'pecas', 'borracharia', 'outro')
    ORDER BY nome ASC
")->fetchAll();

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    ORDER BY nome ASC
")->fetchAll();

require_once "../includes/header.php";
?>

<h3>Editar manutenção</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="atualizar.php">
            <input type="hidden" name="id" value="<?= $manutencao["id"] ?>">

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Data *</label>
                    <input
                        type="date"
                        name="data_manutencao"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($manutencao["data_manutencao"]) ?>"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Veículo *</label>
                    <select name="id_veiculo" class="form-select" required>
                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= $v["id"] ?>" <?= $manutencao["id_veiculo"] == $v["id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($v["placa"] . " - " . $v["modelo"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo_manutencao" class="form-select" required>
                        <option value="corretiva" <?= $manutencao["tipo_manutencao"] === "corretiva" ? "selected" : "" ?>>
                            Corretiva
                        </option>

                        <option value="preventiva" <?= $manutencao["tipo_manutencao"] === "preventiva" ? "selected" : "" ?>>
                            Preventiva
                        </option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="concluida" <?= $manutencao["status"] === "concluida" ? "selected" : "" ?>>
                            Concluída
                        </option>

                        <option value="aberta" <?= $manutencao["status"] === "aberta" ? "selected" : "" ?>>
                            Aberta
                        </option>

                        <option value="andamento" <?= $manutencao["status"] === "andamento" ? "selected" : "" ?>>
                            Andamento
                        </option>

                        <option value="cancelada" <?= $manutencao["status"] === "cancelada" ? "selected" : "" ?>>
                            Cancelada
                        </option>
                    </select>
                </div>

                <div class="col-md-5 mb-3">
                    <label class="form-label">Serviço *</label>
                    <input
                        type="text"
                        name="servico"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($manutencao["servico"]) ?>"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Fornecedor / Oficina</label>
                    <select name="id_fornecedor" class="form-select">
                        <option value="">Não informado</option>

                        <?php foreach ($fornecedores as $f): ?>
                            <option value="<?= $f["id"] ?>" <?= $manutencao["id_fornecedor"] == $f["id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($f["nome"] . " - " . $f["tipo"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Operação</label>
                    <select name="id_operacao" class="form-select">
                        <option value="">Não informado</option>

                        <?php foreach ($operacoes as $op): ?>
                            <option value="<?= $op["id"] ?>" <?= $manutencao["id_operacao"] == $op["id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($op["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">KM da manutenção</label>
                    <input
                        type="text"
                        name="km_manutencao"
                        class="form-control"
                        value="<?= $manutencao["km_manutencao"] !== null ? number_format((float)$manutencao["km_manutencao"], 0, ",", ".") : "" ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Mão de obra</label>
                    <input
                        type="text"
                        name="valor_mao_obra"
                        class="form-control"
                        value="<?= number_format((float)$manutencao["valor_mao_obra"], 2, ",", ".") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Peças</label>
                    <input
                        type="text"
                        name="valor_pecas"
                        class="form-control"
                        value="<?= number_format((float)$manutencao["valor_pecas"], 2, ",", ".") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Validade KM</label>
                    <input
                        type="text"
                        name="validade_km"
                        class="form-control"
                        value="<?= $manutencao["validade_km"] !== null ? number_format((float)$manutencao["validade_km"], 0, ",", ".") : "" ?>"
                    >
                    <small class="text-muted">Ex: troca de óleo a cada 10.000 km.</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Validade dias</label>
                    <input
                        type="number"
                        name="validade_dias"
                        class="form-control"
                        value="<?= htmlspecialchars($manutencao["validade_dias"] ?? "") ?>"
                    >
                    <small class="text-muted">Ex: trocar em 180 dias.</small>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($manutencao["descricao"] ?? "") ?></textarea>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="2"><?= htmlspecialchars($manutencao["observacao"] ?? "") ?></textarea>
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