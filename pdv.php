<?php
require_once 'config.php';
verificarLogin();

// Verificar se é necessário ter caixa aberto para vender e se existe um caixa aberto
if ($config_sistema->buscar()['caixa_obrigatorio'] == 1) {
    $caixa_aberto = $caixa->verificarCaixaAberto();
    if (!$caixa_aberto) {
        alerta('É necessário abrir o caixa antes de realizar vendas!', 'warning');
        header('Location: caixa.php');
        exit;
    }
}

// Template da página
$titulo_pagina = 'PDV - Ponto de Venda';
include 'header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Produtos</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" id="codigo-produto" class="form-control" placeholder="Código do produto ou nome" autofocus>
                                <button class="btn btn-outline-secondary" type="button" id="btn-buscar-produto">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modal-buscar-produtos">
                                <i class="fas fa-list"></i> Listar Produtos
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tabela-produtos">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Produto</th>
                                    <th>Preço</th>
                                    <th>Qtd</th>
                                    <th>Subtotal</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Resumo da Venda</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="cliente" class="form-label">Cliente</label>
                        <div class="input-group">
                            <select id="cliente" class="form-select">
                                <option value="">Cliente não identificado</option>
                                <?php
                                $clientes = $cliente->listar();
                                foreach ($clientes as $c) {
                                    echo '<option value="'.$c['id'].'">'.$c['nome'].'</option>';
                                }
                                ?>
                            </select>
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modal-cliente-rapido">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="forma-pagamento" class="form-label">Forma de Pagamento</label>
                        <select id="forma-pagamento" class="form-select">
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="pix">PIX</option>
                            <option value="boleto">Boleto</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="subtotal" class="form-label">Subtotal</label>
                            <input type="text" id="subtotal" class="form-control" readonly value="R$ 0,00">
                        </div>
                        <div class="col-md-6">
                            <label for="desconto" class="form-label">Desconto</label>
                            <input type="text" id="desconto" class="form-control" value="0">
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="total" class="form-label">Total</label>
                            <input type="text" id="total" class="form-control form-control-lg fw-bold" readonly value="R$ 0,00">
                        </div>
                        <div class="col-md-6">
                            <label for="recebido" class="form-label">Valor Recebido</label>
                            <input type="text" id="recebido" class="form-control" value="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="troco" class="form-label">Troco</label>
                        <input type="text" id="troco" class="form-control" readonly value="R$ 0,00">
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea id="observacoes" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="button" id="btn-finalizar-venda" class="btn btn-success btn-lg">
                            <i class="fas fa-check"></i> Finalizar Venda
                        </button>
                        <button type="button" id="btn-cancelar-venda" class="btn btn-danger">
                            <i class="fas fa-times"></i> Cancelar Venda
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                                <th>Preço</th>
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
                                    echo '<td>'.formatarDinheiro($p['preco_venda']).'</td>';
                                    echo '<td>'.$p['estoque_atual'].'</td>';
                                    echo '<td><button class="btn btn-sm btn-primary btn-selecionar-produto" data-id="'.$p['id'].'" data-codigo="'.$p['codigo'].'" data-nome="'.$p['nome'].'" data-preco="'.$p['preco_venda'].'"><i class="fas fa-plus"></i></button></td>';
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

