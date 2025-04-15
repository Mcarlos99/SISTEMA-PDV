<?php
require_once 'config.php';
verificarLogin();

// Processar ajuste de estoque
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajustar_estoque'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'];
    $tipo = $_POST['tipo'];
    $observacao = $_POST['observacao'];
    
    if (empty($produto_id) || empty($quantidade) || $quantidade <= 0) {
        alerta('Dados inválidos para ajuste de estoque!', 'danger');
    } else {
        // Registrar movimentação de estoque
        $dados = [
            'produto_id' => $produto_id,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
            'observacao' => $observacao,
            'origem' => 'ajuste_manual'
        ];
        
        if ($produto->registrarMovimentacao($dados)) {
            alerta('Estoque ajustado com sucesso!', 'success');
        } else {
            alerta('Erro ao ajustar estoque!', 'danger');
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: estoque.php');
    exit;
}

// Filtros para movimentações
$filtro_produto = null;
$filtro_data_inicio = null;
$filtro_data_fim = null;

if (isset($_GET['filtrar'])) {
    $filtro_produto = !empty($_GET['produto_id']) ? $_GET['produto_id'] : null;
    $filtro_data_inicio = !empty($_GET['data_inicio']) ? $_GET['data_inicio'] . ' 00:00:00' : null;
    $filtro_data_fim = !empty($_GET['data_fim']) ? $_GET['data_fim'] . ' 23:59:59' : null;
}

// Template da página
$titulo_pagina = 'Controle de Estoque';
include 'header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Controle de Estoque</h1>
    
    <div class="row">
        <!-- Coluna para ajuste de estoque -->
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ajuste de Estoque</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="produto_id" class="form-label">Produto *</label>
                            <select class="form-select" id="produto_id" name="produto_id" required>
                                <option value="">Selecione um produto</option>
                                <?php
                                $produtos = $produto->listar();
                                foreach ($produtos as $p) {
                                    if ($p['ativo']) {
                                        echo '<option value="'.$p['id'].'" data-estoque="'.$p['estoque_atual'].'">'.$p['codigo'].' - '.$p['nome'].' (Estoque: '.$p['estoque_atual'].')</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tipo" class="form-label">Tipo de Ajuste *</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="entrada">Entrada</option>
                                    <option value="saida">Saída</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="quantidade" class="form-label">Quantidade *</label>
                                <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
                                <div id="estoque-insuficiente" class="text-danger small" style="display: none;">
                                    Estoque insuficiente para esta saída!
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacao" class="form-label">Motivo do Ajuste *</label>
                            <textarea class="form-control" id="observacao" name="observacao" rows="3" required></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="ajustar_estoque" class="btn btn-primary" id="btn-ajustar">
                                <i class="fas fa-save"></i> Registrar Ajuste
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Produtos com Estoque Baixo</h5>
                </div>
                <div class="card-body">
                    <?php
                    $produtos_estoque_baixo = $produto->listarEstoqueBaixo();
                    if (count($produtos_estoque_baixo) > 0) {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm table-striped">';
                        echo '<thead><tr><th>Código</th><th>Produto</th><th>Atual</th><th>Mínimo</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($produtos_estoque_baixo as $p) {
                            echo '<tr>';
                            echo '<td>'.$p['codigo'].'</td>';
                            echo '<td>'.$p['nome'].'</td>';
                            echo '<td class="text-danger">'.$p['estoque_atual'].'</td>';
                            echo '<td>'.$p['estoque_minimo'].'</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    } else {
                        echo '<p class="text-center">Nenhum produto com estoque baixo.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Coluna para movimentações de estoque -->
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Movimentações de Estoque</h5>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtro-movimentacoes">
                        <i class="fas fa-filter"></i> Filtros
                    </button>
                </div>
                
                <div class="collapse <?php echo isset($_GET['filtrar']) ? 'show' : ''; ?>" id="filtro-movimentacoes">
                    <div class="card-body border-bottom">
                        <form method="get" action="">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="produto_id_filtro" class="form-label">Produto</label>
                                    <select class="form-select" id="produto_id_filtro" name="produto_id">
                                        <option value="">Todos os produtos</option>
                                        <?php
                                        foreach ($produtos as $p) {
                                            $selected = ($filtro_produto == $p['id']) ? 'selected' : '';
                                            echo '<option value="'.$p['id'].'" '.$selected.'>'.$p['codigo'].' - '.$p['nome'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="data_inicio" class="form-label">Data Inicial</label>
                                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo isset($_GET['data_inicio']) ? $_GET['data_inicio'] : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="data_fim" class="form-label">Data Final</label>
                                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo isset($_GET['data_fim']) ? $_GET['data_fim'] : ''; ?>">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" name="filtrar" class="btn btn-primary btn-sm">Aplicar Filtros</button>
                                    <a href="estoque.php" class="btn btn-secondary btn-sm">Limpar Filtros</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Produto</th>
                                    <th>Tipo</th>
                                    <th>Quantidade</th>
                                    <th>Origem</th>
                                    <th>Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $movimentacoes = $relatorio->movimentacoesEstoque($filtro_produto, $filtro_data_inicio, $filtro_data_fim);
                                foreach ($movimentacoes as $m) {
                                    echo '<tr>';
                                    echo '<td>'.$m['data_formatada'].'</td>';
                                    echo '<td>'.$m['produto_codigo'].' - '.$m['produto_nome'].'</td>';
                                    
                                    // Tipo
                                    if ($m['tipo'] == 'entrada') {
                                        echo '<td><span class="badge bg-success">Entrada</span></td>';
                                    } else if ($m['tipo'] == 'saida') {
                                        echo '<td><span class="badge bg-danger">Saída</span></td>';
                                    } else {
                                        echo '<td><span class="badge bg-info">Ajuste</span></td>';
                                    }
                                    
                                    echo '<td>'.$m['quantidade'].'</td>';
                                    
                                    // Origem
                                    switch ($m['origem']) {
                                        case 'compra':
                                            echo '<td><span class="badge bg-primary">Compra</span></td>';
                                            break;
                                        case 'venda':
                                            echo '<td><span class="badge bg-info">Venda</span></td>';
                                            break;
                                        case 'ajuste_manual':
                                            echo '<td><span class="badge bg-warning">Ajuste Manual</span></td>';
                                            break;
                                        case 'devolucao':
                                            echo '<td><span class="badge bg-secondary">Devolução</span></td>';
                                            break;
                                        default:
                                            echo '<td><span class="badge bg-secondary">Outro</span></td>';
                                    }
                                    
                                    echo '<td>'.$m['observacao'].'</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const produtoSelect = document.getElementById('produto_id');
        const tipoSelect = document.getElementById('tipo');
        const quantidadeInput = document.getElementById('quantidade');
        const estoqueInsuficiente = document.getElementById('estoque-insuficiente');
        const btnAjustar = document.getElementById('btn-ajustar');
        
        // Verificar estoque ao alterar produto, tipo ou quantidade
        function verificarEstoque() {
            const produtoOption = produtoSelect.options[produtoSelect.selectedIndex];
            
            if (produtoOption.value && tipoSelect.value === 'saida') {
                const estoqueAtual = parseInt(produtoOption.getAttribute('data-estoque')) || 0;
                const quantidade = parseInt(quantidadeInput.value) || 0;
                
                if (quantidade > estoqueAtual) {
                    estoqueInsuficiente.style.display = 'block';
                    btnAjustar.disabled = true;
                } else {
                    estoqueInsuficiente.style.display = 'none';
                    btnAjustar.disabled = false;
                }
            } else {
                estoqueInsuficiente.style.display = 'none';
                btnAjustar.disabled = false;
            }
        }
        
        produtoSelect.addEventListener('change', verificarEstoque);
        tipoSelect.addEventListener('change', verificarEstoque);
        quantidadeInput.addEventListener('input', verificarEstoque);
        
        // Validar formulário antes de enviar
        document.querySelector('form').addEventListener('submit', function(e) {
            const produtoOption = produtoSelect.options[produtoSelect.selectedIndex];
            
            if (!produtoSelect.value || !quantidadeInput.value || quantidadeInput.value <= 0) {
                e.preventDefault();
                alert('Preencha todos os campos corretamente!');
                return;
            }
            
            if (tipoSelect.value === 'saida') {
                const estoqueAtual = parseInt(produtoOption.getAttribute('data-estoque')) || 0;
                const quantidade = parseInt(quantidadeInput.value) || 0;
                
                if (quantidade > estoqueAtual) {
                    e.preventDefault();
                    alert('Estoque insuficiente para esta saída!');
                    return;
                }
            }
            
            if (!document.getElementById('observacao').value.trim()) {
                e.preventDefault();
                alert('Informe o motivo do ajuste!');
                return;
            }
        });
    });
</script>

<?php include 'footer.php'; ?>
