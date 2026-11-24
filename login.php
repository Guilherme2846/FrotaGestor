<?php

session_start();
require_once "config/conexao.php";

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $senha = trim($_POST["senha"] ?? "");

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario["senha"])) {
        session_regenerate_id(true);
        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["usuario_nome"] = $usuario["nome"];
        $_SESSION["usuario_nivel"] = $usuario["nivel"];

        header("Location: dashboard.php");
        exit;
    } else {
        $erro = "Usuário ou senha inválidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - FrotaGestor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h4 class="text-center mb-4">FrotaGestor</h4>

                    <?php if ($erro): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($erro) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="login">Usuário ou e-mail</label>
                            <input id="login" type="text" name="email" autocomplete="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="senha">Senha</label>
                            <input id="senha" type="password" name="senha" autocomplete="current-password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Entrar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
