<?php
require_once 'config.php';
verificarLogin();

// Processar o formulário de compra
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['salvar_compra'])) {
        $compra_dados = [
            'fornecedor_id' => !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null,
            'valor_total' => $_POST['valor_total'],
            'status' => $_POST['status'],
            'observacoes' => $_POST['observacoes'],
            'itens' => []
        ];
        
        // Processar itens da compra
        $produtos_id = $_POST['produto_id'];
        $quantidades = $_POST['quantidade'];
        $precos = $_POST['preco_unitario'];
        
        for ($i = 0; $i < count($produtos_id); $i++) {
            if (!empty($produtos_id[$i]) && !empty($quantidades[$i]) && !empty($precos[$i])) {
                $compra_dados['itens'][] = [
                    'produto_id' => $produtos_id[$i],
                    'quantidade' => $quantidades[$i],
                    'preco_unitario' => str_replace(',', '.', $precos[$i])
                ];
            }
        }
        
        // Verificar se há itens
        if (count($compra_dados['itens']) == 0) {
            alerta('É necessário adicionar pelo menos um produto à compra!', 'danger');
        } else {
            // Salvar compra
            $compra_id = $compra->adicionar($compra_dados);
            
            if ($compra_id) {
                alerta('Compra registrada com sucesso!', 'success');
                header('Location: compras.php?id=' . $compra_id);
                exit;
            } else {
                alerta('Erro ao registrar compra!', 'danger');
            }
        }
    }
}

