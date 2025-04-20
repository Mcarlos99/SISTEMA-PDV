<?php
// Garantir que não haja BOM no início do arquivo
require_once 'config.php';
verificarLogin();

// Verificar se o usuário tem permissão de administrador
if ($_SESSION['usuario_nivel'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Permissão negada']);
    exit;
}

// Verificar parâmetros necessários
if (!isset($_POST['usuario_id']) || !isset($_POST['offset'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Parâmetros incompletos']);
    exit;
}

$id_usuario = intval($_POST['usuario_id']);
$offset = intval($_POST['offset']);
$limite = 10; // Quantas atividades carregar por vez

// Buscar atividades adicionais
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(data_hora, '%d/%m/%Y %H:%i') AS data_formatada,
        acao,
        detalhes
    FROM logs_sistema
    WHERE usuario_id = :usuario_id
    ORDER BY data_hora DESC
    LIMIT :limite OFFSET :offset
");

$stmt->bindParam(':usuario_id', $id_usuario, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
$stmt->execute();
$atividades = $stmt->fetchAll();

// Verificar se tem mais atividades para carregar
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM logs_sistema 
    WHERE usuario_id = :usuario_id
");
$stmt->bindParam(':usuario_id', $id_usuario, PDO::PARAM_INT);
$stmt->execute();
$total = $stmt->fetch()['total'];

$tem_mais = ($total > ($offset + $limite));

// Definir cabeçalho antes de qualquer saída
header('Content-Type: application/json');

// Retornar os dados em formato JSON
echo json_encode([
    'atividades' => $atividades,
    'tem_mais' => $tem_mais
]);