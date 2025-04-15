<?php
require_once 'config.php';
verificarLogin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Adicionar categoria
    if (isset($_POST['adicionar'])) {
        $dados = [
            'nome' => $_POST['nome'],
            'descricao' => $_POST['descricao']
        ];
        
        if ($categoria->adicionar($dados)) {
            alerta('Categoria adicionada com sucesso!', 'success');
        } else {
            alerta('Erro ao adicionar categoria!', 'danger');
        }
    }
    
    // Atualizar categoria
    if (isset($_POST['atualizar'])) {
        $id = $_POST['id'];
        $dados = [
            'nome' => $_POST['nome'],
            'descricao' => $_POST['descricao']
        ];
        
        if ($categoria->atualizar($id, $dados)) {
            alerta('Categoria atualizada com sucesso!', 'success');
        } else {
            alerta('Erro ao atualizar categoria!', 'danger');
        }
    }
    
    // Excluir categoria
    if (isset($_POST['excluir'])) {
        $id = $_POST['id'];
        
        if ($categoria->excluir($id)) {
            alerta('Categoria excluída com sucesso!', 'success');
        } else {
            alerta('Não é possível excluir esta categoria pois existem produtos vinculados a ela!', 'danger');
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: categorias.php');
    exit;
}

// Buscar categoria para edição
$categoria_edicao = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $categoria_edicao = $categoria->buscarPorId($id);
}

// Template da página
$titulo_pagina = 'Gerenciamento de Categorias';
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Categorias</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-categoria">
            <i class="fas fa-plus"></i> Nova Categoria
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $categorias = $categoria->listar();
                        foreach ($categorias as $c) {
                            echo '<tr>';
                            echo '<td>'.$c['id'].'</td>';
                            echo '<td>'.$c['nome'].'</td>';
                            echo '<td>'.(mb_strlen($c['descricao']) > 50 ? mb_substr($c['descricao'], 0, 50).'...' : $c['descricao']).'</td>';
                            echo '<td>
                                    <a href="?editar='.$c['id'].'" class="btn btn-sm btn-primary me-1"><i class="fas fa-edit"></i></a>
                                    <a href="#" class="btn btn-sm btn-danger btn-excluir" data-id="'.$c['id'].'" data-nome="'.$c['nome'].'"><i class="fas fa-trash"></i></a>
                                  </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Categoria -->
<div class="modal fade" id="modal-categoria" tabindex="-1" aria-labelledby="modal-categoria-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-categoria-label"><?php echo $categoria_edicao ? 'Editar Categoria' : 'Nova Categoria'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-categoria" method="post" action="">
                    <?php if ($categoria_edicao): ?>
                        <input type="hidden" name="id" value="<?php echo $categoria_edicao['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo $categoria_edicao ? $categoria_edicao['nome'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo $categoria_edicao ? $categoria_edicao['descricao'] : ''; ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-categoria" class="btn btn-primary" name="<?php echo $categoria_edicao ? 'atualizar' : 'adicionar'; ?>">
                    <?php echo $categoria_edicao ? 'Atualizar' : 'Adicionar'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modal-excluir" tabindex="-1" aria-labelledby="modal-excluir-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-excluir-label">Excluir Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a categoria <strong id="nome-categoria-excluir"></strong>?</p>
                <p class="text-danger">Esta ação não poderá ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <form method="post" action="">
                    <input type="hidden" name="id" id="id-categoria-excluir">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="excluir" class="btn btn-danger">Confirmar Exclusão</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Abrir modal de edição automaticamente se tiver categoria para editar
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($categoria_edicao): ?>
        var modalCategoria = new bootstrap.Modal(document.getElementById('modal-categoria'));
        modalCategoria.show();
        <?php endif; ?>
        
        // Configurar modal de exclusão
        var botoesExcluir = document.getElementsByClassName('btn-excluir');
        for (var i = 0; i < botoesExcluir.length; i++) {
            botoesExcluir[i].addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                var nome = this.getAttribute('data-nome');
                document.getElementById('id-categoria-excluir').value = id;
                document.getElementById('nome-categoria-excluir').textContent = nome;
                var modalExcluir = new bootstrap.Modal(document.getElementById('modal-excluir'));
                modalExcluir.show();
            });
        }
    });
</script>

<?php include 'footer.php'; ?>
