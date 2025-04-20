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
$produto_obj = new Produto($pdo);
// Inicializar o objeto relatório aqui, para uso em toda a página
$relatorio_obj = new Relatorio($pdo);

// Processar ações
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// Processar ajuste de estoque
if ($acao == 'ajustar' && isset($_POST['produto_id'], $_POST['quantidade'], $_POST['tipo'])) {
    try {
        $produto_id = intval($_POST['produto_id']);
        $quantidade = intval($_POST['quantidade']);
        $tipo = $_POST['tipo'];
        $observacao = isset($_POST['observacao']) ? $_POST['observacao'] : '';
        
        if ($quantidade <= 0) {
            throw new Exception("A quantidade deve ser maior que zero");
        }
        
        if (!in_array($tipo, ['entrada', 'saida'])) {
            throw new Exception("Tipo de movimentação inválido");
        }
        
        $produto = $produto_obj->buscarPorId($produto_id);
        if (!$produto) {
            throw new Exception("Produto não encontrado");
        }
        
        // Se for saída, verificar se tem estoque suficiente
        if ($tipo == 'saida' && $produto['estoque_atual'] < $quantidade) {
            throw new Exception("Estoque insuficiente para esta operação");
        }
        
        // Registrar movimentação
        $resultado = $produto_obj->registrarMovimentacao([
            'produto_id' => $produto_id,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
            'observacao' => $observacao,
            'origem' => 'ajuste_manual'
        ]);
        
        if ($resultado) {
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $operacao = ($tipo == 'entrada') ? "Entrada" : "Saída";
                $GLOBALS['log']->registrar(
                    'Estoque', 
                    "{$operacao} de {$quantidade} unidades do produto {$produto['nome']} (ID: {$produto_id})"
                );
            }
            
            alerta("Estoque ajustado com sucesso!", 'success');
            header('Location: estoque.php');
            exit;
        } else {
            throw new Exception("Erro ao ajustar estoque");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Obter informações para exibição
$produtos_estoque_baixo = $produto_obj->listarEstoqueBaixo();

// Verificar filtros de relatório
$filtro_produto = isset($_GET['produto_id']) ? intval($_GET['produto_id']) : null;
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Se tem filtros, preparar para exibir o relatório
$movimentacoes = [];
if ($acao == 'relatorio' || $filtro_produto) {
    // Formatar datas para o formato correto para consulta
    $data_inicio_formatada = date('Y-m-d 00:00:00', strtotime($filtro_data_inicio));
    $data_fim_formatada = date('Y-m-d 23:59:59', strtotime($filtro_data_fim));
    
    $movimentacoes = $relatorio_obj->movimentacoesEstoque(
        $filtro_produto, 
        $data_inicio_formatada, 
        $data_fim_formatada
    );
}

// Template da página
$titulo_pagina = 'Controle de Estoque - Sistema PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-warehouse me-2 text-primary"></i>
                Controle de Estoque
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item active">Estoque</li>
                </ol>
            </nav>
        </div>
        
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjusteEstoque">
                <i class="fas fa-plus-circle me-1"></i>
                Ajustar Estoque
            </button>
        </div>
    </div>
    
    <!-- Abas -->
    <ul class="nav nav-tabs mb-4" id="estoqueTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="visao-geral-tab" data-bs-toggle="tab" data-bs-target="#visao-geral" type="button" role="tab" aria-controls="visao-geral" aria-selected="true">
                <i class="fas fa-chart-pie me-1"></i>
                Visão Geral
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="estoque-baixo-tab" data-bs-toggle="tab" data-bs-target="#estoque-baixo" type="button" role="tab" aria-controls="estoque-baixo" aria-selected="false">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Estoque Baixo
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="relatorio-tab" data-bs-toggle="tab" data-bs-target="#relatorio" type="button" role="tab" aria-controls="relatorio" aria-selected="false">
                <i class="fas fa-file-alt me-1"></i>
                Relatório de Movimentações
            </button>
        </li>
    </ul>
 <!-- PARTE 2 -->
<!-- Conteúdo das abas -->
<div class="tab-content" id="estoqueTabContent">
        <!-- Aba de Visão Geral -->
        <div class="tab-pane fade show active" id="visao-geral" role="tabpanel" aria-labelledby="visao-geral-tab">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cubes me-2"></i>
                        Estoque Atual de Produtos
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover datatable mb-0" id="tabelaProdutos">
                            <thead>
                                <tr>
                                    <th width="80">Código</th>
                                    <th data-priority="1">Produto</th>
                                    <th data-priority="2">Categoria</th>
                                    <th data-priority="1" width="100">Estoque</th>
                                    <th data-priority="2" width="100">Mínimo</th>
                                    <th data-priority="3" width="120">Preço Compra</th>
                                    <th data-priority="3" width="120">Preço Venda</th>
                                    <th data-priority="1" width="100">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $produtos = $produto_obj->listar();
                                if (empty($produtos)): 
                                ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <p class="mb-0">Nenhum produto cadastrado.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($produtos as $produto): ?>
                                        <tr class="<?php echo ($produto['estoque_atual'] <= $produto['estoque_minimo']) ? 'table-warning' : ''; ?>">
                                            <td><span class="badge bg-secondary"><?php echo esc($produto['codigo']); ?></span></td>
                                            <td><?php echo esc($produto['nome']); ?></td>
                                            <td><?php echo esc($produto['categoria_nome'] ?? 'Sem categoria'); ?></td>
                                            <td>
                                                <span class="badge <?php echo ($produto['estoque_atual'] <= $produto['estoque_minimo']) ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                                    <?php echo $produto['estoque_atual']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $produto['estoque_minimo']; ?></td>
                                            <td><?php echo formatarDinheiro($produto['preco_custo']); ?></td>
                                            <td><?php echo formatarDinheiro($produto['preco_venda']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" 
                                                           class="btn btn-primary btn-ajuste-rapido" 
                                                           data-id="<?php echo $produto['id']; ?>"
                                                           data-nome="<?php echo esc($produto['nome']); ?>"
                                                           data-codigo="<?php echo esc($produto['codigo']); ?>"
                                                           data-estoque="<?php echo $produto['estoque_atual']; ?>"
                                                           data-bs-toggle="tooltip" 
                                                           title="Ajustar Estoque"
                                                           style="display: inline-block !important; background-color: #0d6efd !important;">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    
                                                    <a href="estoque.php?acao=relatorio&produto_id=<?php echo $produto['id']; ?>" 
                                                       class="btn btn-info text-white" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Ver Histórico"
                                                       style="display: inline-block !important; background-color: #0dcaf0 !important;">
                                                        <i class="fas fa-history"></i>
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
        </div>
        
        <!-- Aba de Estoque Baixo -->
        <div class="tab-pane fade" id="estoque-baixo" role="tabpanel" aria-labelledby="estoque-baixo-tab">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Produtos com Estoque Abaixo do Mínimo
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover datatable mb-0" id="tabelaEstoqueBaixo">
                            <thead>
                                <tr>
                                    <th width="80">Código</th>
                                    <th data-priority="1">Produto</th>
                                    <th data-priority="2">Categoria</th>
                                    <th data-priority="1" width="100">Estoque</th>
                                    <th data-priority="1" width="100">Mínimo</th>
                                    <th data-priority="2" width="100">A Comprar</th>
                                    <th data-priority="3" width="120">Preço Compra</th>
                                    <th data-priority="1" width="100">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (empty($produtos_estoque_baixo)): 
                                ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <p class="mb-0">Não há produtos com estoque baixo.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($produtos_estoque_baixo as $produto): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?php echo esc($produto['codigo']); ?></span></td>
                                            <td><?php echo esc($produto['nome']); ?></td>
                                            <td><?php echo esc($produto['categoria_nome'] ?? 'Sem categoria'); ?></td>
                                            <td><span class="badge bg-warning text-dark"><?php echo $produto['estoque_atual']; ?></span></td>
                                            <td><?php echo $produto['estoque_minimo']; ?></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?php echo max(0, $produto['estoque_minimo'] - $produto['estoque_atual']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatarDinheiro($produto['preco_custo']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" 
                                                           class="btn btn-primary btn-ajuste-rapido" 
                                                           data-id="<?php echo $produto['id']; ?>"
                                                           data-nome="<?php echo esc($produto['nome']); ?>"
                                                           data-codigo="<?php echo esc($produto['codigo']); ?>"
                                                           data-estoque="<?php echo $produto['estoque_atual']; ?>"
                                                           data-bs-toggle="tooltip" 
                                                           title="Ajustar Estoque"
                                                           style="display: inline-block !important; background-color: #0d6efd !important;">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    
                                                    <a href="estoque.php?acao=relatorio&produto_id=<?php echo $produto['id']; ?>" 
                                                       class="btn btn-info text-white" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Ver Histórico"
                                                       style="display: inline-block !important; background-color: #0dcaf0 !important;">
                                                        <i class="fas fa-history"></i>
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
        </div>
        
        <!-- Aba de Relatório de Movimentações -->
        <div class="tab-pane fade" id="relatorio" role="tabpanel" aria-labelledby="relatorio-tab">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Filtros do Relatório
                    </h5>
                </div>
                <div class="card-body">
                    <form action="estoque.php" method="get" class="row g-3">
                        <input type="hidden" name="acao" value="relatorio">
                        
                        <div class="col-md-4">
                            <label for="produto_id" class="form-label">Produto:</label>
                            <select class="form-select" id="produto_id" name="produto_id">
                                <option value="">Todos os produtos</option>
                                <?php foreach ($produtos as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo ($filtro_produto == $p['id']) ? 'selected' : ''; ?>>
                                        <?php echo esc($p['codigo'] . ' - ' . $p['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="data_inicio" class="form-label">Data Inicial:</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $filtro_data_inicio; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="data_fim" class="form-label">Data Final:</label>
                            <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $filtro_data_fim; ?>">
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        <?php if ($filtro_produto): ?>
                            <?php 
                            $produto_filtrado = $produto_obj->buscarPorId($filtro_produto);
                            echo 'Movimentações do Produto: ' . esc($produto_filtrado['nome']);
                            ?>
                        <?php else: ?>
                            Histórico de Movimentações de Estoque
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover datatable mb-0" id="tabelaMovimentacoes">
                            <thead>
                                <tr>
                                    <th data-priority="3" width="80">ID</th>
                                    <th data-priority="1">Produto</th>
                                    <th data-priority="1" width="100">Tipo</th>
                                    <th data-priority="1" width="100">Quantidade</th>
                                    <th data-priority="2" width="120">Origem</th>
                                    <th data-priority="2">Data</th>
                                    <th data-priority="3">Usuário</th>
                                    <th data-priority="2">Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (empty($movimentacoes)): 
                                ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                            <p class="mb-0">Não foram encontradas movimentações para os filtros selecionados.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($movimentacoes as $movimentacao): ?>
                                        <tr>
                                            <td><?php echo $movimentacao['id']; ?></td>
                                            <td>
                                                <span class="badge bg-secondary me-1"><?php echo esc($movimentacao['produto_codigo']); ?></span>
                                                <?php echo esc($movimentacao['produto_nome']); ?>
                                            </td>
                                            <td>
                                                <?php if ($movimentacao['tipo'] == 'entrada'): ?>
                                                    <span class="badge bg-success">Entrada</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Saída</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $movimentacao['quantidade']; ?></td>
                                            <td>
                                                <?php
                                                $origens = [
                                                    'compra' => '<span class="badge bg-primary">Compra</span>',
                                                    'venda' => '<span class="badge bg-info text-dark">Venda</span>',
                                                    'ajuste_manual' => '<span class="badge bg-warning text-dark">Ajuste</span>',
                                                    'devolucao' => '<span class="badge bg-secondary">Devolução</span>'
                                                ];
                                                echo $origens[$movimentacao['origem']] ?? $movimentacao['origem'];
                                                ?>
                                            </td>
                                            <td><?php echo $movimentacao['data_formatada']; ?></td>
                                            <td><?php echo esc($movimentacao['usuario_nome']); ?></td>
                                            <td>
                                                <?php if (!empty($movimentacao['observacao'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                            data-bs-toggle="tooltip" data-bs-html="true"
                                                            title="<?php echo esc($movimentacao['observacao']); ?>">
                                                        <i class="fas fa-comment-alt"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
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
        </div>
    </div>
     <!-- PARTE 3 --> 
     </div>

<!-- Modal Ajuste de Estoque -->
<div class="modal fade" id="modalAjusteEstoque" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Ajuste de Estoque
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="estoque.php?acao=ajustar" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="produto_id_ajuste" class="form-label fw-bold">Produto:</label>
                        <select class="form-select form-select-lg" id="produto_id_ajuste" name="produto_id" required>
                            <option value="">Selecione um produto</option>
                            <?php foreach ($produtos as $p): ?>
                                <option value="<?php echo $p['id']; ?>" data-estoque="<?php echo $p['estoque_atual']; ?>">
                                    <?php echo esc($p['codigo'] . ' - ' . $p['nome']); ?> (Estoque: <?php echo $p['estoque_atual']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo de Ajuste:</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo" id="tipo_entrada" value="entrada" checked>
                                    <label class="form-check-label" for="tipo_entrada">
                                        <i class="fas fa-arrow-circle-up text-success me-1"></i>
                                        Entrada
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo" id="tipo_saida" value="saida">
                                    <label class="form-check-label" for="tipo_saida">
                                        <i class="fas fa-arrow-circle-down text-danger me-1"></i>
                                        Saída
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="quantidade" class="form-label fw-bold">Quantidade:</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" value="1" required>
                            <small id="estoque-info" class="form-text text-muted mt-1">
                                Estoque atual: <span id="estoque-atual">-</span>
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacao" class="form-label fw-bold">Motivo do Ajuste:</label>
                        <textarea class="form-control" id="observacao" name="observacao" rows="3" placeholder="Explique o motivo deste ajuste de estoque..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>
                        Confirmar Ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ajuste Rápido -->
<div class="modal fade" id="modalAjusteRapido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Ajuste Rápido de Estoque
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="estoque.php?acao=ajustar" method="post">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle fa-2x me-2"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Produto: <span id="produto-nome-rapido"></span></h5>
                                <p class="mb-0">Código: <strong><span id="produto-codigo-rapido"></span></strong></p>
                                <p class="mb-0">Estoque atual: <strong><span id="produto-estoque-rapido"></span></strong></p>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="produto_id_rapido" name="produto_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo de Ajuste:</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo" id="tipo_entrada_rapido" value="entrada" checked>
                                    <label class="form-check-label" for="tipo_entrada_rapido">
                                        <i class="fas fa-arrow-circle-up text-success me-1"></i>
                                        Entrada
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo" id="tipo_saida_rapido" value="saida">
                                    <label class="form-check-label" for="tipo_saida_rapido">
                                        <i class="fas fa-arrow-circle-down text-danger me-1"></i>
                                        Saída
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="quantidade_rapido" class="form-label fw-bold">Quantidade:</label>
                            <input type="number" class="form-control" id="quantidade_rapido" name="quantidade" min="1" value="1" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacao_rapido" class="form-label fw-bold">Motivo do Ajuste:</label>
                        <textarea class="form-control" id="observacao_rapido" name="observacao" rows="3" placeholder="Explique o motivo deste ajuste de estoque..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>
                        Confirmar Ajuste
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
    
    /* Estilo para abas */
    .nav-tabs .nav-link {
        border-radius: 0.5rem 0.5rem 0 0;
    }
    
    .nav-tabs .nav-link.active {
        background-color: #f8f9fa;
        border-color: #dee2e6 #dee2e6 #f8f9fa;
        font-weight: 500;
    }
    
    /* Melhoria na visualização de tabelas em dispositivos pequenos */
    @media (max-width: 576px) {
        .datatable td, .datatable th {
            padding: 0.5rem 0.5rem;
        }
    }
</style>

<script>
    $(document).ready(function() {
        // Não inicializamos DataTables aqui porque já está sendo inicializado no footer.php
        // Os ids das tabelas já estão na lista de exclusão: '#tabelaProdutos,#tabelaEstoqueBaixo,#tabelaMovimentacoes'
        
        // Inicializar tooltips
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Mostrar estoque atual do produto selecionado no modal de ajuste
        $('#produto_id_ajuste').change(function() {
            var option = $(this).find('option:selected');
            var estoque = option.data('estoque') || 0;
            
            $('#estoque-atual').text(estoque);
        });
        
        // Configurar modal de ajuste rápido quando clicado no botão
        $('.btn-ajuste-rapido').click(function() {
            var id = $(this).data('id');
            var nome = $(this).data('nome');
            var codigo = $(this).data('codigo');
            var estoque = $(this).data('estoque');
            
            $('#produto_id_rapido').val(id);
            $('#produto-nome-rapido').text(nome);
            $('#produto-codigo-rapido').text(codigo);
            $('#produto-estoque-rapido').text(estoque);
            
            var modalAjusteRapido = new bootstrap.Modal(document.getElementById('modalAjusteRapido'));
            modalAjusteRapido.show();
        });
        
        // Verificar quantidade máxima para saída de estoque
        $('#tipo_saida, #tipo_entrada').change(function() {
            checkQuantityLimit('produto_id_ajuste', 'quantidade', 'tipo_saida');
        });
        
        $('#quantidade').change(function() {
            checkQuantityLimit('produto_id_ajuste', 'quantidade', 'tipo_saida');
        });
        
        $('#produto_id_ajuste').change(function() {
            checkQuantityLimit('produto_id_ajuste', 'quantidade', 'tipo_saida');
        });
        
        // Para o modal de ajuste rápido
        $('#tipo_saida_rapido, #tipo_entrada_rapido').change(function() {
            var estoque = parseInt($('#produto-estoque-rapido').text()) || 0;
            var isSaida = $('#tipo_saida_rapido').prop('checked');
            
            if (isSaida) {
                $('#quantidade_rapido').attr('max', estoque);
                // Ajusta o valor se estiver acima do máximo
                var quantidade = parseInt($('#quantidade_rapido').val()) || 0;
                if (quantidade > estoque) {
                    $('#quantidade_rapido').val(estoque);
                }
            } else {
                $('#quantidade_rapido').removeAttr('max');
            }
        });
        
        $('#quantidade_rapido').change(function() {
            var estoque = parseInt($('#produto-estoque-rapido').text()) || 0;
            var isSaida = $('#tipo_saida_rapido').prop('checked');
            
            if (isSaida) {
                var quantidade = parseInt($(this).val()) || 0;
                if (quantidade > estoque) {
                    $(this).val(estoque);
                    alert('Quantidade não pode ser maior que o estoque atual para saídas.');
                }
            }
        });
        
        // Função para verificar limite de quantidade
        function checkQuantityLimit(selectId, quantityId, radioId) {
            var option = $('#' + selectId + ' option:selected');
            var estoque = parseInt(option.data('estoque')) || 0;
            var isSaida = $('#' + radioId).prop('checked');
            
            if (isSaida) {
                $('#' + quantityId).attr('max', estoque);
                // Ajusta o valor se estiver acima do máximo
                var quantidade = parseInt($('#' + quantityId).val()) || 0;
                if (quantidade > estoque) {
                    $('#' + quantityId).val(estoque);
                    alert('Quantidade não pode ser maior que o estoque atual para saídas.');
                }
            } else {
                $('#' + quantityId).removeAttr('max');
            }
        }
        
        // Ativar aba específica baseado no URL
        activateTabFromUrl();
        
        // Manter aba selecionada após recarregar página
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(this).attr('href');
            var relatedTab = $('.nav-tabs .nav-link[data-bs-target="' + target + '"]');
            
            if (relatedTab.length > 0) {
                // Salva a aba no localStorage
                localStorage.setItem('activeEstoqueTab', relatedTab.attr('id'));
            }
        });
        
        // Função para ativar aba baseado no URL ou localStorage
        function activateTabFromUrl() {
            // Verificar se tem filtro para relatório
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('acao') && urlParams.get('acao') === 'relatorio') {
                // Ativa a aba de relatório
                $('#relatorio-tab').tab('show');
                return;
            }
            
            // Se não tem filtro no URL, tenta restaurar da última aba visitada
            var activeTab = localStorage.getItem('activeEstoqueTab');
            if (activeTab) {
                $('#' + activeTab).tab('show');
            }
        }
    });
</script>

<?php include 'footer.php'; ?>