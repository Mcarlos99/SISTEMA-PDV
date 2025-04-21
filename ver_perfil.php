<?php
require_once 'config.php';
verificarLogin();

// Verificar se o usuário tem permissão de administrador
if ($_SESSION['usuario_nivel'] != 'admin') {
    alerta('Você não tem permissão para acessar esta página!', 'danger');
    header('Location: index.php');
    exit;
}

// Verificar se o ID do usuário foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    alerta('ID de usuário não fornecido', 'danger');
    header('Location: usuarios.php');
    exit;
}

$id_usuario = intval($_GET['id']);
$usuario_obj = new Usuario($pdo);
$usuario_dados = $usuario_obj->buscarPorId($id_usuario);

// Verificar se o usuário existe
if (!$usuario_dados) {
    alerta('Usuário não encontrado', 'danger');
    header('Location: usuarios.php');
    exit;
}

// Buscar atividades do usuário
$atividades = [];
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(data_hora, '%d/%m/%Y %H:%i') AS data_formatada,
        acao,
        detalhes
    FROM logs_sistema
    WHERE usuario_id = :usuario_id
    ORDER BY data_hora DESC
    LIMIT 20
");
$stmt->bindParam(':usuario_id', $id_usuario, PDO::PARAM_INT);
$stmt->execute();
$atividades = $stmt->fetchAll();

