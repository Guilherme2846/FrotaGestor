<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tituloPagina = $tituloPagina ?? "FrotaGestor";

/*
    Detecta se está rodando assim:
    http://localhost/frotagestor
    ou assim:
    http://localhost:8000
*/
$scriptName = str_replace("\\", "/", $_SERVER["SCRIPT_NAME"]);

if (str_starts_with(strtolower($scriptName), "/frotagestor/")) {
    $baseUrl = substr($scriptName, 0, strlen("/frotagestor"));
} else {
    $baseUrl = "";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($tituloPagina) ?> - FrotaGestor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .tabela-relatorio {
        font-size: 12px;
        white-space: nowrap;
    }

    .tabela-relatorio th,
    .tabela-relatorio td {
        padding: 6px 8px;
        vertical-align: middle;
    }

    @media print {
        .tabela-relatorio {
            font-size: 9px;
        }
    }
</style>
    <style>
    @media print {
        nav,
        .no-print,
        .btn,
        form {
            display: none !important;
        }

        body {
            background: white !important;
            font-size: 12px;
        }

        .container {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .card {
            border: 1px solid #ccc !important;
            box-shadow: none !important;
            page-break-inside: avoid;
        }

        .card-body {
            padding: 10px !important;
        }

        table {
            font-size: 11px;
        }

        .table-responsive {
            overflow: visible !important;
        }

        h3, h4, h5 {
            color: black !important;
        }

        .text-success,
        .text-danger,
        .text-muted {
            color: black !important;
        }

        @page {
           size: A4 landscape;
        margin: 8mm;
        }
    }
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $baseUrl ?>/dashboard.php">FrotaGestor</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/dashboard.php">Dashboard</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/veiculos/listar.php">Veículos</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/motoristas/listar.php">Motoristas</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/operacoes/listar.php">Operações</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/fornecedores/listar.php">Fornecedores</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/abastecimentos/listar.php">Abastecimentos</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/fretes/listar.php">Fretes</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/pagamentos/listar.php">Pagamentos</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>/manutencoes/listar.php">Manutenções</a>
                </li>

                
                    <li class="nav-item dropdown">
                        <a
                            class="nav-link dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                        >
                            Relatórios
                        </a>

                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/relatorios/geral_placa.php">
                                    Geral por placa
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/relatorios/geral.php">
                                    Geral da frota
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/relatorios/media_placa.php">
                                    Média por placa
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/relatorios/medias.php">
                                    Médias
                                </a>
                            </li>
                            
                            <li>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/relatorios/manutencoes.php">
                                    Manutenções
                                </a>
                            </li>
                            
                            <li>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/relatorios/pagamentos.php">
                                    Pagamentos
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/relatorios/fretes.php">
                                    Fretes
                                </a>
                            </li>
                        </ul>
                    </li>
                
            </ul>

            <span class="navbar-text text-white me-3">
                <?= htmlspecialchars($_SESSION["usuario_nome"] ?? "Usuário") ?>
            </span>

            <a href="<?= $baseUrl ?>/logout.php" class="btn btn-outline-light btn-sm">
                Sair
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">