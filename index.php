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

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Bem-vindo(a) ao Sistema PDV</h5>
                </div>
                <div class="card-body">
                    <p>Olá, <?php echo $_SESSION['usuario_nome']; ?>!</p>
                    <p>Você está no painel de controle do Sistema PDV. Utilize o menu lateral para navegar pelo sistema.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Produtos com Estoque Baixo -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="card-title mb-0">Produtos com Estoque Baixo</h5>
                </div>
                <div class="card-body">
                    <?php
                    $produtos_estoque_baixo = $produto->listarEstoqueBaixo();
                    if (count($produtos_estoque_baixo) > 0) {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm table-striped">';
                        echo '<thead><tr><th>Código</th><th>Produto</th><th>Atual</th><th>Mínimo</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($produtos_estoque_baixo as $p) {
                            echo '<tr>';
                            echo '<td>'.$p['codigo'].'</td>';
                            echo '<td>'.$p['nome'].'</td>';
                            echo '<td class="text-danger">'.$p['estoque_atual'].'</td>';
                            echo '<td>'.$p['estoque_minimo'].'</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    } else {
                        echo '<p class="text-center">Nenhum produto com estoque baixo.</p>';
                    }
                    ?>
                    <a href="produtos.php" class="btn btn-sm btn-outline-secondary">Ver todos os produtos</a>
                </div>
            </div>
        </div>
        
        <!-- Vendas Recentes -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Vendas Recentes</h5>
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
                        echo '<table class="table table-sm table-striped">';
                        echo '<thead><tr><th>ID</th><th>Data</th><th>Cliente</th><th>Valor</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($vendas_recentes as $v) {
                            echo '<tr>';
                            echo '<td>'.$v['id'].'</td>';
                            echo '<td>'.$v['data'].'</td>';
                            echo '<td>'.($v['cliente'] ?: 'Cliente não informado').'</td>';
                            echo '<td>'.formatarDinheiro($v['valor_total']).'</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    } else {
                        echo '<p class="text-center">Nenhuma venda recente.</p>';
                    }
                    ?>
                    <a href="vendas.php" class="btn btn-sm btn-outline-secondary">Ver todas as vendas</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Resumo Financeiro -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Resumo Financeiro</h5>
                </div>
                <div class="card-body">
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
                    ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body text-center">
                                    <h5>Vendas de Hoje</h5>
                                    <h3 class="text-primary"><?php echo formatarDinheiro($vendas_hoje['valor_total'] ?: 0); ?></h3>
                                    <p><?php echo $vendas_hoje['total_vendas']; ?> venda(s)</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body text-center">
                                    <h5>Vendas do Mês</h5>
                                    <h3 class="text-primary"><?php echo formatarDinheiro($vendas_mes['valor_total'] ?: 0); ?></h3>
                                    <p><?php echo $vendas_mes['total_vendas']; ?> venda(s)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="relatorios.php" class="btn btn-sm btn-outline-primary">Ver relatórios detalhados</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
