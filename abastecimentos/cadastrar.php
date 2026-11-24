<?php

require_once "../config/auth.php";
require_once __DIR__ . "/funcoes.php";
require_once "../config/conexao.php";

$tituloPagina = "Cadastrar Abastecimento";

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    WHERE status <> 'inativo'
    ORDER BY placa ASC
")->fetchAll();

$motoristas = $pdo->query("
    SELECT id, nome
    FROM motoristas
    WHERE status = 'ativo'
    ORDER BY nome ASC
")->fetchAll();

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    WHERE status = 'ativa'
    ORDER BY nome ASC
")->fetchAll();

$postos = $pdo->query("
    SELECT id, nome
    FROM fornecedores
    WHERE status = 'ativo'
      AND tipo = 'posto'
    ORDER BY nome ASC
")->fetchAll();

require_once "../includes/header.php";
?>

<h3>Cadastrar abastecimento</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="salvar.php">
            <input type="hidden" name="csrf" value="<?= tokenAbastecimento() ?>">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Data *</label>
                    <input type="date" name="data_abastecimento" class="form-control" required value="<?= date("Y-m-d") ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Veículo *</label>
                    <select name="id_veiculo" class="form-select" required>
                        <option value="">Selecione</option>

                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= $v["id"] ?>">
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
                            <option value="<?= $m["id"] ?>">
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
                            <option value="<?= $op["id"] ?>">
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
                            <option value="<?= $p["id"] ?>">
                                <?= htmlspecialchars($p["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipo de diesel</label>
                    <select name="tipo_diesel" class="form-select">
                        <option value="S500">S500</option>
                        <option value="S10">S10</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Litros diesel *</label>
                    <input type="text" name="litros" class="form-control" required placeholder="Ex: 300,50">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Valor diesel *</label>
                    <input type="text" name="valor_total" class="form-control" required placeholder="Ex: 1800,00">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Litros ARLA</label>
                    <input type="text" name="arla_litros" class="form-control" placeholder="Ex: 20,00">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Valor ARLA</label>
                    <input type="text" name="arla_valor_total" class="form-control" placeholder="Ex: 90,00">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">KM anterior</label>
                    <input type="text" name="km_anterior" class="form-control" placeholder="Opcional">
                    <small class="text-muted">Se deixar vazio, tenta buscar o último KM lançado.</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">KM atual *</label>
                    <input type="text" name="km_atual" class="form-control" required placeholder="Ex: 850000">
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Salvar
            </button>

            <a href="listar.php" class="btn btn-secondary">
                Voltar
            </a>
        </form>

    </div>
</div>

<?php require_once "../includes/footer.php"; ?>