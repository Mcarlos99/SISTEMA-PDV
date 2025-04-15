<?php
require_once 'config.php';
verificarLogin();

// Verificar ação
$acao = $_POST['acao'] ?? '';

switch ($acao) {
    case 'buscar_produto':
        buscarProduto();
        break;
    case 'salvar_cliente':
        salvarCliente();
        break;
    case 'finalizar_venda':
        finalizarVenda();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Ação inválida']);
}

// Função para buscar produto
function buscarProduto() {
    global $produto;
    
    $codigo = $_POST['codigo'] ?? '';
    
    if (empty($codigo)) {
        echo json_encode(['status' => 'error', 'message' => 'Código do produto não informado']);
        return;
    }
    
    // Tenta buscar pelo código exato
    $prod = $produto->buscarPorCodigo($codigo);
    
    if (!$prod) {
        // Se não achar pelo código, tenta pelo nome (parcial)
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT * FROM produtos 
            WHERE nome LIKE :nome AND ativo = TRUE 
            ORDER BY nome LIMIT 1
        ");
        $param = "%{$codigo}%";
        $stmt->bindParam(':nome', $param);
        $stmt->execute();
        $prod = $stmt->fetch();
    }
    
    if ($prod) {
        // Verifica estoque
        if ($prod['estoque_atual'] <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Produto sem estoque disponível']);
            return;
        }
        
        echo json_encode(['status' => 'success', 'produto' => $prod]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Produto não encontrado']);
    }
}

// Função para salvar cliente rápido
function salvarCliente() {
    global $cliente;
    
    $nome = $_POST['nome'] ?? '';
    $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($nome)) {
        echo json_encode(['status' => 'error', 'message' => 'Nome do cliente é obrigatório']);
        return;
    }
    
    // Verifica se já existe cliente com esse CPF/CNPJ
    if (!empty($cpf_cnpj)) {
        $cliente_existente = $cliente->buscarPorCpfCnpj($cpf_cnpj);
        if ($cliente_existente) {
            echo json_encode(['status' => 'error', 'message' => 'Já existe um cliente com este CPF/CNPJ']);
            return;
        }
    }
    
    $dados = [
        'nome' => $nome,
        'cpf_cnpj' => $cpf_cnpj,
        'telefone' => $telefone,
        'email' => $email,
        'endereco' => '',
        'cidade' => '',
        'estado' => '',
        'cep' => '',
        'observacoes' => 'Cliente cadastrado via PDV'
    ];
    
    if ($cliente->adicionar($dados)) {
        // Busca o ID do cliente recém cadastrado
        $stmt = $GLOBALS['pdo']->prepare("SELECT id FROM clientes WHERE nome = :nome ORDER BY id DESC LIMIT 1");
        $stmt->bindParam(':nome', $nome);
        $stmt->execute();
        $id = $stmt->fetch()['id'];
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Cliente cadastrado com sucesso',
            'cliente' => [
                'id' => $id,
                'nome' => $nome
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao cadastrar cliente']);
    }
}

// Função para finalizar venda
function finalizarVenda() {
    global $venda;
    
    $venda_json = $_POST['venda'] ?? '';
    
    if (empty($venda_json)) {
        echo json_encode(['status' => 'error', 'message' => 'Dados da venda não informados']);
        return;
    }
    
    $venda_dados = json_decode($venda_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao processar dados da venda']);
        return;
    }
    
    if (empty($venda_dados['itens'])) {
        echo json_encode(['status' => 'error', 'message' => 'Venda sem itens']);
        return;
    }
    
    // Adicionar dados padrão
    $venda_dados['status'] = 'finalizada';
    
    // Finalizar venda
    $venda_id = $venda->adicionar($venda_dados);
    
    if ($venda_id) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Venda finalizada com sucesso',
            'venda_id' => $venda_id
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao finalizar venda']);
    }
}
