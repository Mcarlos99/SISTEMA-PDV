<?php
// Configurações do banco de dados
$config = [
    'db_host' => 'localhost',
    'db_name' => 'extremes_pdv',
    'db_user' => 'extremes_pdv',     // Altere para seu usuário do MySQL
    'db_pass' => 'Mjunior@123',         // Altere para sua senha do MySQL
    'charset' => 'utf8mb4',
];

// Inicia a sessão
session_start();

// Carrega o arquivo principal do sistema
require_once 'sistema.php';

// Conecta ao banco de dados
$pdo = conectarBD($config);

// Cria as tabelas se não existirem
$tabelas = new TabelasBD($pdo);
$tabelas->criarTabelas();

// Inicializa as classes do sistema
$usuario = new Usuario($pdo);
$produto = new Produto($pdo);
$categoria = new Categoria($pdo);
$cliente = new Cliente($pdo);
$fornecedor = new Fornecedor($pdo);
$venda = new Venda($pdo);
$compra = new Compra($pdo);
$relatorio = new Relatorio($pdo);
$log = new LogSistema($pdo);
// $backup = new Backup($pdo, $config); // Removido
$config_empresa = new ConfiguracaoEmpresa($pdo);
$config_sistema = new ConfiguracaoSistema($pdo);
// Inicializa a classe Caixa
$caixa = new Caixa($pdo);
// Inicializa a classe Comanda
$comanda = new Comanda($pdo);