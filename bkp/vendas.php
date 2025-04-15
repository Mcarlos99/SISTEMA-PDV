<?php
require_once 'config.php';
verificarLogin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cancelar venda
    if (isset($_POST['cancelar'])) {
        $id = $_POST['id'];
        
        if ($venda->cancelar($id)) {
            alerta('Venda cancelada com sucesso!', 'success');
        } else {
            alerta('Erro ao cancelar venda!', 'danger');
        }
        
        // Redirecionar para evitar reenvio do formulário
        header('Location: vendas.php');
        exit;
    }
}

// Verificar se quer mostrar detalhes de uma venda
$venda_detalhes = null;
$itens_venda = [];
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $venda_detalhes = $venda->buscarPorId($id);
    
    if ($venda_detalhes) {
        $itens_venda = $venda->buscarItens($id);
    }
}

// Template da página
$titulo_pagina = 'Gerenciamento de Vendas';
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Vendas</h1>
        <a href="pdv.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nova Venda
        </a>
    </div>
    
    <?php if ($venda_detalhes): ?>
    
    <!-- Detalhes da Venda -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detalhes da Venda #<?php echo $venda_detalhes['id']; ?></h5>
                    <div>
                        <a href="comprovante.php?id=<?php echo $venda_detalhes['id']; ?>" target="_blank" class="btn btn-sm btn-info">
                            <i class="fas fa-print"></i> Imprimir
                        </a>
                        <a href="vendas.php" class="btn btn-sm btn-secondary ms-1">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Data/Hora:</strong> <?php echo $venda_detalhes['data_formatada']; ?></p>
                            <p><strong>Cliente:</strong> <?php echo $venda_detalhes['cliente_nome'] ?: 'Cliente não identificado'; ?></p>
                            <p><strong>Vendedor:</strong> <?php echo $venda_detalhes['usuario_nome']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> 
                                <?php 
                                    if ($venda_detalhes['status'] == 'finalizada') {
                                        echo '<span class="badge bg-success">Finalizada</span>';
                                    } else if ($venda_detalhes['status'] == 'cancelada') {
                                        echo '<span class="badge bg-danger">Cancelada</span>';
                                    } else {
                                        echo '<span class="badge bg-warning">Pendente</span>';
                                    }
                                ?>
                            </p>
                            <p><strong>Forma de Pagamento:</strong> <?php echo ucfirst(str_replace('_', ' ', $venda_detalhes['forma_pagamento'])); ?></p>
                            <p><strong>Observações:</strong> <?php echo $venda_detalhes['observacoes'] ?: 'Nenhuma observação'; ?></p>
                        </div>
                    </div>
                    
                    <div class="table-responsive mt-4">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Produto</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-end">Preço Unitário</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itens_venda as $item): ?>
                                <tr>
                                    <td><?php echo $item['produto_codigo']; ?></td>
                                    <td><?php echo $item['produto_nome']; ?></td>
                                    <td class="text-center"><?php echo $item['quantidade']; ?></td>
                                    <td class="text-end"><?php echo formatarDinheiro($item['preco_unitario']); ?></td>
                                    <td class="text-end"><?php echo formatarDinheiro($item['subtotal']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><?php echo formatarDinheiro($venda_detalhes['valor_total'] + $venda_detalhes['desconto']); ?></td>
                                </tr>
                                <?php if ($venda_detalhes['desconto'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Desconto:</strong></td>
                                    <td class="text-end"><?php echo formatarDinheiro($venda_detalhes['desconto']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong><?php echo formatarDinheiro($venda_detalhes['valor_total']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if ($venda_detalhes['status'] == 'finalizada'): ?>
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-cancelar">
                            <i class="fas fa-ban"></i> Cancelar Venda
                        </button>
                    </div>
                    
                    <!-- Modal de Cancelamento -->
                    <div class="modal fade" id="modal-cancelar" tabindex="-1" aria-labelledby="modal-cancelar-label" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modal-cancelar-label">Cancelar Venda</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Tem certeza que deseja cancelar a venda #<?php echo $venda_detalhes['id']; ?>?</p>
                                    <p class="text-danger">Esta ação irá estornar o estoque dos produtos e marcar a venda como cancelada.</p>
                                </div>
                                <div class="modal-footer">
                                    <form method="post" action="">
                                        <input type="hidden" name="id" value="<?php echo $venda_detalhes['id']; ?>">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                        <button type="submit" name="cancelar" class="btn btn-danger">Confirmar Cancelamento</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    
    <!-- Listagem de Vendas -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data/Hora</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Valor Total</th>
                            <th>Forma de Pagamento</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $vendas = $venda->listar();
                        foreach ($vendas as $v) {
                            echo '<tr>';
                            echo '<td>'.$v['id'].'</td>';
                            echo '<td>'.$v['data_formatada'].'</td>';
                            echo '<td>'.($v['cliente_nome'] ?: 'Cliente não identificado').'</td>';
                            echo '<td>'.$v['usuario_nome'].'</td>';
                            echo '<td>'.formatarDinheiro($v['valor_total']).'</td>';
                            echo '<td>'.ucfirst(str_replace('_', ' ', $v['forma_pagamento'])).'</td>';
                            
                            // Status
                            if ($v['status'] == 'finalizada') {
                                echo '<td><span class="badge bg-success">Finalizada</span></td>';
                            } else if ($v['status'] == 'cancelada') {
                                echo '<td><span class="badge bg-danger">Cancelada</span></td>';
                            } else {
                                echo '<td><span class="badge bg-warning">Pendente</span></td>';
                            }
                            
                            // Ações
                            echo '<td>
                                    <a href="?id='.$v['id'].'" class="btn btn-sm btn-info me-1" title="Detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="comprovante.php?id='.$v['id'].'" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir">
                                        <i class="fas fa-print"></i>
                                    </a>
                                  </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
