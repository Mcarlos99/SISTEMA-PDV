<?php
// Remover qualquer saída antes dos cabeçalhos
ob_clean();

require_once 'config.php';
verificarLogin();

// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Desabilitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Definir cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

// Verificar ação
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

try {
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
} catch (Exception $e) {
    // Captura qualquer exceção não tratada
    error_log("Erro não tratado em ajax_pdv.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro interno: ' . $e->getMessage()]);
}

// Função para buscar produto
function buscarProduto() {
    global $produto;
    
    try {
        $codigo = isset($_POST['codigo']) ? $_POST['codigo'] : '';
        
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
    } catch (Exception $e) {
        error_log("Erro ao buscar produto: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar produto: ' . $e->getMessage()]);
    }
}

// Função para salvar cliente rápido
function salvarCliente() {
    global $cliente;
    
    try {
        $nome = isset($_POST['nome']) ? $_POST['nome'] : '';
        $cpf_cnpj = isset($_POST['cpf_cnpj']) ? $_POST['cpf_cnpj'] : '';
        $telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        
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
            $result = $stmt->fetch();
            
            if ($result) {
                $id = $result['id'];
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Cliente cadastrado com sucesso',
                    'cliente' => [
                        'id' => $id,
                        'nome' => $nome
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Cliente cadastrado, mas não foi possível obter o ID']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao cadastrar cliente']);
        }
    } catch (Exception $e) {
        error_log("Erro ao salvar cliente: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar cliente: ' . $e->getMessage()]);
    }
}

// Função para finalizar venda
function finalizarVenda() {
    global $venda, $caixa, $config_sistema;
    
    try {
        error_log("Iniciando finalização de venda");
        
        $venda_json = isset($_POST['venda']) ? $_POST['venda'] : '';
        
        if (empty($venda_json)) {
            echo json_encode(['status' => 'error', 'message' => 'Dados da venda não informados']);
            return;
        }
        
        $venda_dados = json_decode($venda_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $erro = json_last_error_msg();
            error_log("Erro ao decodificar JSON da venda: " . $erro);
            echo json_encode(['status' => 'error', 'message' => 'Erro ao processar dados da venda: ' . $erro]);
            return;
        }
        
        if (empty($venda_dados['itens'])) {
            echo json_encode(['status' => 'error', 'message' => 'Venda sem itens']);
            return;
        }
        
        // Tratar cliente_id vazio ou inválido
        if (empty($venda_dados['cliente_id']) || $venda_dados['cliente_id'] === "null" || $venda_dados['cliente_id'] === "") {
            $venda_dados['cliente_id'] = null;
        }
        
        // Log para debug
        error_log("Dados da venda preparados: " . print_r($venda_dados, true));
        
        // Adicionar dados padrão
        $venda_dados['status'] = 'finalizada';
        
        // Finalizar venda
        $venda_id = $venda->adicionar($venda_dados);
        
        if ($venda_id) {
            error_log("Venda finalizada com ID: " . $venda_id);
            
            // Registrar a venda no caixa se a configuração exigir caixa aberto
            if ($config_sistema->buscar()['caixa_obrigatorio'] == 1) {
                $caixa_aberto = $caixa->verificarCaixaAberto();
                if ($caixa_aberto) {
                    try {
                        // Adicionar a venda como movimentação no caixa
                        $dados_movimentacao = [
                            'tipo' => 'venda',
                            'valor' => $venda_dados['valor_total'],
                            'forma_pagamento' => $venda_dados['forma_pagamento'],
                            'documento_id' => $venda_id,
                            'observacoes' => 'Venda #' . $venda_id
                        ];
                        
                        $movimento_id = $caixa->adicionarMovimentacao($dados_movimentacao);
                        error_log("Movimentação de caixa registrada com ID: " . $movimento_id);
                    } catch (Exception $e) {
                        // Apenas registra o erro, não impede a conclusão da venda
                        error_log("Erro ao registrar venda no caixa: " . $e->getMessage());
                    }
                } else {
                    error_log("Caixa não está aberto, movimentação não registrada");
                }
            }
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Venda finalizada com sucesso',
                'venda_id' => $venda_id
            ]);
        } else {
            error_log("Falha ao finalizar venda. Retorno false da função venda->adicionar()");
            echo json_encode(['status' => 'error', 'message' => 'Erro ao finalizar venda. Verifique o log para mais detalhes.']);
        }
    } catch (Exception $e) {
        error_log("Exceção ao finalizar venda: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
        echo json_encode(['status' => 'error', 'message' => 'Erro ao finalizar venda: ' . $e->getMessage()]);
    }
}
