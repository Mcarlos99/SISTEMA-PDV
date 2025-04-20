<?php
require_once 'config.php';
verificarLogin();

// Verificar se o usuário tem permissão
if ($_SESSION['usuario_nivel'] != 'admin') {
    alerta('Você não tem permissão para acessar esta página!', 'danger');
    header('Location: index.php');
    exit;
}

// Inicializar objetos
$empresa_obj = new ConfiguracaoEmpresa($pdo);
$sistema_obj = new ConfiguracaoSistema($pdo);

// Carregar configurações atuais
$empresa = $empresa_obj->buscar();
$sistema = $sistema_obj->buscar();

// Processar requisições
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Atualizar configurações da empresa
    if (isset($_POST['atualizar_empresa'])) {
        $dados = [
            'id' => $empresa['id'],
            'nome' => $_POST['nome'],
            'razao_social' => $_POST['razao_social'],
            'cnpj' => $_POST['cnpj'],
            'endereco' => $_POST['endereco'],
            'cidade' => $_POST['cidade'],
            'estado' => $_POST['estado'],
            'telefone' => $_POST['telefone'],
            'email' => $_POST['email'],
            'site' => $_POST['site']
        ];
        
        if ($empresa_obj->atualizar($dados)) {
            alerta('Configurações da empresa atualizadas com sucesso!', 'success');
            
            // Atualizar logo se tiver sido enviado
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $arquivo_temp = $_FILES['logo']['tmp_name'];
                $nome_arquivo = $_FILES['logo']['name'];
                
                // Validar tipo de arquivo
                $tipo_arquivo = $_FILES['logo']['type'];
                $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (in_array($tipo_arquivo, $tipos_permitidos)) {
                    if ($empresa_obj->atualizarLogo($arquivo_temp, $nome_arquivo)) {
                        alerta('Logo atualizado com sucesso!', 'success');
                    } else {
                        alerta('Erro ao atualizar o logo.', 'danger');
                    }
                } else {
                    alerta('Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.', 'danger');
                }
            }
        } else {
            alerta('Erro ao atualizar configurações da empresa.', 'danger');
        }
        
        // Redirecionar para evitar reenvio de formulário
        header('Location: configuracoes.php#empresa');
        exit;
    }
    // PARTE 2
    // Atualizar configurações do sistema
    if (isset($_POST['atualizar_sistema'])) {
        $dados = [
            'id' => $sistema['id'],
            'itens_por_pagina' => intval($_POST['itens_por_pagina']),
            'tema' => $_POST['tema'],
            'moeda' => $_POST['moeda'],
            'formato_data' => $_POST['formato_data'],
            'estoque_negativo' => isset($_POST['estoque_negativo']) ? 1 : 0,
            'alerta_estoque' => isset($_POST['alerta_estoque']) ? 1 : 0,
            'impressao_automatica' => isset($_POST['impressao_automatica']) ? 1 : 0,
            'caixa_obrigatorio' => isset($_POST['caixa_obrigatorio']) ? 1 : 0
        ];
        
        if ($sistema_obj->atualizar($dados)) {
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar('Configuração', "Configurações do sistema atualizadas por " . $_SESSION['usuario_nome']);
            }
            
            alerta('Configurações do sistema atualizadas com sucesso!', 'success');
        } else {
            alerta('Erro ao atualizar configurações do sistema.', 'danger');
        }
        
        // Redirecionar para evitar reenvio de formulário
        header('Location: configuracoes.php#sistema');
        exit;
    }
    
    // Limpar logs
    if (isset($_POST['limpar_logs'])) {
        $dias = isset($_POST['dias_log']) ? intval($_POST['dias_log']) : 30;
        
        if ($GLOBALS['log']->limparAntigos($dias)) {
            alerta("Logs com mais de {$dias} dias excluídos com sucesso!", 'success');
        } else {
            alerta('Erro ao limpar logs antigos.', 'danger');
        }
        
        // Redirecionar para evitar reenvio de formulário
        header('Location: configuracoes.php#manutencao');
        exit;
    }
    
    // Limpar todos os logs
    if (isset($_POST['limpar_todos_logs'])) {
        if ($GLOBALS['log']->limparTodos()) {
            alerta("Todos os logs do sistema foram excluídos com sucesso!", 'success');
        } else {
            alerta('Erro ao limpar todos os logs.', 'danger');
        }
        
        // Redirecionar para evitar reenvio de formulário
        header('Location: configuracoes.php#manutencao');
        exit;
    }
    
    // Backup do banco de dados
    if (isset($_POST['backup_banco'])) {
        // Implementação do backup seria realizada aqui
        // Este é apenas um exemplo e não uma implementação real
        
        alerta("Funcionalidade de backup ainda não implementada.", 'warning');
        
        // Redirecionar para evitar reenvio de formulário
        header('Location: configuracoes.php#manutencao');
        exit;
    }
}

