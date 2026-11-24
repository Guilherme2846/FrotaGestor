<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../config/conexao.php';

$email = trim($argv[1] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Uso: php bin/criar-admin.php seu-email\n");
}
$senha = bin2hex(random_bytes(12));
$stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel) VALUES (?, ?, ?, 'admin')");
try {
    $stmt->execute(['Administrador', $email, password_hash($senha, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
    fwrite(STDERR, "Não foi possível criar o usuário. Confira o esquema do banco e se o e-mail já está cadastrado.\n");
    exit(1);
}
echo "Conta criada. Guarde a senha em local privado: $senha\n";
