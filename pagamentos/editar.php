<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Editar Pagamento";

$id = (int) ($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM pagamentos WHERE id = ?");
$stmt->execute([$id]);
$pagamento = $stmt->fetch();

if (!$pagamento) {
    die("Pagamento não encontrado.");
}

$motoristas = $pdo->query("
    SELECT id, nome
    FROM motoristas
    ORDER BY nome ASC
")->fetchAll();

$veiculos = $pdo->query("
    SELECT id, placa, modelo
    FROM veiculos
    ORDER BY placa ASC
")->fetchAll();

$operacoes = $pdo->query("
    SELECT id, nome
    FROM operacoes
    ORDER BY nome ASC
")->fetchAll();

require_once "../includes/header.php";
?>

<h3>Editar pagamento</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="atualizar.php">
            <input type="hidden" name="id" value="<?= $pagamento["id"] ?>">

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de lançamento *</label>
                    <select name="tipo_lancamento" id="tipo_lancamento" class="form-select" required>
                        <option value="mensal" <?= $pagamento["tipo_lancamento"] === "mensal" ? "selected" : "" ?>>
                            Mensal/resumido
                        </option>

                        <option value="detalhado" <?= $pagamento["tipo_lancamento"] === "detalhado" ? "selected" : "" ?>>
                            Detalhado
                        </option>
                    </select>
                </div>

                <div class="col-md-5 mb-3">
                    <label class="form-label">Motorista *</label>
                    <select name="id_motorista" class="form-select" required>
                        <?php foreach ($motoristas as $m): ?>
                            <option value="<?= $m["id"] ?>" <?= $pagamento["id_motorista"] == $m["id"] ? "selected" : "" ?>>
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
                            <option value="<?= $op["id"] ?>" <?= $pagamento["id_operacao"] == $op["id"] ? "selected" : "" ?>>
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
                            <option value="<?= $v["id"] ?>" <?= $pagamento["id_veiculo"] == $v["id"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($v["placa"] . " - " . $v["modelo"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3 campo-mensal">
                    <label class="form-label">Mês de referência *</label>
                    <input
                        type="month"
                        name="mes_referencia"
                        class="form-control"
                        value="<?= $pagamento["mes_referencia"] ? date("Y-m", strtotime($pagamento["mes_referencia"])) : "" ?>"
                    >
                </div>

                <div class="col-md-3 mb-3 campo-detalhado">
                    <label class="form-label">Data do pagamento *</label>
                    <input
                        type="date"
                        name="data_pagamento"
                        class="form-control"
                        value="<?= htmlspecialchars($pagamento["data_pagamento"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de pagamento *</label>
                    <select name="tipo_pagamento" class="form-select" required>
                        <?php
                        $tipos = [
                            "salario" => "Salário",
                            "diaria" => "Diária",
                            "comissao" => "Comissão",
                            "bonus" => "Bônus",
                            "extra" => "Extra",
                            "ajuda_custo" => "Ajuda de custo",
                            "adiantamento" => "Adiantamento",
                            "desconto" => "Desconto",
                            "outros" => "Outros"
                        ];

                        foreach ($tipos as $valorTipo => $nomeTipo):
                        ?>
                            <option value="<?= $valorTipo ?>" <?= $pagamento["tipo_pagamento"] === $valorTipo ? "selected" : "" ?>>
                                <?= $nomeTipo ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Valor *</label>
                    <input
                        type="text"
                        name="valor"
                        class="form-control"
                        required
                        value="<?= number_format((float)$pagamento["valor"], 2, ",", ".") ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="lancado" <?= $pagamento["status"] === "lancado" ? "selected" : "" ?>>Lançado</option>
                        <option value="pago" <?= $pagamento["status"] === "pago" ? "selected" : "" ?>>Pago</option>
                        <option value="cancelado" <?= $pagamento["status"] === "cancelado" ? "selected" : "" ?>>Cancelado</option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"><?= htmlspecialchars($pagamento["observacao"] ?? "") ?></textarea>
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