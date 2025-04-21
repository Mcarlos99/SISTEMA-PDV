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

// Inicializar objetos
$compra_obj = new Compra($pdo);
$fornecedor_obj = new Fornecedor($pdo);
$produto_obj = new Produto($pdo);

// Processar ações
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// Processar adição de compra
if ($acao == 'adicionar' && isset($_POST['itens'])) {
    try {
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? intval($_POST['fornecedor_id']) : null;
        $valor_total = floatval($_POST['valor_total']);
        $status = $_POST['status'];
        $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
        $itens = json_decode($_POST['itens'], true);
        
        if (empty($itens)) {
            throw new Exception("É necessário adicionar pelo menos um produto à compra");
        }
        
        $dados = [
            'fornecedor_id' => $fornecedor_id,
            'valor_total' => $valor_total,
            'status' => $status,
            'observacoes' => $observacoes,
            'itens' => $itens
        ];
        
        $compra_id = $compra_obj->adicionar($dados);
        
        if ($compra_id) {
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $fornecedor_nome = "Não informado";
                if ($fornecedor_id) {
                    $fornecedor = $fornecedor_obj->buscarPorId($fornecedor_id);
                    if ($fornecedor) {
                        $fornecedor_nome = $fornecedor['nome'];
                    }
                }
                
                $GLOBALS['log']->registrar(
                    'Compra', 
                    "Nova compra registrada (ID: {$compra_id}) - Fornecedor: {$fornecedor_nome} - Valor: " . 
                    formatarDinheiro($valor_total)
                );
            }
            
            alerta('Compra registrada com sucesso!', 'success');
            header('Location: compras.php?id=' . $compra_id);
            exit;
        } else {
            throw new Exception("Erro ao registrar compra");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar finalização de compra pendente
if ($acao == 'finalizar' && isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);
        $compra = $compra_obj->buscarPorId($id);
        
        if (!$compra) {
            throw new Exception("Compra não encontrada");
        }
        
        if ($compra['status'] != 'pendente') {
            throw new Exception("Esta compra não está pendente");
        }
        
        $resultado = $compra_obj->finalizar($id);
        
        if ($resultado) {
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Compra', 
                    "Compra #{$id} finalizada"
                );
            }
            
            alerta('Compra finalizada com sucesso!', 'success');
            header('Location: compras.php?id=' . $id);
            exit;
        } else {
            throw new Exception("Erro ao finalizar compra");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar cancelamento de compra
if ($acao == 'cancelar' && isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);
        $compra = $compra_obj->buscarPorId($id);
        
        if (!$compra) {
            throw new Exception("Compra não encontrada");
        }
        
        if ($compra['status'] == 'cancelada') {
            throw new Exception("Esta compra já está cancelada");
        }
        
        $resultado = $compra_obj->cancelar($id);
        
        if ($resultado) {
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Compra', 
                    "Compra #{$id} cancelada"
                );
            }
            
            alerta('Compra cancelada com sucesso!', 'success');
            header('Location: compras.php?id=' . $id);
            exit;
        } else {
            throw new Exception("Erro ao cancelar compra");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Verificar se está visualizando uma compra específica
$compra = null;
$produtos_compra = [];
if (isset($_GET['id'])) {
    $compra_id = intval($_GET['id']);
    $compra = $compra_obj->buscarPorId($compra_id);
    
    if ($compra) {
        $produtos_compra = $compra_obj->buscarItens($compra_id);
    }
}

// Template da página
$titulo_pagina = 'Gestão de Compras - EXTREME PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-shopping-basket me-2 text-primary"></i>
                <?php echo $compra ? 'Detalhes da Compra #' . $compra['id'] : 'Gestão de Compras'; ?>
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <?php if ($compra): ?>
                        <li class="breadcrumb-item"><a href="compras.php">Compras</a></li>
                        <li class="breadcrumb-item active">Compra #<?php echo $compra['id']; ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active">Compras</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
        
        <div>
            <?php if (!$compra): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaCompra">
                    <i class="fas fa-plus-circle me-1"></i>
                    Nova Compra
                </button>
            <?php elseif ($compra['status'] == 'pendente'): ?>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a href="compras.php?acao=finalizar&id=<?php echo $compra['id']; ?>" class="btn btn-success mb-2 mb-sm-0" 
                       onclick="return confirm('Tem certeza que deseja finalizar esta compra? Isso irá adicionar os produtos ao estoque.')">
                        <i class="fas fa-check-circle me-1"></i>
                        Finalizar Compra
                    </a>
                    <a href="compras.php?acao=cancelar&id=<?php echo $compra['id']; ?>" class="btn btn-danger" 
                       onclick="return confirm('Tem certeza que deseja cancelar esta compra?')">
                        <i class="fas fa-times-circle me-1"></i>
                        Cancelar Compra
                    </a>
                </div>
            <?php elseif ($compra['status'] == 'finalizada'): ?>
                <a href="compras.php?acao=cancelar&id=<?php echo $compra['id']; ?>" class="btn btn-danger" 
                   onclick="return confirm('Tem certeza que deseja cancelar esta compra? Isso irá remover os produtos do estoque.')">
                    <i class="fas fa-times-circle me-1"></i>
                    Cancelar Compra
                </a>
            <?php endif; ?>
        </div>
    </div>
    <!-- PARTE 2 -->
    <?php if ($compra): ?>
        <!-- Detalhes da Compra -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informações da Compra
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Fornecedor</h6>
                                        <h5 class="mb-1">
                                            <i class="fas fa-truck text-primary me-1"></i>
                                            <?php 
                                            if (isset($compra['fornecedor_nome']) && !empty($compra['fornecedor_nome'])) {
                                                echo esc($compra['fornecedor_nome']);
                                            } else {
                                                echo '<span class="text-muted">Não informado</span>';
                                            }
                                            ?>
                                        </h5>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            Data: <?php echo $compra['data_formatada']; ?>
                                        </p>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-user-circle me-1"></i>
                                            Responsável: <?php echo esc($compra['usuario_nome']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Status</h6>
                                        <?php if ($compra['status'] == 'finalizada'): ?>
                                            <h5 class="text-success mb-0">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Compra Finalizada
                                            </h5>
                                            <p class="text-muted mb-0">Produtos já adicionados ao estoque</p>
                                        <?php elseif ($compra['status'] == 'pendente'): ?>
                                            <h5 class="text-warning mb-0">
                                                <i class="fas fa-clock me-1"></i>
                                                Compra Pendente
                                            </h5>
                                            <p class="text-muted mb-0">Produtos ainda não adicionados ao estoque</p>
                                        <?php else: ?>
                                            <h5 class="text-danger mb-0">
                                                <i class="fas fa-ban me-1"></i>
                                                Compra Cancelada
                                            </h5>
                                            <p class="text-muted mb-0">Esta compra foi cancelada</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card h-100 bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Valor Total</h6>
                                        <h3 class="text-primary mb-0">
                                            <?php echo formatarDinheiro($compra['valor_total']); ?>
                                        </h3>
                                        <?php if (!empty($compra['observacoes'])): ?>
                                            <p class="text-muted mt-2">
                                                <i class="fas fa-comment-alt me-1"></i>
                                                <?php echo esc($compra['observacoes']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Produtos da Compra -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-box me-2"></i>
                    Produtos da Compra
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0" id="tabelaProdutosCompra">
                        <thead>
                            <tr>
                                <th data-priority="1">Código</th>
                                <th data-priority="1">Produto</th>
                                <th data-priority="1">Qtd</th>
                                <th data-priority="2">Preço Un.</th>
                                <th data-priority="2">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($produtos_compra)): ?>
                                <?php foreach ($produtos_compra as $item): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo esc($item['produto_codigo']); ?></span></td>
                                        <td><?php echo esc($item['produto_nome']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $item['quantidade']; ?></span></td>
                                        <td><?php echo formatarDinheiro($item['preco_unitario']); ?></td>
                                        <td><strong><?php echo formatarDinheiro($item['subtotal']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhum produto adicionado a esta compra.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Lista de Compras -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Lista de Compras
                        </h5>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" id="buscarCompra" placeholder="Buscar compra...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0" id="tabelaCompras">
                        <thead>
                            <tr>
                                <th data-priority="1" width="80">ID</th>
                                <th data-priority="1">Fornecedor</th>
                                <th data-priority="2">Data</th>
                                <th data-priority="1">Valor</th>
                                <th data-priority="1">Status</th>
                                <th data-priority="3">Responsável</th>
                                <th data-priority="1" width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $compras = $compra_obj->listar();
                            if (empty($compras)): 
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-shopping-basket fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhuma compra registrada.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($compras as $c): ?>
                                    <tr class="<?php echo $c['status'] == 'pendente' ? 'table-warning' : ($c['status'] == 'cancelada' ? 'table-danger' : ''); ?>">
                                        <td><?php echo $c['id']; ?></td>
                                        <td>
                                            <?php 
                                            if (isset($c['fornecedor_nome']) && !empty($c['fornecedor_nome'])) {
                                                echo esc($c['fornecedor_nome']);
                                            } else {
                                                echo '<span class="text-muted">Não informado</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $c['data_formatada']; ?></td>
                                        <td><strong><?php echo formatarDinheiro($c['valor_total']); ?></strong></td>
                                        <td>
                                            <?php
                                            $status_badges = [
                                                'finalizada' => '<span class="badge bg-success">Finalizada</span>',
                                                'pendente' => '<span class="badge bg-warning text-dark">Pendente</span>',
                                                'cancelada' => '<span class="badge bg-danger">Cancelada</span>'
                                            ];
                                            echo $status_badges[$c['status']] ?? $c['status'];
                                            ?>
                                        </td>
                                        <td><?php echo esc($c['usuario_nome']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="compras.php?id=<?php echo $c['id']; ?>" 
                                                   class="btn btn-primary" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Detalhes"
                                                   style="display: inline-block !important; background-color: #0d6efd !important;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($c['status'] == 'pendente'): ?>
                                                    <a href="compras.php?acao=finalizar&id=<?php echo $c['id']; ?>" 
                                                       class="btn btn-success" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Finalizar"
                                                       style="display: inline-block !important; background-color: #198754 !important;"
                                                       onclick="return confirm('Tem certeza que deseja finalizar esta compra? Isso irá adicionar os produtos ao estoque.')">
                                                        <i class="fas fa-check-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($c['status'] != 'cancelada'): ?>
                                                    <a href="compras.php?acao=cancelar&id=<?php echo $c['id']; ?>" 
                                                       class="btn btn-danger" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Cancelar"
                                                       style="display: inline-block !important; background-color: #dc3545 !important;"
                                                       onclick="return confirm('Tem certeza que deseja cancelar esta compra?')">
                                                        <i class="fas fa-times-circle"></i>
                                                    </a>
                                                <?php endif; ?>
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
    <!-- PARTE 3 -->
    </div>

<!-- Modal Nova Compra -->
<div class="modal fade" id="modalNovaCompra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-shopping-basket me-2"></i>
                    Nova Compra
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNovaCompra" action="compras.php?acao=adicionar" method="post">
                <input type="hidden" name="itens" id="itensCompra">
                <input type="hidden" name="valor_total" id="valorTotalCompra">
                
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="fornecedor_id" class="form-label fw-bold">Fornecedor (opcional):</label>
                            <select class="form-select" id="fornecedor_id" name="fornecedor_id">
                                <option value="">Selecione um fornecedor</option>
                                <?php
                                $fornecedores = $fornecedor_obj->listar();
                                foreach ($fornecedores as $fornecedor) {
                                    echo '<option value="'.$fornecedor['id'].'">'.esc($fornecedor['nome']).'</option>';
                                }
                                ?>
                            </select>
                            <div class="d-flex justify-content-end mt-2">
                                <a href="fornecedores.php?acao=novo" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-plus me-1"></i>
                                    Novo Fornecedor
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-bold">Status da Compra:</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="finalizada">Finalizada (adicionar ao estoque)</option>
                                <option value="pendente">Pendente (não adicionar ao estoque)</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3">Adicionar Produtos</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="produto_busca" class="form-label">Buscar Produto:</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="produto_busca" placeholder="Nome ou código do produto">
                                <button class="btn btn-outline-secondary" type="button" id="btnBuscarProduto">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="produto_id" class="form-label fw-bold">Produto:</label>
                            <select class="form-select" id="produto_id">
                                <option value="">Selecione um produto</option>
                                <?php
                                $produtos = $produto_obj->listar();
                                foreach ($produtos as $p) {
                                    echo '<option value="'.$p['id'].'" data-preco="'.$p['preco_custo'].'" data-codigo="'.$p['codigo'].'">'.esc($p['nome']).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="quantidade" class="form-label fw-bold">Quantidade:</label>
                            <input type="number" class="form-control" id="quantidade" min="1" value="1">
                        </div>
                        <div class="col-md-3">
                            <label for="preco_unitario" class="form-label fw-bold">Preço Unitário:</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="preco_unitario" step="0.01" min="0.01">
                            </div>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" id="btnAdicionarProduto" class="btn btn-success w-100">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="tabelaProdutos">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Produto</th>
                                    <th>Qtd</th>
                                    <th>Preço Un.</th>
                                    <th>Subtotal</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Itens serão adicionados dinamicamente via JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold" id="totalCompra">R$ 0,00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Adicione produtos à compra preenchendo os campos acima e clicando no botão <i class="fas fa-plus"></i>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label fw-bold">Observações (opcional):</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarCompra">
                        <i class="fas fa-save me-1"></i>
                        Salvar Compra
                    </button>
                </div>
            </form>
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
        // Não inicializamos DataTables aqui porque já está sendo inicializado no footer.php
        // Os ids das tabelas já estão na lista de exclusão: '#tabelaCompras,#tabelaProdutosCompra'
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Filtro de busca rápida para tabela de compras
        $('#buscarCompra').on('keyup', function() {
            $('#tabelaCompras').DataTable().search($(this).val()).draw();
        });
        
        // Variáveis para controle dos itens da compra
        var itens = [];
        var totalCompra = 0;
        
        // Busca de produtos por nome/código
        $('#btnBuscarProduto').click(function() {
            var termo = $('#produto_busca').val().toLowerCase().trim();
            if (termo) {
                $('#produto_id option').each(function() {
                    var texto = $(this).text().toLowerCase();
                    var codigo = $(this).data('codigo') || '';
                    if (texto.indexOf(termo) > -1 || (codigo && codigo.toLowerCase().indexOf(termo) > -1)) {
                        $('#produto_id').val($(this).val()).trigger('change');
                        return false; // Interrompe o loop quando encontrar o primeiro
                    }
                });
            }
        });
        
        // Preencher preço do produto quando selecionado
        $('#produto_id').change(function() {
            var option = $(this).find('option:selected');
            var preco = option.data('preco') || 0;
            $('#preco_unitario').val(preco.toFixed(2));
        });
        
        // Adicionar produto à tabela
        $('#btnAdicionarProduto').click(function() {
            var produtoId = $('#produto_id').val();
            var produtoTexto = $('#produto_id option:selected').text();
            var produtoCodigo = $('#produto_id option:selected').data('codigo');
            var quantidade = parseInt($('#quantidade').val()) || 0;
            var precoUnitario = parseFloat($('#preco_unitario').val()) || 0;
            
            if (!produtoId) {
                alert('Selecione um produto');
                return;
            }
            
            if (quantidade <= 0) {
                alert('A quantidade deve ser maior que zero');
                return;
            }
            
            if (precoUnitario <= 0) {
                alert('O preço unitário deve ser maior que zero');
                return;
            }
            
            // Verificar se o produto já foi adicionado
            var itemExistente = false;
            $.each(itens, function(i, item) {
                if (item.produto_id == produtoId) {
                    item.quantidade += quantidade;
                    item.subtotal = item.quantidade * item.preco_unitario;
                    itemExistente = true;
                    return false; // Break the loop
                }
            });
            
            // Se não existe, adiciona novo item
            if (!itemExistente) {
                var item = {
                    produto_id: produtoId,
                    produto_nome: produtoTexto,
                    produto_codigo: produtoCodigo,
                    quantidade: quantidade,
                    preco_unitario: precoUnitario,
                    subtotal: quantidade * precoUnitario
                };
                
                itens.push(item);
            }
            
            // Atualiza a tabela
            atualizarTabela();
            
            // Limpa os campos
            $('#produto_id').val('');
            $('#produto_busca').val('');
            $('#quantidade').val(1);
            $('#preco_unitario').val('');
        });
        
        // Função para atualizar a tabela de produtos
        function atualizarTabela() {
            var tbody = $('#tabelaProdutos tbody');
            tbody.empty();
            
            totalCompra = 0;
            
            $.each(itens, function(i, item) {
                var tr = $('<tr>');
                
                tr.append($('<td>').text(item.produto_codigo));
                tr.append($('<td>').text(item.produto_nome));
                tr.append($('<td>').text(item.quantidade));
                tr.append($('<td>').text(formatarDinheiro(item.preco_unitario)));
                tr.append($('<td>').text(formatarDinheiro(item.subtotal)));
                
                // Botão para remover o item
                var tdAcoes = $('<td>');
                var btnRemover = $('<button>')
                    .attr('type', 'button')
                    .addClass('btn btn-sm btn-danger')
                    .html('<i class="fas fa-trash-alt"></i>')
                    .on('click', function() {
                        itens.splice(i, 1);
                        atualizarTabela();
                    });
                
                tdAcoes.append(btnRemover);
                tr.append(tdAcoes);
                
                tbody.append(tr);
                
                totalCompra += item.subtotal;
            });
            
            // Atualiza o total
            $('#totalCompra').text(formatarDinheiro(totalCompra));
            $('#valorTotalCompra').val(totalCompra);
            
            // Habilita/desabilita o botão de salvar
            if (itens.length > 0) {
                $('#btnSalvarCompra').prop('disabled', false);
            } else {
                $('#btnSalvarCompra').prop('disabled', true);
            }
        }
        
        // Função para formatar valor monetário
        function formatarDinheiro(valor) {
            return 'R$ ' + valor.toFixed(2).replace('.', ',');
        }
        
        // Processar o formulário de nova compra
        $('#formNovaCompra').on('submit', function(e) {
            if (itens.length === 0) {
                e.preventDefault();
                alert('Adicione pelo menos um produto à compra');
                return false;
            }
            
            // Atualiza o campo hidden com os itens em JSON
            $('#itensCompra').val(JSON.stringify(itens));
            
            return true;
        });
    });
</script>

<?php include 'footer.php'; ?>