<?php
require_once 'config.php';
verificarLogin();

// Parâmetros para relatórios
$tipo_relatorio = isset($_GET['tipo']) ? $_GET['tipo'] : 'vendas_periodo';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');

// Dados do relatório
$dados_relatorio = [];
$titulo_relatorio = '';

// Carregar dados do relatório selecionado
switch ($tipo_relatorio) {
    case 'vendas_periodo':
        $titulo_relatorio = 'Vendas por Período';
        $dados_relatorio = $venda->relatorioVendasPorPeriodo($data_inicio, $data_fim);
        break;
        
    case 'vendas_vendedor':
        $titulo_relatorio = 'Vendas por Vendedor';
        $dados_relatorio = $venda->relatorioVendasPorVendedor($data_inicio, $data_fim);
        break;
        
    case 'produtos_mais_vendidos':
        $titulo_relatorio = 'Produtos Mais Vendidos';
        $dados_relatorio = $venda->relatorioProdutosMaisVendidos($data_inicio, $data_fim);
        break;
        
    case 'estoque_atual':
        $titulo_relatorio = 'Estoque Atual';
        $dados_relatorio = $relatorio->estoqueAtual();
        break;
        
    case 'produtos_estoque_baixo':
        $titulo_relatorio = 'Produtos com Estoque Baixo';
        $dados_relatorio = $relatorio->produtosAbaixoEstoqueMinimo();
        break;
        
    case 'faturamento_diario':
        $titulo_relatorio = 'Faturamento Diário';
        $dados_relatorio = $relatorio->faturamentoDiario($mes, $ano);
        break;
        
    case 'faturamento_mensal':
        $titulo_relatorio = 'Faturamento Mensal';
        $dados_relatorio = $relatorio->faturamentoMensal($ano);
        break;
        
    case 'lucratividade':
        $titulo_relatorio = 'Relatório de Lucratividade';
        $dados_relatorio = $relatorio->lucratividade($data_inicio, $data_fim);
        break;
}