// Template da página
$titulo_pagina = 'Visualizar Perfil de Usuário - EXTREME PDV';
include 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-user-circle me-2 text-primary"></i>
                Perfil do Usuário
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item"><a href="usuarios.php">Usuários</a></li>
                    <li class="breadcrumb-item active">Visualizar Perfil</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="usuarios.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
            <a href="usuarios.php?editar=<?php echo $id_usuario; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>
                Editar Usuário
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Coluna com dados do usuário -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-id-card me-2"></i>
                        Informações da Conta
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-circle mx-auto mb-3">
                            <span class="avatar-initials"><?php echo substr($usuario_dados['nome'], 0, 1); ?></span>
                        </div>
                        <h4><?php echo esc($usuario_dados['nome']); ?></h4>
                        <span class="badge <?php echo $usuario_dados['nivel'] == 'admin' ? 'bg-danger' : ($usuario_dados['nivel'] == 'gerente' ? 'bg-warning' : 'bg-info'); ?>">
                            <?php
                            echo $usuario_dados['nivel'] == 'admin' ? 'Administrador' : 
                                ($usuario_dados['nivel'] == 'gerente' ? 'Gerente' : 'Vendedor');
                            ?>
                        </span>
                        <span class="ms-2 badge <?php echo $usuario_dados['ativo'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $usuario_dados['ativo'] ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-user me-2 text-muted"></i>
                                <strong>Usuário:</strong>
                            </div>
                            <span><?php echo esc($usuario_dados['usuario']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-envelope me-2 text-muted"></i>
                                <strong>E-mail:</strong>
                            </div>
                            <span><?php echo esc($usuario_dados['email']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                <strong>Cadastrado em:</strong>
                            </div>
                            <span>
                                <?php 
                                // Verificar se há data de criação disponível
                                if (isset($usuario_dados['criado_em'])) {
                                    echo $usuario_dados['criado_em'];
                                } else {
                                    echo '<span class="text-muted">Não disponível</span>';
                                }
                                ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Estatísticas do usuário -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Estatísticas
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Contar vendas do usuário
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM vendas WHERE usuario_id = :id");
                    $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
                    $stmt->execute();
                    $total_vendas = $stmt->fetch()['total'];
                    
                    // Contar valor total de vendas
                    $stmt = $pdo->prepare("SELECT SUM(valor_total) as total FROM vendas WHERE usuario_id = :id AND status = 'finalizada'");
                    $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
                    $stmt->execute();
                    $valor_total_vendas = $stmt->fetch()['total'] ?: 0;
                    
                    // Contar total de atividades/logs
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs_sistema WHERE usuario_id = :id");
                    $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
                    $stmt->execute();
                    $total_atividades = $stmt->fetch()['total'];
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h4 class="mb-0"><?php echo $total_vendas; ?></h4>
                                <small class="text-muted">Vendas Realizadas</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h4 class="mb-0"><?php echo formatarDinheiro($valor_total_vendas); ?></h4>
                                <small class="text-muted">Valor Total</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded">
                                <h4 class="mb-0"><?php echo $total_atividades; ?></h4>
                                <small class="text-muted">Atividades no Sistema</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Coluna com histórico de atividades -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Histórico de Atividades
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="180">Data/Hora</th>
                                    <th width="150">Ação</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($atividades)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3">
                                        <i class="fas fa-info-circle me-2 text-muted"></i>
                                        Nenhuma atividade registrada para este usuário
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($atividades as $atividade): ?>
                                    <tr>
                                        <td><?php echo esc($atividade['data_formatada']); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                $badge_class = 'bg-secondary';
                                                switch ($atividade['acao']) {
                                                    case 'Login':
                                                        $badge_class = 'bg-success';
                                                        break;
                                                    case 'Venda':
                                                        $badge_class = 'bg-info';
                                                        break;
                                                    case 'Produto':
                                                        $badge_class = 'bg-primary';
                                                        break;
                                                    case 'Estoque':
                                                        $badge_class = 'bg-warning';
                                                        break;
                                                    case 'Usuários':
                                                        $badge_class = 'bg-danger';
                                                        break;
                                                }
                                                echo $badge_class;
                                                ?>
                                                ">
                                                <?php echo esc($atividade['acao']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc($atividade['detalhes']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Mostrando as últimas 20 atividades</span>
                        <?php if (count($atividades) >= 20): ?>
                            <a href="#" class="btn btn-sm btn-outline-primary" id="carregarMais" data-id="<?php echo $id_usuario; ?>">
                                <i class="fas fa-sync-alt me-1"></i>
                                    Carregar Mais
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Vendas recentes -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Vendas Recentes
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Buscar vendas recentes do usuário
                                $stmt = $pdo->prepare("
                                    SELECT v.*, 
                                           c.nome AS cliente_nome,
                                           DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada
                                    FROM vendas v
                                    LEFT JOIN clientes c ON v.cliente_id = c.id
                                    WHERE v.usuario_id = :usuario_id
                                    ORDER BY v.data_venda DESC
                                    LIMIT 5
                                ");
                                $stmt->bindParam(':usuario_id', $id_usuario, PDO::PARAM_INT);
                                $stmt->execute();
                                $vendas = $stmt->fetchAll();
                                
                                if (empty($vendas)):
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">
                                        <i class="fas fa-shopping-cart me-2 text-muted"></i>
                                        Nenhuma venda registrada para este usuário
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($vendas as $venda): ?>
                                    <tr>
                                        <td>#<?php echo $venda['id']; ?></td>
                                        <td><?php echo esc($venda['data_formatada']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($venda['cliente_nome'])) {
                                                echo esc($venda['cliente_nome']);
                                            } else {
                                                echo '<span class="text-muted">Cliente não identificado</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo formatarDinheiro($venda['valor_total']); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                echo $venda['status'] == 'finalizada' ? 'bg-success' : 
                                                    ($venda['status'] == 'cancelada' ? 'bg-danger' : 'bg-warning');
                                                ?>">
                                                <?php 
                                                echo $venda['status'] == 'finalizada' ? 'Finalizada' : 
                                                    ($venda['status'] == 'cancelada' ? 'Cancelada' : 'Pendente');
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="vendas.php?id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <a href="vendas.php?usuario_id=<?php echo $id_usuario; ?>" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-list me-1"></i>
                        Ver Todas as Vendas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        width: 100px;
        height: 100px;
        background-color: #4361ee;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .avatar-initials {
        color: white;
        font-size: 48px;
        font-weight: 600;
        text-transform: uppercase;
    }
</style>

<script>
    $(document).ready(function() {
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Variáveis para paginação
        let offset = 20;
        const usuarioId = <?php echo $id_usuario; ?>;
        
        // Seletor específico para a tabela de atividades
        // Seleciona apenas a tabela dentro do card com cabeçalho "Histórico de Atividades"
        const tabelaAtividades = $('.card-header:contains("Histórico de Atividades")').closest('.card').find('tbody');
        
        // Manipulador para carregar mais atividades
        $('#carregarMais').on('click', function(e) {
            e.preventDefault();
            
            // Mostrar indicador de carregamento
            const btnText = $(this).html();
            $(this).html('<i class="fas fa-spinner fa-spin me-1"></i> Carregando...');
            $(this).prop('disabled', true);
            
            // Fazer requisição Ajax
            $.ajax({
                url: 'carregar_atividades.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    usuario_id: usuarioId,
                    offset: offset
                },
                success: function(response) {
                    console.log('Resposta recebida:', response);
                    
                    // Verificar se a resposta contém atividades
                    if (response.atividades && response.atividades.length > 0) {
                        // Adicionar cada atividade à tabela
                        $.each(response.atividades, function(index, atividade) {
                            // Determinar a classe da badge baseada na ação
                            let badgeClass = 'bg-secondary';
                            switch (atividade.acao) {
                                case 'Login':
                                    badgeClass = 'bg-success';
                                    break;
                                case 'Venda':
                                    badgeClass = 'bg-info';
                                    break;
                                case 'Produto':
                                    badgeClass = 'bg-primary';
                                    break;
                                case 'Estoque':
                                    badgeClass = 'bg-warning';
                                    break;
                                case 'Usuários':
                                    badgeClass = 'bg-danger';
                                    break;
                            }
                            
                            // Criar e adicionar a linha à tabela de atividades
                            const novaLinha = $(`
                                <tr>
                                    <td>${atividade.data_formatada}</td>
                                    <td>
                                        <span class="badge ${badgeClass}">
                                            ${atividade.acao}
                                        </span>
                                    </td>
                                    <td>${atividade.detalhes}</td>
                                </tr>
                            `);
                            tabelaAtividades.append(novaLinha);
                        });
                        
                        // Atualizar o offset para a próxima carga
                        offset += response.atividades.length;
                        
                        // Verificar se ainda há mais atividades para carregar
                        if (!response.tem_mais) {
                            $('#carregarMais').hide();
                        }
                    } else {
                        // Nenhuma atividade encontrada
                        $('#carregarMais').hide();
                        tabelaAtividades.append(`
                            <tr>
                                <td colspan="3" class="text-center py-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Não há mais atividades para carregar
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro ao carregar atividades:', error);
                    console.log('Resposta:', xhr.responseText);
                    
                    // Mostrar mensagem de erro apenas na tabela de atividades
                    tabelaAtividades.append(`
                        <tr>
                            <td colspan="3" class="text-center py-3 text-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Erro ao carregar mais atividades. Tente novamente mais tarde.
                            </td>
                        </tr>
                    `);
                },
                complete: function() {
                    // Restaurar o botão
                    $('#carregarMais').html(btnText);
                    $('#carregarMais').prop('disabled', false);
                }
            });
        });
    });
</script>

<?php include 'footer.php'; ?>