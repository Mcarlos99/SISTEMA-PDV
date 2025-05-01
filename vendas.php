<?php
/**
 * EXTREME PDV - Gerenciamento de Vendas
 * 
 * Este arquivo gerencia a visualização e manipulação de vendas no sistema
 */

require_once 'config.php';
verificarLogin();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Inicializar objetos
$venda_obj = new Venda($pdo);
$caixa_obj = new Caixa($pdo);
$cliente_obj = new Cliente($pdo);

// Verificar se existe um caixa aberto
$caixa_aberto = $caixa_obj->verificarCaixaAberto();

// Processar ações
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// Sistema de permissões simplificado
$usuario_permissoes = $_SESSION['usuario_permissoes'] ?? [];
$pode_criar = true;   // Todos podem criar vendas
$pode_ver = true;     // Todos podem ver detalhes
$pode_cancelar = true;  // Todos podem cancelar vendas
$pode_relatorios = true; // Todos podem gerar relatórios

// Verificação específica para administrador e gerente - usando o mesmo formato do header.php
$pode_editar = in_array($_SESSION['usuario_nivel'], ['admin', 'gerente']);

// Função auxiliar para verificar permissões
if (!function_exists('verificarPermissao')) {
    function verificarPermissao($permissao, $usuario_permissoes = []) {
        // Se não houver controle de permissões, permite tudo
        if (empty($usuario_permissoes)) {
            return true;
        }
        
        // Verifica se o usuário tem a permissão específica
        return in_array($permissao, $usuario_permissoes);
    }
}

