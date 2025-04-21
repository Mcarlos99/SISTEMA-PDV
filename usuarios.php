<?php
require_once 'config.php';
verificarLogin();

// Verificar se o usuário tem permissão de administrador
if ($_SESSION['usuario_nivel'] != 'admin') {
    alerta('Você não tem permissão para acessar esta página!', 'danger');
    header('Location: index.php');
    exit;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Adicionar usuário
    if (isset($_POST['adicionar'])) {
        // Verificar se senha e confirmação são iguais
        if ($_POST['senha'] != $_POST['confirmar_senha']) {
            alerta('As senhas não coincidem!', 'danger');
        } else {
            $dados = [
                'nome' => $_POST['nome'],
                'usuario' => $_POST['usuario'],
                'senha' => $_POST['senha'],
                'email' => $_POST['email'],
                'nivel' => $_POST['nivel']
            ];
            
            // Verificar se já existe usuário com este nome de usuário
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario LIMIT 1");
            $stmt->bindParam(':usuario', $dados['usuario']);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                alerta('Já existe um usuário com este nome de usuário!', 'danger');
            } else {
                if ($usuario->adicionar($dados)) {
                    // Registrar no log do sistema
                    if (isset($GLOBALS['log'])) {
                        $GLOBALS['log']->registrar(
                            'Usuários', 
                            "Novo usuário {$dados['nome']} ({$dados['usuario']}) adicionado"
                        );
                    }
                    
                    alerta('Usuário adicionado com sucesso!', 'success');
                } else {
                    alerta('Erro ao adicionar usuário!', 'danger');
                }
            }
        }
    }
    // PARTE 2
    // Atualizar usuário
    if (isset($_POST['atualizar'])) {
        $id = $_POST['id'];
        
        // Verificar se é o próprio usuário ou outro usuário
        $proprio_usuario = ($_SESSION['usuario_id'] == $id);
        
        // Se estiver alterando senha, verificar se são iguais
        if (!empty($_POST['senha']) && $_POST['senha'] != $_POST['confirmar_senha']) {
            alerta('As senhas não coincidem!', 'danger');
        } else {
            $dados = [
                'nome' => $_POST['nome'],
                'usuario' => $_POST['usuario'],
                'senha' => $_POST['senha'],
                'email' => $_POST['email'],
                'nivel' => $_POST['nivel'],
                'ativo' => isset($_POST['ativo']) ? 1 : 0
            ];
            
            // Verificar se já existe outro usuário com este nome de usuário
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario AND id != :id LIMIT 1");
            $stmt->bindParam(':usuario', $dados['usuario']);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                alerta('Já existe outro usuário com este nome de usuário!', 'danger');
            } else {
                $usuario_antes = $usuario->buscarPorId($id);
                if ($usuario->atualizar($id, $dados)) {
                    // Registrar no log do sistema
                    if (isset($GLOBALS['log'])) {
                        $GLOBALS['log']->registrar(
                            'Usuários', 
                            "Usuário ID #{$id} ({$dados['usuario']}) atualizado"
                        );
                    }
                    
                    alerta('Usuário atualizado com sucesso!', 'success');
                    
                    // Se for o próprio usuário, atualizar dados da sessão
                    if ($proprio_usuario) {
                        $_SESSION['usuario_nome'] = $dados['nome'];
                        $_SESSION['usuario_nivel'] = $dados['nivel'];
                    }
                } else {
                    alerta('Erro ao atualizar usuário!', 'danger');
                }
            }
        }
    }
    
    // Excluir usuário
    if (isset($_POST['excluir'])) {
        $id = $_POST['id'];
        
        // Verificar se não está tentando excluir a si mesmo
        if ($_SESSION['usuario_id'] == $id) {
            alerta('Você não pode excluir seu próprio usuário!', 'danger');
        } else {
            $usuario_excluir = $usuario->buscarPorId($id);
            if ($usuario->excluir($id)) {
                // Registrar no log do sistema
                if (isset($GLOBALS['log'])) {
                    $GLOBALS['log']->registrar(
                        'Usuários', 
                        "Usuário ID #{$id} ({$usuario_excluir['usuario']}) excluído"
                    );
                }
                
                alerta('Usuário excluído com sucesso!', 'success');
            } else {
                alerta('Erro ao excluir usuário!', 'danger');
            }
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: usuarios.php');
    exit;
}

// Buscar usuário para edição
$usuario_edicao = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $usuario_edicao = $usuario->buscarPorId($id);
}

