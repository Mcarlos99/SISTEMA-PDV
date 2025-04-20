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
$relatorio_obj = new Relatorio($pdo);
$venda_obj = new Venda($pdo);
$produto_obj = new Produto($pdo);

// Definir parâmetros padrão para filtros
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$tipo_relatorio = isset($_GET['tipo']) ? $_GET['tipo'] : 'vendas_periodo';

// Processar solicitação de download do relatório em CSV
if (isset($_GET['download']) && $_GET['download'] == 'csv') {
    $tipo = $_GET['tipo'];
    
    // Formatar datas para consulta
    $datetime_inicio = date('Y-m-d 00:00:00', strtotime($data_inicio));
    $datetime_fim = date('Y-m-d 23:59:59', strtotime($data_fim));
    
    // Nome do arquivo CSV
    $filename = 'relatorio_' . $tipo . '_' . date('Y-m-d') . '.csv';
    
    // Cabeçalhos para download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Criar o arquivo CSV
    $output = fopen('php://output', 'w');
    
    // Incluir BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));



    // Gerar o CSV com base no tipo de relatório
    switch ($tipo) {
        case 'vendas_periodo':
            $vendas = $relatorio_obj->faturamentoDiario(date('m', strtotime($data_inicio)), date('Y', strtotime($data_inicio)));
            fputcsv($output, ['Data', 'Total de Vendas', 'Valor Total']);
            foreach ($vendas as $venda) {
                fputcsv($output, [
                    $venda['dia'] . '/' . date('m', strtotime($data_inicio)) . '/' . date('Y', strtotime($data_inicio)),
                    $venda['total_vendas'],
                    number_format($venda['valor_total'], 2, ',', '.')
                ]);
            }
            break;
            
        case 'produtos_vendidos':
            $produtos = $relatorio_obj->produtosMaisVendidos($datetime_inicio, $datetime_fim);
            fputcsv($output, ['Código', 'Produto', 'Quantidade Vendida', 'Valor Total']);
            foreach ($produtos as $produto) {
                fputcsv($output, [
                    $produto['codigo'],
                    $produto['nome'],
                    $produto['quantidade_total'],
                    number_format($produto['valor_total'], 2, ',', '.')
                ]);
            }
            break;
            
        case 'vendas_vendedor':
            $vendedores = $relatorio_obj->vendedoresPorVenda($datetime_inicio, $datetime_fim);
            fputcsv($output, ['Vendedor', 'Total de Vendas', 'Valor Total']);
            foreach ($vendedores as $vendedor) {
                fputcsv($output, [
                    $vendedor['vendedor'],
                    $vendedor['total_vendas'],
                    number_format($vendedor['valor_total'], 2, ',', '.')
                ]);
            }
            break;
            
        case 'estoque_atual':
            $estoque = $relatorio_obj->estoqueAtual();
            fputcsv($output, ['Código', 'Produto', 'Categoria', 'Estoque Atual', 'Estoque Mínimo', 'Preço Custo', 'Preço Venda', 'Valor em Estoque']);
            foreach ($estoque as $produto) {
                fputcsv($output, [
                    $produto['codigo'],
                    $produto['nome'],
                    $produto['categoria'],
                    $produto['estoque_atual'],
                    $produto['estoque_minimo'],
                    number_format($produto['preco_custo'], 2, ',', '.'),
                    number_format($produto['preco_venda'], 2, ',', '.'),
                    number_format($produto['valor_estoque'], 2, ',', '.')
                ]);
            }
            break;
            
            case 'lucratividade':
                $lucratividade = $relatorio_obj->lucratividade($datetime_inicio, $datetime_fim);
                fputcsv($output, ['ID Venda', 'Data', 'Receita', 'Custo', 'Lucro', 'Margem de Lucro (%)']);
                foreach ($lucratividade as $venda) {
                    fputcsv($output, [
                        $venda['venda_id'],
                        $venda['data'],
                        number_format(is_null($venda['receita']) ? 0 : $venda['receita'], 2, ',', '.'),
                        number_format(is_null($venda['custo']) ? 0 : $venda['custo'], 2, ',', '.'),
                        number_format(is_null($venda['lucro']) ? 0 : $venda['lucro'], 2, ',', '.'),
                        number_format(is_null($venda['margem_lucro']) ? 0 : $venda['margem_lucro'], 2, ',', '.')
                    ]);
                }
                break;
    }
    
    fclose($output);
    exit;
}

