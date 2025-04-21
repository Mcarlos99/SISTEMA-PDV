<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Inicializar variáveis
$usuario_obj = new Usuario($pdo);
$usuario_dados = $usuario_obj->buscarPorId($_SESSION['usuario_id']);
$mensagem_sucesso = '';
$mensagem_erro = '';

// Processar formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['atualizar_perfil'])) {
        $dados = [
            'nome' => $_POST['nome'],
            'email' => $_POST['email'],
            'usuario' => $_POST['usuario']
        ];
        
        // Verificar se a senha foi preenchida para alteração
        if (!empty($_POST['senha']) && !empty($_POST['confirmar_senha'])) {
            if ($_POST['senha'] == $_POST['confirmar_senha']) {
                $dados['senha'] = $_POST['senha'];
            } else {
                $mensagem_erro = 'As senhas não coincidem!';
            }
        }
        
        // Se não houve erro de senha, atualizar perfil
        if (empty($mensagem_erro)) {
            if ($usuario_obj->atualizar($_SESSION['usuario_id'], $dados)) {
                // Atualizar variáveis de sessão
                $_SESSION['usuario_nome'] = $dados['nome'];
                
                // Registrar no log do sistema
                if (isset($GLOBALS['log'])) {
                    $GLOBALS['log']->registrar('Perfil', 'Perfil atualizado com sucesso');
                }
                
                $mensagem_sucesso = 'Perfil atualizado com sucesso!';
                $usuario_dados = $usuario_obj->buscarPorId($_SESSION['usuario_id']); // Recarregar dados
            } else {
                $mensagem_erro = 'Erro ao atualizar perfil. Tente novamente.';
            }
        }
    }
}

// Template da página
$titulo_pagina = 'Meu Perfil - EXTREME PDV';
include 'header.php';
?>
<!-- PARTE 2 -->
<div class="container-fluid py-4">
    <!-- Cabeçalho da Página -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="mb-1">
                <i class="fas fa-user-circle me-2 text-primary"></i>
                Meu Perfil
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item active">Meu Perfil</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (!empty($mensagem_sucesso)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $mensagem_sucesso; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($mensagem_erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $mensagem_erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
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
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-user me-2 text-muted"></i>
                            <strong>Usuário:</strong> <?php echo esc($usuario_dados['usuario']); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-envelope me-2 text-muted"></i>
                            <strong>E-mail:</strong> <?php echo esc($usuario_dados['email']); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-key me-2 text-muted"></i>
                            <strong>Senha:</strong> ••••••••
                        </li>
                    </ul>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-grid">
                        <button type="button" class="btn btn-primary" id="botaoEditarPerfil">
                            <i class="fas fa-edit me-2"></i>
                            Editar Perfil
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- PARTE 3 -->
        <!-- Coluna com formulário de edição -->
        <div class="col-md-8 mb-4" id="divFormulario" style="display: none;">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Atualizar Informações
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="" id="formPerfil">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo esc($usuario_dados['nome']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Nome de Usuário</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo esc($usuario_dados['usuario']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo esc($usuario_dados['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="senha" class="form-label">Nova Senha (deixe em branco para manter a atual)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha">
                            </div>
                            <div class="form-text">A senha deve ter no mínimo 6 caracteres</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" class="btn btn-secondary" id="botaoCancelar">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-success" name="atualizar_perfil">
                                <i class="fas fa-save me-2"></i>
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Coluna com histórico de atividades -->
        <div class="col-md-8 mb-4" id="divHistorico">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Atividades Recentes
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Ação</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Buscar logs do usuário
                                $logs = [];
                                if (isset($GLOBALS['log'])) {
                                    $stmt = $pdo->prepare("
                                        SELECT 
                                            DATE_FORMAT(data_hora, '%d/%m/%Y %H:%i') AS data_formatada,
                                            acao,
                                            detalhes
                                        FROM logs_sistema
                                        WHERE usuario_id = :usuario_id
                                        ORDER BY data_hora DESC
                                        LIMIT 10
                                    ");
                                    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id'], PDO::PARAM_INT);
                                    $stmt->execute();
                                    $logs = $stmt->fetchAll();
                                }
                                
                                if (empty($logs)):
                                ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3">Nenhuma atividade recente encontrada</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo esc($log['data_formatada']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo esc($log['acao']); ?></span></td>
                                        <td><?php echo esc($log['detalhes']); ?></td>
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
    document.addEventListener('DOMContentLoaded', function() {
        const botaoEditarPerfil = document.getElementById('botaoEditarPerfil');
        const botaoCancelar = document.getElementById('botaoCancelar');
        const divFormulario = document.getElementById('divFormulario');
        const divHistorico = document.getElementById('divHistorico');
        
        // Mostrar formulário ao clicar em Editar Perfil
        botaoEditarPerfil.addEventListener('click', function() {
            divFormulario.style.display = 'block';
            divHistorico.style.display = 'none';
        });
        
        // Esconder formulário ao clicar em Cancelar
        botaoCancelar.addEventListener('click', function() {
            divFormulario.style.display = 'none';
            divHistorico.style.display = 'block';
        });
        
        // Validação do formulário
        const formPerfil = document.getElementById('formPerfil');
        formPerfil.addEventListener('submit', function(event) {
            const senha = document.getElementById('senha').value;
            const confirmarSenha = document.getElementById('confirmar_senha').value;
            
            // Se a senha foi preenchida, verificar se confirma
            if (senha && senha !== confirmarSenha) {
                event.preventDefault();
                alert('As senhas não coincidem. Por favor, verifique.');
            }
            
            // Verificar tamanho mínimo da senha
            if (senha && senha.length < 6) {
                event.preventDefault();
                alert('A senha deve ter no mínimo 6 caracteres.');
            }
        });
        
        // Se tem mensagem de sucesso ou erro, mostrar o formulário
        <?php if (!empty($mensagem_sucesso) || !empty($mensagem_erro)): ?>
        divFormulario.style.display = 'block';
        divHistorico.style.display = 'none';
        <?php endif; ?>
    });
</script>

<?php include 'footer.php'; ?>
