<?php
/**
 * EXTREME PDV - Relatório de Comandas com Pagamentos Parciais
 * 
 * Este arquivo gera relatórios de comandas que possuem pagamentos parciais registrados
 */
require_once 'config.php';
verificarLogin();

// Verificar permissões
if ($_SESSION['usuario_nivel'] != 'admin' && $_SESSION['usuario_nivel'] != 'gerente') {
    alerta('Você não tem permissão para acessar esta página', 'danger');
    header('Location: index.php');
    exit;
}

// Definir filtros
$filtros = [];
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01'); // Primeiro dia do mês atual
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d'); // Data atual
$status = isset($_GET['status']) ? $_GET['status'] : 'aberta'; // Por padrão, mostra apenas comandas abertas

// Buscar comandas com pagamentos parciais
$sql = "
    SELECT 
        c.id AS comanda_id,
        c.data_abertura,
        DATE_FORMAT(c.data_abertura, '%d/%m/%Y %H:%i') AS data_abertura_formatada,
        c.data_fechamento,
        DATE_FORMAT(c.data_fechamento, '%d/%m/%Y %H:%i') AS data_fechamento_formatada,
        c.valor_total,
        c.status,
        cl.id AS cliente_id,
        cl.nome AS cliente_nome,
        cl.telefone AS cliente_telefone,
        u1.nome AS usuario_abertura,
        u2.nome AS usuario_fechamento,
        (SELECT COUNT(*) FROM pagamentos_comanda WHERE comanda_id = c.id) AS qtd_pagamentos,
        (SELECT COALESCE(SUM(valor_pago), 0) FROM pagamentos_comanda WHERE comanda_id = c.id) AS total_pago
    FROM comandas c
    LEFT JOIN clientes cl ON c.cliente_id = cl.id
    LEFT JOIN usuarios u1 ON c.usuario_abertura_id = u1.id
    LEFT JOIN usuarios u2 ON c.usuario_fechamento_id = u2.id
    WHERE 
        c.id IN (SELECT DISTINCT comanda_id FROM pagamentos_comanda)
        AND DATE(c.data_abertura) BETWEEN ? AND ?
";

// Adicionar filtro por status
if ($status != 'todos') {
    $sql .= " AND c.status = ?";
    $filtros[] = $status;
}

$sql .= " ORDER BY c.data_abertura DESC";

// Preparar e executar a consulta
$stmt = $pdo->prepare($sql);
$params = [$data_inicio, $data_fim];

if ($status != 'todos') {
    $params[] = $status;
}

