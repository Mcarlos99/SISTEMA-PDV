<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar se o caixa precisa ser aberto
$caixa = new Caixa($pdo);
if ($caixa->verificarCaixaNecessario()) {
    alerta('É necessário abrir o caixa antes de realizar vendas.', 'warning');
    header('Location: caixa.php?acao=abrir');
    exit;
}

// Template da página
$titulo_pagina = 'PDV - Ponto de Venda';
include 'header.php';
?>
<style>
/* Estilos para avisos de produtos não encontrados */
.empty-results {
    padding: 25px;
    text-align: center;
}

.empty-results i {
    display: block;
    margin-bottom: 15px;
    color: #ccc;
}

.empty-results h5 {
    margin-bottom: 10px;
    color: #6c757d;
}

.empty-results p {
    color: #888;
    max-width: 80%;
    margin: 0 auto;
}

/* Efeito de carregamento para campos de busca */
.is-loading {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="20" height="20"><circle cx="50" cy="50" r="40" stroke="%236c757d" stroke-width="8" fill="none" stroke-dasharray="60 20" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="1s" repeatCount="indefinite"/></circle></svg>');
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 20px;
}

/* Efeito para campos inválidos e válidos */
.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
}

.is-valid {
    border-color: #198754 !important;
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important;
}

