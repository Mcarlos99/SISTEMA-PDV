<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Processar ação de exclusão
if (isset($_GET['acao']) && $_GET['acao'] == 'excluir' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($produto->excluir($id)) {
        alerta('Produto excluído com sucesso!', 'success');
    } else {
        alerta('Erro ao excluir o produto.', 'danger');
    }
    
    header('Location: produtos.php');
    exit;
}

// Template da página
$titulo_pagina = 'Produtos - EXTREME PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-box me-2 text-primary"></i>
                Produtos
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item active">Produtos</li>
                </ol>
            </nav>
        </div>
        <a href="produtos.php?acao=novo" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>
            <span class="d-none d-sm-inline">Novo Produto</span>
            <span class="d-inline d-sm-none">Novo</span>
        </a>
    </div>
    
    <?php if (isset($_GET['acao']) && $_GET['acao'] == 'novo'): ?>
        <!-- Formulário de Novo Produto -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    Adicionar Novo Produto
                </h5>
            </div>
            <div class="card-body">
                <form action="produtos_processar.php" method="post" class="row g-3">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="col-md-4 col-lg-3">
                        <label for="codigo" class="form-label">Código/Barcode</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-barcode"></i>
                            </span>
                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                        </div>
                    </div>
                    
                    <div class="col-md-8 col-lg-5">
                        <label for="nome" class="form-label">Nome do Produto</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-box"></i>
                            </span>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                    </div>
                    
                    <div class="col-md-12 col-lg-4">
                        <label for="categoria_id" class="form-label">Categoria</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-tags"></i>
                            </span>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">Selecione uma categoria</option>
                                <?php
                                $categorias = (new Categoria($pdo))->listar();
                                foreach ($categorias as $cat) {
                                    echo '<option value="'.$cat['id'].'">'.esc($cat['nome']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="col-sm-6 col-md-3">
                        <label for="preco_custo" class="form-label">Preço de Custo</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="preco_custo" name="preco_custo" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-3">
                        <label for="preco_venda" class="form-label">Preço de Venda</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="preco_venda" name="preco_venda" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-3">
                        <label for="estoque_atual" class="form-label">Estoque Atual</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-cubes"></i>
                            </span>
                            <input type="number" class="form-control" id="estoque_atual" name="estoque_atual" min="0" value="0" required>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-3">
                        <label for="estoque_minimo" class="form-label">Estoque Mínimo</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                            <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo" min="0" value="5" required>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" checked>
                            <label class="form-check-label" for="ativo">Produto ativo</label>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <hr>
                        <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                            <a href="produtos.php" class="btn btn-secondary mb-2 mb-sm-0">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Salvar Produto
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif (isset($_GET['acao']) && $_GET['acao'] == 'editar' && isset($_GET['id'])): ?>
        <?php
        $id = intval($_GET['id']);
        $prod = $produto->buscarPorId($id);
        
        if (!$prod) {
            alerta('Produto não encontrado.', 'danger');
            echo '<script>window.location.href = "produtos.php";</script>';
            exit;
        }
        ?>
        
        <!-- Formulário de Edição -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Editar Produto
                </h5>
            </div>
            <div class="card-body">
                <form action="produtos_processar.php" method="post" class="row g-3">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                    
                    <div class="col-md-4 col-lg-3">
                        <label for="codigo" class="form-label">Código/Barcode</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-barcode"></i>
                            </span>
                            <input type="text" class="form-control" id="codigo" name="codigo" value="<?php echo esc($prod['codigo']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-8 col-lg-5">
                        <label for="nome" class="form-label">Nome do Produto</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-box"></i>
                            </span>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo esc($prod['nome']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-12 col-lg-4">
                        <label for="categoria_id" class="form-label">Categoria</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-tags"></i>
                            </span>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">Selecione uma categoria</option>
                                <?php
                                $categorias = (new Categoria($pdo))->listar();
                                foreach ($categorias as $cat) {
                                    $selected = ($cat['id'] == $prod['categoria_id']) ? 'selected' : '';
                                    echo '<option value="'.$cat['id'].'" '.$selected.'>'.esc($cat['nome']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo esc($prod['descricao']); ?></textarea>
                    </div>
                    
                    <div class="col-sm-6 col-md-3">
                        <label for="preco_custo" class="form-label">Preço de Custo</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="preco_custo" name="preco_custo" step="0.01" min="0" value="<?php echo $prod['preco_custo']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-3">
                        <label for="preco_venda" class="form-label">Preço de Venda</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="preco_venda" name="preco_venda" step="0.01" min="0" value="<?php echo $prod['preco_venda']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-3">
                        <label for="estoque_atual" class="form-label">Estoque Atual</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-cubes"></i>
                            </span>
                            <input type="number" class="form-control" id="estoque_atual" name="estoque_atual" min="0" value="<?php echo $prod['estoque_atual']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-3">
                        <label for="estoque_minimo" class="form-label">Estoque Mínimo</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                            <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo" min="0" value="<?php echo $prod['estoque_minimo']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" <?php echo $prod['ativo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="ativo">Produto ativo</label>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <hr>
                        <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                            <a href="produtos.php" class="btn btn-secondary mb-2 mb-sm-0">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Listagem de Produtos -->
        <div class="card shadow">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-boxes me-2"></i>
                            Lista de Produtos
                        </h5>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" id="buscarProduto" placeholder="Buscar produto...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
            <div class="table-responsive">
    <table class="table table-hover datatable mb-0" id="tabelaProdutos" width="100%">
        <thead>
            <tr>
                <th data-priority="1">Código</th>
                <th data-priority="1">Nome</th>
                <th data-priority="2">Categoria</th>
                <th data-priority="3">Preço Custo</th>
                <th data-priority="2">Preço Venda</th>
                <th data-priority="1">Estoque</th>
                <th data-priority="2">Status</th>
                <th data-priority="1" width="120">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $produtos = $produto->listar();
            foreach ($produtos as $p) {
                $status_class = $p['ativo'] ? 'success' : 'danger';
                $status_text = $p['ativo'] ? 'Ativo' : 'Inativo';
                
                $estoque_class = 'success';
                if ($p['estoque_atual'] <= 0) {
                    $estoque_class = 'danger';
                } else if ($p['estoque_atual'] <= $p['estoque_minimo']) {
                    $estoque_class = 'warning';
                }
            ?>
                <tr>
                    <td><span class="badge bg-secondary"><?php echo esc($p['codigo']); ?></span></td>
                    <td><?php echo esc($p['nome']); ?></td>
                    <td><?php echo esc($p['categoria_nome'] ?? 'Sem categoria'); ?></td>
                    <td>R$ <?php echo number_format($p['preco_custo'], 2, ',', '.'); ?></td>
                    <td>R$ <?php echo number_format($p['preco_venda'], 2, ',', '.'); ?></td>
                    <td><span class="badge bg-<?php echo $estoque_class; ?>"><?php echo $p['estoque_atual']; ?></span></td>
                    <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="produtos.php?acao=editar&id=<?php echo $p['id']; ?>" class="btn btn-primary" data-bs-toggle="tooltip" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="estoque.php?produto_id=<?php echo $p['id']; ?>" class="btn btn-info text-white" data-bs-toggle="tooltip" title="Movimentações">
                                <i class="fas fa-history"></i>
                            </a>
                            <button type="button" class="btn btn-danger btn-excluir" data-id="<?php echo $p['id']; ?>" data-nome="<?php echo esc($p['nome']); ?>" data-bs-toggle="tooltip" title="Excluir">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
            <div class="card-footer">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <span class="mb-2 mb-md-0">Mostrando <?php echo count($produtos); ?> produtos</span>
                    <div>
                        <a href="relatorios.php?tipo=produtos" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-file-export me-1"></i>
                            Exportar Dados
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de Confirmação de Exclusão -->
        <div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Confirmação de Exclusão
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Você está prestes a excluir o produto: <strong id="produtoNome"></strong></p>
                        <p>Esta ação não poderá ser desfeita. Deseja continuar?</p>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Nota: A exclusão apenas desativará o produto, mantendo seu histórico intacto.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <a href="#" id="btnConfirmarExclusao" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i>
                            Confirmar Exclusão
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
    $(document).ready(function() {
        // Inicializa DataTables com recursos avançados
        var table = $('#tabelaProdutos').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            },
            "pageLength": 25,
            "responsive": {
                details: {
                    display: $.fn.dataTable.Responsive.display.childRowImmediate,
                    type: 'column',
                    renderer: function(api, rowIdx, columns) {
                        var data = $.map(columns, function(col, i) {
                            return col.hidden ?
                                '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                                    '<td class="fw-bold">' + col.title + ':</td> ' +
                                    '<td>' + col.data + '</td>' +
                                '</tr>' :
                                '';
                        }).join('');
                        
                        return data ? $('<table class="table table-sm mb-0"></table>').append(data) : false;
                    }
                }
            },
            "order": [[1, 'asc']], // Ordenar por nome de produto
            "autoWidth": false,
            "columnDefs": [
                { responsivePriority: 1, targets: [0, 1, 5, 7] }, // Prioridade alta (sempre visível)
                { responsivePriority: 2, targets: [2, 4, 6] },    // Prioridade média
                { responsivePriority: 3, targets: 3 },            // Prioridade baixa
                { orderable: false, targets: 7 }                  // Coluna ações não ordenável
            ]
        });
        
        // Filtro de busca rápida vinculado ao campo personalizado
        $('#buscarProduto').on('keyup', function() {
            table.search($(this).val()).draw();
        });
        
        // Lógica do modal de exclusão
        $('.btn-excluir').click(function() {
            const id = $(this).data('id');
            const nome = $(this).data('nome');
            
            $('#produtoNome').text(nome);
            $('#btnConfirmarExclusao').attr('href', 'produtos.php?acao=excluir&id=' + id);
            
            $('#modalExcluir').modal('show');
        });
        
        // Cálculo automático de margem de lucro
        $('#preco_custo, #preco_venda').on('input', function() {
            const custo = parseFloat($('#preco_custo').val()) || 0;
            const venda = parseFloat($('#preco_venda').val()) || 0;
            
            if (custo > 0 && venda > 0) {
                const lucro = venda - custo;
                const margemLucro = (lucro / custo * 100).toFixed(2);
                
                // Se já existe, atualiza, senão cria
                if ($('#margem-info').length) {
                    $('#margem-info').html(`Margem de lucro: <strong>${margemLucro}%</strong>`);
                } else {
                    $('<div id="margem-info" class="text-info mt-2">Margem de lucro: <strong>' + margemLucro + '%</strong></div>')
                        .insertAfter('#preco_venda');
                }
            }
        });
        
        // Ajuste responsivo para campos de formulário dentro da tabela
        $('.datatable').on('draw.dt', function() {
            $('input, select, textarea').on('focus', function() {
                $(this).closest('.table-responsive').css('overflow-x', 'visible');
            }).on('blur', function() {
                $(this).closest('.table-responsive').css('overflow-x', 'auto');
            });
        });
        
        // Atualizar ao redimensionar a janela
        $(window).resize(function() {
            if (table) {
                table.responsive.recalc();
            }
        });
    });
</script>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>