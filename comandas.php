<?php
require_once 'config.php';
verificarLogin();

// Adicionando código para exibir erros (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicializa a classe Comanda
$comanda = new Comanda($pdo);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Abrir comanda
        if (isset($_POST['abrir_comanda'])) {
            $cliente_id = $_POST['cliente_id'];
            $observacoes = $_POST['observacoes'] ?? '';
            
            if (empty($cliente_id)) {
                throw new Exception("Selecione um cliente para abrir a comanda.");
            }
            
            $comanda_id = $comanda->abrir($cliente_id, $observacoes);
            
            alerta('Comanda aberta com sucesso!', 'success');
            header('Location: comandas.php?id=' . $comanda_id);
            exit;
        }
        
        // Adicionar produto à comanda
        if (isset($_POST['adicionar_produto'])) {
            $comanda_id = $_POST['comanda_id'];
            $produto_id = $_POST['produto_id'];
            $quantidade = $_POST['quantidade'];
            $observacoes = $_POST['observacoes'] ?? '';
            
            $comanda->adicionarProduto($comanda_id, $produto_id, $quantidade, $observacoes);
            
            alerta('Produto adicionado com sucesso!', 'success');
            header('Location: comandas.php?id=' . $comanda_id);
            exit;
        }
        
        // Remover produto da comanda
        if (isset($_POST['remover_produto'])) {
            $item_id = $_POST['item_id'];
            $comanda_id = $_POST['comanda_id'];
            
            $comanda->removerProduto($item_id);
            
            alerta('Produto removido com sucesso!', 'success');
            header('Location: comandas.php?id=' . $comanda_id);
            exit;
        }
        
        // Fechar comanda
        if (isset($_POST['fechar_comanda'])) {
            $comanda_id = $_POST['comanda_id'];
            $forma_pagamento = $_POST['forma_pagamento'];
            $desconto = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['desconto']);
            $observacoes = $_POST['observacoes'] ?? '';
            
            $resultado = $comanda->fechar($comanda_id, $forma_pagamento, $desconto, $observacoes);
            
            alerta('Comanda fechada com sucesso! Venda #' . $resultado['venda_id'] . ' gerada.', 'success');
            header('Location: vendas.php?id=' . $resultado['venda_id']);
            exit;
        }
        
        // Cancelar comanda
        if (isset($_POST['cancelar_comanda'])) {
            $comanda_id = $_POST['comanda_id'];
            $observacoes = $_POST['observacoes'] ?? '';
            
            $comanda->cancelar($comanda_id, $observacoes);
            
            alerta('Comanda cancelada com sucesso!', 'success');
            header('Location: comandas.php');
            exit;
        }
    } catch (Exception $e) {
        alerta('Erro: ' . $e->getMessage(), 'danger');
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Inicializa variáveis
$comanda_atual = null;
$itens_comanda = [];
$detalhes_cliente = null;

// Verificar se quer mostrar detalhes de uma comanda
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $comanda_atual = $comanda->buscarPorId($id);
    
    if ($comanda_atual) {
        $itens_comanda = $comanda->listarProdutos($id);
        $detalhes_cliente = $cliente->buscarPorId($comanda_atual['cliente_id']);
    }
}

// Filtro para listagem de comandas
$filtro = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filtro['status'] = $_GET['status'];
}

if (isset($_GET['cliente']) && !empty($_GET['cliente'])) {
    $filtro['cliente_id'] = $_GET['cliente'];
}

// Listar comandas
$lista_comandas = $comanda->listar($filtro);

