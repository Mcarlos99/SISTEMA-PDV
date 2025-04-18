<?php
require_once 'config.php';
verificarLogin();

// Inicializar a classe Caixa
$caixa = new Caixa($pdo);

// Verificar se existe um caixa aberto
$caixa_aberto = $caixa->verificarCaixaAberto();

// Processar formulário de abertura de caixa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['abrir_caixa'])) {
    try {
        $valor_inicial = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_inicial']);
        $observacoes = $_POST['observacoes'] ?? '';
        
        $caixa_id = $caixa->abrir($valor_inicial, $observacoes);
        
        alerta('Caixa aberto com sucesso!', 'success');
        header('Location: caixa.php');
        exit;
    } catch (Exception $e) {
        alerta('Erro ao abrir caixa: ' . $e->getMessage(), 'danger');
    }
}

// Processar formulário de sangria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_sangria'])) {
    try {
        $valor = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_sangria']);
        $observacoes = $_POST['observacoes_sangria'] ?? '';
        
        $movimentacao_id = $caixa->registrarSangria($valor, $observacoes);
        
        alerta('Sangria registrada com sucesso!', 'success');
        header('Location: caixa.php');
        exit;
    } catch (Exception $e) {
        alerta('Erro ao registrar sangria: ' . $e->getMessage(), 'danger');
    }
}

// Processar formulário de suprimento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_suprimento'])) {
    try {
        $valor = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_suprimento']);
        $observacoes = $_POST['observacoes_suprimento'] ?? '';
        
        $movimentacao_id = $caixa->registrarSuprimento($valor, $observacoes);
        
        alerta('Suprimento registrado com sucesso!', 'success');
        header('Location: caixa.php');
        exit;
    } catch (Exception $e) {
        alerta('Erro ao registrar suprimento: ' . $e->getMessage(), 'danger');
    }
}

// Processar formulário de fechamento de caixa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fechar_caixa'])) {
    try {
        $valor_final = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_final']);
        $observacoes = $_POST['observacoes_fechamento'] ?? '';
        
        $resultado = $caixa->fechar($valor_final, $observacoes);
        
        $_SESSION['fechamento_caixa'] = $resultado;
        
        alerta('Caixa fechado com sucesso!', 'success');
        header('Location: fechamento_caixa.php');
        exit;
    } catch (Exception $e) {
        alerta('Erro ao fechar caixa: ' . $e->getMessage(), 'danger');
    }
}

// Obter a lista de movimentações se o caixa estiver aberto
$movimentacoes = [];
if ($caixa_aberto) {
    try {
        $movimentacoes = $caixa->listarMovimentacoes($caixa_aberto['id']);
    } catch (Exception $e) {
        alerta('Erro ao listar movimentações: ' . $e->getMessage(), 'warning');
    }
}

