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
                    alerta('Usuário adicionado com sucesso!', 'success');
                } else {
                    alerta('Erro ao adicionar usuário!', 'danger');
                }
            }
        }
    }
    
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
                if ($usuario->atualizar($id, $dados)) {
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
            if ($usuario->excluir($id)) {
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
$titulo_pagina = 'Gerenciamento de Usuários';
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Usuários</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-usuario">
            <i class="fas fa-plus"></i> Novo Usuário
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Nível</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $usuarios = $usuario->listar();
                        foreach ($usuarios as $u) {
                            echo '<tr>';
                            echo '<td>'.$u['nome'].'</td>';
                            echo '<td>'.$u['usuario'].'</td>';
                            echo '<td>'.$u['email'].'</td>';
                            
                            // Nível de acesso
                            if ($u['nivel'] == 'admin') {
                                echo '<td><span class="badge bg-danger">Administrador</span></td>';
                            } else if ($u['nivel'] == 'gerente') {
                                echo '<td><span class="badge bg-warning">Gerente</span></td>';
                            } else {
                                echo '<td><span class="badge bg-info">Vendedor</span></td>';
                            }
                            
                            // Status
                            echo '<td>'.($u['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>').'</td>';
                            
                            echo '<td>'.$u['criado_em'].'</td>';
                            
                            echo '<td>
                                    <a href="?editar='.$u['id'].'" class="btn btn-sm btn-primary me-1"><i class="fas fa-edit"></i></a>';
                            
                            // Só mostra botão de excluir se não for o próprio usuário
                            if ($_SESSION['usuario_id'] != $u['id']) {
                                echo '<a href="#" class="btn btn-sm btn-danger btn-excluir" data-id="'.$u['id'].'" data-nome="'.$u['nome'].'"><i class="fas fa-trash"></i></a>';
                            }
                            
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Usuário -->
<div class="modal fade" id="modal-usuario" tabindex="-1" aria-labelledby="modal-usuario-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-usuario-label"><?php echo $usuario_edicao ? 'Editar Usuário' : 'Novo Usuário'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-usuario" method="post" action="">
                    <?php if ($usuario_edicao): ?>
                        <input type="hidden" name="id" value="<?php echo $usuario_edicao['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo $usuario_edicao ? $usuario_edicao['nome'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Nome de Usuário *</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required value="<?php echo $usuario_edicao ? $usuario_edicao['usuario'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail *</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo $usuario_edicao ? $usuario_edicao['email'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="senha" class="form-label"><?php echo $usuario_edicao ? 'Nova Senha (deixe em branco para manter a atual)' : 'Senha *'; ?></label>
                        <input type="password" class="form-control" id="senha" name="senha" <?php echo $usuario_edicao ? '' : 'required'; ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" <?php echo $usuario_edicao ? '' : 'required'; ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nivel" class="form-label">Nível de Acesso *</label>
                        <select class="form-select" id="nivel" name="nivel" required>
                            <option value="vendedor" <?php echo ($usuario_edicao && $usuario_edicao['nivel'] == 'vendedor') ? 'selected' : ''; ?>>Vendedor</option>
                            <option value="gerente" <?php echo ($usuario_edicao && $usuario_edicao['nivel'] == 'gerente') ? 'selected' : ''; ?>>Gerente</option>
                            <option value="admin" <?php echo ($usuario_edicao && $usuario_edicao['nivel'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                    
                    <?php if ($usuario_edicao): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="ativo" name="ativo" <?php echo $usuario_edicao['ativo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativo">Usuário Ativo</label>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-usuario" class="btn btn-primary" name="<?php echo $usuario_edicao ? 'atualizar' : 'adicionar'; ?>">
                    <?php echo $usuario_edicao ? 'Atualizar' : 'Adicionar'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modal-excluir" tabindex="-1" aria-labelledby="modal-excluir-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-excluir-label">Excluir Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o usuário <strong id="nome-usuario-excluir"></strong>?</p>
                <p class="text-danger">Esta ação não poderá ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <form method="post" action="">
                    <input type="hidden" name="id" id="id-usuario-excluir">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="excluir" class="btn btn-danger">Confirmar Exclusão</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Abrir modal de edição automaticamente se tiver usuário para editar
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($usuario_edicao): ?>
        var modalUsuario = new bootstrap.Modal(document.getElementById('modal-usuario'));
        modalUsuario.show();
        <?php endif; ?>
        
        // Configurar modal de exclusão
        var botoesExcluir = document.getElementsByClassName('btn-excluir');
        for (var i = 0; i < botoesExcluir.length; i++) {
            botoesExcluir[i].addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                var nome = this.getAttribute('data-nome');
                document.getElementById('id-usuario-excluir').value = id;
                document.getElementById('nome-usuario-excluir').textContent = nome;
                var modalExcluir = new bootstrap.Modal(document.getElementById('modal-excluir'));
                modalExcluir.show();
            });
        }
        
        // Validação de senha
        document.getElementById('form-usuario').addEventListener('submit', function(e) {
            var senha = document.getElementById('senha').value;
            var confirmarSenha = document.getElementById('confirmar_senha').value;
            
            // Se o campo de senha estiver preenchido, verificar se são iguais
            if (senha && senha !== confirmarSenha) {
                e.preventDefault();
                alert('As senhas não coincidem!');
            }
        });
    });
</script>

<?php include 'footer.php'; ?>
