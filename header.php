<?php
// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina ?? 'Sistema PDV'; ?></title>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- jQuery Mask Plugin -->
   <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script> -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --header-height: 60px;
            --card-border-radius: 10px;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            overflow-x: hidden; /* Previne rolagem horizontal */
        }
        
        .main-content {
            flex: 1;
            margin-top: var(--header-height);
            transition: all 0.3s;
            width: 100%; /* Garante que o conteúdo não ultrapasse a largura da tela */
            max-width: 100%; /* Reforça o limite máximo de largura */
        }
        
        .navbar {
            height: var(--header-height);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .navbar .dropdown-item i {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }
        
        .navbar .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #fff;
            color: var(--primary-color);
            margin-right: 8px;
            font-weight: 600;
        }
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            bottom: 0;
            left: 0;
            width: var(--sidebar-width);
            background-color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s;
            padding-top: 1rem;
        }
        
        .sidebar .nav-item {
            padding: 0.25rem 1.5rem;
        }
        
        .sidebar .nav-link {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--dark-color);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            white-space: nowrap; /* Evita quebra de texto */
        }
        
        .sidebar .nav-link i {
            font-size: 1.1rem;
            margin-right: 10px;
            width: 20px;
            text-align: center;
            transition: all 0.2s;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .sidebar .nav-link:hover i {
            color: var(--primary-color);
        }
        
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
        }
        
        .sidebar .nav-link.active i {
            color: white;
        }
        
        .content-wrapper {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all 0.3s;
            width: calc(100% - var(--sidebar-width)); /* Ajusta a largura corretamente */
            overflow-x: hidden; /* Previne rolagem horizontal */
        }
        

        /* Estilos para os campos de data e hora relatorio */
.input-group input[type="date"],
.input-group input[type="time"] {
    border-radius: 0.25rem;
}

.input-group input[type="date"] {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    flex: 2;
}

.input-group input[type="time"] {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    flex: 1;
}

