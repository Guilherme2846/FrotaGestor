<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Cadastrar Manutenção";

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    WHERE status <> 'inativo'
    ORDER BY placa ASC
")->fetchAll();

$fornecedores = $pdo->query("
    SELECT id, nome, tipo
    FROM fornecedores
    WHERE status = 'ativo'
      AND tipo IN ('oficina', 'pecas', 'borracharia', 'outro')
    ORDER BY nome ASC
")->fetchAll();

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    WHERE status = 'ativa'
    ORDER BY nome ASC
")->fetchAll();

require_once "../includes/header.php";
?>

<h3>Cadastrar manutenção</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="salvar.php">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Data *</label>
                    <input type="date" name="data_manutencao" class="form-control" required value="<?= date("Y-m-d") ?>">
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

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo_manutencao" class="form-select" required>
                        <option value="corretiva">Corretiva</option>
                        <option value="preventiva">Preventiva</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="concluida">Concluída</option>
                        <option value="aberta">Aberta</option>
                        <option value="andamento">Andamento</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>

                <div class="col-md-5 mb-3">
                    <label class="form-label">Serviço *</label>
                    <input
                        type="text"
                        name="servico"
                        class="form-control"
                        required
                        placeholder="Ex: Troca de óleo, freio, embreagem..."
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Fornecedor / Oficina</label>
                    <select name="id_fornecedor" class="form-select">
                        <option value="">Não informado</option>
                        <?php foreach ($fornecedores as $f): ?>
                            <option value="<?= $f["id"] ?>">
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
                            <option value="<?= $op["id"] ?>">
                                <?= htmlspecialchars($op["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">KM da manutenção</label>
                    <input type="text" name="km_manutencao" class="form-control" placeholder="Ex: 850000">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Mão de obra</label>
                    <input type="text" name="valor_mao_obra" class="form-control" placeholder="Ex: 500,00">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Peças</label>
                    <input type="text" name="valor_pecas" class="form-control" placeholder="Ex: 1200,00">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Validade KM</label>
                    <input type="text" name="validade_km" class="form-control" placeholder="Ex: 10000">
                    <small class="text-muted">Ex: troca de óleo a cada 10.000 km.</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Validade dias</label>
                    <input type="number" name="validade_dias" class="form-control" placeholder="Ex: 180">
                    <small class="text-muted">Ex: trocar em 180 dias.</small>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="3"></textarea>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="listar.php" class="btn btn-secondary">Voltar</a>
        </form>

    </div>
</div>

<?php require_once "../includes/footer.php"; ?>