// Template da página
$titulo_pagina = 'Relatórios';
include 'header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Relatórios</h1>
    
    <div class="row">
        <!-- Coluna de seleção de relatórios -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Selecione o Relatório</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="?tipo=vendas_periodo" class="list-group-item list-group-item-action <?php echo $tipo_relatorio == 'vendas_periodo' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt me-2"></i> Vendas por Período
                        </a>
                        <a href="?tipo=vendas_vendedor" class="list-group-item list-group-item-action <?php echo $tipo_relatorio == 'vendas_vendedor' ? 'active' : ''; ?>">
                            <i class="fas fa-user-tag me-2"></i> Vendas por Vendedor
                        </a>
                        <a href="?tipo=produtos_mais_vendidos" class="list-group-item list-group-item-action <?php echo $tipo_relatorio == 'produtos_mais_vendidos' ? 'active' : ''; ?>">
                            <i class="fas fa-trophy me-2"></i> Produtos Mais Vendidos
                        </a>
                        <a href="?tipo=estoque_atual" class="list-group-item list-group-item-action <?php echo $tipo_relatorio == 'estoque_atual' ? 'active' : ''; ?>">
                            <i class="fas fa-boxes me-2"></i> Estoque Atual
                        </a>
                        <a href="?tipo=produtos_estoque_baixo" class="list-group-item list-group-item-action <?php echo $tipo_relatorio == 'produtos_estoque_baixo' ? 'active' : ''; ?>">
                            <i class="fas fa-exclamation-triangle me-2"></i> Produtos com Estoque Baixo
                        </a>
                        <a href="?tipo=faturamento_diario" class="list-group-item list-group-item-action <?php echo $tipo_relatorio == 'faturamento_diario' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line me-2"></i> Faturamento Diário
                        </a>
                        <a href="?tipo=faturamento_mensal" class="list-group-item list-group-item-action <?php echo $tipo_relatorio == 'faturamento_mensal' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar me-2"></i> Faturamento Mensal
                        </a>
                        <a href="?tipo=lucratividade" class="list-group-item list-group-item-action <?php echo $tipo_relatorio == 'lucratividade' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie me-2"></i> Lucratividade
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Coluna de relatório -->
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo $titulo_relatorio; ?></h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
                <div class="card-body">
                    <!-- Filtros para relatórios com período -->
                    <?php if (in_array($tipo_relatorio, ['vendas_periodo', 'vendas_vendedor', 'produtos_mais_vendidos', 'lucratividade'])): ?>
                    <form method="get" action="" class="mb-4 row g-3 align-items-end">
                        <input type="hidden" name="tipo" value="<?php echo $tipo_relatorio; ?>">
                        
                        <div class="col-md-4">
                            <label for="data_inicio" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="data_fim" class="form-label">Data Final</label>
                            <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Filtros para faturamento diário -->
                    <?php if ($tipo_relatorio == 'faturamento_diario'): ?>
                    <form method="get" action="" class="mb-4 row g-3 align-items-end">
                        <input type="hidden" name="tipo" value="<?php echo $tipo_relatorio; ?>">
                        
                        <div class="col-md-4">
                            <label for="mes" class="form-label">Mês</label>
                            <select class="form-select" id="mes" name="mes">
                                <?php
                                $meses = [
                                    '01' => 'Janeiro',
                                    '02' => 'Fevereiro',
                                    '03' => 'Março',
                                    '04' => 'Abril',
                                    '05' => 'Maio',
                                    '06' => 'Junho',
                                    '07' => 'Julho',
                                    '08' => 'Agosto',
                                    '09' => 'Setembro',
                                    '10' => 'Outubro',
                                    '11' => 'Novembro',
                                    '12' => 'Dezembro'
                                ];
                                
                                foreach ($meses as $num => $nome) {
                                    $selected = ($mes == $num) ? 'selected' : '';
                                    echo '<option value="'.$num.'" '.$selected.'>'.$nome.'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="ano" class="form-label">Ano</label>
                            <select class="form-select" id="ano" name="ano">
                                <?php
                                $ano_atual = date('Y');
                                for ($a = $ano_atual; $a >= $ano_atual - 5; $a--) {
                                    $selected = ($ano == $a) ? 'selected' : '';
                                    echo '<option value="'.$a.'" '.$selected.'>'.$a.'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Filtros para faturamento mensal -->
                    <?php if ($tipo_relatorio == 'faturamento_mensal'): ?>
                    <form method="get" action="" class="mb-4 row g-3 align-items-end">
                        <input type="hidden" name="tipo" value="<?php echo $tipo_relatorio; ?>">
                        
                        <div class="col-md-8">
                            <label for="ano" class="form-label">Ano</label>
                            <select class="form-select" id="ano" name="ano">
                                <?php
                                $ano_atual = date('Y');
                                for ($a = $ano_atual; $a >= $ano_atual - 5; $a--) {
                                    $selected = ($ano == $a) ? 'selected' : '';
                                    echo '<option value="'.$a.'" '.$selected.'>'.$a.'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                    <?php endif; ?>
                    
                    <?php if (empty($dados_relatorio)): ?>
                    <div class="alert alert-info">
                        Nenhum dado encontrado para o período selecionado.
                    </div>
                    <?php else: ?>
                    
                    <!-- Conteúdo do relatório -->
                    <div class="table-responsive">
                        <?php if ($tipo_relatorio == 'vendas_periodo'): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th>Forma de Pagamento</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total = 0;
                                foreach ($dados_relatorio as $venda) {
                                    echo '<tr>';
                                    echo '<td>'.$venda['id'].'</td>';
                                    echo '<td>'.$venda['data'].'</td>';
                                    echo '<td>'.($venda['cliente'] ?: 'Cliente não identificado').'</td>';
                                    echo '<td>'.$venda['vendedor'].'</td>';
                                    echo '<td>'.ucfirst(str_replace('_', ' ', $venda['forma_pagamento'])).'</td>';
                                    echo '<td>'.formatarDinheiro($venda['valor_total']).'</td>';
                                    echo '</tr>';
                                    $total += $venda['valor_total'];
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Total:</th>
                                    <th><?php echo formatarDinheiro($total); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php endif; ?>
                        
                        <?php if ($tipo_relatorio == 'vendas_vendedor'): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Vendedor</th>
                                    <th>Total de Vendas</th>
                                    <th>Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_vendas = 0;
                                $total_valor = 0;
                                foreach ($dados_relatorio as $vendedor) {
                                    echo '<tr>';
                                    echo '<td>'.$vendedor['vendedor'].'</td>';
                                    echo '<td>'.$vendedor['total_vendas'].'</td>';
                                    echo '<td>'.formatarDinheiro($vendedor['valor_total']).'</td>';
                                    echo '</tr>';
                                    $total_vendas += $vendedor['total_vendas'];
                                    $total_valor += $vendedor['valor_total'];
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th><?php echo $total_vendas; ?></th>
                                    <th><?php echo formatarDinheiro($total_valor); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php endif; ?>
                        
                        <?php if ($tipo_relatorio == 'produtos_mais_vendidos'): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Produto</th>
                                    <th>Quantidade Total</th>
                                    <th>Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_quantidade = 0;
                                $total_valor = 0;
                                foreach ($dados_relatorio as $produto) {
                                    echo '<tr>';
                                    echo '<td>'.$produto['codigo'].'</td>';
                                    echo '<td>'.$produto['produto'].'</td>';
                                    echo '<td>'.$produto['quantidade_total'].'</td>';
                                    echo '<td>'.formatarDinheiro($produto['valor_total']).'</td>';
                                    echo '</tr>';
                                    $total_quantidade += $produto['quantidade_total'];
                                    $total_valor += $produto['valor_total'];
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Total</th>
                                    <th><?php echo $total_quantidade; ?></th>
                                    <th><?php echo formatarDinheiro($total_valor); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php endif; ?>
                        
                        <?php if ($tipo_relatorio == 'estoque_atual'): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Estoque</th>
                                    <th>Mínimo</th>
                                    <th>Custo Unit.</th>
                                    <th>Valor Estoque</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_qtd = 0;
                                $total_valor = 0;
                                foreach ($dados_relatorio as $produto) {
                                    echo '<tr>';
                                    echo '<td>'.$produto['codigo'].'</td>';
                                    echo '<td>'.$produto['nome'].'</td>';
                                    echo '<td>'.$produto['categoria'].'</td>';
                                    echo '<td>'.($produto['estoque_atual'] <= $produto['estoque_minimo'] ? '<span class="text-danger">'.$produto['estoque_atual'].'</span>' : $produto['estoque_atual']).'</td>';
                                    echo '<td>'.$produto['estoque_minimo'].'</td>';
                                    echo '<td>'.formatarDinheiro($produto['preco_custo']).'</td>';
                                    echo '<td>'.formatarDinheiro($produto['valor_estoque']).'</td>';
                                    echo '</tr>';
                                    $total_qtd += $produto['estoque_atual'];
                                    $total_valor += $produto['valor_estoque'];
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total</th>
                                    <th><?php echo $total_qtd; ?></th>
                                    <th></th>
                                    <th></th>
                                    <th><?php echo formatarDinheiro($total_valor); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php endif; ?>
                        
                        <?php if ($tipo_relatorio == 'produtos_estoque_baixo'): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Estoque Atual</th>
                                    <th>Estoque Mínimo</th>
                                    <th>Comprar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_comprar = 0;
                                foreach ($dados_relatorio as $produto) {
                                    echo '<tr>';
                                    echo '<td>'.$produto['codigo'].'</td>';
                                    echo '<td>'.$produto['nome'].'</td>';
                                    echo '<td>'.$produto['categoria'].'</td>';
                                    echo '<td class="text-danger">'.$produto['estoque_atual'].'</td>';
                                    echo '<td>'.$produto['estoque_minimo'].'</td>';
                                    echo '<td class="text-primary">'.$produto['quantidade_comprar'].'</td>';
                                    echo '</tr>';
                                    $total_comprar += $produto['quantidade_comprar'];
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5">Total</th>
                                    <th><?php echo $total_comprar; ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php endif; ?>
                        
                        <?php if ($tipo_relatorio == 'faturamento_diario'): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Dia</th>
                                    <th>Total de Vendas</th>
                                    <th>Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_vendas = 0;
                                $total_valor = 0;
                                foreach ($dados_relatorio as $dia) {
                                    echo '<tr>';
                                    echo '<td>'.$dia['dia'].'</td>';
                                    echo '<td>'.$dia['total_vendas'].'</td>';
                                    echo '<td>'.formatarDinheiro($dia['valor_total']).'</td>';
                                    echo '</tr>';
                                    $total_vendas += $dia['total_vendas'];
                                    $total_valor += $dia['valor_total'];
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th><?php echo $total_vendas; ?></th>
                                    <th><?php echo formatarDinheiro($total_valor); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php endif; ?>
                        
                        <?php if ($tipo_relatorio == 'faturamento_mensal'): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Mês</th>
                                    <th>Total de Vendas</th>
                                    <th>Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_vendas = 0;
                                $total_valor = 0;
                                $meses = [
                                    1 => 'Janeiro',
                                    2 => 'Fevereiro',
                                    3 => 'Março',
                                    4 => 'Abril',
                                    5 => 'Maio',
                                    6 => 'Junho',
                                    7 => 'Julho',
                                    8 => 'Agosto',
                                    9 => 'Setembro',
                                    10 => 'Outubro',
                                    11 => 'Novembro',
                                    12 => 'Dezembro'
                                ];
                                
                                foreach ($dados_relatorio as $mes_dado) {
                                    echo '<tr>';
                                    echo '<td>'.$meses[$mes_dado['mes']].'</td>';
                                    echo '<td>'.$mes_dado['total_vendas'].'</td>';
                                    echo '<td>'.formatarDinheiro($mes_dado['valor_total']).'</td>';
                                    echo '</tr>';
                                    $total_vendas += $mes_dado['total_vendas'];
                                    $total_valor += $mes_dado['valor_total'];
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th><?php echo $total_vendas; ?></th>
                                    <th><?php echo formatarDinheiro($total_valor); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php endif; ?>
                        
                        <?php if ($tipo_relatorio == 'lucratividade'): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID Venda</th>
                                    <th>Data</th>
                                    <th>Receita</th>
                                    <th>Custo</th>
                                    <th>Lucro</th>
                                    <th>Margem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_receita = 0;
                                $total_custo = 0;
                                $total_lucro = 0;
                                foreach ($dados_relatorio as $venda) {
                                    echo '<tr>';
                                    echo '<td>'.$venda['venda_id'].'</td>';
                                    echo '<td>'.$venda['data'].'</td>';
                                    echo '<td>'.formatarDinheiro($venda['receita']).'</td>';
                                    echo '<td>'.formatarDinheiro($venda['custo']).'</td>';
                                    echo '<td>'.formatarDinheiro($venda['lucro']).'</td>';
                                    echo '<td>'.number_format($venda['margem_lucro'], 2, ',', '.').'%</td>';
                                    echo '</tr>';
                                    $total_receita += $venda['receita'];
                                    $total_custo += $venda['custo'];
                                    $total_lucro += $venda['lucro'];
                                }
                                
                                // Calcular margem média
                                $margem_media = ($total_receita > 0) ? ($total_lucro / $total_receita * 100) : 0;
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Total</th>
                                    <th><?php echo formatarDinheiro($total_receita); ?></th>
                                    <th><?php echo formatarDinheiro($total_custo); ?></th>
                                    <th><?php echo formatarDinheiro($total_lucro); ?></th>
                                    <th><?php echo number_format($margem_media, 2, ',', '.'); ?>%</th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style media="print">
    /* Estilos para impressão */
    @page {
        size: landscape;
    }
    
    .sidebar, .navbar, .footer, .card-header button, form {
        display: none !important;
    }
    
    .content-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        box-shadow: none !important;
        border: none !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
    
    .table {
        width: 100% !important;
    }
</style>

<?php include 'footer.php'; ?>
