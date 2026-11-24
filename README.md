# FrotaGestor

Sistema web do Trabalho de Graduação em Análise e Desenvolvimento de Sistemas. O primeiro fluxo de uso acompanha abastecimentos por placa, com consulta do consumo e dos gastos de diesel e ARLA por período. A base do projeto inclui os cadastros de veículos, motoristas, operações e fornecedores, além dos módulos anteriores de manutenção, pagamentos e fretes.

## Executar no Windows com XAMPP

1. Clone o repositório e abra a pasta no terminal. Use PHP 8.1 ou superior com PDO MySQL e MariaDB 10.4 ou superior (ou MySQL 8).
2. Inicie o MySQL no XAMPP. Em uma instalação nova, crie um banco vazio chamado `frotagestor` com codificação `utf8mb4` e importe `database/schema.sql` pelo phpMyAdmin.
3. A configuração local usa `127.0.0.1` e porta `3307`. Se a sua instalação usa outra porta ou credenciais, copie `config/local.exemplo.php` para `config/local.php` e ajuste os valores. Também é possível configurar as variáveis `FROTA_DB_HOST`, `FROTA_DB_PORT`, `FROTA_DB_NAME`, `FROTA_DB_USER` e `FROTA_DB_PASSWORD`.
4. Para criar o primeiro acesso, rode `C:\xampp\php\php.exe bin/criar-admin.php seu-email@exemplo.com`. O comando gera uma senha individual e a mostra uma vez no terminal.
5. Na pasta do projeto, execute `C:\xampp\php\php.exe -S 127.0.0.1:8000`. Abra [o sistema local](http://127.0.0.1:8000) e entre com o e-mail cadastrado.

**Banco de uma versão anterior.** Faça um backup e aplique somente `database/migrations/001_abastecimentos.sql`, uma vez. O esquema completo destina-se a banco vazio. As contas existentes continuam válidas.

## Usar o relatório

Cadastre o veículo em **Veículos**. No painel, abra **Consultar abastecimentos** e use **Novo abastecimento** para registrar data, veículo, hodômetro, litros e valor total do diesel. Use vírgula decimal, por exemplo `300,125` litros ou `105.230,5` km. Quando houver ARLA no mesmo lançamento, informe os respectivos litros e valor.

O histórico abre com toda a frota. Selecione a placa e as datas e clique em **Consultar**. Os cartões, o CSV e a versão de impressão abrangem todos os registros do filtro. A tela lista 30 por página. Em **Detalhes** aparecem posto, motorista, preço por litro, observação e opções de edição.

O sistema calcula a distância a partir do abastecimento anterior da placa, inclusive quando ele está fora do período consultado. Um lançamento sem referência inicial entra no gasto e no volume. A média ponderada divide a soma das distâncias válidas pelos litros de diesel desses mesmos lançamentos. Uma alteração retroativa recalcula o histórico do veículo.

## Histórico da planilha

A aba de origem é `abastecimentos`. Os campos a conferir na importação são data, placa, tipo de produto, quantidade, preço total, posto, km lido, giro de tacógrafo e km corrigido. As outras abas de consulta podem repetir os mesmos registros. O volume mantém três casas e o valor monetário mantém duas.

A migração do histórico fica para a próxima etapa. Antes de confirmar um lote, será preciso validar o pareamento de linhas de diesel e ARLA, os abastecimentos parciais ou artificiais, a correção de hodômetros e possíveis duplicatas. A conferência deve comparar contagem de linhas por produto, volume e gasto por placa em um período conhecido. As planilhas, os backups e a configuração local ficam fora do Git.
