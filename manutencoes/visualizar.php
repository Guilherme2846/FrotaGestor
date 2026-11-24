<?php

require_once "../config/auth.php";
require_once "../config/conexao.php";

$tituloPagina = "Detalhes da Manutenção";

$id = (int) ($_GET["id"] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

$stmt = $pdo->prepare("
    SELECT
        m.*,
        v.placa,
        v.modelo,
        v.marca,
        v.km_atual,
        f.nome AS fornecedor,
        o.nome AS operacao
    FROM manutencoes m
    INNER JOIN veiculos v ON v.id = m.id_veiculo
    LEFT JOIN fornecedores f ON f.id = m.id_fornecedor
    LEFT JOIN operacoes o ON o.id = m.id_operacao
    WHERE m.id = ?
");

$stmt->execute([$id]);
$manutencao = $stmt->fetch();

if (!$manutencao) {
    die("Manutenção não encontrada.");
}

require_once "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Detalhes da manutenção</h3>

    <div>
        <a href="editar.php?id=<?= $manutencao["id"] ?>" class="btn btn-warning">
            Editar
        </a>

        <a href="listar.php" class="btn btn-secondary">
            Voltar
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-1">
            <?= htmlspecialchars($manutencao["placa"]) ?>
            -
            <?= htmlspecialchars($manutencao["modelo"]) ?>
        </h5>

        <p class="text-muted mb-0">
            <?= htmlspecialchars($manutencao["marca"] ?? "") ?>
            |
            KM atual: <?= number_format((float)$manutencao["km_atual"], 0, ",", ".") ?>
        </p>
    </div>
</div>

<div class="row mb-4">

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Data</small>
                <h5><?= date("d/m/Y", strtotime($manutencao["data_manutencao"])) ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Tipo</small>
                <h5><?= ucfirst($manutencao["tipo_manutencao"]) ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Status</small>
                <h5><?= ucfirst($manutencao["status"]) ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">KM manutenção</small>
                <h5>
                    <?= $manutencao["km_manutencao"] !== null ? number_format((float)$manutencao["km_manutencao"], 0, ",", ".") . " km" : "-" ?>
                </h5>
            </div>
        </div>
    </div>

</div>

<div class="row mb-4">

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Mão de obra</small>
                <h5>R$ <?= number_format((float)$manutencao["valor_mao_obra"], 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Peças</small>
                <h5>R$ <?= number_format((float)$manutencao["valor_pecas"], 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted">Total</small>
                <h5>R$ <?= number_format((float)$manutencao["valor_total"], 2, ",", ".") ?></h5>
            </div>
        </div>
    </div>

</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Serviço</h5>

        <p class="mb-0">
            <?= htmlspecialchars($manutencao["servico"]) ?>
        </p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Descrição</h5>

        <?php if (!empty($manutencao["descricao"])): ?>
            <p class="mb-0">
                <?= nl2br(htmlspecialchars($manutencao["descricao"])) ?>
            </p>
        <?php else: ?>
            <p class="text-muted mb-0">Sem descrição informada.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Dados complementares</h5>

        <div class="row">
            <div class="col-md-4 mb-3">
                <strong>Fornecedor:</strong><br>
                <?= htmlspecialchars($manutencao["fornecedor"] ?? "Não informado") ?>
            </div>

            <div class="col-md-4 mb-3">
                <strong>Operação:</strong><br>
                <?= htmlspecialchars($manutencao["operacao"] ?? "Não informado") ?>
            </div>

            <div class="col-md-4 mb-3">
                <strong>Próxima troca KM:</strong><br>
                <?= $manutencao["proxima_troca_km"] !== null ? number_format((float)$manutencao["proxima_troca_km"], 0, ",", ".") . " km" : "-" ?>
            </div>

            <div class="col-md-4 mb-3">
                <strong>Próxima troca data:</strong><br>
                <?= $manutencao["proxima_troca_data"] ? date("d/m/Y", strtotime($manutencao["proxima_troca_data"])) : "-" ?>
            </div>

            <div class="col-md-4 mb-3">
                <strong>Validade KM:</strong><br>
                <?= $manutencao["validade_km"] !== null ? number_format((float)$manutencao["validade_km"], 0, ",", ".") . " km" : "-" ?>
            </div>

            <div class="col-md-4 mb-3">
                <strong>Validade dias:</strong><br>
                <?= $manutencao["validade_dias"] !== null ? $manutencao["validade_dias"] . " dias" : "-" ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Observação</h5>

        <?php if (!empty($manutencao["observacao"])): ?>
            <p class="mb-0">
                <?= nl2br(htmlspecialchars($manutencao["observacao"])) ?>
            </p>
        <?php else: ?>
            <p class="text-muted mb-0">Sem observação informada.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>