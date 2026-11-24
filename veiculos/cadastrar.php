<?php

require_once "../config/auth.php";

$tituloPagina = "Cadastrar Veículo";

require_once "../includes/header.php";
?>

<h3>Cadastrar veículo</h3>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <form method="POST" action="salvar.php">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Placa *</label>
                    <input type="text" name="placa" class="form-control" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" class="form-control">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Modelo *</label>
                    <input type="text" name="modelo" class="form-control" required>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Ano</label>
                    <input type="number" name="ano" class="form-control">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo</label>
                    <input type="text" name="tipo" class="form-control" placeholder="Cavalo, truck, carreta...">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Eixos</label>
                    <input type="text" name="eixos" class="form-control" placeholder="4x2, 6x2, 6x4...">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">KM atual</label>
                    <input type="number" step="0.01" name="km_atual" class="form-control" value="0">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ativo">Ativo</option>
                        <option value="manutencao">Manutenção</option>
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