<?php

require_once "config/auth.php";
require_once "config/conexao.php";

$alertasPreventiva = $pdo->query("
    SELECT
        m.id,
        m.servico,
        m.proxima_troca_km,
        m.proxima_troca_data,
        v.placa,
        v.modelo,
        v.km_atual
    FROM manutencoes m
    INNER JOIN veiculos v ON v.id = m.id_veiculo
    WHERE m.status <> 'cancelada'
      AND m.tipo_manutencao = 'preventiva'
      AND (
            (m.proxima_troca_km IS NOT NULL AND m.proxima_troca_km <= v.km_atual + 1000)
            OR
            (m.proxima_troca_data IS NOT NULL AND m.proxima_troca_data <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY))
      )
    ORDER BY m.proxima_troca_data ASC, m.proxima_troca_km ASC
    LIMIT 10
")->fetchAll();

$placasMediaBaixa = $pdo->query("
    SELECT
        v.placa,
        v.modelo,
        COALESCE(SUM(a.km_rodado), 0) AS km_rodado,
        COALESCE(SUM(a.litros), 0) AS litros,
        COALESCE(SUM(a.km_rodado), 0) / COALESCE(SUM(a.litros), 1) AS media
    FROM abastecimentos a
    INNER JOIN veiculos v ON v.id = a.id_veiculo
    WHERE a.km_rodado IS NOT NULL
      AND a.km_rodado > 0
      AND MONTH(a.data_abastecimento) = MONTH(CURRENT_DATE())
      AND YEAR(a.data_abastecimento) = YEAR(CURRENT_DATE())
    GROUP BY v.id, v.placa, v.modelo
    HAVING media < 2.50
    ORDER BY media ASC
    LIMIT 10
")->fetchAll();

$fretesPendentes = $pdo->query("
    SELECT
        COUNT(*) AS qtd,
        COALESCE(SUM(valor), 0) AS total
    FROM fretes
    WHERE status IN ('lancado', 'faturado')
      AND MONTH(COALESCE(data_frete, data_inicio)) = MONTH(CURRENT_DATE())
      AND YEAR(COALESCE(data_frete, data_inicio)) = YEAR(CURRENT_DATE())
")->fetch();

$pagamentosSemPlaca = $pdo->query("
    SELECT
        COUNT(*) AS qtd,
        COALESCE(SUM(
            CASE
                WHEN tipo_pagamento = 'desconto' THEN -valor
                ELSE valor
            END
        ), 0) AS total
    FROM pagamentos
    WHERE id_veiculo IS NULL
      AND status <> 'cancelado'
      AND MONTH(COALESCE(mes_referencia, data_pagamento)) = MONTH(CURRENT_DATE())
      AND YEAR(COALESCE(mes_referencia, data_pagamento)) = YEAR(CURRENT_DATE())
")->fetch();

$abastecimentosSemMedia = $pdo->query("
    SELECT
        COUNT(*) AS qtd
    FROM abastecimentos
    WHERE (km_rodado IS NULL OR media_km_l IS NULL)
      AND MONTH(data_abastecimento) = MONTH(CURRENT_DATE())
      AND YEAR(data_abastecimento) = YEAR(CURRENT_DATE())
")->fetch();

$tituloPagina = "Dashboard";

$totalVeiculos = $pdo->query("SELECT COUNT(*) AS total FROM veiculos")->fetch()["total"];
$totalMotoristas = $pdo->query("SELECT COUNT(*) AS total FROM motoristas")->fetch()["total"];
$totalOperacoes = $pdo->query("SELECT COUNT(*) AS total FROM operacoes")->fetch()["total"];
$totalFornecedores = $pdo->query("SELECT COUNT(*) AS total FROM fornecedores")->fetch()["total"];
$totalAbastecimentos = $pdo->query("SELECT COUNT(*) AS total FROM abastecimentos")->fetch()["total"];

$gastoDieselMes = $pdo->query("
    SELECT COALESCE(SUM(valor_total), 0) AS total
    FROM abastecimentos
    WHERE MONTH(data_abastecimento) = MONTH(CURRENT_DATE())
      AND YEAR(data_abastecimento) = YEAR(CURRENT_DATE())
")->fetch()["total"];

$totalFretes = $pdo->query("SELECT COUNT(*) AS total FROM fretes")->fetch()["total"];

$totalFretesMes = $pdo->query("
    SELECT COALESCE(SUM(valor), 0) AS total
    FROM fretes
    WHERE status <> 'cancelado'
      AND MONTH(COALESCE(data_frete, data_inicio)) = MONTH(CURRENT_DATE())
      AND YEAR(COALESCE(data_frete, data_inicio)) = YEAR(CURRENT_DATE())
")->fetch()["total"];

$totalPagamentos = $pdo->query("SELECT COUNT(*) AS total FROM pagamentos")->fetch()["total"];

$totalPagamentosMes = $pdo->query("
    SELECT COALESCE(SUM(
        CASE
            WHEN tipo_pagamento = 'desconto' THEN -valor
            ELSE valor
        END
    ), 0) AS total
    FROM pagamentos
    WHERE status <> 'cancelado'
      AND MONTH(COALESCE(mes_referencia, data_pagamento)) = MONTH(CURRENT_DATE())
      AND YEAR(COALESCE(mes_referencia, data_pagamento)) = YEAR(CURRENT_DATE())
")->fetch()["total"];

$totalManutencoes = $pdo->query("SELECT COUNT(*) AS total FROM manutencoes")->fetch()["total"];

$totalManutencoesMes = $pdo->query("
    SELECT COALESCE(SUM(valor_total), 0) AS total
    FROM manutencoes
    WHERE status <> 'cancelada'
      AND MONTH(data_manutencao) = MONTH(CURRENT_DATE())
      AND YEAR(data_manutencao) = YEAR(CURRENT_DATE())
")->fetch()["total"];

require_once "includes/header.php";
?>

<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between"><h3>Painel da frota</h3><a class="btn btn-primary" href="abastecimentos/listar.php">Consultar abastecimentos</a></div>

<div class="row mt-4">
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Veículos</h5>
                <h2><?= $totalVeiculos ?></h2>
                <a href="veiculos/listar.php" class="btn btn-primary btn-sm">
                    Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Motoristas</h5>
                <h2><?= $totalMotoristas ?></h2>
                <a href="motoristas/listar.php" class="btn btn-primary btn-sm">
                    Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Operações</h5>
                <h2><?= $totalOperacoes ?></h2>
                <a href="operacoes/listar.php" class="btn btn-primary btn-sm">
                    Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Fornecedores</h5>
                <h2><?= $totalFornecedores ?></h2>
                <a href="fornecedores/listar.php" class="btn btn-primary btn-sm">
                    Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Pagamentos</h5>
                <h2><?= $totalPagamentos ?></h2>
                <a href="pagamentos/listar.php" class="btn btn-primary btn-sm">
                    Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Abastecimentos</h5>
                <h2><?= $totalAbastecimentos ?></h2>
                <a href="abastecimentos/listar.php" class="btn btn-primary btn-sm">
                    Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Fretes</h5>
                <h2><?= $totalFretes ?></h2>
                <a href="fretes/listar.php" class="btn btn-primary btn-sm">
                    Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Manutenções</h5>
                <h2><?= $totalManutencoes ?></h2>
                <a href="manutencoes/listar.php" class="btn btn-primary btn-sm">
                    Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Diesel no mês</h5>
                <h2>R$ <?= number_format((float)$gastoDieselMes, 2, ",", ".") ?></h2>
                <a href="abastecimentos/listar.php" class="btn btn-primary btn-sm">
                    Ver
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Fretes no mês</h5>
                <h2>R$ <?= number_format((float)$totalFretesMes, 2, ",", ".") ?></h2>
                <a href="fretes/listar.php" class="btn btn-primary btn-sm">
                    Ver
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Manutenção no mês</h5>
                <h2>R$ <?= number_format((float)$totalManutencoesMes, 2, ",", ".") ?></h2>
                <a href="manutencoes/listar.php" class="btn btn-primary btn-sm">
                    Ver
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Pagamentos no mês</h5>
                <h2>R$ <?= number_format((float)$totalPagamentosMes, 2, ",", ".") ?></h2>
                <a href="pagamentos/listar.php" class="btn btn-primary btn-sm">
                    Ver
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Alertas rápidos</h5>

        <div class="row">

            <div class="col-md-3 mb-3">
                <div class="card border-warning h-100">
                    <div class="card-body">
                        <small class="text-muted">Preventivas vencendo</small>
                        <h4><?= count($alertasPreventiva) ?></h4>
                        <a href="relatorios/manutencoes.php" class="btn btn-sm btn-outline-warning">
                            Ver manutenções
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-danger h-100">
                    <div class="card-body">
                        <small class="text-muted">Média baixa no mês</small>
                        <h4><?= count($placasMediaBaixa) ?></h4>
                        <a href="relatorios/medias.php" class="btn btn-sm btn-outline-danger">
                            Ver médias
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-primary h-100">
                    <div class="card-body">
                        <small class="text-muted">Fretes pendentes</small>
                        <h4>R$ <?= number_format((float)$fretesPendentes["total"], 2, ",", ".") ?></h4>
                        <small><?= (int)$fretesPendentes["qtd"] ?> fretes</small><br>
                        <a href="relatorios/fretes.php" class="btn btn-sm btn-outline-primary mt-2">
                            Ver fretes
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-secondary h-100">
                    <div class="card-body">
                        <small class="text-muted">Pagamentos sem placa</small>
                        <h4>R$ <?= number_format((float)$pagamentosSemPlaca["total"], 2, ",", ".") ?></h4>
                        <small><?= (int)$pagamentosSemPlaca["qtd"] ?> pagamentos</small><br>
                        <a href="relatorios/pagamentos.php" class="btn btn-sm btn-outline-secondary mt-2">
                            Ver pagamentos
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <?php if ((int)$abastecimentosSemMedia["qtd"] > 0): ?>
            <div class="alert alert-warning mb-0">
                Existem
                <strong><?= (int)$abastecimentosSemMedia["qtd"] ?></strong>
                abastecimentos neste mês sem média calculada.
                <a href="abastecimentos/listar.php" class="alert-link">
                    Conferir abastecimentos
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (count($alertasPreventiva) > 0): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Preventivas vencidas ou próximas</h5>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Serviço</th>
                            <th>KM atual</th>
                            <th>Próx. KM</th>
                            <th>Próx. data</th>
                            <th>Situação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($alertasPreventiva as $a): ?>
                            <?php
                                $kmAtual = (float)$a["km_atual"];
                                $proxKm = $a["proxima_troca_km"] !== null ? (float)$a["proxima_troca_km"] : null;

                                $kmVencido = $proxKm !== null && $proxKm <= $kmAtual;
                                $dataVencida = $a["proxima_troca_data"] !== null && $a["proxima_troca_data"] <= date("Y-m-d");

                                $vencido = $kmVencido || $dataVencida;
                            ?>

                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($a["placa"]) ?></strong><br>
                                    <small><?= htmlspecialchars($a["modelo"]) ?></small>
                                </td>

                                <td><?= htmlspecialchars($a["servico"]) ?></td>

                                <td><?= number_format($kmAtual, 0, ",", ".") ?> km</td>

                                <td>
                                    <?= $proxKm !== null ? number_format($proxKm, 0, ",", ".") . " km" : "-" ?>
                                </td>

                                <td>
                                    <?= $a["proxima_troca_data"] ? date("d/m/Y", strtotime($a["proxima_troca_data"])) : "-" ?>
                                </td>

                                <td>
                                    <?php if ($vencido): ?>
                                        <span class="badge bg-danger">Vencida</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Próxima</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (count($placasMediaBaixa) > 0): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Placas com média baixa no mês</h5>

            <p class="text-muted">
                Considerando média abaixo de 2,50 km/L.
            </p>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>KM rodado</th>
                            <th>Litros</th>
                            <th>Média</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($placasMediaBaixa as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p["placa"]) ?></strong><br>
                                    <small><?= htmlspecialchars($p["modelo"]) ?></small>
                                </td>

                                <td><?= number_format((float)$p["km_rodado"], 0, ",", ".") ?> km</td>

                                <td><?= number_format((float)$p["litros"], 2, ",", ".") ?> L</td>

                                <td>
                                    <strong class="text-danger">
                                        <?= number_format((float)$p["media"], 2, ",", ".") ?> km/L
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <small class="text-muted">
                Depois podemos criar meta individual por placa/modelo, exemplo: Scania R500 2,30 km/L e Mercedes 1418 3,60 km/L.
            </small>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm mt-4">
    <div class="card-body">
        <h5>Próximos módulos</h5>

        <p class="text-muted mb-2">
            Base inicial concluída:
        </p>

        <ul class="mb-0">
            <li>Veículos</li>
            <li>Motoristas</li>
            <li>Operações</li>
            <li>Fornecedores</li>
        </ul>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>