// Template da página
$titulo_pagina = 'Relatórios - Sistema PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-chart-bar me-2 text-primary"></i>
                Relatórios
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item active">Relatórios</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Filtros de Relatório -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>
                Filtros do Relatório
            </h5>
        </div>
        <div class="card-body">
            <form action="relatorios.php" method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="tipo" class="form-label fw-bold">Tipo de Relatório:</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="vendas_periodo" <?php echo $tipo_relatorio == 'vendas_periodo' ? 'selected' : ''; ?>>Vendas por Período</option>
                        <option value="produtos_vendidos" <?php echo $tipo_relatorio == 'produtos_vendidos' ? 'selected' : ''; ?>>Produtos Mais Vendidos</option>
                        <option value="vendas_vendedor" <?php echo $tipo_relatorio == 'vendas_vendedor' ? 'selected' : ''; ?>>Vendas por Vendedor</option>
                        <option value="estoque_atual" <?php echo $tipo_relatorio == 'estoque_atual' ? 'selected' : ''; ?>>Estoque Atual</option>
                        <option value="lucratividade" <?php echo $tipo_relatorio == 'lucratividade' ? 'selected' : ''; ?>>Lucratividade</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label fw-bold">Data Inicial:</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="data_fim" class="form-label fw-bold">Data Final:</label>
                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>
                        Gerar Relatório
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- PARTE 2 -->
    <!-- Resultados do Relatório -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    <?php
                    $tipos_relatorio = [
                        'vendas_periodo' => 'Relatório de Vendas por Período',
                        'produtos_vendidos' => 'Relatório de Produtos Mais Vendidos',
                        'vendas_vendedor' => 'Relatório de Vendas por Vendedor',
                        'estoque_atual' => 'Relatório de Estoque Atual',
                        'lucratividade' => 'Relatório de Lucratividade'
                    ];
                    
                    echo $tipos_relatorio[$tipo_relatorio] ?? 'Relatório';
                    ?>
                </h5>
                <a href="relatorios.php?tipo=<?php echo $tipo_relatorio; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&download=csv" class="btn btn-sm btn-light">
                    <i class="fas fa-download me-1"></i>
                    Exportar CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php
                // Define o locale para português do Brasil
    setlocale(LC_TIME, 'pt_BR.UTF-8');
    
    // Usa IntlDateFormatter para formatar a data em "mês/ano" no formato textual
    $fmt = new IntlDateFormatter(
    'pt_BR',                          // Locale
    IntlDateFormatter::LONG,         // Tipo de data (longo)
    IntlDateFormatter::NONE,         // Sem hora
    'America/Sao_Paulo',             // Fuso horário
    IntlDateFormatter::GREGORIAN,    // Calendário Gregoriano
    'MMMM/yyyy'                      // Formato personalizado (ex: abril/2025)
    );
            // Formatar datas para consulta
            $datetime_inicio = date('Y-m-d 00:00:00', strtotime($data_inicio));
            $datetime_fim = date('Y-m-d 23:59:59', strtotime($data_fim));
            
            // Exibir relatório com base no tipo selecionado
            switch ($tipo_relatorio) {
                case 'vendas_periodo':
                    // Pegamos o mês e ano da data de início para o relatório mensal
                    $mes = date('m', strtotime($data_inicio));
                    $ano = date('Y', strtotime($data_inicio));
                    $vendas = $relatorio_obj->faturamentoDiario($mes, $ano);
                    
                    // Preparar dados para o gráfico
                    $dias = [];
                    $valores = [];
                    $quantidades = [];
                    $total_mes = 0;
                    
                    foreach ($vendas as $venda) {
                        $dias[] = $venda['dia'];
                        $valores[] = $venda['valor_total'];
                        $quantidades[] = $venda['total_vendas'];
                        $total_mes += $venda['valor_total'];
                    }
                    
                    // Resumo do período
                    echo '<div class="row mb-4">';
                    echo '<div class="col-md-4">';
                    echo '<div class="card h-100 border-primary">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Total de Vendas no Período</h6>';
                    echo '<h3 class="card-title text-primary mb-0">' . array_sum($quantidades) . '</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="col-md-4">';
                    echo '<div class="card h-100 border-success">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Faturamento Total</h6>';
                    echo '<h3 class="card-title text-success mb-0">' . formatarDinheiro($total_mes) . '</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="col-md-4">';
                    echo '<div class="card h-100 border-info">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Ticket Médio</h6>';
                    $ticket_medio = (array_sum($quantidades) > 0) ? $total_mes / array_sum($quantidades) : 0;
                    echo '<h3 class="card-title text-info mb-0">' . formatarDinheiro($ticket_medio) . '</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
// Gráfico de vendas
echo '<div class="mb-4">';
echo '<h5>Gráfico de Vendas Diárias - ' . ucfirst($fmt->format(new DateTime($data_inicio))) . '</h5>';
echo '<canvas id="vendasChart" width="400" height="200"></canvas>';
echo '</div>';
                    
                    // Tabela de dados
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped table-hover">';
                    echo '<thead><tr><th>Dia</th><th>Total de Vendas</th><th>Valor Total</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($vendas as $venda) {
                        echo '<tr>';
                        echo '<td>' . $venda['dia'] . '/' . $mes . '</td>';
                        echo '<td>' . $venda['total_vendas'] . '</td>';
                        echo '<td>' . formatarDinheiro($venda['valor_total']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    
                    // Script para o gráfico
                    echo '<script>
                    $(document).ready(function() {
                        var ctx = document.getElementById("vendasChart").getContext("2d");
                        var chart = new Chart(ctx, {
                            type: "bar",
                            data: {
                                labels: ' . json_encode($dias) . ',
                                datasets: [
                                    {
                                        label: "Valor Total (R$)",
                                        data: ' . json_encode($valores) . ',
                                        backgroundColor: "rgba(54, 162, 235, 0.5)",
                                        borderColor: "rgba(54, 162, 235, 1)",
                                        borderWidth: 1,
                                        yAxisID: "y-axis-1"
                                    },
                                    {
                                        label: "Quantidade de Vendas",
                                        data: ' . json_encode($quantidades) . ',
                                        backgroundColor: "rgba(255, 99, 132, 0.5)",
                                        borderColor: "rgba(255, 99, 132, 1)",
                                        borderWidth: 1,
                                        type: "line",
                                        yAxisID: "y-axis-2"
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        type: "linear",
                                        display: true,
                                        position: "left",
                                        title: {
                                            display: true,
                                            text: "Eixo Y1"
                                        },
                                        ticks: {
                                            beginAtZero: true,
                                            callback: function(value) {
                                                return "R$ " + value.toFixed(2);
                                            }
                                        }
                                    },
                                    y2: {
                                        type: "linear",
                                        display: true,
                                        position: "right",
                                        title: {
                                            display: true,
                                            text: "Eixo Y2"
                                        },
                                        grid: {
                                            drawOnChartArea: false // Importante para evitar sobreposição
                                        },
                                        ticks: {
                                            beginAtZero: true
                                            }
                                        }
                                    
                                }
                            }
                        });
                    });
                    </script>';
                    break;
                    
                case 'produtos_vendidos':
                    // Buscar produtos mais vendidos no período
                    $produtos = $relatorio_obj->produtosMaisVendidos($datetime_inicio, $datetime_fim);
                    
                    // Preparar dados para o gráfico
                    $nomes = [];
                    $quantidades = [];
                    $valores = [];
                    
                    // Limitamos a 10 produtos para o gráfico
                    $produtos_grafico = array_slice($produtos, 0, 10);
                    foreach ($produtos_grafico as $produto) {
                        $nomes[] = $produto['nome'];
                        $quantidades[] = $produto['quantidade_total'];
                        $valores[] = $produto['valor_total'];
                    }
                    
                    // Gráfico de produtos
                    echo '<div class="mb-4">';
                    echo '<h5>Top 10 Produtos Mais Vendidos - ' . date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim)) . '</h5>';
                    echo '<canvas id="produtosChart" width="400" height="300"></canvas>';
                    echo '</div>';
                    
                    // Tabela de dados
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped table-hover datatable" id="tabela-produtos-vendidos">';
                    echo '<thead><tr><th>Código</th><th>Produto</th><th>Quantidade Vendida</th><th>Valor Total</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($produtos as $produto) {
                        echo '<tr>';
                        echo '<td>' . esc($produto['codigo']) . '</td>';
                        echo '<td>' . esc($produto['nome']) . '</td>';
                        echo '<td>' . $produto['quantidade_total'] . '</td>';
                        echo '<td>' . formatarDinheiro($produto['valor_total']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    
                    // Script para o gráfico
                    echo '<script>
                    $(document).ready(function() {
                        var ctx = document.getElementById("produtosChart").getContext("2d");
                        var chart = new Chart(ctx, {
                            type: "bar",  // Changed from "horizontalBar" to "bar"
                            data: {
                                labels: ' . json_encode($nomes) . ',
                                datasets: [
                                    {
                                        label: "Quantidade Vendida",
                                        data: ' . json_encode($quantidades) . ',
                                        backgroundColor: "rgba(54, 162, 235, 0.5)",
                                        borderColor: "rgba(54, 162, 235, 1)",
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                indexAxis: "y",  // This makes it a horizontal bar chart
                                responsive: true,
                                scales: {
                                    x: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    });
                    </script>';
                    break;
                    
                case 'vendas_vendedor':
                    // Buscar vendas por vendedor no período
                    $vendedores = $venda_obj->relatorioVendasPorVendedor($datetime_inicio, $datetime_fim);
                    
                    // Preparar dados para o gráfico
                    $nomes = [];
                    $quantidades = [];
                    $valores = [];
                    
                    foreach ($vendedores as $vendedor) {
                        $nomes[] = $vendedor['vendedor'];
                        $quantidades[] = $vendedor['total_vendas'];
                        $valores[] = $vendedor['valor_total'];
                    }
                    
                    // Gráfico de vendedores
                    echo '<div class="row mb-4">';
                    echo '<div class="col-md-6">';
                    echo '<h5>Vendas por Vendedor - Quantidade</h5>';
                    echo '<canvas id="vendedoresQtdChart" width="400" height="300"></canvas>';
                    echo '</div>';
                    echo '<div class="col-md-6">';
                    echo '<h5>Vendas por Vendedor - Valor</h5>';
                    echo '<canvas id="vendedoresValorChart" width="400" height="300"></canvas>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Tabela de dados
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped table-hover">';
                    echo '<thead><tr><th>Vendedor</th><th>Total de Vendas</th><th>Valor Total</th><th>Ticket Médio</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($vendedores as $vendedor) {
                        $ticket_medio = ($vendedor['total_vendas'] > 0) ? $vendedor['valor_total'] / $vendedor['total_vendas'] : 0;
                        echo '<tr>';
                        echo '<td>' . esc($vendedor['vendedor']) . '</td>';
                        echo '<td>' . $vendedor['total_vendas'] . '</td>';
                        echo '<td>' . formatarDinheiro($vendedor['valor_total']) . '</td>';
                        echo '<td>' . formatarDinheiro($ticket_medio) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    
                    // Script para os gráficos
                    echo '<script>
                    $(document).ready(function() {
                        // Gráfico de quantidade
                        var ctxQtd = document.getElementById("vendedoresQtdChart").getContext("2d");
                        var chartQtd = new Chart(ctxQtd, {
                            type: "pie",
                            data: {
                                labels: ' . json_encode($nomes) . ',
                                datasets: [
                                    {
                                        data: ' . json_encode($quantidades) . ',
                                        backgroundColor: [
                                            "rgba(255, 99, 132, 0.7)",
                                            "rgba(54, 162, 235, 0.7)",
                                            "rgba(255, 206, 86, 0.7)",
                                            "rgba(75, 192, 192, 0.7)",
                                            "rgba(153, 102, 255, 0.7)",
                                            "rgba(255, 159, 64, 0.7)",
                                            "rgba(199, 199, 199, 0.7)",
                                            "rgba(83, 102, 255, 0.7)",
                                            "rgba(40, 159, 64, 0.7)",
                                            "rgba(240, 120, 120, 0.7)"
                                        ],
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                legend: {
                                    position: "right"
                                },
                                title: {
                                    display: true,
                                    text: "Quantidade de Vendas por Vendedor"
                                }
                            }
                        });
                        
                        // Gráfico de valor
                        var ctxValor = document.getElementById("vendedoresValorChart").getContext("2d");
                        var chartValor = new Chart(ctxValor, {
                            type: "doughnut",
                            data: {
                                labels: ' . json_encode($nomes) . ',
                                datasets: [
                                    {
                                        data: ' . json_encode($valores) . ',
                                        backgroundColor: [
                                            "rgba(255, 99, 132, 0.7)",
                                            "rgba(54, 162, 235, 0.7)",
                                            "rgba(255, 206, 86, 0.7)",
                                            "rgba(75, 192, 192, 0.7)",
                                            "rgba(153, 102, 255, 0.7)",
                                            "rgba(255, 159, 64, 0.7)",
                                            "rgba(199, 199, 199, 0.7)",
                                            "rgba(83, 102, 255, 0.7)",
                                            "rgba(40, 159, 64, 0.7)",
                                            "rgba(240, 120, 120, 0.7)"
                                        ],
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                legend: {
                                    position: "right"
                                },
                                title: {
                                    display: true,
                                    text: "Valor Total de Vendas por Vendedor"
                                }
                            }
                        });
                    });
                    </script>';
                    break;
                    // PARTE 2 - 2
    case 'estoque_atual':
                    // Buscar dados de estoque atual
                    $estoque = $relatorio_obj->estoqueAtual();
                    
                    // Resumo do estoque
                    $total_itens = count($estoque);
                    $valor_total_estoque = 0;
                    $itens_abaixo_minimo = 0;
                    
                    foreach ($estoque as $produto) {
                        $valor_total_estoque += $produto['valor_estoque'];
                        if ($produto['estoque_atual'] < $produto['estoque_minimo']) {
                            $itens_abaixo_minimo++;
                        }
                    }
                    
                    // Cards de resumo
                    echo '<div class="row mb-4">';
                    echo '<div class="col-md-4">';
                    echo '<div class="card h-100 border-primary">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Total de Produtos</h6>';
                    echo '<h3 class="card-title text-primary mb-0">' . $total_itens . '</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="col-md-4">';
                    echo '<div class="card h-100 border-success">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Valor Total em Estoque</h6>';
                    echo '<h3 class="card-title text-success mb-0">' . formatarDinheiro($valor_total_estoque) . '</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="col-md-4">';
                    echo '<div class="card h-100 border-danger">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Produtos Abaixo do Mínimo</h6>';
                    echo '<h3 class="card-title text-danger mb-0">' . $itens_abaixo_minimo . '</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Tabela de estoque
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped table-hover datatable" id="tabela-estoque-atual">';
                    echo '<thead><tr><th>Código</th><th>Produto</th><th>Categoria</th><th>Estoque Atual</th><th>Mínimo</th><th>Preço Custo</th><th>Preço Venda</th><th>Valor em Estoque</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($estoque as $produto) {
                        $classe = ($produto['estoque_atual'] < $produto['estoque_minimo']) ? 'table-warning' : '';
                        echo '<tr class="' . $classe . '">';
                        echo '<td>' . esc($produto['codigo']) . '</td>';
                        echo '<td>' . esc($produto['nome']) . '</td>';
                        echo '<td>' . esc($produto['categoria']) . '</td>';
                        echo '<td>' . $produto['estoque_atual'] . '</td>';
                        echo '<td>' . $produto['estoque_minimo'] . '</td>';
                        echo '<td>' . formatarDinheiro($produto['preco_custo']) . '</td>';
                        echo '<td>' . formatarDinheiro($produto['preco_venda']) . '</td>';
                        echo '<td>' . formatarDinheiro($produto['valor_estoque']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    break;
                    
                case 'lucratividade':
                    // Buscar dados de lucratividade
                    $lucratividade = $relatorio_obj->lucratividade($datetime_inicio, $datetime_fim);
                    
                    // Calcular totais
                    $total_receita = 0;
                    $total_custo = 0;
                    $total_lucro = 0;
                    
                    foreach ($lucratividade as $venda) {
                        $total_receita += $venda['receita'];
                        $total_custo += $venda['custo'];
                        $total_lucro += $venda['lucro'];
                    }
                    
                    $margem_media = ($total_receita > 0) ? ($total_lucro / $total_receita * 100) : 0;
                    
                    // Cards de resumo
                    echo '<div class="row mb-4">';
                    echo '<div class="col-md-3">';
                    echo '<div class="card h-100 border-primary">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Receita Total</h6>';
                    echo '<h3 class="card-title text-primary mb-0">' . formatarDinheiro($total_receita) . '</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="col-md-3">';
                    echo '<div class="card h-100 border-warning">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Custo Total</h6>';
                    echo '<h3 class="card-title text-warning mb-0">' . formatarDinheiro($total_custo) . '</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="col-md-3">';
                    echo '<div class="card h-100 border-success">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Lucro Total</h6>';
                    echo '<h3 class="card-title text-success mb-0">' . formatarDinheiro($total_lucro) . '</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="col-md-3">';
                    echo '<div class="card h-100 border-info">';
                    echo '<div class="card-body text-center">';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Margem Média</h6>';
                    echo '<h3 class="card-title text-info mb-0">' . number_format($margem_media, 2, ',', '.') . '%</h3>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Gráfico de lucratividade
                    echo '<div class="mb-4">';
                    echo '<h5>Lucratividade - ' . date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim)) . '</h5>';
                    echo '<canvas id="lucratividadeChart" width="400" height="200"></canvas>';
                    echo '</div>';
                    
                    // Tabela de dados
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped table-hover datatable" id="tabela-lucratividade">';
                    echo '<thead><tr><th>ID Venda</th><th>Data</th><th>Receita</th><th>Custo</th><th>Lucro</th><th>Margem (%)</th></tr></thead>';
                    echo '<tbody>';
                    
                    // Preparar dados para o gráfico
                    $vendas_ids = [];
                    $receitas = [];
                    $custos = [];
                    $lucros = [];
                    $margens = [];
                    
                    // Limitamos a 20 últimas vendas para o gráfico
                    $lucratividade_grafico = array_slice($lucratividade, 0, 20);
                    
                    foreach ($lucratividade as $venda) {
                        echo '<tr>';
                        echo '<td>' . $venda['venda_id'] . '</td>';
                        echo '<td>' . $venda['data'] . '</td>';
                        echo '<td>' . formatarDinheiro($venda['receita']) . '</td>';
                        echo '<td>' . formatarDinheiro($venda['custo']) . '</td>';
                        echo '<td>' . formatarDinheiro($venda['lucro']) . '</td>';
                        echo '<td>' . (is_null($venda['margem_lucro']) ? '0,00' : number_format($venda['margem_lucro'], 2, ',', '.')) . '%</td>';
                        echo '</tr>';
                        
                        // Adicionar aos arrays para o gráfico
                        if (in_array($venda, $lucratividade_grafico)) {
                            $vendas_ids[] = '#' . $venda['venda_id'];
                            $receitas[] = $venda['receita'];
                            $custos[] = $venda['custo'];
                            $lucros[] = $venda['lucro'];
                            $margens[] = $venda['margem_lucro'];
                        }
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    
                    // Inverter arrays para o gráfico (mostrar mais recentes à direita)
                    $vendas_ids = array_reverse($vendas_ids);
                    $receitas = array_reverse($receitas);
                    $custos = array_reverse($custos);
                    $lucros = array_reverse($lucros);
                    
                    // Script para o gráfico
                    echo '<script>
                    $(document).ready(function() {
                        var ctx = document.getElementById("lucratividadeChart").getContext("2d");
                        var chart = new Chart(ctx, {
                            type: "bar",
                            data: {
                                labels: ' . json_encode($vendas_ids) . ',
                                datasets: [
                                    {
                                        label: "Receita",
                                        data: ' . json_encode($receitas) . ',
                                        backgroundColor: "rgba(54, 162, 235, 0.5)",
                                        borderColor: "rgba(54, 162, 235, 1)",
                                        borderWidth: 1
                                    },
                                    {
                                        label: "Custo",
                                        data: ' . json_encode($custos) . ',
                                        backgroundColor: "rgba(255, 206, 86, 0.5)",
                                        borderColor: "rgba(255, 206, 86, 1)",
                                        borderWidth: 1
                                    },
                                    {
                                        label: "Lucro",
                                        data: ' . json_encode($lucros) . ',
                                        backgroundColor: "rgba(75, 192, 192, 0.5)",
                                        borderColor: "rgba(75, 192, 192, 1)",
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        ticks: {
                                            beginAtZero: true,
                                            callback: function(value, index, values) {
                                                return "R$ " + value.toFixed(2);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    });
                    </script>';
                    break;
                    
                default:
                    echo '<div class="alert alert-info">';
                    echo '<h4 class="alert-heading">Selecione um tipo de relatório</h4>';
                    echo '<p>Escolha um tipo de relatório e defina o período para gerar os dados.</p>';
                    echo '</div>';
                    break;
            }
            ?>
        </div>
    </div>
    <!-- PARTE 3 -->
    </div>

<script>
    $(document).ready(function() {
        // Verificar se o Chart.js está disponível
        if (typeof Chart === 'undefined') {
            console.error('Chart.js não está disponível. Verifique se o script foi carregado corretamente.');
        }
        
        // Aplicar o datepicker aos campos de data se o plugin estiver disponível
        if ($.fn.datepicker) {
            $('#data_inicio, #data_fim').datepicker({
                format: 'yyyy-mm-dd',
                language: 'pt-BR',
                autoclose: true,
                todayHighlight: true
            });
        }
        
        // Atualizar campos de data quando o tipo de relatório mudar
        $('#tipo').change(function() {
            var tipo = $(this).val();
            
            // Se for relatório de estoque atual, desabilitar datas
            if (tipo === 'estoque_atual') {
                $('#data_inicio, #data_fim').prop('disabled', true);
            } else {
                $('#data_inicio, #data_fim').prop('disabled', false);
            }
            
            // Ajustar período para vendas por período
            if (tipo === 'vendas_periodo') {
                // Definir para o primeiro dia do mês atual
                var dataInicio = new Date();
                dataInicio.setDate(1);
                
                // Definir para o último dia do mês atual
                var dataFim = new Date();
                var ultimoDia = new Date(dataFim.getFullYear(), dataFim.getMonth() + 1, 0).getDate();
                dataFim.setDate(ultimoDia);
                
                $('#data_inicio').val(formatarData(dataInicio));
                $('#data_fim').val(formatarData(dataFim));
            }
        });
        
        // Função para formatar data no padrão YYYY-MM-DD
        function formatarData(data) {
            var ano = data.getFullYear();
            var mes = (data.getMonth() + 1).toString().padStart(2, '0');
            var dia = data.getDate().toString().padStart(2, '0');
            return ano + '-' + mes + '-' + dia;
        }
        
        // Configurar a tabela de dados com DataTables
        if ($.fn.DataTable) {
            const dataTablesOptions = {
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
                },
                "responsive": true,
                "pageLength": 25,
                "autoWidth": false
            };
            
            // Inicializar tabelas dinâmicas
            if ($('#tabela-produtos-vendidos').length) {
                $('#tabela-produtos-vendidos').DataTable(dataTablesOptions);
            }
            
            if ($('#tabela-estoque-atual').length) {
                $('#tabela-estoque-atual').DataTable(dataTablesOptions);
            }
            
            if ($('#tabela-lucratividade').length) {
                $('#tabela-lucratividade').DataTable(dataTablesOptions);
            }
        }
        
        // Ajustar tamanho dos gráficos quando a janela for redimensionada
        $(window).resize(function() {
            if (typeof Chart !== 'undefined' && Chart.instances) {
                for (var id in Chart.instances) {
                    Chart.instances[id].resize();
                }
            }
        });
        
        // Corrigir possíveis erros no gráfico de vendas diárias
        if ($('#vendasChart').length) {
            var chartInstance = Chart.instances[$('#vendasChart')[0].id];
            if (chartInstance && chartInstance.options && chartInstance.options.scales && chartInstance.options.scales.yAxes) {
                for (var i = 0; i < chartInstance.options.scales.yAxes.length; i++) {
                    var yAxis = chartInstance.options.scales.yAxes[i];
                    if (yAxis.ticks && typeof yAxis.ticks.callback === 'function') {
                        // Verificar se a função de callback está funcionando corretamente
                        var testCallback = yAxis.ticks.callback(100, 0, []);
                        if (testCallback === undefined) {
                            // Se o callback não estiver funcionando, substituir por um mais simples
                            yAxis.ticks.callback = function(value) {
                                return 'R$ ' + value.toFixed(2);
                            };
                        }
                    }
                }
                
                // Atualizar o gráfico com as opções corrigidas
                chartInstance.update();
            }
        }
    });
</script>

<style>
    /* Estilo para os cards de resumo */
    .card-body {
        padding: 1.25rem;
    }
    
    .card-subtitle {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Ajustes para os gráficos */
    canvas {
        max-width: 100%;
    }
    
    /* Corrigir layout em telas pequenas */
    @media (max-width: 768px) {
        .btn-light {
            width: 100%;
            margin-top: 0.5rem;
        }
        
        .card-header .d-flex {
            flex-direction: column;
        }
    }
    
    /* Melhorar visualização das tabelas em dispositivos móveis */
    .table-responsive {
        margin-bottom: 1rem;
    }
    
    /* Estilo para os filtros */
    .form-select, .form-control {
        border-radius: 0.375rem;
    }
    
    /* Garantir que botões de exportação tenham aparência correta */
    .btn-light {
        background-color: #f8f9fa;
        border-color: #f8f9fa;
        color: #212529;
    }
    
    .btn-light:hover {
        background-color: #e2e6ea;
        border-color: #dae0e5;
    }
    
    /* Estilo para o título do relatório */
    .card-header h5 {
        margin-bottom: 0;
        font-weight: 500;
    }
    
    /* Cores para os diferentes tipos de alerta */
    .border-primary {
        border-color: #0d6efd !important;
    }
    
    .border-success {
        border-color: #198754 !important;
    }
    
    .border-warning {
        border-color: #ffc107 !important;
    }
    
    .border-danger {
        border-color: #dc3545 !important;
    }
    
    .border-info {
        border-color: #0dcaf0 !important;
    }
    
    /* Estilos para garantir que as tabelas não ultrapassem a largura da página */
    .dataTables_wrapper {
        width: 100%;
        overflow-x: auto;
    }
    
    table.dataTable {
        width: 100% !important;
    }
    
    /* Corrigir visualização para tabelas no modo responsivo */
    table.dataTable.dtr-inline.collapsed > tbody > tr > td:first-child:before,
    table.dataTable.dtr-inline.collapsed > tbody > tr > th:first-child:before {
        top: 50%;
        transform: translateY(-50%);
    }
</style>

<?php include 'footer.php'; ?>