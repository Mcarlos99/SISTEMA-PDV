<?php
// Substitua esta parte na listagem de movimentações no arquivo caixa.php
// Isso geralmente está na seção que busca as movimentações do caixa atual

// Verifique se o caixa atual tem movimentações
if ($caixa_aberto) {
    // Modificar a consulta para buscar todas as movimentações do caixa, sem limitações
    // e garantir que todas as vendas sejam incluídas
    $stmt = $pdo->prepare("
        SELECT 
            m.*, 
            u.nome AS usuario_nome,
            DATE_FORMAT(m.data_hora, '%d/%m/%Y %H:%i') AS data_formatada
        FROM movimentacoes_caixa m
        LEFT JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.caixa_id = :caixa_id
        ORDER BY m.data_hora DESC
    ");
    
    $stmt->bindParam(':caixa_id', $caixa_aberto['id'], PDO::PARAM_INT);
    $stmt->execute();
    $movimentacoes = $stmt->fetchAll();
    
    // Calcular totais por tipo de movimentação
    $total_vendas = 0;
    $total_sangrias = 0;
    $total_suprimentos = 0;
    
    foreach ($movimentacoes as $mov) {
        if ($mov['tipo'] == 'venda') {
            $total_vendas += $mov['valor'];
        } else if ($mov['tipo'] == 'sangria') {
            $total_sangrias += $mov['valor'];
        } else if ($mov['tipo'] == 'suprimento') {
            $total_suprimentos += $mov['valor'];
        }
    }
    
    // Calcular saldo atual
    $saldo_atual = $caixa_aberto['valor_inicial'] + $total_vendas + $total_suprimentos - $total_sangrias;
}

// Adicione esta função para verificar se uma venda já está registrada como movimentação
function verificarVendaRegistrada($vendas_registradas, $venda_id) {
    foreach ($vendas_registradas as $vr) {
        if ($vr['documento_id'] == $venda_id && $vr['tipo'] == 'venda') {
            return true;
        }
    }
    return false;
}

// Se o caixa estiver aberto, verificar se há vendas não registradas nas movimentações
if ($caixa_aberto) {
    // Buscar todas as vendas do dia que pertencem ao usuário atual
    $hoje = date('Y-m-d');
    $usuario_id = $_SESSION['usuario_id'];
    
    $stmt = $pdo->prepare("
        SELECT 
            v.id, 
            v.valor_total, 
            v.forma_pagamento,
            DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada,
            c.nome AS cliente_nome
        FROM vendas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        WHERE v.usuario_id = :usuario_id 
        AND DATE(v.data_venda) = :data_hoje
        AND v.status = 'finalizada'
        ORDER BY v.data_venda DESC
    ");
    
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':data_hoje', $hoje);
    $stmt->execute();
    $vendas_hoje = $stmt->fetchAll();
    
    // Verificar se há vendas não registradas nas movimentações
    $vendas_nao_registradas = [];
    
    foreach ($vendas_hoje as $venda) {
        if (!verificarVendaRegistrada($movimentacoes, $venda['id'])) {
            $vendas_nao_registradas[] = $venda;
        }
    }
    
    // Se houver vendas não registradas, exiba um alerta e ofereça a opção de registrá-las
    if (!empty($vendas_nao_registradas)) {
        echo '<div class="alert alert-warning">';
        echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>Atenção! Há vendas não registradas no caixa atual.</h5>';
        echo '<p>Foram encontradas ' . count($vendas_nao_registradas) . ' vendas que não estão registradas nas movimentações do caixa atual.</p>';
        echo '<a href="registrar_vendas_caixa.php?caixa_id=' . $caixa_aberto['id'] . '" class="btn btn-primary">Registrar Vendas no Caixa</a>';
        echo '</div>';
    }
}