// Template da página
$titulo_pagina = 'Gerenciamento de Comandas';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Títulos e ações principais -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $comanda_atual ? 'Detalhes da Comanda #' . $comanda_atual['id'] : 'Comandas'; ?></h1>
        <div>
            <?php if ($comanda_atual): ?>
                <a href="comandas.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaComanda">
                    <i class="fas fa-plus"></i> Nova Comanda
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($comanda_atual): ?>
    <!-- Detalhes da Comanda -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informações da Comanda</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status:</label>
                        <?php
                        if ($comanda_atual['status'] == 'aberta') {
                            echo '<span class="badge bg-success">Aberta</span>';
                        } else if ($comanda_atual['status'] == 'fechada') {
                            echo '<span class="badge bg-secondary">Fechada</span>';
                        } else {
                            echo '<span class="badge bg-danger">Cancelada</span>';
                        }
                        ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente:</label>
                        <div class="fw-bold"><?php echo $comanda_atual['cliente_nome']; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Data de Abertura:</label>
                        <div><?php echo $comanda_atual['data_abertura_formatada']; ?></div>
                    </div>
                    
                    <?php if ($comanda_atual['status'] != 'aberta'): ?>
                    <div class="mb-3">
                        <label class="form-label">Data de Fechamento:</label>
                        <div><?php echo $comanda_atual['data_fechamento_formatada']; ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Usuário Responsável:</label>
                        <div><?php echo $comanda_atual['usuario_abertura_nome']; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Valor Total:</label>
                        <div class="fw-bold fs-4"><?php echo formatarDinheiro($comanda_atual['valor_total']); ?></div>
                    </div>
                    
                    <?php if (!empty($comanda_atual['observacoes'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Observações:</label>
                        <div><?php echo nl2br($comanda_atual['observacoes']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($comanda_atual['status'] == 'aberta'): ?>
                    <div class="d-grid gap-2 mt-4">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarProduto">
                            <i class="fas fa-plus"></i> Adicionar Produto
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalFecharComanda">
                            <i class="fas fa-check"></i> Fechar Comanda
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCancelarComanda">
                            <i class="fas fa-times"></i> Cancelar Comanda
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($detalhes_cliente): ?>
            <!-- Informações do Cliente -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informações do Cliente</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($detalhes_cliente['cpf_cnpj'])): ?>
                    <div class="mb-2">
                        <label class="form-label">CPF/CNPJ:</label>
                        <div><?php echo $detalhes_cliente['cpf_cnpj']; ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($detalhes_cliente['telefone'])): ?>
                    <div class="mb-2">
                        <label class="form-label">Telefone:</label>
                        <div><?php echo $detalhes_cliente['telefone']; ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($detalhes_cliente['email'])): ?>
                    <div class="mb-2">
                        <label class="form-label">E-mail:</label>
                        <div><?php echo $detalhes_cliente['email']; ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <a href="clientes.php?id=<?php echo $detalhes_cliente['id']; ?>" class="btn btn-sm btn-outline-secondary mt-2">
                        <i class="fas fa-user"></i> Ver Detalhes Completos
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Produtos na Comanda</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($itens_comanda)): ?>
                    <div class="alert alert-info">
                        Nenhum produto adicionado à comanda.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Data</th>
                                    <th>Preço Unit.</th>
                                    <th>Qtd</th>
                                    <th>Subtotal</th>
                                    <th>Atendente</th>
                                    <?php if ($comanda_atual['status'] == 'aberta'): ?>
                                    <th>Ações</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itens_comanda as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $item['produto_nome']; ?></strong>
                                        <?php if (!empty($item['observacoes'])): ?>
                                        <br><small class="text-muted"><?php echo $item['observacoes']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $item['data_formatada']; ?></td>
                                    <td><?php echo formatarDinheiro($item['preco_unitario']); ?></td>
                                    <td><?php echo $item['quantidade']; ?></td>
                                    <td><?php echo formatarDinheiro($item['subtotal']); ?></td>
                                    <td><?php echo $item['usuario_nome']; ?></td>
                                    <?php if ($comanda_atual['status'] == 'aberta'): ?>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Tem certeza que deseja remover este produto?');">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="comanda_id" value="<?php echo $comanda_atual['id']; ?>">
                                            <button type="submit" name="remover_produto" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="<?php echo ($comanda_atual['status'] == 'aberta') ? '4' : '3'; ?>" class="text-end"><strong>Total:</strong></td>
                                    <td colspan="<?php echo ($comanda_atual['status'] == 'aberta') ? '3' : '3'; ?>"><strong><?php echo formatarDinheiro($comanda_atual['valor_total']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Adicionar Produto -->
    <div class="modal fade" id="modalAdicionarProduto" tabindex="-1" aria-labelledby="modalAdicionarProdutoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAdicionarProdutoLabel">Adicionar Produto à Comanda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="formAdicionarProduto">
                        <input type="hidden" name="comanda_id" value="<?php echo $comanda_atual['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="produto_id" class="form-label">Produto</label>
                            <select id="produto_id" name="produto_id" class="form-select" required>
    <option value="">Selecione um produto</option>
    <?php
    $produtos = $produto->listar();
    foreach ($produtos as $p) {
        if ($p['ativo'] && $p['estoque_atual'] > 0) {
            // Certifique-se de que o data-preco tem o valor correto
            echo '<option value="'.$p['id'].'" data-preco="'.$p['preco_venda'].'" data-estoque="'.$p['estoque_atual'].'">'.$p['codigo'].' - '.$p['nome'].' - Estoque: '.$p['estoque_atual'].'</option>';
        }
    }
    ?>
</select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="quantidade" class="form-label">Quantidade</label>
                                <input type="number" class="form-control" id="quantidade" name="quantidade" value="1" min="1" required>
                                <div class="form-text" id="estoque-disponivel"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="subtotal" class="form-label">Subtotal</label>
                                <input type="text" class="form-control" id="subtotal" readonly value="R$ 0,00">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formAdicionarProduto" name="adicionar_produto" class="btn btn-primary">Adicionar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Fechar Comanda -->
    <div class="modal fade" id="modalFecharComanda" tabindex="-1" aria-labelledby="modalFecharComandaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFecharComandaLabel">Fechar Comanda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="formFecharComanda">
                        <input type="hidden" name="comanda_id" value="<?php echo $comanda_atual['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="valor_total" class="form-label">Valor Total</label>
                            <input type="text" class="form-control" id="valor_total" readonly value="<?php echo formatarDinheiro($comanda_atual['valor_total']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="desconto" class="form-label">Desconto</label>
                            <input type="text" class="form-control" id="desconto" name="desconto" data-mask-money value="R$ 0,00">
                        </div>
                        
                        <div class="mb-3">
                            <label for="valor_final" class="form-label">Valor Final</label>
                            <input type="text" class="form-control" id="valor_final" readonly value="<?php echo formatarDinheiro($comanda_atual['valor_total']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                            <select id="forma_pagamento" name="forma_pagamento" class="form-select" required>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                                <option value="pix">PIX</option>
                                <option value="boleto">Boleto</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes_fechamento" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes_fechamento" name="observacoes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formFecharComanda" name="fechar_comanda" class="btn btn-success">Fechar Comanda</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Cancelar Comanda -->
    <div class="modal fade" id="modalCancelarComanda" tabindex="-1" aria-labelledby="modalCancelarComandaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCancelarComandaLabel">Cancelar Comanda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> Atenção: Esta ação não poderá ser desfeita!
                    </div>
                    <p>Tem certeza que deseja cancelar esta comanda? Todos os produtos serão estornados para o estoque.</p>
                    <form method="post" id="formCancelarComanda">
                        <input type="hidden" name="comanda_id" value="<?php echo $comanda_atual['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="observacoes_cancelamento" class="form-label">Motivo do Cancelamento</label>
                            <textarea class="form-control" id="observacoes_cancelamento" name="observacoes" rows="2" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não, Manter Comanda</button>
                    <button type="submit" form="formCancelarComanda" name="cancelar_comanda" class="btn btn-danger">Sim, Cancelar Comanda</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Listagem de Comandas -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Filtrar Comandas</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-5">
                            <label for="cliente" class="form-label">Cliente</label>
                            <select id="cliente" name="cliente" class="form-select">
                                <option value="">Todos os clientes</option>
                                <?php
                                $clientes = $cliente->listar();
                                foreach ($clientes as $c) {
                                    $selected = (isset($_GET['cliente']) && $_GET['cliente'] == $c['id']) ? 'selected' : '';
                                    echo '<option value="'.$c['id'].'" '.$selected.'>'.$c['nome'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Todos os status</option>
                                <option value="aberta" <?php echo (isset($_GET['status']) && $_GET['status'] == 'aberta') ? 'selected' : ''; ?>>Aberta</option>
                                <option value="fechada" <?php echo (isset($_GET['status']) && $_GET['status'] == 'fechada') ? 'selected' : ''; ?>>Fechada</option>
                                <option value="cancelada" <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Data Abertura</th>
                            <th>Data Fechamento</th>
                            <th>Valor Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista_comandas)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Nenhuma comanda encontrada</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($lista_comandas as $cmd): ?>
                        <tr>
                            <td><?php echo $cmd['id']; ?></td>
                            <td><?php echo $cmd['cliente_nome']; ?></td>
                            <td><?php echo $cmd['data_abertura_formatada']; ?></td>
                            <td><?php echo $cmd['data_fechamento_formatada'] ?: '-'; ?></td>
                            <td><?php echo formatarDinheiro($cmd['valor_total']); ?></td>
                            <td>
                                <?php
                                if ($cmd['status'] == 'aberta') {
                                    echo '<span class="badge bg-success">Aberta</span>';
                                } else if ($cmd['status'] == 'fechada') {
                                    echo '<span class="badge bg-secondary">Fechada</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Cancelada</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="comandas.php?id=<?php echo $cmd['id']; ?>" class="btn btn-sm btn-info me-1" title="Detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($cmd['status'] == 'aberta'): ?>
                                <a href="comandas.php?id=<?php echo $cmd['id']; ?>" class="btn btn-sm btn-primary me-1" title="Adicionar Produtos">
                                    <i class="fas fa-plus"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Nova Comanda -->
    <div class="modal fade" id="modalNovaComanda" tabindex="-1" aria-labelledby="modalNovaComandaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovaComandaLabel">Nova Comanda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="formNovaComanda">
                        <div class="mb-3">
                            <label for="cliente_id" class="form-label">Cliente</label>
                            <div class="input-group">
                                <select id="cliente_id" name="cliente_id" class="form-select" required>
                                    <option value="">Selecione um cliente</option>
                                    <?php
                                    foreach ($clientes as $c) {
                                        echo '<option value="'.$c['id'].'">'.$c['nome'].'</option>';
                                    }
                                    ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalClienteRapido">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes_comanda" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes_comanda" name="observacoes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formNovaComanda" name="abrir_comanda" class="btn btn-primary">Abrir Comanda</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Cadastro Rápido de Cliente -->
    <div class="modal fade" id="modalClienteRapido" tabindex="-1" aria-labelledby="modalClienteRapidoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalClienteRapidoLabel">Cadastro Rápido de Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formClienteRapido">
                        <div class="mb-3">
                            <label for="cliente-nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="cliente-nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="cliente-cpf-cnpj" class="form-label">CPF/CNPJ</label>
                            <input type="text" class="form-control" id="cliente-cpf-cnpj">
                        </div>
                        <div class="mb-3">
                            <label for="cliente-telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="cliente-telefone">
                        </div>
                        <div class="mb-3">
                            <label for="cliente-email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="cliente-email">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-salvar-cliente">Salvar Cliente</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
 /* document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    const table = document.querySelector('.datatable');
    if (table) {
        new DataTable(table, {
            language: {
                url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            }
        });
    }
*/

 

    // Máscaras para valores monetários
    const moneyInputs = document.querySelectorAll('[data-mask-money]');
    moneyInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove caracteres não numéricos, exceto vírgula
            value = value.replace(/[^\d,]/g, '');
            
            // Garante apenas uma vírgula
            const parts = value.split(',');
            if (parts.length > 2) {
                value = parts[0] + ',' + parts.slice(1).join('');
            }
            
            // Formata o número para ter sempre 2 casas decimais
            if (value.includes(',')) {
                const decimal = parts[1] || '';
                value = parts[0] + ',' + decimal.padEnd(2, '0').slice(0, 2);
            } else if (value) {
                value = value + ',00';
            }
            
            // Adiciona os separadores de milhar
            if (value) {
                const parts = value.split(',');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                value = parts.join(',');
            }
            
            e.target.value = 'R$ ' + value;
            
            // Se for o campo de desconto, atualiza o valor final
            if (e.target.id === 'desconto') {
                calcularValorFinal();
            }
        });
        
        // Já aplica a formatação ao carregar a página
        const event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
    });
    
