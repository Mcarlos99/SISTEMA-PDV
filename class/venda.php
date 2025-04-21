<?php
/**
 * Classe de Gerenciamento de Vendas
 * 
 * Responsável por operações relacionadas às vendas no EXTREME PDV
 */
class Venda
{
    private $pdo;
    
    /**
     * Construtor da classe
     * 
     * @param PDO $pdo Conexão com o banco de dados
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Listar todas as vendas com paginação e filtros
     * 
     * @param int $offset Início dos registros
     * @param int $limite Número de registros por página
     * @param array $filtros Filtros para a consulta
     * @return array Vendas e total de registros
     */
    public function listarPaginado($offset = 0, $limite = 25, $filtros = [])
    {
        // Construir a consulta SQL base
        $sql = "
            SELECT v.*, 
                   DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada,
                   c.nome AS cliente_nome, 
                   u.nome AS usuario_nome,
                   uc.nome AS usuario_cancelamento_nome
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN usuarios uc ON v.usuario_cancelamento_id = uc.id
            WHERE 1 = 1
        ";
        
        // Contagem total de registros
        $sqlCount = "SELECT COUNT(*) FROM vendas v WHERE 1 = 1";
        
        // Parâmetros para a consulta
        $params = [];
        
        // Aplicar filtros
        if (!empty($filtros)) {
            // Filtro por busca geral
            if (isset($filtros['busca']) && !empty($filtros['busca'])) {
                $busca = $filtros['busca'];
                $sql .= " AND (v.id LIKE ? OR c.nome LIKE ? OR v.valor_total LIKE ?)";
                $sqlCount .= " AND (v.id LIKE ? OR c.nome LIKE ? OR v.valor_total LIKE ?)";
                $params[] = "%$busca%";
                $params[] = "%$busca%";
                $params[] = "%$busca%";
            }
            
            // Filtro por cliente
            if (isset($filtros['cliente_id']) && !empty($filtros['cliente_id'])) {
                $sql .= " AND v.cliente_id = ?";
                $sqlCount .= " AND v.cliente_id = ?";
                $params[] = $filtros['cliente_id'];
            }
            
            // Filtro por status
            if (isset($filtros['status']) && !empty($filtros['status'])) {
                $sql .= " AND v.status = ?";
                $sqlCount .= " AND v.status = ?";
                $params[] = $filtros['status'];
            }
            
            // Filtro por data inicial
            if (isset($filtros['data_inicio']) && !empty($filtros['data_inicio'])) {
                $sql .= " AND DATE(v.data_venda) >= ?";
                $sqlCount .= " AND DATE(v.data_venda) >= ?";
                $params[] = $filtros['data_inicio'];
            }
            
            // Filtro por data final
            if (isset($filtros['data_fim']) && !empty($filtros['data_fim'])) {
                $sql .= " AND DATE(v.data_venda) <= ?";
                $sqlCount .= " AND DATE(v.data_venda) <= ?";
                $params[] = $filtros['data_fim'];
            }
            
            // Filtro por forma de pagamento
            if (isset($filtros['forma_pagamento']) && !empty($filtros['forma_pagamento'])) {
                $sql .= " AND v.forma_pagamento = ?";
                $sqlCount .= " AND v.forma_pagamento = ?";
                $params[] = $filtros['forma_pagamento'];
            }
        }
        
        // Ordenação e paginação
        $sql .= " ORDER BY v.data_venda DESC LIMIT ?, ?";
        
        // Adicionar parâmetros de paginação
        $paramsCount = $params;
        $params[] = $offset;
        $params[] = $limite;
        
        // Executar consulta
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar total de registros
        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($paramsCount);
        $total = $stmtCount->fetchColumn();
        
        return [
            'vendas' => $vendas,
            'total' => $total
        ];
    }
    
    /**
     * Listar todas as vendas (método legado para compatibilidade)
     * 
     * @return array Lista de vendas
     */
    public function listar()
    {
        $resultado = $this->listarPaginado(0, 1000);
        return $resultado['vendas'];
    }
    
