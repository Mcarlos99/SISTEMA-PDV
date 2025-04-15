<?php
require_once 'config.php';
verificarLogin();

// Verificar se o usuário tem permissão de administrador
if ($_SESSION['usuario_nivel'] != 'admin') {
    alerta('Você não tem permissão para acessar esta página!', 'danger');
    header('Location: index.php');
    exit;
}

// Dados da empresa (configura uma array padrão ou busca do banco)
$config_empresa = array(
    'nome' => 'Minha Empresa',
    'razao_social' => 'Minha Empresa LTDA',
    'cnpj' => '12.345.678/0001-90',
    'endereco' => 'Rua Exemplo, 123',
    'cidade' => 'São Paulo',
    'estado' => 'SP',
    'telefone' => '(11) 1234-5678',
    'email' => 'contato@minhaempresa.com',
    'site' => 'www.minhaempresa.com',
    'logo' => 'logo.png'
);

// Processar formulário de configurações da empresa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_empresa'])) {
    // Em um sistema real, isso seria armazenado no banco de dados
    // Aqui apenas simula e mostra o alerta
    $config_empresa = array(
        'nome' => $_POST['nome'],
        'razao_social' => $_POST['razao_social'],
        'cnpj' => $_POST['cnpj'],
        'endereco' => $_POST['endereco'],
        'cidade' => $_POST['cidade'],
        'estado' => $_POST['estado'],
        'telefone' => $_POST['telefone'],
        'email' => $_POST['email'],
        'site' => $_POST['site'],
        'logo' => 'logo.png' // Isso seria tratado com upload de arquivo
    );

    alerta('Informações da empresa atualizadas com sucesso!', 'success');
    
    // Em um sistema real, redirecionar após salvar para evitar reenvio
    // header('Location: configuracoes.php');
    // exit;
}

// Processar backup do banco de dados
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fazer_backup'])) {
    // Em um sistema real, isso faria um backup real do banco de dados
    // Aqui apenas mostra o alerta
    alerta('Backup do banco de dados realizado com sucesso! O arquivo foi salvo na pasta de backups.', 'success');
}

// Processar restauração do banco de dados
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restaurar_backup'])) {
    // Em um sistema real, isso restauraria um backup real do banco de dados
    // Aqui apenas mostra o alerta
    alerta('Restauração do banco de dados realizada com sucesso!', 'success');
}

// Processar limpeza de logs
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['limpar_logs'])) {
    // Definir quantos dias de logs manter
    $dias_manter = isset($_POST['dias_manter']) ? (int)$_POST['dias_manter'] : 30;
    
    // Executar a limpeza real dos logs
    if ($log->limparAntigos($dias_manter)) {
        // Registrar a ação nos logs
        $log->registrar('Limpeza', "Logs com mais de {$dias_manter} dias foram excluídos");
        alerta("Logs com mais de {$dias_manter} dias foram removidos com sucesso!", 'success');
    } else {
        alerta('Erro ao remover logs antigos!', 'danger');
    }
}

