<?php
require_once 'config.php';
verificarLogin();

// Verificar se o usuário tem permissão de super administrador
if ($_SESSION['usuario_nivel'] != 'admin') {
    alerta('Você não tem permissão para acessar esta página!', 'danger');
    header('Location: index.php');
    exit;
}

// Criar a tabela de planos se ainda não existir
function criarTabelaPlanos($pdo) {
    try {
        // Verificar se a tabela já existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'planos'");
        if ($stmt->rowCount() > 0) {
            return true; // Tabela já existe
        }

        // Criar a tabela
        $pdo->exec("
            CREATE TABLE planos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(50) NOT NULL,
                limite_usuarios INT NOT NULL DEFAULT 5,
                limite_produtos INT NOT NULL DEFAULT 100,
                limite_clientes INT NOT NULL DEFAULT 100,
                limite_vendas_diarias INT NOT NULL DEFAULT 50,
                data_expiracao DATE NULL,
                modulo_comanda BOOLEAN NOT NULL DEFAULT FALSE,
                modulo_estoque BOOLEAN NOT NULL DEFAULT TRUE,
                modulo_financeiro BOOLEAN NOT NULL DEFAULT FALSE,
                modulo_relatorios_avancados BOOLEAN NOT NULL DEFAULT FALSE,
                modulo_multiplos_caixas BOOLEAN NOT NULL DEFAULT FALSE,
                ativo BOOLEAN NOT NULL DEFAULT TRUE,
                descricao TEXT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Inserir planos padrão
        $pdo->exec("
            INSERT INTO planos (nome, limite_usuarios, limite_produtos, limite_clientes, limite_vendas_diarias, descricao, ativo) VALUES 
            ('Básico', 1, 10, 10, 10, 'Plano básico com recursos limitados para pequenas empresas', 1),
            ('Intermediário', 5, 30, 30, 30, 'Plano intermediário com mais recursos para médias empresas', 1),
            ('Avançado', 0, 0, 0, 0, 'Plano avançado com recursos ilimitados para grandes empresas', 1)
        ");

        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela planos: " . $e->getMessage());
        return false;
    }
}

// Criar a tabela de empresa_planos se ainda não existir
function criarTabelaEmpresaPlanos($pdo) {
    try {
        // Verificar se a tabela já existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'empresa_planos'");
        if ($stmt->rowCount() > 0) {
            return true; // Tabela já existe
        }

        // Criar a tabela
        $pdo->exec("
            CREATE TABLE empresa_planos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT NOT NULL,
                plano_id INT NOT NULL,
                limite_usuarios_personalizado INT NULL,
                limite_produtos_personalizado INT NULL,
                limite_clientes_personalizado INT NULL,
                limite_vendas_diarias_personalizado INT NULL,
                data_expiracao DATE NULL,
                modulo_comanda BOOLEAN NULL,
                modulo_estoque BOOLEAN NULL,
                modulo_financeiro BOOLEAN NULL,
                modulo_relatorios_avancados BOOLEAN NULL,
                modulo_multiplos_caixas BOOLEAN NULL,
                ativo BOOLEAN NOT NULL DEFAULT TRUE,
                observacoes TEXT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (empresa_id) REFERENCES configuracoes_empresa(id),
                FOREIGN KEY (plano_id) REFERENCES planos(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela empresa_planos: " . $e->getMessage());
        return false;
    }
}

// Criar tabelas necessárias
criarTabelaPlanos($pdo);
criarTabelaEmpresaPlanos($pdo);

// Classe para gerenciar planos
class Plano {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Listar todos os planos
    public function listar() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM planos ORDER BY nome");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao listar planos: " . $e->getMessage());
            return [];
        }
    }

    // Buscar plano por ID
    public function buscarPorId($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM planos WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erro ao buscar plano por ID: " . $e->getMessage());
            return false;
        }
    }

    // Adicionar plano
    public function adicionar($dados) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO planos 
                (nome, limite_usuarios, limite_produtos, limite_clientes, limite_vendas_diarias, 
                data_expiracao, modulo_comanda, modulo_estoque, modulo_financeiro, 
                modulo_relatorios_avancados, modulo_multiplos_caixas, descricao, ativo) 
                VALUES 
                (:nome, :limite_usuarios, :limite_produtos, :limite_clientes, :limite_vendas_diarias, 
                :data_expiracao, :modulo_comanda, :modulo_estoque, :modulo_financeiro, 
                :modulo_relatorios_avancados, :modulo_multiplos_caixas, :descricao, :ativo)
            ");
            
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':limite_usuarios', $dados['limite_usuarios'], PDO::PARAM_INT);
            $stmt->bindParam(':limite_produtos', $dados['limite_produtos'], PDO::PARAM_INT);
            $stmt->bindParam(':limite_clientes', $dados['limite_clientes'], PDO::PARAM_INT);
            $stmt->bindParam(':limite_vendas_diarias', $dados['limite_vendas_diarias'], PDO::PARAM_INT);
            
            if (!empty($dados['data_expiracao'])) {
                $stmt->bindParam(':data_expiracao', $dados['data_expiracao']);
            } else {
                $stmt->bindValue(':data_expiracao', null, PDO::PARAM_NULL);
            }
            
            $stmt->bindParam(':modulo_comanda', $dados['modulo_comanda'], PDO::PARAM_BOOL);
            $stmt->bindParam(':modulo_estoque', $dados['modulo_estoque'], PDO::PARAM_BOOL);
            $stmt->bindParam(':modulo_financeiro', $dados['modulo_financeiro'], PDO::PARAM_BOOL);
            $stmt->bindParam(':modulo_relatorios_avancados', $dados['modulo_relatorios_avancados'], PDO::PARAM_BOOL);
            $stmt->bindParam(':modulo_multiplos_caixas', $dados['modulo_multiplos_caixas'], PDO::PARAM_BOOL);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':ativo', $dados['ativo'], PDO::PARAM_BOOL);
            
            $result = $stmt->execute();
            
            // Registrar no log do sistema
            if ($result && isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Planos', 
                    "Novo plano {$dados['nome']} adicionado"
                );
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao adicionar plano: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar plano
    public function atualizar($id, $dados) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE planos SET 
                nome = :nome, 
                limite_usuarios = :limite_usuarios, 
                limite_produtos = :limite_produtos, 
                limite_clientes = :limite_clientes, 
                limite_vendas_diarias = :limite_vendas_diarias, 
                data_expiracao = :data_expiracao, 
                modulo_comanda = :modulo_comanda, 
                modulo_estoque = :modulo_estoque, 
                modulo_financeiro = :modulo_financeiro, 
                modulo_relatorios_avancados = :modulo_relatorios_avancados, 
                modulo_multiplos_caixas = :modulo_multiplos_caixas, 
                descricao = :descricao, 
                ativo = :ativo 
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':limite_usuarios', $dados['limite_usuarios'], PDO::PARAM_INT);
            $stmt->bindParam(':limite_produtos', $dados['limite_produtos'], PDO::PARAM_INT);
            $stmt->bindParam(':limite_clientes', $dados['limite_clientes'], PDO::PARAM_INT);
            $stmt->bindParam(':limite_vendas_diarias', $dados['limite_vendas_diarias'], PDO::PARAM_INT);
            
            if (!empty($dados['data_expiracao'])) {
                $stmt->bindParam(':data_expiracao', $dados['data_expiracao']);
            } else {
                $stmt->bindValue(':data_expiracao', null, PDO::PARAM_NULL);
            }
            
            $stmt->bindParam(':modulo_comanda', $dados['modulo_comanda'], PDO::PARAM_BOOL);
            $stmt->bindParam(':modulo_estoque', $dados['modulo_estoque'], PDO::PARAM_BOOL);
            $stmt->bindParam(':modulo_financeiro', $dados['modulo_financeiro'], PDO::PARAM_BOOL);
            $stmt->bindParam(':modulo_relatorios_avancados', $dados['modulo_relatorios_avancados'], PDO::PARAM_BOOL);
            $stmt->bindParam(':modulo_multiplos_caixas', $dados['modulo_multiplos_caixas'], PDO::PARAM_BOOL);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':ativo', $dados['ativo'], PDO::PARAM_BOOL);
            
            $result = $stmt->execute();
            
            // Registrar no log do sistema
            if ($result && isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Planos', 
                    "Plano ID #{$id} ({$dados['nome']}) atualizado"
                );
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao atualizar plano: " . $e->getMessage());
            return false;
        }
    }

    // Excluir plano
    public function excluir($id) {
        try {
            // Verificar se o plano está sendo usado
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM empresa_planos WHERE plano_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                return false; // Não pode excluir se estiver sendo usado
            }
            
            // Buscar o nome do plano para o log
            $plano_excluir = $this->buscarPorId($id);
            
            $stmt = $this->pdo->prepare("DELETE FROM planos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            // Registrar no log do sistema
            if ($result && isset($GLOBALS['log']) && $plano_excluir) {
                $GLOBALS['log']->registrar(
                    'Planos', 
                    "Plano ID #{$id} ({$plano_excluir['nome']}) excluído"
                );
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao excluir plano: " . $e->getMessage());
            return false;
        }
    }
}

