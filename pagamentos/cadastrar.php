<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Cadastrar Pagamento";

$motoristas = $pdo->query("
    SELECT id, nome
    FROM motoristas
    WHERE status = 'ativo'
    ORDER BY nome ASC
")->fetchAll();

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    WHERE status <> 'inativo'
    ORDER BY placa ASC
")->fetchAll();

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    WHERE status = 'ativa'
    ORDER BY nome ASC
")->fetchAll();

require_once "../includes/header.php";
?>

<h3>Cadastrar pagamento</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="salvar.php">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de lançamento *</label>
                    <select name="tipo_lancamento" id="tipo_lancamento" class="form-select" required>
                        <option value="mensal">Mensal/resumido</option>
                        <option value="detalhado">Detalhado</option>
                    </select>
                </div>

                <div class="col-md-5 mb-3">
                    <label class="form-label">Motorista *</label>
                    <select name="id_motorista" class="form-select" required>
                        <option value="">Selecione</option>

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
                    <label class="form-label">Placa</label>
                    <select name="id_veiculo" class="form-select">
                        <option value="">Não informado</option>

                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= $v["id"] ?>">
                                <?= htmlspecialchars($v["placa"] . " - " . $v["modelo"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3 campo-mensal">
                    <label class="form-label">Mês de referência *</label>
                    <input type="month" name="mes_referencia" class="form-control" value="<?= date("Y-m") ?>">
                </div>

                <div class="col-md-3 mb-3 campo-detalhado d-none">
                    <label class="form-label">Data do pagamento *</label>
                    <input type="date" name="data_pagamento" class="form-control" value="<?= date("Y-m-d") ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de pagamento *</label>
                    <select name="tipo_pagamento" class="form-select" required>
                        <option value="salario">Salário</option>
                        <option value="diaria">Diária</option>
                        <option value="comissao">Comissão</option>
                        <option value="bonus">Bônus</option>
                        <option value="extra">Extra</option>
                        <option value="ajuda_custo">Ajuda de custo</option>
                        <option value="adiantamento">Adiantamento</option>
                        <option value="desconto">Desconto</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Valor *</label>
                    <input type="text" name="valor" class="form-control" required placeholder="Ex: 4500,00">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="lancado">Lançado</option>
                        <option value="pago">Pago</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea
                        name="observacao"
                        class="form-control"
                        rows="3"
                        placeholder="Ex: Pagamento referente ao mês 07, operação Special Dog."
                    ></textarea>
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
function atualizarCamposPagamento() {
    const tipo = document.getElementById("tipo_lancamento").value;

    document.querySelectorAll(".campo-mensal").forEach(campo => {
        campo.classList.toggle("d-none", tipo !== "mensal");
    });

    document.querySelectorAll(".campo-detalhado").forEach(campo => {
        campo.classList.toggle("d-none", tipo !== "detalhado");
    });
}

document.getElementById("tipo_lancamento").addEventListener("change", atualizarCamposPagamento);
atualizarCamposPagamento();
</script>

<?php require_once "../includes/footer.php"; ?>