// Template da página
$titulo_pagina = 'Gerenciamento de Usuários - EXTREME PDV';
include 'header.php';
?>
<!-- PARTE 3 -->
<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-user-cog me-2 text-primary"></i>
                Gerenciamento de Usuários
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item active">Usuários</li>
                </ol>
            </nav>
        </div>
        
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-usuario">
            <i class="fas fa-plus-circle me-1"></i>
            Novo Usuário
        </button>
    </div>
    
    <div class="card shadow">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col-md-6 mb-2 mb-md-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>
                        Lista de Usuários
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="buscarUsuario" placeholder="Buscar usuário...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover datatable mb-0" id="tabelaUsuarios">
                    <thead>
                        <tr>
                            <th data-priority="1">Nome</th>
                            <th data-priority="2">Usuário</th>
                            <th data-priority="3">Email</th>
                            <th data-priority="2">Nível</th>
                            <th data-priority="2">Status</th>
                            <th data-priority="3">Criado em</th>
                            <th data-priority="1" width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $usuarios = $usuario->listar();
                        foreach ($usuarios as $u) {
                            // Definir classes de acordo com o nível
                            $nivel_class = ($u['nivel'] == 'admin') ? 'bg-danger' : 
                                          (($u['nivel'] == 'gerente') ? 'bg-warning' : 'bg-info');
                            
                            $nivel_texto = ($u['nivel'] == 'admin') ? 'Administrador' : 
                                          (($u['nivel'] == 'gerente') ? 'Gerente' : 'Vendedor');
                            
                            // Status ativo/inativo
                            $status_class = $u['ativo'] ? 'bg-success' : 'bg-secondary';
                            $status_texto = $u['ativo'] ? 'Ativo' : 'Inativo';
                        ?>
                            <tr>
                                <td><?php echo esc($u['nome']); ?></td>
                                <td><?php echo esc($u['usuario']); ?></td>
                                <td><?php echo esc($u['email']); ?></td>
                                <td><span class="badge <?php echo $nivel_class; ?>"><?php echo $nivel_texto; ?></span></td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_texto; ?></span></td>
                                <td><?php echo $u['criado_em']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                    <a href="?editar=<?php echo $u['id']; ?>" 
                                    class="btn btn-primary">
                                    
                                    
                                    <i class="fas fa-edit"></i>
                                    </a>
        
                                    <a href="ver_perfil.php?id=<?php echo $u['id']; ?>" 
                                    class="btn btn-info text-white"> 


                                    <i class="fas fa-user-circle"></i>
                                    </a>
                                        
                                        <?php if ($_SESSION['usuario_id'] != $u['id']): ?>
                                            <button type="button" class="btn btn-danger btn-excluir" 
                                                data-id="<?php echo $u['id']; ?>" 
                                                data-nome="<?php echo esc($u['nome']); ?>">

                                                
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light">
            <span class="text-muted">Total de <?php echo count($usuarios); ?> usuários cadastrados</span>
        </div>
    </div>
</div>

<!-- Modal de Usuário -->
<div class="modal fade" id="modal-usuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas <?php echo $usuario_edicao ? 'fa-edit' : 'fa-plus-circle'; ?> me-2"></i>
                    <?php echo $usuario_edicao ? 'Editar Usuário' : 'Novo Usuário'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-usuario" method="post" action="">
                    <?php if ($usuario_edicao): ?>
                        <input type="hidden" name="id" value="<?php echo $usuario_edicao['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label fw-bold">Nome Completo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo $usuario_edicao ? esc($usuario_edicao['nome']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="usuario" class="form-label fw-bold">Nome de Usuário</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                            <input type="text" class="form-control" id="usuario" name="usuario" required value="<?php echo $usuario_edicao ? esc($usuario_edicao['usuario']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo $usuario_edicao ? esc($usuario_edicao['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="senha" class="form-label fw-bold">
                            <?php echo $usuario_edicao ? 'Nova Senha (deixe em branco para manter a atual)' : 'Senha'; ?>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" id="senha" name="senha" <?php echo $usuario_edicao ? '' : 'required'; ?>>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label fw-bold">Confirmar Senha</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" <?php echo $usuario_edicao ? '' : 'required'; ?>>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nivel" class="form-label fw-bold">Nível de Acesso</label>
                        <select class="form-select" id="nivel" name="nivel" required>
                            <option value="vendedor" <?php echo ($usuario_edicao && $usuario_edicao['nivel'] == 'vendedor') ? 'selected' : ''; ?>>
                                Vendedor
                            </option>
                            <option value="gerente" <?php echo ($usuario_edicao && $usuario_edicao['nivel'] == 'gerente') ? 'selected' : ''; ?>>
                                Gerente
                            </option>
                            <option value="admin" <?php echo ($usuario_edicao && $usuario_edicao['nivel'] == 'admin') ? 'selected' : ''; ?>>
                                Administrador
                            </option>
                        </select>
                    </div>
                    
                    <?php if ($usuario_edicao): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="ativo" name="ativo" <?php echo $usuario_edicao['ativo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="ativo">Usuário Ativo</label>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <button type="submit" form="form-usuario" class="btn btn-primary" name="<?php echo $usuario_edicao ? 'atualizar' : 'adicionar'; ?>">
                    <i class="fas fa-save me-1"></i>
                    <?php echo $usuario_edicao ? 'Atualizar' : 'Adicionar'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modal-excluir" tabindex="-1" aria-hidden="true">
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
                <p>Tem certeza que deseja excluir o usuário <strong id="nome-usuario-excluir"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Esta ação não poderá ser desfeita. O usuário perderá o acesso ao sistema imediatamente.
                </div>
            </div>
            <div class="modal-footer">
                <form method="post" action="">
                    <input type="hidden" name="id" id="id-usuario-excluir">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" name="excluir" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>
                        Confirmar Exclusão
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Inicializa DataTables
        var table = $('#tabelaUsuarios').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            },
            "responsive": true,
            "order": [[0, 'asc']], // Ordenar por nome
            "pageLength": 10
        });
        
        // Filtro de busca
        $('#buscarUsuario').on('keyup', function() {
            table.search($(this).val()).draw();
        });
        
        // Abrir modal de edição automaticamente se tiver usuário para editar
        <?php if ($usuario_edicao): ?>
        var modalUsuario = new bootstrap.Modal(document.getElementById('modal-usuario'));
        modalUsuario.show();
        <?php endif; ?>
        
        // Configurar modal de exclusão
        $('.btn-excluir').on('click', function() {
            var id = $(this).data('id');
            var nome = $(this).data('nome');
            $('#id-usuario-excluir').val(id);
            $('#nome-usuario-excluir').text(nome);
            
            var modalExcluir = new bootstrap.Modal(document.getElementById('modal-excluir'));
            modalExcluir.show();
        });
        
        // Validação de senha
        $('#form-usuario').on('submit', function(e) {
            var senha = $('#senha').val();
            var confirmarSenha = $('#confirmar_senha').val();
            
            // Se o campo de senha estiver preenchido, verificar se são iguais
            if (senha && senha !== confirmarSenha) {
                e.preventDefault();
                alert('As senhas não coincidem!');
            }
        });
        
         // Inicializar tooltips
         var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
         var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
             return new bootstrap.Tooltip(tooltipTriggerEl)
         });

    });
</script>

<?php include 'footer.php'; ?>