// Classe para gerenciar associação de planos a empresas
class EmpresaPlano {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Listar associações empresa-plano
    public function listar() {
        try {
            $stmt = $this->pdo->query("
                SELECT ep.*, p.nome AS plano_nome, e.nome AS empresa_nome,
                DATE_FORMAT(ep.data_expiracao, '%d/%m/%Y') AS data_expiracao_formatada
                FROM empresa_planos ep
                LEFT JOIN planos p ON ep.plano_id = p.id
                LEFT JOIN configuracoes_empresa e ON ep.empresa_id = e.id
                ORDER BY e.nome
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao listar empresas-planos: " . $e->getMessage());
            return [];
        }
    }

    // Buscar associação por ID
    public function buscarPorId($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ep.*, p.nome AS plano_nome, e.nome AS empresa_nome
                FROM empresa_planos ep
                LEFT JOIN planos p ON ep.plano_id = p.id
                LEFT JOIN configuracoes_empresa e ON ep.empresa_id = e.id
                WHERE ep.id = :id LIMIT 1
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erro ao buscar empresa-plano por ID: " . $e->getMessage());
            return false;
        }
    }

    // Buscar associação por empresa ID
    public function buscarPorEmpresaId($empresa_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ep.*, p.nome AS plano_nome, e.nome AS empresa_nome
                FROM empresa_planos ep
                LEFT JOIN planos p ON ep.plano_id = p.id
                LEFT JOIN configuracoes_empresa e ON ep.empresa_id = e.id
                WHERE ep.empresa_id = :empresa_id LIMIT 1
            ");
            $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erro ao buscar empresa-plano por empresa ID: " . $e->getMessage());
            return false;
        }
    }

    // Adicionar associação
    public function adicionar($dados) {
        try {
            // Verificar se já existe uma associação para esta empresa
            $stmt = $this->pdo->prepare("SELECT id FROM empresa_planos WHERE empresa_id = :empresa_id");
            $stmt->bindParam(':empresa_id', $dados['empresa_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                return false; // Já existe uma associação
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO empresa_planos 
                (empresa_id, plano_id, limite_usuarios_personalizado, limite_produtos_personalizado, 
                limite_clientes_personalizado, limite_vendas_diarias_personalizado, data_expiracao,
                modulo_comanda, modulo_estoque, modulo_financeiro, modulo_relatorios_avancados,
                modulo_multiplos_caixas, observacoes, ativo) 
                VALUES 
                (:empresa_id, :plano_id, :limite_usuarios_personalizado, :limite_produtos_personalizado, 
                :limite_clientes_personalizado, :limite_vendas_diarias_personalizado, :data_expiracao,
                :modulo_comanda, :modulo_estoque, :modulo_financeiro, :modulo_relatorios_avancados,
                :modulo_multiplos_caixas, :observacoes, :ativo)
            ");
            
            // Vincular parâmetros básicos
            $stmt->bindParam(':empresa_id', $dados['empresa_id'], PDO::PARAM_INT);
            $stmt->bindParam(':plano_id', $dados['plano_id'], PDO::PARAM_INT);
            $stmt->bindParam(':observacoes', $dados['observacoes']);
            $stmt->bindParam(':ativo', $dados['ativo'], PDO::PARAM_BOOL);
            
            // Vincular parâmetros personalizados (podem ser NULL se usar o padrão do plano)
            $this->vincularParametroOuNull($stmt, ':limite_usuarios_personalizado', $dados['limite_usuarios_personalizado']);
            $this->vincularParametroOuNull($stmt, ':limite_produtos_personalizado', $dados['limite_produtos_personalizado']);
            $this->vincularParametroOuNull($stmt, ':limite_clientes_personalizado', $dados['limite_clientes_personalizado']);
            $this->vincularParametroOuNull($stmt, ':limite_vendas_diarias_personalizado', $dados['limite_vendas_diarias_personalizado']);
            $this->vincularParametroOuNull($stmt, ':data_expiracao', $dados['data_expiracao']);
            $this->vincularParametroOuNull($stmt, ':modulo_comanda', $dados['modulo_comanda']);
            $this->vincularParametroOuNull($stmt, ':modulo_estoque', $dados['modulo_estoque']);
            $this->vincularParametroOuNull($stmt, ':modulo_financeiro', $dados['modulo_financeiro']);
            $this->vincularParametroOuNull($stmt, ':modulo_relatorios_avancados', $dados['modulo_relatorios_avancados']);
            $this->vincularParametroOuNull($stmt, ':modulo_multiplos_caixas', $dados['modulo_multiplos_caixas']);
            
            $result = $stmt->execute();
            
            // Registrar no log do sistema
            if ($result && isset($GLOBALS['log'])) {
                // Buscar nome da empresa
                $stmt_empresa = $this->pdo->prepare("SELECT nome FROM configuracoes_empresa WHERE id = :id");
                $stmt_empresa->bindParam(':id', $dados['empresa_id'], PDO::PARAM_INT);
                $stmt_empresa->execute();
                $empresa = $stmt_empresa->fetch();
                
                // Buscar nome do plano
                $stmt_plano = $this->pdo->prepare("SELECT nome FROM planos WHERE id = :id");
                $stmt_plano->bindParam(':id', $dados['plano_id'], PDO::PARAM_INT);
                $stmt_plano->execute();
                $plano = $stmt_plano->fetch();
                
                $GLOBALS['log']->registrar(
                    'Planos', 
                    "Associação de plano '{$plano['nome']}' à empresa '{$empresa['nome']}' adicionada"
                );
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao adicionar empresa-plano: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar associação
    public function atualizar($id, $dados) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE empresa_planos SET 
                empresa_id = :empresa_id,
                plano_id = :plano_id,
                limite_usuarios_personalizado = :limite_usuarios_personalizado,
                limite_produtos_personalizado = :limite_produtos_personalizado,
                limite_clientes_personalizado = :limite_clientes_personalizado,
                limite_vendas_diarias_personalizado = :limite_vendas_diarias_personalizado,
                data_expiracao = :data_expiracao,
                modulo_comanda = :modulo_comanda,
                modulo_estoque = :modulo_estoque,
                modulo_financeiro = :modulo_financeiro,
                modulo_relatorios_avancados = :modulo_relatorios_avancados,
                modulo_multiplos_caixas = :modulo_multiplos_caixas,
                observacoes = :observacoes,
                ativo = :ativo
                WHERE id = :id
            ");
            
            // Vincular parâmetros básicos
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':empresa_id', $dados['empresa_id'], PDO::PARAM_INT);
            $stmt->bindParam(':plano_id', $dados['plano_id'], PDO::PARAM_INT);
            $stmt->bindParam(':observacoes', $dados['observacoes']);
            $stmt->bindParam(':ativo', $dados['ativo'], PDO::PARAM_BOOL);
            
            // Vincular parâmetros personalizados (podem ser NULL se usar o padrão do plano)
            $this->vincularParametroOuNull($stmt, ':limite_usuarios_personalizado', $dados['limite_usuarios_personalizado']);
            $this->vincularParametroOuNull($stmt, ':limite_produtos_personalizado', $dados['limite_produtos_personalizado']);
            $this->vincularParametroOuNull($stmt, ':limite_clientes_personalizado', $dados['limite_clientes_personalizado']);
            $this->vincularParametroOuNull($stmt, ':limite_vendas_diarias_personalizado', $dados['limite_vendas_diarias_personalizado']);
            $this->vincularParametroOuNull($stmt, ':data_expiracao', $dados['data_expiracao']);
            $this->vincularParametroOuNull($stmt, ':modulo_comanda', $dados['modulo_comanda']);
            $this->vincularParametroOuNull($stmt, ':modulo_estoque', $dados['modulo_estoque']);
            $this->vincularParametroOuNull($stmt, ':modulo_financeiro', $dados['modulo_financeiro']);
            $this->vincularParametroOuNull($stmt, ':modulo_relatorios_avancados', $dados['modulo_relatorios_avancados']);
            $this->vincularParametroOuNull($stmt, ':modulo_multiplos_caixas', $dados['modulo_multiplos_caixas']);
            
            $result = $stmt->execute();
            
            // Registrar no log do sistema
            if ($result && isset($GLOBALS['log'])) {
                // Buscar detalhes atualizados
                $detalhe = $this->buscarPorId($id);
                
                $GLOBALS['log']->registrar(
                    'Planos', 
                    "Associação ID #{$id} - Plano '{$detalhe['plano_nome']}' da empresa '{$detalhe['empresa_nome']}' atualizada"
                );
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao atualizar empresa-plano: " . $e->getMessage());
            return false;
        }
    }

    // Excluir associação
    public function excluir($id) {
        try {
            // Buscar detalhes para o log
            $detalhe = $this->buscarPorId($id);
            
            $stmt = $this->pdo->prepare("DELETE FROM empresa_planos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            // Registrar no log do sistema
            if ($result && isset($GLOBALS['log']) && $detalhe) {
                $GLOBALS['log']->registrar(
                    'Planos', 
                    "Associação ID #{$id} - Plano '{$detalhe['plano_nome']}' da empresa '{$detalhe['empresa_nome']}' excluída"
                );
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao excluir empresa-plano: " . $e->getMessage());
            return false;
        }
    }

    // Método auxiliar para vincular parâmetro ou NULL
    private function vincularParametroOuNull($stmt, $param, $valor) {
        if ($valor === '' || $valor === null) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            if (is_bool($valor) || $valor === '0' || $valor === '1') {
                $stmt->bindValue($param, $valor, PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue($param, $valor);
            }
        }
    }
}

// Instanciar classes
$plano = new Plano($pdo);
$empresaPlano = new EmpresaPlano($pdo);

// Processar ações de formulários
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ações de Planos
    if (isset($_POST['adicionar_plano'])) {
        $dados = [
            'nome' => $_POST['nome'],
            'limite_usuarios' => $_POST['limite_usuarios'],
            'limite_produtos' => $_POST['limite_produtos'],
            'limite_clientes' => $_POST['limite_clientes'],
            'limite_vendas_diarias' => $_POST['limite_vendas_diarias'],
            'data_expiracao' => $_POST['data_expiracao'],
            'modulo_comanda' => isset($_POST['modulo_comanda']) ? 1 : 0,
            'modulo_estoque' => isset($_POST['modulo_estoque']) ? 1 : 0,
            'modulo_financeiro' => isset($_POST['modulo_financeiro']) ? 1 : 0,
            'modulo_relatorios_avancados' => isset($_POST['modulo_relatorios_avancados']) ? 1 : 0,
            'modulo_multiplos_caixas' => isset($_POST['modulo_multiplos_caixas']) ? 1 : 0,
            'descricao' => $_POST['descricao'],
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];
        
        if ($plano->adicionar($dados)) {
            alerta('Plano adicionado com sucesso!', 'success');
        } else {
            alerta('Erro ao adicionar plano!', 'danger');
        }
    }
    
    if (isset($_POST['atualizar_plano'])) {
        $id = $_POST['id'];
        $dados = [
            'nome' => $_POST['nome'],
            'limite_usuarios' => $_POST['limite_usuarios'],
            'limite_produtos' => $_POST['limite_produtos'],
            'limite_clientes' => $_POST['limite_clientes'],
            'limite_vendas_diarias' => $_POST['limite_vendas_diarias'],
            'data_expiracao' => $_POST['data_expiracao'],
            'modulo_comanda' => isset($_POST['modulo_comanda']) ? 1 : 0,
            'modulo_estoque' => isset($_POST['modulo_estoque']) ? 1 : 0,
            'modulo_financeiro' => isset($_POST['modulo_financeiro']) ? 1 : 0,
            'modulo_relatorios_avancados' => isset($_POST['modulo_relatorios_avancados']) ? 1 : 0,
            'modulo_multiplos_caixas' => isset($_POST['modulo_multiplos_caixas']) ? 1 : 0,
            'descricao' => $_POST['descricao'],
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];
        
        if ($plano->atualizar($id, $dados)) {
            alerta('Plano atualizado com sucesso!', 'success');
        } else {
            alerta('Erro ao atualizar plano!', 'danger');
        }
    }
    
    if (isset($_POST['excluir_plano'])) {
        $id = $_POST['id'];
        if ($plano->excluir($id)) {
            alerta('Plano excluído com sucesso!', 'success');
        } else {
            alerta('Erro ao excluir plano! Verifique se ele não está sendo usado.', 'danger');
        }
    }
    
    // Ações de Empresa-Plano
    if (isset($_POST['adicionar_empresa_plano'])) {
        $dados = [
            'empresa_id' => $_POST['empresa_id'],
            'plano_id' => $_POST['plano_id'],
            'limite_usuarios_personalizado' => $_POST['limite_usuarios_personalizado'],
            'limite_produtos_personalizado' => $_POST['limite_produtos_personalizado'],
            'limite_clientes_personalizado' => $_POST['limite_clientes_personalizado'],
            'limite_vendas_diarias_personalizado' => $_POST['limite_vendas_diarias_personalizado'],
            'data_expiracao' => $_POST['data_expiracao'],
            'modulo_comanda' => isset($_POST['modulo_comanda']) ? 1 : null,
            'modulo_estoque' => isset($_POST['modulo_estoque']) ? 1 : null,
            'modulo_financeiro' => isset($_POST['modulo_financeiro']) ? 1 : null,
            'modulo_relatorios_avancados' => isset($_POST['modulo_relatorios_avancados']) ? 1 : null,
            'modulo_multiplos_caixas' => isset($_POST['modulo_multiplos_caixas']) ? 1 : null,
            'observacoes' => $_POST['observacoes'],
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];
        
        if ($empresaPlano->adicionar($dados)) {
            alerta('Associação de plano à empresa adicionada com sucesso!', 'success');
        } else {
            alerta('Erro ao adicionar associação de plano à empresa! Verifique se a empresa já possui um plano.', 'danger');
        }
    }
    
    if (isset($_POST['atualizar_empresa_plano'])) {
        $id = $_POST['id'];
        $dados = [
            'empresa_id' => $_POST['empresa_id'],
            'plano_id' => $_POST['plano_id'],
            'limite_usuarios_personalizado' => $_POST['limite_usuarios_personalizado'],
            'limite_produtos_personalizado' => $_POST['limite_produtos_personalizado'],
            'limite_clientes_personalizado' => $_POST['limite_clientes_personalizado'],
            'limite_vendas_diarias_personalizado' => $_POST['limite_vendas_diarias_personalizado'],
            'data_expiracao' => $_POST['data_expiracao'],
            'modulo_comanda' => isset($_POST['modulo_comanda']) ? 1 : null,
            'modulo_estoque' => isset($_POST['modulo_estoque']) ? 1 : null,
            'modulo_financeiro' => isset($_POST['modulo_financeiro']) ? 1 : null,
            'modulo_relatorios_avancados' => isset($_POST['modulo_relatorios_avancados']) ? 1 : null,
            'modulo_multiplos_caixas' => isset($_POST['modulo_multiplos_caixas']) ? 1 : null,
            'observacoes' => $_POST['observacoes'],
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];
        
        if ($empresaPlano->atualizar($id, $dados)) {
            alerta('Associação de plano à empresa atualizada com sucesso!', 'success');
        } else {
            alerta('Erro ao atualizar associação de plano à empresa!', 'danger');
        }
    }
    
    if (isset($_POST['excluir_empresa_plano'])) {
        $id = $_POST['id'];
        if ($empresaPlano->excluir($id)) {
            alerta('Associação de plano à empresa excluída com sucesso!', 'success');
        } else {
            alerta('Erro ao excluir associação de plano à empresa!', 'danger');
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: planos_super.php');
    exit;
}

// Buscar dados para edição
$plano_edicao = null;
if (isset($_GET['editar_plano'])) {
    $id = $_GET['editar_plano'];
    $plano_edicao = $plano->buscarPorId($id);
}

$empresa_plano_edicao = null;
if (isset($_GET['editar_empresa_plano'])) {
    $id = $_GET['editar_empresa_plano'];
    $empresa_plano_edicao = $empresaPlano->buscarPorId($id);
}

// Buscar todas as empresas para o dropdown
function listarEmpresas($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, nome, razao_social FROM configuracoes_empresa ORDER BY nome");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erro ao listar empresas: " . $e->getMessage());
        return [];
    }
}
$empresas = listarEmpresas($pdo);

// Template da página
$titulo_pagina = 'Gerenciamento de Planos - EXTREME PDV';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Cabeçalho da Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-project-diagram me-2 text-primary"></i> Gerenciamento de Planos</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Painel</a></li>
                    <li class="breadcrumb-item active">Planos</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex">
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modal-plano">
                <i class="fas fa-plus-circle me-1"></i> Novo Plano
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-empresa-plano">
                <i class="fas fa-link me-1"></i> Associar Plano a Empresa
            </button>
        </div>
    </div>
    
    <!-- Tabs para alternar entre Planos e Associações -->
    <ul class="nav nav-tabs mb-4" id="planosTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="planos-tab" data-bs-toggle="tab" href="#planos" role="tab" aria-selected="true">
                <i class="fas fa-layer-group me-1"></i> Planos Disponíveis
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="empresas-tab" data-bs-toggle="tab" href="#empresas" role="tab" aria-selected="false">
                <i class="fas fa-building me-1"></i> Planos de Empresas
            </a>
        </li>
    </ul>
    
    <div class="tab-content" id="planosTabContent">
        <!-- Tab Planos -->
        <div class="tab-pane fade show active" id="planos" role="tabpanel">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i> Lista de Planos
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="buscarPlano" placeholder="Buscar plano...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover datatable mb-0" id="tabelaPlanos">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Usuários</th>
                                    <th>Produtos</th>
                                    <th>Clientes</th>
                                    <th>Vendas/dia</th>
                                    <th>Módulos</th>
                                    <th>Status</th>
                                    <th width="120">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $planos = $plano->listar();
                                foreach ($planos as $p) {
                                    $status_class = $p['ativo'] ? 'bg-success' : 'bg-secondary';
                                    $status_texto = $p['ativo'] ? 'Ativo' : 'Inativo';
                                    
                                    // Formatar textos para ilimitado
                                    $usuarios = ($p['limite_usuarios'] == 0) ? 'Ilimitado' : $p['limite_usuarios'];
                                    $produtos = ($p['limite_produtos'] == 0) ? 'Ilimitado' : $p['limite_produtos'];
                                    $clientes = ($p['limite_clientes'] == 0) ? 'Ilimitado' : $p['limite_clientes'];
                                    $vendas = ($p['limite_vendas_diarias'] == 0) ? 'Ilimitado' : $p['limite_vendas_diarias'];
                                    
                                    // Listar módulos ativos
                                    $modulos = [];
                                    if ($p['modulo_comanda']) $modulos[] = 'Comanda';
                                    if ($p['modulo_estoque']) $modulos[] = 'Estoque';
                                    if ($p['modulo_financeiro']) $modulos[] = 'Financeiro';
                                    if ($p['modulo_relatorios_avancados']) $modulos[] = 'Relatórios Avançados';
                                    if ($p['modulo_multiplos_caixas']) $modulos[] = 'Múltiplos Caixas';
                                    $modulos_texto = implode(', ', $modulos);
                                ?>
                                <tr>
                                    <td><?php echo esc($p['nome']); ?></td>
                                    <td><?php echo $usuarios; ?></td>
                                    <td><?php echo $produtos; ?></td>
                                    <td><?php echo $clientes; ?></td>
                                    <td><?php echo $vendas; ?></td>
                                    <td><?php echo $modulos_texto; ?></td>
                                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_texto; ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?editar_plano=<?php echo $p['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-excluir-plano" 
                                                data-id="<?php echo $p['id']; ?>" 
                                                data-nome="<?php echo esc($p['nome']); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <span class="text-muted">Total de <?php echo count($planos); ?> planos cadastrados</span>
                </div>
            </div>
        </div>
        
        <!-- Tab Empresas com Planos -->
        <div class="tab-pane fade" id="empresas" role="tabpanel">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-building me-2"></i> Empresas com Planos
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="buscarEmpresaPlano" placeholder="Buscar empresa...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover datatable mb-0" id="tabelaEmpresas">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Plano</th>
                                    <th>Usuários</th>
                                    <th>Produtos</th>
                                    <th>Clientes</th>
                                    <th>Expiração</th>
                                    <th>Status</th>
                                    <th width="120">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $empresas_planos = $empresaPlano->listar();
                                foreach ($empresas_planos as $ep) {
                                    $status_class = $ep['ativo'] ? 'bg-success' : 'bg-secondary';
                                    $status_texto = $ep['ativo'] ? 'Ativo' : 'Inativo';
                                    
                                    // Formatar valores personalizados ou padrão
                                    $usuarios = ($ep['limite_usuarios_personalizado'] !== null) ? 
                                        (($ep['limite_usuarios_personalizado'] == 0) ? 'Ilimitado' : $ep['limite_usuarios_personalizado']) : 
                                        'Padrão do plano';
                                        
                                    $produtos = ($ep['limite_produtos_personalizado'] !== null) ? 
                                        (($ep['limite_produtos_personalizado'] == 0) ? 'Ilimitado' : $ep['limite_produtos_personalizado']) : 
                                        'Padrão do plano';
                                        
                                    $clientes = ($ep['limite_clientes_personalizado'] !== null) ? 
                                        (($ep['limite_clientes_personalizado'] == 0) ? 'Ilimitado' : $ep['limite_clientes_personalizado']) : 
                                        'Padrão do plano';
                                        
                                    $data_expiracao = $ep['data_expiracao_formatada'] ?? 'Sem data';
                                ?>
                                <tr>
                                    <td><?php echo esc($ep['empresa_nome']); ?></td>
                                    <td><?php echo esc($ep['plano_nome']); ?></td>
                                    <td><?php echo $usuarios; ?></td>
                                    <td><?php echo $produtos; ?></td>
                                    <td><?php echo $clientes; ?></td>
                                    <td><?php echo $data_expiracao; ?></td>
                                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_texto; ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?editar_empresa_plano=<?php echo $ep['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-excluir-empresa-plano" 
                                                data-id="<?php echo $ep['id']; ?>" 
                                                data-empresa="<?php echo esc($ep['empresa_nome']); ?>">
                                                <i class="fas fa-unlink"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <span class="text-muted">Total de <?php echo count($empresas_planos); ?> empresas com planos associados</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Plano -->
<div class="modal fade" id="modal-plano" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas <?php echo $plano_edicao ? 'fa-edit' : 'fa-plus-circle'; ?> me-2"></i>
                    <?php echo $plano_edicao ? 'Editar Plano' : 'Novo Plano'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-plano" method="post" action="">
                    <?php if ($plano_edicao): ?>
                        <input type="hidden" name="id" value="<?php echo $plano_edicao['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label fw-bold">Nome do Plano</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo $plano_edicao ? esc($plano_edicao['nome']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="data_expiracao" class="form-label fw-bold">Data de Expiração (opcional)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control" id="data_expiracao" name="data_expiracao" value="<?php echo $plano_edicao ? esc($plano_edicao['data_expiracao']) : ''; ?>">
                            </div>
                            <small class="text-muted">Deixe em branco para não definir uma data de expiração.</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="limite_usuarios" class="form-label fw-bold">Limite de Usuários</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-users"></i></span>
                                <input type="number" class="form-control" id="limite_usuarios" name="limite_usuarios" required min="0" value="<?php echo $plano_edicao ? esc($plano_edicao['limite_usuarios']) : '5'; ?>">
                            </div>
                            <small class="text-muted">0 = Ilimitado</small>
                        </div>
                        <div class="col-md-3">
                            <label for="limite_produtos" class="form-label fw-bold">Limite de Produtos</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                                <input type="number" class="form-control" id="limite_produtos" name="limite_produtos" required min="0" value="<?php echo $plano_edicao ? esc($plano_edicao['limite_produtos']) : '100'; ?>">
                            </div>
                            <small class="text-muted">0 = Ilimitado</small>
                        </div>
                        <div class="col-md-3">
                            <label for="limite_clientes" class="form-label fw-bold">Limite de Clientes</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-friends"></i></span>
                                <input type="number" class="form-control" id="limite_clientes" name="limite_clientes" required min="0" value="<?php echo $plano_edicao ? esc($plano_edicao['limite_clientes']) : '100'; ?>">
                            </div>
                            <small class="text-muted">0 = Ilimitado</small>
                        </div>
                        <div class="col-md-3">
                            <label for="limite_vendas_diarias" class="form-label fw-bold">Limite de Vendas/dia</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-chart-line"></i></span>
                                <input type="number" class="form-control" id="limite_vendas_diarias" name="limite_vendas_diarias" required min="0" value="<?php echo $plano_edicao ? esc($plano_edicao['limite_vendas_diarias']) : '50'; ?>">
                            </div>
                            <small class="text-muted">0 = Ilimitado</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Módulos Incluídos</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="modulo_comanda" name="modulo_comanda" <?php echo ($plano_edicao && $plano_edicao['modulo_comanda']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="modulo_comanda">Módulo de Comandas</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="modulo_estoque" name="modulo_estoque" <?php echo ($plano_edicao && $plano_edicao['modulo_estoque']) ? 'checked' : ''; ?> checked>
                                    <label class="form-check-label" for="modulo_estoque">Módulo de Estoque</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="modulo_financeiro" name="modulo_financeiro" <?php echo ($plano_edicao && $plano_edicao['modulo_financeiro']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="modulo_financeiro">Módulo Financeiro</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="modulo_relatorios_avancados" name="modulo_relatorios_avancados" <?php echo ($plano_edicao && $plano_edicao['modulo_relatorios_avancados']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="modulo_relatorios_avancados">Relatórios Avançados</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="modulo_multiplos_caixas" name="modulo_multiplos_caixas" <?php echo ($plano_edicao && $plano_edicao['modulo_multiplos_caixas']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="modulo_multiplos_caixas">Múltiplos Caixas</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label fw-bold">Descrição do Plano</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo $plano_edicao ? esc($plano_edicao['descricao']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php echo (!$plano_edicao || $plano_edicao['ativo']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="ativo">Plano Ativo</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <button type="submit" form="form-plano" class="btn btn-primary" name="<?php echo $plano_edicao ? 'atualizar_plano' : 'adicionar_plano'; ?>">
                    <i class="fas fa-save me-1"></i>
                    <?php echo $plano_edicao ? 'Atualizar' : 'Adicionar'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Associação Empresa-Plano -->
<div class="modal fade" id="modal-empresa-plano" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas <?php echo $empresa_plano_edicao ? 'fa-edit' : 'fa-link'; ?> me-2"></i>
                    <?php echo $empresa_plano_edicao ? 'Editar Plano da Empresa' : 'Associar Plano a Empresa'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-empresa-plano" method="post" action="">
                    <?php if ($empresa_plano_edicao): ?>
                        <input type="hidden" name="id" value="<?php echo $empresa_plano_edicao['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="empresa_id" class="form-label fw-bold">Empresa</label>
                            <select class="form-select" id="empresa_id" name="empresa_id" required <?php echo $empresa_plano_edicao ? 'disabled' : ''; ?>>
                                <option value="">Selecione uma empresa</option>
                                <?php foreach ($empresas as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo ($empresa_plano_edicao && $empresa_plano_edicao['empresa_id'] == $emp['id']) ? 'selected' : ''; ?>>
                                        <?php echo esc($emp['nome']); ?> (<?php echo esc($emp['razao_social']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($empresa_plano_edicao): ?>
                                <input type="hidden" name="empresa_id" value="<?php echo $empresa_plano_edicao['empresa_id']; ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="plano_id" class="form-label fw-bold">Plano</label>
                            <select class="form-select" id="plano_id" name="plano_id" required>
                                <option value="">Selecione um plano</option>
                                <?php foreach ($plano->listar() as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo ($empresa_plano_edicao && $empresa_plano_edicao['plano_id'] == $p['id']) ? 'selected' : ''; ?>>
                                        <?php echo esc($p['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="data_expiracao" class="form-label fw-bold">Data de Expiração (opcional)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control" id="data_expiracao" name="data_expiracao" value="<?php echo $empresa_plano_edicao ? esc($empresa_plano_edicao['data_expiracao']) : ''; ?>">
                            </div>
                            <small class="text-muted">Deixe em branco para usar a data do plano ou sem data.</small>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-sliders-h me-2"></i> Limites Personalizados (opcional)
                            </h5>
                            <small class="text-muted">Deixe em branco para usar os limites padrão do plano selecionado.</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="limite_usuarios_personalizado" class="form-label">Limite de Usuários</label>
                                    <input type="number" class="form-control" id="limite_usuarios_personalizado" name="limite_usuarios_personalizado" min="0" value="<?php echo $empresa_plano_edicao ? esc($empresa_plano_edicao['limite_usuarios_personalizado']) : ''; ?>">
                                    <small class="text-muted">0 = Ilimitado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="limite_produtos_personalizado" class="form-label">Limite de Produtos</label>
                                    <input type="number" class="form-control" id="limite_produtos_personalizado" name="limite_produtos_personalizado" min="0" value="<?php echo $empresa_plano_edicao ? esc($empresa_plano_edicao['limite_produtos_personalizado']) : ''; ?>">
                                    <small class="text-muted">0 = Ilimitado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="limite_clientes_personalizado" class="form-label">Limite de Clientes</label>
                                    <input type="number" class="form-control" id="limite_clientes_personalizado" name="limite_clientes_personalizado" min="0" value="<?php echo $empresa_plano_edicao ? esc($empresa_plano_edicao['limite_clientes_personalizado']) : ''; ?>">
                                    <small class="text-muted">0 = Ilimitado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="limite_vendas_diarias_personalizado" class="form-label">Limite de Vendas/dia</label>
                                    <input type="number" class="form-control" id="limite_vendas_diarias_personalizado" name="limite_vendas_diarias_personalizado" min="0" value="<?php echo $empresa_plano_edicao ? esc($empresa_plano_edicao['limite_vendas_diarias_personalizado']) : ''; ?>">
                                    <small class="text-muted">0 = Ilimitado</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-puzzle-piece me-2"></i> Módulos Personalizados (opcional)
                            </h5>
                            <small class="text-muted">Selecione apenas os módulos que deseja personalizar. Os não selecionados usarão a configuração do plano.</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="modulo_comanda" name="modulo_comanda" <?php echo ($empresa_plano_edicao && $empresa_plano_edicao['modulo_comanda'] !== null && $empresa_plano_edicao['modulo_comanda']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="modulo_comanda">Módulo de Comandas</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="modulo_estoque" name="modulo_estoque" <?php echo ($empresa_plano_edicao && $empresa_plano_edicao['modulo_estoque'] !== null && $empresa_plano_edicao['modulo_estoque']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="modulo_estoque">Módulo de Estoque</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="modulo_financeiro" name="modulo_financeiro" <?php echo ($empresa_plano_edicao && $empresa_plano_edicao['modulo_financeiro'] !== null && $empresa_plano_edicao['modulo_financeiro']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="modulo_financeiro">Módulo Financeiro</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="modulo_relatorios_avancados" name="modulo_relatorios_avancados" <?php echo ($empresa_plano_edicao && $empresa_plano_edicao['modulo_relatorios_avancados'] !== null && $empresa_plano_edicao['modulo_relatorios_avancados']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="modulo_relatorios_avancados">Relatórios Avançados</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="modulo_multiplos_caixas" name="modulo_multiplos_caixas" <?php echo ($empresa_plano_edicao && $empresa_plano_edicao['modulo_multiplos_caixas'] !== null && $empresa_plano_edicao['modulo_multiplos_caixas']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="modulo_multiplos_caixas">Múltiplos Caixas</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label fw-bold">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo $empresa_plano_edicao ? esc($empresa_plano_edicao['observacoes']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php echo (!$empresa_plano_edicao || $empresa_plano_edicao['ativo']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="ativo">Associação Ativa</label>
                        <small class="d-block text-muted">Se desativada, a empresa não terá acesso ao sistema.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancelar
                </button>
                <button type="submit" form="form-empresa-plano" class="btn btn-success" name="<?php echo $empresa_plano_edicao ? 'atualizar_empresa_plano' : 'adicionar_empresa_plano'; ?>">
                    <i class="fas fa-save me-1"></i>
                    <?php echo $empresa_plano_edicao ? 'Atualizar' : 'Adicionar'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão de Plano -->
<div class="modal fade" id="modal-excluir-plano" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o plano <strong id="nome-plano-excluir"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Esta ação não poderá ser desfeita. Só é possível excluir planos que não estão sendo usados por empresas.
                </div>
            </div>
            <div class="modal-footer">
                <form method="post" action="">
                    <input type="hidden" name="id" id="id-plano-excluir">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" name="excluir_plano" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>
                        Confirmar Exclusão
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão de Associação Empresa-Plano -->
<div class="modal fade" id="modal-excluir-empresa-plano" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja remover o plano da empresa <strong id="nome-empresa-excluir"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Esta ação não poderá ser desfeita. A empresa perderá o acesso ao sistema imediatamente.
                </div>
            </div>
            <div class="modal-footer">
                <form method="post" action="">
                    <input type="hidden" name="id" id="id-empresa-plano-excluir">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" name="excluir_empresa_plano" class="btn btn-danger">
                        <i class="fas fa-unlink me-1"></i>
                        Confirmar Exclusão
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Inicializa DataTables
        var tabelaPlanos = $('#tabelaPlanos').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            },
            "responsive": true,
            "order": [[0, 'asc']], // Ordenar por nome
            "pageLength": 10
        });
        
        var tabelaEmpresas = $('#tabelaEmpresas').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            },
            "responsive": true,
            "order": [[0, 'asc']], // Ordenar por nome da empresa
            "pageLength": 10
        });
        
        // Filtros de busca
        $('#buscarPlano').on('keyup', function() {
            tabelaPlanos.search($(this).val()).draw();
        });
        
        $('#buscarEmpresaPlano').on('keyup', function() {
            tabelaEmpresas.search($(this).val()).draw();
        });
        
        // Abrir modal de edição automaticamente se tiver plano para editar
        <?php if ($plano_edicao): ?>
        var modalPlano = new bootstrap.Modal(document.getElementById('modal-plano'));
        modalPlano.show();
        <?php endif; ?>
        
        // Abrir modal de edição automaticamente se tiver empresa-plano para editar
        <?php if ($empresa_plano_edicao): ?>
        var modalEmpresaPlano = new bootstrap.Modal(document.getElementById('modal-empresa-plano'));
        modalEmpresaPlano.show();
        <?php endif; ?>
        
        // Configurar modal de exclusão de plano
        $('.btn-excluir-plano').on('click', function() {
            var id = $(this).data('id');
            var nome = $(this).data('nome');
            $('#id-plano-excluir').val(id);
            $('#nome-plano-excluir').text(nome);
            
            var modalExcluirPlano = new bootstrap.Modal(document.getElementById('modal-excluir-plano'));
            modalExcluirPlano.show();
        });
        
        // Configurar modal de exclusão de empresa-plano
        $('.btn-excluir-empresa-plano').on('click', function() {
            var id = $(this).data('id');
            var empresa = $(this).data('empresa');
            $('#id-empresa-plano-excluir').val(id);
            $('#nome-empresa-excluir').text(empresa);
            
            var modalExcluirEmpresaPlano = new bootstrap.Modal(document.getElementById('modal-excluir-empresa-plano'));
            modalExcluirEmpresaPlano.show();
        });
        
        // Ativar as tabs
        var triggerTabList = [].slice.call(document.querySelectorAll('#planosTab a'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
            })
        });
        
        // Validação dos formulários
        $('#form-plano').on('submit', function() {
            if ($('#nome').val().trim() === '') {
                alert('Por favor, informe o nome do plano.');
                $('#nome').focus();
                return false;
            }
            return true;
        });
        
        $('#form-empresa-plano').on('submit', function() {
            if ($('#empresa_id').val() === '') {
                alert('Por favor, selecione uma empresa.');
                $('#empresa_id').focus();
                return false;
            }
            if ($('#plano_id').val() === '') {
                alert('Por favor, selecione um plano.');
                $('#plano_id').focus();
                return false;
            }
            return true;
        });
    });
</script>

<?php include 'footer.php'; ?>