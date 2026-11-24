<?php

require_once "../config/auth.php";

$tituloPagina = "Cadastrar Motorista";

require_once "../includes/header.php";
?>

<h3>Cadastrar motorista</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="salvar.php">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-control" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">CPF</label>
                    <input type="text" name="cpf" class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">CNH</label>
                    <input type="text" name="cnh" class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Categoria CNH</label>
                    <select name="categoria_cnh" class="form-select">
                        <option value="">Selecione</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="AB">AB</option>
                        <option value="AC">AC</option>
                        <option value="AD">AD</option>
                        <option value="AE">AE</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de vínculo</label>
                    <select name="tipo_vinculo" class="form-select">
                        <option value="">Selecione</option>
                        <option value="Funcionário">Funcionário</option>
                        <option value="Agregado">Agregado</option>
                        <option value="Terceiro">Terceiro</option>
                        <option value="Diarista">Diarista</option>
                        <option value="Autônomo">Autônomo</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Salvar
            </button>

            <a href="listar.php" class="btn btn-secondary">
                Voltar
            </a>
        </form>

    </div>
</div>

<?php require_once "../includes/footer.php"; ?>