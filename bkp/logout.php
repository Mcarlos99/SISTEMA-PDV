<?php
require_once 'config.php';

// Registrar o logout no log do sistema
if (isset($_SESSION['usuario_id'])) {
    $log->registrar('Logout', 'Usuário realizou logout');
}

// Executar logout
$usuario->logout();

// Redirecionar para página de login
header('Location: login.php');
exit;