    /**
     * Buscar venda pelo ID
     * 
     * @param int $id ID da venda
     * @return array|false Dados da venda ou false se não encontrar
     */
    public function buscarPorId($id)
    {
        $sql = "
            SELECT v.*, 
                   DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada,
                   c.nome AS cliente_nome, 
                   u.nome AS usuario_nome,
                   uc.nome AS usuario_cancelamento_nome
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN usuarios uc ON v.usuario_cancelamento_id = uc.id
            WHERE v.id = ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar itens da venda
     * 
     * @param int $venda_id ID da venda
     * @return array Lista de itens da venda
     */
    public function buscarItens($venda_id)
    {
        $sql = "
            SELECT vi.*, 
                   p.nome AS produto_nome, 
                   p.codigo AS produto_codigo,
                   (vi.quantidade * vi.preco_unitario) AS subtotal
            FROM venda_itens vi
            INNER JOIN produtos p ON vi.produto_id = p.id
            WHERE vi.venda_id = ?
            ORDER BY vi.id ASC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$venda_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar histórico de atividades da venda
     * 
     * @param int $venda_id ID da venda
     * @return array Lista de eventos do histórico
     */
    public function buscarHistorico($venda_id)
    {
        $sql = "
            SELECT h.*,
                   DATE_FORMAT(h.data_registro, '%d/%m/%Y %H:%i') AS data_formatada,
                   u.nome AS usuario_nome
            FROM venda_historico h
            LEFT JOIN usuarios u ON h.usuario_id = u.id
            WHERE h.venda_id = ?
            ORDER BY h.data_registro DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$venda_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Adicionar nova venda
     * 
     * @param array $dados Dados da venda
     * @return int|false ID da venda ou false em caso de erro
     */
    public function adicionar($dados)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Inserir venda
            $sql = "
                INSERT INTO vendas (
                    cliente_id, 
                    usuario_id, 
                    data_venda, 
                    valor_total, 
                    desconto, 
                    forma_pagamento, 
                    status, 
                    observacoes,
                    comanda_id
                ) VALUES (?, ?, NOW(), ?, ?, ?, 'finalizada', ?, ?)
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['cliente_id'] ?? null,
                $dados['usuario_id'],
                $dados['valor_total'],
                $dados['desconto'] ?? 0,
                $dados['forma_pagamento'],
                $dados['observacoes'] ?? null,
                $dados['comanda_id'] ?? null
            ]);
            
            $venda_id = $this->pdo->lastInsertId();
            
            // Inserir itens da venda
            foreach ($dados['itens'] as $item) {
                $sql = "
                    INSERT INTO venda_itens (
                        venda_id, 
                        produto_id, 
                        quantidade, 
                        preco_unitario,
                        observacoes
                    ) VALUES (?, ?, ?, ?, ?)
                ";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $venda_id,
                    $item['produto_id'],
                    $item['quantidade'],
                    $item['preco_unitario'],
                    $item['observacoes'] ?? null
                ]);
                
