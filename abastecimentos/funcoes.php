<?php

function tokenAbastecimento(): string
{
    return $_SESSION['csrf_abastecimentos'] ??= bin2hex(random_bytes(32));
}

function exigirPostAbastecimento(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit;
    }
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(tokenAbastecimento(), $_POST['csrf'])) {
        http_response_code(403);
        exit('Atualize a página e tente novamente.');
    }
}

function numeroBanco($entrada, int $casas): ?string
{
    $valor = trim((string)$entrada);
    if ($valor === '') {
        return null;
    }
    if (!preg_match('/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,' . $casas . '})?$/D', $valor)) {
        throw new InvalidArgumentException('Confira os números. Use vírgula decimal e ponto somente para milhares.');
    }
    return str_replace(',', '.', str_replace('.', '', $valor));
}

function recalcularPlaca(PDO $pdo, int $placa): void
{
    $busca = $pdo->prepare('SELECT id, km_atual, km_base_manual, litros, valor_total FROM abastecimentos
        WHERE id_veiculo = ? ORDER BY data_abastecimento, km_atual, id FOR UPDATE');
    $busca->execute([$placa]);
    $salvar = $pdo->prepare('UPDATE abastecimentos SET km_anterior = ?, km_rodado = ?, media_km_l = ?,
        valor_litro = ? WHERE id = ?');
    $ultimoKm = null;
    while ($linha = $busca->fetch()) {
        $atual = (float)$linha['km_atual'];
        $anterior = $linha['km_base_manual'] ?? $ultimoKm;
        $rodado = $anterior !== null && $atual > $anterior ? $atual - $anterior : null;
        $litros = (float)$linha['litros'];
        $media = $rodado !== null && $litros > 0 ? $rodado / $litros : null;
        $preco = $litros > 0 ? $linha['valor_total'] / $litros : null;
        $salvar->execute([$anterior, $rodado, $media, $preco, $linha['id']]);
        $ultimoKm = $atual;
    }
    if ($ultimoKm !== null) {
        $pdo->prepare('UPDATE veiculos SET km_atual = ? WHERE id = ?')->execute([$ultimoKm, $placa]);
    }
}

function travarVeiculos(PDO $pdo, array $veiculos): array
{
    $veiculos = array_unique($veiculos);
    sort($veiculos, SORT_NUMERIC);
    $travar = $pdo->prepare('SELECT id FROM veiculos WHERE id = ? FOR UPDATE');
    foreach ($veiculos as $id) {
        $travar->execute([$id]);
        if (!$travar->fetchColumn()) {
            throw new InvalidArgumentException('Veículo não encontrado.');
        }
    }
    return $veiculos;
}
