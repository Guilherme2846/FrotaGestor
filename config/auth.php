<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    $pastaAtual = basename(dirname($_SERVER['SCRIPT_NAME']));

    if (strtolower($pastaAtual) === 'frotagestor' || $pastaAtual === '') {
        header("Location: login.php");
    } else {
        header("Location: ../login.php");
    }

    exit;
}