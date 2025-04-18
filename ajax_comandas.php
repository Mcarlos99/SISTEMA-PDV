<?php
require_once 'config.php';
verificarLogin();

// Inicializa resposta
$response = [
    'status' => 'error',
    'message' => 'Nenhuma ação foi solicitada'
];

// Verificar se a ação foi especificada
if (isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    try {
        switch ($acao) {
            // Buscar dados do cliente
            case 'buscar_cliente':
                if (!isset($_POST['cliente_id'])) {
                    throw new Exception('ID do cliente não fornecido');
                }
                
                $cliente_id = (int)$_POST['cliente_id'];
                $cliente_data = $cliente->buscarPorId($cliente_id);
                
                if (!$cliente_data) {
                    throw new Exception('Cliente não encontrado');
                }
                
                // Buscar comandas abertas do cliente
                $comanda_data = $comanda->verificarComandaAberta($cliente_id);
                
                $response = [
                    'status' => 'success',
                    'cliente' => $cliente_data,
                    'comanda_aberta' => $comanda_data
                ];
                break;
            
            // Buscar produto por código ou nome
            case 'buscar_produto':
                if (!isset($_POST['codigo'])) {
                    throw new Exception('Código ou nome do produto não fornecido');
                }
                
                $codigo = $_POST['codigo'];
                
                // Tenta buscar por código primeiro
                $produto_data = $produto->buscarPorCodigo($codigo);
                
                if (!$produto_data) {
                    // Se não encontrar por código, busca produtos que contenham o termo no nome
                    // Esta funcionalidade exigiria uma modificação na classe Produto para adicionar um método de busca por nome
                    $response = [
                        'status' => 'error',
                        'message' => 'Produto não encontrado'
                    ];
                } else {
                    // Verifica se tem estoque
                    if ($produto_data['estoque_atual'] <= 0) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Produto sem estoque disponível'
                        ];
                    } else {
                        $response = [
                            'status' => 'success',
                            'produto' => $produto_data
                        ];
                    }
                }
                break;
            
            // Salvar cliente rápido
            case 'salvar_cliente':
                if (!isset($_POST['nome']) || empty($_POST['nome'])) {
                    throw new Exception('Nome do cliente é obrigatório');
                }
                
                $dados = [
                    'nome' => $_POST['nome'],
                    'cpf_cnpj' => $_POST['cpf_cnpj'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'telefone' => $_POST['telefone'] ?? '',
                    'endereco' => '',
                    'cidade' => '',
                    'estado' => '',
                    'cep' => '',
                    'observacoes' => 'Cliente cadastrado pelo sistema de comandas'
                ];
                
                // Verificar se já existe cliente com este CPF/CNPJ
                if (!empty($dados['cpf_cnpj'])) {
                    $cliente_existente = $cliente->buscarPorCpfCnpj($dados['cpf_cnpj']);
                    if ($cliente_existente) {
                        $response = [
                            'status' => 'success',
                            'message' => 'Cliente já cadastrado',
                            'cliente' => $cliente_existente
                        ];
                        break;
                    }
                }
                
                // Cadastrar novo cliente
                $resultado = $cliente->adicionar($dados);
                
                if (!$resultado) {
                    throw new Exception('Erro ao salvar cliente');
                }
                
                // Buscar o cliente recém-cadastrado
                $cliente_id = $pdo->lastInsertId();
                $novo_cliente = $cliente->buscarPorId($cliente_id);
                
                $response = [
                    'status' => 'success',
                    'message' => 'Cliente cadastrado com sucesso',
                    'cliente' => $novo_cliente
                ];
                break;
            
            // Adicionar produto à comanda via AJAX
            case 'adicionar_produto_comanda':
                if (!isset($_POST['comanda_id']) || !isset($_POST['produto_id']) || !isset($_POST['quantidade'])) {
                    throw new Exception('Dados incompletos para adicionar produto');
                }
                
                $comanda_id = (int)$_POST['comanda_id'];
                $produto_id = (int)$_POST['produto_id'];
                $quantidade = (int)$_POST['quantidade'];
                $observacoes = $_POST['observacoes'] ?? '';
                
                if ($quantidade <= 0) {
                    throw new Exception('A quantidade deve ser maior que zero');
                }
                
                // Adicionar produto à comanda
                $item_id = $comanda->adicionarProduto($comanda_id, $produto_id, $quantidade, $observacoes);
                
                // Buscar dados atualizados da comanda
                $comanda_atualizada = $comanda->buscarPorId($comanda_id);
                $produtos_comanda = $comanda->listarProdutos($comanda_id);
                
                $response = [
                    'status' => 'success',
                    'message' => 'Produto adicionado com sucesso',
                    'item_id' => $item_id,
                    'comanda' => $comanda_atualizada,
                    'produtos' => $produtos_comanda
                ];
                break;
                
            // Obter detalhes da comanda via AJAX
            case 'obter_comanda':
                if (!isset($_POST['comanda_id'])) {
                    throw new Exception('ID da comanda não fornecido');
                }
                
                $comanda_id = (int)$_POST['comanda_id'];
                $comanda_data = $comanda->buscarPorId($comanda_id);
                
                if (!$comanda_data) {
                    throw new Exception('Comanda não encontrada');
                }
                
                $produtos_comanda = $comanda->listarProdutos($comanda_id);
                
                $response = [
                    'status' => 'success',
                    'comanda' => $comanda_data,
                    'produtos' => $produtos_comanda
                ];
                break;
                
            default:
                $response = [
                    'status' => 'error',
                    'message' => 'Ação não reconhecida'
                ];
        }
    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// Remover BOM que pode causar problemas de JSON
ob_clean();

// Enviar resposta como JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;
?>