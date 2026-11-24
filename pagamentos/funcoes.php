<?php

function numeroBanco($valor)
{
    if ($valor === null || $valor === "") {
        return 0;
    }

    $valor = trim((string) $valor);
    $valor = str_replace(".", "", $valor);
    $valor = str_replace(",", ".", $valor);

    return (float) $valor;
}

function nomeTipoPagamento($tipo)
{
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

    return $tipos[$tipo] ?? $tipo;
}