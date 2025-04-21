<?php
require_once 'config.php';
verificarLogin();

// Verificar se o ID do caixa foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    alerta('Caixa não especificado!', 'warning');
    header('Location: caixa.php');
    exit;
}

$caixa_id = (int)$_GET['id'];

// Inicializar a classe Caixa
$caixa = new Caixa($pdo);

// Buscar informações detalhadas do caixa
try {
    $caixa_info = $caixa->buscarPorId($caixa_id);
    
    if (!$caixa_info) {
        alerta('Caixa não encontrado!', 'warning');
        header('Location: caixa.php');
        exit;
    }
    
    $movimentacoes = $caixa->listarMovimentacoes($caixa_id);
    
    // Se for um caixa fechado, busca o resumo de pagamentos
    $resumo_pagamentos = [];
    if ($caixa_info['status'] == 'fechado') {
        $resumo_pagamentos = $caixa->resumoVendasPorFormaPagamento($caixa_id);
    }
    
    // Calcular saldo esperado e diferença se for um caixa fechado
    $saldo_esperado = 0;
    $diferenca = 0;
    
    if ($caixa_info['status'] == 'fechado') {
        $saldo_esperado = $caixa_info['valor_inicial'] + $caixa_info['valor_vendas'] + $caixa_info['valor_suprimentos'] - $caixa_info['valor_sangrias'];
        $diferenca = $caixa_info['valor_final'] - $saldo_esperado;
    }
    
} catch (Exception $e) {
    alerta('Erro ao buscar informações do caixa: ' . $e->getMessage(), 'danger');
    header('Location: caixa.php');
    exit;
}

