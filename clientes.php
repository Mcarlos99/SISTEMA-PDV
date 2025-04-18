<?php
require_once 'config.php';
verificarLogin();

$estados = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
    'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
    'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
    'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
    'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
    'SE' => 'Sergipe', 'TO' => 'Tocantins'
];

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Adicionar cliente
    if (isset($_POST['adicionar'])) {
        $dados = [
            'nome' => $_POST['nome'],
            'cpf_cnpj' => $_POST['cpf_cnpj'],
            'email' => $_POST['email'],
            'telefone' => $_POST['telefone'],
            'endereco' => $_POST['endereco'],
            'cidade' => $_POST['cidade'],
            'estado' => $_POST['estado'],
            'cep' => $_POST['cep'],
            'observacoes' => $_POST['observacoes']
        ];
        
        if ($cliente->adicionar($dados)) {
            alerta('Cliente adicionado com sucesso!', 'success');
        } else {
            alerta('Erro ao adicionar cliente!', 'danger');
        }
        
        header('Location: clientes.php');
        exit;
    }
    
    // Atualizar cliente
    if (isset($_POST['atualizar'])) {
        $id = $_POST['id'];
        $dados = [
            'nome' => $_POST['nome'],
            'cpf_cnpj' => $_POST['cpf_cnpj'],
            'email' => $_POST['email'],
            'telefone' => $_POST['telefone'],
            'endereco' => $_POST['endereco'],
            'cidade' => $_POST['cidade'],
            'estado' => $_POST['estado'],
            'cep' => $_POST['cep'],
            'observacoes' => $_POST['observacoes']
        ];
        
        if ($cliente->atualizar($id, $dados)) {
            alerta('Cliente atualizado com sucesso!', 'success');
        } else {
            alerta('Erro ao atualizar cliente!', 'danger');
        }
        
        header('Location: clientes.php?id=' . $id);
        exit;
    }
    
    // Excluir cliente
    if (isset($_POST['excluir'])) {
        $id = $_POST['id'];
        
        if ($cliente->excluir($id)) {
            alerta('Cliente excluído com sucesso!', 'success');
        } else {
            alerta('Erro ao excluir cliente! Verifique se não há vendas associadas a este cliente.', 'danger');
        }
        
        header('Location: clientes.php');
        exit;
    }
    
    // Abrir comanda para o cliente
    if (isset($_POST['abrir_comanda'])) {
        $cliente_id = $_POST['cliente_id'];
        $observacoes = $_POST['observacoes'] ?? '';
        
        if (empty($cliente_id)) {
            alerta('Selecione um cliente para abrir a comanda.', 'warning');
            header('Location: clientes.php');
            exit;
        }
        
        try {
            $comanda_id = $comanda->abrir($cliente_id, $observacoes);
            alerta('Comanda aberta com sucesso!', 'success');
            header('Location: comandas.php?id=' . $comanda_id);
            exit;
        } catch (Exception $e) {
            alerta('Erro ao abrir comanda: ' . $e->getMessage(), 'danger');
            header('Location: clientes.php?id=' . $cliente_id);
            exit;
        }
    }
}

// Verificar se é para mostrar detalhes de um cliente
$cliente_detalhes = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $cliente_detalhes = $cliente->buscarPorId($id);
    
    // Se cliente não existir, voltar para listagem
    if (!$cliente_detalhes) {
        alerta('Cliente não encontrado!', 'warning');
        header('Location: clientes.php');
        exit;
    }
}

