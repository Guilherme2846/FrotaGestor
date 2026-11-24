<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoes.php';

function h($valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function numero($valor, int $casas): string { return number_format((float)$valor, $casas, ',', '.'); }
function dataRelatorio($data): string { return date('d/m/Y', strtotime($data)); }

$placa = (int)($_GET['id_veiculo'] ?? 0);
$inicio = trim($_GET['data_inicio'] ?? '');
$fim = trim($_GET['data_fim'] ?? '');
$parametros = [];
$condicoes = [];
$erro = '';
if ($placa > 0) {
    $condicoes[] = 'a.id_veiculo = ?';
    $parametros[] = $placa;
}
foreach ([[$inicio, '>='], [$fim, '<=']] as [$valor, $comparador]) {
    if ($valor === '') { continue; }
    $dia = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
    if (!$dia || $dia->format('Y-m-d') !== $valor) {
        $erro = 'Informe uma data válida.';
    }
    $condicoes[] = "a.data_abastecimento $comparador ?";
    $parametros[] = $valor;
}
if ($inicio !== '' && $fim !== '' && $inicio > $fim) {
    $erro = 'A data inicial deve ser anterior ou igual à data final.';
}
if ($erro !== '') {
    http_response_code(422);
    $condicoes = ['1 = 0'];
    $parametros = [];
}
$where = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';
$veiculos = $pdo->query('SELECT id, placa, modelo FROM veiculos ORDER BY placa')->fetchAll();
$rotuloPlaca = $placa ? 'Veículo não encontrado' : 'Toda a frota';
foreach ($veiculos as $v) {
    if ((int)$v['id'] === $placa) { $rotuloPlaca = $v['placa'] . ' · ' . $v['modelo']; }
}
$filtros = array_filter(['id_veiculo' => $placa, 'data_inicio' => $inicio, 'data_fim' => $fim]);
$totais = $pdo->prepare("SELECT COUNT(*) AS qtd, SUM(litros) AS diesel, SUM(valor_total) AS gasto,
    SUM(arla_litros) AS arla, SUM(arla_valor_total) AS gasto_arla,
    SUM(CASE WHEN km_rodado > 0 AND litros > 0 THEN km_rodado END) AS km,
    SUM(CASE WHEN km_rodado > 0 THEN litros END) AS litros_media,
    COUNT(CASE WHEN km_rodado > 0 AND litros > 0 THEN 1 END) AS validos
    FROM abastecimentos a $where");
$totais->execute($parametros);
$t = $totais->fetch();
$qtd = (int)$t['qtd'];
$paginas = max(1, (int)ceil($qtd / 30));
$pagina = min($paginas, max(1, (int)($_GET['pagina'] ?? 1)));
$modo = $_GET['saida'] ?? '';
$limite = in_array($modo, ['csv','imprimir'], true) ? '' : ' LIMIT 30 OFFSET ' . (($pagina - 1) * 30);
$linhas = $pdo->prepare("SELECT a.*, v.placa, v.modelo, m.nome AS motorista, o.nome AS operacao, f.nome AS fornecedor
    FROM abastecimentos a JOIN veiculos v ON v.id = a.id_veiculo
    LEFT JOIN motoristas m ON m.id = a.id_motorista LEFT JOIN operacoes o ON o.id = a.id_operacao
    LEFT JOIN fornecedores f ON f.id = a.id_fornecedor $where
    ORDER BY a.data_abastecimento DESC, a.km_atual DESC, a.id DESC $limite");
$linhas->execute($parametros);
if ($modo === 'csv' && $erro === '') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="abastecimentos.csv"');
    $arquivo = fopen('php://output', 'wb');
    fwrite($arquivo, "\xEF\xBB\xBF");
    $cab = ['Data','Placa','Modelo','Motorista','Operação','Posto','Diesel','Litros diesel','Valor diesel (R$)',
        'Preço diesel (R$/L)','Litros ARLA','Valor ARLA (R$)','Odômetro (km)','Distância (km)','Média (km/L)','Observação'];
    fputcsv($arquivo, $cab, ';', '"', '');
    while ($a = $linhas->fetch()) {
        $registro = [dataRelatorio($a['data_abastecimento']),$a['placa'],$a['modelo'],$a['motorista'],$a['operacao'],
            $a['fornecedor'],$a['tipo_diesel'],numero($a['litros'],3),numero($a['valor_total'],2),numero($a['valor_litro'],4),
            numero($a['arla_litros'],3),numero($a['arla_valor_total'],2),numero($a['km_atual'],2),
            $a['km_rodado'] === null ? '' : numero($a['km_rodado'],2),
            $a['media_km_l'] === null ? '' : numero($a['media_km_l'],3),$a['observacao']];
        foreach ($registro as &$celula) {
            if (preg_match('/^\s*[=+@-]|^[\t\r\n]/u', (string)$celula)) { $celula = "'" . $celula; }
        }
        unset($celula);
        fputcsv($arquivo, $registro, ';', '"', '');
    }
    fclose($arquivo);
    exit;
}
