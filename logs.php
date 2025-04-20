<?php
require_once 'config.php';
verificarLogin();

// Verificar se o usuário tem permissão
if ($_SESSION['usuario_nivel'] != 'admin') {
    alerta('Você não tem permissão para acessar esta página!', 'danger');
    header('Location: index.php');
    exit;
}

// Parâmetros de paginação
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$registros_por_pagina = 50;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Parâmetros de filtro
$filtro_usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';
$filtro_acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Buscar logs com filtros
$logs = [];
$total_registros = 0;

// Montar query base
$sql_base = "FROM logs_sistema WHERE 1=1";
$params = [];

// Aplicar filtros
if (!empty($filtro_usuario)) {
    $sql_base .= " AND usuario_nome LIKE :usuario";
    $params[':usuario'] = "%{$filtro_usuario}%";
}

if (!empty($filtro_acao)) {
    $sql_base .= " AND acao = :acao";
    $params[':acao'] = $filtro_acao;
}

if (!empty($filtro_data_inicio)) {
    $sql_base .= " AND data_hora >= :data_inicio";
    $params[':data_inicio'] = $filtro_data_inicio . ' 00:00:00';
}

if (!empty($filtro_data_fim)) {
    $sql_base .= " AND data_hora <= :data_fim";
    $params[':data_fim'] = $filtro_data_fim . ' 23:59:59';
}

// Contar total de registros
$stmt = $pdo->prepare("SELECT COUNT(*) as total " . $sql_base);
foreach ($params as $param => $value) {
    $stmt->bindValue($param, $value);
}
$stmt->execute();
$total_registros = $stmt->fetch()['total'];

// Calcular total de páginas
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar logs paginados
$sql = "SELECT id, usuario_nome, acao, detalhes, ip, DATE_FORMAT(data_hora, '%d/%m/%Y %H:%i:%s') AS data_formatada " . 
       $sql_base . " ORDER BY data_hora DESC LIMIT :offset, :limit";
$stmt = $pdo->prepare($sql);
foreach ($params as $param => $value) {
    $stmt->bindValue($param, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Buscar ações distintas para o filtro
$stmt = $pdo->query("SELECT DISTINCT acao FROM logs_sistema ORDER BY acao");
$acoes_disponiveis = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Template da página
$titulo_pagina = 'Logs do Sistema - Sistema PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-history me-2 text-primary"></i>
                Logs do Sistema
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item"><a href="configuracoes.php#manutencao">Configurações</a></li>
                    <li class="breadcrumb-item active">Logs</li>
                </ol>
            </nav>
        </div>
        
        <div>
            <a href="configuracoes.php#manutencao" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>
                Filtros
            </h5>
        </div>
        <div class="card-body">
            <form method="get" action="logs.php" class="row g-3">
                <div class="col-md-3">
                    <label for="usuario" class="form-label">Usuário:</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo esc($filtro_usuario); ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="acao" class="form-label">Ação:</label>
                    <select class="form-select" id="acao" name="acao">
                        <option value="">Todas as ações</option>
                        <?php foreach ($acoes_disponiveis as $acao): ?>
                            <option value="<?php echo $acao; ?>" <?php echo ($filtro_acao == $acao) ? 'selected' : ''; ?>>
                                <?php echo $acao; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label">Data Inicial:</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $filtro_data_inicio; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="data_fim" class="form-label">Data Final:</label>
                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $filtro_data_fim; ?>">
                </div>
                
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>
                        Filtrar
                    </button>
                    <a href="logs.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-eraser me-1"></i>
                        Limpar Filtros
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resultados -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Registros de Log
                </h5>
                <span class="badge bg-primary">Total: <?php echo $total_registros; ?></span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="160">Data/Hora</th>
                            <th width="180">Usuário</th>
                            <th width="120">Ação</th>
                            <th>Detalhes</th>
                            <th width="120">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2 text-muted"></i>
                                    Nenhum registro encontrado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['data_formatada']; ?></td>
                                    <td><?php echo esc($log['usuario_nome']); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            $badge_class = 'bg-secondary';
                                            switch ($log['acao']) {
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
                                            ?>">
                                            <?php echo esc($log['acao']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc($log['detalhes']); ?></td>
                                    <td><?php echo esc($log['ip']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <div class="card-footer bg-light">
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo ($pagina_atual == 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=1<?php echo montarQueryString(['usuario', 'acao', 'data_inicio', 'data_fim']); ?>" aria-label="Primeira">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item <?php echo ($pagina_atual == 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_atual - 1; ?><?php echo montarQueryString(['usuario', 'acao', 'data_inicio', 'data_fim']); ?>" aria-label="Anterior">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        
                        <?php
                        // Mostrar 5 páginas no máximo
                        $inicio = max(1, $pagina_atual - 2);
                        $fim = min($inicio + 4, $total_paginas);
                        
                        if ($fim - $inicio < 4) {
                            $inicio = max(1, $fim - 4);
                        }
                        
                        for ($i = $inicio; $i <= $fim; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo montarQueryString(['usuario', 'acao', 'data_inicio', 'data_fim']); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($pagina_atual == $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_atual + 1; ?><?php echo montarQueryString(['usuario', 'acao', 'data_inicio', 'data_fim']); ?>" aria-label="Próxima">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item <?php echo ($pagina_atual == $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php echo montarQueryString(['usuario', 'acao', 'data_inicio', 'data_fim']); ?>" aria-label="Última">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Função auxiliar para montar query string mantendo filtros
function montarQueryString($campos) {
    $query = '';
    foreach ($campos as $campo) {
        if (isset($_GET[$campo]) && $_GET[$campo] !== '') {
            $query .= "&{$campo}=" . urlencode($_GET[$campo]);
        }
    }
    return $query;
}
?>

<?php include 'footer.php'; ?>