// Template da página
$titulo_pagina = 'Controle de Caixa';
include 'header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Controle de Caixa</h1>
    
    <?php if (!$caixa_aberto): ?>
    <!-- Caixa Fechado - Formulário de Abertura -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-cash-register me-2"></i> Abertura de Caixa</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="valor_inicial" class="form-label">Valor Inicial (Fundo de Caixa)</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="valor_inicial" name="valor_inicial" required
                                       value="0,00" data-mask-money
                                       title="Use vírgula para separar os centavos.">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="abrir_caixa" class="btn btn-primary">
                        <i class="fas fa-unlock-alt me-2"></i> Abrir Caixa
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Histórico de Caixas -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i> Histórico de Caixas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable" id="tabelaHistoricoCaixas">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Data/Hora Abertura</th>
                            <th>Data/Hora Fechamento</th>
                            <th>Usuário</th>
                            <th>Valor Inicial</th>
                            <th>Valor Final</th>
                            <th>Diferença</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $historico = $caixa->listarHistorico();
                            foreach ($historico as $c):
                                $diferenca = 0;
                                if ($c['status'] == 'fechado' && $c['valor_final'] !== null) {
                                    $valor_esperado = $c['valor_inicial'] + $c['valor_vendas'] + $c['valor_suprimentos'] - $c['valor_sangrias'];
                                    $diferenca = $c['valor_final'] - $valor_esperado;
                                }
                        ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><?php echo $c['data_abertura_formatada']; ?></td>
                            <td><?php echo $c['data_fechamento_formatada'] ?? 'Em aberto'; ?></td>
                            <td><?php echo $c['usuario_nome']; ?></td>
                            <td><?php echo formatarDinheiro($c['valor_inicial']); ?></td>
                            <td><?php echo $c['valor_final'] ? formatarDinheiro($c['valor_final']) : '-'; ?></td>
                            <td class="<?php echo $diferenca < 0 ? 'text-danger' : ($diferenca > 0 ? 'text-success' : ''); ?>">
                                <?php echo $diferenca != 0 ? formatarDinheiro($diferenca) : '-'; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $c['status'] == 'aberto' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo ucfirst($c['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="detalhe_caixa.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="9" class="text-center text-danger">Erro ao carregar histórico: ' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Caixa Aberto - Painel de Controle -->
    <div class="row">
        <div class="col-md-7">
            <!-- Informações do Caixa Atual -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cash-register me-2"></i> Caixa Aberto
                        <span class="badge bg-light text-dark float-end">
                            Aberto em: <?php echo date('d/m/Y H:i', strtotime($caixa_aberto['data_abertura'])); ?>
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Calcular totais
                    $total_vendas = 0;
                    $total_sangrias = 0;
                    $total_suprimentos = 0;
                    
                    foreach ($movimentacoes as $m) {
                        if ($m['tipo'] == 'venda') $total_vendas += $m['valor'];
                        if ($m['tipo'] == 'sangria') $total_sangrias += $m['valor'];
                        if ($m['tipo'] == 'suprimento') $total_suprimentos += $m['valor'];
                    }
                    
                    $saldo_atual = $caixa_aberto['valor_inicial'] + $total_vendas + $total_suprimentos - $total_sangrias;
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-light">
                                <div class="card-body py-3">
                                    <h6 class="card-title">Valor Inicial</h6>
                                    <h4><?php echo formatarDinheiro($caixa_aberto['valor_inicial']); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body py-3">
                                    <h6 class="card-title">Vendas</h6>
                                    <h4><?php echo formatarDinheiro($total_vendas); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body py-3">
                                    <h6 class="card-title">Sangrias</h6>
                                    <h4><?php echo formatarDinheiro($total_sangrias); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body py-3">
                                    <h6 class="card-title">Suprimentos</h6>
                                    <h4><?php echo formatarDinheiro($total_suprimentos); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6 offset-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body py-3 text-center">
                                    <h6 class="card-title">Saldo em Caixa (Esperado)</h6>
                                    <h3><?php echo formatarDinheiro($saldo_atual); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalFecharCaixa">
                            <i class="fas fa-lock me-2"></i> Fechar Caixa
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Movimentações do Caixa -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-exchange-alt me-2"></i> Movimentações do Caixa</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Hora</th>
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
                                    <td><?php echo date('H:i', strtotime($m['data_hora'])); ?></td>
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
                                                'dinheiro' => '<i class="fas fa-money-bill-wave text-success"></i> Dinheiro',
                                                'cartao_credito' => '<i class="far fa-credit-card text-primary"></i> Crédito',
                                                'cartao_debito' => '<i class="fas fa-credit-card text-info"></i> Débito',
                                                'pix' => '<i class="fas fa-qrcode text-primary"></i> PIX',
                                                'boleto' => '<i class="fas fa-file-invoice-dollar text-secondary"></i> Boleto'
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
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <!-- Sangria de Caixa -->
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-money-bill-wave me-2"></i> Registrar Sangria</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Use este formulário para registrar retiradas de dinheiro do caixa.</p>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="valor_sangria" class="form-label">Valor da Sangria</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="valor_sangria" name="valor_sangria" required
                                      data-mask-money>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes_sangria" class="form-label">Motivo da Sangria</label>
                            <textarea class="form-control" id="observacoes_sangria" name="observacoes_sangria" rows="2" required></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="registrar_sangria" class="btn btn-danger">
                                <i class="fas fa-check me-2"></i> Registrar Sangria
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Suprimento de Caixa -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-hand-holding-usd me-2"></i> Registrar Suprimento</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Use este formulário para registrar entradas adicionais de dinheiro no caixa.</p>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="valor_suprimento" class="form-label">Valor do Suprimento</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="valor_suprimento" name="valor_suprimento" required
                                      data-mask-money>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes_suprimento" class="form-label">Motivo do Suprimento</label>
                            <textarea class="form-control" id="observacoes_suprimento" name="observacoes_suprimento" rows="2" required></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="registrar_suprimento" class="btn btn-info">
                                <i class="fas fa-check me-2"></i> Registrar Suprimento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Fechar Caixa -->
    <div class="modal fade" id="modalFecharCaixa" tabindex="-1" aria-labelledby="modalFecharCaixaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalFecharCaixaLabel"><i class="fas fa-lock me-2"></i> Fechar Caixa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> Atenção: O fechamento de caixa encerra todas as operações deste caixa e não pode ser desfeito!
                    </div>
                    
                    <form method="post" action="" id="formFecharCaixa">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center py-2">
                                        <h6 class="card-title">Valor Inicial</h6>
                                        <h5><?php echo formatarDinheiro($caixa_aberto['valor_inicial']); ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center py-2">
                                        <h6 class="card-title">Movimentações</h6>
                                        <h5><?php echo formatarDinheiro($total_vendas + $total_suprimentos - $total_sangrias); ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center py-2">
                                        <h6 class="card-title">Valor Esperado</h6>
                                        <h5><?php echo formatarDinheiro($saldo_atual); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="valor_final" class="form-label">Valor em Caixa (Contagem Real)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="text" class="form-control" id="valor_final" name="valor_final" required
                                              value="<?php echo number_format($saldo_atual, 2, ',', '.'); ?>" data-mask-money>
                                    </div>
                                    <div class="form-text">Informe o valor real contado no fechamento do caixa.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="observacoes_fechamento" class="form-label">Observações do Fechamento</label>
                                    <textarea class="form-control" id="observacoes_fechamento" name="observacoes_fechamento" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="fechar_caixa" class="btn btn-danger">
                                <i class="fas fa-lock me-2"></i> Confirmar Fechamento de Caixa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    // Inicializar máscaras de moeda
    document.addEventListener('DOMContentLoaded', function() {
        const moneyInputs = document.querySelectorAll('[data-mask-money]');
        
        moneyInputs.forEach(function(input) {
            // Seleciona o conteúdo ao focar
            input.addEventListener('focus', function() {
                setTimeout(() => this.select(), 10);
            });
            
            // Trata a entrada do usuário sem formatação automática durante a digitação
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                
                // Remove qualquer caractere que não seja número ou vírgula
                value = value.replace(/[^\d,]/g, '');
                
                // Limita a uma única vírgula
                const parts = value.split(',');
                if (parts.length > 2) {
                    value = parts[0] + ',' + parts.slice(1).join('');
                }
                
                // Atualiza o valor sem formatação automática
                e.target.value = value;
            });
            
            // Aplica a formatação completa quando o campo perde o foco
            input.addEventListener('blur', function(e) {
                let value = e.target.value;
                
                // Garante que tenha um valor
                if (!value) {
                    value = '0,00';
                } 
                
                // Garante que tenha a vírgula e 2 casas decimais
                if (!value.includes(',')) {
                    value = value + ',00';
                } else {
                    const parts = value.split(',');
                    const decimal = parts[1] || '';
                    value = parts[0] + ',' + decimal.padEnd(2, '0').slice(0, 2);
                }
                
                // Adiciona os separadores de milhar
                const parts = value.split(',');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                value = parts.join(',');
                
                e.target.value = value;
            });
            
            // Aplica a formatação inicial, mas apenas se o valor for o padrão
            if (input.value === '0,00') {
                const event = new Event('blur', { bubbles: true });
                input.dispatchEvent(event);
            }
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Permite que o campo de valor inicial seja limpo ao receber foco
    const valorInicial = document.getElementById('valor_inicial');
    if (valorInicial) {
        valorInicial.addEventListener('focus', function() {
            if (this.value === '0,00') {
                this.value = '';
            }
        });
        
        // Ao perder o foco, se estiver vazio, restaura o valor padrão
        valorInicial.addEventListener('blur', function() {
            if (this.value === '') {
                this.value = '0,00';
                // Dispara o evento input para formatar corretamente
                const event = new Event('input', { bubbles: true });
                this.dispatchEvent(event);
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // Função para reinicializar a tabela de histórico após DataTables ser carregado
    function configurarTabelaHistorico() {
        // Aguarda até que DataTables esteja completamente carregado
        if (typeof $.fn.dataTable !== 'undefined') {
            // Espera um breve momento para garantir que o DataTables padrão já inicializou
            setTimeout(function() {
                // Se já for uma instância DataTable, destrói para reinicializar
                if ($.fn.dataTable.isDataTable('#tabelaHistoricoCaixas')) {
                    $('#tabelaHistoricoCaixas').DataTable().destroy();
                }
                
                // Reinicializa com ordenação específica
                $('#tabelaHistoricoCaixas').DataTable({
                    "order": [[ 0, "desc" ]], // Ordena pelo ID (primeira coluna) em ordem decrescente
                    // Mantém as outras configurações padrão do DataTables
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
                    }
                });
            }, 100); // Pequeno atraso para garantir que a inicialização padrão já ocorreu
        } else {
            // Se DataTables ainda não estiver carregado, tenta novamente em 100ms
            setTimeout(configurarTabelaHistorico, 100);
        }
    }
    
    // Inicia o processo de configuração
    configurarTabelaHistorico();
});
</script>

<?php include 'footer.php'; ?>