<?php
require_once 'config.php';
verificarLogin();

// Inicializa a classe Comanda
$comanda = new Comanda($pdo);

// Verificar se o ID do cliente foi fornecido
if (!isset($_GET['cliente_id']) || empty($_GET['cliente_id'])) {
    alerta('Cliente não especificado', 'warning');
    header('Location: clientes.php');
    exit;
}

$cliente_id = (int)$_GET['cliente_id'];
$detalhes_cliente = $cliente->buscarPorId($cliente_id);

if (!$detalhes_cliente) {
    alerta('Cliente não encontrado', 'warning');
    header('Location: clientes.php');
    exit;
}

// Verificar se o cliente tem comanda aberta
$comanda_aberta = $comanda->verificarComandaAberta($cliente_id);

// Buscar histórico de comandas do cliente
$filtro = ['cliente_id' => $cliente_id];
$historico_comandas = $comanda->listar($filtro);

// Template da página
$titulo_pagina = 'Comandas do Cliente: ' . $detalhes_cliente['nome'];
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Comandas do Cliente: <?php echo $detalhes_cliente['nome']; ?></h1>
        <div>
            <?php if (!$comanda_aberta): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaComanda">
                <i class="fas fa-plus"></i> Nova Comanda
            </button>
            <?php endif; ?>
            <a href="clientes.php?id=<?php echo $cliente_id; ?>" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left"></i> Voltar para Cliente
            </a>
        </div>
    </div>
    
    <!-- Informações do Cliente -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Informações do Cliente</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Nome:</th>
                            <td><?php echo $detalhes_cliente['nome']; ?></td>
                        </tr>
                        <?php if (!empty($detalhes_cliente['cpf_cnpj'])): ?>
                        <tr>
                            <th>CPF/CNPJ:</th>
                            <td><?php echo $detalhes_cliente['cpf_cnpj']; ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($detalhes_cliente['telefone'])): ?>
                        <tr>
                            <th>Telefone:</th>
                            <td><?php echo $detalhes_cliente['telefone']; ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($detalhes_cliente['email'])): ?>
                        <tr>
                            <th>E-mail:</th>
                            <td><?php echo $detalhes_cliente['email']; ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if ($comanda_aberta): ?>
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Comanda Aberta</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Comanda #:</strong> <?php echo $comanda_aberta['id']; ?></p>
                            <p><strong>Data de Abertura:</strong> <?php echo date('d/m/Y H:i', strtotime($comanda_aberta['data_abertura'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Valor Atual:</strong> <span class="fs-4 fw-bold"><?php echo formatarDinheiro($comanda_aberta['valor_total']); ?></span></p>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-3">
                        <a href="comandas.php?id=<?php echo $comanda_aberta['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Ver Detalhes da Comanda
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Histórico de Comandas -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="card-title mb-0">Histórico de Comandas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Data Abertura</th>
                            <th>Data Fechamento</th>
                            <th>Valor Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historico_comandas)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Nenhuma comanda encontrada</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($historico_comandas as $cmd): ?>
                        <tr>
                            <td><?php echo $cmd['id']; ?></td>
                            <td><?php echo $cmd['data_abertura_formatada']; ?></td>
                            <td><?php echo $cmd['data_fechamento_formatada'] ?: '-'; ?></td>
                            <td><?php echo formatarDinheiro($cmd['valor_total']); ?></td>
                            <td>
                                <?php
                                if ($cmd['status'] == 'aberta') {
                                    echo '<span class="badge bg-success">Aberta</span>';
                                } else if ($cmd['status'] == 'fechada') {
                                    echo '<span class="badge bg-secondary">Fechada</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Cancelada</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="comandas.php?id=<?php echo $cmd['id']; ?>" class="btn btn-sm btn-info me-1" title="Detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($cmd['status'] == 'aberta'): ?>
                                <a href="comandas.php?id=<?php echo $cmd['id']; ?>" class="btn btn-sm btn-primary me-1" title="Adicionar Produtos">
                                    <i class="fas fa-plus"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Nova Comanda -->
    <div class="modal fade" id="modalNovaComanda" tabindex="-1" aria-labelledby="modalNovaComandaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovaComandaLabel">Nova Comanda para <?php echo $detalhes_cliente['nome']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="comandas.php" id="formNovaComanda">
                        <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                        
                        <div class="mb-3">
                            <label for="observacoes_comanda" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes_comanda" name="observacoes" rows="3"></textarea>
                            <div class="form-text">Informe quaisquer detalhes importantes sobre esta comanda.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formNovaComanda" name="abrir_comanda" class="btn btn-primary">Abrir Comanda</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    const table = document.querySelector('.datatable');
    if (table) {
        new DataTable(table, {
            language: {
                url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            },
            order: [[0, 'desc']] // Ordenar por ID de forma decrescente
        });
    }
});
</script>

<?php include 'footer.php'; ?>