<?php
require_once 'config.php';
verificarLogin();

// Verificar se quer mostrar detalhes de uma compra
$compra_detalhes = null;
$itens_compra = [];
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $compra_detalhes = $compra->buscarPorId($id);
    
    if ($compra_detalhes) {
        $itens_compra = $compra->buscarItens($id);
    }
}

// Processar ações via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Finalizar compra pendente
    if (isset($_POST['finalizar'])) {
        $id = $_POST['id'];
        
        if ($compra->finalizar($id)) {
            alerta('Compra finalizada com sucesso!', 'success');
        } else {
            alerta('Erro ao finalizar compra!', 'danger');
        }
        
        header('Location: compras.php?id=' . $id);
        exit;
    }
    
    // Cancelar compra
    if (isset($_POST['cancelar'])) {
        $id = $_POST['id'];
        
        if ($compra->cancelar($id)) {
            alerta('Compra cancelada com sucesso!', 'success');
        } else {
            alerta('Erro ao cancelar compra!', 'danger');
        }
        
        header('Location: compras.php?id=' . $id);
        exit;
    }
}

// Template da página
$titulo_pagina = 'Gerenciamento de Compras';
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Compras</h1>
        <a href="nova_compra.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nova Compra
        </a>
    </div>
    
    <?php if ($compra_detalhes): ?>
    
    <!-- Detalhes da Compra -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detalhes da Compra #<?php echo $compra_detalhes['id']; ?></h5>
                    <a href="compras.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Data:</strong> <?php echo $compra_detalhes['data_formatada']; ?></p>
                            <p><strong>Fornecedor:</strong> <?php echo $compra_detalhes['fornecedor_nome'] ?: 'Fornecedor não informado'; ?></p>
                            <p><strong>Responsável:</strong> <?php echo $compra_detalhes['usuario_nome']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> 
                                <?php 
                                    if ($compra_detalhes['status'] == 'finalizada') {
                                        echo '<span class="badge bg-success">Finalizada</span>';
                                    } else if ($compra_detalhes['status'] == 'cancelada') {
                                        echo '<span class="badge bg-danger">Cancelada</span>';
                                    } else {
                                        echo '<span class="badge bg-warning">Pendente</span>';
                                    }
                                ?>
                            </p>
                            <p><strong>Valor Total:</strong> <?php echo formatarDinheiro($compra_detalhes['valor_total']); ?></p>
                            <p><strong>Observações:</strong> <?php echo $compra_detalhes['observacoes'] ?: 'Nenhuma observação'; ?></p>
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
                                <?php foreach ($itens_compra as $item): ?>
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
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong><?php echo formatarDinheiro($compra_detalhes['valor_total']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if ($compra_detalhes['status'] == 'pendente'): ?>
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modal-finalizar">
                            <i class="fas fa-check"></i> Finalizar Compra
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-cancelar">
                            <i class="fas fa-ban"></i> Cancelar Compra
                        </button>
                    </div>
                    
                    <!-- Modal de Finalização -->
                    <div class="modal fade" id="modal-finalizar" tabindex="-1" aria-labelledby="modal-finalizar-label" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modal-finalizar-label">Finalizar Compra</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Tem certeza que deseja finalizar a compra #<?php echo $compra_detalhes['id']; ?>?</p>
                                    <p class="text-info">Esta ação irá adicionar os produtos ao estoque e marcar a compra como finalizada.</p>
                                </div>
                                <div class="modal-footer">
                                    <form method="post" action="">
                                        <input type="hidden" name="id" value="<?php echo $compra_detalhes['id']; ?>">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                        <button type="submit" name="finalizar" class="btn btn-success">Confirmar Finalização</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($compra_detalhes['status'] != 'cancelada'): ?>
                    <!-- Modal de Cancelamento -->
                    <div class="modal fade" id="modal-cancelar" tabindex="-1" aria-labelledby="modal-cancelar-label" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modal-cancelar-label">Cancelar Compra</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Tem certeza que deseja cancelar a compra #<?php echo $compra_detalhes['id']; ?>?</p>
                                    <?php if ($compra_detalhes['status'] == 'finalizada'): ?>
                                    <p class="text-danger">Esta ação irá remover os produtos do estoque e marcar a compra como cancelada.</p>
                                    <?php else: ?>
                                    <p class="text-warning">Esta ação irá marcar a compra como cancelada.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <form method="post" action="">
                                        <input type="hidden" name="id" value="<?php echo $compra_detalhes['id']; ?>">
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
    
    <!-- Listagem de Compras -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Fornecedor</th>
                            <th>Responsável</th>
                            <th>Valor Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $compras = $compra->listar();
                        foreach ($compras as $c) {
                            echo '<tr>';
                            echo '<td>'.$c['id'].'</td>';
                            echo '<td>'.$c['data_formatada'].'</td>';
                            echo '<td>'.($c['fornecedor_nome'] ?: 'Não informado').'</td>';
                            echo '<td>'.$c['usuario_nome'].'</td>';
                            echo '<td>'.formatarDinheiro($c['valor_total']).'</td>';
                            
                            // Status
                            if ($c['status'] == 'finalizada') {
                                echo '<td><span class="badge bg-success">Finalizada</span></td>';
                            } else if ($c['status'] == 'cancelada') {
                                echo '<td><span class="badge bg-danger">Cancelada</span></td>';
                            } else {
                                echo '<td><span class="badge bg-warning">Pendente</span></td>';
                            }
                            
                            // Ações
                            echo '<td>
                                    <a href="?id='.$c['id'].'" class="btn btn-sm btn-info" title="Detalhes">
                                        <i class="fas fa-eye"></i>
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
