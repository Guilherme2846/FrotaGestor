<?php
require __DIR__ . '/consulta.php';
$tituloPagina = 'Relatório de abastecimentos';
require __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= h($baseUrl) ?>/abastecimentos/relatorio.css">
<main class="combustivel">
<header class="topo">
    <div><h1>Abastecimentos</h1><p><?= h($rotuloPlaca) ?> · <?= $inicio ? 'Desde ' . h(dataRelatorio($inicio)) : 'Desde o início' ?><?= $fim ? ' até ' . h(dataRelatorio($fim)) : '' ?></p></div>
    <a href="cadastrar.php" class="btn btn-primary no-print">Novo abastecimento</a>
</header>
<form method="get" class="filtros no-print">
    <label>Veículo<select name="id_veiculo"><option value="">Toda a frota</option>
        <?php foreach ($veiculos as $v): ?><option value="<?= (int)$v['id'] ?>" <?= $placa === (int)$v['id'] ? 'selected' : '' ?>><?= h($v['placa'] . ' · ' . $v['modelo']) ?></option><?php endforeach; ?>
    </select></label>
    <label>De<input type="date" name="data_inicio" value="<?= h($inicio) ?>"></label>
    <label>Até<input type="date" name="data_fim" value="<?= h($fim) ?>"></label>
    <button class="btn btn-dark">Consultar</button><a href="listar.php">Limpar</a>
</form>
<?php if ($erro): ?><p class="alert alert-danger"><?= h($erro) ?></p><?php endif; ?>
<?php if (isset($_GET['sucesso'])): ?><p role="status" class="alert alert-success no-print">Lançamentos atualizados.</p><?php endif; ?>
<div class="resumo">
    <section><h2>Consumo médio</h2><strong><?= $t['litros_media'] > 0 ? numero($t['km'] / $t['litros_media'],3) . ' km/L' : 'Sem cálculo' ?></strong><small><?= numero($t['km'],2) ?> km em <?= (int)$t['validos'] ?> abastecimento(s) com distância</small></section>
    <section><h2>Diesel</h2><strong>R$ <?= numero($t['gasto'],2) ?></strong><small><?= numero($t['diesel'],3) ?> L<?= $t['diesel'] > 0 ? ' · R$ ' . numero($t['gasto'] / $t['diesel'],3) . '/L' : '' ?></small></section>
    <section><h2>ARLA</h2><strong>R$ <?= numero($t['gasto_arla'],2) ?></strong><small><?= numero($t['arla'],3) ?> L<?= $t['arla'] > 0 ? ' · R$ ' . numero($t['gasto_arla'] / $t['arla'],3) . '/L' : '' ?></small></section>
    <section><h2>Gasto do período</h2><strong>R$ <?= numero($t['gasto'] + $t['gasto_arla'],2) ?></strong><small>Diesel + ARLA</small></section>
</div>
<section class="historico"><div class="cabecalho">
    <h2>Histórico <span>(<?= $qtd ?>)</span></h2>
    <?php if ($qtd): ?><div class="acoes no-print">
        <a href="?<?= h(http_build_query($filtros + ['saida'=>'csv'])) ?>">Baixar CSV</a>
        <?php if ($modo === 'imprimir'): ?><button class="btn btn-outline-dark btn-sm" onclick="window.print()">Imprimir / salvar PDF</button>
        <?php else: ?><a href="?<?= h(http_build_query($filtros + ['saida'=>'imprimir'])) ?>">Versão para impressão</a><?php endif; ?>
    </div><?php endif; ?>
</div>
<?php if (!$qtd): ?><p>Nenhum abastecimento encontrado para esta consulta.</p>
<?php else: ?><div class="rolagem"><table><thead><tr>
<th>Data</th><th>Placa</th><th>Odômetro (km)</th><th>Distância (km)</th><th>Diesel (L)</th><th>Diesel (R$)</th><th>km/L</th><th>ARLA (R$)</th><th class="no-print">Lançamento</th>
</tr></thead><tbody>
<?php while ($a = $linhas->fetch()): ?><tr>
<td><?= dataRelatorio($a['data_abastecimento']) ?></td><td><b><?= h($a['placa']) ?></b></td>
<td><?= numero($a['km_atual'],2) ?></td><td><?= $a['km_rodado'] === null ? '—' : numero($a['km_rodado'],2) ?></td>
<td><?= numero($a['litros'],3) ?></td><td><?= numero($a['valor_total'],2) ?></td><td><b><?= $a['media_km_l'] === null ? '—' : numero($a['media_km_l'],3) ?></b></td>
<td><?= numero($a['arla_valor_total'],2) ?></td><td class="no-print"><details><summary>Detalhes</summary>
<dl><?php foreach (['Modelo'=>$a['modelo'],'Motorista'=>$a['motorista'],'Operação'=>$a['operacao'],'Posto'=>$a['fornecedor'],'Diesel'=>$a['tipo_diesel'],
'Preço diesel'=>'R$ '.numero($a['valor_litro'],4).'/L','ARLA'=>numero($a['arla_litros'],3).' L','Preço ARLA'=>$a['arla_valor_litro'] === null ? null : 'R$ '.numero($a['arla_valor_litro'],4).'/L','Observação'=>$a['observacao']] as $nome=>$valor):
if ($valor === null || $valor === '') {continue;} ?><dt><?= h($nome) ?></dt><dd><?= h($valor) ?></dd><?php endforeach; ?></dl>
<a href="editar.php?id=<?= (int)$a['id'] ?>">Editar</a>
<form class="d-inline" method="post" action="excluir.php" onsubmit="return confirm('Excluir este abastecimento?')"><input type="hidden" name="csrf" value="<?= tokenAbastecimento() ?>"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-link text-danger btn-sm">Excluir</button></form>
</details></td></tr><?php endwhile; ?>
</tbody></table></div>
<?php if ($modo !== 'imprimir' && $paginas > 1): ?><nav class="paginacao no-print" aria-label="Páginas">
<?php if ($pagina > 1): ?><a href="?<?= h(http_build_query($filtros + ['pagina'=>$pagina-1])) ?>">Anterior</a><?php endif; ?> Página <?= $pagina ?> de <?= $paginas ?>
<?php if ($pagina < $paginas): ?><a href="?<?= h(http_build_query($filtros + ['pagina'=>$pagina+1])) ?>">Próxima</a><?php endif; ?></nav><?php endif; ?>
<?php endif; ?></section>
<?php if ($qtd && $modo !== 'imprimir'): ?><details class="no-print mt-3"><summary>Recalcular histórico</summary>
<form method="post" action="recalcular.php" onsubmit="return confirm('Recalcular todo o histórico dos veículos selecionados?')"><input type="hidden" name="csrf" value="<?= tokenAbastecimento() ?>"><input type="hidden" name="id_veiculo" value="<?= $placa ?>"><button class="btn btn-outline-dark btn-sm mt-2">Recalcular <?= $placa ? 'esta placa' : 'a frota' ?></button></form></details><?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
