<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Processar ações
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$caixa_obj = new Caixa($pdo);

// Verificar se existe um caixa aberto
$caixa_aberto = $caixa_obj->verificarCaixaAberto();

// Processar abertura de caixa
if ($acao == 'abrir_caixa' && isset($_POST['valor_inicial'])) {
    try {
        $valor_inicial = floatval(str_replace(',', '.', $_POST['valor_inicial']));
        $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
        
        $caixa_id = $caixa_obj->abrir($valor_inicial, $observacoes);
        
        alerta('Caixa aberto com sucesso!', 'success');
        header('Location: caixa.php');
        exit;
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar fechamento de caixa
if ($acao == 'fechar_caixa' && isset($_POST['valor_final'])) {
    try {
        $valor_final = floatval(str_replace(',', '.', $_POST['valor_final']));
        $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
        
        $resultado = $caixa_obj->fechar($caixa_aberto['id'], $valor_final, $observacoes);
        
        $diferenca = $resultado['diferenca'];
        $tipo_alerta = 'success';
        
        if ($diferenca < 0) {
            $tipo_alerta = 'danger';
            $mensagem = 'Caixa fechado com diferença negativa de ' . formatarDinheiro(abs($diferenca)) . '!';
        } else if ($diferenca > 0) {
            $tipo_alerta = 'warning';
            $mensagem = 'Caixa fechado com diferença positiva de ' . formatarDinheiro($diferenca) . '!';
        } else {
            $mensagem = 'Caixa fechado sem diferenças!';
        }
        
        // Salvar os dados de fechamento na sessão
        $_SESSION['fechamento_caixa'] = [
            'caixa_id' => $caixa_aberto['id'],
            'valor_inicial' => $resultado['valor_inicial'],
            'valor_final' => $valor_final,
            'valor_vendas' => $resultado['valor_vendas'],
            'valor_sangrias' => $resultado['valor_sangrias'],
            'valor_suprimentos' => $resultado['valor_suprimentos'],
            'valor_esperado' => $resultado['valor_esperado'],
            'diferenca' => $diferenca
        ];
        
        alerta($mensagem, $tipo_alerta);
        
        // Redirecionar para a página de fechamento de caixa com o relatório para impressão
        header('Location: fechamento_caixa.php');
        exit;
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar sangria
if ($acao == 'sangria' && isset($_POST['valor_sangria'])) {
    try {
        $valor = floatval(str_replace(',', '.', $_POST['valor_sangria']));
        $observacoes = isset($_POST['observacoes_sangria']) ? $_POST['observacoes_sangria'] : '';
        
        $caixa_obj->registrarSangria($valor, $observacoes);
        
        alerta('Sangria registrada com sucesso!', 'success');
        header('Location: caixa.php');
        exit;
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar suprimento
if ($acao == 'suprimento' && isset($_POST['valor_suprimento'])) {
    try {
        $valor = floatval(str_replace(',', '.', $_POST['valor_suprimento']));
        $observacoes = isset($_POST['observacoes_suprimento']) ? $_POST['observacoes_suprimento'] : '';
        
        $caixa_obj->registrarSuprimento($valor, $observacoes);
        
        alerta('Suprimento registrado com sucesso!', 'success');
        header('Location: caixa.php');
        exit;
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Listar movimentações se tiver caixa aberto
$movimentacoes = [];
if ($caixa_aberto) {
    // Buscar todas as vendas do dia que não foram incluídas nas movimentações do caixa
    $stmt = $pdo->prepare("
    SELECT 
        v.id, 
        v.usuario_id, 
        v.data_venda, 
        v.valor_total, 
        v.forma_pagamento,
        v.observacoes,
        u.nome AS usuario_nome
    FROM vendas v
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    WHERE DATE(v.data_venda) = CURDATE() 
      AND v.status = 'finalizada'  -- Apenas vendas finalizadas, não canceladas
      AND NOT EXISTS (
          SELECT 1 FROM movimentacoes_caixa m 
          WHERE m.documento_id = v.id AND m.tipo = 'venda'
      )
");
    $stmt->execute();
    $vendas_nao_registradas = $stmt->fetchAll();

    
    
    // Remover movimentações de vendas que foram canceladas
    $stmt = $pdo->prepare("
        SELECT m.id
        FROM movimentacoes_caixa m
        LEFT JOIN vendas v ON m.documento_id = v.id AND m.tipo = 'venda'
        WHERE m.caixa_id = :caixa_id
          AND m.tipo = 'venda'
          AND (v.status = 'cancelada' OR v.id IS NULL)
    ");
    $stmt->bindParam(':caixa_id', $caixa_aberto['id'], PDO::PARAM_INT);
    $stmt->execute();
    $movs_para_remover = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Remover as movimentações de vendas canceladas
    foreach ($movs_para_remover as $mov_id) {
        $stmt = $pdo->prepare("DELETE FROM movimentacoes_caixa WHERE id = :id");
        $stmt->bindParam(':id', $mov_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    
    // Registrar as vendas encontradas nas movimentações do caixa
    foreach ($vendas_nao_registradas as $venda) {
        $dados = [
            'tipo' => 'venda',
            'valor' => $venda['valor_total'],
            'forma_pagamento' => $venda['forma_pagamento'],
            'documento_id' => $venda['id'],
            'observacoes' => "Venda #{$venda['id']} registrada automaticamente no caixa"
        ];
        
        // Usar a função da classe Caixa para registrar a movimentação
        try {
            $caixa_obj->adicionarMovimentacao($dados);
        } catch (Exception $e) {
            // Apenas registrar o erro, sem interromper a execução
            error_log("Erro ao adicionar venda #{$venda['id']} às movimentações do caixa: " . $e->getMessage());
        }
    }
    
    // Agora buscar todas as movimentações atualizadas
    $movimentacoes = $caixa_obj->listarMovimentacoes($caixa_aberto['id']);
}

// Template da página
$titulo_pagina = 'Controle de Caixa - Sistema PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-cash-register me-2 text-primary"></i>
                Controle de Caixa
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item active">Caixa</li>
                </ol>
            </nav>
        </div>
        
        <?php if ($caixa_aberto): ?>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <button type="button" class="btn btn-success mb-2 mb-sm-0" data-bs-toggle="modal" data-bs-target="#modalSuprimento">
                    <i class="fas fa-plus-circle me-1"></i>
                    <span class="d-none d-sm-inline">Registrar Suprimento</span>
                    <span class="d-inline d-sm-none">Suprimento</span>
                </button>
                <button type="button" class="btn btn-warning text-white mb-2 mb-sm-0" data-bs-toggle="modal" data-bs-target="#modalSangria">
                    <i class="fas fa-minus-circle me-1"></i>
                    <span class="d-none d-sm-inline">Registrar Sangria</span>
                    <span class="d-inline d-sm-none">Sangria</span>
                </button>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalFecharCaixa">
                    <i class="fas fa-door-closed me-1"></i>
                    <span class="d-none d-sm-inline">Fechar Caixa</span>
                    <span class="d-inline d-sm-none">Fechar</span>
                </button>
            </div>
        <?php else: ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAbrirCaixa">
                <i class="fas fa-door-open me-1"></i>
                Abrir Caixa
            </button>
        <?php endif; ?>
    </div>
    
    <?php if ($caixa_aberto): ?>
        <!-- Status do Caixa Atual -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Status do Caixa Atual
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Abertura</h6>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar-day me-1 text-primary"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($caixa_aberto['data_abertura'])); ?>
                                        </p>
                                        <h4 class="text-primary mb-0">
                                            <?php echo formatarDinheiro($caixa_aberto['valor_inicial']); ?>
                                        </h4>
                                        <small class="text-muted">Valor inicial</small>
                                    </div>
                                </div>
                            </div>
                            
                            <?php
                            // Calcular valores
                            $total_entradas = 0;
                            $total_saidas = 0;
                            
                            foreach ($movimentacoes as $mov) {
                                if ($mov['tipo'] == 'venda' || $mov['tipo'] == 'suprimento') {
                                    $total_entradas += $mov['valor'];
                                } else if ($mov['tipo'] == 'sangria') {
                                    $total_saidas += $mov['valor'];
                                }
                            }
                            
                            $saldo_atual = $caixa_aberto['valor_inicial'] + $total_entradas - $total_saidas;
                            ?>
                            
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Movimentações</h6>
                                        <div class="d-flex justify-content-around">
                                            <div>
                                                <p class="mb-1 text-success">
                                                    <i class="fas fa-arrow-up me-1"></i>
                                                    <?php echo formatarDinheiro($total_entradas); ?>
                                                </p>
                                                <small class="text-muted">Entradas</small>
                                            </div>
                                            <div>
                                                <p class="mb-1 text-danger">
                                                    <i class="fas fa-arrow-down me-1"></i>
                                                    <?php echo formatarDinheiro($total_saidas); ?>
                                                </p>
                                                <small class="text-muted">Saídas</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card h-100 bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Saldo Atual</h6>
                                        <h3 class="text-primary mb-0">
                                            <?php echo formatarDinheiro($saldo_atual); ?>
                                        </h3>
                                        <small class="text-muted">
                                            Valor inicial + Entradas - Saídas
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabela de Movimentações -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Movimentações do Caixa Atual
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0" id="tabelaMovimentacoes">
                        <thead>
                            <tr>
                                <th data-priority="1">ID</th>
                                <th data-priority="1">Tipo</th>
                                <th data-priority="2">Data/Hora</th>
                                <th data-priority="3">Usuário</th>
                                <th data-priority="1">Valor</th>
                                <th data-priority="2">Forma Pagto.</th>
                                <th data-priority="3">Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <?php 
                                // Definir classes e ícones de acordo com o tipo
                                $tipo_classe = '';
                                $tipo_icone = '';
                                $tipo_texto = ucfirst($mov['tipo']);
                                
                                switch ($mov['tipo']) {
                                    case 'venda':
                                        $tipo_classe = 'success';
                                        $tipo_icone = 'fa-shopping-cart';
                                        break;
                                    case 'sangria':
                                        $tipo_classe = 'danger';
                                        $tipo_icone = 'fa-minus-circle';
                                        break;
                                    case 'suprimento':
                                        $tipo_classe = 'primary';
                                        $tipo_icone = 'fa-plus-circle';
                                        break;
                                }
                                ?>
                                <tr>
                                    <td><?php echo $mov['id']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $tipo_classe; ?>">
                                            <i class="fas <?php echo $tipo_icone; ?> me-1"></i>
                                            <?php echo $tipo_texto; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $mov['data_formatada']; ?></td>
                                    <td><?php echo esc($mov['usuario_nome']); ?></td>
                                    <td>
                                        <span class="fw-bold text-<?php echo $tipo_classe; ?>">
                                            <?php echo formatarDinheiro($mov['valor']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($mov['forma_pagamento']): ?>
                                            <?php 
                                            $formas = [
                                                'dinheiro' => '<i class="fas fa-money-bill-wave text-success me-1"></i> Dinheiro',
                                                'cartao_credito' => '<i class="fas fa-credit-card text-primary me-1"></i> Crédito',
                                                'cartao_debito' => '<i class="fas fa-credit-card text-info me-1"></i> Débito',
                                                'pix' => '<i class="fas fa-qrcode text-warning me-1"></i> PIX',
                                                'boleto' => '<i class="fas fa-file-invoice-dollar text-secondary me-1"></i> Boleto'
                                            ];
                                            echo $formas[$mov['forma_pagamento']] ?? ucfirst(str_replace('_', ' ', $mov['forma_pagamento']));
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc($mov['observacoes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($movimentacoes)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhuma movimentação registrada neste caixa.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Mensagem de caixa fechado -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-door-closed fa-5x text-muted mb-4"></i>
                        <h4>O caixa está fechado no momento</h4>
                        <p class="text-muted">Para realizar operações financeiras, é necessário abrir o caixa.</p>
                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalAbrirCaixa">
                            <i class="fas fa-door-open me-1"></i>
                            Abrir Caixa Agora
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Histórico de Caixas -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Histórico de Caixas
                </h5>
            </div>
            <div class="card-body p-0">
                <?php $historico = $caixa_obj->listarHistorico(10); ?>
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0" id="tabelaHistorico">
                        <thead>
                            <tr>
                                <th data-priority="1">ID</th>
                                <th data-priority="1">Status</th>
                                <th data-priority="2">Abertura</th>
                                <th data-priority="3">Fechamento</th>
                                <th data-priority="2">Operador</th>
                                <th data-priority="1">Valor Inicial</th>
                                <th data-priority="1">Valor Final</th>
                                <th data-priority="3">Diferença</th>
                                <th data-priority="3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico as $h): ?>
                                <?php 
                                $diferenca = 0;
                                if ($h['status'] == 'fechado' && $h['valor_final'] != null) {
                                    $diferenca = $h['valor_final'] - ($h['valor_inicial'] + ($h['valor_vendas'] ?: 0) + ($h['valor_suprimentos'] ?: 0) - ($h['valor_sangrias'] ?: 0));
                                }
                                
                                $diferenca_class = 'secondary';
                                if ($diferenca > 0) {
                                    $diferenca_class = 'success';
                                } else if ($diferenca < 0) {
                                    $diferenca_class = 'danger';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $h['id']; ?></td>
                                    <td>
                                        <?php if ($h['status'] == 'aberto'): ?>
                                            <span class="badge bg-success">Aberto</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Fechado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $h['data_abertura_formatada']; ?></td>
                                    <td>
                                        <?php if ($h['data_fechamento']): ?>
                                            <?php echo $h['data_fechamento_formatada']; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc($h['usuario_nome']); ?></td>
                                    <td><?php echo formatarDinheiro($h['valor_inicial']); ?></td>
                                    <td>
                                        <?php if ($h['valor_final'] !== null): ?>
                                            <?php echo formatarDinheiro($h['valor_final']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($h['status'] == 'fechado'): ?>
                                            <span class="text-<?php echo $diferenca_class; ?>">
                                                <?php echo formatarDinheiro($diferenca); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
    <?php if ($h['status'] == 'fechado'): ?>
        <a href="relatorios.php?tipo=caixa&id=<?php echo $h['id']; ?>" class="btn btn-sm btn-info text-white" style="display: inline-block !important; background-color: #0dcaf0 !important;" data-bs-toggle="tooltip" title="Ver Relatório">
            <i class="fas fa-file-alt"></i>
        </a>
    <?php else: ?>
        <button class="btn btn-sm btn-secondary" style="display: inline-block !important;" disabled>
            <i class="fas fa-file-alt"></i>
        </button>
    <?php endif; ?>
</td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($historico)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhum histórico de caixas encontrado.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Abrir Caixa -->
<div class="modal fade" id="modalAbrirCaixa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-door-open me-2"></i>
                    Abrir Caixa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="caixa.php?acao=abrir_caixa" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="valor_inicial" class="form-label fw-bold">Valor Inicial (R$):</label>
                        <input type="number" class="form-control form-control-lg" id="valor_inicial" name="valor_inicial" step="0.01" min="0" value="0.00" required>
                        <div class="form-text">Informe o valor em dinheiro com o qual o caixa será iniciado.</div>
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
                        <i class="fas fa-door-open me-1"></i>
                        Abrir Caixa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Fechar Caixa -->
<?php if ($caixa_aberto): ?>
<div class="modal fade" id="modalFecharCaixa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-door-closed me-2"></i>
                    Fechar Caixa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="caixa.php?acao=fechar_caixa" method="post">
                <div class="modal-body">
                    <?php
                    // Calcular valores para exibição
                    $valor_inicial = $caixa_aberto['valor_inicial'];
                    $total_vendas = 0;
                    $total_suprimentos = 0;
                    $total_sangrias = 0;
                    
                    foreach ($movimentacoes as $mov) {
                        if ($mov['tipo'] == 'venda') {
                            $total_vendas += $mov['valor'];
                        } else if ($mov['tipo'] == 'suprimento') {
                            $total_suprimentos += $mov['valor'];
                        } else if ($mov['tipo'] == 'sangria') {
                            $total_sangrias += $mov['valor'];
                        }
                    }
                    
                    $saldo_calculado = $valor_inicial + $total_vendas + $total_suprimentos - $total_sangrias;
                    ?>
                    
                    <div class="mb-4">
                        <h6 class="mb-2">Resumo do Caixa</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">Valor Inicial: <strong><?php echo formatarDinheiro($valor_inicial); ?></strong></p>
                                <p class="mb-1">+ Vendas: <strong><?php echo formatarDinheiro($total_vendas); ?></strong></p>
                                <p class="mb-1">+ Suprimentos: <strong><?php echo formatarDinheiro($total_suprimentos); ?></strong></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">- Sangrias: <strong><?php echo formatarDinheiro($total_sangrias); ?></strong></p>
                                <p class="mb-1 fw-bold">= Saldo Esperado: <span class="text-primary"><?php echo formatarDinheiro($saldo_calculado); ?></span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="valor_final" class="form-label fw-bold">Valor Final em Caixa (R$):</label>
                        <input type="number" class="form-control form-control-lg" id="valor_final" name="valor_final" step="0.01" min="0" value="<?php echo number_format($saldo_calculado, 2, '.', ''); ?>" required>
                        <div class="form-text">Informe o valor real contado no fechamento do caixa.</div>
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
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-door-closed me-1"></i>
                        Fechar Caixa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sangria -->
<div class="modal fade" id="modalSangria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-minus-circle me-2"></i>
                    Registrar Sangria
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="caixa.php?acao=sangria" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="valor_sangria" class="form-label fw-bold">Valor da Sangria (R$):</label>
                        <input type="number" class="form-control form-control-lg" id="valor_sangria" name="valor_sangria" step="0.01" min="0.01" required>
                        <div class="form-text">Informe o valor que será retirado do caixa.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes_sangria" class="form-label">Motivo da Sangria:</label>
                        <textarea class="form-control" id="observacoes_sangria" name="observacoes_sangria" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning text-white">
                        <i class="fas fa-minus-circle me-1"></i>
                        Registrar Sangria
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Suprimento -->
<div class="modal fade" id="modalSuprimento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Registrar Suprimento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="caixa.php?acao=suprimento" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="valor_suprimento" class="form-label fw-bold">Valor do Suprimento (R$):</label>
                        <input type="number" class="form-control form-control-lg" id="valor_suprimento" name="valor_suprimento" step="0.01" min="0.01" required>
                        <div class="form-text">Informe o valor que será adicionado ao caixa.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes_suprimento" class="form-label">Motivo do Suprimento:</label>
                        <textarea class="form-control" id="observacoes_suprimento" name="observacoes_suprimento" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus-circle me-1"></i>
                        Registrar Suprimento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    $(document).ready(function() {
        // Inicializa DataTables com responsividade para tabela de movimentações
        $('#tabelaMovimentacoes').DataTable({
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
                { responsivePriority: 1, targets: [0, 1, 4] }, // Prioridade alta 
                { responsivePriority: 2, targets: [2, 5] },    // Prioridade média
                { responsivePriority: 3, targets: [3, 6] }     // Prioridade baixa
            ]
        });
        
        // Inicializa DataTables com responsividade para tabela de histórico
        $('#tabelaHistorico').DataTable({
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
                { responsivePriority: 1, targets: [0, 1, 5, 6] }, // Prioridade alta 
                { responsivePriority: 2, targets: [2, 4] },       // Prioridade média
                { responsivePriority: 3, targets: [3, 7, 8] }     // Prioridade baixa
            ]
        });
        
        // Formatação de campos de valores monetários
        $('input[type="number"]').on('focus', function() {
            // Armazena o valor original ao focar
            $(this).data('original', $(this).val());
        });

        $('input[type="number"]').on('blur', function() {
            // Formata ao sair do campo
            if ($(this).val() === '') {
                $(this).val($(this).data('original') || 0);
            }
        });
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Alerta de saldo insuficiente na sangria
        $('#valor_sangria').on('input', function() {
            var valorSangria = parseFloat($(this).val()) || 0;
            var saldoAtual = <?php echo isset($saldo_atual) ? $saldo_atual : 0; ?>;
            
            if (valorSangria > saldoAtual) {
                if (!$('#alerta-saldo').length) {
                    $('<div id="alerta-saldo" class="alert alert-danger mt-2">O valor da sangria não pode ser maior que o saldo atual (<?php echo isset($saldo_atual) ? formatarDinheiro($saldo_atual) : "R$ 0,00"; ?>).</div>').insertAfter($(this).parent());
                }
            } else {
                $('#alerta-saldo').remove();
            }
        });
        
        // Cálculo automático da diferença no fechamento
        $('#valor_final').on('input', function() {
            var valorFinal = parseFloat($(this).val()) || 0;
            var saldoCalculado = <?php echo isset($saldo_calculado) ? $saldo_calculado : 0; ?>;
            var diferenca = valorFinal - saldoCalculado;
            
            var mensagem = '';
            var classe = 'alert-info';
            
            if (diferenca > 0) {
                mensagem = 'O caixa terá uma sobra de ' + formatarDinheiro(diferenca);
                classe = 'alert-warning';
            } else if (diferenca < 0) {
                mensagem = 'O caixa terá uma falta de ' + formatarDinheiro(Math.abs(diferenca));
                classe = 'alert-danger';
            } else {
                mensagem = 'O caixa está exato, sem diferenças.';
                classe = 'alert-success';
            }
            
            if (!$('#alerta-diferenca').length) {
                $('<div id="alerta-diferenca" class="alert mt-2">' + mensagem + '</div>').insertAfter($(this).parent());
                $('#alerta-diferenca').addClass(classe);
            } else {
                $('#alerta-diferenca').text(mensagem).removeClass('alert-success alert-warning alert-danger alert-info').addClass(classe);
            }
        });
        
        // Função para formatar valores monetários
        function formatarDinheiro(valor) {
            return 'R$ ' + valor.toFixed(2).replace('.', ',');
        }
    });
</script>

<?php include 'footer.php'; ?>