// Template da página
$titulo_pagina = 'Configurações do Sistema - Sistema PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-cog me-2 text-primary"></i>
                Configurações do Sistema
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item active">Configurações</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- PARTE 3 -->
    <!-- Abas de navegação -->
    <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="empresa-tab" data-bs-toggle="tab" data-bs-target="#empresa" 
                    type="button" role="tab" aria-controls="empresa" aria-selected="true">
                <i class="fas fa-building me-2"></i>
                Empresa
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sistema-tab" data-bs-toggle="tab" data-bs-target="#sistema" 
                    type="button" role="tab" aria-controls="sistema" aria-selected="false">
                <i class="fas fa-sliders-h me-2"></i>
                Sistema
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="manutencao-tab" data-bs-toggle="tab" data-bs-target="#manutencao" 
                    type="button" role="tab" aria-controls="manutencao" aria-selected="false">
                <i class="fas fa-tools me-2"></i>
                Manutenção
            </button>
        </li>
    </ul>
    
    <!-- Conteúdo das abas -->
    <div class="tab-content" id="configTabsContent">
        <!-- Aba Empresa -->
        <div class="tab-pane fade show active" id="empresa" role="tabpanel" aria-labelledby="empresa-tab">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-building me-2"></i>
                        Dados da Empresa
                    </h5>
                </div>
                <div class="card-body">
                    <form action="configuracoes.php" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Informações da empresa -->
                            <div class="col-md-8">
                                <div class="mb-3 row">
                                    <label for="nome" class="col-sm-3 col-form-label">Nome Fantasia:</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo esc($empresa['nome']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="razao_social" class="col-sm-3 col-form-label">Razão Social:</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="razao_social" name="razao_social" value="<?php echo esc($empresa['razao_social']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="cnpj" class="col-sm-3 col-form-label">CNPJ:</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?php echo esc($empresa['cnpj']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="endereco" class="col-sm-3 col-form-label">Endereço:</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo esc($empresa['endereco']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="cidade" class="col-sm-3 col-form-label">Cidade:</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo esc($empresa['cidade']); ?>">
                                    </div>
                                    <label for="estado" class="col-sm-1 col-form-label">UF:</label>
                                    <div class="col-sm-2">
                                        <select class="form-select" id="estado" name="estado">
                                            <?php
                                            $estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                                            foreach ($estados as $uf) {
                                                $selected = ($empresa['estado'] == $uf) ? 'selected' : '';
                                                echo "<option value=\"{$uf}\" {$selected}>{$uf}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="telefone" class="col-sm-3 col-form-label">Telefone:</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo esc($empresa['telefone']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="email" class="col-sm-3 col-form-label">E-mail:</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo esc($empresa['email']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="site" class="col-sm-3 col-form-label">Site:</label>
                                    <div class="col-sm-9">
                                        <input type="url" class="form-control" id="site" name="site" value="<?php echo esc($empresa['site']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Logo -->
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Logo da Empresa</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php if (!empty($empresa['logo']) && file_exists('uploads/' . $empresa['logo'])): ?>
                                            <img src="uploads/<?php echo $empresa['logo']; ?>" alt="Logo da Empresa" class="img-fluid mb-3" style="max-height: 150px;">
                                        <?php else: ?>
                                            <div class="p-4 bg-light mb-3 rounded">
                                                <i class="fas fa-image fa-4x text-muted"></i>
                                                <p class="mt-2 text-muted">Nenhum logo definido</p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png,image/gif">
                                        </div>
                                        <small class="form-text text-muted">Formatos aceitos: JPG, PNG e GIF</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="atualizar_empresa" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Aba Sistema -->
        <div class="tab-pane fade" id="sistema" role="tabpanel" aria-labelledby="sistema-tab">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sliders-h me-2"></i>
                        Configurações do Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <form action="configuracoes.php" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Interface e Exibição</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="itens_por_pagina" class="form-label">Itens por Página:</label>
                                            <input type="number" class="form-control" id="itens_por_pagina" name="itens_por_pagina" min="10" max="100" value="<?php echo $sistema['itens_por_pagina']; ?>" required>
                                            <small class="form-text text-muted">Número de itens exibidos em listas paginadas</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tema" class="form-label">Tema do Sistema:</label>
                                            <select class="form-select" id="tema" name="tema">
                                                <option value="claro" <?php echo ($sistema['tema'] == 'claro') ? 'selected' : ''; ?>>Claro</option>
                                                <option value="escuro" <?php echo ($sistema['tema'] == 'escuro') ? 'selected' : ''; ?>>Escuro</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="formato_data" class="form-label">Formato de Data:</label>
                                            <select class="form-select" id="formato_data" name="formato_data">
                                                <option value="d/m/Y" <?php echo ($sistema['formato_data'] == 'd/m/Y') ? 'selected' : ''; ?>>DD/MM/AAAA</option>
                                                <option value="m/d/Y" <?php echo ($sistema['formato_data'] == 'm/d/Y') ? 'selected' : ''; ?>>MM/DD/AAAA</option>
                                                <option value="Y-m-d" <?php echo ($sistema['formato_data'] == 'Y-m-d') ? 'selected' : ''; ?>>AAAA-MM-DD</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="moeda" class="form-label">Moeda:</label>
                                            <select class="form-select" id="moeda" name="moeda">
                                                <option value="BRL" <?php echo ($sistema['moeda'] == 'BRL') ? 'selected' : ''; ?>>Real (R$)</option>
                                                <option value="USD" <?php echo ($sistema['moeda'] == 'USD') ? 'selected' : ''; ?>>Dólar ($)</option>
                                                <option value="EUR" <?php echo ($sistema['moeda'] == 'EUR') ? 'selected' : ''; ?>>Euro (€)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Comportamento do Sistema</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="estoque_negativo" name="estoque_negativo" <?php echo $sistema['estoque_negativo'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="estoque_negativo">Permitir estoque negativo</label>
                                            <div class="form-text text-muted">Se desativado, o sistema impedirá vendas quando não houver estoque suficiente</div>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="alerta_estoque" name="alerta_estoque" <?php echo $sistema['alerta_estoque'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="alerta_estoque">Alertar sobre estoque baixo</label>
                                            <div class="form-text text-muted">Exibe alertas quando produtos atingem o nível mínimo de estoque</div>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="impressao_automatica" name="impressao_automatica" <?php echo $sistema['impressao_automatica'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="impressao_automatica">Impressão automática de comprovantes</label>
                                            <div class="form-text text-muted">Imprime comprovantes automaticamente ao finalizar vendas</div>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="caixa_obrigatorio" name="caixa_obrigatorio" <?php echo $sistema['caixa_obrigatorio'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="caixa_obrigatorio">Caixa obrigatório para vendas</label>
                                            <div class="form-text text-muted">Se ativado, é necessário abrir o caixa antes de realizar vendas</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="atualizar_sistema" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Aba Manutenção -->
        <div class="tab-pane fade" id="manutencao" role="tabpanel" aria-labelledby="manutencao-tab">
            <div class="row">
                

 <!-- Manutenção de Logs -->
<div class="col-md-6 mb-4">
    <div class="card shadow-sm">
        <div class="card-header bg-warning">
            <h5 class="card-title mb-0">
                <i class="fas fa-history me-2"></i>
                Manutenção de Logs
            </h5>
        </div>
        <div class="card-body">
            <?php
            // Contar total de logs
            $total_logs = $GLOBALS['log']->contarTotal();
            
            // Buscar logs recentes
            $logs_recentes = $GLOBALS['log']->listar(10);
            ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                O sistema possui atualmente <strong><?php echo $total_logs; ?></strong> registros de log.
            </div>
            
            <form action="configuracoes.php" method="post" class="mb-3">
    <div class="input-group">
        <span class="input-group-text">Limpar logs com mais de</span>
        <input type="number" class="form-control" name="dias_log" value="30" min="1" max="365" style="max-width: 80px;">
        <span class="input-group-text">dias</span>
        <button type="submit" name="limpar_logs" class="btn btn-warning" onclick="return confirm('Tem certeza que deseja limpar os logs antigos?')">
            <i class="fas fa-broom me-1"></i>
            <!-- Limpar -->
        </button>
    </div>
</form>
            
            <form action="configuracoes.php" method="post" class="mb-3">
                <button type="submit" name="limpar_todos_logs" class="btn btn-danger w-100" onclick="return confirm('ATENÇÃO: Esta ação irá excluir TODOS os logs do sistema. Esta ação não pode ser desfeita. Deseja continuar?')">
                    <i class="fas fa-trash-alt me-1"></i>
                    Limpar Todos os Logs
                </button>
            </form>
            
            <div class="d-grid">
                <a href="logs.php" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>
                    Visualizar Todos os Logs
                </a>
            </div>
            
            <!-- Logs Recentes -->
            <div class="mt-3">
                <h6>Logs Recentes</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs_recentes)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">Nenhum log encontrado</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs_recentes as $log): ?>
                                    <tr>
                                        <td><?php echo $log['data_formatada']; ?></td>
                                        <td><?php echo esc($log['usuario_nome']); ?></td>
                                        <td><?php echo esc($log['acao']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
                
                <!-- Backup do Sistema -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-database me-2"></i>
                                Backup do Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Recomendamos realizar backups regulares do sistema para evitar perda de dados.
                            </div>
                            
                            <form action="configuracoes.php" method="post">
                                <button type="submit" name="backup_banco" class="btn btn-info text-white w-100 mb-3">
                                    <i class="fas fa-download me-1"></i>
                                    Fazer Backup do Banco de Dados
                                </button>
                            </form>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Últimos Backups</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Tamanho</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="3" class="text-center py-3">
                                                    <i class="fas fa-info-circle text-muted me-1"></i>
                                                    Nenhum backup encontrado
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
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Ativar aba baseado na URL (hash)
        if (window.location.hash) {
            $('.nav-tabs a[href="' + window.location.hash + '"]').tab('show');
        }
        
        // Mudar hash na URL quando a aba for alterada
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            window.location.hash = e.target.getAttribute('data-bs-target');
        });
        
        // Mascara para CNPJ
        if ($('#cnpj').length) {
            $('#cnpj').mask('00.000.000/0000-00');
        }
        
        // Mascara para telefone
        if ($('#telefone').length) {
            $('#telefone').mask('(00) 00000-0000');
        }
    });
</script>

<?php include 'footer.php'; ?>