// Template da página
$titulo_pagina = 'Gerenciamento de Clientes';
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $cliente_detalhes ? 'Detalhes do Cliente' : 'Clientes'; ?></h1>
        <div>
            <?php if ($cliente_detalhes): ?>
                <a href="clientes.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Voltar para Listagem
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarCliente">
                    <i class="fas fa-plus me-1"></i> Novo Cliente
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($cliente_detalhes): ?>
    <!-- Detalhes do Cliente -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-user me-2"></i> <?php echo $cliente_detalhes['nome']; ?></h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalEditarCliente">
                                    <i class="fas fa-edit me-1"></i> Editar
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#modalExcluirCliente">
                                    <i class="fas fa-trash me-1"></i> Excluir
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">CPF/CNPJ:</label>
                        <div><?php echo $cliente_detalhes['cpf_cnpj'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">E-mail:</label>
                        <div><?php echo $cliente_detalhes['email'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Telefone:</label>
                        <div><?php echo $cliente_detalhes['telefone'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Endereço:</label>
                        <div><?php echo $cliente_detalhes['endereco'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cidade:</label>
                            <div><?php echo $cliente_detalhes['cidade'] ?: 'Não informado'; ?></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Estado:</label>
                            <div><?php echo $cliente_detalhes['estado'] ?: 'N/I'; ?></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">CEP:</label>
                            <div><?php echo $cliente_detalhes['cep'] ?: 'N/I'; ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Observações:</label>
                        <div><?php echo nl2br($cliente_detalhes['observacoes']) ?: 'Nenhuma observação'; ?></div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <a href="comanda_cliente.php?cliente_id=<?php echo $cliente_detalhes['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-clipboard-list me-1"></i> Visualizar Comandas
                        </a>
                        <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalHistoricoVendas">
                            <i class="fas fa-history me-1"></i> Histórico de Vendas
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Verificar comandas do cliente -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-clipboard-list me-2"></i> Comandas</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Verificar se há comanda aberta
                    $comanda_aberta = $comanda->verificarComandaAberta($cliente_detalhes['id']);
                    
                    if ($comanda_aberta) {
                        echo '<div class="alert alert-success">';
                        echo '<strong>Comanda Aberta #' . $comanda_aberta['id'] . '</strong><br>';
                        echo 'Aberta em: ' . date('d/m/Y H:i', strtotime($comanda_aberta['data_abertura'])) . '<br>';
                        echo 'Valor atual: <strong>' . formatarDinheiro($comanda_aberta['valor_total']) . '</strong>';
                        echo '</div>';
                        
                        echo '<a href="comandas.php?id=' . $comanda_aberta['id'] . '" class="btn btn-primary">';
                        echo '<i class="fas fa-eye me-1"></i> Ver Comanda Atual';
                        echo '</a>';
                    } else {
                        echo '<div class="alert alert-info">Não há comanda aberta para este cliente.</div>';
                        
                        echo '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNovaComanda">';
                        echo '<i class="fas fa-plus me-1"></i> Abrir Nova Comanda';
                        echo '</button>';
                    }
                    ?>
                    
                    <a href="comanda_cliente.php?cliente_id=<?php echo $cliente_detalhes['id']; ?>" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-history me-1"></i> Ver Histórico
                    </a>
                </div>
            </div>
            
            <!-- Últimas vendas do cliente -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-shopping-cart me-2"></i> Últimas Vendas</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT v.id, v.data_venda, v.valor_total, v.forma_pagamento, v.status
                        FROM vendas v
                        WHERE v.cliente_id = :cliente_id
                        ORDER BY v.data_venda DESC
                        LIMIT 5
                    ");
                    $stmt->bindParam(':cliente_id', $cliente_detalhes['id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $vendas = $stmt->fetchAll();
                    
                    if (count($vendas) > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Forma de Pagamento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendas as $v): ?>
                                <tr>
                                    <td><?php echo $v['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($v['data_venda'])); ?></td>
                                    <td><?php echo formatarDinheiro($v['valor_total']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $v['forma_pagamento'])); ?></td>
                                    <td>
                                        <?php
                                        if ($v['status'] == 'finalizada') {
                                            echo '<span class="badge bg-success">Finalizada</span>';
                                        } else if ($v['status'] == 'cancelada') {
                                            echo '<span class="badge bg-danger">Cancelada</span>';
                                        } else {
                                            echo '<span class="badge bg-warning">Pendente</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="vendas.php?id=<?php echo $v['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        Este cliente não possui vendas registradas ainda.
                    </div>
                    <?php endif; ?>
                    
                    <a href="#" class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalNovaVenda">
                        <i class="fas fa-plus me-1"></i> Nova Venda
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Nova Comanda -->
    <div class="modal fade" id="modalNovaComanda" tabindex="-1" aria-labelledby="modalNovaComandaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovaComandaLabel">Nova Comanda para <?php echo $cliente_detalhes['nome']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="formNovaComanda">
                        <input type="hidden" name="cliente_id" value="<?php echo $cliente_detalhes['id']; ?>">
                        
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
    
    <!-- Modal Editar Cliente -->
    <div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-labelledby="modalEditarClienteLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarClienteLabel">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="formEditarCliente">
                        <input type="hidden" name="id" value="<?php echo $cliente_detalhes['id']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo $cliente_detalhes['nome']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="cpf_cnpj" class="form-label">CPF/CNPJ</label>
                                <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo $cliente_detalhes['cpf_cnpj']; ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $cliente_detalhes['email']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo $cliente_detalhes['telefone']; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="endereco" class="form-label">Endereço</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo $cliente_detalhes['endereco']; ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo $cliente_detalhes['cidade']; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Selecione</option>
                                    <?php
                                    $estados = [
                                        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
                                        'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
                                        'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
                                        'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
                                        'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
                                        'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
                                        'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                    ];
                                    
                                    foreach ($estados as $sigla => $nome) {
                                        $selected = ($cliente_detalhes['estado'] == $sigla) ? 'selected' : '';
                                        echo "<option value=\"{$sigla}\" {$selected}>{$nome}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="cep" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep" value="<?php echo $cliente_detalhes['cep']; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo $cliente_detalhes['observacoes']; ?></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formEditarCliente" name="atualizar" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Excluir Cliente -->
    <div class="modal fade" id="modalExcluirCliente" tabindex="-1" aria-labelledby="modalExcluirClienteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExcluirClienteLabel">Excluir Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o cliente <strong><?php echo $cliente_detalhes['nome']; ?></strong>?</p>
                    <p class="text-danger">Esta ação não poderá ser desfeita. Clientes com vendas ou comandas não podem ser excluídos.</p>
                    <form method="post" action="" id="formExcluirCliente">
                        <input type="hidden" name="id" value="<?php echo $cliente_detalhes['id']; ?>">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formExcluirCliente" name="excluir" class="btn btn-danger">Excluir Cliente</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Nova Venda -->
    <div class="modal fade" id="modalNovaVenda" tabindex="-1" aria-labelledby="modalNovaVendaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovaVendaLabel">Nova Venda para <?php echo $cliente_detalhes['nome']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Para iniciar uma nova venda para este cliente, você será redirecionado para o PDV.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="pdv.php?cliente_id=<?php echo $cliente_detalhes['id']; ?>" class="btn btn-primary">Ir para PDV</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Histórico de Vendas -->
    <div class="modal fade" id="modalHistoricoVendas" tabindex="-1" aria-labelledby="modalHistoricoVendasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalHistoricoVendasLabel">Histórico de Vendas - <?php echo $cliente_detalhes['nome']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT v.id, v.data_venda, v.valor_total, v.forma_pagamento, v.status, 
                        DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada
                        FROM vendas v
                        WHERE v.cliente_id = :cliente_id
                        ORDER BY v.data_venda DESC
                        LIMIT 20
                    ");
                    $stmt->bindParam(':cliente_id', $cliente_detalhes['id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $vendas_completo = $stmt->fetchAll();
                    
                    if (count($vendas_completo) > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Forma de Pagamento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendas_completo as $v): ?>
                                <tr>
                                    <td><?php echo $v['id']; ?></td>
                                    <td><?php echo $v['data_formatada']; ?></td>
                                    <td><?php echo formatarDinheiro($v['valor_total']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $v['forma_pagamento'])); ?></td>
                                    <td>
                                        <?php
                                        if ($v['status'] == 'finalizada') {
                                            echo '<span class="badge bg-success">Finalizada</span>';
                                        } else if ($v['status'] == 'cancelada') {
                                            echo '<span class="badge bg-danger">Cancelada</span>';
                                        } else {
                                            echo '<span class="badge bg-warning">Pendente</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="vendas.php?id=<?php echo $v['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        Este cliente não possui vendas registradas ainda.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Listagem de Clientes -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF/CNPJ</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Cidade/UF</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $clientes = $cliente->listar();
                        foreach ($clientes as $c) {
                            echo '<tr>';
                            echo '<td>'.$c['id'].'</td>';
                            echo '<td>'.$c['nome'].'</td>';
                            echo '<td>'.$c['cpf_cnpj'].'</td>';
                            echo '<td>'.$c['telefone'].'</td>';
                            echo '<td>'.$c['email'].'</td>';
                            echo '<td>'.($c['cidade'] ? $c['cidade'].'/'.$c['estado'] : '-').'</td>';
                            
                            // Ações
                            echo '<td>';
                            echo '<a href="?id='.$c['id'].'" class="btn btn-sm btn-info me-1" title="Detalhes">
                                    <i class="fas fa-eye"></i>
                                  </a>';
                                  
                            // Verificar se o cliente tem comanda aberta
                            $cmd_aberta = $comanda->verificarComandaAberta($c['id']);
                            if ($cmd_aberta) {
                                echo '<a href="comandas.php?id='.$cmd_aberta['id'].'" class="btn btn-sm btn-success me-1" title="Ver Comanda Aberta">
                                        <i class="fas fa-clipboard-list"></i>
                                      </a>';
                            } else {
                                echo '<a href="comanda_cliente.php?cliente_id='.$c['id'].'" class="btn btn-sm btn-primary me-1" title="Comandas">
                                        <i class="fas fa-clipboard-list"></i>
                                      </a>';
                            }
                            
                            echo '<a href="pdv.php?cliente_id='.$c['id'].'" class="btn btn-sm btn-secondary me-1" title="Vender">
                                    <i class="fas fa-shopping-cart"></i>
                                  </a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Adicionar Cliente -->
    <div class="modal fade" id="modalAdicionarCliente" tabindex="-1" aria-labelledby="modalAdicionarClienteLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAdicionarClienteLabel">Novo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="formAdicionarCliente">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="nome_add" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome_add" name="nome" required>
                            </div>
                            <div class="col-md-4">
                                <label for="cpf_cnpj_add" class="form-label">CPF/CNPJ</label>
                                <input type="text" class="form-control" id="cpf_cnpj_add" name="cpf_cnpj">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email_add" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email_add" name="email">
                            </div>
                            <div class="col-md-6">
                                <label for="telefone_add" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone_add" name="telefone">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="endereco_add" class="form-label">Endereço</label>
                            <input type="text" class="form-control" id="endereco_add" name="endereco">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cidade_add" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade_add" name="cidade">
                            </div>
                            <div class="col-md-3">
                                <label for="estado_add" class="form-label">Estado</label>
                                <select class="form-select" id="estado_add" name="estado">
                                    <option value="">Selecione</option>
                                    <?php
                                    foreach ($estados as $sigla => $nome) {
                                        echo "<option value=\"{$sigla}\">{$nome}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="cep_add" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep_add" name="cep">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes_add" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes_add" name="observacoes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formAdicionarCliente" name="adicionar" class="btn btn-primary">Salvar Cliente</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('.datatable');
    if (table) {
        // Destruir se já existir uma instância DataTable
        if ($.fn.DataTable.isDataTable(table)) {
            $(table).DataTable().destroy();
        }
        
        // Inicializar novamente
        new DataTable(table, {
            language: {
                url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            }
        });
    }
});
    
    // Máscaras para CPF/CNPJ e CEP
    const cpfCnpjInputs = document.querySelectorAll('[id$="cpf_cnpj"], [id$="cpf_cnpj_add"]');
    cpfCnpjInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length <= 11) {
                // Formatar como CPF
                if (value.length > 9) {
                    value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
                } else if (value.length > 6) {
                    value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
                } else if (value.length > 3) {
                    value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
                }
            } else {
                // Formatar como CNPJ
                if (value.length > 12) {
                    value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})/, '$1.$2.$3/$4-$5');
                } else if (value.length > 8) {
                    value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{1,4})/, '$1.$2.$3/$4');
                } else if (value.length > 5) {
                    value = value.replace(/(\d{2})(\d{3})(\d{1,3})/, '$1.$2.$3');
                } else if (value.length > 2) {
                    value = value.replace(/(\d{2})(\d{1,3})/, '$1.$2');
                }
            }
            this.value = value;
        });
    });
    
    const cepInputs = document.querySelectorAll('[id$="cep"], [id$="cep_add"]');
    cepInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.replace(/(\d{5})(\d{1,3})/, '$1-$2');
            }
            this.value = value;
        });
    });
    
    const telefoneInputs = document.querySelectorAll('[id$="telefone"], [id$="telefone_add"]');
    telefoneInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length === 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length === 10) {
                value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{0,5})(\d{0,4})/, '($1) $2$3');
            } else if (value.length > 0) {
                value = value.replace(/(\d{0,2})/, '($1');
            }
            this.value = value;
        });
    });
});
</script>

<?php include 'footer.php'; ?>