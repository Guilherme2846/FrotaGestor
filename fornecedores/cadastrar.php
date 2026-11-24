<?php

require_once "../config/auth.php";

$tituloPagina = "Cadastrar Fornecedor";

require_once "../includes/header.php";
?>

<h3>Cadastrar fornecedor</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="salvar.php">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome *</label>
                    <input
                        type="text"
                        name="nome"
                        class="form-control"
                        placeholder="Ex: Posto São João, Oficina Diesel..."
                        required
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" class="form-select" required>
                        <option value="posto">Posto</option>
                        <option value="oficina">Oficina</option>
                        <option value="borracharia">Borracharia</option>
                        <option value="pecas">Peças</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">CNPJ</label>
                    <input type="text" name="cnpj" class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-control">
                </div>

                <div class="col-md-5 mb-3">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="cidade" class="form-control">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Observação</label>
                    <textarea
                        name="observacao"
                        class="form-control"
                        rows="3"
                        placeholder="Informações adicionais sobre o fornecedor"
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