<?php
/**
 * EXTREME PDV - Retorna dados da empresa para impressão
 * 
 * Este arquivo retorna os dados da empresa ou hotel para serem utilizados
 * na impressão de comandas, recibos e documentos
 */

require_once 'config.php';

// Verificar se o usuário está logado
verificarLogin();

// Inicializa resposta
$response = [
    'status' => 'success',
    'nome' => 'Nome',
    'fantasia' => 'Nome Fantasia',
    'endereco' => 'endereco',
    'cidade' => 'cidade',
    'estado' => 'estado',
    'cep' => '68488-000',
    'telefone' => '(99) 9999-9999',
    'cpf_cnpj' => '01.001.001/0001-01',
    'email' => 'maurocarlos.ti@gmail.com',
    'site' => 'www.extremesti.com.br'
];

// Na implementação real, estes dados viriam do banco de dados:
try {
    // Verificar se existe a tabela de configurações da empresa
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'configuracoes_empresa'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Buscar dados da empresa no banco
        $stmt = $pdo->prepare("SELECT * FROM configuracoes_empresa WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config) {
            // Mapeamento específico para os campos encontrados no seu banco de dados
            $mapeamento = [
                'nome' => 'razao_social',
                'fantasia' => 'nome', // Usando razao_social como nome fantasia
                'endereco' => 'endereco',
                'cidade' => 'cidade',
                'estado' => 'estado',
                'telefone' => 'telefone',
                'cpf_cnpj' => 'cnpj',
                'email' => 'email',
                'site' => 'site'
            ];
            
            // Sobrescrever dados padrão com dados do banco
            foreach ($mapeamento as $campo_destino => $campo_banco) {
                if (isset($config[$campo_banco]) && !empty($config[$campo_banco])) {
                    $response[$campo_destino] = $config[$campo_banco];
                }
            }
        }
    }
    
    // Registrar que os dados foram obtidos com sucesso
    error_log("Dados da empresa obtidos com sucesso do banco de dados");
} catch (Exception $e) {
    // Registra o erro para depuração
    error_log("Erro ao buscar dados da empresa: " . $e->getMessage());
    // Silenciosamente ignora erros e usa os dados padrão
}

// Remover BOM que pode causar problemas de JSON
ob_clean();

// Enviar resposta como JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;
?>