// Cancelar venda
if ($acao == 'cancelar' && isset($_POST['id'])) {
    if ($pode_cancelar === false) {
        alerta('Você não tem permissão para cancelar vendas!', 'danger');
        header('Location: vendas.php');
        exit;
    }
    
    $id = intval($_POST['id']);
    $motivo = $_POST['motivo'] ?? '';
    
    try {
        if (empty($motivo)) {
            throw new Exception('Informe o motivo do cancelamento!');
        }
        
        if (!$caixa_aberto) {
            throw new Exception('É necessário que um caixa esteja aberto para cancelar uma venda!');
        }
        
        $resultado = $venda_obj->cancelar($id, $motivo);
        
        if ($resultado) {
            // Registrar log de atividade
            if (function_exists('registrarLog')) {
                registrarLog('Cancelou a venda #' . $id . '. Motivo: ' . $motivo);
            }
            
            alerta('Venda #' . $id . ' cancelada com sucesso! Os produtos foram devolvidos ao estoque.', 'success');
        } else {
            throw new Exception('Erro ao cancelar venda!');
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: vendas.php');
    exit;
}

// // Editar venda
// if ($acao == 'editar' && isset($_POST['id'])) {
//     if (!in_array($_SESSION['usuario_nivel'], ['admin', 'gerente'])) {
//         alerta('Você não tem permissão para editar vendas!', 'danger');
//         header('Location: vendas.php');
//         exit;
//     }
    
//     $id = intval($_POST['id']);
//     $forma_pagamento = $_POST['forma_pagamento'] ?? '';
//     $observacoes = $_POST['observacoes'] ?? '';
//     $desconto = isset($_POST['desconto']) ? floatval(str_replace(',', '.', $_POST['desconto'])) : 0;
    
//     try {
//      //   if (!$caixa_aberto) {
//     //        throw new Exception('É necessário que um caixa esteja aberto para editar uma venda!');
//     //    }
        
//         // Obter venda atual para verificar se houve mudança
//         $venda_atual = $venda_obj->buscarPorId($id);
        
//         if (!$venda_atual) {
//             throw new Exception('Venda não encontrada!');
//         }
        
//         if ($venda_atual['status'] != 'finalizada') {
//             throw new Exception('Apenas vendas finalizadas podem ser editadas!');
//         }
        
//         // Editar a venda no banco de dados
//         $sql = "UPDATE vendas SET forma_pagamento = ?, observacoes = ?, desconto = ? WHERE id = ?";
//         $stmt = $pdo->prepare($sql);
//         $resultado = $stmt->execute([$forma_pagamento, $observacoes, $desconto, $id]);
        
//         if ($resultado) {
//             // Registrar histórico
//             $sql = "
//                 INSERT INTO venda_historico (
//                     venda_id,
//                     tipo,
//                     descricao,
//                     usuario_id,
//                     data_registro
//                 ) VALUES (?, ?, ?, ?, NOW())
//             ";
            
//             $descricao = 'Venda editada por ' . $_SESSION['usuario_nome'] . 
//                         '. Alterações: Forma de pagamento: ' . 
//                         $venda_atual['forma_pagamento'] . ' -> ' . $forma_pagamento . 
//                         ', Desconto: ' . formatarDinheiro($venda_atual['desconto']) . 
//                         ' -> ' . formatarDinheiro($desconto);
            
//             $stmt = $pdo->prepare($sql);
//             $stmt->execute([
//                 $id,
//                 'edicao',
//                 $descricao,
//                 $_SESSION['usuario_id']
//             ]);
            
//             // Registrar log de atividade
//             if (function_exists('registrarLog')) {
//                 registrarLog('Editou a venda #' . $id . ' (Forma pagto: ' . 
//                             $venda_atual['forma_pagamento'] . ' -> ' . $forma_pagamento . 
//                             ', Desconto: ' . formatarDinheiro($venda_atual['desconto']) . 
//                             ' -> ' . formatarDinheiro($desconto) . ')');
//             }
            
//             alerta('Venda #' . $id . ' editada com sucesso!', 'success');
//         } else {
//             throw new Exception('Erro ao editar venda!');
//         }
//     } catch (Exception $e) {
//         alerta($e->getMessage(), 'danger');
//     }
    
//     // Redirecionar para evitar reenvio do formulário
//     header('Location: vendas.php?id=' . $id);
//     exit;
// }

// Editar venda
if ($acao == 'editar' && isset($_POST['id'])) {
    if (!in_array($_SESSION['usuario_nivel'], ['admin', 'gerente'])) {
        alerta('Você não tem permissão para editar vendas!', 'danger');
        header('Location: vendas.php');
        exit;
    }
    
    $id = intval($_POST['id']);
    $forma_pagamento = $_POST['forma_pagamento'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    $desconto = isset($_POST['desconto']) ? floatval(str_replace(',', '.', str_replace('.', '', $_POST['desconto']))) : 0;
    
    try {
        // Obter venda atual para verificar se houve mudança
        $venda_atual = $venda_obj->buscarPorId($id);
        
        if (!$venda_atual) {
            throw new Exception('Venda não encontrada!');
        }
        
        if ($venda_atual['status'] != 'finalizada') {
            throw new Exception('Apenas vendas finalizadas podem ser editadas!');
        }
        
        // Calcular o valor total com base nos itens
        $itens = $venda_obj->buscarItens($id);
        $subtotal = 0;
        
        foreach ($itens as $item) {
            $subtotal += $item['quantidade'] * $item['preco_unitario'];
        }
        
        // O valor total é o subtotal menos o desconto
        $valor_total = $subtotal - $desconto;
        
        // Prevenir valor total negativo
        if ($valor_total < 0) {
            throw new Exception('O desconto não pode ser maior que o subtotal da venda!');
        }
        
        // Editar a venda no banco de dados, incluindo o valor_total
        $sql = "UPDATE vendas SET forma_pagamento = ?, observacoes = ?, desconto = ?, valor_total = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([$forma_pagamento, $observacoes, $desconto, $valor_total, $id]);
        
        if ($resultado) {
            // Registrar log de atividade (se existir essa função)
            if (function_exists('registrarLog')) {
                registrarLog('Editou a venda #' . $id . ' (Forma pagto: ' . 
                            $venda_atual['forma_pagamento'] . ' -> ' . $forma_pagamento . 
                            ', Desconto: ' . formatarDinheiro($venda_atual['desconto']) . 
                            ' -> ' . formatarDinheiro($desconto) . 
                            ', Valor total: ' . formatarDinheiro($venda_atual['valor_total']) . 
                            ' -> ' . formatarDinheiro($valor_total) . ')');
            }
            
            alerta('Venda #' . $id . ' editada com sucesso!', 'success');
        } else {
            throw new Exception('Erro ao editar venda!');
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: vendas.php?id=' . $id);
    exit;
}

// Exportar relatório em CSV
if ($acao == 'exportar_csv' && isset($_POST['data_inicio'], $_POST['data_fim'])) {
    if ($pode_relatorios === false) {
        alerta('Você não tem permissão para gerar relatórios!', 'danger');
        header('Location: vendas.php');
        exit;
    }
    
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $filtro_status = $_POST['filtro_status'] ?? 'todos';
    
    try {
        // Obter dados para o relatório
        $relatorio = $venda_obj->gerarRelatorio($data_inicio, $data_fim, $filtro_status);
        
        // Se o método não existir, criar um relatório básico
        if (!is_array($relatorio)) {
            // Buscar todas as vendas
            $vendas = $venda_obj->listar();
            $relatorio = [];
            
            // Filtrar por data e status
            foreach ($vendas as $venda) {
                if (!isset($venda['data_venda'])) continue;
                
                $data_venda = date('Y-m-d', strtotime($venda['data_venda']));
                
                if ($data_venda >= $data_inicio && $data_venda <= $data_fim) {
                    if ($filtro_status == 'todos' || $venda['status'] == $filtro_status) {
                        $relatorio[] = $venda;
                    }
                }
            }
        }
        
        // Configurar cabeçalhos para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio_vendas_' . date('Y-m-d') . '.csv');
        
        // Criar arquivo CSV
        $output = fopen('php://output', 'w');
        
        // Adicionar BOM (Byte Order Mark) para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalhos
        fputcsv($output, [
            'ID', 'Data/Hora', 'Cliente', 'Vendedor', 'Valor Total', 
            'Desconto', 'Forma Pagamento', 'Status', 'Observações'
        ]);
        
        // Dados
        foreach ($relatorio as $linha) {
            // Verificar se os campos existem
            $id = $linha['id'] ?? '';
            $data = isset($linha['data_formatada']) ? $linha['data_formatada'] : 
                 (isset($linha['data_venda']) ? date('d/m/Y H:i', strtotime($linha['data_venda'])) : '');
            $cliente = isset($linha['cliente_nome']) ? $linha['cliente_nome'] : 'Cliente não identificado';
            $vendedor = $linha['usuario_nome'] ?? 'Não informado';
            $valor = isset($linha['valor_total']) ? number_format($linha['valor_total'], 2, ',', '.') : '0,00';
            $desconto = isset($linha['desconto']) ? number_format($linha['desconto'], 2, ',', '.') : '0,00';
            $pagamento = isset($linha['forma_pagamento']) ? ucfirst(str_replace('_', ' ', $linha['forma_pagamento'])) : '';
            $status = isset($linha['status']) ? ucfirst($linha['status']) : '';
            $obs = $linha['observacoes'] ?? '';
            
            fputcsv($output, [
                $id, $data, $cliente, $vendedor, $valor, $desconto, $pagamento, $status, $obs
            ]);
        }
        
        fclose($output);
        exit;
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
        header('Location: vendas.php');
        exit;
    }
}
// 2
// Verificar se está visualizando uma venda específica
$venda_detalhes = null;
$itens_venda = [];
$historico_venda = [];

if (isset($_GET['id'])) {
    if ($pode_ver === false) {
        alerta('Você não tem permissão para visualizar detalhes das vendas!', 'danger');
        header('Location: vendas.php');
        exit;
    }
    
    $id = intval($_GET['id']);
    $venda_detalhes = $venda_obj->buscarPorId($id);
    
    if ($venda_detalhes) {
        // Buscar itens da venda
        $itens_venda = $venda_obj->buscarItens($id);
        
        // Se o método não existir, criar um array vazio
        if (!is_array($itens_venda)) {
            $itens_venda = [];
        }
        
        // Buscar histórico de atividades desta venda
        if (method_exists($venda_obj, 'buscarHistorico')) {
            $historico_venda = $venda_obj->buscarHistorico($id);
        } else {
            $historico_venda = [];
        }
    } else {
        alerta('Venda não encontrada!', 'danger');
        header('Location: vendas.php');
        exit;
    }
}

// Configurações para paginação
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$registros_por_pagina = isset($_GET['por_pagina']) ? intval($_GET['por_pagina']) : 25;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Filtros de pesquisa
$filtros = [];
if (isset($_GET['busca'])) {
    $busca = trim($_GET['busca']);
    if (!empty($busca)) {
        $filtros['busca'] = $busca;
    }
}

if (isset($_GET['cliente_id']) && intval($_GET['cliente_id']) > 0) {
    $filtros['cliente_id'] = intval($_GET['cliente_id']);
}

if (isset($_GET['status']) && in_array($_GET['status'], ['finalizada', 'cancelada'])) {
    $filtros['status'] = $_GET['status'];
}

if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filtros['data_inicio'] = $_GET['data_inicio'];
}

if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filtros['data_fim'] = $_GET['data_fim'];
}

if (isset($_GET['forma_pagamento']) && !empty($_GET['forma_pagamento'])) {
    $filtros['forma_pagamento'] = $_GET['forma_pagamento'];
}

// Listar vendas com filtros
$vendas = [];
$total_registros = 0;

if (!$venda_detalhes) {
    // Adaptar para usar o método listar existente
    $todos_vendas = $venda_obj->listar();
    
    // Verificar se é um array válido
    if (!is_array($todos_vendas)) {
        $todos_vendas = [];
    }
    
    // Aplicar filtros manualmente
    if (!empty($filtros)) {
        $vendas_filtradas = [];
        
        foreach ($todos_vendas as $v) {
            if (!is_array($v)) {
                continue;
            }
            
            $incluir = true;
            
            // Filtro por busca geral
            if (isset($filtros['busca']) && !empty($filtros['busca'])) {
                $busca = strtolower($filtros['busca']);
                $encontrado = false;
                
                // Verificar nos campos principais
                if (
                    (isset($v['id']) && stripos($v['id'], $busca) !== false) || 
                    (isset($v['cliente_nome']) && stripos($v['cliente_nome'], $busca) !== false) ||
                    (isset($v['valor_total']) && stripos((string)$v['valor_total'], $busca) !== false)
                ) {
                    $encontrado = true;
                }
                
                if (!$encontrado) {
                    $incluir = false;
                }
            }
            
            // Filtro por cliente
            if (isset($filtros['cliente_id']) && !empty($filtros['cliente_id'])) {
                if (!isset($v['cliente_id']) || $v['cliente_id'] != $filtros['cliente_id']) {
                    $incluir = false;
                }
            }
            
            // Filtro por status
            if (isset($filtros['status']) && !empty($filtros['status'])) {
                if (!isset($v['status']) || $v['status'] != $filtros['status']) {
                    $incluir = false;
                }
            }
            
            // Filtro por data inicial
            if (isset($filtros['data_inicio']) && !empty($filtros['data_inicio'])) {
                if (!isset($v['data_venda'])) {
                    $incluir = false;
                } else {
                    $data_venda = date('Y-m-d', strtotime($v['data_venda']));
                    if ($data_venda < $filtros['data_inicio']) {
                        $incluir = false;
                    }
                }
            }
            
            // Filtro por data final
            if (isset($filtros['data_fim']) && !empty($filtros['data_fim'])) {
                if (!isset($v['data_venda'])) {
                    $incluir = false;
                } else {
                    $data_venda = date('Y-m-d', strtotime($v['data_venda']));
                    if ($data_venda > $filtros['data_fim']) {
                        $incluir = false;
                    }
                }
            }
            
            // Filtro por forma de pagamento
            if (isset($filtros['forma_pagamento']) && !empty($filtros['forma_pagamento'])) {
                if (!isset($v['forma_pagamento']) || $v['forma_pagamento'] != $filtros['forma_pagamento']) {
                    $incluir = false;
                }
            }
            
            // Adicionar à lista filtrada se passar em todos os filtros
            if ($incluir) {
                $vendas_filtradas[] = $v;
            }
        }
        
        $vendas = $vendas_filtradas;
    } else {
        $vendas = $todos_vendas;
    }
    
    $total_registros = count($vendas);
    
    // Aplicar paginação manualmente
    $vendas = array_slice($vendas, $offset, $registros_por_pagina);
}

// Calcular total de páginas
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Listar formas de pagamento disponíveis
$formas_pagamento = [
    'dinheiro' => 'Dinheiro',
    'cartao_credito' => 'Cartão de Crédito',
    'cartao_debito' => 'Cartão de Débito',
    'pix' => 'PIX',
    'boleto' => 'Boleto',
    'transferencia' => 'Transferência',
    'cheque' => 'Cheque'
];

// Template da página
$titulo_pagina = 'Gerenciamento de Vendas - EXTREME PDV';
include 'header.php';
?>
<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-shopping-cart me-2 text-primary"></i>
                <?php echo $venda_detalhes ? 'Detalhes da Venda #' . $venda_detalhes['id'] : 'Gerenciamento de Vendas'; ?>
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <?php if ($venda_detalhes): ?>
                        <li class="breadcrumb-item"><a href="vendas.php">Vendas</a></li>
                        <li class="breadcrumb-item active">Venda #<?php echo $venda_detalhes['id']; ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active">Vendas</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
        
        <div class="d-flex flex-column flex-sm-row gap-2">
            <?php if (!$venda_detalhes && $caixa_aberto && $pode_criar): ?>
                <a href="pdv.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i>
                    <span class="d-none d-sm-inline">Nova Venda</span>
                    <span class="d-inline d-sm-none">Nova</span>
                </a>
            <?php endif; ?>
            
            <?php if (!$venda_detalhes && $pode_relatorios): ?>
                <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalRelatorio">
                    <i class="fas fa-chart-bar me-1"></i>
                    <span class="d-none d-sm-inline">Relatórios</span>
                    <span class="d-inline d-sm-none">Relatórios</span>
                </button>
            <?php endif; ?>
            
            <?php if ($venda_detalhes): ?>
                <a href="comprovante.php?id=<?php echo $venda_detalhes['id']; ?>" target="_blank" class="btn btn-info text-white">
                    <i class="fas fa-print me-1"></i>
                    <span class="d-none d-sm-inline">Imprimir Comprovante</span>
                    <span class="d-inline d-sm-none">Imprimir</span>
                </a>
                
                <a href="vendas.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">Voltar</span>
                    <span class="d-inline d-sm-none">Voltar</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <!-- 3 -->
    <?php if ($venda_detalhes): ?>
        <!-- Detalhes da Venda -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informações da Venda
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Informações Gerais</h6>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar-alt text-primary me-1"></i>
                                            <strong>Data/Hora:</strong> 
                                            <?php 
                                            if (isset($venda_detalhes['data_formatada'])) {
                                                echo $venda_detalhes['data_formatada'];
                                            } elseif (isset($venda_detalhes['data_venda'])) {
                                                echo date('d/m/Y H:i', strtotime($venda_detalhes['data_venda']));
                                            } else {
                                                echo 'Data não disponível';
                                            }
                                            ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-user text-primary me-1"></i>
                                            <strong>Cliente:</strong> 
                                            <?php if (isset($venda_detalhes['cliente_id']) && $venda_detalhes['cliente_id']): ?>
                                                <a href="clientes.php?id=<?php echo $venda_detalhes['cliente_id']; ?>" class="text-decoration-none">
                                                    <?php echo esc($venda_detalhes['cliente_nome'] ?? 'Cliente não identificado'); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Cliente não identificado</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-user-circle text-primary me-1"></i>
                                            <strong>Vendedor:</strong> 
                                            <?php echo esc($venda_detalhes['usuario_nome'] ?? 'Não informado'); ?>
                                        </p>
                                        <?php if (isset($venda_detalhes['comanda_id'])): ?>
                                            <p class="mb-0">
                                                <i class="fas fa-clipboard-list text-primary me-1"></i>
                                                <strong>Comanda:</strong>
                                                <a href="comandas.php?id=<?php echo $venda_detalhes['comanda_id']; ?>" class="text-decoration-none">
                                                    #<?php echo $venda_detalhes['comanda_id']; ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Status da Venda</h6>
                                        <?php if (isset($venda_detalhes['status']) && $venda_detalhes['status'] == 'finalizada'): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge bg-success p-2 me-2">
                                                    <i class="fas fa-check-circle fa-fw fa-lg"></i>
                                                </span>
                                                <h5 class="mb-0">Venda Finalizada</h5>
                                            </div>
                                            <p class="text-muted mb-1">
                                                Venda realizada com sucesso e registrada no sistema.
                                            </p>
                                        <?php elseif (isset($venda_detalhes['status']) && $venda_detalhes['status'] == 'cancelada'): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge bg-danger p-2 me-2">
                                                    <i class="fas fa-times-circle fa-fw fa-lg"></i>
                                                </span>
                                                <h5 class="mb-0">Venda Cancelada</h5>
                                            </div>
                                            <p class="text-muted mb-1">
                                                <strong>Motivo:</strong> <?php echo esc($venda_detalhes['observacoes_cancelamento'] ?? 'Não informado'); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <strong>Cancelada por:</strong> <?php echo esc($venda_detalhes['usuario_cancelamento_nome'] ?? 'N/A'); ?>
                                            </p>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge bg-warning p-2 me-2">
                                                    <i class="fas fa-clock fa-fw fa-lg"></i>
                                                </span>
                                                <h5 class="mb-0">Venda Pendente</h5>
                                            </div>
                                            <p class="text-muted mb-0">
                                                Esta venda está em processamento.
                                            </p>
                                        <?php endif; ?>
                                        
                                        <p class="mt-2 mb-0">
                                            <strong>Forma de Pagamento:</strong><br>
                                            <?php
                                            if (isset($venda_detalhes['forma_pagamento'])) {
                                                $icones_pagamento = [
                                                    'dinheiro' => '<i class="fas fa-money-bill-wave text-success me-1"></i> Dinheiro',
                                                    'cartao_credito' => '<i class="fas fa-credit-card text-primary me-1"></i> Cartão de Crédito',
                                                    'cartao_debito' => '<i class="fas fa-credit-card text-info me-1"></i> Cartão de Débito',
                                                    'pix' => '<i class="fas fa-qrcode text-warning me-1"></i> PIX',
                                                    'boleto' => '<i class="fas fa-file-invoice-dollar text-secondary me-1"></i> Boleto',
                                                    'transferencia' => '<i class="fas fa-exchange-alt text-primary me-1"></i> Transferência',
                                                    'cheque' => '<i class="fas fa-money-check text-info me-1"></i> Cheque'
                                                ];
                                                
                                                echo $icones_pagamento[$venda_detalhes['forma_pagamento']] ?? ucfirst(str_replace('_', ' ', $venda_detalhes['forma_pagamento']));
                                            } else {
                                                echo 'Não informada';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card h-100 bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Resumo Financeiro</h6>
                                        <?php
                                        $valor_total = isset($venda_detalhes['valor_total']) ? $venda_detalhes['valor_total'] : 0;
                                        $desconto = isset($venda_detalhes['desconto']) ? $venda_detalhes['desconto'] : 0;
                                        $subtotal = $valor_total + $desconto;
                                        ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span><?php echo formatarDinheiro($subtotal); ?></span>
                                        </div>
                                        
                                        <?php if ($desconto > 0): ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Desconto:</span>
                                                <span class="text-danger">- <?php echo formatarDinheiro($desconto); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between">
                                            <span class="h5 mb-0">Total:</span>
                                            <span class="h5 mb-0 text-primary"><?php echo formatarDinheiro($valor_total); ?></span>
                                        </div>
                                        
                                        <?php if (!empty($venda_detalhes['observacoes'])): ?>
                                            <hr>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-comment-alt me-1"></i>
                                                <strong>Observações:</strong><br>
                                                <?php echo esc($venda_detalhes['observacoes']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Produtos da Venda -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-shopping-basket me-2"></i>
                    Produtos Vendidos
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tabelaProdutosVenda">
                        <thead>
                            <tr>
                                <th class="border-bottom-0">Código</th>
                                <th class="border-bottom-0">Produto</th>
                                <th class="border-bottom-0 text-center">Qtd</th>
                                <th class="border-bottom-0 text-end">Preço Un.</th>
                                <th class="border-bottom-0 text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($itens_venda)): ?>
                                <?php foreach ($itens_venda as $item): ?>
                                    <?php
                                    if (!is_array($item)) continue;
                                    $codigo = $item['produto_codigo'] ?? '---';
                                    $nome = $item['produto_nome'] ?? 'Produto não informado';
                                    $quantidade = $item['quantidade'] ?? 0;
                                    $preco_unitario = $item['preco_unitario'] ?? 0;
                                    $subtotal = isset($item['subtotal']) ? $item['subtotal'] : ($quantidade * $preco_unitario);
                                    $observacoes = $item['observacoes'] ?? '';
                                    ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo esc($codigo); ?></span></td>
                                        <td>
                                            <?php echo esc($nome); ?>
                                            <?php if (!empty($observacoes)): ?>
                                                <br><small class="text-muted"><?php echo esc($observacoes); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><span class="badge bg-primary"><?php echo $quantidade; ?></span></td>
                                        <td class="text-end"><?php echo formatarDinheiro($preco_unitario); ?></td>
                                        <td class="text-end"><strong><?php echo formatarDinheiro($subtotal); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-shopping-basket fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhum produto registrado nesta venda.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="3"></td>
                                <td class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end"><?php echo formatarDinheiro($subtotal); ?></td>
                            </tr>
                            <?php if ($desconto > 0): ?>
                                <tr class="bg-light">
                                    <td colspan="3"></td>
                                    <td class="text-end"><strong>Desconto:</strong></td>
                                    <td class="text-end text-danger">- <?php echo formatarDinheiro($desconto); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="bg-light">
                                <td colspan="3"></td>
                                <td class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong class="text-primary"><?php echo formatarDinheiro($valor_total); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <!-- 4 -->
<!-- Histórico da Venda -->
<?php if (!empty($historico_venda)): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Histórico da Venda
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($historico_venda as $h): ?>
                            <?php if (!is_array($h)) continue; ?>
                            <div class="timeline-item">
                                <div class="timeline-badge <?php echo isset($h['tipo']) && $h['tipo'] == 'cancelamento' ? 'bg-danger' : (isset($h['tipo']) && $h['tipo'] == 'edicao' ? 'bg-warning' : 'bg-primary'); ?>">
                                    <i class="fas <?php echo isset($h['tipo']) && $h['tipo'] == 'cancelamento' ? 'fa-times' : (isset($h['tipo']) && $h['tipo'] == 'edicao' ? 'fa-edit' : 'fa-check'); ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-date">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php 
                                            if (isset($h['data_formatada'])) {
                                                echo $h['data_formatada'];
                                            } elseif (isset($h['data_registro'])) {
                                                echo date('d/m/Y H:i', strtotime($h['data_registro']));
                                            } else {
                                                echo 'Data não disponível';
                                            }
                                            ?>
                                        </span>
                                        <span class="timeline-user">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo esc($h['usuario_nome'] ?? 'Usuário não informado'); ?>
                                        </span>
                                    </div>
                                    <div class="timeline-body">
                                        <p><?php echo esc($h['descricao'] ?? 'Sem descrição'); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Botões de Ação -->
        <?php if (isset($venda_detalhes['status']) && $venda_detalhes['status'] == 'finalizada'): ?>
    <div class="d-flex justify-content-end mb-4 gap-2">
        <?php if (in_array($_SESSION['usuario_nivel'], ['admin', 'gerente'])): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEditarVenda">
                <i class="fas fa-edit me-1"></i>
                Editar Venda
            </button>
        <?php endif; ?>
        
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCancelarVenda">
            <i class="fas fa-ban me-1"></i>
            Cancelar Venda
        </button>
    </div>
            
            <!-- Modal Editar Venda -->
            <div class="modal fade" id="modalEditarVenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editar Venda
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="vendas.php?acao=editar" method="post">
                <input type="hidden" name="id" value="<?php echo $venda_detalhes['id']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Atenção! Você está editando a venda <strong>#<?php echo $venda_detalhes['id']; ?></strong>.
                        Esta ação será registrada no histórico.
                    </div>
                    
                    <div class="mb-3">
                        <label for="forma_pagamento" class="form-label">Forma de Pagamento:</label>
                        <select class="form-select" id="forma_pagamento" name="forma_pagamento" required>
                            <?php foreach ($formas_pagamento as $key => $nome): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($venda_detalhes['forma_pagamento'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo $nome; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="desconto" class="form-label">Desconto (R$):</label>
                        <input type="text" class="form-control money-mask" id="desconto" name="desconto" 
                               value="<?php echo number_format($venda_detalhes['desconto'] ?? 0, 2, ',', '.'); ?>">
                        <div class="form-text">
                            <small>Valor do subtotal: <?php echo formatarDinheiro($subtotal); ?></small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações:</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo esc($venda_detalhes['observacoes'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
        <?php elseif (isset($venda_detalhes['status']) && $venda_detalhes['status'] == 'finalizada' && $pode_cancelar && $caixa_aberto): ?>
            <!-- Botão apenas de cancelar (sem edição) se o usuário não tem permissão de edição -->
            <div class="d-flex justify-content-end mb-4">
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCancelarVenda">
                    <i class="fas fa-ban me-1"></i>
                    Cancelar Venda
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Modal Cancelar Venda -->
        <?php if (isset($venda_detalhes['status']) && $venda_detalhes['status'] == 'finalizada' && $pode_cancelar && $caixa_aberto): ?>
            <div class="modal fade" id="modalCancelarVenda" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-ban me-2"></i>
                                Cancelar Venda
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="vendas.php?acao=cancelar" method="post">
                            <input type="hidden" name="id" value="<?php echo $venda_detalhes['id']; ?>">
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Atenção! Esta ação não poderá ser desfeita.
                                </div>
                                
                                <p>Você está prestes a cancelar a venda <strong>#<?php echo $venda_detalhes['id']; ?></strong>.</p>
                                
                                <p>Ao cancelar a venda:</p>
                                <ul>
                                    <li>Todos os produtos serão devolvidos ao estoque</li>
                                    <li>A venda será marcada como "Cancelada"</li>
                                    <li>O valor será estornado do caixa (caso não seja PIX, cartão ou outra forma eletrônica)</li>
                                </ul>
                                
                                <div class="mb-3">
                                    <label for="motivo" class="form-label">Motivo do Cancelamento:</label>
                                    <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
                                </div>
                                
                                <p class="mb-0">Tem certeza que deseja continuar?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>
                                    Não, Voltar
                                </button>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Sim, Cancelar Venda
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- 5 -->
    <!-- Filtros de Pesquisa -->
    <div class="card mb-4">
            <div class="card-header bg-light">
                <a class="text-decoration-none text-dark d-block" data-bs-toggle="collapse" href="#collapseFilter" role="button" aria-expanded="<?php echo !empty($filtros) ? 'true' : 'false'; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-filter me-2"></i>
                            Filtros de Pesquisa
                            <?php if (!empty($filtros)): ?>
                                <span class="badge bg-primary ms-2"><?php echo count($filtros); ?> filtro(s) ativo(s)</span>
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </a>
            </div>
            <div class="collapse <?php echo !empty($filtros) ? 'show' : ''; ?>" id="collapseFilter">
                <div class="card-body">
                    <form action="vendas.php" method="get" id="form-filtros">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="busca" class="form-label">Busca Geral:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="busca" name="busca" 
                                           placeholder="Buscar por ID, cliente ou valor..." 
                                           value="<?php echo esc($filtros['busca'] ?? ''); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="cliente_id" class="form-label">Cliente:</label>
                                <select class="form-select" id="cliente_id" name="cliente_id">
                                    <option value="">Todos os clientes</option>
                                    <?php
                                    $clientes = [];
                                    if (method_exists($cliente_obj, 'listarResumido')) {
                                        $clientes = $cliente_obj->listarResumido();
                                    } elseif (method_exists($cliente_obj, 'listar')) {
                                        $clientes = $cliente_obj->listar();
                                    }
                                    
                                    if (is_array($clientes)) {
                                        foreach ($clientes as $cliente) {
                                            if (!is_array($cliente)) continue;
                                            $id = $cliente['id'] ?? '';
                                            $nome = $cliente['nome'] ?? 'Cliente sem nome';
                                            $selected = (isset($filtros['cliente_id']) && $filtros['cliente_id'] == $id) ? 'selected' : '';
                                            echo '<option value="'.$id.'" '.$selected.'>'.esc($nome).'</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="data_inicio" class="form-label">Data Inicial:</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                       value="<?php echo esc($filtros['data_inicio'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="data_fim" class="form-label">Data Final:</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" 
                                       value="<?php echo esc($filtros['data_fim'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status:</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="finalizada" <?php echo (isset($filtros['status']) && $filtros['status'] == 'finalizada') ? 'selected' : ''; ?>>Finalizada</option>
                                    <option value="cancelada" <?php echo (isset($filtros['status']) && $filtros['status'] == 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="forma_pagamento" class="form-label">Forma de Pagamento:</label>
                                <select class="form-select" id="forma_pagamento" name="forma_pagamento">
                                    <option value="">Todas</option>
                                    <?php
                                    foreach ($formas_pagamento as $key => $nome) {
                                        $selected = (isset($filtros['forma_pagamento']) && $filtros['forma_pagamento'] == $key) ? 'selected' : '';
                                        echo '<option value="'.$key.'" '.$selected.'>'.$nome.'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <a href="vendas.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo-alt me-1"></i>
                                    Limpar Filtros
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>
                                    Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Listagem de Vendas -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <h5 class="mb-0 text-muted">
                            <i class="fas fa-list me-2"></i>
                            Lista de Vendas
                        </h5>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-md-end">
                            <div class="btn-group">
                                <a href="vendas.php?por_pagina=25<?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" 
                                   class="btn btn-sm <?php echo $registros_por_pagina == 25 ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    25
                                </a>
                                <a href="vendas.php?por_pagina=50<?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" 
                                   class="btn btn-sm <?php echo $registros_por_pagina == 50 ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    50
                                </a>
                                <a href="vendas.php?por_pagina=100<?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" 
                                   class="btn btn-sm <?php echo $registros_por_pagina == 100 ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    100
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tabelaVendas">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Data/Hora</th>
                                <th>Cliente</th>
                                <th>Vendedor</th>
                                <th class="text-end">Valor</th>
                                <th>Pagamento</th>
                                <th>Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vendas)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="py-5">
                                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                            <p class="mb-0 text-muted">Nenhuma venda encontrada.</p>
                                            <?php if (!empty($filtros)): ?>
                                                <p class="mb-0 text-muted">Tente remover alguns filtros para ver mais resultados.</p>
                                                <a href="vendas.php" class="btn btn-outline-primary mt-3">
                                                    <i class="fas fa-undo-alt me-1"></i>
                                                    Limpar Filtros
                                                </a>
                                            <?php else: ?>
                                                <p class="mb-0 text-muted">Você ainda não tem nenhuma venda registrada.</p>
                                                <?php if ($caixa_aberto && $pode_criar): ?>
                                                    <a href="pdv.php" class="btn btn-primary mt-3">
                                                        <i class="fas fa-plus-circle me-1"></i>
                                                        Nova Venda
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vendas as $v): ?>
                                    <?php 
                                    if (!is_array($v)) continue;
                                    
                                    // Valores padrão para campos que podem não existir
                                    $id = $v['id'] ?? '';
                                    $data_venda = $v['data_venda'] ?? '';
                                    $cliente_id = $v['cliente_id'] ?? null;
                                    $cliente_nome = $v['cliente_nome'] ?? 'Cliente não identificado';
                                    $usuario_nome = $v['usuario_nome'] ?? 'Não informado';
                                    $valor_total = $v['valor_total'] ?? 0;
                                    $desconto = $v['desconto'] ?? 0;
                                    $forma_pagamento = $v['forma_pagamento'] ?? '';
                                    $status = $v['status'] ?? '';
                                    ?>
                                    <tr>
                                        <td class="ps-3"><?php echo $id; ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <?php if (!empty($data_venda)): ?>
                                                    <span><?php echo date('d/m/Y', strtotime($data_venda)); ?></span>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($data_venda)); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Data não disponível</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($cliente_id): ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-circle avatar-xs me-2 bg-primary">
                                                        <span class="avatar-initials"><?php echo substr($cliente_nome, 0, 1); ?></span>
                                                    </div>
                                                    <div class="cliente-nome">
                                                        <?php echo esc($cliente_nome); ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Não identificado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc($usuario_nome); ?></td>
                                        <td class="text-end">
                                            <strong>
                                                <?php echo formatarDinheiro($valor_total); ?>
                                            </strong>
                                            <?php if ($desconto > 0): ?>
                                                <br>
                                                <small class="text-danger">
                                                    Desconto: <?php echo formatarDinheiro($desconto); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $icones_pagamento = [
                                                'dinheiro' => '<span class="badge rounded-pill bg-light text-success border border-success"><i class="fas fa-money-bill-wave me-1"></i> Dinheiro</span>',
                                                'cartao_credito' => '<span class="badge rounded-pill bg-light text-primary border border-primary"><i class="fas fa-credit-card me-1"></i> Crédito</span>',
                                                'cartao_debito' => '<span class="badge rounded-pill bg-light text-info border border-info"><i class="fas fa-credit-card me-1"></i> Débito</span>',
                                                'pix' => '<span class="badge rounded-pill bg-light text-warning border border-warning"><i class="fas fa-qrcode me-1"></i> PIX</span>',
                                                'boleto' => '<span class="badge rounded-pill bg-light text-secondary border border-secondary"><i class="fas fa-file-invoice-dollar me-1"></i> Boleto</span>',
                                                'transferencia' => '<span class="badge rounded-pill bg-light text-primary border border-primary"><i class="fas fa-exchange-alt me-1"></i> Transf.</span>',
                                                'cheque' => '<span class="badge rounded-pill bg-light text-info border border-info"><i class="fas fa-money-check me-1"></i> Cheque</span>'
                                            ];
                                            
                                            echo isset($icones_pagamento[$forma_pagamento]) ? 
                                                 $icones_pagamento[$forma_pagamento] : 
                                                 (empty($forma_pagamento) ? 'Não informado' : ucfirst(str_replace('_', ' ', $forma_pagamento)));
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($status == 'finalizada'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    Finalizada
                                                </span>
                                            <?php elseif ($status == 'cancelada'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times-circle me-1"></i>
                                                    Cancelada
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Pendente
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <!-- 6 -->
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <?php if ($pode_ver): ?>
                                                    <a href="vendas.php?id=<?php echo $id; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Detalhes"
                                                       style="display: inline-block !important;">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
<!-- Botão Editar - apenas para administrador e gerente -->
<?php if ($status == 'finalizada' && in_array($_SESSION['usuario_nivel'], ['admin', 'gerente'])): ?>
    <a href="vendas.php?id=<?php echo $id; ?>" 
       class="btn btn-sm btn-outline-warning" 
       data-bs-toggle="tooltip" 
       title="Editar"
       style="display: inline-block !important;">
        <i class="fas fa-edit"></i>
    </a>
<?php endif; ?>
                                                
                                                <a href="comprovante.php?id=<?php echo $id; ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Imprimir"
                                                   style="display: inline-block !important;">
                                                    <i class="fas fa-print"></i>
                                                </a>

                                                <!-- Botão Cancelar - apenas admin/gerente -->
                                                <?php if ($status == 'finalizada' && in_array($_SESSION['usuario_nivel'], ['admin', 'gerente'])): ?>
    <button type="button" 
            class="btn btn-sm btn-outline-danger btn-cancelar-venda" 
            data-id="<?php echo $id; ?>"
            data-bs-toggle="tooltip" 
            title="Cancelar"
            style="display: inline-block !important;">
        <i class="fas fa-ban"></i>
    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if (!empty($vendas)): ?>
                <div class="card-footer bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <div class="text-muted">
                                Mostrando <span class="fw-bold"><?php echo count($vendas); ?></span> de <span class="fw-bold"><?php echo $total_registros; ?></span> vendas
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php if ($total_paginas > 1): ?>
                                <nav aria-label="Paginação de vendas">
                                    <ul class="pagination justify-content-md-end justify-content-center mb-0">
                                        <!-- Primeira página -->
                                        <li class="page-item <?php echo $pagina_atual == 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="vendas.php?pagina=1&por_pagina=<?php echo $registros_por_pagina . (!empty($filtros) ? '&' . http_build_query($filtros) : ''); ?>">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        
                                        <!-- Página anterior -->
                                        <li class="page-item <?php echo $pagina_atual == 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="vendas.php?pagina=<?php echo $pagina_atual - 1; ?>&por_pagina=<?php echo $registros_por_pagina . (!empty($filtros) ? '&' . http_build_query($filtros) : ''); ?>">
                                                <i class="fas fa-angle-left"></i>
                                            </a>
                                        </li>
                                        
                                        <!-- Páginas numeradas -->
                                        <?php
                                        $inicio = max(1, $pagina_atual - 2);
                                        $fim = min($total_paginas, $pagina_atual + 2);
                                        
                                        // Ajustar para mostrar 5 números
                                        if ($fim - $inicio < 4) {
                                            if ($inicio == 1) {
                                                $fim = min($total_paginas, 5);
                                            } elseif ($fim == $total_paginas) {
                                                $inicio = max(1, $total_paginas - 4);
                                            }
                                        }
                                        
                                        for ($i = $inicio; $i <= $fim; $i++):
                                        ?>
                                            <li class="page-item <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                                                <a class="page-link" href="vendas.php?pagina=<?php echo $i; ?>&por_pagina=<?php echo $registros_por_pagina . (!empty($filtros) ? '&' . http_build_query($filtros) : ''); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <!-- Próxima página -->
                                        <li class="page-item <?php echo $pagina_atual == $total_paginas ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="vendas.php?pagina=<?php echo $pagina_atual + 1; ?>&por_pagina=<?php echo $registros_por_pagina . (!empty($filtros) ? '&' . http_build_query($filtros) : ''); ?>">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                        </li>
                                        
                                        <!-- Última página -->
                                        <li class="page-item <?php echo $pagina_atual == $total_paginas ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="vendas.php?pagina=<?php echo $total_paginas; ?>&por_pagina=<?php echo $registros_por_pagina . (!empty($filtros) ? '&' . http_build_query($filtros) : ''); ?>">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- Modal Cancelar Venda Rápido -->
        <div class="modal fade" id="modalCancelarVendaRapido" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-ban me-2"></i>
                            Cancelar Venda
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="vendas.php?acao=cancelar" method="post">
                        <input type="hidden" name="id" id="cancelar_venda_id">
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Atenção! Esta ação não poderá ser desfeita.
                            </div>
                            
                            <p>Você está prestes a cancelar a venda <strong>#<span id="cancelar_venda_numero"></span></strong>.</p>
                            
                            <p>Ao cancelar a venda:</p>
                            <ul>
                                <li>Todos os produtos serão devolvidos ao estoque</li>
                                <li>A venda será marcada como "Cancelada"</li>
                                <li>O valor será estornado do caixa (caso não seja PIX, cartão ou outra forma eletrônica)</li>
                            </ul>
                            
                            <div class="mb-3">
                                <label for="motivo" class="form-label">Motivo do Cancelamento:</label>
                                <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
                            </div>
                            
                            <p class="mb-0">Tem certeza que deseja continuar?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                Não, Voltar
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Sim, Cancelar Venda
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Modal de Relatórios -->
        <?php if ($pode_relatorios): ?>
            <div class="modal fade" id="modalRelatorio" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-chart-bar me-2"></i>
                                Relatórios de Vendas
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                Selecione o período e o tipo de relatório que deseja gerar.
                            </div>
                            
                            <form action="vendas.php?acao=exportar_csv" method="post" id="form-relatorio">
                                <div class="mb-3">
                                    <label for="data_inicio_rel" class="form-label">Data Inicial:</label>
                                    <input type="date" class="form-control" id="data_inicio_rel" name="data_inicio" required 
                                           value="<?php echo date('Y-m-01'); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="data_fim_rel" class="form-label">Data Final:</label>
                                    <input type="date" class="form-control" id="data_fim_rel" name="data_fim" required 
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="filtro_status" class="form-label">Status das Vendas:</label>
                                    <select class="form-select" id="filtro_status" name="filtro_status">
                                        <option value="todos">Todas as vendas</option>
                                        <option value="finalizada">Apenas vendas finalizadas</option>
                                        <option value="cancelada">Apenas vendas canceladas</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Formato do Relatório:</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="formato" id="formato_csv" value="csv" checked>
                                            <label class="form-check-label" for="formato_csv">
                                                <i class="fas fa-file-csv me-1"></i>
                                                CSV
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="formato" id="formato_pdf" value="pdf">
                                            <label class="form-check-label" for="formato_pdf">
                                                <i class="fas fa-file-pdf me-1"></i>
                                                PDF
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </button>
                            <button type="submit" form="form-relatorio" class="btn btn-primary">
                                <i class="fas fa-download me-1"></i>
                                Baixar Relatório
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    /* Estilos para avatar do cliente */
    .avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #fff;
        font-weight: 600;
        width: 32px;
        height: 32px;
    }
    
    .avatar-xs {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
    
    .avatar-initials {
        text-transform: uppercase;
    }
    
    /* Garantir que botões de ação em tabelas responsivas mantenham aparência correta */
    .table .btn {
        padding: 0.25rem 0.5rem;
        margin: 0.1rem;
    }
    
    .table .btn i {
        font-size: 0.875rem;
    }
    
    /* Timeline para histórico da venda */
    .timeline {
        position: relative;
        padding: 1rem 0;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        left: 1rem;
        height: 100%;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 3rem;
        margin-bottom: 1.5rem;
    }
    
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    
    .timeline-badge {
        position: absolute;
        left: 0;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        z-index: 1;
    }
    
    .timeline-badge i {
        font-size: 0.875rem;
    }
    
    .timeline-content {
        background-color: #f8f9fa;
        border-radius: 0.375rem;
        padding: 1rem;
    }
    
    .timeline-header {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .timeline-body p:last-child {
        margin-bottom: 0;
    }
    
    /* Truncar texto longo */
    .cliente-nome {
        max-width: 150px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    @media (max-width: 992px) {
        .cliente-nome {
            max-width: 100px;
        }
    }
</style>

<script>
    // Máscara para campo de dinheiro
if (document.getElementById('desconto')) {
    var descontoInput = document.getElementById('desconto');
    descontoInput.addEventListener('input', function (e) {
        var value = e.target.value.replace(/\D/g, '');
        value = (parseInt(value) / 100).toFixed(2);
        value = value.replace('.', ',');
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        e.target.value = value;
    });
}
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configurar modal de cancelamento rápido
    var botoesCancelarVenda = document.querySelectorAll('.btn-cancelar-venda');
    botoesCancelarVenda.forEach(function(botao) {
        botao.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            document.getElementById('cancelar_venda_id').value = id;
            document.getElementById('cancelar_venda_numero').textContent = id;
            
            var modalCancelar = new bootstrap.Modal(document.getElementById('modalCancelarVendaRapido'));
            modalCancelar.show();
        });
    });
    
    // Validar formulário de relatório
    if (document.getElementById('form-relatorio')) {
        document.getElementById('form-relatorio').addEventListener('submit', function(event) {
            var dataInicio = new Date(document.getElementById('data_inicio_rel').value);
            var dataFim = new Date(document.getElementById('data_fim_rel').value);
            
            if (dataInicio > dataFim) {
                event.preventDefault();
                alert('A data inicial não pode ser maior que a data final!');
            }
        });
        
        // Validação do formato de relatório
        var radioPdf = document.getElementById('formato_pdf');
        if (radioPdf) {
            radioPdf.addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('form-relatorio').action = 'vendas.php?acao=exportar_pdf';
                }
            });
        }
        
        var radioCsv = document.getElementById('formato_csv');
        if (radioCsv) {
            radioCsv.addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('form-relatorio').action = 'vendas.php?acao=exportar_csv';
                }
            });
        }
    }
    
    // Select2 para filtro de clientes, se disponível
    if (typeof $.fn !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $('#cliente_id').select2({
            placeholder: 'Selecione um cliente',
            allowClear: true,
            width: '100%'
        });
    }
    
    // Verificar se há um hash na URL para abrir modal específico
    if (window.location.hash) {
        var hash = window.location.hash.substring(1);
        var modalElement = document.getElementById(hash);
        if (modalElement) {
            var modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }
});
</script>

<?php include 'footer.php'; ?>