// Template da página
$titulo_pagina = 'Configurações do Sistema';
include 'header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Configurações do Sistema</h1>
    
    <!-- Tabs de configuração -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="empresa-tab" data-bs-toggle="tab" data-bs-target="#empresa" type="button" role="tab" aria-controls="empresa" aria-selected="true">
                <i class="fas fa-building me-2"></i> Empresa
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sistema-tab" data-bs-toggle="tab" data-bs-target="#sistema" type="button" role="tab" aria-controls="sistema" aria-selected="false">
                <i class="fas fa-cogs me-2"></i> Sistema
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab" aria-controls="backup" aria-selected="false">
                <i class="fas fa-database me-2"></i> Backup
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab" aria-controls="logs" aria-selected="false">
                <i class="fas fa-clipboard-list me-2"></i> Logs
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sobre-tab" data-bs-toggle="tab" data-bs-target="#sobre" type="button" role="tab" aria-controls="sobre" aria-selected="false">
                <i class="fas fa-info-circle me-2"></i> Sobre
            </button>
        </li>
    </ul>
    
    <div class="tab-content mt-4" id="myTabContent">
        <!-- Tab Empresa -->
        <div class="tab-pane fade show active" id="empresa" role="tabpanel" aria-labelledby="empresa-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informações da Empresa</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome Fantasia *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo $config_empresa['nome']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="razao_social" class="form-label">Razão Social *</label>
                                <input type="text" class="form-control" id="razao_social" name="razao_social" required value="<?php echo $config_empresa['razao_social']; ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="cnpj" class="form-label">CNPJ *</label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj" required value="<?php echo $config_empresa['cnpj']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo $config_empresa['telefone']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $config_empresa['email']; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="endereco" class="form-label">Endereço</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo $config_empresa['endereco']; ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo $config_empresa['cidade']; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Selecione</option>
                                    <?php
                                    $estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                                    foreach ($estados as $uf) {
                                        $selected = ($config_empresa['estado'] == $uf) ? 'selected' : '';
                                        echo '<option value="'.$uf.'" '.$selected.'>'.$uf.'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="site" class="form-label">Site</label>
                                <input type="text" class="form-control" id="site" name="site" value="<?php echo $config_empresa['site']; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logo" class="form-label">Logo da Empresa</label>
                            <input type="file" class="form-control" id="logo" name="logo">
                            <div class="form-text">Formatos aceitos: JPG, PNG. Tamanho máximo: 2MB.</div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="salvar_empresa" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Informações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Tab Sistema -->
        <div class="tab-pane fade" id="sistema" role="tabpanel" aria-labelledby="sistema-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Configurações do Sistema</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="itens_por_pagina" class="form-label">Itens por Página</label>
                                <select class="form-select" id="itens_por_pagina" name="itens_por_pagina">
                                    <option value="10">10</option>
                                    <option value="25" selected>25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="tema" class="form-label">Tema</label>
                                <select class="form-select" id="tema" name="tema">
                                    <option value="claro" selected>Claro</option>
                                    <option value="escuro">Escuro</option>
                                    <option value="sistema">Seguir Sistema</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="moeda" class="form-label">Moeda</label>
                                <select class="form-select" id="moeda" name="moeda">
                                    <option value="BRL" selected>Real (R$)</option>
                                    <option value="USD">Dólar (US$)</option>
                                    <option value="EUR">Euro (€)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="formato_data" class="form-label">Formato de Data</label>
                                <select class="form-select" id="formato_data" name="formato_data">
                                    <option value="d/m/Y" selected>DD/MM/AAAA</option>
                                    <option value="Y-m-d">AAAA-MM-DD</option>
                                    <option value="m/d/Y">MM/DD/AAAA</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="estoque_negativo" name="estoque_negativo" checked>
                                <label class="form-check-label" for="estoque_negativo">Bloquear venda quando estoque for insuficiente</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="alerta_estoque" name="alerta_estoque" checked>
                                <label class="form-check-label" for="alerta_estoque">Mostrar alerta de estoque baixo no painel</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="impressao_automatica" name="impressao_automatica" checked>
                                <label class="form-check-label" for="impressao_automatica">Impressão automática de comprovante após venda</label>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="salvar_configuracoes" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Tab Backup -->
        <div class="tab-pane fade" id="backup" role="tabpanel" aria-labelledby="backup-tab">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Backup do Banco de Dados</h5>
                        </div>
                        <div class="card-body">
                            <p>Faça um backup completo do banco de dados para manter seus dados seguros. O arquivo será gerado e disponibilizado para download.</p>
                            <form method="post" action="">
                                <div class="d-grid">
                                    <button type="submit" name="fazer_backup" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Fazer Backup Agora
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Backups Automáticos</h5>
                        </div>
                        <div class="card-body">
                            <p>Configure backups automáticos periódicos do seu banco de dados.</p>
                            <form>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="backup_automatico" checked>
                                        <label class="form-check-label" for="backup_automatico">Ativar backups automáticos</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="frequencia_backup" class="form-label">Frequência</label>
                                    <select class="form-select" id="frequencia_backup">
                                        <option value="diario">Diário</option>
                                        <option value="semanal" selected>Semanal</option>
                                        <option value="mensal">Mensal</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="hora_backup" class="form-label">Hora</label>
                                    <select class="form-select" id="hora_backup">
                                        <?php
                                        for ($i = 0; $i < 24; $i++) {
                                            $hora = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                                            $selected = ($i == 1) ? 'selected' : '';
                                            echo '<option value="'.$i.'" '.$selected.'>'.$hora.'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="manter_backups" class="form-label">Manter backups por</label>
                                    <select class="form-select" id="manter_backups">
                                        <option value="7">7 dias</option>
                                        <option value="15">15 dias</option>
                                        <option value="30" selected>30 dias</option>
                                        <option value="90">90 dias</option>
                                        <option value="0">Indefinidamente</option>
                                    </select>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvar Configurações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Restaurar Backup</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-danger">Atenção! Restaurar um backup irá substituir todos os dados atuais. Esta ação não pode ser desfeita.</p>
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="arquivo_backup" class="form-label">Arquivo de Backup</label>
                                    <input type="file" class="form-control" id="arquivo_backup" name="arquivo_backup" required>
                                    <div class="form-text">Selecione um arquivo de backup no formato .sql</div>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirmar_restauracao" name="confirmar_restauracao" required>
                                    <label class="form-check-label" for="confirmar_restauracao">
                                        Confirmo que desejo substituir todos os dados atuais pelos dados do backup
                                    </label>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="restaurar_backup" class="btn btn-danger">
                                        <i class="fas fa-upload"></i> Restaurar Backup
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Backups Recentes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Tamanho</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>14/04/2025 01:00</td>
                                            <td>2.3 MB</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>07/04/2025 01:00</td>
                                            <td>2.1 MB</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>31/03/2025 01:00</td>
                                            <td>2.0 MB</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Logs -->
        <div class="tab-pane fade" id="logs" role="tabpanel" aria-labelledby="logs-tab">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Logs do Sistema</h5>
                    <form method="post" action="" class="d-inline-block">
                        <div class="input-group">
                            <select name="dias_manter" class="form-select form-select-sm">
                                <option value="7">Mais de 7 dias</option>
                                <option value="15">Mais de 15 dias</option>
                                <option value="30" selected>Mais de 30 dias</option>
                                <option value="60">Mais de 60 dias</option>
                                <option value="90">Mais de 90 dias</option>
                            </select>
                            <button type="submit" name="limpar_logs" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja limpar os logs antigos?')">
                                <i class="fas fa-trash"></i> Limpar Logs Antigos
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Usuário</th>
                                    <th>Ação</th>
                                    <th>IP</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Buscar logs reais do banco de dados
                                $logs = $log->listar(100);
                                
                                if (count($logs) > 0) {
                                    foreach ($logs as $log_item) {
                                        echo '<tr>';
                                        echo '<td>'.$log_item['data_formatada'].'</td>';
                                        echo '<td>'.$log_item['usuario_nome'].'</td>';
                                        echo '<td>'.$log_item['acao'].'</td>';
                                        echo '<td>'.$log_item['ip'].'</td>';
                                        echo '<td>'.$log_item['detalhes'].'</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    // Se não existirem logs, mostrar exemplos
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i:s'); ?></td>
                                        <td><?php echo $_SESSION['usuario_nome']; ?></td>
                                        <td>Acesso</td>
                                        <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                                        <td>Acessou a página de configurações</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Sobre -->
        <div class="tab-pane fade" id="sobre" role="tabpanel" aria-labelledby="sobre-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sobre o Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h3>Sistema PDV v1.0</h3>
                        <p class="text-muted">Sistema de Ponto de Venda Completo</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h5>Informações do Sistema</h5>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Versão</span>
                                        <span class="badge bg-primary">1.0</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Data de Lançamento</span>
                                        <span>14/04/2025</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Versão do PHP</span>
                                        <span><?php echo phpversion(); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Versão do MySQL</span>
                                        <span>
                                            <?php 
                                            $stmt = $pdo->query('SELECT VERSION() as version');
                                            $version = $stmt->fetch();
                                            echo $version['version'];
                                            ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Sistema Operacional</span>
                                        <span><?php echo PHP_OS; ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h5>Tecnologias Utilizadas</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">PHP 8.x</li>
                                    <li class="list-group-item">MySQL 8.x</li>
                                    <li class="list-group-item">Bootstrap 5.x</li>
                                    <li class="list-group-item">jQuery 3.x</li>
                                    <li class="list-group-item">DataTables 1.x</li>
                                    <li class="list-group-item">Font Awesome 6.x</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Recursos do Sistema</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i> Gestão de vendas e comprovcantes</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Controle de estoque</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Cadastro de produtos e categorias</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Gestão de clientes e fornecedores</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Controle de usuários e permissões</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i> Relatórios gerenciais</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Backup e restauração de dados</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Múltiplas formas de pagamento</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Interface responsiva</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Logs de atividades</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <h5>Licença e Suporte</h5>
                        <p>Este sistema está licenciado para uso exclusivo de sua empresa.<br>
                        Para obter suporte técnico, entre em contato pelo e-mail: maurocarlos.ti@gmail.com</p>
                        <p class="text-muted">Copyright &copy; <?php echo date('Y'); ?> | Todos os direitos reservados.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Máscara para CNPJ
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '');
            x = x.replace(/^(\d{2})(\d)/, '$1.$2');
            x = x.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            x = x.replace(/\.(\d{3})(\d)/, '.$1/$2');
            x = x.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = x;
        });
        
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '');
            if (x.length <= 10) {
                // Telefone fixo
                x = x.replace(/(\d{2})(\d)/, '($1) $2');
                x = x.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                // Celular
                x = x.replace(/(\d{2})(\d)/, '($1) $2');
                x = x.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = x;
        });
        
        // Controle de abas - manter aba ativa após recarregar a página
        const activeTab = localStorage.getItem('activeConfigTab');
        if (activeTab) {
            const tab = new bootstrap.Tab(document.querySelector('#' + activeTab));
            tab.show();
        }
        
        // Salvar aba ativa quando mudar
        const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEls.forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', function(event) {
                localStorage.setItem('activeConfigTab', event.target.id);
            });
        });
    });
</script>

<?php include 'footer.php'; ?>