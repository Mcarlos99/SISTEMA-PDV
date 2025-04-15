<?php
require_once 'config.php';
verificarLogin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Adicionar produto
    if (isset($_POST['adicionar'])) {
        $dados = [
            'codigo' => $_POST['codigo'],
            'nome' => $_POST['nome'],
            'descricao' => $_POST['descricao'],
            'preco_custo' => str_replace(',', '.', $_POST['preco_custo']),
            'preco_venda' => str_replace(',', '.', $_POST['preco_venda']),
            'estoque_atual' => $_POST['estoque_atual'],
            'estoque_minimo' => $_POST['estoque_minimo'],
            'categoria_id' => $_POST['categoria_id'],
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];
        
        if ($produto->adicionar($dados)) {
            alerta('Produto adicionado com sucesso!', 'success');
        } else {
            alerta('Erro ao adicionar produto!', 'danger');
        }
    }
    
    // Atualizar produto
    if (isset($_POST['atualizar'])) {
        $id = $_POST['id'];
        $dados = [
            'codigo' => $_POST['codigo'],
            'nome' => $_POST['nome'],
            'descricao' => $_POST['descricao'],
            'preco_custo' => str_replace(',', '.', $_POST['preco_custo']),
            'preco_venda' => str_replace(',', '.', $_POST['preco_venda']),
            'estoque_atual' => $_POST['estoque_atual'],
            'estoque_minimo' => $_POST['estoque_minimo'],
            'categoria_id' => $_POST['categoria_id'],
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];
        
        if ($produto->atualizar($id, $dados)) {
            alerta('Produto atualizado com sucesso!', 'success');
        } else {
            alerta('Erro ao atualizar produto!', 'danger');
        }
    }
    
    // Excluir produto
    if (isset($_POST['excluir'])) {
        $id = $_POST['id'];
        
        if ($produto->excluir($id)) {
            alerta('Produto excluído com sucesso!', 'success');
        } else {
            alerta('Erro ao excluir produto!', 'danger');
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: produtos.php');
    exit;
}

// Buscar produto para edição
$produto_edicao = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $produto_edicao = $produto->buscarPorId($id);
}

// Template da página
$titulo_pagina = 'Gerenciamento de Produtos';
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Produtos</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-produto">
            <i class="fas fa-plus"></i> Novo Produto
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Preço Custo</th>
                            <th>Preço Venda</th>
                            <th>Estoque</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $produtos = $produto->listar();
                        foreach ($produtos as $p) {
                            echo '<tr>';
                            echo '<td>'.$p['codigo'].'</td>';
                            echo '<td>'.$p['nome'].'</td>';
                            echo '<td>'.$p['categoria_nome'].'</td>';
                            echo '<td>'.formatarDinheiro($p['preco_custo']).'</td>';
                            echo '<td>'.formatarDinheiro($p['preco_venda']).'</td>';
                            echo '<td>'.($p['estoque_atual'] <= $p['estoque_minimo'] ? '<span class="text-danger">'.$p['estoque_atual'].'</span>' : $p['estoque_atual']).'</td>';
                            echo '<td>'.($p['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>').'</td>';
                            echo '<td>
                                    <a href="?editar='.$p['id'].'" class="btn btn-sm btn-primary me-1"><i class="fas fa-edit"></i></a>
                                    <a href="#" class="btn btn-sm btn-danger btn-excluir" data-id="'.$p['id'].'" data-nome="'.$p['nome'].'"><i class="fas fa-trash"></i></a>
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

<!-- Modal de Produto -->
<div class="modal fade" id="modal-produto" tabindex="-1" aria-labelledby="modal-produto-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-produto-label"><?php echo $produto_edicao ? 'Editar Produto' : 'Novo Produto'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-produto" method="post" action="">
                    <?php if ($produto_edicao): ?>
                        <input type="hidden" name="id" value="<?php echo $produto_edicao['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Código *</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required value="<?php echo $produto_edicao ? $produto_edicao['codigo'] : ''; ?>">
                        </div>
                        <div class="col-md-8">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo $produto_edicao ? $produto_edicao['nome'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo $produto_edicao ? $produto_edicao['descricao'] : ''; ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="categoria_id" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <?php
                                $categorias = $categoria->listar();
                                foreach ($categorias as $c) {
                                    $selected = $produto_edicao && $produto_edicao['categoria_id'] == $c['id'] ? 'selected' : '';
                                    echo '<option value="'.$c['id'].'" '.$selected.'>'.$c['nome'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="preco_custo" class="form-label">Preço de Custo *</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="preco_custo" name="preco_custo" required value="<?php echo $produto_edicao ? number_format($produto_edicao['preco_custo'], 2, ',', '.') : '0,00'; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="preco_venda" class="form-label">Preço de Venda *</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="preco_venda" name="preco_venda" required value="<?php echo $produto_edicao ? number_format($produto_edicao['preco_venda'], 2, ',', '.') : '0,00'; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="estoque_atual" class="form-label">Estoque Atual *</label>
                            <input type="number" class="form-control" id="estoque_atual" name="estoque_atual" min="0" required value="<?php echo $produto_edicao ? $produto_edicao['estoque_atual'] : '0'; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="estoque_minimo" class="form-label">Estoque Mínimo *</label>
                            <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo" min="0" required value="<?php echo $produto_edicao ? $produto_edicao['estoque_minimo'] : '5'; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="ativo" name="ativo" <?php echo (!$produto_edicao || $produto_edicao['ativo']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativo">Produto Ativo</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-produto" class="btn btn-primary" name="<?php echo $produto_edicao ? 'atualizar' : 'adicionar'; ?>">
                    <?php echo $produto_edicao ? 'Atualizar' : 'Adicionar'; ?>
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
                <h5 class="modal-title" id="modal-excluir-label">Excluir Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o produto <strong id="nome-produto-excluir"></strong>?</p>
                <p class="text-danger">Esta ação não poderá ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <form method="post" action="">
                    <input type="hidden" name="id" id="id-produto-excluir">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="excluir" class="btn btn-danger">Confirmar Exclusão</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Abrir modal de edição automaticamente se tiver produto para editar
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($produto_edicao): ?>
        var modalProduto = new bootstrap.Modal(document.getElementById('modal-produto'));
        modalProduto.show();
        <?php endif; ?>
        
        // Configurar modal de exclusão
        var botoesExcluir = document.getElementsByClassName('btn-excluir');
        for (var i = 0; i < botoesExcluir.length; i++) {
            botoesExcluir[i].addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                var nome = this.getAttribute('data-nome');
                document.getElementById('id-produto-excluir').value = id;
                document.getElementById('nome-produto-excluir').textContent = nome;
                var modalExcluir = new bootstrap.Modal(document.getElementById('modal-excluir'));
                modalExcluir.show();
            });
        }
    });
</script>

<?php include 'footer.php'; ?>
