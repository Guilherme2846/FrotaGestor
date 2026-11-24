<?php

$local = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];
$host = getenv('FROTA_DB_HOST') ?: ($local['host'] ?? '127.0.0.1');
$porta = getenv('FROTA_DB_PORT') ?: ($local['porta'] ?? 3307);
$banco = getenv('FROTA_DB_NAME') ?: ($local['banco'] ?? 'frotagestor');
$usuario = getenv('FROTA_DB_USER') ?: ($local['usuario'] ?? 'root');
$senha = getenv('FROTA_DB_PASSWORD');
if ($senha === false) {
    $senha = $local['senha'] ?? '';
}

try {
    $pdo = new PDO("mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4", $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    exit("Banco de dados indisponível. Confira o serviço MySQL e a configuração local.\n");
}
