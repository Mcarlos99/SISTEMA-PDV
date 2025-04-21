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
$fornecedor_obj = new Fornecedor($pdo);

// Processar ações
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// Processar adição de fornecedor
if ($acao == 'adicionar' && isset($_POST['nome'])) {
    try {
        $nome = trim($_POST['nome']);
        $cpf_cnpj = isset($_POST['cpf_cnpj']) ? trim($_POST['cpf_cnpj']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
        $endereco = isset($_POST['endereco']) ? trim($_POST['endereco']) : '';
        $cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $cep = isset($_POST['cep']) ? trim($_POST['cep']) : '';
        $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';
        
        if (empty($nome)) {
            throw new Exception("O nome do fornecedor é obrigatório");
        }
        
        // Validar CPF/CNPJ se preenchido
        if (!empty($cpf_cnpj)) {
            // Remover caracteres não numéricos
            $cpf_cnpj = preg_replace('/[^0-9]/', '', $cpf_cnpj);
            
            // Verificar se já existe fornecedor com este CPF/CNPJ
            $fornecedor_existente = $fornecedor_obj->buscarPorCpfCnpj($cpf_cnpj);
            if ($fornecedor_existente) {
                throw new Exception("Já existe um fornecedor cadastrado com este CPF/CNPJ");
            }
        }
        
        $dados = [
            'nome' => $nome,
            'cpf_cnpj' => $cpf_cnpj,
            'email' => $email,
            'telefone' => $telefone,
            'endereco' => $endereco,
            'cidade' => $cidade,
            'estado' => $estado,
            'cep' => $cep,
            'observacoes' => $observacoes
        ];
        
        $resultado = $fornecedor_obj->adicionar($dados);
        
        if ($resultado) {
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Fornecedor', 
                    "Fornecedor '{$nome}' adicionado ao sistema"
                );
            }
            
            alerta('Fornecedor adicionado com sucesso!', 'success');
            
            // Verificar se veio de outra página para retornar
            if (isset($_POST['retorno']) && !empty($_POST['retorno'])) {
                header('Location: ' . $_POST['retorno']);
                exit;
            }
            
            header('Location: fornecedores.php');
            exit;
        } else {
            throw new Exception("Erro ao adicionar fornecedor");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar atualização de fornecedor
if ($acao == 'atualizar' && isset($_POST['id'], $_POST['nome'])) {
    try {
        $id = intval($_POST['id']);
        $nome = trim($_POST['nome']);
        $cpf_cnpj = isset($_POST['cpf_cnpj']) ? trim($_POST['cpf_cnpj']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
        $endereco = isset($_POST['endereco']) ? trim($_POST['endereco']) : '';
        $cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $cep = isset($_POST['cep']) ? trim($_POST['cep']) : '';
        $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';
        
        if (empty($nome)) {
            throw new Exception("O nome do fornecedor é obrigatório");
        }
        
        // Validar CPF/CNPJ se preenchido
        if (!empty($cpf_cnpj)) {
            // Remover caracteres não numéricos
            $cpf_cnpj = preg_replace('/[^0-9]/', '', $cpf_cnpj);
            
            // Verificar se já existe outro fornecedor com este CPF/CNPJ
            $fornecedor_existente = $fornecedor_obj->buscarPorCpfCnpj($cpf_cnpj);
            if ($fornecedor_existente && $fornecedor_existente['id'] != $id) {
                throw new Exception("Já existe um fornecedor cadastrado com este CPF/CNPJ");
            }
        }
        
        $dados = [
            'nome' => $nome,
            'cpf_cnpj' => $cpf_cnpj,
            'email' => $email,
            'telefone' => $telefone,
            'endereco' => $endereco,
            'cidade' => $cidade,
            'estado' => $estado,
            'cep' => $cep,
            'observacoes' => $observacoes
        ];
        
        $resultado = $fornecedor_obj->atualizar($id, $dados);
        
        if ($resultado) {
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Fornecedor', 
                    "Fornecedor '{$nome}' (ID: {$id}) atualizado"
                );
            }
            
            alerta('Fornecedor atualizado com sucesso!', 'success');
            header('Location: fornecedores.php');
            exit;
        } else {
            throw new Exception("Erro ao atualizar fornecedor");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Processar exclusão de fornecedor
if ($acao == 'excluir' && isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);
        $fornecedor = $fornecedor_obj->buscarPorId($id);
        
        if (!$fornecedor) {
            throw new Exception("Fornecedor não encontrado");
        }
        
        // Verificar se o fornecedor tem compras associadas
        // Esta verificação já é feita dentro do método excluir da classe Fornecedor,
        // mas fazemos aqui também para garantir
        $resultado = $fornecedor_obj->excluir($id);
        
        if ($resultado) {
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Fornecedor', 
                    "Fornecedor '{$fornecedor['nome']}' (ID: {$id}) excluído"
                );
            }
            
            alerta('Fornecedor excluído com sucesso!', 'success');
            header('Location: fornecedores.php');
            exit;
        } else {
            throw new Exception("Não é possível excluir este fornecedor porque ele tem compras associadas");
        }
    } catch (Exception $e) {
        alerta($e->getMessage(), 'danger');
    }
}

