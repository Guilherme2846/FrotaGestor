<?php

require_once "../config/auth.php";

$tituloPagina = "Cadastrar Operação";

require_once "../includes/header.php";
?>

<h3>Cadastrar operação</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="salvar.php">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome da operação *</label>
                    <input
                        type="text"
                        name="nome"
                        class="form-control"
                        placeholder="Ex: Special Dog, Arroz quebrado, Transferência..."
                        required
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Cliente</label>
                    <input
                        type="text"
                        name="cliente"
                        class="form-control"
                        placeholder="Ex: Special Dog Company"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativa">Ativa</option>
                        <option value="inativa">Inativa</option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea
                        name="observacao"
                        class="form-control"
                        rows="3"
                        placeholder="Informações adicionais sobre a operação"
                    ></textarea>
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