<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Cadastrar Frete";

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

require_once "../includes/header.php";
?>

<h3>Cadastrar frete</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="salvar.php">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de lançamento *</label>
                    <select name="tipo_lancamento" id="tipo_lancamento" class="form-select" required>
                        <option value="resumido">Resumido por período</option>
                        <option value="detalhado">Detalhado por frete</option>
                    </select>
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
                    <label class="form-label">Cliente</label>
                    <input type="text" name="cliente" class="form-control">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Valor *</label>
                    <input type="text" name="valor" class="form-control" required placeholder="Ex: 18000,00">
                </div>

                <div class="col-md-3 mb-3 campo-resumido">
                    <label class="form-label">Data inicial *</label>
                    <input type="date" name="data_inicio" class="form-control">
                </div>

                <div class="col-md-3 mb-3 campo-resumido">
                    <label class="form-label">Data final *</label>
                    <input type="date" name="data_fim" class="form-control">
                </div>

                <div class="col-md-3 mb-3 campo-detalhado d-none">
                    <label class="form-label">Data do frete *</label>
                    <input type="date" name="data_frete" class="form-control">
                </div>

                <div class="col-md-3 mb-3 campo-detalhado d-none">
                    <label class="form-label">Origem</label>
                    <input type="text" name="origem" class="form-control">
                </div>

                <div class="col-md-3 mb-3 campo-detalhado d-none">
                    <label class="form-label">Destino</label>
                    <input type="text" name="destino" class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="lancado">Lançado</option>
                        <option value="faturado">Faturado</option>
                        <option value="recebido">Recebido</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
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