// Template da página
$titulo_pagina = 'Nova Compra';
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Nova Compra</h1>
        <a href="compras.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <form method="post" action="" id="form-compra">
        <div class="row">
            <!-- Dados da Compra -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Dados da Compra</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="fornecedor_id" class="form-label">Fornecedor</label>
                            <select class="form-select" id="fornecedor_id" name="fornecedor_id">
                                <option value="">Selecione um fornecedor (opcional)</option>
                                <?php
                                $fornecedores = $fornecedor->listar();
                                foreach ($fornecedores as $f) {
                                    echo '<option value="'.$f['id'].'">'.$f['nome'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pendente">Pendente</option>
                                <option value="finalizada">Finalizada</option>
                            </select>
                            <small class="text-muted">Se "Finalizada", os produtos serão adicionados ao estoque imediatamente.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="valor_total" class="form-label">Valor Total</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="valor_total" name="valor_total" readonly value="0,00">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="salvar_compra" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Compra
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Produtos da Compra -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Produtos da Compra</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-adicionar-produto">
                            <i class="fas fa-plus"></i> Adicionar Produto
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tabela-produtos">
                                <thead>
                                    <tr>
                                        <th width="40%">Produto</th>
                                        <th width="15%">Quantidade</th>
                                        <th width="20%">Preço Unit.</th>
                                        <th width="20%">SubTotal</th>
                                        <th width="5%">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Linhas de produtos serão adicionadas dinamicamente -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="sem-produtos" class="text-center py-3">
                            <p class="text-muted">Nenhum produto adicionado. Clique em "Adicionar Produto" para começar.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal de Busca de Produtos -->
<div class="modal fade" id="modal-buscar-produtos" tabindex="-1" aria-labelledby="modal-buscar-produtos-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-buscar-produtos-label">Buscar Produtos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="busca-produtos" class="form-control" placeholder="Digite para buscar...">
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tabela-busca-produtos">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Produto</th>
                                <th>Preço Custo</th>
                                <th>Estoque</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $produtos = $produto->listar();
                            foreach ($produtos as $p) {
                                if ($p['ativo']) {
                                    echo '<tr>';
                                    echo '<td>'.$p['codigo'].'</td>';
                                    echo '<td>'.$p['nome'].'</td>';
                                    echo '<td>'.formatarDinheiro($p['preco_custo']).'</td>';
                                    echo '<td>'.$p['estoque_atual'].'</td>';
                                    echo '<td><button class="btn btn-sm btn-primary btn-selecionar-produto" 
                                                data-id="'.$p['id'].'" 
                                                data-nome="'.$p['nome'].'" 
                                                data-preco="'.$p['preco_custo'].'">
                                                <i class="fas fa-plus"></i>
                                            </button></td>';
                                    echo '</tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let contadorLinhas = 0;
        let produtos = [];
        
        // Datatable para busca de produtos
        const tabelaBuscaProdutos = new DataTable('#tabela-busca-produtos', {
            language: {
                url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            },
            pageLength: 10
        });
        
        // Busca de produtos na modal
        document.getElementById('busca-produtos').addEventListener('keyup', function() {
            const busca = this.value.toLowerCase();
            tabelaBuscaProdutos.search(busca).draw();
        });
        
        // Abrir modal para adicionar produto
        document.getElementById('btn-adicionar-produto').addEventListener('click', function() {
            const modalBuscarProdutos = new bootstrap.Modal(document.getElementById('modal-buscar-produtos'));
            modalBuscarProdutos.show();
        });
        
        // Selecionar produto na modal
        document.querySelectorAll('.btn-selecionar-produto').forEach(button => {
            button.addEventListener('click', function() {
                const produtoId = this.getAttribute('data-id');
                const produtoNome = this.getAttribute('data-nome');
                const produtoPreco = parseFloat(this.getAttribute('data-preco').replace(',', '.').replace('R$ ', ''));
                
                // Verificar se o produto já está na lista
                const produtoExistente = produtos.find(p => p.id === produtoId);
                
                if (produtoExistente) {
                    alert('Este produto já foi adicionado à compra!');
                } else {
                    adicionarLinhaProduto(produtoId, produtoNome, produtoPreco, 1);
                    
                    // Fechar a modal
                    bootstrap.Modal.getInstance(document.getElementById('modal-buscar-produtos')).hide();
                }
            });
        });
        
        // Função para adicionar linha de produto
        function adicionarLinhaProduto(id, nome, preco, quantidade) {
            const tbody = document.querySelector('#tabela-produtos tbody');
            const semProdutos = document.getElementById('sem-produtos');
            
            // Esconder mensagem de "sem produtos"
            semProdutos.style.display = 'none';
            
            const subtotal = preco * quantidade;
            
            // Criar nova linha
            const tr = document.createElement('tr');
            tr.setAttribute('data-id', id);
            tr.setAttribute('data-index', contadorLinhas);
            
            tr.innerHTML = `
                <td>
                    ${nome}
                    <input type="hidden" name="produto_id[]" value="${id}">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm input-quantidade" name="quantidade[]" value="${quantidade}" min="1">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control form-control-sm input-preco" name="preco_unitario[]" value="${preco.toFixed(2).replace('.', ',')}">
                    </div>
                </td>
                <td class="subtotal">R$ ${subtotal.toFixed(2).replace('.', ',')}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger btn-remover">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(tr);
            
            // Adicionar produto ao array
            produtos.push({
                id: id,
                nome: nome,
                preco: preco,
                quantidade: quantidade,
                index: contadorLinhas
            });
            
            // Incrementar contador de linhas
            contadorLinhas++;
            
            // Atualizar valor total
            atualizarValorTotal();
            
            // Adicionar eventos aos novos campos
            adicionarEventosLinha(tr);
        }
        
        // Função para adicionar eventos a uma linha
        function adicionarEventosLinha(linha) {
            // Evento de remover produto
            linha.querySelector('.btn-remover').addEventListener('click', function() {
                const index = parseInt(linha.getAttribute('data-index'));
                
                // Remover produto do array
                const produtoIndex = produtos.findIndex(p => p.index === index);
                if (produtoIndex !== -1) {
                    produtos.splice(produtoIndex, 1);
                }
                
                // Remover linha da tabela
                linha.remove();
                
                // Mostrar mensagem de "sem produtos" se não houver mais produtos
                if (produtos.length === 0) {
                    document.getElementById('sem-produtos').style.display = 'block';
                }
                
                // Atualizar valor total
                atualizarValorTotal();
            });
            
            // Evento de alterar quantidade
            linha.querySelector('.input-quantidade').addEventListener('change', function() {
                const index = parseInt(linha.getAttribute('data-index'));
                const quantidade = parseInt(this.value) || 1;
                
                if (quantidade < 1) {
                    this.value = 1;
                    return;
                }
                
                // Atualizar quantidade no array
                const produtoIndex = produtos.findIndex(p => p.index === index);
                if (produtoIndex !== -1) {
                    produtos[produtoIndex].quantidade = quantidade;
                }
                
                // Atualizar subtotal na linha
                atualizarSubtotal(linha);
                
                // Atualizar valor total
                atualizarValorTotal();
            });
            
            // Evento de alterar preço
            linha.querySelector('.input-preco').addEventListener('input', function() {
                const index = parseInt(linha.getAttribute('data-index'));
                const precoStr = this.value.replace(',', '.');
                const preco = parseFloat(precoStr) || 0;
                
                // Atualizar preço no array
                const produtoIndex = produtos.findIndex(p => p.index === index);
                if (produtoIndex !== -1) {
                    produtos[produtoIndex].preco = preco;
                }
                
                // Atualizar subtotal na linha
                atualizarSubtotal(linha);
                
                // Atualizar valor total
                atualizarValorTotal();
            });
        }
        
        // Função para atualizar subtotal de uma linha
        function atualizarSubtotal(linha) {
            const index = parseInt(linha.getAttribute('data-index'));
            const produtoIndex = produtos.findIndex(p => p.index === index);
            
            if (produtoIndex !== -1) {
                const produto = produtos[produtoIndex];
                const subtotal = produto.preco * produto.quantidade;
                
                linha.querySelector('.subtotal').textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
            }
        }
        
        // Função para atualizar valor total
        function atualizarValorTotal() {
            let total = 0;
            
            produtos.forEach(produto => {
                //total += produto.preco * produto.quantidade;
                total += produto.preco * produto.quantidade;
            });
            
            document.getElementById('valor_total').value = total.toFixed(2).replace('.', ',');
        }
        
        // Validar formulário antes de enviar
        document.getElementById('form-compra').addEventListener('submit', function(e) {
            if (produtos.length === 0) {
                e.preventDefault();
                alert('É necessário adicionar pelo menos um produto à compra!');
            }
        });
    });
</script>

<?php include 'footer.php'; ?>