$stmt->execute($params);
$comandas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Template da página
$titulo_pagina = 'Relatório de Comandas com Pagamentos Parciais';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>
                Relatório de Comandas com Pagamentos Parciais
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item"><a href="relatorios.php">Relatórios</a></li>
                    <li class="breadcrumb-item active">Pagamentos Parciais</li>
                </ol>
            </nav>
        </div>
        
        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a href="comandas.php" class="btn btn-outline-secondary">
                <i class="fas fa-clipboard-list me-1"></i>
                Voltar para Comandas
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print me-1"></i>
                Imprimir Relatório
            </button>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>
                Filtros
            </h5>
        </div>
        <div class="card-body">
            <form action="relatorio_pagamentos_parciais.php" method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="data_inicio" class="form-label">Data Inicial:</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="data_fim" class="form-label">Data Final:</label>
                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="status" class="form-label">Status:</label>
                    <select class="form-select" id="status" name="status">
                        <option value="todos" <?php echo $status == 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="aberta" <?php echo $status == 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                        <option value="fechada" <?php echo $status == 'fechada' ? 'selected' : ''; ?>>Fechada</option>
                        <option value="cancelada" <?php echo $status == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
                
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Sumário do Relatório -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Total de Comandas com Pagamentos Parciais
                    </h6>
                    <h2 class="text-primary"><?php echo count($comandas); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-coins me-2"></i>
                        Valor Total das Comandas
                    </h6>
                    <?php
                    $valor_total_comandas = 0;
                    foreach ($comandas as $c) {
                        $valor_total_comandas += $c['valor_total'];
                    }
                    ?>
                    <h2 class="text-primary"><?php echo formatarDinheiro($valor_total_comandas); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        Total Já Pago
                    </h6>
                    <?php
                    $total_pago = 0;
                    foreach ($comandas as $c) {
                        $total_pago += $c['total_pago'];
                    }
                    $percentual_pago = $valor_total_comandas > 0 ? ($total_pago / $valor_total_comandas * 100) : 0;
                    ?>
                    <h2 class="text-success"><?php echo formatarDinheiro($total_pago); ?></h2>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentual_pago; ?>%"></div>
                    </div>
                    <small class="text-muted"><?php echo number_format($percentual_pago, 1); ?>% do valor total</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Listagem das Comandas -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Comandas com Pagamentos Parciais
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($comandas)): ?>
                <div class="alert alert-info m-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhuma comanda com pagamentos parciais encontrada no período selecionado.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Data Abertura</th>
                                <th class="text-end">Valor Total</th>
                                <th class="text-end">Valor Pago</th>
                                <th class="text-end">Valor Restante</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comandas as $c): ?>
                            <?php
                                $valor_restante = $c['valor_total'] - $c['total_pago'];
                                $percentual_pago = $c['valor_total'] > 0 ? ($c['total_pago'] / $c['valor_total'] * 100) : 0;
                            ?>
                            <tr>
                                <td><?php echo $c['comanda_id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo esc($c['cliente_nome']); ?></strong>
                                        <?php if (!empty($c['cliente_telefone'])): ?>
                                        <div><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo esc($c['cliente_telefone']); ?></small></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $c['data_abertura_formatada']; ?>
                                    <div><small class="text-muted">por <?php echo esc($c['usuario_abertura']); ?></small></div>
                                </td>
                                <td class="text-end"><?php echo formatarDinheiro($c['valor_total']); ?></td>
                                <td class="text-end">
                                    <span class="text-success"><?php echo formatarDinheiro($c['total_pago']); ?></span>
                                    <div>
                                        <small class="text-muted"><?php echo number_format($percentual_pago, 1); ?>%</small>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentual_pago; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end fw-bold"><?php echo formatarDinheiro($valor_restante); ?></td>
                                <td>
                                    <?php if ($c['status'] == 'aberta'): ?>
                                        <span class="badge bg-success">Aberta</span>
                                    <?php elseif ($c['status'] == 'fechada'): ?>
                                        <span class="badge bg-secondary">Fechada</span>
                                        <div><small class="text-muted"><?php echo $c['data_fechamento_formatada']; ?></small></div>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="comandas.php?id=<?php echo $c['comanda_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>
                                        Detalhes
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Totais por Status -->
    <?php if (!empty($comandas)): ?>
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Pagamentos por Status da Comanda
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $status_totais = [
                        'aberta' => ['qtd' => 0, 'valor_total' => 0, 'total_pago' => 0],
                        'fechada' => ['qtd' => 0, 'valor_total' => 0, 'total_pago' => 0],
                        'cancelada' => ['qtd' => 0, 'valor_total' => 0, 'total_pago' => 0]
                    ];
                    
                    foreach ($comandas as $c) {
                        $status = $c['status'];
                        $status_totais[$status]['qtd']++;
                        $status_totais[$status]['valor_total'] += $c['valor_total'];
                        $status_totais[$status]['total_pago'] += $c['total_pago'];
                    }
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-end">Valor Total</th>
                                    <th class="text-end">Valor Pago</th>
                                    <th class="text-center">% Pago</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($status_totais as $status => $dados): ?>
                                <?php if ($dados['qtd'] > 0): ?>
                                <tr>
                                    <td>
                                        <?php if ($status == 'aberta'): ?>
                                            <span class="badge bg-success">Aberta</span>
                                        <?php elseif ($status == 'fechada'): ?>
                                            <span class="badge bg-secondary">Fechada</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Cancelada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo $dados['qtd']; ?></td>
                                    <td class="text-end"><?php echo formatarDinheiro($dados['valor_total']); ?></td>
                                    <td class="text-end"><?php echo formatarDinheiro($dados['total_pago']); ?></td>
                                    <td class="text-center">
                                        <?php 
                                        $percentual = $dados['valor_total'] > 0 ? ($dados['total_pago'] / $dados['valor_total'] * 100) : 0;
                                        echo number_format($percentual, 1) . '%';
                                        ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th>Total</th>
                                    <th class="text-center"><?php echo count($comandas); ?></th>
                                    <th class="text-end"><?php echo formatarDinheiro($valor_total_comandas); ?></th>
                                    <th class="text-end"><?php echo formatarDinheiro($total_pago); ?></th>
                                    <th class="text-center">
                                        <?php echo number_format($percentual_pago, 1); ?>%
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Comandas por Cliente (Top 5)
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Agrupar comandas por cliente
                    $clientes = [];
                    foreach ($comandas as $c) {
                        $cliente_id = $c['cliente_id'];
                        $cliente_nome = $c['cliente_nome'];
                        
                        if (!isset($clientes[$cliente_id])) {
                            $clientes[$cliente_id] = [
                                'nome' => $cliente_nome,
                                'qtd' => 0,
                                'valor_total' => 0,
                                'total_pago' => 0
                            ];
                        }
                        
                        $clientes[$cliente_id]['qtd']++;
                        $clientes[$cliente_id]['valor_total'] += $c['valor_total'];
                        $clientes[$cliente_id]['total_pago'] += $c['total_pago'];
                    }
                    
                    // Ordenar por valor total
                    uasort($clientes, function($a, $b) {
                        return $b['valor_total'] - $a['valor_total'];
                    });
                    
                    // Pegar os 5 primeiros
                    $top_clientes = array_slice($clientes, 0, 5, true);
                    ?>
                    
                    <?php if (empty($top_clientes)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhum dado disponível.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th class="text-center">Comandas</th>
                                        <th class="text-end">Valor Total</th>
                                        <th class="text-end">Valor Pago</th>
                                        <th class="text-end">Saldo Devedor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_clientes as $cliente_id => $cliente): ?>
                                    <?php
                                        $saldo_devedor = $cliente['valor_total'] - $cliente['total_pago'];
                                        $percentual_pago = $cliente['valor_total'] > 0 ? ($cliente['total_pago'] / $cliente['valor_total'] * 100) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo esc($cliente['nome']); ?></td>
                                        <td class="text-center"><?php echo $cliente['qtd']; ?></td>
                                        <td class="text-end"><?php echo formatarDinheiro($cliente['valor_total']); ?></td>
                                        <td class="text-end"><?php echo formatarDinheiro($cliente['total_pago']); ?></td>
                                        <td class="text-end">
                                            <?php echo formatarDinheiro($saldo_devedor); ?>
                                            <div class="progress mt-1" style="height: 4px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentual_pago; ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    body {
        padding: 20px;
    }
    
    .navbar, .breadcrumb, .btn, footer {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        break-inside: avoid;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    
    .badge.bg-success {
        background-color: #28a745 !important;
        color: #fff !important;
    }
    
    .badge.bg-secondary {
        background-color: #6c757d !important;
        color: #fff !important;
    }
    
    .badge.bg-danger {
        background-color: #dc3545 !important;
        color: #fff !important;
    }
    
    .text-success {
        color: #28a745 !important;
    }
    
    .progress-bar.bg-success {
        background-color: #28a745 !important;
    }
}
</style>

<?php include 'footer.php'; ?>