// Template da página
$titulo_pagina = 'Detalhes do Caixa #' . $caixa_id;
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Detalhes do Caixa #<?php echo $caixa_id; ?></h1>
        <div>
            <button onclick="window.print();" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i> Imprimir
            </button>
            <a href="caixa.php" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i> Voltar para Caixa
            </a>
        </div>
    </div>
    
    <div class="card mb-4" id="relatorio-caixa">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-invoice-dollar me-2"></i> 
                <?php echo $caixa_info['status'] == 'aberto' ? 'Caixa Aberto' : 'Relatório de Caixa Fechado'; ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Informações do Caixa</h6>
                    <table class="table table-sm table-striped">
                        <tr>
                            <td><strong>Caixa:</strong></td>
                            <td>#<?php echo $caixa_id; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <span class="badge <?php echo $caixa_info['status'] == 'aberto' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo ucfirst($caixa_info['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Operador:</strong></td>
                            <td><?php echo $caixa_info['usuario_nome']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Data de Abertura:</strong></td>
                            <td><?php echo $caixa_info['data_abertura_formatada']; ?></td>
                        </tr>
                        <?php if ($caixa_info['status'] == 'fechado'): ?>
                        <tr>
                            <td><strong>Data de Fechamento:</strong></td>
                            <td><?php echo $caixa_info['data_fechamento_formatada']; ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Resumo Financeiro</h6>
                    <table class="table table-sm table-striped">
                        <tr>
                            <td><strong>Valor Inicial:</strong></td>
                            <td><?php echo formatarDinheiro($caixa_info['valor_inicial']); ?></td>
                        </tr>
                        <?php if ($caixa_info['status'] == 'fechado'): ?>
                        <tr>
                            <td><strong>Total de Vendas:</strong></td>
                            <td><?php echo formatarDinheiro($caixa_info['valor_vendas']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total de Suprimentos:</strong></td>
                            <td><?php echo formatarDinheiro($caixa_info['valor_suprimentos']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total de Sangrias:</strong></td>
                            <td><?php echo formatarDinheiro($caixa_info['valor_sangrias']); ?></td>
                        </tr>
                        <tr class="table-primary">
                            <td><strong>Saldo Esperado:</strong></td>
                            <td><strong><?php echo formatarDinheiro($saldo_esperado); ?></strong></td>
                        </tr>
                        <tr class="table-info">
                            <td><strong>Valor Final:</strong></td>
                            <td><strong><?php echo formatarDinheiro($caixa_info['valor_final']); ?></strong></td>
                        </tr>
                        <tr class="<?php echo $diferenca < 0 ? 'table-danger' : ($diferenca > 0 ? 'table-success' : 'table-secondary'); ?>">
                            <td><strong>Diferença:</strong></td>
                            <td><strong><?php echo formatarDinheiro($diferenca); ?></strong></td>
                        </tr>
                        <?php else: ?>
                        <?php
                        // Calcular totais para caixa aberto
                        $total_vendas = 0;
                        $total_sangrias = 0;
                        $total_suprimentos = 0;
                        
                        foreach ($movimentacoes as $m) {
                            if ($m['tipo'] == 'venda') $total_vendas += $m['valor'];
                            if ($m['tipo'] == 'sangria') $total_sangrias += $m['valor'];
                            if ($m['tipo'] == 'suprimento') $total_suprimentos += $m['valor'];
                        }
                        
                        $saldo_atual = $caixa_info['valor_inicial'] + $total_vendas + $total_suprimentos - $total_sangrias;
                        ?>
                        <tr>
                            <td><strong>Total de Vendas:</strong></td>
                            <td><?php echo formatarDinheiro($total_vendas); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total de Suprimentos:</strong></td>
                            <td><?php echo formatarDinheiro($total_suprimentos); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total de Sangrias:</strong></td>
                            <td><?php echo formatarDinheiro($total_sangrias); ?></td>
                        </tr>
                        <tr class="table-primary">
                            <td><strong>Saldo Atual:</strong></td>
                            <td><strong><?php echo formatarDinheiro($saldo_atual); ?></strong></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <?php if (!empty($resumo_pagamentos)): ?>
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Resumo por Forma de Pagamento</h6>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Forma de Pagamento</th>
                                <th>Quantidade</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $formas_nomes = [
                                'dinheiro' => 'Dinheiro',
                                'cartao_credito' => 'Cartão de Crédito',
                                'cartao_debito' => 'Cartão de Débito',
                                'pix' => 'PIX',
                                'boleto' => 'Boleto'
                            ];
                            
                            foreach ($resumo_pagamentos as $r): 
                                $nome_forma = $formas_nomes[$r['forma_pagamento']] ?? $r['forma_pagamento'];
                            ?>
                            <tr>
                                <td><?php echo $nome_forma; ?></td>
                                <td><?php echo $r['quantidade']; ?></td>
                                <td><?php echo formatarDinheiro($r['total']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-primary">
                                <td><strong>Total</strong></td>
                                <td><strong><?php echo array_sum(array_column($resumo_pagamentos, 'quantidade')); ?></strong></td>
                                <td><strong><?php echo formatarDinheiro(array_sum(array_column($resumo_pagamentos, 'total'))); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Observações</h6>
                    <div class="card">
                        <div class="card-body bg-light">
                            <?php echo nl2br($caixa_info['observacoes']) ?: 'Nenhuma observação registrada.'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <h6>Observações</h6>
                    <div class="card">
                        <div class="card-body bg-light">
                            <?php echo nl2br($caixa_info['observacoes']) ?: 'Nenhuma observação registrada.'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <h6>Movimentações do Caixa</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Forma Pagto</th>
                            <th>Usuário</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimentacoes)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Nenhuma movimentação registrada</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($movimentacoes as $m): ?>
                        <tr>
                            <td><?php echo $m['data_formatada']; ?></td>
                            <td>
                                <?php if ($m['tipo'] == 'venda'): ?>
                                <span class="badge bg-success">Venda</span>
                                <?php elseif ($m['tipo'] == 'sangria'): ?>
                                <span class="badge bg-danger">Sangria</span>
                                <?php elseif ($m['tipo'] == 'suprimento'): ?>
                                <span class="badge bg-info">Suprimento</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatarDinheiro($m['valor']); ?></td>
                            <td>
                                <?php 
                                if ($m['forma_pagamento']) {
                                    $formas = [
                                        'dinheiro' => 'Dinheiro',
                                        'cartao_credito' => 'Cartão de Crédito',
                                        'cartao_debito' => 'Cartão de Débito',
                                        'pix' => 'PIX',
                                        'boleto' => 'Boleto'
                                    ];
                                    echo $formas[$m['forma_pagamento']] ?? $m['forma_pagamento'];
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo $m['usuario_nome']; ?></td>
                            <td><?php echo $m['observacoes'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-center">
                <p class="mb-1">Relatório gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
                <p class="small text-muted">EXTREME PDV v1.0</p>
            </div>
        </div>
    </div>
</div>

<style type="text/css" media="print">
    @media print {
        body * {
            visibility: hidden;
        }
        #relatorio-caixa, #relatorio-caixa * {
            visibility: visible;
        }
        #relatorio-caixa {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .card {
            border: none !important;
        }
        .card-header {
            background-color: #f8f9fa !important;
            color: #000 !important;
            border-bottom: 1px solid #dee2e6 !important;
        }
    }
</style>

<?php include 'footer.php'; ?>