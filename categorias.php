<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar permissões
if (!in_array($_SESSION['usuario_nivel'], ['admin', 'gerente'])) {
    alerta('Você não tem permissão para acessar esta página.', 'danger');
    header('Location: index.php');
    exit;
}

// Processar ações
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$categoria_obj = new Categoria($pdo);

// Processar adição de categoria
if ($acao == 'adicionar' && isset($_POST['nome'])) {
    try {
        $nome = trim($_POST['nome']);
        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        
        if (empty($nome)) {
            throw new Exception("O nome da categoria é obrigatório");
        }
        
        $dados = [
            'nome' => $nome,
            'descricao' => $descricao
        ];
        
        $resultado = $categoria_obj->adicionar($dados);
        
        if ($resultado) {
            // Registrar no log
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar('Categoria', "Nova categoria '{$nome}' adicionada");
            }
            
            alerta('Categoria adicionada com sucesso!', 'success');
            header('Location: categorias.php');
            exit;
        } else {
            throw new Exception("Erro ao adicionar categoria");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar atualização de categoria
if ($acao == 'atualizar' && isset($_POST['id'], $_POST['nome'])) {
    try {
        $id = intval($_POST['id']);
        $nome = trim($_POST['nome']);
        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        
        if (empty($nome)) {
            throw new Exception("O nome da categoria é obrigatório");
        }
        
        $dados = [
            'nome' => $nome,
            'descricao' => $descricao
        ];
        
        $resultado = $categoria_obj->atualizar($id, $dados);
        
        if ($resultado) {
            // Registrar no log
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar('Categoria', "Categoria '{$nome}' (ID: {$id}) atualizada");
            }
            
            alerta('Categoria atualizada com sucesso!', 'success');
            header('Location: categorias.php');
            exit;
        } else {
            throw new Exception("Erro ao atualizar categoria");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar exclusão de categoria
if ($acao == 'excluir' && isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);
        $categoria = $categoria_obj->buscarPorId($id);
        
        if (!$categoria) {
            throw new Exception("Categoria não encontrada");
        }
        
        $resultado = $categoria_obj->excluir($id);
        
        if ($resultado) {
            // Registrar no log
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar('Categoria', "Categoria '{$categoria['nome']}' (ID: {$id}) excluída");
            }
            
            alerta('Categoria excluída com sucesso!', 'success');
            header('Location: categorias.php');
            exit;
        } else {
            throw new Exception("Não é possível excluir esta categoria porque existem produtos associados a ela");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Verificar se está visualizando uma categoria específica
$categoria = null;
if (isset($_GET['id'])) {
    $categoria_id = intval($_GET['id']);
    $categoria = $categoria_obj->buscarPorId($categoria_id);
}

// Template da página
$titulo_pagina = 'Gerenciamento de Categorias - Sistema PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-tags me-2 text-primary"></i>
                <?php echo $categoria ? 'Editar Categoria: ' . esc($categoria['nome']) : 'Gerenciamento de Categorias'; ?>
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <?php if ($categoria): ?>
                        <li class="breadcrumb-item"><a href="categorias.php">Categorias</a></li>
                        <li class="breadcrumb-item active">Editar Categoria</li>
                    <?php else: ?>
                        <li class="breadcrumb-item active">Categorias</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
        
        <div>
            <?php if (!$categoria): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaCategoria">
                    <i class="fas fa-plus-circle me-1"></i>
                    Nova Categoria
                </button>
            <?php else: ?>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a href="categorias.php" class="btn btn-secondary mb-2 mb-sm-0">
                        <i class="fas fa-arrow-left me-1"></i>
                        Voltar para Lista
                    </a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalExcluirCategoria">
                        <i class="fas fa-trash-alt me-1"></i>
                        Excluir Categoria
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($categoria): ?>
        <!-- Formulário de Edição -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Editar Categoria
                </h5>
            </div>
            <div class="card-body">
                <form action="categorias.php?acao=atualizar" method="post">
                    <input type="hidden" name="id" value="<?php echo $categoria['id']; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label fw-bold">Nome da Categoria:</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo esc($categoria['nome']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label fw-bold">Descrição:</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4"><?php echo esc($categoria['descricao']); ?></textarea>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end">
                        <a href="categorias.php" class="btn btn-secondary me-2">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Lista de Categorias -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Lista de Categorias
                        </h5>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" id="buscarCategoria" placeholder="Buscar categoria...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0" id="tabelaCategorias">
                        <thead>
                            <tr>
                                <th width="80">ID</th>
                                <th data-priority="1">Nome</th>
                                <th data-priority="2">Descrição</th>
                                <th data-priority="3" width="150">Data de Criação</th>
                                <th data-priority="1" width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $categorias = $categoria_obj->listar();
                            if (empty($categorias)): 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhuma categoria encontrada.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categorias as $cat): ?>
                                    <tr>
                                        <td><?php echo $cat['id']; ?></td>
                                        <td><?php echo esc($cat['nome']); ?></td>
                                        <td><?php echo !empty($cat['descricao']) ? esc(substr($cat['descricao'], 0, 80)) . (strlen($cat['descricao']) > 80 ? '...' : '') : '<span class="text-muted">-</span>'; ?></td>
                                        <td><?php echo isset($cat['criado_em']) ? $cat['criado_em'] : formatarData($cat['criado_em']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="categorias.php?id=<?php echo $cat['id']; ?>" 
                                                   class="btn btn-primary" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Editar"
                                                   style="display: inline-block !important; background-color: #0d6efd !important;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <a href="#" 
                                                   class="btn btn-danger btn-excluir-categoria" 
                                                   data-id="<?php echo $cat['id']; ?>"
                                                   data-nome="<?php echo esc($cat['nome']); ?>"
                                                   data-bs-toggle="tooltip" 
                                                   title="Excluir"
                                                   style="display: inline-block !important; background-color: #dc3545 !important;">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Categoria -->
<div class="modal fade" id="modalNovaCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Nova Categoria
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="categorias.php?acao=adicionar" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label fw-bold">Nome da Categoria:</label>
                        <input type="text" class="form-control form-control-lg" id="nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição (opcional):</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>
                        Adicionar Categoria
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmação Excluir Categoria -->
<div class="modal fade" id="modalExcluirCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($categoria): ?>
                    <p>Tem certeza que deseja excluir a categoria <strong><?php echo esc($categoria['nome']); ?></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Esta ação não poderá ser desfeita e só é possível se a categoria não estiver associada a nenhum produto.
                    </div>
                <?php else: ?>
                    <p>Selecione uma categoria para excluir.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <?php if ($categoria): ?>
                    <a href="categorias.php?acao=excluir&id=<?php echo $categoria['id']; ?>" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>
                        Confirmar Exclusão
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmação Excluir (para botão na lista) -->
<div class="modal fade" id="modalConfirmExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a categoria <strong id="categoriaNome"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Esta ação não poderá ser desfeita e só é possível se a categoria não estiver associada a nenhum produto.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <a href="#" id="btnConfirmExcluir" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-1"></i>
                    Confirmar Exclusão
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Garantir que botões de ação em tabelas responsivas mantenham aparência correta */
    .datatable .btn {
        display: inline-block !important;
    }
    
    /* Forçar cores de background nos botões de ação */
    .datatable .btn-info {
        background-color: #0dcaf0 !important;
        border-color: #0dcaf0 !important;
    }
    
    .datatable .btn-primary {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    
    .datatable .btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    
    .datatable .btn-success {
        background-color: #198754 !important;
        border-color: #198754 !important;
    }
    
    /* Garantir que botões em linhas expandidas mantenham estilo */
    .dtr-details .btn {
        display: inline-block !important;
        margin: 0.1rem;
    }
    
    /* Manter cor do texto nos botões */
    .datatable .btn-info.text-white {
        color: #fff !important;
    }
</style>

<script>
    $(document).ready(function() {
        // Inicializa DataTables com responsividade
        $('#tabelaCategorias').DataTable({
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
            "order": [[0, 'asc']], // Ordenar por ID crescente
            "autoWidth": false,
            "columnDefs": [
                { responsivePriority: 1, targets: [0, 1, 4] }, // Prioridade alta 
                { responsivePriority: 2, targets: [2] },       // Prioridade média
                { responsivePriority: 3, targets: [3] }        // Prioridade baixa
            ]
        });
        
        // Filtro de busca rápida para tabela de categorias
        $('#buscarCategoria').on('keyup', function() {
            $('#tabelaCategorias').DataTable().search($(this).val()).draw();
        });
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Manipular exclusão de categoria
        $('.btn-excluir-categoria').on('click', function(e) {
            e.preventDefault();
            
            var id = $(this).data('id');
            var nome = $(this).data('nome');
            
            $('#categoriaNome').text(nome);
            $('#btnConfirmExcluir').attr('href', 'categorias.php?acao=excluir&id=' + id);
            
            var modalExcluir = new bootstrap.Modal(document.getElementById('modalConfirmExcluir'));
            modalExcluir.show();
        });
    });
</script>

<?php include 'footer.php'; ?>