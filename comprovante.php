<?php
require_once 'config.php';
verificarLogin();

// Verificar se ID foi informado
$id = $_GET['id'] ?? 0;

if (!$id) {
    die('ID da venda não informado');
}

// Buscar dados da venda
$venda_dados = $venda->buscarPorId($id);

if (!$venda_dados) {
    die('Venda não encontrada');
}

// Buscar itens da venda
$itens = $venda->buscarItens($id);

// Buscar dados do cliente
$cliente_dados = null;
if ($venda_dados['cliente_id']) {
    $cliente_dados = $cliente->buscarPorId($venda_dados['cliente_id']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante de Venda #<?php echo $id; ?></title>
    <style>
        body {
            font-family: monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 80mm; /* Largura padrão de impressora térmica */
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 10px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        .info {
            margin-bottom: 10px;
        }
        .info p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            text-align: left;
            padding: 3px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .totals {
            text-align: right;
        }
        .totals p {
            margin: 2px 0;
        }
        .bold {
            font-weight: bold;
        }
        @media print {
            body {
                width: 100%;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">SISTEMA PDV</p>
        <p>COMPROVANTE DE VENDA</p>
    </div>
    
    <div class="info">
        <p><strong>Venda Nº:</strong> <?php echo $id; ?></p>
        <p><strong>Data:</strong> <?php echo $venda_dados['data_formatada']; ?></p>
        <p><strong>Vendedor:</strong> <?php echo $venda_dados['usuario_nome']; ?></p>
        <?php if ($cliente_dados): ?>
        <p><strong>Cliente:</strong> <?php echo $cliente_dados['nome']; ?></p>
        <?php if (!empty($cliente_dados['cpf_cnpj'])): ?>
        <p><strong>CPF/CNPJ:</strong> <?php echo $cliente_dados['cpf_cnpj']; ?></p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="divider"></div>
    
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qtd</th>
                <th>Valor</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $i => $item): ?>
            <tr>
                <td><?php echo $item['produto_nome']; ?></td>
                <td><?php echo $item['quantidade']; ?></td>
                <td><?php echo formatarDinheiro($item['preco_unitario']); ?></td>
                <td><?php echo formatarDinheiro($item['subtotal']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="divider"></div>
    
    <div class="totals">
        <p><strong>Subtotal:</strong> <?php echo formatarDinheiro($venda_dados['valor_total'] + $venda_dados['desconto']); ?></p>
        <?php if ($venda_dados['desconto'] > 0): ?>
        <p><strong>Desconto:</strong> <?php echo formatarDinheiro($venda_dados['desconto']); ?></p>
        <?php endif; ?>
        <p class="bold"><strong>TOTAL:</strong> <?php echo formatarDinheiro($venda_dados['valor_total']); ?></p>
        <p><strong>Forma de Pagamento:</strong> <?php echo ucfirst(str_replace('_', ' ', $venda_dados['forma_pagamento'])); ?></p>
    </div>
    
    <div class="divider"></div>
    
    <div class="footer">
        <p>Obrigado pela preferência!</p>
        <p>Data/Hora da Impressão: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
    
    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Fechar</button>
    </div>
    
    <script>
        // Imprimir automaticamente ao carregar
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