// Atualizar subtotal ao selecionar produto ou alterar quantidade
const produtoSelect = document.getElementById('produto_id');
const quantidadeInput = document.getElementById('quantidade');
const subtotalInput = document.getElementById('subtotal');
const estoqueDisponivel = document.getElementById('estoque-disponivel');

if (produtoSelect && quantidadeInput && subtotalInput) {
    // Função para atualizar o subtotal
    function atualizarSubtotal() {
        const option = produtoSelect.options[produtoSelect.selectedIndex];
        if (option && option.value) {
            // Obter o preço do atributo data-preco
            const preco = parseFloat(option.dataset.preco || 0);
            const quantidade = parseInt(quantidadeInput.value) || 0;
            const estoque = parseInt(option.dataset.estoque) || 0;
            
            // Atualizar mensagem de estoque disponível
            if (estoqueDisponivel) {
                estoqueDisponivel.textContent = `Estoque disponível: ${estoque}`;
            }
            
            // Limitar quantidade ao estoque disponível
            if (quantidade > estoque) {
                quantidadeInput.value = estoque;
                quantidade = estoque;
            }
            
            // Calcular e exibir o subtotal formatado como moeda
            const subtotal = preco * quantidade;
            subtotalInput.value = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
            
            // Log para debug
            console.log('Preço:', preco, 'Quantidade:', quantidade, 'Subtotal:', subtotal);
        } else {
            subtotalInput.value = 'R$ 0,00';
            if (estoqueDisponivel) {
                estoqueDisponivel.textContent = '';
            }
        }
    }
    
    // Adicionar eventos para atualizar o subtotal
    produtoSelect.addEventListener('change', atualizarSubtotal);
    quantidadeInput.addEventListener('input', atualizarSubtotal);
    
    // Chamar a função inicialmente para calcular subtotal com valores padrão
    atualizarSubtotal();
}
    // Calcular valor final (com desconto)
    function calcularValorFinal() {
        const valorTotalInput = document.getElementById('valor_total');
        const descontoInput = document.getElementById('desconto');
        const valorFinalInput = document.getElementById('valor_final');
        
        if (valorTotalInput && descontoInput && valorFinalInput) {
            // Extrair valores numéricos
            const valorTotal = parseFloat(valorTotalInput.value.replace('R$', '').replace('.', '').replace(',', '.')) || 0;
const desconto = parseFloat(descontoInput.value.replace('R$', '').replace('.', '').replace(',', '.')) || 0;
            
            // Calcular valor final
            let valorFinal = valorTotal - desconto;
            if (valorFinal < 0) valorFinal = 0;
            
            valorFinalInput.value = `R$ ${valorFinal.toFixed(2).replace('.', ',')}`;
        }
    }
    
    // Cadastro rápido de cliente
    const btnSalvarCliente = document.getElementById('btn-salvar-cliente');
    if (btnSalvarCliente) {
        btnSalvarCliente.addEventListener('click', function() {
            const nome = document.getElementById('cliente-nome').value.trim();
            const cpfCnpj = document.getElementById('cliente-cpf-cnpj').value.trim();
            const telefone = document.getElementById('cliente-telefone').value.trim();
            const email = document.getElementById('cliente-email').value.trim();
            
            if (!nome) {
                alert('O nome do cliente é obrigatório!');
                return;
            }
            
            // Enviar dados via AJAX
            fetch('ajax_pdv.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'acao': 'salvar_cliente',
                    'nome': nome,
                    'cpf_cnpj': cpfCnpj,
                    'telefone': telefone,
                    'email': email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Adiciona cliente ao select e seleciona
                    const clienteSelect = document.getElementById('cliente_id');
                    const novaOption = new Option(data.cliente.nome, data.cliente.id);
                    clienteSelect.add(novaOption);
                    clienteSelect.value = data.cliente.id;
                    
                    // Fecha o modal
                    const modalClienteRapido = bootstrap.Modal.getInstance(document.getElementById('modalClienteRapido'));
                    modalClienteRapido.hide();
                    
                    // Limpa formulário
                    document.getElementById('cliente-nome').value = '';
                    document.getElementById('cliente-cpf-cnpj').value = '';
                    document.getElementById('cliente-telefone').value = '';
                    document.getElementById('cliente-email').value = '';
                    
                    alert('Cliente adicionado com sucesso!');
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar cliente.');
            });
        });
    }

</script>

<?php include 'footer.php'; ?>