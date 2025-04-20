<?php
require_once 'config.php';
verificarLogin();

// Verificar se há dados de fechamento na sessão OU se foi solicitada uma reimpressão por ID
$fechamento_ativo = isset($_SESSION['fechamento_caixa']);
$reimpressao = false;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $caixa_id = intval($_GET['id']);
    $reimpressao = true;
} elseif ($fechamento_ativo) {
    $fechamento = $_SESSION['fechamento_caixa'];
    $caixa_id = $fechamento['caixa_id'];
} else {
    alerta('Nenhum fechamento de caixa em andamento!', 'warning');
    header('Location: caixa.php');
    exit;
}

// Inicializar a classe Caixa
$caixa = new Caixa($pdo);

// Buscar informações detalhadas do caixa
try {
    $caixa_info = $caixa->buscarPorId($caixa_id);
    
    // Verificar se o caixa existe e está fechado para reimpressão
    if ($reimpressao && (!$caixa_info || $caixa_info['status'] != 'fechado')) {
        alerta('Caixa não encontrado ou não está fechado!', 'warning');
        header('Location: caixa.php');
        exit;
    }
    
    $movimentacoes = $caixa->listarMovimentacoes($caixa_id);
    $resumo_pagamentos = $caixa->resumoVendasPorFormaPagamento($caixa_id);
    
    // Se for uma reimpressão, construa o array de fechamento com os dados do caixa
    if ($reimpressao) {
        $fechamento = [
            'caixa_id' => $caixa_id,
            'valor_inicial' => $caixa_info['valor_inicial'],
            'valor_final' => $caixa_info['valor_final'],
            'valor_vendas' => $caixa_info['valor_vendas'],
            'valor_sangrias' => $caixa_info['valor_sangrias'],
            'valor_suprimentos' => $caixa_info['valor_suprimentos'],
            'valor_esperado' => $caixa_info['valor_inicial'] + $caixa_info['valor_vendas'] + $caixa_info['valor_suprimentos'] - $caixa_info['valor_sangrias'],
            'diferenca' => $caixa_info['valor_final'] - ($caixa_info['valor_inicial'] + $caixa_info['valor_vendas'] + $caixa_info['valor_suprimentos'] - $caixa_info['valor_sangrias'])
        ];
    }
} catch (Exception $e) {
    alerta('Erro ao buscar informações do caixa: ' . $e->getMessage(), 'danger');
    header('Location: caixa.php');
    exit;
}

// Template da página
$titulo_pagina = $reimpressao ? 'Reimpressão de Fechamento de Caixa #' . $caixa_id : 'Fechamento de Caixa';
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $reimpressao ? 'Reimpressão de Fechamento - Caixa #' . $caixa_id : 'Fechamento de Caixa #' . $caixa_id; ?></h1>
        <div>
            <button onclick="window.print();" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i> Imprimir
            </button>
            <a href="caixa.php" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i> Voltar para Caixa
            </a>
        </div>
    </div>
    
    <div class="card mb-4" id="relatorio-fechamento">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Relatório de Fechamento</h5>
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
                            <td><strong>Operador:</strong></td>
                            <td><?php echo $caixa_info['usuario_nome']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Data de Abertura:</strong></td>
                            <td><?php echo $caixa_info['data_abertura_formatada']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Data de Fechamento:</strong></td>
                            <td><?php echo $caixa_info['data_fechamento_formatada']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Resumo Financeiro</h6>
                    <table class="table table-sm table-striped">
                        <tr>
                            <td><strong>Valor Inicial:</strong></td>
                            <td><?php echo formatarDinheiro($fechamento['valor_inicial']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total de Vendas:</strong></td>
                            <td><?php echo formatarDinheiro($fechamento['valor_vendas']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total de Suprimentos:</strong></td>
                            <td><?php echo formatarDinheiro($fechamento['valor_suprimentos']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total de Sangrias:</strong></td>
                            <td><?php echo formatarDinheiro($fechamento['valor_sangrias']); ?></td>
                        </tr>
                        <tr class="table-primary">
                            <td><strong>Saldo Esperado:</strong></td>
                            <td><strong><?php echo formatarDinheiro($fechamento['valor_esperado']); ?></strong></td>
                        </tr>
                        <tr class="table-info">
                            <td><strong>Valor Informado:</strong></td>
                            <td><strong><?php echo formatarDinheiro($fechamento['valor_final']); ?></strong></td>
                        </tr>
                        <tr class="<?php echo $fechamento['diferenca'] < 0 ? 'table-danger' : ($fechamento['diferenca'] > 0 ? 'table-success' : 'table-secondary'); ?>">
                            <td><strong>Diferença:</strong></td>
                            <td><strong><?php echo formatarDinheiro($fechamento['diferenca']); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
            
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
                            <?php if (empty($resumo_pagamentos)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Nenhuma venda registrada</td>
                            </tr>
                            <?php else: ?>
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
                            <?php endif; ?>
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
                <p class="mb-1"><?php echo $reimpressao ? 'Reimpressão realizada em ' . date('d/m/Y H:i:s') : 'Fechamento realizado em ' . date('d/m/Y H:i:s'); ?></p>
                <p class="small text-muted">Mauro Carlos |94| 981709809 - Sistema PDV v1.0</p>
                <?php if ($reimpressao): ?>
                <div class="alert alert-info mt-2">
                    <i class="fas fa-info-circle me-2"></i>
                    Esta é uma reimpressão do fechamento de caixa. O documento original foi gerado em <?php echo $caixa_info['data_fechamento_formatada']; ?>.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style type="text/css" media="print">
    @media print {
        body * {
            visibility: hidden;
        }
        #relatorio-fechamento, #relatorio-fechamento * {
            visibility: visible;
        }
        #relatorio-fechamento {
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

<?php 
// Limpar os dados de fechamento da sessão após exibir a página,
// mas apenas se não for uma reimpressão
if (!$reimpressao && isset($_SESSION['fechamento_caixa'])) {
    unset($_SESSION['fechamento_caixa']);
}

include 'footer.php'; 
?>