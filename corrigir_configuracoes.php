<?php
require_once 'config.php';

// Verificar se o usuário está logado e é administrador
verificarLogin();
if ($_SESSION['usuario_nivel'] != 'admin') {
    header('Location: index.php');
    exit;
}

// Função para exibir resultados em formato legível
function exibirResultado($titulo, $resultado) {
    echo "<h3>$titulo</h3>";
    echo "<pre>";
    print_r($resultado);
    echo "</pre>";
    echo "<hr>";
}

// Cabeçalho da página
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correção de Configurações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1>Ferramenta de Diagnóstico e Correção de Configurações</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Configurações Atuais</h2>
            </div>
            <div class="card-body">
                <?php
                // Buscar configurações atuais
                $stmt = $pdo->query("SELECT * FROM configuracoes_sistema LIMIT 1");
                $configuracoes = $stmt->fetch(PDO::FETCH_ASSOC);
                exibirResultado("Dados do banco", $configuracoes);
                ?>
            </div>
        </div>
        
        <?php
        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Processar formulário
            if (isset($_POST['corrigir_checkbox'])) {
                try {
                    // Atualizar diretamente os valores dos checkboxes
                    $estoque_negativo = isset($_POST['estoque_negativo']) ? 1 : 0;
                    $alerta_estoque = isset($_POST['alerta_estoque']) ? 1 : 0;
                    $impressao_automatica = isset($_POST['impressao_automatica']) ? 1 : 0;
                    
                    $sql = "UPDATE configuracoes_sistema SET 
                        estoque_negativo = :estoque_negativo,
                        alerta_estoque = :alerta_estoque,
                        impressao_automatica = :impressao_automatica
                        WHERE id = :id";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':id', $configuracoes['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':estoque_negativo', $estoque_negativo, PDO::PARAM_INT);
                    $stmt->bindValue(':alerta_estoque', $alerta_estoque, PDO::PARAM_INT);
                    $stmt->bindValue(':impressao_automatica', $impressao_automatica, PDO::PARAM_INT);
                    
                    $resultado = $stmt->execute();
                    
                    if ($resultado) {
                        echo '<div class="alert alert-success">Configurações atualizadas com sucesso!</div>';
                        
                        // Recarregar os dados atualizados
                        $stmt = $pdo->query("SELECT * FROM configuracoes_sistema LIMIT 1");
                        $configuracoes = $stmt->fetch(PDO::FETCH_ASSOC);
                        exibirResultado("Dados atualizados", $configuracoes);
                    } else {
                        echo '<div class="alert alert-danger">Erro ao atualizar configurações.</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Exceção: ' . $e->getMessage() . '</div>';
                }
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Corrigir Configurações</h2>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="estoque_negativo" name="estoque_negativo" value="1" <?php echo $configuracoes['estoque_negativo'] == 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="estoque_negativo">Bloquear venda quando estoque for insuficiente</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="alerta_estoque" name="alerta_estoque" value="1" <?php echo $configuracoes['alerta_estoque'] == 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="alerta_estoque">Mostrar alerta de estoque baixo no painel</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="impressao_automatica" name="impressao_automatica" value="1" <?php echo $configuracoes['impressao_automatica'] == 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="impressao_automatica">Impressão automática de comprovante após venda</label>
                        </div>
                    </div>
                    
                    <button type="submit" name="corrigir_checkbox" class="btn btn-primary">Corrigir Configurações</button>
                </form>
                
                <div class="mt-4">
                    <h3>Consultas SQL para correção manual</h3>
                    <p>Execute estas consultas diretamente no banco de dados se necessário:</p>
                    <pre>
-- Para ativar todas as opções
UPDATE configuracoes_sistema SET 
estoque_negativo = 1,
alerta_estoque = 1,
impressao_automatica = 1
WHERE id = <?php echo $configuracoes['id']; ?>;

-- Para desativar todas as opções
UPDATE configuracoes_sistema SET 
estoque_negativo = 0,
alerta_estoque = 0,
impressao_automatica = 0
WHERE id = <?php echo $configuracoes['id']; ?>;
                    </pre>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="configuracoes.php" class="btn btn-secondary">Voltar para Configurações</a>
        </div>
    </div>
</body>
</html>