<!-- Modal de Cliente Rápido -->
<div class="modal fade" id="modal-cliente-rapido" tabindex="-1" aria-labelledby="modal-cliente-rapido-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-cliente-rapido-label">Cadastro Rápido de Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-cliente-rapido">
                    <div class="mb-3">
                        <label for="cliente-nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="cliente-nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="cliente-cpf-cnpj" class="form-label">CPF/CNPJ</label>
                        <input type="text" class="form-control" id="cliente-cpf-cnpj">
                    </div>
                    <div class="mb-3">
                        <label for="cliente-telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="cliente-telefone">
                    </div>
                    <div class="mb-3">
                        <label for="cliente-email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="cliente-email">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-salvar-cliente">Salvar Cliente</button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- Script para o PDV - Colocado no final para garantir que o jQuery já foi carregado -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Garante que o código só será executado depois que o DOM estiver carregado
    // e após todos os scripts incluídos no footer.php serem carregados
    
    // Inicializar DataTable apenas quando o modal for aberto
    let dataTableInitialized = false;
    $('#modal-buscar-produtos').on('shown.bs.modal', function () {
        if (!dataTableInitialized) {
            $('#tabela-busca-produtos').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
                }
            });
            dataTableInitialized = true;
        }
    });
    
    // Código do PDV
    let produtos = [];
    let subtotal = 0;
    let desconto = 0;
    let total = 0;
    
    // Buscar produto pelo código
    $("#btn-buscar-produto").click(function() {
        buscarProduto();
    });
    
    $("#codigo-produto").keypress(function(e) {
        if (e.which == 13) {
            buscarProduto();
        }
    });
    
    function buscarProduto() {
    const codigo = $("#codigo-produto").val();
    if (!codigo) return;
    
    $.ajax({
        url: 'ajax_pdv.php',
        type: 'POST',
        data: {
            acao: 'buscar_produto',
            codigo: codigo
        },
        // Importante: não usar dataType: 'json' para processar manualmente
        complete: function(xhr) {
            let response = xhr.responseText;
            console.log('Resposta original:', response);
            
            try {
                // Remover qualquer BOM no início
                if (response.charCodeAt(0) === 0xFEFF || response.charCodeAt(0) === 65279) {
                    response = response.substring(1);
                }
                
                // Converter para objeto JSON
                const data = JSON.parse(response);
                console.log('JSON processado:', data);
                
                if (data.status === 'success' && data.produto) {
                    // Converter preço para número
                    data.produto.preco_venda = parseFloat(data.produto.preco_venda);
                    adicionarProduto(data.produto);
                } else {
                    alert(data.message || 'Erro ao processar produto');
                }
            } catch (e) {
                console.error('Erro ao processar resposta:', e);
                alert('Erro ao processar resposta do servidor');
            }
            
            // Limpar campo e focar nele novamente
            $("#codigo-produto").val('').focus();
        }
    });
}
    
    // Adicionar produto ao clicar em selecionar na modal
    $(document).on('click', '.btn-selecionar-produto', function() {
        const id = $(this).data('id');
        const codigo = $(this).data('codigo');
        const nome = $(this).data('nome');
        const preco = $(this).data('preco');
        
        const produto = {
            id: id,
            codigo: codigo,
            nome: nome,
            preco_venda: parseFloat(preco), // Converter explicitamente para número
            quantidade: 1
        };
        
        adicionarProduto(produto);
        $('#modal-buscar-produtos').modal('hide');
    });
    
    // Adicionar produto à tabela
    function adicionarProduto(produto) {
        // Garantir que preço seja um número
        produto.preco_venda = parseFloat(produto.preco_venda);
        
        // Verificar se o produto já está na lista
        let existe = false;
        for (let i = 0; i < produtos.length; i++) {
            if (produtos[i].id == produto.id) {
                produtos[i].quantidade++;
                atualizarLinhaProduto(i);
                existe = true;
                break;
            }
        }
        
        // Se não existir, adiciona
        if (!existe) {
            produto.quantidade = 1;
            const indice = produtos.length;
            produtos.push(produto);
            
            const linha = `
                <tr id="produto-${indice}">
                    <td>${produto.codigo}</td>
                    <td>${produto.nome}</td>
                    <td>R$ ${produto.preco_venda.toFixed(2).replace('.', ',')}</td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary btn-diminuir" data-indice="${indice}">-</button>
                            <input type="text" class="form-control text-center input-quantidade" data-indice="${indice}" value="1">
                            <button class="btn btn-outline-secondary btn-aumentar" data-indice="${indice}">+</button>
                        </div>
                    </td>
                    <td>R$ ${produto.preco_venda.toFixed(2).replace('.', ',')}</td>
                    <td>
                        <button class="btn btn-sm btn-danger btn-remover" data-indice="${indice}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $("#tabela-produtos tbody").append(linha);
        }
        
        atualizarTotais();
    }
    
    // Atualizar linha de produto quando a quantidade muda
    function atualizarLinhaProduto(indice) {
        const produto = produtos[indice];
        const subtotal = produto.quantidade * produto.preco_venda;
        
        $(`#produto-${indice} td:eq(3) input`).val(produto.quantidade);
        $(`#produto-${indice} td:eq(4)`).text(`R$ ${subtotal.toFixed(2).replace('.', ',')}`);
    }
    
    // Aumentar quantidade
    $(document).on('click', '.btn-aumentar', function() {
        const indice = $(this).data('indice');
        produtos[indice].quantidade++;
        atualizarLinhaProduto(indice);
        atualizarTotais();
    });
    
    // Diminuir quantidade
    $(document).on('click', '.btn-diminuir', function() {
        const indice = $(this).data('indice');
        if (produtos[indice].quantidade > 1) {
            produtos[indice].quantidade--;
            atualizarLinhaProduto(indice);
            atualizarTotais();
        }
    });
    
    // Alterar quantidade manualmente
    $(document).on('change', '.input-quantidade', function() {
        const indice = $(this).data('indice');
        let quantidade = parseInt($(this).val());
        
        if (isNaN(quantidade) || quantidade < 1) {
            quantidade = 1;
            $(this).val(quantidade);
        }
        
        produtos[indice].quantidade = quantidade;
        atualizarLinhaProduto(indice);
        atualizarTotais();
    });
    
    // Remover produto
    $(document).on('click', '.btn-remover', function() {
        const indice = $(this).data('indice');
        produtos.splice(indice, 1);
        
        // Reconstruir tabela
        $("#tabela-produtos tbody").empty();
        for (let i = 0; i < produtos.length; i++) {
            const produto = produtos[i];
            // Garantir que preço é número
            produto.preco_venda = parseFloat(produto.preco_venda);
            const subtotal = produto.quantidade * produto.preco_venda;
            
            const linha = `
                <tr id="produto-${i}">
                    <td>${produto.codigo}</td>
                    <td>${produto.nome}</td>
                    <td>R$ ${produto.preco_venda.toFixed(2).replace('.', ',')}</td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary btn-diminuir" data-indice="${i}">-</button>
                            <input type="text" class="form-control text-center input-quantidade" data-indice="${i}" value="${produto.quantidade}">
                            <button class="btn btn-outline-secondary btn-aumentar" data-indice="${i}">+</button>
                        </div>
                    </td>
                    <td>R$ ${subtotal.toFixed(2).replace('.', ',')}</td>
                    <td>
                        <button class="btn btn-sm btn-danger btn-remover" data-indice="${i}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $("#tabela-produtos tbody").append(linha);
        }
        
        atualizarTotais();
    });
    
    // Atualizar totais
    function atualizarTotais() {
        subtotal = 0;
        for (let i = 0; i < produtos.length; i++) {
            subtotal += produtos[i].quantidade * produtos[i].preco_venda;
        }
        
        desconto = parseFloat($("#desconto").val()) || 0;
        total = subtotal - desconto;
        
        $("#subtotal").val(`R$ ${subtotal.toFixed(2).replace('.', ',')}`);
        $("#total").val(`R$ ${total.toFixed(2).replace('.', ',')}`);
        
        calcularTroco();
    }
    
    // Atualizar desconto
    $("#desconto").change(function() {
        desconto = parseFloat($(this).val()) || 0;
        if (desconto > subtotal) {
            desconto = subtotal;
            $(this).val(desconto);
        }
        
        total = subtotal - desconto;
        $("#total").val(`R$ ${total.toFixed(2).replace('.', ',')}`);
        
        calcularTroco();
    });
    
    // Calcular troco
    $("#recebido").change(function() {
        calcularTroco();
    });
    
    function calcularTroco() {
        const recebido = parseFloat($("#recebido").val()) || 0;
        let troco = recebido - total;
        
        if (troco < 0) troco = 0;
        
        $("#troco").val(`R$ ${troco.toFixed(2).replace('.', ',')}`);
    }
    
    // Cadastro rápido de cliente
    $("#btn-salvar-cliente").click(function() {
        const nome = $("#cliente-nome").val().trim();
        const cpf_cnpj = $("#cliente-cpf-cnpj").val().trim();
        const telefone = $("#cliente-telefone").val().trim();
        const email = $("#cliente-email").val().trim();
        
        if (!nome) {
            alert("O nome do cliente é obrigatório!");
            return;
        }
        
        $.ajax({
            url: 'ajax_pdv.php',
            type: 'POST',
            data: {
                acao: 'salvar_cliente',
                nome: nome,
                cpf_cnpj: cpf_cnpj,
                telefone: telefone,
                email: email
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Adiciona cliente ao select e seleciona
                    $("#cliente").append(`<option value="${response.cliente.id}">${response.cliente.nome}</option>`);
                    $("#cliente").val(response.cliente.id);
                    $("#modal-cliente-rapido").modal('hide');
                    
                    // Limpa formulário
                    $("#cliente-nome").val('');
                    $("#cliente-cpf-cnpj").val('');
                    $("#cliente-telefone").val('');
                    $("#cliente-email").val('');
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Erro ao salvar cliente!');
            }
        });
    });
    
    // Finalizar venda
    $("#btn-finalizar-venda").click(function() {
        if (produtos.length === 0) {
            alert("Adicione pelo menos um produto à venda!");
            return;
        }
        
        const recebido = parseFloat($("#recebido").val()) || 0;
        if (recebido < total && $("#forma-pagamento").val() === 'dinheiro') {
            alert("O valor recebido é menor que o total da venda!");
            return;
        }
        
        const venda = {
            cliente_id: $("#cliente").val(),
            forma_pagamento: $("#forma-pagamento").val(),
            valor_total: total,
            desconto: desconto,
            observacoes: $("#observacoes").val(),
            itens: []
        };
        
        for (let i = 0; i < produtos.length; i++) {
            venda.itens.push({
                produto_id: produtos[i].id,
                quantidade: produtos[i].quantidade,
                preco_unitario: produtos[i].preco_venda
            });
        }
        
        $.ajax({
            url: 'ajax_pdv.php',
            type: 'POST',
            data: {
                acao: 'finalizar_venda',
                venda: JSON.stringify(venda)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert("Venda finalizada com sucesso!");
                    
                    // Limpar venda
                    produtos = [];
                    $("#tabela-produtos tbody").empty();
                    $("#cliente").val('');
                    $("#forma-pagamento").val('dinheiro');
                    $("#desconto").val('0');
                    $("#recebido").val('0');
                    $("#observacoes").val('');
                    atualizarTotais();
                    
                    // Imprimir comprovante
                    if (confirm("Deseja imprimir o comprovante?")) {
                        window.open(`comprovante.php?id=${response.venda_id}`, '_blank');
                    }
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Erro ao finalizar venda!');
            }
        });
    });
    
    // Cancelar venda
    $("#btn-cancelar-venda").click(function() {
        if (produtos.length === 0) return;
        
        if (confirm("Tem certeza que deseja cancelar esta venda?")) {
            produtos = [];
            $("#tabela-produtos tbody").empty();
            $("#cliente").val('');
            $("#forma-pagamento").val('dinheiro');
            $("#desconto").val('0');
            $("#recebido").val('0');
            $("#observacoes").val('');
            atualizarTotais();
        }
    });
    
    // Busca de produtos na modal (sem DataTables)
    $("#busca-produtos").keyup(function() {
        const busca = $(this).val().toLowerCase();
        $("#tabela-busca-produtos tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(busca) > -1);
        });
    });
    
    // Foco no campo de código ao abrir a página
    $("#codigo-produto").focus();
});
</script>
