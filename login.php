<?php
require_once 'config.php';

// Verificar se o usuário já está logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Processar tentativa de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
    
    if (empty($usuario) || empty($senha)) {
        alerta('Preencha todos os campos.', 'danger');
    } else {
        // Tentativa de login
        $user = new Usuario($pdo);
        if ($user->login($usuario, $senha)) {
            header('Location: index.php');
            exit;
        } else {
            alerta('Usuário ou senha incorretos.', 'danger');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema PDV</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
        }
        
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .card-header {
            background: white;
            text-align: center;
            padding: 30px 20px;
            border: none;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .card-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-control {
            padding: 12px 15px;
            height: auto;
            border-radius: 8px 0 0 8px;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: 8px 0 0 8px;
            margin-bottom: 1rem;
            padding: 12px 15px;
            color: #6c757d;
            
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .card-footer {
            background: white;
            border: none;
            text-align: center;
            padding: 20px 30px 30px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        .alert-danger {
            background-color: #fee2e2;
            border-color: #fecaca;
            color: #ef4444;
        }
        
        .copyright {
            color: rgba(255,255,255,0.7);
            text-align: center;
            margin-top: 2rem;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <div class="logo">
                    <i class="fas fa-cash-register"></i>
                </div>
                <h4 class="card-title">Sistema PDV</h4>
                <p class="card-subtitle">Faça login para acessar o sistema</p>
            </div>
            <div class="card-body">
                <?php exibirAlerta(); ?>
                
                <form method="post" action="login.php">
                    <div class="input-group mb-3">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" name="usuario" placeholder="Nome de usuário" required autofocus>
                    </div>
                    
                    <div class="input-group mb-4">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="senha" placeholder="Senha" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Entrar
                    </button>
                </form>
            </div>
            <div class="card-footer">
    <p class="mb-0">
        Esqueceu sua senha? Entre em contato com o administrador<br>
        <a href="https://wa.me/5594981709809?text=Gostaria%20de%20saber%20mais%20sobre%20o%20Sistema%20de%20PDV!" target="_blank" class="text-primary">
            <i class="fab fa-whatsapp text-success me-1"></i>Mauro Carlos - 94 98170-9809
        </a>
    </p>
</div>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Sistema PDV - Desenvolvido por Mauro Carlos
        </div>
    </div>
    
    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fechar alertas automaticamente após 5 segundos
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>