                // Atualizar estoque
                $sql = "UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$item['quantidade'], $item['produto_id']]);
            }
            
            // Registrar no caixa se houver um aberto
            $caixa = new Caixa($this->pdo);
            $caixa_aberto = $caixa->verificarCaixaAberto();
            
            if ($caixa_aberto) {
                $caixa->registrarMovimentacao(
                    $caixa_aberto['id'],
                    'venda',
                    $dados['valor_total'],
                    $dados['forma_pagamento'],
                    "Venda #$venda_id"
                );
            }
            
            // Registrar histórico
            $this->registrarHistorico($venda_id, 'criacao', 'Venda realizada com sucesso', $dados['usuario_id']);
            
            $this->pdo->commit();
            return $venda_id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Erro ao adicionar venda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancelar venda
     * 
     * @param int $id ID da venda
     * @param string $motivo Motivo do cancelamento
     * @return bool Sucesso ou falha
     */
    public function cancelar($id, $motivo = '')
    {
        try {
            $this->pdo->beginTransaction();
            
            // Verificar se a venda existe e se não está cancelada
            $venda = $this->buscarPorId($id);
            if (!$venda || $venda['status'] == 'cancelada') {
                throw new Exception("Venda não encontrada ou já cancelada");
            }
            
            // Buscar itens para devolução ao estoque
            $itens = $this->buscarItens($id);
            
            // Devolver produtos ao estoque
            foreach ($itens as $item) {
                $sql = "UPDATE produtos SET estoque_atual = estoque_atual + ? WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$item['quantidade'], $item['produto_id']]);
            }
            
            // Atualizar status da venda
            $sql = "
                UPDATE vendas 
                SET status = 'cancelada', 
                    observacoes_cancelamento = ?,
                    usuario_cancelamento_id = ?,
                    data_cancelamento = NOW()
                WHERE id = ?
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $motivo,
                $_SESSION['usuario_id'],
                $id
            ]);
            
            // Registrar no caixa se houver um aberto e a forma de pagamento for dinheiro
            if ($venda['forma_pagamento'] == 'dinheiro') {
                $caixa = new Caixa($this->pdo);
                $caixa_aberto = $caixa->verificarCaixaAberto();
                
                if ($caixa_aberto) {
                    $caixa->registrarMovimentacao(
                        $caixa_aberto['id'],
                        'estorno',
                        $venda['valor_total'],
                        'dinheiro',
                        "Estorno da venda #$id - Motivo: $motivo"
                    );
                }
            }
            
            // Registrar histórico
            $this->registrarHistorico($id, 'cancelamento', 'Venda cancelada. Motivo: ' . $motivo, $_SESSION['usuario_id']);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Erro ao cancelar venda: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Registrar evento no histórico da venda
     * 
     * @param int $venda_id ID da venda
     * @param string $tipo Tipo do evento
     * @param string $descricao Descrição do evento
     * @param int $usuario_id ID do usuário que executou a ação
     * @return int|false ID do registro ou false em caso de erro
     */
    private function registrarHistorico($venda_id, $tipo, $descricao, $usuario_id)
    {
        $sql = "
            INSERT INTO venda_historico (
                venda_id,
                tipo,
                descricao,
                usuario_id,
                data_registro
            ) VALUES (?, ?, ?, ?, NOW())
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            $venda_id,
            $tipo,
            $descricao,
            $usuario_id
        ]);
        
        return $result ? $this->pdo->lastInsertId() : false;
    }
    
    /**
     * Gerar relatório de vendas
     * 
     * @param string $data_inicio Data inicial
     * @param string $data_fim Data final
     * @param string $filtro_status Status das vendas para filtrar
     * @return array Dados do relatório
     */
    public function gerarRelatorio($data_inicio, $data_fim, $filtro_status = 'todos')
    {
        $sql = "
            SELECT v.*,
                   DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada,
                   c.nome AS cliente_nome,
                   u.nome AS usuario_nome
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            WHERE DATE(v.data_venda) BETWEEN ? AND ?
        ";
        
        $params = [$data_inicio, $data_fim];
        
        if ($filtro_status != 'todos') {
            $sql .= " AND v.status = ?";
            $params[] = $filtro_status;
        }
        
        $sql .= " ORDER BY v.data_venda DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Gerar relatório com estatísticas de vendas por período
     * 
     * @param string $data_inicio Data inicial
     * @param string $data_fim Data final
     * @return array Estatísticas do relatório
     */
    public function gerarEstatisticas($data_inicio, $data_fim)
    {
        // Total de vendas e valor
        $sql = "
            SELECT 
                COUNT(*) AS total_vendas,
                SUM(valor_total) AS valor_total,
                SUM(desconto) AS valor_descontos,
                COUNT(DISTINCT cliente_id) AS total_clientes
            FROM vendas
            WHERE DATE(data_venda) BETWEEN ? AND ?
            AND status = 'finalizada'
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $totais = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vendas por forma de pagamento
        $sql = "
            SELECT 
                forma_pagamento,
                COUNT(*) AS quantidade,
                SUM(valor_total) AS valor_total
            FROM vendas
            WHERE DATE(data_venda) BETWEEN ? AND ?
            AND status = 'finalizada'
            GROUP BY forma_pagamento
            ORDER BY valor_total DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $por_pagamento = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top 10 produtos mais vendidos
        $sql = "
            SELECT 
                p.id,
                p.nome,
                p.codigo,
                SUM(vi.quantidade) AS quantidade,
                SUM(vi.quantidade * vi.preco_unitario) AS valor_total
            FROM venda_itens vi
            INNER JOIN vendas v ON vi.venda_id = v.id
            INNER JOIN produtos p ON vi.produto_id = p.id
            WHERE DATE(v.data_venda) BETWEEN ? AND ?
            AND v.status = 'finalizada'
            GROUP BY p.id
            ORDER BY quantidade DESC
            LIMIT 10
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $top_produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Vendas por dia no período
        $sql = "
            SELECT 
                DATE(data_venda) AS data,
                COUNT(*) AS quantidade,
                SUM(valor_total) AS valor_total
            FROM vendas
            WHERE DATE(data_venda) BETWEEN ? AND ?
            AND status = 'finalizada'
            GROUP BY DATE(data_venda)
            ORDER BY data
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top 10 clientes
        $sql = "
            SELECT 
                c.id,
                c.nome,
                COUNT(v.id) AS quantidade_compras,
                SUM(v.valor_total) AS valor_total
            FROM vendas v
            INNER JOIN clientes c ON v.cliente_id = c.id
            WHERE DATE(v.data_venda) BETWEEN ? AND ?
            AND v.status = 'finalizada'
            GROUP BY c.id
            ORDER BY valor_total DESC
            LIMIT 10
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Retornar todos os dados
        return [
            'periodo' => [
                'inicio' => $data_inicio,
                'fim' => $data_fim,
            ],
            'totais' => $totais,
            'por_pagamento' => $por_pagamento,
            'top_produtos' => $top_produtos,
            'vendas_diarias' => $por_dia,
            'top_clientes' => $top_clientes
        ];
    }
    
    /**
     * Buscar detalhes resumidos de todas as vendas (para dashboard)
     * 
     * @param int $limite Limite de vendas a retornar
     * @return array Vendas recentes
     */
    public function buscarRecentes($limite = 5)
    {
        $sql = "
            SELECT v.id, v.data_venda, v.valor_total, v.status, v.forma_pagamento,
                   DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada,
                   c.nome AS cliente_nome,
                   (SELECT COUNT(*) FROM venda_itens WHERE venda_id = v.id) AS total_itens
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            ORDER BY v.data_venda DESC
            LIMIT ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limite]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcular estatísticas para dashboard
     * 
     * @return array Estatísticas
     */
    public function calcularEstatisticasDashboard()
    {
        // Vendas hoje
        $sql = "
            SELECT 
                COUNT(*) AS total_vendas,
                SUM(valor_total) AS valor_total
            FROM vendas
            WHERE DATE(data_venda) = CURDATE()
            AND status = 'finalizada'
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $hoje = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vendas na semana atual
        $sql = "
            SELECT 
                COUNT(*) AS total_vendas,
                SUM(valor_total) AS valor_total
            FROM vendas
            WHERE YEARWEEK(data_venda) = YEARWEEK(NOW())
            AND status = 'finalizada'
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $semana = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vendas no mês atual
        $sql = "
            SELECT 
                COUNT(*) AS total_vendas,
                SUM(valor_total) AS valor_total
            FROM vendas
            WHERE MONTH(data_venda) = MONTH(NOW()) 
            AND YEAR(data_venda) = YEAR(NOW())
            AND status = 'finalizada'
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $mes = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Total geral
        $sql = "
            SELECT 
                COUNT(*) AS total_vendas,
                SUM(valor_total) AS valor_total
            FROM vendas
            WHERE status = 'finalizada'
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'hoje' => $hoje,
            'semana' => $semana,
            'mes' => $mes,
            'total' => $total
        ];
    }
}