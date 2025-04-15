<?php
require_once 'config.php';
verificarLogin();

// Verificar se o usuário tem permissão de administrador
if ($_SESSION['usuario_nivel'] != 'admin') {
    die('Acesso negado');
}

// Verificar se o arquivo foi especificado
if (!isset($_GET['arquivo']) || empty($_GET['arquivo'])) {
    die('Arquivo não especificado');
}

// Obter o nome do arquivo
$arquivo = basename($_GET['arquivo']);

// Verificar se é um arquivo de backup válido
if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $arquivo)) {
    die('Arquivo inválido');
}

// Caminho completo para o arquivo
$caminho_arquivo = dirname(__FILE__) . '/backups/' . $arquivo;

// Verificar se o arquivo existe
if (!file_exists($caminho_arquivo)) {
    die('Arquivo não encontrado');
}

// Registrar o download no log
$log->registrar('Backup', "Download do arquivo de backup: {$arquivo}");

// Configurar cabeçalhos para download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($caminho_arquivo));

// Enviar o arquivo
readfile($caminho_arquivo);
exit;