/* Estilo para toast fixo na tela */
#toastContainer {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}
 </style>   
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">
            <i class="fas fa-cash-register me-2 text-primary"></i>
            Ponto de Venda (PDV)
        </h2>
        <button type="button" class="btn btn-outline-secondary" id="btnLimpar">
            <i class="fas fa-trash-alt me-1"></i>
            Limpar
        </button>
    </div>
    
    <div class="row">
        <!-- Produtos e Cliente -->
        <div class="col-lg-8 mb-4 mb-lg-0">
            <!-- Busca de produtos -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-search me-2"></i>
                        Buscar Produtos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-barcode"></i>
                                </span>
                                <input type="text" id="codigoProduto" class="form-control" placeholder="Código/Barcode" autofocus>
                                <button class="btn btn-primary" type="button" id="btnBuscarCodigo">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-tag"></i>
                                </span>
                                <input type="text" id="nomeProduto" class="form-control" placeholder="Nome do produto">
                                <button class="btn btn-primary" type="button" id="btnBuscarNome">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cliente -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>
                        Cliente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-users"></i>
                                </span>
                                <select id="clienteId" class="form-select">
                                    <option value="">Selecione um cliente (opcional)</option>
                                    <?php
                                    $clientes = (new Cliente($pdo))->listar();
                                    foreach ($clientes as $c) {
                                        echo '<option value="'.$c['id'].'">'.esc($c['nome']).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <a href="clientes.php?acao=novo" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user-plus me-1"></i>
                                <span class="d-none d-md-inline">Novo Cliente</span>
                                <span class="d-inline d-md-none">Novo</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Itens da venda -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Itens da Venda
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Produto</th>
                                    <th>Qtd</th>
                                    <th>Preço</th>
                                    <th>Subtotal</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="itensVenda">
                                <tr class="no-items">
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">Nenhum item adicionado à venda</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resumo da venda e pagamento -->
        <div class="col-lg-4">
            <div class="card sticky-lg-top" style="top: 80px; z-index: 100;">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        Resumo da Venda
                    </h5>
                </div>
                <div class="card-body">
                    <div class="total-section mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Subtotal:</span>
                            <span id="subtotal" class="fw-bold">R$ 0,00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label for="desconto" class="text-muted mb-0">Desconto:</label>
                            <div class="input-group" style="max-width: 150px">
                                <span class="input-group-text">R$</span>
                                <input type="number" id="desconto" class="form-control" value="0" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center total-value">
                            <span class="h5 mb-0">Total:</span>
                            <span id="total" class="h3 mb-0 text-success">R$ 0,00</span>
                        </div>
                    </div>
                    
                    <div class="payment-section mb-4">
                        <label class="form-label">Forma de Pagamento:</label>
                        <div class="payment-options d-flex flex-wrap gap-2 mb-3">
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="formaPagamento" id="pagamentoDinheiro" value="dinheiro" checked>
                                <label class="form-check-label px-2 py-2 rounded border d-flex align-items-center" for="pagamentoDinheiro">
                                    <i class="fas fa-money-bill-wave text-success me-2"></i>
                                    <span>Dinheiro</span>
                                </label>
                            </div>
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="formaPagamento" id="pagamentoCartaoCredito" value="cartao_credito">
                                <label class="form-check-label px-2 py-2 rounded border d-flex align-items-center" for="pagamentoCartaoCredito">
                                    <i class="fas fa-credit-card text-primary me-2"></i>
                                    <span>Crédito</span>
                                </label>
                            </div>
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="formaPagamento" id="pagamentoCartaoDebito" value="cartao_debito">
                                <label class="form-check-label px-2 py-2 rounded border d-flex align-items-center" for="pagamentoCartaoDebito">
                                    <i class="fas fa-credit-card text-info me-2"></i>
                                    <span>Débito</span>
                                </label>
                            </div>
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="formaPagamento" id="pagamentoPix" value="pix">
                                <label class="form-check-label px-2 py-2 rounded border d-flex align-items-center" for="pagamentoPix">
                                    <i class="fas fa-qrcode text-warning me-2"></i>
                                    <span>PIX</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações:</label>
                        <textarea id="observacoes" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="button" id="btnFinalizar" class="btn btn-success btn-lg" disabled>
                            <i class="fas fa-check-circle me-2"></i>
                            Finalizar Venda
                        </button>
                        <button type="button" id="btnCancelar" class="btn btn-outline-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Quantidade -->
<div class="modal fade" id="modalQuantidade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Adicionar Produto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="produto-info mb-3">
                    <h5 id="modalProdutoNome">Nome do Produto</h5>
                    <div class="d-flex justify-content-between">
                        <span>Preço: <span id="modalProdutoPreco" class="text-primary">R$ 0,00</span></span>
                        <span>Estoque: <span id="modalProdutoEstoque" class="text-success">0</span></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="quantidade" class="form-label">Quantidade:</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" id="diminuirQtd">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="quantidade" class="form-control text-center" value="1" min="1">
                        <button type="button" class="btn btn-outline-secondary" id="aumentarQtd">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnAdicionarProduto">
                    <i class="fas fa-cart-plus me-1"></i>
                    Adicionar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Busca de Produtos -->
<div class="modal fade" id="modalBuscaProdutos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Buscar Produtos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Produto</th>
                                <th>Preço</th>
                                <th>Estoque</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="resultadoBusca">
                            <!-- Resultados da busca serão inseridos aqui via JavaScript -->
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
    // Variáveis globais
    let produtos = [];
    let itensVenda = [];
    let produtoSelecionado = null;
    
    // Quando a página carregar
    $(document).ready(function() {
        // Eventos de busca de produtos
        $('#btnBuscarCodigo').click(buscarProdutoPorCodigo);
        $('#codigoProduto').keypress(function(e) {
            if (e.which === 13) buscarProdutoPorCodigo();
        });
        
        $('#btnBuscarNome').click(buscarProdutosPorNome);
        $('#nomeProduto').keypress(function(e) {
            if (e.which === 13) buscarProdutosPorNome();
        });
        
        // Eventos do modal de quantidade
        $('#diminuirQtd').click(function() {
            let qtd = parseInt($('#quantidade').val());
            if (qtd > 1) $('#quantidade').val(qtd - 1);
        });
        
        $('#aumentarQtd').click(function() {
            let qtd = parseInt($('#quantidade').val());
            let estoque = parseInt($('#modalProdutoEstoque').text());
            if (qtd < estoque) $('#quantidade').val(qtd + 1);
        });
        
        $('#btnAdicionarProduto').click(adicionarProdutoVenda);
        
        // Eventos do resumo da venda
        $('#desconto').on('input', calcularTotal);
        
        // Botões de ação final
        $('#btnLimpar').click(limparVenda);
        $('#btnFinalizar').click(finalizarVenda);
        $('#btnCancelar').click(function() {
            if (confirm('Deseja realmente cancelar esta venda?')) {
                window.location.href = 'index.php';
            }
        });
        
        // Em telas menores, adicionar aviso quando itens são adicionados
        // pois a tabela pode ficar fora da área visível
        if (window.innerWidth < 992) {
            $('body').append('<div id="toastContainer" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"></div>');
        }
    });
    
 // Função para buscar produto por código (com aviso melhorado)
 function buscarProdutoPorCodigo() {
    const codigo = $('#codigoProduto').val().trim();
    if (!codigo) return;
    
    // Mostrar indicador de carregamento
    $('#codigoProduto').addClass('is-loading');
    
    $.ajax({
        url: 'ajax_pdv.php',
        type: 'POST',
        data: { 
            acao: 'buscar_produto',
            codigo: codigo 
        },
        dataType: 'json',
        success: function(data) {
            // Remover indicador de carregamento
            $('#codigoProduto').removeClass('is-loading');
            
            if (data.status === 'error') {
                // Mostrar mensagem de produto não encontrado
                mostrarToast('Produto não encontrado', 'Nenhum produto encontrado com o código: ' + codigo, 'warning');
                
                // Destacar o campo com animação "shake" para chamar atenção
                $('#codigoProduto').addClass('is-invalid').effect('shake', { times: 2, distance: 5 }, 300, function() {
                    // Remover a classe após a animação
                    setTimeout(function() {
                        $('#codigoProduto').removeClass('is-invalid');
                    }, 2000);
                });
                
                return;
            }
            
            produtoSelecionado = data.produto;
            exibirModalQuantidade(produtoSelecionado);
            
            // Limpar o campo após sucesso (opcional)
            $('#codigoProduto').val('');
        },
        error: function(xhr, status, error) {
            // Remover indicador de carregamento
            $('#codigoProduto').removeClass('is-loading');
            
            console.error("Erro AJAX:", xhr.responseText);
            console.error("Status:", status);
            console.error("Erro:", error);
            mostrarToast('Erro', 'Erro ao buscar o produto. Tente novamente.', 'danger');
        }
    });
 }

 // Função para buscar produtos por nome (com aviso melhorado)
 function buscarProdutosPorNome() {
    const nome = $('#nomeProduto').val().trim();
    if (!nome) return;
    
    // Mostrar indicador de carregamento
    $('#nomeProduto').addClass('is-loading');
    
    $.ajax({
        url: 'ajax_pdv.php',
        type: 'POST',
        data: { 
            acao: 'buscar_produtos_por_nome',
            nome: nome 
        },
        dataType: 'json',
        success: function(data) {
            // Remover indicador de carregamento
            $('#nomeProduto').removeClass('is-loading');
            
            if (data.status === 'error') {
                // Mostrar mensagem de nenhum produto encontrado mais amigável
                mostrarToast('Nenhum resultado', 'Nenhum produto encontrado com o nome: ' + nome, 'warning');
                
                // Exibir resultado vazio com mensagem amigável
                let html = `
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div class="empty-results">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhum produto encontrado</h5>
                            <p class="text-muted">Tente buscar com outro nome ou verifique se o produto está cadastrado.</p>
                        </div>
                    </td>
                </tr>
                `;
                $('#resultadoBusca').html(html);
                $('#modalBuscaProdutos').modal('show');
                return;
            }
            
            produtos = data.produtos;
            exibirResultadosBusca(produtos);
            
            // Destacar visualmente o sucesso (opcional)
            $('#nomeProduto').addClass('is-valid');
            setTimeout(function() {
                $('#nomeProduto').removeClass('is-valid');
            }, 2000);
        },
        error: function(xhr, status, error) {
            // Remover indicador de carregamento
            $('#nomeProduto').removeClass('is-loading');
            
            console.error("Erro AJAX:", xhr.responseText);
            console.error("Status:", status);
            console.error("Erro:", error);
            mostrarToast('Erro', 'Erro ao buscar produtos. Tente novamente.', 'danger');
        }
    });
 }

 // Função auxiliar para exibir toast se ainda não existir
 function mostrarToast(titulo, mensagem, tipo) {
    // Verificar se a função já existe no contexto global
    if (typeof window.mostrarToast === 'function') {
        window.mostrarToast(titulo, mensagem, tipo);
        return;
    }
    
    // Se não existir container de toast, criar um
    if ($('#toastContainer').length === 0) {
        $('body').append('<div id="toastContainer" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"></div>');
    }
    
    // Criar o toast
    const toastId = 'toast-' + Math.random().toString(36).substr(2, 9);
    const toast = `
    <div id="${toastId}" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-${tipo} text-white">
            <strong class="me-auto">${titulo}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${mensagem}
        </div>
    </div>
    `;
    
    // Adicionar ao container
    $('#toastContainer').append(toast);
    
    // Remover após 3 segundos
    setTimeout(function() {
        $(`#${toastId}`).remove();
    }, 3000);
    }
    
    // Exibir modal com resultados da busca
    function exibirResultadosBusca(produtos) {
        let html = '';
        
        if (produtos.length === 0) {
            html = '<tr><td colspan="5" class="text-center">Nenhum produto encontrado</td></tr>';
        } else {
            produtos.forEach(function(p) {
                html += `
                <tr>
                    <td><span class="badge bg-secondary">${p.codigo}</span></td>
                    <td>${p.nome}</td>
                    <td>R$ ${parseFloat(p.preco_venda).toFixed(2).replace('.', ',')}</td>
                    <td>${p.estoque_atual}</td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm" onclick="selecionarProduto(${p.id})">
                            <i class="fas fa-plus-circle"></i>
                        </button>
                    </td>
                </tr>
                `;
            });
        }
        
        $('#resultadoBusca').html(html);
        $('#modalBuscaProdutos').modal('show');
    }
    
    // Selecionar produto da lista de resultados
    function selecionarProduto(id) {
        produtoSelecionado = produtos.find(p => p.id == id);
        $('#modalBuscaProdutos').modal('hide');
        exibirModalQuantidade(produtoSelecionado);
    }
    
    // Exibir modal para informar quantidade
    function exibirModalQuantidade(produto) {
        $('#modalProdutoNome').text(produto.nome);
        $('#modalProdutoPreco').text(`R$ ${parseFloat(produto.preco_venda).toFixed(2).replace('.', ',')}`);
        $('#modalProdutoEstoque').text(produto.estoque_atual);
        $('#quantidade').val(1);
        $('#modalQuantidade').modal('show');
        
        // Garantir que o valor digitado não exceda o estoque
        $('#quantidade').attr('max', produto.estoque_atual);
    }
    
    // Adicionar produto à venda
    function adicionarProdutoVenda() {
        if (!produtoSelecionado) return;
        
        const quantidade = parseInt($('#quantidade').val());
        if (quantidade <= 0 || quantidade > produtoSelecionado.estoque_atual) {
            mostrarToast('Atenção', 'Quantidade inválida ou maior que o estoque disponível!', 'warning');
            return;
        }
        
        // Verificar se o produto já está na lista
        const index = itensVenda.findIndex(item => item.id == produtoSelecionado.id);
        
        if (index !== -1) {
            // Atualizar quantidade
            itensVenda[index].quantidade += quantidade;
            itensVenda[index].subtotal = itensVenda[index].quantidade * itensVenda[index].preco_unitario;
        } else {
            // Adicionar novo item
            itensVenda.push({
                id: produtoSelecionado.id,
                codigo: produtoSelecionado.codigo,
                nome: produtoSelecionado.nome,
                quantidade: quantidade,
                preco_unitario: parseFloat(produtoSelecionado.preco_venda),
                subtotal: quantidade * parseFloat(produtoSelecionado.preco_venda)
            });
        }
        
        atualizarTabelaItens();
        calcularTotal();
        $('#modalQuantidade').modal('hide');
        
        // Mostrar toast de confirmação em telas menores
        if (window.innerWidth < 992) {
            mostrarToast('Sucesso', `${quantidade}x ${produtoSelecionado.nome} adicionado!`, 'success');
        }
        
        // Limpar campo de busca
        $('#codigoProduto').val('').focus();
        $('#nomeProduto').val('');
    }
    
    // Atualizar tabela de itens
    function atualizarTabelaItens() {
        let html = '';
        
        if (itensVenda.length === 0) {
            html = `
            <tr class="no-items">
                <td colspan="6" class="text-center py-4">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <p class="mb-0">Nenhum item adicionado à venda</p>
                </td>
            </tr>
            `;
            $('#btnFinalizar').prop('disabled', true);
        } else {
            itensVenda.forEach(function(item, index) {
                html += `
                <tr>
                    <td><span class="badge bg-secondary">${item.codigo}</span></td>
                    <td>${item.nome}</td>
                    <td>${item.quantidade}</td>
                    <td>R$ ${item.preco_unitario.toFixed(2).replace('.', ',')}</td>
                    <td>R$ ${item.subtotal.toFixed(2).replace('.', ',')}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removerItem(${index})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                `;
            });
            $('#btnFinalizar').prop('disabled', false);
        }
        
        $('#itensVenda').html(html);
        
        // Em telas pequenas, role até o fim da tabela para mostrar o novo item
        if (itensVenda.length > 0 && window.innerWidth < 992) {
            const tableContainer = $('#itensVenda').closest('.table-responsive');
            tableContainer.scrollTop(tableContainer[0].scrollHeight);
        }
    }
    
    // Remover item da venda
    function removerItem(index) {
        const item = itensVenda[index];
        itensVenda.splice(index, 1);
        atualizarTabelaItens();
        calcularTotal();
        
        // Mostrar toast de confirmação em telas menores
        if (window.innerWidth < 992) {
            mostrarToast('Removido', `${item.nome} removido da venda!`, 'danger');
        }
    }
    
    // Calcular total da venda
    function calcularTotal() {
        let subtotal = 0;
        itensVenda.forEach(item => {
            subtotal += item.subtotal;
        });
        
        const desconto = parseFloat($('#desconto').val()) || 0;
        const total = subtotal - desconto;
        
        $('#subtotal').text(`R$ ${subtotal.toFixed(2).replace('.', ',')}`);
        $('#total').text(`R$ ${total.toFixed(2).replace('.', ',')}`);
    }
    
    // Limpar venda atual
    function limparVenda() {
        if (itensVenda.length === 0) return;
        
        if (confirm('Deseja realmente limpar todos os itens da venda?')) {
            itensVenda = [];
            $('#clienteId').val('');
            $('#desconto').val(0);
            $('#observacoes').val('');
            atualizarTabelaItens();
            calcularTotal();
            
            mostrarToast('Limpo', 'Todos os itens foram removidos!', 'warning');
        }
    }
    
// Função para finalizar venda (corrigida)
function finalizarVenda() {
    if (itensVenda.length === 0) {
        mostrarToast('Atenção', 'Adicione pelo menos um produto à venda!', 'warning');
        return;
    }
    
    // Mostrar indicador de carregamento
    $('#btnFinalizar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...');
    
    // Preparar dados da venda
    const venda = {
        cliente_id: $('#clienteId').val() || null,
        valor_total: parseFloat($('#total').text().replace('R$ ', '').replace(',', '.')),
        desconto: parseFloat($('#desconto').val()) || 0,
        forma_pagamento: $('input[name="formaPagamento"]:checked').val(),
        status: 'finalizada',
        observacoes: $('#observacoes').val(),
        itens: itensVenda.map(item => ({
            produto_id: item.id,
            quantidade: item.quantidade,
            preco_unitario: item.preco_unitario
        }))
    };
    
    // Adicionar log no console para debug
    console.log('Enviando dados da venda:', venda);
    
    // Enviar venda para o servidor
    $.ajax({
        url: 'ajax_pdv.php',
        type: 'POST',
        data: { 
            acao: 'finalizar_venda',
            venda: JSON.stringify(venda)
        },
        dataType: 'json',
        success: function(response) {
            console.log('Resposta do servidor:', response);
            
            if (response.status === 'error') {
                mostrarToast('Erro', response.message, 'danger');
                $('#btnFinalizar').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Finalizar Venda');
                return;
            }
            
            mostrarToast('Sucesso', 'Venda finalizada com sucesso!', 'success');
            
            // Redirecionar para a página de impressão ou nova venda
            if (response.imprimir) {
                window.open(`imprimir_venda.php?id=${response.venda_id}`, '_blank');
            }
            
            // Limpar venda atual
            itensVenda = [];
            $('#clienteId').val('');
            $('#desconto').val(0);
            $('#observacoes').val('');
            atualizarTabelaItens();
            calcularTotal();
            
            // Restaurar botão
            $('#btnFinalizar').prop('disabled', true).html('<i class="fas fa-check-circle me-2"></i>Finalizar Venda');
            
            // Focar no campo de código
            $('#codigoProduto').focus();
        },
        error: function(xhr, status, error) {
            console.error('Erro AJAX ao finalizar venda:', xhr.responseText);
            console.error('Status:', status);
            console.error('Erro:', error);
            
            mostrarToast('Erro', 'Erro ao finalizar a venda. Verifique o console para mais detalhes.', 'danger');
            $('#btnFinalizar').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Finalizar Venda');
        }
    });
}

    
    // Função para mostrar toast
    function mostrarToast(titulo, mensagem, tipo) {
        const toast = `
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-${tipo} text-white">
                <strong class="me-auto">${titulo}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${mensagem}
            </div>
        </div>
        `;
        
        $('#toastContainer').append(toast);
        
        // Remover toast após 3 segundos
        setTimeout(function() {
            $('#toastContainer .toast:first-child').remove();
        }, 3000);
    }
    
    // Ajustes responsivos
    $(window).resize(function() {
        // Ajusta a altura máxima da tabela em dispositivos móveis
        if (window.innerWidth < 992) {
            $('.table-responsive').css('max-height', (window.innerHeight * 0.4) + 'px');
        } else {
            $('.table-responsive').css('max-height', 'none');
        }
    }).resize(); // Executar no carregamento
</script>

<?php include 'footer.php'; ?>