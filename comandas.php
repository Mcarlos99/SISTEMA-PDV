<?php
/**
 * EXTREME PDV - Gerenciamento de Comandas
 * 
 * Este arquivo gerencia a criação, visualização e manipulação de comandas no sistema
 */

require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Processar ações
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$comanda_obj = new Comanda($pdo);

// Processar abertura de comanda
if ($acao == 'abrir' && isset($_POST['cliente_id'])) {
    try {
        $cliente_id = intval($_POST['cliente_id']);
        $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
        
        if ($cliente_id <= 0) {
            throw new Exception("Selecione um cliente válido");
        }
        
        $comanda_id = $comanda_obj->abrir($cliente_id, $observacoes);
        
        alerta('Comanda aberta com sucesso!', 'success');
        header('Location: comandas.php?id=' . $comanda_id);
        exit;
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar adição de produto à comanda
if ($acao == 'adicionar_produto' && isset($_POST['comanda_id'], $_POST['produto_id'], $_POST['quantidade'])) {
    try {
        $comanda_id = intval($_POST['comanda_id']);
        $produto_id = intval($_POST['produto_id']);
        $quantidade = intval($_POST['quantidade']);
        $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
        
        if ($quantidade <= 0) {
            throw new Exception("Quantidade deve ser maior que zero");
        }
        
        $item_id = $comanda_obj->adicionarProduto($comanda_id, $produto_id, $quantidade, $observacoes);
        
        alerta('Produto adicionado com sucesso!', 'success');
        header('Location: comandas.php?id=' . $comanda_id);
        exit;
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar remoção de produto da comanda
if ($acao == 'remover_produto' && isset($_GET['item_id'], $_GET['comanda_id'])) {
    try {
        $item_id = intval($_GET['item_id']);
        $comanda_id = intval($_GET['comanda_id']);
        
        $comanda_obj->removerProduto($item_id);
        
        alerta('Produto removido com sucesso!', 'success');
        header('Location: comandas.php?id=' . $comanda_id);
        exit;
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar fechamento de comanda
if ($acao == 'fechar' && isset($_POST['comanda_id'], $_POST['forma_pagamento'])) {
    try {
        $comanda_id = intval($_POST['comanda_id']);
        $forma_pagamento = $_POST['forma_pagamento'];
        $desconto = isset($_POST['desconto']) ? floatval(str_replace(',', '.', $_POST['desconto'])) : 0;
        $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
        
        // Verificar se é pagamento parcial
        $pagamento_parcial = isset($_POST['pagamento_parcial']) && $_POST['pagamento_parcial'] == 'on';
        $valor_parcial = 0;
        
        if ($pagamento_parcial && isset($_POST['valor_parcial'])) {
            $valor_parcial = floatval(str_replace(',', '.', $_POST['valor_parcial']));
            
            // Validar valor parcial
            $comanda_info = $comanda_obj->buscarPorId($comanda_id);
            $valor_total_comanda = $comanda_info['valor_total'] - $desconto;
            
            if ($valor_parcial <= 0) {
                throw new Exception("O valor do pagamento parcial deve ser maior que zero");
            }
            
            if ($valor_parcial >= $valor_total_comanda) {
                // Se o valor parcial for igual ou maior que o total, consideramos pagamento completo
                $pagamento_parcial = false;
            } elseif ($valor_parcial < ($valor_total_comanda * 0.1)) {
                // Exemplo: exigir pelo menos 10% do valor total
                throw new Exception("O pagamento parcial deve ser de pelo menos 10% do valor total");
            }
        }
        
        if ($pagamento_parcial) {
            // Para pagamento parcial, usamos uma função específica
            $resultado = $comanda_obj->fecharParcial($comanda_id, $forma_pagamento, $desconto, $valor_parcial, $observacoes);
            
            alerta('Pagamento parcial realizado com sucesso! A comanda continua aberta para pagamentos futuros.', 'success');
            header('Location: comandas.php?id=' . $comanda_id);
            exit;
        } else {
            // Para pagamento completo, usamos a função existente
            $resultado = $comanda_obj->fechar($comanda_id, $forma_pagamento, $desconto, $observacoes);
            
            alerta('Comanda fechada e venda registrada com sucesso!', 'success');
            header('Location: vendas.php?id=' . $resultado['venda_id']);
            exit;
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar cancelamento de comanda
if ($acao == 'cancelar' && isset($_GET['id'])) {
    try {
        $comanda_id = intval($_GET['id']);
        
        $comanda_obj->cancelar($comanda_id);
        
        alerta('Comanda cancelada com sucesso!', 'success');
        header('Location: comandas.php');
        exit;
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Verificar se está visualizando uma comanda específica
$comanda = null;
$produtos_comanda = [];
if (isset($_GET['id'])) {
    $comanda_id = intval($_GET['id']);
    $comanda = $comanda_obj->buscarPorId($comanda_id);
    
    if ($comanda) {
        $produtos_comanda = $comanda_obj->listarProdutos($comanda_id);
    }
}

// Template da página
$titulo_pagina = 'Controle de Comandas - EXTREME PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-clipboard-list me-2 text-primary"></i>
                <?php echo $comanda ? 'Detalhes da Comanda #' . $comanda['id'] : 'Controle de Comandas'; ?>
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <?php if ($comanda): ?>
                        <li class="breadcrumb-item"><a href="comandas.php">Comandas</a></li>
                        <li class="breadcrumb-item active">Comanda #<?php echo $comanda['id']; ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active">Comandas</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
        
        <div>
            <?php if (!$comanda): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaComanda">
                    <i class="fas fa-plus-circle me-1"></i>
                    Nova Comanda
                </button>
            <?php elseif ($comanda['status'] == 'aberta'): ?>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-success mb-2 mb-sm-0" data-bs-toggle="modal" data-bs-target="#modalAdicionarProduto">
                        <i class="fas fa-cart-plus me-1"></i>
                        <span class="d-none d-sm-inline">Adicionar Produto</span>
                        <span class="d-inline d-sm-none">Produto</span>
                    </button>
                    <button type="button" class="btn btn-info text-white mb-2 mb-sm-0" data-bs-toggle="modal" data-bs-target="#modalFecharComanda">
                        <i class="fas fa-check-circle me-1"></i>
                        <span class="d-none d-sm-inline">Fechar Comanda</span>
                        <span class="d-inline d-sm-none">Fechar</span>
                    </button>
                           <!-- ADICIONAR O BOTÃO DE IMPRESSÃO AQUI -->
                    <button type="button" class="btn btn-secondary mb-2 mb-sm-0" id="btnImprimirComanda">
                        <i class="fas fa-print me-1"></i>
                        <span class="d-none d-sm-inline">Imprimir Comanda</span>
                        <span class="d-inline d-sm-none">Imprimir</span>
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCancelarComanda">
                        <i class="fas fa-times-circle me-1"></i>
                        <span class="d-none d-sm-inline">Cancelar Comanda</span>
                        <span class="d-inline d-sm-none">Cancelar</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($comanda): ?>
        <!-- Detalhes da Comanda -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informações da Comanda
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Cliente</h6>
                                        <h5 class="mb-1">
                                            <i class="fas fa-user text-primary me-1"></i>
                                            <?php echo esc($comanda['cliente_nome']); ?>
                                        </h5>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            Aberta em: <?php echo $comanda['data_abertura_formatada']; ?>
                                        </p>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-user-circle me-1"></i>
                                            Por: <?php echo esc($comanda['usuario_abertura_nome']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Status</h6>
                                        <?php if ($comanda['status'] == 'aberta'): ?>
                                            <h5 class="text-success mb-0">
                                                <i class="fas fa-clipboard-check me-1"></i>
                                                Comanda Aberta
                                            </h5>
                                            <p class="text-muted mb-0">Adicione produtos conforme necessário</p>
                                        <?php elseif ($comanda['status'] == 'fechada'): ?>
                                            <h5 class="text-secondary mb-0">
                                                <i class="fas fa-clipboard me-1"></i>
                                                Comanda Fechada
                                            </h5>
                                            <p class="text-muted mb-0">
                                                Fechada em: <?php echo $comanda['data_fechamento_formatada']; ?>
                                            </p>
                                        <?php else: ?>
                                            <h5 class="text-danger mb-0">
                                                <i class="fas fa-ban me-1"></i>
                                                Comanda Cancelada
                                            </h5>
                                            <p class="text-muted mb-0">
                                                Cancelada em: <?php echo $comanda['data_fechamento_formatada']; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card h-100 bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Informações Financeiras</h6>
                                        <?php
                                        // Verificar se há pagamentos parciais
                                        $total_pago = 0;
                                        $pagamentos = [];
                                            
                                        // Verificar se a classe Comanda tem os métodos para pagamentos parciais
                                        if (method_exists($comanda_obj, 'calcularTotalPago')) {
                                            $total_pago = $comanda_obj->calcularTotalPago($comanda['id']);
                                            $pagamentos = $comanda_obj->listarPagamentosParciais($comanda['id']);
                                        } else {
                                            // Tentar buscar diretamente do banco de dados
                                            try {
                                                $stmt = $pdo->prepare("
                                                    SELECT COALESCE(SUM(valor_pago), 0) AS total_pago 
                                                    FROM pagamentos_comanda 
                                                    WHERE comanda_id = :comanda_id
                                                ");
                                                
                                                $stmt->bindParam(':comanda_id', $comanda['id'], PDO::PARAM_INT);
                                                $stmt->execute();
                                                
                                                $resultado = $stmt->fetch();
                                                if ($resultado) {
                                                    $total_pago = floatval($resultado['total_pago']);
                                                }
                                            } catch (Exception $e) {
                                                // Provavelmente a tabela não existe ainda, mantém $total_pago como 0
                                            }
                                        }
                                            
                                        $valor_total = $comanda['valor_total'];
                                        $valor_restante = $valor_total - $total_pago;
                                        ?>
                                            
                                        <!-- Valor total da comanda -->
                                        <h3 class="text-primary mb-0">
                                            <?php echo formatarDinheiro($valor_total); ?>
                                        </h3>
                                        <p class="text-muted mb-0 font-size-sm">Valor total da comanda</p>
                                            
                                        <?php if ($total_pago > 0): ?>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <span>Valor já pago:</span>
                                                <span class="text-success"><?php echo formatarDinheiro($total_pago); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span><strong>Valor restante:</strong></span>
                                                <span class="text-primary"><strong><?php echo formatarDinheiro($valor_restante); ?></strong></span>
                                            </div>
                                            
                                            <div class="alert alert-info mt-2 mb-0">
                                                <small>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Esta comanda possui pagamentos parciais registrados.
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                            
                                        <?php if (!empty($comanda['observacoes'])): ?>
                                            <hr>
                                            <p class="text-muted mt-2 mb-0">
                                                <i class="fas fa-comment-alt me-1"></i>
                                                <?php echo esc($comanda['observacoes']); ?>
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
        
        <!-- Produtos da Comanda -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Produtos na Comanda
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0" id="tabelaProdutosComanda">
                        <thead>
                            <tr>
                                <th data-priority="1">Código</th>
                                <th data-priority="1">Produto</th>
                                <th data-priority="1">Qtd</th>
                                <th data-priority="2">Preço Un.</th>
                                <th data-priority="2">Subtotal</th>
                                <th data-priority="2">Data Adição</th>
                                <th data-priority="3">Usuário</th>
                                <th data-priority="3">Observações</th>
                                <?php if ($comanda['status'] == 'aberta'): ?>
                                <th data-priority="1" width="80">Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($produtos_comanda)): ?>
                                <?php foreach ($produtos_comanda as $item): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo esc($item['produto_codigo']); ?></span></td>
                                        <td><?php echo esc($item['produto_nome']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $item['quantidade']; ?></span></td>
                                        <td><?php echo formatarDinheiro($item['preco_unitario']); ?></td>
                                        <td><strong><?php echo formatarDinheiro($item['subtotal']); ?></strong></td>
                                        <td><?php echo $item['data_formatada']; ?></td>
                                        <td><?php echo esc($item['usuario_nome']); ?></td>
                                        <td>
                                            <?php if (!empty($item['observacoes'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                        data-bs-toggle="tooltip" data-bs-html="true"
                                                        title="<?php echo esc($item['observacoes']); ?>">
                                                    <i class="fas fa-comment-alt"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($comanda['status'] == 'aberta'): ?>
                                        <td>
                                            <a href="comandas.php?acao=remover_produto&item_id=<?php echo $item['id']; ?>&comanda_id=<?php echo $comanda['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Tem certeza que deseja remover este produto?')"
                                               style="display: inline-block !important; background-color: #dc3545 !important;">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $comanda['status'] == 'aberta' ? 9 : 8; ?>" class="text-center py-4">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhum produto adicionado a esta comanda.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- PARTE 2 -->
        <!-- Histórico de Pagamentos Parciais, se existirem -->
        <?php
        // Verificar se a comanda está aberta e se há pagamentos parciais
        if ($comanda && $comanda['status'] == 'aberta'):
            // Verificar se a classe Comanda tem método para listar pagamentos
            if (method_exists($comanda_obj, 'listarPagamentosParciais')):
                $pagamentos = $comanda_obj->listarPagamentosParciais($comanda['id']);
            else:
                // Buscar pagamentos diretamente do banco
                try {
                    $stmt = $pdo->prepare("
                        SELECT 
                            pc.*, 
                            DATE_FORMAT(pc.data_pagamento, '%d/%m/%Y %H:%i') AS data_formatada,
                            u.nome AS usuario_nome
                        FROM pagamentos_comanda pc
                        LEFT JOIN usuarios u ON pc.usuario_id = u.id
                        WHERE pc.comanda_id = :comanda_id
                        ORDER BY pc.data_pagamento DESC
                    ");
                    $stmt->bindParam(':comanda_id', $comanda['id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $pagamentos = $stmt->fetchAll();
                } catch (Exception $e) {
                    // A tabela pode não existir ainda
                    $pagamentos = [];
                }
            endif;
            
            // Se existirem pagamentos parciais, exibir card
            if (!empty($pagamentos)):
                // Calcular total já pago (já calculado anteriormente)
                // Valor restante também já foi calculado
        ?>
        <!-- Card de Histórico de Pagamentos -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Histórico de Pagamentos Parciais
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0">Total da Comanda: <?php echo formatarDinheiro($comanda['valor_total']); ?></h5>
                        </div>
                        <div class="col-md-4">
                            <h5 class="mb-0">Total Pago: <?php echo formatarDinheiro($total_pago); ?></h5>
                        </div>
                        <div class="col-md-4">
                            <h5 class="mb-0">Valor Restante: <?php echo formatarDinheiro($valor_restante); ?></h5>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Valor Pago</th>
                                <th>Forma de Pagamento</th>
                                <th>Operador</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagamentos as $pag): ?>
                            <tr>
                                <td><?php echo $pag['data_formatada']; ?></td>
                                <td><strong><?php echo formatarDinheiro($pag['valor_pago']); ?></strong></td>
                                <td>
                                    <?php
                                    $icones = [
                                        'dinheiro' => '<i class="fas fa-money-bill-wave text-success me-1"></i> Dinheiro',
                                        'cartao_credito' => '<i class="fas fa-credit-card text-primary me-1"></i> Cartão de Crédito',
                                        'cartao_debito' => '<i class="fas fa-credit-card text-info me-1"></i> Cartão de Débito',
                                        'pix' => '<i class="fas fa-qrcode text-warning me-1"></i> PIX',
                                        'boleto' => '<i class="fas fa-file-invoice-dollar text-secondary me-1"></i> Boleto',
                                        'transferencia' => '<i class="fas fa-exchange-alt text-primary me-1"></i> Transferência',
                                        'cheque' => '<i class="fas fa-money-check text-info me-1"></i> Cheque'
                                    ];
                                    echo $icones[$pag['forma_pagamento']] ?? ucfirst(str_replace('_', ' ', $pag['forma_pagamento']));
                                    ?>
                                </td>
                                <td><?php echo esc($pag['usuario_nome']); ?></td>
                                <td><?php echo esc($pag['observacoes']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php 
            endif; // fim do if pagamentos
        endif; // fim do if comanda aberta
        ?>
    <?php else: ?>
        <!-- Lista de Comandas -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Lista de Comandas
                        </h5>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" id="buscarComanda" placeholder="Buscar comanda...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0" id="tabelaComandas">
                        <thead>
                            <tr>
                                <th data-priority="1">ID</th>
                                <th data-priority="1">Cliente</th>
                                <th data-priority="1">Status</th>
                                <th data-priority="2">Abertura</th>
                                <th data-priority="2">Fechamento</th>
                                <th data-priority="1">Valor</th>
                                <th data-priority="3">Responsável</th>
                                <th data-priority="1" width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $comandas = $comanda_obj->listar();
                            if (empty($comandas)): 
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhuma comanda encontrada.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($comandas as $c): ?>
                                <?php if (!is_array($c)) continue; ?>
                                 
                                    <tr>
                                        <td><?php echo $c['id']; ?></td>
                                        <td><?php echo esc($c['cliente_nome'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($c['status'] == 'aberta'): ?>
                                                <span class="badge bg-success">Aberta</span>
                                            <?php elseif ($c['status'] == 'fechada'): ?>
                                                <span class="badge bg-secondary">Fechada</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Cancelada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $c['data_abertura_formatada']; ?></td>
                                        <td>
                                            <?php if ($c['data_fechamento']): ?>
                                                <?php echo $c['data_fechamento_formatada']; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo formatarDinheiro($c['valor_total']); ?></strong>
                                            
                                            <?php
                                            // Verificar se tem pagamentos parciais
                                            $tem_parcial = false;
                                            $total_pago = 0;
                                            
                                            try {
                                                $stmt = $pdo->prepare("
                                                    SELECT COALESCE(SUM(valor_pago), 0) AS total_pago 
                                                    FROM pagamentos_comanda 
                                                    WHERE comanda_id = :comanda_id
                                                ");
                                                $stmt->bindParam(':comanda_id', $c['id'], PDO::PARAM_INT);
                                                $stmt->execute();
                                                $resultado = $stmt->fetch();
                                                
                                                if ($resultado && floatval($resultado['total_pago']) > 0) {
                                                    $tem_parcial = true;
                                                    $total_pago = floatval($resultado['total_pago']);
                                                }
                                            } catch (Exception $e) {
                                                // Tabela pode não existir ainda
                                            }
                                            
                                            if ($tem_parcial && $c['status'] == 'aberta'):
                                                $valor_restante = $c['valor_total'] - $total_pago;
                                            ?>
                                                <div>
                                                    <small class="text-success">Pago: <?php echo formatarDinheiro($total_pago); ?></small>
                                                </div>
                                                <div>
                                                    <small class="text-primary">Restante: <?php echo formatarDinheiro($valor_restante); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc($c['usuario_abertura_nome'] ?? '-'); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="comandas.php?id=<?php echo $c['id']; ?>" 
                                                   class="btn btn-primary" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Detalhes"
                                                   style="display: inline-block !important; background-color: #0d6efd !important;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($c['status'] == 'aberta'): ?>
                                                    <a href="comandas.php?id=<?php echo $c['id']; ?>#modalFecharComanda" 
                                                       class="btn btn-success" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Fechar"
                                                       style="display: inline-block !important; background-color: #198754 !important;">
                                                        <i class="fas fa-check-circle"></i>
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
</div>
<!-- Modal Nova Comanda -->
<div class="modal fade" id="modalNovaComanda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Nova Comanda
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="comandas.php?acao=abrir" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label fw-bold">Cliente:</label>
                        <select class="form-select form-select-lg" id="cliente_id" name="cliente_id" required>
                            <option value="">Selecione um cliente</option>
                            <?php
                            $clientes = (new Cliente($pdo))->listar();
                            foreach ($clientes as $cliente) {
                                echo '<option value="'.$cliente['id'].'">'.esc($cliente['nome']).'</option>';
                            }
                            ?>
                        </select>
                        <div class="d-flex justify-content-end mt-2">
                            <a href="clientes.php?acao=novo" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-user-plus me-1"></i>
                                Novo Cliente
                            </a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações (opcional):</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>
                        Abrir Comanda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($comanda && $comanda['status'] == 'aberta'): ?>
    <!-- PARTE 3 -->
<!-- Modal Adicionar Produto -->
<div class="modal fade" id="modalAdicionarProduto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-cart-plus me-2"></i>
                    Adicionar Produto à Comanda
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="comandas.php?acao=adicionar_produto" method="post">
                <input type="hidden" name="comanda_id" value="<?php echo $comanda['id']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="produto_busca" class="form-label">Buscar Produto:</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" id="produto_busca" placeholder="Nome ou código do produto">
                            <button class="btn btn-outline-secondary" type="button" id="btnBuscarProduto">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="produto_id" class="form-label fw-bold">Produto:</label>
                        <select class="form-select" id="produto_id" name="produto_id" required>
                            <option value="">Selecione um produto</option>
                            <?php
                            $produtos = (new Produto($pdo))->listar();
                            foreach ($produtos as $p) {
                                if ($p['ativo'] && $p['estoque_atual'] > 0) {
                                    echo '<option value="'.$p['id'].'" data-preco="'.$p['preco_venda'].'" data-estoque="'.$p['estoque_atual'].'">'.esc($p['nome']).' - '.formatarDinheiro($p['preco_venda']).'</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <label for="quantidade" class="form-label">Quantidade:</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" id="diminuirQtd">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control text-center" id="quantidade" name="quantidade" value="1" min="1" required>
                                <button type="button" class="btn btn-outline-secondary" id="aumentarQtd">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small id="estoque-info" class="form-text text-muted mt-1">Estoque disponível: <span id="estoque-disponivel">-</span></small>
                        </div>
                        <div class="col-sm-6">
                            <label for="preco_exibicao" class="form-label">Preço unitário:</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="preco_exibicao" disabled>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações (opcional):</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="2" placeholder="Ex: Sem gelo, bem passado, etc."></textarea>
                    </div>
                    
                    <div class="alert alert-info mb-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-calculator fa-2x me-2"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <p class="mb-0">Subtotal: <strong id="subtotal-produto">R$ 0,00</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-cart-plus me-1"></i>
                        Adicionar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Fechar Comanda -->
<div class="modal fade" id="modalFecharComanda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Fechar Comanda
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="comandas.php?acao=fechar" method="post">
                <input type="hidden" name="comanda_id" value="<?php echo $comanda['id']; ?>">
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="mb-2">Resumo da Comanda</h6>
                        <p class="mb-1">Cliente: <strong><?php echo esc($comanda['cliente_nome']); ?></strong></p>
                        <p class="mb-1">Total de itens: <strong><?php echo count($produtos_comanda); ?></strong></p>
                        <p class="mb-1 fw-bold">Valor Total: <span class="text-primary"><?php echo formatarDinheiro($comanda['valor_total']); ?></span></p>
                    </div>
                    
                    <?php
                    // Verificar se há pagamentos parciais
                    $total_pago = 0;
                    $valor_restante = $comanda['valor_total'];

                    // Verificar se a classe Comanda tem os métodos para pagamentos parciais
                    if (method_exists($comanda_obj, 'calcularTotalPago')) {
                        $total_pago = $comanda_obj->calcularTotalPago($comanda['id']);
                        $valor_restante = $comanda['valor_total'] - $total_pago;
                    } else {
                        // Tentar buscar diretamente do banco de dados
                        try {
                            $stmt = $pdo->prepare("
                                SELECT COALESCE(SUM(valor_pago), 0) AS total_pago 
                                FROM pagamentos_comanda 
                                WHERE comanda_id = :comanda_id
                            ");
                            
                            $stmt->bindParam(':comanda_id', $comanda['id'], PDO::PARAM_INT);
                            $stmt->execute();
                            
                            $resultado = $stmt->fetch();
                            if ($resultado) {
                                $total_pago = floatval($resultado['total_pago']);
                                $valor_restante = $comanda['valor_total'] - $total_pago;
                            }
                        } catch (Exception $e) {
                            // Provavelmente a tabela não existe ainda, mantém os valores padrão
                        }
                    }

                    // Se houver pagamentos parciais, exibir informação
                    if ($total_pago > 0):
                    ?>
                    <div class="alert alert-info mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-info-circle me-2"></i>
                                Esta comanda já possui pagamentos parciais:
                            </div>
                            <div class="text-end">
                                <div>Total pago: <strong><?php echo formatarDinheiro($total_pago); ?></strong></div>
                                <div>Valor restante: <strong><?php echo formatarDinheiro($valor_restante); ?></strong></div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="total_pago" value="<?php echo $total_pago; ?>">
                    <input type="hidden" name="valor_restante" value="<?php echo $valor_restante; ?>">
                    <?php endif; ?>
                    
                    <!-- Opção de Pagamento Parcial -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="pagamentoParcial" name="pagamento_parcial">
                            <label class="form-check-label" for="pagamentoParcial">Pagamento Parcial</label>
                        </div>
                        <div id="divValorParcial" class="mt-2 d-none">
                            <label for="valorParcial" class="form-label">Valor a ser pago agora (R$):</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="valorParcial" name="valor_parcial" step="0.01" min="0" 
                                       placeholder="0,00" max="<?php echo $valor_restante; ?>">
                            </div>
                            <div class="form-text text-info">O restante ficará como pendente para pagamento futuro.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="desconto" class="form-label">Desconto (R$):</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="desconto" name="desconto" step="0.01" min="0" 
                                   max="<?php echo $valor_restante; ?>" value="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Forma de Pagamento:</label>
                        <div class="payment-options d-flex flex-wrap gap-2 mb-3">
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="forma_pagamento" id="pagamentoDinheiro" value="dinheiro" checked>
                                <label class="form-check-label px-2 py-2 rounded border d-flex align-items-center" for="pagamentoDinheiro">
                                    <i class="fas fa-money-bill-wave text-success me-2"></i>
                                    <span>Dinheiro</span>
                                </label>
                            </div>
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="forma_pagamento" id="pagamentoCartaoCredito" value="cartao_credito">
                                <label class="form-check-label px-2 py-2 rounded border d-flex align-items-center" for="pagamentoCartaoCredito">
                                    <i class="fas fa-credit-card text-primary me-2"></i>
                                    <span>Crédito</span>
                                </label>
                            </div>
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="forma_pagamento" id="pagamentoCartaoDebito" value="cartao_debito">
                                <label class="form-check-label px-2 py-2 rounded border d-flex align-items-center" for="pagamentoCartaoDebito">
                                    <i class="fas fa-credit-card text-info me-2"></i>
                                    <span>Débito</span>
                                </label>
                            </div>
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="forma_pagamento" id="pagamentoPix" value="pix">
                                <label class="form-check-label px-2 py-2 rounded border d-flex align-items-center" for="pagamentoPix">
                                    <i class="fas fa-qrcode text-warning me-2"></i>
                                    <span>PIX</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações (opcional):</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-receipt fa-2x me-2"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <p class="mb-0">Total a pagar: <strong id="total-comanda"><?php echo formatarDinheiro($valor_restante); ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-check-circle me-1"></i>
                        <span id="btnTextFechar">Fechar e Gerar Venda</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cancelar Comanda -->
<div class="modal fade" id="modalCancelarComanda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle me-2"></i>
                    Cancelar Comanda
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Atenção! Esta ação não poderá ser desfeita.
                </div>
                
                <p>Você está prestes a cancelar a comanda <strong>#<?php echo $comanda['id']; ?></strong> do cliente <strong><?php echo esc($comanda['cliente_nome']); ?></strong>.</p>
                
                <p>Ao cancelar a comanda:</p>
                <ul>
                    <li>Todos os produtos serão devolvidos ao estoque</li>
                    <li>A comanda será marcada como "Cancelada"</li>
                    <li>Não será gerada nenhuma venda</li>
                </ul>
                
                <p>Tem certeza que deseja continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Não, Voltar
                </button>
                <a href="comandas.php?acao=cancelar&id=<?php echo $comanda['id']; ?>" class="btn btn-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Sim, Cancelar Comanda
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
    
    /* Estilo para opções de pagamento */
    .payment-option {
        margin-right: 0;
    }
    
    .payment-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }
    
    .payment-option label {
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .payment-option input[type="radio"]:checked + label {
        border-color: #0d6efd !important;
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    /* Melhor visualização em dispositivos pequenos */
    @media (max-width: 576px) {
        .payment-options {
            flex-direction: column;
            gap: 0.5rem !important;
        }
        
        .payment-option label {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializa DataTables com responsividade para tabela de comandas
        $('#tabelaComandas').DataTable({
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
            "order": [[0, 'desc']], // Ordenar por ID decrescente (mais recente primeiro)
            "autoWidth": false,
            "columnDefs": [
                { responsivePriority: 1, targets: [0, 1, 2, 5, 7] }, // Prioridade alta 
                { responsivePriority: 2, targets: [3, 4] },         // Prioridade média
                { responsivePriority: 3, targets: 6 }               // Prioridade baixa
            ]
        });
        
        // Inicializa DataTables com responsividade para tabela de produtos da comanda
        $('#tabelaProdutosComanda').DataTable({
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
            "order": [[5, 'desc']], // Ordenar por data de adição (mais recente primeiro)
            "autoWidth": false
        });
        
        // Filtro de busca rápida para tabela de comandas
        $('#buscarComanda').on('keyup', function() {
            $('#tabelaComandas').DataTable().search($(this).val()).draw();
        });
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Busca de produtos por nome/código
        $('#btnBuscarProduto').click(function() {
            var termo = $('#produto_busca').val().toLowerCase().trim();
            if (termo) {
                $('#produto_id option').each(function() {
                    var texto = $(this).text().toLowerCase();
                    if (texto.indexOf(termo) > -1) {
                        $('#produto_id').val($(this).val()).trigger('change');
                        return false; // Interrompe o loop quando encontrar o primeiro
                    }
                });
            }
        });
        
        // Mostrar informações do produto selecionado
        $('#produto_id').change(function() {
            var option = $(this).find('option:selected');
            var preco = option.data('preco') || 0;
            var estoque = option.data('estoque') || 0;
            
            // Mostra o preço e o estoque disponível
            $('#preco_exibicao').val(formatarDinheiro(preco).replace('R$ ', ''));
            $('#estoque-disponivel').text(estoque);
            
            // Define o máximo para a quantidade
            $('#quantidade').attr('max', estoque);
            
            // Calcula o subtotal
            calcularSubtotalProduto();
        });

        // Evento para controles de quantidade
        $('#diminuirQtd').click(function() {
            var qtd = parseInt($('#quantidade').val());
            if (qtd > 1) {
                $('#quantidade').val(qtd - 1);
                calcularSubtotalProduto();
            }
        });

        $('#aumentarQtd').click(function() {
            var qtd = parseInt($('#quantidade').val());
            var max = parseInt($('#quantidade').attr('max') || 9999);
            if (qtd < max) {
                $('#quantidade').val(qtd + 1);
                calcularSubtotalProduto();
            }
        });

        $('#quantidade').on('input change keyup', function() {
            calcularSubtotalProduto();
        });

        // Controlar pagamento parcial
        const checkboxParcial = document.getElementById('pagamentoParcial');
        const divValorParcial = document.getElementById('divValorParcial');
        const inputValorParcial = document.getElementById('valorParcial');
        const inputDesconto = document.getElementById('desconto');
        const btnTextFechar = document.getElementById('btnTextFechar');
        const valorTotalComanda = <?php echo isset($comanda['valor_total']) ? $comanda['valor_total'] : 0; ?>;
        const totalPago = <?php echo $total_pago ?? 0; ?>;
        const valorRestante = valorTotalComanda - totalPago;
        
        if (checkboxParcial) {
            checkboxParcial.addEventListener('change', function() {
                if (this.checked) {
                    divValorParcial.classList.remove('d-none');
                    inputValorParcial.setAttribute('required', 'required');
                    inputValorParcial.setAttribute('max', valorRestante - parseFloat(inputDesconto.value || 0));
                    btnTextFechar.textContent = 'Registrar Pagamento Parcial';
                } else {
                    divValorParcial.classList.add('d-none');
                    inputValorParcial.removeAttribute('required');
                    btnTextFechar.textContent = 'Fechar e Gerar Venda';
                }
                atualizarTotalPagar();
            });
        }
        
        // Atualizar quando o valor parcial ou desconto mudar
        if (inputValorParcial) {
            inputValorParcial.addEventListener('input', atualizarTotalPagar);
        }
        
        if (inputDesconto) {
            inputDesconto.addEventListener('input', function() {
                if (checkboxParcial && checkboxParcial.checked) {
                    const valorMaximo = valorRestante - parseFloat(this.value || 0);
                    inputValorParcial.setAttribute('max', valorMaximo);
                    if (parseFloat(inputValorParcial.value || 0) > valorMaximo) {
                        inputValorParcial.value = valorMaximo;
                    }
                }
                atualizarTotalPagar();
            });
        }
        
        // Cálculo do subtotal do produto
        function calcularSubtotalProduto() {
            var option = $('#produto_id').find('option:selected');
            var preco = parseFloat(option.data('preco')) || 0;
            var quantidade = parseInt($('#quantidade').val()) || 1; // Use 1 como valor mínimo
            var subtotal = preco * quantidade;
            
            // Atualiza o texto e garante que ele permaneça visível
            $('#subtotal-produto').text(formatarDinheiro(subtotal));
            
            // Garante que o elemento permaneça visível
            $('#subtotal-produto').closest('.alert').show();
        }
        
        // Para garantir que o subtotal seja calculado quando o modal é aberto
        $('#modalAdicionarProduto').on('shown.bs.modal', function() {
            // Força o cálculo inicial se um produto já estiver selecionado
            if ($('#produto_id').val()) {
                calcularSubtotalProduto();
            }
        });
        
        // Evite que o subtotal seja escondido por outras interações
        $('#modalAdicionarProduto input, #modalAdicionarProduto select').on('focus blur', function() {
            setTimeout(calcularSubtotalProduto, 100);
        });
        
        // Atualizar valor final a pagar
        function atualizarTotalPagar() {
            const totalComandaElement = document.getElementById('total-comanda');
            if (!totalComandaElement) return;
            
            const valorDesconto = parseFloat(inputDesconto.value || 0);
            let valorTotal = valorRestante - valorDesconto;
            
            if (valorTotal < 0) valorTotal = 0;
            
            if (checkboxParcial && checkboxParcial.checked && inputValorParcial) {
                const valorParcial = parseFloat(inputValorParcial.value || 0);
                if (valorParcial > 0 && valorParcial <= valorTotal) {
                    valorTotal = valorParcial;
                }
            }
            
            totalComandaElement.textContent = formatarDinheiro(valorTotal);
        }
        
        // Função para formatar valores monetários
        function formatarDinheiro(valor) {
            // Converte para número antes de usar toFixed
            var num = parseFloat(valor);
            
            // Verifica se é um número válido
            if (isNaN(num)) {
                num = 0;
            }
            
            return 'R$ ' + num.toFixed(2).replace('.', ',');
        }
        
        // Se o hash da URL contiver o ID de um modal, abre-o
        if(window.location.hash) {
            var hash = window.location.hash.substring(1);
            var modalElement = $('#' + hash);
            if(modalElement.length) {
                modalElement.modal('show');
            }
        }

        // impressao da comanda
        // ADICIONAR O CÓDIGO DE IMPRESSÃO AQUI
        // Manipulador para o botão de impressão da comanda
        $('#btnImprimirComanda').click(function() {
            // Busca os dados da empresa do sistema antes de imprimir
            $.ajax({
                url: 'ajax_dados_empresa.php',
                type: 'GET',
                dataType: 'json',
                success: function(dadosEmpresa) {
                    // Cria uma nova janela para impressão com os dados obtidos
                    var conteudoImpressao = prepararConteudoImpressao(dadosEmpresa);
                    var janelaImpressao = window.open('', '_blank', 'height=600,width=800');
                    
                    janelaImpressao.document.write(conteudoImpressao);
                    janelaImpressao.document.close();
                    
                    // Aguarda o carregamento completo antes de imprimir
                    janelaImpressao.onload = function() {
                        janelaImpressao.focus();
                        janelaImpressao.print();
                        // Fecha a janela após a impressão (opcional, pode ser comentado)
                        // janelaImpressao.close();
                    };
                },
                error: function() {
                    // Se não conseguir obter os dados da empresa, usa valores padrão
                    var dadosEmpresa = {
                        nome: 'EXTREME PDV',
                        fantasia: 'EXTREME HOTEL',
                        endereco: '',
                        cidade: '',
                        estado: '',
                        cep: '',
                        telefone: '',
                        cpf_cnpj: '',
                        email: ''
                    };
                    var conteudoImpressao = prepararConteudoImpressao(dadosEmpresa);
                    var janelaImpressao = window.open('', '_blank', 'height=600,width=800');
                    
                    janelaImpressao.document.write(conteudoImpressao);
                    janelaImpressao.document.close();
                    
                    janelaImpressao.onload = function() {
                        janelaImpressao.focus();
                        janelaImpressao.print();
                    };
                }
            });
        });
        
        // Função para preparar o conteúdo a ser impresso
        function prepararConteudoImpressao(dadosEmpresa) {
            // Obter todos os dados necessários da página
            var comandaId = '<?php echo $comanda["id"] ?? ""; ?>';
            var dataCriacao = '<?php echo $comanda["data_abertura_formatada"] ?? ""; ?>';
            var nomeCliente = '<?php echo esc($comanda["cliente_nome"] ?? ""); ?>';
            var valorTotal = '<?php echo formatarDinheiro($comanda["valor_total"] ?? 0); ?>';
            var observacoes = '<?php echo esc($comanda["observacoes"] ?? ""); ?>';
            
            // Definir dados da empresa
            dadosEmpresa = dadosEmpresa || {
                nome: 'EXTREME PDV',
                fantasia: 'Hotel Rio',
                endereco: 'Rua Raimundo Veridiano Cardoso, 1152 - Bela Vista',
                cidade: 'Tucuruí',
                estado: 'PA',
                cep: '',
                telefone: '(94) 91879-6546',
                cpf_cnpj: '07.081.608/0001-37',
                email: 'hotelriotucurui@gmail.com'
            };
            
            // Preparar endereço completo
            var enderecoCompleto = [];
            if (dadosEmpresa.endereco) enderecoCompleto.push(dadosEmpresa.endereco);
            
            var cidadeEstado = [];
            if (dadosEmpresa.cidade) cidadeEstado.push(dadosEmpresa.cidade);
            if (dadosEmpresa.estado) cidadeEstado.push(dadosEmpresa.estado);
            if (cidadeEstado.length > 0) enderecoCompleto.push(cidadeEstado.join(' - '));
            
            if (dadosEmpresa.cep) enderecoCompleto.push('CEP: ' + dadosEmpresa.cep);
            
            // Preparar HTML para impressão
            var html = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Comanda #${comandaId}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 20px;
                        font-size: 14px;
                    }
                    .cabecalho {
                        text-align: center;
                        margin-bottom: 20px;
                        border-bottom: 1px solid #ccc;
                        padding-bottom: 10px;
                    }
                    .cabecalho h1 {
                        margin: 0 0 5px 0;
                        font-size: 22px;
                    }
                    .cabecalho h2 {
                        margin: 0 0 10px 0;
                        font-size: 18px;
                        font-weight: normal;
                    }
                    .cabecalho p {
                        margin: 3px 0;
                        font-size: 12px;
                    }
                    .info-empresa {
                        font-size: 12px;
                        margin-bottom: 10px;
                    }
                    .info-comanda {
                        margin-bottom: 20px;
                    }
                    .info-comanda p {
                        margin: 5px 0;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 30px;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #f2f2f2;
                    }
                    .totais {
                        margin-top: 20px;
                        text-align: right;
                    }
                    .assinatura {
                        margin-top: 50px;
                        text-align: center;
                    }
                    .linha-assinatura {
                        border-top: 1px solid #000;
                        width: 70%;
                        margin: 10px auto;
                    }
                    .observacoes {
                        margin-top: 20px;
                        font-style: italic;
                    }
                    @media print {
                        .no-print {
                            display: none;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="cabecalho">
                    <h1>${dadosEmpresa.fantasia || dadosEmpresa.nome}</h1>
                    ${dadosEmpresa.nome && dadosEmpresa.fantasia && dadosEmpresa.nome !== dadosEmpresa.fantasia ? `<h2>${dadosEmpresa.nome}</h2>` : ''}
                    
                    <div class="info-empresa">
                        ${enderecoCompleto.length > 0 ? `<p>${enderecoCompleto.join(' - ')}</p>` : ''}
                        ${dadosEmpresa.telefone ? `<p>Telefone: ${dadosEmpresa.telefone}</p>` : ''}
                        ${dadosEmpresa.email ? `<p>E-mail: ${dadosEmpresa.email}</p>` : ''}
                        ${dadosEmpresa.cpf_cnpj ? `<p>${dadosEmpresa.cpf_cnpj.length > 14 ? 'CNPJ' : 'CPF'}: ${dadosEmpresa.cpf_cnpj}</p>` : ''}
                    </div>
                    
                    <h3>Comanda #${comandaId}</h3>
                </div>
                
                <div class="info-comanda">
                    <p><strong>Data/Hora:</strong> ${dataCriacao}</p>
                    <p><strong>Cliente:</strong> ${nomeCliente}</p>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>Preço Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            // Adicionar cada item da comanda na tabela
            var produtos = <?php echo json_encode($produtos_comanda ?? []); ?>;
            if (produtos.length > 0) {
                produtos.forEach(function(item) {
                    html += `
                        <tr>
                            <td>${item.produto_codigo}</td>
                            <td>${item.produto_nome}</td>
                            <td>${item.quantidade}</td>
                            <td>${formatarDinheiro(item.preco_unitario)}</td>
                            <td>${formatarDinheiro(item.subtotal)}</td>
                        </tr>`;
                });
            } else {
                html += `
                    <tr>
                        <td colspan="5" style="text-align: center;">Nenhum produto adicionado</td>
                    </tr>`;
            }
            
            // Adicionar totalização e área para assinatura
            html += `
                    </tbody>
                </table>
                
                <div class="totais">
                    <h3>Total: ${valorTotal}</h3>
                </div>`;
                
            // Adicionar observações se existirem
            if (observacoes) {
                html += `
                <div class="observacoes">
                    <p><strong>Observações:</strong> ${observacoes}</p>
                </div>`;
            }
            
            // Adicionar linha para assinatura
            html += `
                <div class="assinatura">
                    <div class="linha-assinatura"></div>
                    <p>Assinatura do Cliente</p>
                </div>
                
                <div class="no-print" style="margin-top: 20px; text-align: center;">
                    <button onclick="window.print();" style="padding: 10px 20px; cursor: pointer;">Imprimir</button>
                    <button onclick="window.close();" style="padding: 10px 20px; margin-left: 10px; cursor: pointer;">Fechar</button>
                </div>
            </body>
            </html>`;
            
            return html;
        }

    });
</script>

<?php include 'footer.php'; ?>