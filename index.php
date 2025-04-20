<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Template da página inicial
$titulo_pagina = 'Painel Principal - Sistema PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-md-row align-items-center">
                    <div class="me-0 me-md-4 mb-3 mb-md-0 text-center text-md-start">
                        <div class="display-4 text-primary">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                    </div>
                    <div class="text-center text-md-start">
                        <h2 class="card-title mb-1 fs-3">Bem-vindo(a) ao EXTREME PDV</h2>
                        <p class="text-muted mb-0">Olá, <?php echo $_SESSION['usuario_nome']; ?>! Acompanhe suas vendas e controle seu estoque facilmente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Overview -->
    <?php
    // Vendas do dia
    $hoje = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_vendas,
            SUM(valor_total) AS valor_total
        FROM vendas
        WHERE DATE(data_venda) = :hoje AND status = 'finalizada'
    ");
    $stmt->bindParam(':hoje', $hoje);
    $stmt->execute();
    $vendas_hoje = $stmt->fetch();
    
    // Vendas do mês
    $mes_atual = date('Y-m');
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_vendas,
            SUM(valor_total) AS valor_total
        FROM vendas
        WHERE DATE_FORMAT(data_venda, '%Y-%m') = :mes AND status = 'finalizada'
    ");
    $stmt->bindParam(':mes', $mes_atual);
    $stmt->execute();
    $vendas_mes = $stmt->fetch();
    
    // Total de produtos
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM produtos WHERE ativo = TRUE");
    $stmt->execute();
    $total_produtos = $stmt->fetch();
    
    // Total de clientes
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM clientes");
    $stmt->execute();
    $total_clientes = $stmt->fetch();
    ?>
    
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card bg-white">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart text-primary"></i>
                </div>
                <p class="stat-label">Vendas Hoje</p>
                <h3 class="stat-value text-primary"><?php echo formatarDinheiro($vendas_hoje['valor_total'] ?: 0); ?></h3>
                <p class="mb-0 text-muted"><?php echo $vendas_hoje['total_vendas']; ?> venda(s)</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card bg-white">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt text-success"></i>
                </div>
                <p class="stat-label">Vendas do Mês</p>
                <h3 class="stat-value text-success"><?php echo formatarDinheiro($vendas_mes['valor_total'] ?: 0); ?></h3>
                <p class="mb-0 text-muted"><?php echo $vendas_mes['total_vendas']; ?> venda(s)</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card bg-white">
                <div class="stat-icon">
                    <i class="fas fa-box text-warning"></i>
                </div>
                <p class="stat-label">Produtos</p>
                <h3 class="stat-value text-warning"><?php echo $total_produtos['total']; ?></h3>
                <p class="mb-0 text-muted">cadastrados no sistema</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card bg-white">
                <div class="stat-icon">
                    <i class="fas fa-users text-info"></i>
                </div>
                <p class="stat-label">Clientes</p>
                <h3 class="stat-value text-info"><?php echo $total_clientes['total']; ?></h3>
                <p class="mb-0 text-muted">cadastrados no sistema</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Produtos com Estoque Baixo -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-warning text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Produtos com Estoque Baixo
                    </h5>
                    <a href="produtos.php" class="btn btn-sm btn-light">
                        <i class="fas fa-eye me-1"></i>
                        <span class="d-none d-sm-inline">Ver Todos</span>
                    </a>
                </div>
                <div class="card-body">
                    <?php
                    $produtos_estoque_baixo = $produto->listarEstoqueBaixo();
                    if (count($produtos_estoque_baixo) > 0) {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm table-hover">';
                        echo '<thead><tr><th>Código</th><th>Produto</th><th>Atual</th><th>Mínimo</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($produtos_estoque_baixo as $p) {
                            echo '<tr>';
                            echo '<td><span class="badge bg-secondary">'.$p['codigo'].'</span></td>';
                            echo '<td>'.$p['nome'].'</td>';
                            echo '<td><span class="badge bg-danger">'.$p['estoque_atual'].'</span></td>';
                            echo '<td><span class="badge bg-dark">'.$p['estoque_minimo'].'</span></td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    } else {
                        echo '<div class="text-center py-4">';
                        echo '<i class="fas fa-check-circle text-success fa-3x mb-3"></i>';
                        echo '<p class="mb-0">Nenhum produto com estoque baixo.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Vendas Recentes -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Vendas Recentes
                    </h5>
                    <a href="vendas.php" class="btn btn-sm btn-light">
                        <i class="fas fa-eye me-1"></i>
                        <span class="d-none d-sm-inline">Ver Todas</span>
                    </a>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("
                        SELECT v.id, v.valor_total, DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data, c.nome AS cliente
                        FROM vendas v
                        LEFT JOIN clientes c ON v.cliente_id = c.id
                        WHERE v.status = 'finalizada'
                        ORDER BY v.data_venda DESC
                        LIMIT 5
                    ");
                    $vendas_recentes = $stmt->fetchAll();
                    
                    if (count($vendas_recentes) > 0) {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm table-hover">';
                        echo '<thead><tr><th>ID</th><th>Data</th><th>Cliente</th><th>Valor</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($vendas_recentes as $v) {
                            echo '<tr>';
                            echo '<td><span class="badge bg-secondary">'.$v['id'].'</span></td>';
                            echo '<td>'.$v['data'].'</td>';
                            echo '<td>'.($v['cliente'] ?: '<span class="text-muted">Cliente não informado</span>').'</td>';
                            echo '<td><span class="badge bg-success">'.formatarDinheiro($v['valor_total']).'</span></td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    } else {
                        echo '<div class="text-center py-4">';
                        echo '<i class="fas fa-receipt text-muted fa-3x mb-3"></i>';
                        echo '<p class="mb-0">Nenhuma venda recente.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Ações Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <a href="pdv.php" class="btn btn-primary w-100 py-3">
                                <i class="fas fa-cash-register d-block mx-auto mb-2"></i>
                                <span>Nova Venda</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="clientes.php?acao=novo" class="btn btn-success w-100 py-3">
                                <i class="fas fa-user-plus d-block mx-auto mb-2"></i>
                                <span>Novo Cliente</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="produtos.php?acao=novo" class="btn btn-info w-100 py-3 text-white">
                                <i class="fas fa-box-open d-block mx-auto mb-2"></i>
                                <span>Novo Produto</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="relatorios.php" class="btn btn-warning w-100 py-3 text-white">
                                <i class="fas fa-chart-line d-block mx-auto mb-2"></i>
                                <span>Relatórios</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumo Financeiro -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Resumo Financeiro
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-day fa-3x text-primary mb-3"></i>
                                    <h5>Vendas de Hoje</h5>
                                    <h3 class="text-primary"><?php echo formatarDinheiro($vendas_hoje['valor_total'] ?: 0); ?></h3>
                                    <p class="mb-0"><?php echo $vendas_hoje['total_vendas']; ?> venda(s)</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                                    <h5>Vendas do Mês</h5>
                                    <h3 class="text-primary"><?php echo formatarDinheiro($vendas_mes['valor_total'] ?: 0); ?></h3>
                                    <p class="mb-0"><?php echo $vendas_mes['total_vendas']; ?> venda(s)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="relatorios.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-alt me-1"></i>
                            Ver relatórios detalhados
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>