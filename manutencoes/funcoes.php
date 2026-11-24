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

function valorOuNull($valor)
{
    if ($valor === null || trim((string)$valor) === "") {
        return null;
    }

    return numeroBanco($valor);
}