<?php
// Arquivo simples apenas para teste de AJAX

// Desabilitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Definir cabeçalho JSON
header('Content-Type: application/json');

// Responder com um JSON simples
echo json_encode([
    'status' => 'success',
    'message' => 'Teste AJAX funcionando!',
    'timestamp' => date('Y-m-d H:i:s')
]);