# FrotaGestor · Abastecimentos

Aplicação local escrita do zero com Python 3.10+ e SQLite, sem dependências externas. No Windows, abra **Abrir_FrotaGestor.cmd** e mantenha a janela aberta. O endereço é http://127.0.0.1:8011. Use Ctrl+C para encerrar.

Cadastre uma nota com diesel, ARLA ou ambos. Informe litros com até três casas decimais, km e valores com até duas. Use vírgula decimal. O relatório permite filtrar por placa, modelo ou período, imprimir e exportar o resultado completo para CSV.

A distância usa o hodômetro do lançamento de diesel anterior da placa, inclusive fora do período. Lançamentos do mesmo dia são ordenados por hodômetro e depois por ordem de cadastro. O cálculo por abastecimento supõe níveis de tanque comparáveis; abastecimentos parciais podem distorcer o resultado. ARLA nunca entra no denominador. O relatório recalcula ao consultar, inclusive após lançamentos retroativos.

Os dados são criados em dados/abastecimentos.sqlite, fora do Git. Para fazer uma cópia, encerre o programa e copie a pasta dados. Esta primeira versão é para uso individual no PC. A importação do Excel fica para uma etapa posterior.