@media (max-width: 768px) {
    .input-group {
        flex-direction: column;
    }
    
    .input-group input[type="date"],
    .input-group input[type="time"] {
        width: 100%;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
    }
}


        /* Card styling */
        .card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        
        .card-header.bg-primary,
        .card-header.bg-success,
        .card-header.bg-warning,
        .card-header.bg-danger {
            color: white;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .bg-success {
            background-color: var(--success-color) !important;
        }
        
        .bg-warning {
            background-color: var(--warning-color) !important;
        }
        

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


        /* Buttons styling */
        .btn {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
        }
        
        /* Table styling */
        .table {
            border-radius: var(--card-border-radius);
            overflow: hidden;
            width: 100%;
        }
        
        .table thead th {
            background-color: rgba(67, 97, 238, 0.05);
            border-bottom: none;
            font-weight: 600;
            padding: 0.75rem 1rem;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,0.02);
        }
        
        /* Tabelas responsivas */
        .table-responsive {
            width: 100%; /* Garante que a tabela responsiva não extrapole */
            overflow-x: auto; /* Adiciona rolagem horizontal apenas quando necessário */
            -webkit-overflow-scrolling: touch; /* Melhora a rolagem em dispositivos touch */
            max-width: 100%; /* Reforça o limite máximo */
        }
        
        /* Alerts styling */
        .alert {
            border-radius: var(--card-border-radius);
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        /* Status badges */
        .badge {
            padding: 0.4em 0.8em;
            font-weight: 500;
            border-radius: 6px;
        }
        
        /* Dashboard stats */
        .stat-card {
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background-color: white;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .stat-card .stat-icon {
            position: absolute;
            right: 1rem;
            bottom: 1rem;
            font-size: 4rem;
            opacity: 0.1;
            transform: rotate(-15deg);
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--gray-color);
        }
        
        /* Sidebar collapsed state */
        body.sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed-width);
        }
        
        body.sidebar-collapsed .sidebar .nav-item span,
        body.sidebar-collapsed .sidebar .nav-link span {
            display: none;
        }
        
        body.sidebar-collapsed .sidebar .nav-link i {
            margin-right: 0;
            font-size: 1.2rem;
        }
        
        body.sidebar-collapsed .content-wrapper {
            margin-left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }
        
        .sidebar-toggle {
            cursor: pointer;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
            }
            
            .content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 1rem; /* Reduz o padding em telas menores */
            }
            
            body.sidebar-open .sidebar {
                transform: translateX(0);
            }
            
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            
            /* Ajuste para tabelas responsivas em dispositivos móveis */
            .table-responsive {
                margin-bottom: 1rem;
            }
            
            /* Reduz tamanho de cards em telas menores */
            .card {
                margin-bottom: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            /* Ajusta botões em telas menores */
            .btn {
                padding: 0.375rem 0.75rem;
            }
            
            /* Ajusta espaçamento no header em telas menores */
            .navbar-brand {
                font-size: 1.1rem;
            }
        }
        
        /* Ajustes para telas muito pequenas */
        @media (max-width: 575.98px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .card-header, .card-body, .card-footer {
                padding: 0.75rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-card .stat-value {
                font-size: 1.5rem;
            }
            
            /* Simplifica headers em telas pequenas */
            .navbar .user-avatar {
                width: 28px;
                height: 28px;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            /* Ajusta tabelas em telas pequenas */
            .table thead th, .table tbody td {
                padding: 0.5rem;
            }
        }
        
.modal-dialog {
    margin: 1.75rem auto;
    width: auto !important;
    max-width: 90%;
}
.modal-dialog.modal-lg {
    max-width: 800px;
}
.modal-dialog.modal-sm {
    max-width: 300px;
}
.modal-content {
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border: none;
    max-width: 100%;
    overflow-x: hidden
}
/* Control form element widths */
.modal-body .form-control, 
.modal-body .form-select, 
.modal-body .input-group {
    max-width: 100%;
}
/* Add more padding for better visual spacing */
.modal-body {
    padding: 1.5rem;
}
/* Ensure table doesn't force width beyond container */
.modal-body .table-responsive {
    overflow-x: auto;
    width: 100%;
}

        
        @media (max-width: 576px) {
             .modal-dialog {
              margin: 1rem auto;
             max-width: 95%;
        }
            
            .modal-dialog.modal-lg {
                max-width: 800px;
            }
        }
        
        /* Garante que formulários não extrapolem */
        .form-control, .form-select {
            max-width: 70%;
        }

/* Garantir que o menu colapsado apareça por trás do topo */
@media (max-width: 991.98px) {
    .navbar {
        z-index: 1030;
    }
    
    .navbar-collapse {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        width: 100%;
        border-radius: 0 0 10px 10px;
        padding: 10px;
        box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        z-index: 1020;
    }
    
    /* Melhora a visibilidade dos itens dentro do menu colapsado */
    .navbar-collapse .nav-link {
        color: white !important;
        padding: 10px 15px;
        border-radius: 5px;
    }
    
    .navbar-collapse .nav-link:hover {
        background-color: var(--secondary-color);
    }
    
    /* Ajusta o dropdown do perfil no menu colapsado */
    .navbar-collapse .dropdown-menu {
        background-color: white;
        margin-top: 5px;
    }
    
    .navbar-collapse .dropdown-menu .dropdown-item {
        color: var(--dark-color);
    }
}
.tooltip {
    z-index: 9999 !important;
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white sidebar-toggle me-2 d-lg-none" type="button">
                <i class="fas fa-bars"></i>
            </button>
            <button class="btn btn-link text-white sidebar-toggle me-2 d-none d-lg-block" id="sidebarCollapseBtn" type="button">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="index.php">
                <img src="logo/logo2.png" alt="Logo Extreme PDV" style="height: 40px; margin-right: 10px;">
                EXTREME PDV
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="pdv.php">
                            <i class="fas fa-cash-register"></i>
                            <span class="d-none d-sm-inline-block ms-1">PDV</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <?php echo substr($_SESSION['usuario_nome'], 0, 1); ?>
                            </div>
                            <span class="d-none d-sm-inline-block"><?php echo $_SESSION['usuario_nome']; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user"></i> Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="configuracoes.php"><i class="fas fa-cog"></i> Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar & Content -->
    <div class="container-fluid main-content p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Painel</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pdv.php' ? 'active' : ''; ?>" href="pdv.php">
                            <i class="fas fa-cash-register"></i>
                            <span>PDV</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'caixa.php' ? 'active' : ''; ?>" href="caixa.php">
                            <i class="fas fa-cash-register"></i>
                            <span>Caixa</span>
                        </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'comandas.php') !== false) ? 'active' : ''; ?>" href="comandas.php">
                      <i class="fas fa-clipboard-list"></i>
                      <span>Comandas</span>
                      </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vendas.php' ? 'active' : ''; ?>" href="vendas.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Vendas</span>
                        </a>
                    </li>
                    <?php if (in_array($_SESSION['usuario_nivel'], ['admin', 'gerente'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'produtos.php' ? 'active' : ''; ?>" href="produtos.php">
                            <i class="fas fa-box"></i>
                            <span>Produtos</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>" href="categorias.php">
                            <i class="fas fa-tags"></i>
                            <span>Categorias</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'estoque.php' ? 'active' : ''; ?>" href="estoque.php">
                            <i class="fas fa-warehouse"></i>
                            <span>Estoque</span>
                        </a>
                    </li>
                    <?php endif; ?>


                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'active' : ''; ?>" href="clientes.php">
                            <i class="fas fa-users"></i>
                            <span>Clientes</span>
                        </a>
                    </li>

                    
                    <?php if (in_array($_SESSION['usuario_nivel'], ['admin', 'gerente'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'fornecedores.php' ? 'active' : ''; ?>" href="fornecedores.php">
                            <i class="fas fa-truck"></i>
                            <span>Fornecedores</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'compras.php' ? 'active' : ''; ?>" href="compras.php">
                            <i class="fas fa-shopping-basket"></i>
                            <span>Compras</span>
                        </a>
                    </li>
                    
                        <!-- Links para admin e gerente -->
                        <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : ''; ?>" href="relatorios.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Relatórios</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['usuario_nivel'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
                            <i class="fas fa-user-cog"></i>
                            <span>Usuários</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active' : ''; ?>" href="configuracoes.php">
                            <i class="fas fa-cog"></i>
                            <span>Configurações</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Content -->
            <div class="content-wrapper">
                <?php exibirAlerta(); ?>