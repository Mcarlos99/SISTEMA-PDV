<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar permissões (apenas admin e gerente podem acessar)
if (!in_array($_SESSION['usuario_nivel'], ['admin', 'gerente'])) {
    alerta('Você não tem permissão para acessar esta funcionalidade.', 'danger');
    header('Location: index.php');
    exit;
}

// Processar ação
if (isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    // Adicionar novo produto
    if ($acao == 'adicionar') {
        try {
            // Validar dados obrigatórios
            if (empty($_POST['codigo']) || empty($_POST['nome']) || 
                !isset($_POST['preco_custo']) || !isset($_POST['preco_venda'])) {
                throw new Exception("Todos os campos obrigatórios devem ser preenchidos.");
            }
            
            // Preparar dados
            $dados = [
                'codigo' => $_POST['codigo'],
                'nome' => $_POST['nome'],
                'descricao' => $_POST['descricao'] ?? '',
                'preco_custo' => (float)$_POST['preco_custo'],
                'preco_venda' => (float)$_POST['preco_venda'],
                'estoque_atual' => (int)$_POST['estoque_atual'] ?? 0,
                'estoque_minimo' => (int)$_POST['estoque_minimo'] ?? 5,
                'categoria_id' => $_POST['categoria_id'] ? (int)$_POST['categoria_id'] : null,
                'ativo' => isset($_POST['ativo']) ? 1 : 0
            ];
            
            // Verificar se já existe um produto com o mesmo código
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE codigo = :codigo");
            $stmt->bindParam(':codigo', $dados['codigo']);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Já existe um produto cadastrado com este código.");
            }
            
            // Adicionar produto
            if ($produto->adicionar($dados)) {
                alerta('Produto adicionado com sucesso!', 'success');
                header('Location: produtos.php');
                exit;
            } else {
                throw new Exception("Erro ao adicionar o produto.");
            }
            
        } catch (Exception $e) {
            alerta($e->getMessage(), 'danger');
            header('Location: produtos.php?acao=novo');
            exit;
        }
    } 
    // Editar produto existente
    elseif ($acao == 'editar') {
        try {
            // Verificar se o ID foi fornecido
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                throw new Exception("ID do produto não informado.");
            }
            
            $id = (int)$_POST['id'];
            
            // Validar dados obrigatórios
            if (empty($_POST['codigo']) || empty($_POST['nome']) || 
                !isset($_POST['preco_custo']) || !isset($_POST['preco_venda'])) {
                throw new Exception("Todos os campos obrigatórios devem ser preenchidos.");
            }
            
            // Preparar dados
            $dados = [
                'codigo' => $_POST['codigo'],
                'nome' => $_POST['nome'],
                'descricao' => $_POST['descricao'] ?? '',
                'preco_custo' => (float)$_POST['preco_custo'],
                'preco_venda' => (float)$_POST['preco_venda'],
                'estoque_atual' => (int)$_POST['estoque_atual'],
                'estoque_minimo' => (int)$_POST['estoque_minimo'],
                'categoria_id' => $_POST['categoria_id'] ? (int)$_POST['categoria_id'] : null,
                'ativo' => isset($_POST['ativo']) ? 1 : 0
            ];
            
            // Verificar se existe outro produto com o mesmo código (exceto o atual)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE codigo = :codigo AND id != :id");
            $stmt->bindParam(':codigo', $dados['codigo']);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Já existe outro produto cadastrado com este código.");
            }
            
            // Atualizar produto
            if ($produto->atualizar($id, $dados)) {
                alerta('Produto atualizado com sucesso!', 'success');
                header('Location: produtos.php');
                exit;
            } else {
                throw new Exception("Erro ao atualizar o produto.");
            }
            
        } catch (Exception $e) {
            alerta($e->getMessage(), 'danger');
            // Retornar para a página de edição
            header("Location: produtos.php?acao=editar&id={$_POST['id']}");
            exit;
        }
    } 
    // Ação desconhecida
    else {
        alerta('Ação desconhecida.', 'danger');
        header('Location: produtos.php');
        exit;
    }
} 
// Nenhuma ação especificada
else {
    alerta('Parâmetros insuficientes.', 'danger');
    header('Location: produtos.php');
    exit;
}
?>