// Verificar se está visualizando um fornecedor específico
$fornecedor = null;
$historico_compras = [];
if (isset($_GET['id'])) {
    $fornecedor_id = intval($_GET['id']);
    $fornecedor = $fornecedor_obj->buscarPorId($fornecedor_id);
    
    if ($fornecedor) {
        // Buscar histórico de compras do fornecedor
        $compra_obj = new Compra($pdo);
        $historico_compras = $compra_obj->listarComprasFornecedor($fornecedor_id);
    }
}

// Template da página
$titulo_pagina = 'Gerenciamento de Fornecedores - EXTREME PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-truck me-2 text-primary"></i>
                <?php echo $fornecedor ? 'Editar Fornecedor: ' . esc($fornecedor['nome']) : 'Gerenciamento de Fornecedores'; ?>
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <?php if ($fornecedor): ?>
                        <li class="breadcrumb-item"><a href="fornecedores.php">Fornecedores</a></li>
                        <li class="breadcrumb-item active">Editar Fornecedor</li>
                    <?php else: ?>
                        <li class="breadcrumb-item active">Fornecedores</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
        
        <div>
            <?php if (!$fornecedor): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoFornecedor">
                    <i class="fas fa-plus-circle me-1"></i>
                    Novo Fornecedor
                </button>
            <?php else: ?>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a href="fornecedores.php" class="btn btn-secondary mb-2 mb-sm-0">
                        <i class="fas fa-arrow-left me-1"></i>
                        Voltar para Lista
                    </a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalExcluirFornecedor">
                        <i class="fas fa-trash-alt me-1"></i>
                        Excluir Fornecedor
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- PARTE 2 -->
    <?php if ($fornecedor): ?>
        <!-- Formulário de Edição -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Editar Informações do Fornecedor
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="fornecedores.php?acao=atualizar" method="post">
                            <input type="hidden" name="id" value="<?php echo $fornecedor['id']; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="nome" class="form-label fw-bold">Nome/Razão Social:</label>
                                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo esc($fornecedor['nome']); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="cpf_cnpj" class="form-label fw-bold">CPF/CNPJ:</label>
                                    <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo esc($fornecedor['cpf_cnpj']); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-bold">Email:</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo esc($fornecedor['email']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="telefone" class="form-label fw-bold">Telefone:</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo esc($fornecedor['telefone']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="endereco" class="form-label fw-bold">Endereço:</label>
                                <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo esc($fornecedor['endereco']); ?>">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label for="cidade" class="form-label fw-bold">Cidade:</label>
                                    <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo esc($fornecedor['cidade']); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="estado" class="form-label fw-bold">Estado:</label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="">Selecione</option>
                                        <?php
                                        $estados = [
                                            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                            'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                            'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                                            'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                            'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                            'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                            'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                        ];
                                        
                                        foreach ($estados as $sigla => $nome) {
                                            $selected = ($fornecedor['estado'] == $sigla) ? 'selected' : '';
                                            echo '<option value="' . $sigla . '" ' . $selected . '>' . $nome . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="cep" class="form-label fw-bold">CEP:</label>
                                    <input type="text" class="form-control" id="cep" name="cep" value="<?php echo esc($fornecedor['cep']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observacoes" class="form-label fw-bold">Observações:</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="4"><?php echo esc($fornecedor['observacoes']); ?></textarea>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-end">
                                <a href="fornecedores.php" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-1"></i>
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informações do Fornecedor
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Data de Cadastro:</span>
                                <span><?php echo isset($fornecedor['criado_em']) ? formatarData($fornecedor['criado_em']) : 'N/A'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Última Atualização:</span>
                                <span><?php echo isset($fornecedor['atualizado_em']) ? formatarData($fornecedor['atualizado_em']) : 'N/A'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Total de Compras:</span>
                                <span class="badge bg-primary rounded-pill"><?php echo count($historico_compras); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Histórico de Compras -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-shopping-basket me-2"></i>
                            Histórico de Compras
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($historico_compras)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                                <p class="mb-0">Nenhuma compra registrada com este fornecedor.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="tabelahistorico">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Data</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historico_compras as $compra): ?>
                                            <tr>
                                                <td><?php echo $compra['id']; ?></td>
                                                <td><?php echo $compra['data_formatada']; ?></td>
                                                <td><?php echo formatarDinheiro($compra['valor_total']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_badges = [
                                                        'finalizada' => '<span class="badge bg-success">Finalizada</span>',
                                                        'pendente' => '<span class="badge bg-warning text-dark">Pendente</span>',
                                                        'cancelada' => '<span class="badge bg-danger">Cancelada</span>'
                                                    ];
                                                    echo $status_badges[$compra['status']] ?? $compra['status'];
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="compras.php?id=<?php echo $compra['id']; ?>" class="btn btn-sm btn-info text-white" data-bs-toggle="tooltip" title="Ver Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Lista de Fornecedores -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Lista de Fornecedores
                        </h5>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" id="buscarFornecedor" placeholder="Buscar fornecedor...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0" id="tabelaFornecedores">
                        <thead>
                            <tr>
                                <th width="80">ID</th>
                                <th data-priority="1">Nome</th>
                                <th data-priority="3">CPF/CNPJ</th>
                                <th data-priority="3">E-mail</th>
                                <th data-priority="2">Telefone</th>
                                <th data-priority="3">Cidade/UF</th>
                                <th data-priority="1" width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $fornecedores = $fornecedor_obj->listar();
                            if (empty($fornecedores)): 
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhum fornecedor cadastrado.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($fornecedores as $f): ?>
                                    <tr>
                                        <td><?php echo $f['id']; ?></td>
                                        <td><?php echo esc($f['nome']); ?></td>
                                        <td><?php echo !empty($f['cpf_cnpj']) ? esc($f['cpf_cnpj']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td><?php echo !empty($f['email']) ? esc($f['email']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td><?php echo !empty($f['telefone']) ? esc($f['telefone']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td>
                                            <?php 
                                            $cidade_uf = '';
                                            if (!empty($f['cidade'])) {
                                                $cidade_uf .= esc($f['cidade']);
                                                if (!empty($f['estado'])) {
                                                    $cidade_uf .= '/' . esc($f['estado']);
                                                }
                                            } elseif (!empty($f['estado'])) {
                                                $cidade_uf .= esc($f['estado']);
                                            }
                                            echo !empty($cidade_uf) ? $cidade_uf : '<span class="text-muted">-</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="fornecedores.php?id=<?php echo $f['id']; ?>" 
                                                   class="btn btn-primary" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Editar"
                                                   style="display: inline-block !important; background-color: #0d6efd !important;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <a href="#" 
                                                   class="btn btn-danger btn-excluir-fornecedor" 
                                                   data-id="<?php echo $f['id']; ?>"
                                                   data-nome="<?php echo esc($f['nome']); ?>"
                                                   data-bs-toggle="tooltip" 
                                                   title="Excluir"
                                                   style="display: inline-block !important; background-color: #dc3545 !important;">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- PARTE 3  -->
    </div>

<!-- Modal Novo Fornecedor -->
<div class="modal fade" id="modalNovoFornecedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Novo Fornecedor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="fornecedores.php?acao=adicionar" method="post">
                <?php if (isset($_SERVER['HTTP_REFERER'])): ?>
                    <input type="hidden" name="retorno" value="<?php echo $_SERVER['HTTP_REFERER']; ?>">
                <?php endif; ?>
                
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="nome_novo" class="form-label fw-bold">Nome/Razão Social:</label>
                            <input type="text" class="form-control" id="nome_novo" name="nome" required>
                        </div>
                        <div class="col-md-4">
                            <label for="cpf_cnpj_novo" class="form-label fw-bold">CPF/CNPJ:</label>
                            <input type="text" class="form-control" id="cpf_cnpj_novo" name="cpf_cnpj">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email_novo" class="form-label fw-bold">Email:</label>
                            <input type="email" class="form-control" id="email_novo" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="telefone_novo" class="form-label fw-bold">Telefone:</label>
                            <input type="text" class="form-control" id="telefone_novo" name="telefone">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="endereco_novo" class="form-label fw-bold">Endereço:</label>
                        <input type="text" class="form-control" id="endereco_novo" name="endereco">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label for="cidade_novo" class="form-label fw-bold">Cidade:</label>
                            <input type="text" class="form-control" id="cidade_novo" name="cidade">
                        </div>
                        <div class="col-md-3">
                            <label for="estado_novo" class="form-label fw-bold">Estado:</label>
                            <select class="form-select" id="estado_novo" name="estado">
                                <option value="">Selecione</option>
                                <?php
                                $estados = [
                                    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                    'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                    'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                                    'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                    'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                    'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                    'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                ];
                                
                                foreach ($estados as $sigla => $nome) {
                                    echo '<option value="' . $sigla . '">' . $nome . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="cep_novo" class="form-label fw-bold">CEP:</label>
                            <input type="text" class="form-control" id="cep_novo" name="cep">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes_novo" class="form-label fw-bold">Observações:</label>
                        <textarea class="form-control" id="observacoes_novo" name="observacoes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Salvar Fornecedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmação Excluir Fornecedor -->
<div class="modal fade" id="modalExcluirFornecedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($fornecedor): ?>
                    <p>Tem certeza que deseja excluir o fornecedor <strong><?php echo esc($fornecedor['nome']); ?></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Esta ação não poderá ser desfeita e só é possível se o fornecedor não tiver compras associadas.
                    </div>
                <?php else: ?>
                    <p>Selecione um fornecedor para excluir.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <?php if ($fornecedor): ?>
                    <a href="fornecedores.php?acao=excluir&id=<?php echo $fornecedor['id']; ?>" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>
                        Confirmar Exclusão
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmação Excluir (para botão na lista) -->
<div class="modal fade" id="modalConfirmExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o fornecedor <strong id="fornecedorNome"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Esta ação não poderá ser desfeita e só é possível se o fornecedor não tiver compras associadas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <a href="#" id="btnConfirmExcluir" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-1"></i>
                    Confirmar Exclusão
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Garantir que botões de ação em tabelas responsivas mantenham aparência correta */
    .datatable .btn {
        display: inline-block !important;
    }
    
    /* Forçar cores de background nos botões de ação */
    .datatable .btn-info {
        background-color: #0dcaf0 !important;
        border-color: #0dcaf0 !important;
    }
    
    .datatable .btn-primary {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    
    .datatable .btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    
    /* Garantir que botões em linhas expandidas mantenham estilo */
    .dtr-details .btn {
        display: inline-block !important;
        margin: 0.1rem;
    }
    
    /* Manter cor do texto nos botões */
    .datatable .btn-info.text-white {
        color: #fff !important;
    }
</style>

<script>
    $(document).ready(function() {
        // Não inicializamos DataTables aqui porque já está sendo inicializado no footer.php
        // O id da tabela já está na lista de exclusão: '#tabelaFornecedores'
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Filtro de busca rápida para tabela de fornecedores
        $('#buscarFornecedor').on('keyup', function() {
            $('#tabelaFornecedores').DataTable().search($(this).val()).draw();
        });
        
        // Máscara para campos de CPF/CNPJ
        if (typeof $.fn.mask !== 'undefined') {
            var CPFMaskBehavior = function(val) {
                return val.replace(/\D/g, '').length <= 11 ? '000.000.000-00' : '00.000.000/0000-00';
            };
            
            var cpfOptions = {
                onKeyPress: function(val, e, field, options) {
                    field.mask(CPFMaskBehavior.apply({}, arguments), options);
                }
            };
            
            $('#cpf_cnpj, #cpf_cnpj_novo').mask(CPFMaskBehavior, cpfOptions);
            
            // Máscara para telefone
            var SPMaskBehavior = function(val) {
                return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
            };
            
            var spOptions = {
                onKeyPress: function(val, e, field, options) {
                    field.mask(SPMaskBehavior.apply({}, arguments), options);
                }
            };
            
            $('#telefone, #telefone_novo').mask(SPMaskBehavior, spOptions);
            
            // Máscara para CEP
            $('#cep, #cep_novo').mask('00000-000');
        }
        
        // Manipular exclusão de fornecedor
        $('.btn-excluir-fornecedor').on('click', function(e) {
            e.preventDefault();
            
            var id = $(this).data('id');
            var nome = $(this).data('nome');
            
            $('#fornecedorNome').text(nome);
            $('#btnConfirmExcluir').attr('href', 'fornecedores.php?acao=excluir&id=' + id);
            
            var modalExcluir = new bootstrap.Modal(document.getElementById('modalConfirmExcluir'));
            modalExcluir.show();
        });
        
        // Busca de CEP via API ViaCEP
        function pesquisaCEP(cep, sufixo = '') {
            // Remove tudo que não é número
            cep = cep.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                return false;
            }
            
            // Fazer a requisição AJAX para a API ViaCEP
            $.getJSON("https://viacep.com.br/ws/" + cep + "/json/?callback=?", function(dados) {
                if (!("erro" in dados)) {
                    // Preencher os campos com os dados retornados
                    $("#endereco" + sufixo).val(dados.logradouro);
                    $("#cidade" + sufixo).val(dados.localidade);
                    $("#estado" + sufixo).val(dados.uf);
                } else {
                    // CEP não encontrado
                    alert("CEP não encontrado.");
                }
            });
        }
        
        // Evento para busca automática de CEP ao sair do campo
        $("#cep").blur(function() {
            pesquisaCEP($(this).val());
        });
        
        $("#cep_novo").blur(function() {
            pesquisaCEP($(this).val(), '_novo');
        });
    });
</script>

<?php include 'footer.php'; ?>
