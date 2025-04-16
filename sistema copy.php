<?php
/*
 * Sistema PDV (Ponto de Venda)
 * 
 * Arquivo principal do sistema contendo:
 * - Funções de conexão com banco de dados
 * - Funções utilitárias
 * - Classes principais
 */

// Função para conectar ao banco de dados
function conectarBD($config) {
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['charset']}";
        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $opcoes);
        return $pdo;
    } catch (PDOException $e) {
        die('Erro de conexão: ' . $e->getMessage());
    }
}

// Função para verificar se o usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Função para redirecionar
function redirecionar($url) {
    header("Location: $url");
    exit;
}

// Função para escapar dados e prevenir XSS
function esc($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Função para gerar hash seguro de senha
function gerarHash($senha) {
    return password_hash($senha, PASSWORD_DEFAULT);
}

// Função para verificar senha
function verificarSenha($senha, $hash) {
    return password_verify($senha, $hash);
}

// Função para mensagem de alerta
function alerta($mensagem, $tipo = 'info') {
    $_SESSION['alerta'] = [
        'mensagem' => $mensagem,
        'tipo' => $tipo
    ];
}

// Função para exibir alerta
function exibirAlerta() {
    if (isset($_SESSION['alerta'])) {
        $alerta = $_SESSION['alerta'];
        echo "<div class='alert alert-{$alerta['tipo']}'>{$alerta['mensagem']}</div>";
        unset($_SESSION['alerta']);
    }
}

// Função para formatar valor monetário
function formatarDinheiro($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// Função para formatar data
function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}
// Classe para tabelas do banco de dados
class TabelasBD {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Criar todas as tabelas necessárias
    public function criarTabelas() {
        // Usuários
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                usuario VARCHAR(50) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                nivel ENUM('admin', 'vendedor', 'gerente') NOT NULL DEFAULT 'vendedor',
                ativo BOOLEAN NOT NULL DEFAULT TRUE,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Categorias
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS categorias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL UNIQUE,
                descricao TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Produtos
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS produtos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(50) NOT NULL UNIQUE,
                nome VARCHAR(100) NOT NULL,
                descricao TEXT,
                preco_custo DECIMAL(10,2) NOT NULL,
                preco_venda DECIMAL(10,2) NOT NULL,
                estoque_atual INT NOT NULL DEFAULT 0,
                estoque_minimo INT NOT NULL DEFAULT 5,
                categoria_id INT,
                ativo BOOLEAN NOT NULL DEFAULT TRUE,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Clientes
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS clientes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                cpf_cnpj VARCHAR(20) UNIQUE,
                email VARCHAR(100),
                telefone VARCHAR(20),
                endereco TEXT,
                cidade VARCHAR(100),
                estado CHAR(2),
                cep VARCHAR(10),
                observacoes TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Fornecedores
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fornecedores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                cpf_cnpj VARCHAR(20) UNIQUE,
                email VARCHAR(100),
                telefone VARCHAR(20),
                endereco TEXT,
                cidade VARCHAR(100),
                estado CHAR(2),
                cep VARCHAR(10),
                observacoes TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Vendas
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS vendas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                cliente_id INT,
                data_venda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                valor_total DECIMAL(10,2) NOT NULL,
                desconto DECIMAL(10,2) DEFAULT 0,
                forma_pagamento ENUM('dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'boleto') NOT NULL,
                status ENUM('finalizada', 'cancelada', 'pendente') NOT NULL DEFAULT 'finalizada',
                observacoes TEXT,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
                FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Itens Venda
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS itens_venda (
                id INT AUTO_INCREMENT PRIMARY KEY,
                venda_id INT NOT NULL,
                produto_id INT NOT NULL,
                quantidade INT NOT NULL,
                preco_unitario DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
                FOREIGN KEY (produto_id) REFERENCES produtos(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Movimentações de Estoque
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
                id INT AUTO_INCREMENT PRIMARY KEY,
                produto_id INT NOT NULL,
                usuario_id INT NOT NULL,
                tipo ENUM('entrada', 'saida', 'ajuste') NOT NULL,
                quantidade INT NOT NULL,
                data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                observacao TEXT,
                origem ENUM('compra', 'venda', 'ajuste_manual', 'devolucao') NOT NULL,
                documento_id INT,
                FOREIGN KEY (produto_id) REFERENCES produtos(id),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

// Caixa - Controle de abertura e fechamento do caixa
$this->pdo->exec("
    CREATE TABLE IF NOT EXISTS caixas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        data_abertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_fechamento TIMESTAMP NULL DEFAULT NULL,
        valor_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
        valor_final DECIMAL(10,2) NULL DEFAULT NULL,
        valor_vendas DECIMAL(10,2) NULL DEFAULT NULL,
        valor_sangrias DECIMAL(10,2) NULL DEFAULT NULL,
        valor_suprimentos DECIMAL(10,2) NULL DEFAULT NULL,
        observacoes TEXT NULL,
        status ENUM('aberto', 'fechado') NOT NULL DEFAULT 'aberto',
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Movimentações do Caixa - Registra entradas e saídas
$this->pdo->exec("
    CREATE TABLE IF NOT EXISTS movimentacoes_caixa (
        id INT AUTO_INCREMENT PRIMARY KEY,
        caixa_id INT NOT NULL,
        usuario_id INT NOT NULL,
        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        tipo ENUM('venda', 'sangria', 'suprimento') NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        forma_pagamento ENUM('dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'boleto') NULL DEFAULT NULL,
        documento_id INT NULL, -- ID da venda, se for o caso
        observacoes TEXT NULL,
        FOREIGN KEY (caixa_id) REFERENCES caixas(id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

        // Compras (Entrada de produtos)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS compras (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fornecedor_id INT,
                usuario_id INT NOT NULL,
                data_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                valor_total DECIMAL(10,2) NOT NULL,
                status ENUM('finalizada', 'pendente', 'cancelada') NOT NULL DEFAULT 'finalizada',
                observacoes TEXT,
                FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Itens Compra
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS itens_compra (
                id INT AUTO_INCREMENT PRIMARY KEY,
                compra_id INT NOT NULL,
                produto_id INT NOT NULL,
                quantidade INT NOT NULL,
                preco_unitario DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
                FOREIGN KEY (produto_id) REFERENCES produtos(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Logs do Sistema
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS logs_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                usuario_nome VARCHAR(100) NOT NULL,
                acao VARCHAR(50) NOT NULL,
                detalhes TEXT,
                ip VARCHAR(45),
                data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Configurações da Empresa
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS configuracoes_empresa (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                razao_social VARCHAR(100) NOT NULL,
                cnpj VARCHAR(20),
                endereco TEXT,
                cidade VARCHAR(100),
                estado CHAR(2),
                telefone VARCHAR(20),
                email VARCHAR(100),
                site VARCHAR(100),
                logo VARCHAR(255),
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Configurações do Sistema
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS configuracoes_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                itens_por_pagina INT NOT NULL DEFAULT 25,
                tema VARCHAR(20) NOT NULL DEFAULT 'claro',
                moeda VARCHAR(10) NOT NULL DEFAULT 'BRL',
                formato_data VARCHAR(20) NOT NULL DEFAULT 'd/m/Y',
                estoque_negativo BOOLEAN NOT NULL DEFAULT TRUE,
                alerta_estoque BOOLEAN NOT NULL DEFAULT TRUE,
                impressao_automatica BOOLEAN NOT NULL DEFAULT TRUE,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        // Adicionar campo caixa_obrigatorio à tabela configuracoes_sistema se ainda não existir
try {
    // Verificar se a coluna existe
    $stmt = $this->pdo->prepare("SHOW COLUMNS FROM configuracoes_sistema LIKE 'caixa_obrigatorio'");
    $stmt->execute();
    $coluna_existe = $stmt->rowCount() > 0;
    
    if (!$coluna_existe) {
        // Adicionar a coluna se não existir
        $this->pdo->exec("ALTER TABLE configuracoes_sistema ADD COLUMN caixa_obrigatorio BOOLEAN NOT NULL DEFAULT TRUE");
    }
} catch (Exception $e) {
    // Ignora o erro, pois não é crítico
    if (function_exists('error_log')) {
        error_log("Erro ao verificar/adicionar coluna caixa_obrigatorio: " . $e->getMessage());
    }
}
        
        // Inserir configurações padrão do sistema se não existirem
        $stmt = $this->pdo->prepare("SELECT id FROM configuracoes_sistema LIMIT 1");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            $stmt = $this->pdo->prepare("
                INSERT INTO configuracoes_sistema 
                (itens_por_pagina, tema, moeda, formato_data, estoque_negativo, alerta_estoque, impressao_automatica) 
                VALUES 
                (25, 'claro', 'BRL', 'd/m/Y', TRUE, TRUE, TRUE)
            ");
            $stmt->execute();
        }
        
        // Inserir configurações padrão da empresa se não existirem
        $stmt = $this->pdo->prepare("SELECT id FROM configuracoes_empresa LIMIT 1");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            $stmt = $this->pdo->prepare("
                INSERT INTO configuracoes_empresa 
                (nome, razao_social, cnpj, endereco, cidade, estado, telefone, email, site) 
                VALUES 
                ('Minha Empresa', 'Minha Empresa LTDA', '12.345.678/0001-90', 'Rua Exemplo, 123', 'São Paulo', 'SP', '(11) 1234-5678', 'contato@minhaempresa.com', 'www.minhaempresa.com')
            ");
            $stmt->execute();
        }

        // Criar usuário admin padrão se não existir
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE usuario = 'admin' LIMIT 1");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            $senhaHash = gerarHash('admin123');
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, usuario, senha, email, nivel) VALUES ('Administrador', 'admin', :senha, 'admin@sistema.com', 'admin')");
            $stmt->bindParam(':senha', $senhaHash);
            $stmt->execute();
        }

        // Criar categoria padrão se não existir
        $stmt = $this->pdo->prepare("SELECT id FROM categorias WHERE nome = 'Geral' LIMIT 1");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            $stmt = $this->pdo->prepare("INSERT INTO categorias (nome, descricao) VALUES ('Geral', 'Categoria geral para produtos diversos')");
            $stmt->execute();
        }

        return true;
    }
}
// Classe para gerenciar configurações da empresa
class ConfiguracaoEmpresa {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Buscar configurações da empresa
    public function buscar() {
        $stmt = $this->pdo->query("SELECT * FROM configuracoes_empresa LIMIT 1");
        return $stmt->fetch();
    }

    // Atualizar configurações da empresa
    public function atualizar($dados) {
        $stmt = $this->pdo->prepare("
            UPDATE configuracoes_empresa SET 
            nome = :nome, 
            razao_social = :razao_social, 
            cnpj = :cnpj, 
            endereco = :endereco, 
            cidade = :cidade, 
            estado = :estado, 
            telefone = :telefone, 
            email = :email, 
            site = :site
            WHERE id = :id
        ");
        
        $stmt->bindParam(':id', $dados['id']);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':razao_social', $dados['razao_social']);
        $stmt->bindParam(':cnpj', $dados['cnpj']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':cidade', $dados['cidade']);
        $stmt->bindParam(':estado', $dados['estado']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':site', $dados['site']);
        
        $result = $stmt->execute();
        
        // Registrar no log do sistema
        if ($result && isset($GLOBALS['log'])) {
            $GLOBALS['log']->registrar('Configuração', "Informações da empresa atualizadas");
        }
        
        return $result;
    }

    // Atualizar logo da empresa
    public function atualizarLogo($arquivo_temp, $nome_arquivo) {
        // Diretório para uploads
        $diretorio_uploads = dirname(__FILE__) . '/uploads';
        
        // Cria o diretório se não existir
        if (!file_exists($diretorio_uploads)) {
            mkdir($diretorio_uploads, 0755, true);
        }
        
        // Gera um nome único para o arquivo
        $extensao = pathinfo($nome_arquivo, PATHINFO_EXTENSION);
        $novo_nome = 'logo_' . date('YmdHis') . '.' . $extensao;
        $caminho_destino = $diretorio_uploads . '/' . $novo_nome;
        
        // Move o arquivo para o diretório de uploads
        if (move_uploaded_file($arquivo_temp, $caminho_destino)) {
            // Atualiza o nome do arquivo no banco de dados
            $stmt = $this->pdo->prepare("UPDATE configuracoes_empresa SET logo = :logo");
            $stmt->bindParam(':logo', $novo_nome);
            
            $result = $stmt->execute();
            
            // Registrar no log do sistema
            if ($result && isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar('Configuração', "Logo da empresa atualizada");
            }
            
            return $result;
        }
        
        return false;
    }
}

// Classe para gerenciar configurações do sistema
class ConfiguracaoSistema {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Buscar configurações do sistema
    public function buscar() {
        $stmt = $this->pdo->query("SELECT * FROM configuracoes_sistema LIMIT 1");
        return $stmt->fetch();
    }

    // Atualizar configurações do sistema
    public function atualizar($dados) {
        $stmt = $this->pdo->prepare("
        UPDATE configuracoes_sistema SET 
        itens_por_pagina = :itens_por_pagina, 
        tema = :tema, 
        moeda = :moeda, 
        formato_data = :formato_data, 
        estoque_negativo = :estoque_negativo, 
        alerta_estoque = :alerta_estoque, 
        impressao_automatica = :impressao_automatica,
        caixa_obrigatorio = :caixa_obrigatorio
        WHERE id = :id
    ");
        
        // Converter checkbox para booleano
        $estoque_negativo = isset($dados['estoque_negativo']) ? 1 : 0;
        $alerta_estoque = isset($dados['alerta_estoque']) ? 1 : 0;
        $impressao_automatica = isset($dados['impressao_automatica']) ? 1 : 0;
        $caixa_obrigatorio = isset($dados['caixa_obrigatorio']) ? 1 : 0;

        $stmt->bindParam(':id', $dados['id']);
        $stmt->bindParam(':itens_por_pagina', $dados['itens_por_pagina']);
        $stmt->bindParam(':tema', $dados['tema']);
        $stmt->bindParam(':moeda', $dados['moeda']);
        $stmt->bindParam(':formato_data', $dados['formato_data']);
        $stmt->bindParam(':estoque_negativo', $estoque_negativo);
        $stmt->bindParam(':alerta_estoque', $alerta_estoque);
        $stmt->bindParam(':impressao_automatica', $impressao_automatica);
        $stmt->bindParam(':caixa_obrigatorio', $caixa_obrigatorio, PDO::PARAM_INT);
        
        $result = $stmt->execute();
        
        // Registrar no log do sistema
        if ($result && isset($GLOBALS['log'])) {
            $GLOBALS['log']->registrar('Configuração', "Configurações do sistema atualizadas");
        }
        
        return $result;
    }
}
// Classe para gerenciar o caixa
class Caixa {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Verificar se existe um caixa aberto para o usuário atual
    public function verificarCaixaAberto() {
        $usuario_id = $_SESSION['usuario_id'];
        $stmt = $this->pdo->prepare("
            SELECT id, data_abertura, valor_inicial 
            FROM caixas 
            WHERE usuario_id = :usuario_id AND status = 'aberto' 
            LIMIT 1
        ");
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Abrir um novo caixa
    public function abrir($valor_inicial, $observacoes = '') {
        try {
            // Verificar se já existe um caixa aberto para este usuário
            $caixa_existente = $this->verificarCaixaAberto();
            if ($caixa_existente) {
                throw new Exception("Já existe um caixa aberto para este usuário.");
            }
            
            $usuario_id = $_SESSION['usuario_id'];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO caixas 
                (usuario_id, valor_inicial, observacoes) 
                VALUES 
                (:usuario_id, :valor_inicial, :observacoes)
            ");
            
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':valor_inicial', $valor_inicial, PDO::PARAM_STR);
            $stmt->bindParam(':observacoes', $observacoes, PDO::PARAM_STR);
            
            $stmt->execute();
            $caixa_id = $this->pdo->lastInsertId();
            
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Caixa', 
                    "Caixa #{$caixa_id} aberto com valor inicial de " . formatarDinheiro($valor_inicial)
                );
            }
            
            return $caixa_id;
            
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log("Erro ao abrir caixa: " . $e->getMessage());
            }
            throw $e;
        }
    }

    // Adicionar uma movimentação (venda, sangria ou suprimento)
    public function adicionarMovimentacao($dados) {
        try {
            // Verificar se existe um caixa aberto
            $caixa = $this->verificarCaixaAberto();
            if (!$caixa) {
                throw new Exception("Não há um caixa aberto para registrar esta movimentação.");
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO movimentacoes_caixa 
                (caixa_id, usuario_id, tipo, valor, forma_pagamento, documento_id, observacoes) 
                VALUES 
                (:caixa_id, :usuario_id, :tipo, :valor, :forma_pagamento, :documento_id, :observacoes)
            ");
            
            $usuario_id = $_SESSION['usuario_id'];
            
            $stmt->bindParam(':caixa_id', $caixa['id'], PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':tipo', $dados['tipo'], PDO::PARAM_STR);
            $stmt->bindParam(':valor', $dados['valor'], PDO::PARAM_STR);
            $stmt->bindParam(':forma_pagamento', $dados['forma_pagamento'], PDO::PARAM_STR);
            $stmt->bindParam(':documento_id', $dados['documento_id'], PDO::PARAM_INT);
            $stmt->bindParam(':observacoes', $dados['observacoes'], PDO::PARAM_STR);
            
            $stmt->execute();
            $movimentacao_id = $this->pdo->lastInsertId();
            
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $tipo_texto = ucfirst($dados['tipo']);
                $valor_formatado = formatarDinheiro($dados['valor']);
                
                $GLOBALS['log']->registrar(
                    'Caixa', 
                    "{$tipo_texto} registrada no caixa #{$caixa['id']} no valor de {$valor_formatado}"
                );
            }
            
            return $movimentacao_id;
            
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log("Erro ao adicionar movimentação: " . $e->getMessage());
            }
            throw $e;
        }
    }

    // Registrar uma sangria (retirada de dinheiro do caixa)
    public function registrarSangria($valor, $observacoes = '') {
        try {
            $dados = [
                'tipo' => 'sangria',
                'valor' => $valor,
                'forma_pagamento' => 'dinheiro', // Sangria só acontece em dinheiro
                'documento_id' => null,
                'observacoes' => $observacoes
            ];
            
            return $this->adicionarMovimentacao($dados);
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Registrar um suprimento (adição de dinheiro ao caixa)
    public function registrarSuprimento($valor, $observacoes = '') {
        try {
            $dados = [
                'tipo' => 'suprimento',
                'valor' => $valor,
                'forma_pagamento' => 'dinheiro', // Suprimento só acontece em dinheiro
                'documento_id' => null,
                'observacoes' => $observacoes
            ];
            
            return $this->adicionarMovimentacao($dados);
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Fechar o caixa
    public function fechar($valor_final, $observacoes = '') {
        try {
            // Verificar se existe um caixa aberto
            $caixa = $this->verificarCaixaAberto();
            if (!$caixa) {
                throw new Exception("Não há um caixa aberto para fechar.");
            }
            
            // Calcular totais
            $valor_vendas = $this->calcularTotalVendas($caixa['id']);
            $valor_sangrias = $this->calcularTotalSangrias($caixa['id']);
            $valor_suprimentos = $this->calcularTotalSuprimentos($caixa['id']);
            
            // Atualizar o caixa
            $stmt = $this->pdo->prepare("
                UPDATE caixas SET
                data_fechamento = NOW(),
                valor_final = :valor_final,
                valor_vendas = :valor_vendas,
                valor_sangrias = :valor_sangrias,
                valor_suprimentos = :valor_suprimentos,
                observacoes = CONCAT(IFNULL(observacoes, ''), '\n', :observacoes),
                status = 'fechado'
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $caixa['id'], PDO::PARAM_INT);
            $stmt->bindParam(':valor_final', $valor_final, PDO::PARAM_STR);
            $stmt->bindParam(':valor_vendas', $valor_vendas, PDO::PARAM_STR);
            $stmt->bindParam(':valor_sangrias', $valor_sangrias, PDO::PARAM_STR);
            $stmt->bindParam(':valor_suprimentos', $valor_suprimentos, PDO::PARAM_STR);
            $stmt->bindParam(':observacoes', $observacoes, PDO::PARAM_STR);
            
            $stmt->execute();
            
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Caixa', 
                    "Caixa #{$caixa['id']} fechado com valor final de " . formatarDinheiro($valor_final)
                );
            }
            
            // Calcular diferença entre valor esperado e valor informado
            $valor_esperado = $caixa['valor_inicial'] + $valor_vendas + $valor_suprimentos - $valor_sangrias;
            $diferenca = $valor_final - $valor_esperado;
            
            return [
                'caixa_id' => $caixa['id'],
                'valor_inicial' => $caixa['valor_inicial'],
                'valor_final' => $valor_final,
                'valor_vendas' => $valor_vendas,
                'valor_sangrias' => $valor_sangrias,
                'valor_suprimentos' => $valor_suprimentos,
                'valor_esperado' => $valor_esperado,
                'diferenca' => $diferenca
            ];
            
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log("Erro ao fechar caixa: " . $e->getMessage());
            }
            throw $e;
        }
    }

    // Listar movimentações do caixa atual
    public function listarMovimentacoes($caixa_id = null) {
        try {
            // Se não foi informado um caixa_id, usa o caixa aberto do usuário atual
            if ($caixa_id === null) {
                $caixa = $this->verificarCaixaAberto();
                if (!$caixa) {
                    throw new Exception("Não há um caixa aberto para listar movimentações.");
                }
                $caixa_id = $caixa['id'];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.*, 
                    u.nome AS usuario_nome,
                    DATE_FORMAT(m.data_hora, '%d/%m/%Y %H:%i') AS data_formatada
                FROM movimentacoes_caixa m
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                WHERE m.caixa_id = :caixa_id
                ORDER BY m.data_hora
            ");
            
            $stmt->bindParam(':caixa_id', $caixa_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log("Erro ao listar movimentações: " . $e->getMessage());
            }
            throw $e;
        }
    }

    // Buscar caixa por ID
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   u.nome AS usuario_nome,
                   DATE_FORMAT(c.data_abertura, '%d/%m/%Y %H:%i') AS data_abertura_formatada,
                   DATE_FORMAT(c.data_fechamento, '%d/%m/%Y %H:%i') AS data_fechamento_formatada
            FROM caixas c
            LEFT JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.id = :id
            LIMIT 1
        ");
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Listar histórico de caixas
    public function listarHistorico($limite = 30) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   u.nome AS usuario_nome,
                   DATE_FORMAT(c.data_abertura, '%d/%m/%Y %H:%i') AS data_abertura_formatada,
                   DATE_FORMAT(c.data_fechamento, '%d/%m/%Y %H:%i') AS data_fechamento_formatada
            FROM caixas c
            LEFT JOIN usuarios u ON c.usuario_id = u.id
            ORDER BY c.data_abertura DESC
            LIMIT :limite
        ");
        
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Calcular o total de vendas do caixa
    private function calcularTotalVendas($caixa_id) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) AS total 
            FROM movimentacoes_caixa 
            WHERE caixa_id = :caixa_id AND tipo = 'venda'
        ");
        
        $stmt->bindParam(':caixa_id', $caixa_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado['total'];
    }

    // Calcular o total de sangrias do caixa
    private function calcularTotalSangrias($caixa_id) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) AS total 
            FROM movimentacoes_caixa 
            WHERE caixa_id = :caixa_id AND tipo = 'sangria'
        ");
        
        $stmt->bindParam(':caixa_id', $caixa_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado['total'];
    }

    // Calcular o total de suprimentos do caixa
    private function calcularTotalSuprimentos($caixa_id) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) AS total 
            FROM movimentacoes_caixa 
            WHERE caixa_id = :caixa_id AND tipo = 'suprimento'
        ");
        
        $stmt->bindParam(':caixa_id', $caixa_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado['total'];
    }

    // Resumo das vendas por forma de pagamento
    public function resumoVendasPorFormaPagamento($caixa_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                forma_pagamento,
                COUNT(*) AS quantidade,
                SUM(valor) AS total
            FROM movimentacoes_caixa 
            WHERE caixa_id = :caixa_id AND tipo = 'venda'
            GROUP BY forma_pagamento
        ");
        
        $stmt->bindParam(':caixa_id', $caixa_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Verificar se o caixa precisa ser aberto para fazer vendas
    public function verificarCaixaNecessario() {
        try {
            // Buscar configuração na tabela configuracoes_sistema
            $stmt = $this->pdo->query("
                SELECT caixa_obrigatorio FROM configuracoes_sistema LIMIT 1
            ");
            $config = $stmt->fetch();
            
            // Se a configuração existir e for verdadeira, verifica se o caixa está aberto
            if (isset($config['caixa_obrigatorio']) && $config['caixa_obrigatorio']) {
                $caixa = $this->verificarCaixaAberto();
                return $caixa ? false : true; // Precisa abrir caixa se não houver caixa aberto
            }
            
            // Se a configuração não existir ou for falsa, não é necessário ter caixa aberto
            return false;
            
        } catch (Exception $e) {
            // Em caso de erro, retorna false (não bloqueia a venda)
            if (function_exists('error_log')) {
                error_log("Erro ao verificar necessidade de caixa: " . $e->getMessage());
            }
            return false;
        }
    }
}

// Classe para gerenciar usuários
class Usuario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Login de usuário
    public function login($usuario, $senha) {
        $stmt = $this->pdo->prepare("SELECT id, nome, usuario, senha, nivel FROM usuarios WHERE usuario = :usuario AND ativo = TRUE LIMIT 1");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if ($user && verificarSenha($senha, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome'];
            $_SESSION['usuario_nivel'] = $user['nivel'];
            
            // Registrar o login no log do sistema
            if (isset($GLOBALS['log'])) {
                // Como o usuário ainda não está logado nos cabeçalhos da sessão,
                // precisamos configurar manualmente para o log
                $_SESSION['usuario_id_temp'] = $user['id'];
                $_SESSION['usuario_nome_temp'] = $user['nome'];
                
                // Registrar no log
                $GLOBALS['log']->registrar('Login', 'Login realizado com sucesso');
                
                // Remover as variáveis temporárias
                unset($_SESSION['usuario_id_temp']);
                unset($_SESSION['usuario_nome_temp']);
            }
            
            return true;
        }
        
        // Registrar tentativa de login falha, se possível
        if (isset($GLOBALS['log'])) {
            // Como não podemos identificar o usuário com certeza (login falhou),
            // registramos com informações limitadas
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            
            // Inserir diretamente no banco, já que o método normal requer usuário logado
            $stmt = $this->pdo->prepare("
                INSERT INTO logs_sistema 
                (usuario_id, usuario_nome, acao, detalhes, ip) 
                VALUES 
                (0, :usuario_tentativa, 'Login', 'Tentativa de login falhou', :ip)
            ");
            
            $stmt->bindParam(':usuario_tentativa', $usuario);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
        }
        
        return false;
    }

    // Logout
    public function logout() {
        session_unset();
        session_destroy();
        session_start();
        return true;
    }

    // Listar todos os usuários
    public function listar() {
        $stmt = $this->pdo->query("SELECT id, nome, usuario, email, nivel, ativo, DATE_FORMAT(criado_em, '%d/%m/%Y') AS criado_em FROM usuarios ORDER BY nome");
        return $stmt->fetchAll();
    }

    // Buscar usuário por ID
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, nome, usuario, email, nivel, ativo FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Adicionar usuário
    public function adicionar($dados) {
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, usuario, senha, email, nivel) VALUES (:nome, :usuario, :senha, :email, :nivel)");
        
        $senhaHash = gerarHash($dados['senha']);
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':usuario', $dados['usuario']);
        $stmt->bindParam(':senha', $senhaHash);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':nivel', $dados['nivel']);
        
        return $stmt->execute();
    }

    // Atualizar usuário
    public function atualizar($id, $dados) {
        // Verifica se tem senha nova
        if (!empty($dados['senha'])) {
            $stmt = $this->pdo->prepare("UPDATE usuarios SET nome = :nome, usuario = :usuario, senha = :senha, email = :email, nivel = :nivel, ativo = :ativo WHERE id = :id");
            $senhaHash = gerarHash($dados['senha']);
            $stmt->bindParam(':senha', $senhaHash);
        } else {
            $stmt = $this->pdo->prepare("UPDATE usuarios SET nome = :nome, usuario = :usuario, email = :email, nivel = :nivel, ativo = :ativo WHERE id = :id");
        }
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':usuario', $dados['usuario']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':nivel', $dados['nivel']);
        $stmt->bindParam(':ativo', $dados['ativo'], PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }

    // Excluir usuário
    public function excluir($id) {
        // Verificar se não é o próprio usuário logado
        if ($_SESSION['usuario_id'] == $id) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
// Classe para gerenciar produtos
class Produto {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Listar todos os produtos
    public function listar() {
        $stmt = $this->pdo->query("
            SELECT p.*, c.nome AS categoria_nome 
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            ORDER BY p.nome
        ");
        return $stmt->fetchAll();
    }

    // Buscar produto por ID
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.nome AS categoria_nome 
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.id = :id LIMIT 1
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Buscar produto por código
    public function buscarPorCodigo($codigo) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.nome AS categoria_nome 
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.codigo = :codigo AND p.ativo = TRUE LIMIT 1
        ");
        $stmt->bindParam(':codigo', $codigo);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Adicionar produto
    public function adicionar($dados) {
        $stmt = $this->pdo->prepare("
            INSERT INTO produtos 
            (codigo, nome, descricao, preco_custo, preco_venda, estoque_atual, estoque_minimo, categoria_id, ativo) 
            VALUES 
            (:codigo, :nome, :descricao, :preco_custo, :preco_venda, :estoque_atual, :estoque_minimo, :categoria_id, :ativo)
        ");
        
        $stmt->bindParam(':codigo', $dados['codigo']);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        $stmt->bindParam(':preco_custo', $dados['preco_custo']);
        $stmt->bindParam(':preco_venda', $dados['preco_venda']);
        $stmt->bindParam(':estoque_atual', $dados['estoque_atual'], PDO::PARAM_INT);
        $stmt->bindParam(':estoque_minimo', $dados['estoque_minimo'], PDO::PARAM_INT);
        $stmt->bindParam(':categoria_id', $dados['categoria_id'], PDO::PARAM_INT);
        $stmt->bindParam(':ativo', $dados['ativo'], PDO::PARAM_BOOL);
        
        $result = $stmt->execute();
        
        if ($result) {
            // Registrar movimentação inicial de estoque
            $produto_id = $this->pdo->lastInsertId();
            
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->registrar(
                    'Produto', 
                    "Produto {$dados['nome']} (ID: {$produto_id}) adicionado"
                );
            }
            
            if ($dados['estoque_atual'] > 0) {
                $this->registrarMovimentacao([
                    'produto_id' => $produto_id,
                    'tipo' => 'entrada',
                    'quantidade' => $dados['estoque_atual'],
                    'observacao' => 'Estoque inicial',
                    'origem' => 'ajuste_manual'
                ]);
            }
        }
        
        return $result;
    }

    // Atualizar produto
    public function atualizar($id, $dados) {
        $produto = $this->buscarPorId($id);
        $estoqueAtual = $produto['estoque_atual'];
        
        $stmt = $this->pdo->prepare("
            UPDATE produtos SET 
            codigo = :codigo, 
            nome = :nome, 
            descricao = :descricao, 
            preco_custo = :preco_custo, 
            preco_venda = :preco_venda, 
            estoque_atual = :estoque_atual, 
            estoque_minimo = :estoque_minimo, 
            categoria_id = :categoria_id, 
            ativo = :ativo 
            WHERE id = :id
        ");
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':codigo', $dados['codigo']);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        $stmt->bindParam(':preco_custo', $dados['preco_custo']);
        $stmt->bindParam(':preco_venda', $dados['preco_venda']);
        $stmt->bindParam(':estoque_atual', $dados['estoque_atual'], PDO::PARAM_INT);
        $stmt->bindParam(':estoque_minimo', $dados['estoque_minimo'], PDO::PARAM_INT);
        $stmt->bindParam(':categoria_id', $dados['categoria_id'], PDO::PARAM_INT);
        $stmt->bindParam(':ativo', $dados['ativo'], PDO::PARAM_BOOL);
        
        $result = $stmt->execute();
        
        // Se houve ajuste de estoque, registrar movimentação
        if ($result && $estoqueAtual != $dados['estoque_atual']) {
            $diferenca = $dados['estoque_atual'] - $estoqueAtual;
            $tipo = ($diferenca > 0) ? 'entrada' : 'saida';
            $quantidade = abs($diferenca);
            
            $this->registrarMovimentacao([
                'produto_id' => $id,
                'tipo' => $tipo,
                'quantidade' => $quantidade,
                'observacao' => 'Ajuste manual de estoque',
                'origem' => 'ajuste_manual'
            ]);
        }
        
        return $result;
    }

    // Excluir produto
    public function excluir($id) {
        // Na prática, é melhor desativar do que excluir
        $stmt = $this->pdo->prepare("UPDATE produtos SET ativo = FALSE WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Atualizar estoque
    public function atualizarEstoque($id, $quantidade, $tipo) {
        if ($tipo == 'entrada') {
            $stmt = $this->pdo->prepare("UPDATE produtos SET estoque_atual = estoque_atual + :quantidade WHERE id = :id");
        } else {
            $stmt = $this->pdo->prepare("UPDATE produtos SET estoque_atual = estoque_atual - :quantidade WHERE id = :id");
        }
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Registrar movimentação de estoque
    public function registrarMovimentacao($dados) {
        $stmt = $this->pdo->prepare("
            INSERT INTO movimentacoes_estoque 
            (produto_id, usuario_id, tipo, quantidade, observacao, origem, documento_id) 
            VALUES 
            (:produto_id, :usuario_id, :tipo, :quantidade, :observacao, :origem, :documento_id)
        ");
        
        $usuario_id = $_SESSION['usuario_id'] ?? 1; // Admin padrão se não estiver logado
        
        $stmt->bindParam(':produto_id', $dados['produto_id'], PDO::PARAM_INT);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(':tipo', $dados['tipo']);
        $stmt->bindParam(':quantidade', $dados['quantidade'], PDO::PARAM_INT);
        $stmt->bindParam(':observacao', $dados['observacao']);
        $stmt->bindParam(':origem', $dados['origem']);
        //$stmt->bindParam(':documento_id', $dados['documento_id'] ?? null, PDO::PARAM_INT);
        $documento_id = $dados['documento_id'] ?? null;
        $stmt->bindParam(':documento_id', $documento_id, PDO::PARAM_INT);
        $result = $stmt->execute();
        
        if ($result) {
            // Atualiza o estoque do produto
            $this->atualizarEstoque($dados['produto_id'], $dados['quantidade'], $dados['tipo']);
        }
        
        return $result;
    }

    // Verificar estoque disponível
    public function verificarEstoque($id, $quantidade) {
        $produto = $this->buscarPorId($id);
        return ($produto && $produto['estoque_atual'] >= $quantidade);
    }

    // Listar produtos com estoque baixo
    public function listarEstoqueBaixo() {
        $stmt = $this->pdo->query("
            SELECT p.*, c.nome AS categoria_nome 
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.estoque_atual <= p.estoque_minimo AND p.ativo = TRUE
            ORDER BY p.nome
        ");
        return $stmt->fetchAll();
    }
}

// Classe para gerenciar vendas
class Venda {
    private $pdo;
    private $produto;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->produto = new Produto($pdo);
    }

    // Listar todas as vendas
    public function listar() {
        $stmt = $this->pdo->query("
            SELECT v.*, u.nome AS usuario_nome, c.nome AS cliente_nome, 
            DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN clientes c ON v.cliente_id = c.id
            ORDER BY v.data_venda DESC
        ");
        return $stmt->fetchAll();
    }

    // Buscar venda por ID
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("
            SELECT v.*, u.nome AS usuario_nome, c.nome AS cliente_nome,
            DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN clientes c ON v.cliente_id = c.id
            WHERE v.id = :id LIMIT 1
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Buscar itens de uma venda
    public function buscarItens($venda_id) {
        $stmt = $this->pdo->prepare("
            SELECT i.*, p.nome AS produto_nome, p.codigo AS produto_codigo
            FROM itens_venda i
            LEFT JOIN produtos p ON i.produto_id = p.id
            WHERE i.venda_id = :venda_id
            ORDER BY i.id
        ");
        $stmt->bindParam(':venda_id', $venda_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Adicionar venda
    public function adicionar($dados) {
        try {
            // Log para debug
            error_log("Iniciando adição de venda com dados: " . print_r($dados, true));
            
            // Inicia transação
            $this->pdo->beginTransaction();
            
            // Insere a venda
            $stmt = $this->pdo->prepare("
                INSERT INTO vendas 
                (usuario_id, cliente_id, valor_total, desconto, forma_pagamento, status, observacoes) 
                VALUES 
                (:usuario_id, :cliente_id, :valor_total, :desconto, :forma_pagamento, :status, :observacoes)
            ");
            
            $usuario_id = $_SESSION['usuario_id'];
            $cliente_id = $dados['cliente_id'];
            $valor_total = $dados['valor_total'];
            $desconto = $dados['desconto'];
            $forma_pagamento = $dados['forma_pagamento'];
            $status = $dados['status'];
            $observacoes = $dados['observacoes'];
            
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            
            // Tratar corretamente o cliente_id (pode ser NULL)
            if ($cliente_id === null || $cliente_id === '' || $cliente_id === 'null') {
                $cliente_id = null;
                $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
            }
            
            $stmt->bindParam(':valor_total', $valor_total);
            $stmt->bindParam(':desconto', $desconto);
            $stmt->bindParam(':forma_pagamento', $forma_pagamento);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':observacoes', $observacoes);
            
            $stmt->execute();
            $venda_id = $this->pdo->lastInsertId();
            
            // Log para debug
            error_log("Venda inserida com ID: " . $venda_id);
            
            // Insere os itens da venda
            foreach ($dados['itens'] as $item) {
                // Verifica estoque
                if (!$this->produto->verificarEstoque($item['produto_id'], $item['quantidade'])) {
                    throw new Exception("Estoque insuficiente para o produto ID: " . $item['produto_id']);
                }
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO itens_venda 
                    (venda_id, produto_id, quantidade, preco_unitario, subtotal) 
                    VALUES 
                    (:venda_id, :produto_id, :quantidade, :preco_unitario, :subtotal)
                ");
                
                $produto_id = $item['produto_id'];
                $quantidade = $item['quantidade'];
                $preco_unitario = $item['preco_unitario'];
                $subtotal = $quantidade * $preco_unitario;
                
                $stmt->bindParam(':venda_id', $venda_id, PDO::PARAM_INT);
                $stmt->bindParam(':produto_id', $produto_id, PDO::PARAM_INT);
                $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
                $stmt->bindParam(':preco_unitario', $preco_unitario);
                $stmt->bindParam(':subtotal', $subtotal);
                
                $stmt->execute();
                
                // Registra movimentação de estoque
                $this->produto->registrarMovimentacao([
                    'produto_id' => $produto_id,
                    'tipo' => 'saida',
                    'quantidade' => $quantidade,
                    'observacao' => 'Venda #' . $venda_id,
                    'origem' => 'venda',
                    'documento_id' => $venda_id
                ]);
            }
            
            // Finaliza transação
            $this->pdo->commit();
            
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $cliente_nome = "não identificado";
                if (!empty($cliente_id)) {
                    $stmt = $this->pdo->prepare("SELECT nome FROM clientes WHERE id = :id LIMIT 1");
                    $stmt->bindParam(':id', $cliente_id);
                    $stmt->execute();
                    $cliente = $stmt->fetch();
                    if ($cliente) {
                        $cliente_nome = $cliente['nome'];
                    }
                }
                
                $GLOBALS['log']->registrar(
                    'Venda', 
                    "Nova venda #{$venda_id} registrada - Cliente: {$cliente_nome} - Valor: " . 
                    number_format($valor_total, 2, ',', '.')
                );
            }
            
            return $venda_id;
            
        } catch (Exception $e) {
            // Desfaz transação em caso de erro
            $this->pdo->rollBack();
            error_log("Erro ao adicionar venda: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Cancelar venda
    public function cancelar($id) {
        try {
            // Inicia transação
            $this->pdo->beginTransaction();
            
            // Busca itens da venda
            $itens = $this->buscarItens($id);
            
            // Atualiza status da venda
            $stmt = $this->pdo->prepare("UPDATE vendas SET status = 'cancelada' WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Estorna estoque dos produtos
            foreach ($itens as $item) {
                // Registra movimentação de estoque (entrada por cancelamento)
                $produto_id = $item['produto_id'];
                $quantidade = $item['quantidade'];
                $observacao = 'Cancelamento da Venda #' . $id;
                $origem = 'devolucao';
                $documento_id = $id;
                
                $this->produto->registrarMovimentacao([
                    'produto_id' => $produto_id,
                    'tipo' => 'entrada',
                    'quantidade' => $quantidade,
                    'observacao' => $observacao,
                    'origem' => $origem,
                    'documento_id' => $documento_id
                ]);
            }
            
            // Finaliza transação
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            // Desfaz transação em caso de erro
            $this->pdo->rollBack();
            error_log("Erro ao cancelar venda: " . $e->getMessage());
            return false;
        }
    }

    // Relatório de vendas por período
    public function relatorioVendasPorPeriodo($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                v.id, 
                DATE_FORMAT(v.data_venda, '%d/%m/%Y') AS data, 
                v.valor_total, 
                v.desconto,
                v.forma_pagamento,
                u.nome AS vendedor,
                c.nome AS cliente
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN clientes c ON v.cliente_id = c.id
            WHERE v.data_venda BETWEEN :data_inicio AND :data_fim
            AND v.status = 'finalizada'
            ORDER BY v.data_venda
        ");
        
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Relatório de vendas por vendedor
    public function relatorioVendasPorVendedor($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.nome AS vendedor,
                COUNT(v.id) AS total_vendas,
                SUM(v.valor_total) AS valor_total
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            WHERE v.data_venda BETWEEN :data_inicio AND :data_fim
            AND v.status = 'finalizada'
            GROUP BY v.usuario_id
            ORDER BY valor_total DESC
        ");
        
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Relatório de produtos mais vendidos
    public function relatorioProdutosMaisVendidos($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.codigo,
                p.nome AS produto,
                SUM(i.quantidade) AS quantidade_total,
                SUM(i.subtotal) AS valor_total
            FROM itens_venda i
            LEFT JOIN produtos p ON i.produto_id = p.id
            LEFT JOIN vendas v ON i.venda_id = v.id
            WHERE v.data_venda BETWEEN :data_inicio AND :data_fim
            AND v.status = 'finalizada'
            GROUP BY i.produto_id
            ORDER BY quantidade_total DESC
        ");
        
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// Classe para gerenciar categorias
class Categoria {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Listar todas as categorias
    public function listar() {
        $stmt = $this->pdo->query("SELECT * FROM categorias ORDER BY nome");
        return $stmt->fetchAll();
    }

    // Buscar categoria por ID
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Adicionar categoria
    public function adicionar($dados) {
        $stmt = $this->pdo->prepare("INSERT INTO categorias (nome, descricao) VALUES (:nome, :descricao)");
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        return $stmt->execute();
    }

    // Atualizar categoria
    public function atualizar($id, $dados) {
        $stmt = $this->pdo->prepare("UPDATE categorias SET nome = :nome, descricao = :descricao WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        return $stmt->execute();
    }

    // Excluir categoria
    public function excluir($id) {
        // Verifica se a categoria tem produtos
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produtos WHERE categoria_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return false; // Não pode excluir se tiver produtos
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

// Classe para gerenciar clientes
class Cliente {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Listar todos os clientes
    public function listar() {
        $stmt = $this->pdo->query("SELECT * FROM clientes ORDER BY nome");
        return $stmt->fetchAll();
    }

    // Buscar cliente por ID
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Buscar cliente por CPF/CNPJ
    public function buscarPorCpfCnpj($cpf_cnpj) {
        $stmt = $this->pdo->prepare("SELECT * FROM clientes WHERE cpf_cnpj = :cpf_cnpj LIMIT 1");
        $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Adicionar cliente
    public function adicionar($dados) {
        $stmt = $this->pdo->prepare("
            INSERT INTO clientes 
            (nome, cpf_cnpj, email, telefone, endereco, cidade, estado, cep, observacoes) 
            VALUES 
            (:nome, :cpf_cnpj, :email, :telefone, :endereco, :cidade, :estado, :cep, :observacoes)
        ");
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cpf_cnpj', $dados['cpf_cnpj']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':cidade', $dados['cidade']);
        $stmt->bindParam(':estado', $dados['estado']);
        $stmt->bindParam(':cep', $dados['cep']);
        $stmt->bindParam(':observacoes', $dados['observacoes']);
        
        return $stmt->execute();
    }

    // Atualizar cliente
    public function atualizar($id, $dados) {
        $stmt = $this->pdo->prepare("
            UPDATE clientes SET 
            nome = :nome, 
            cpf_cnpj = :cpf_cnpj, 
            email = :email, 
            telefone = :telefone, 
            endereco = :endereco, 
            cidade = :cidade, 
            estado = :estado, 
            cep = :cep, 
            observacoes = :observacoes 
            WHERE id = :id
        ");
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cpf_cnpj', $dados['cpf_cnpj']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':cidade', $dados['cidade']);
        $stmt->bindParam(':estado', $dados['estado']);
        $stmt->bindParam(':cep', $dados['cep']);
        $stmt->bindParam(':observacoes', $dados['observacoes']);
        
        return $stmt->execute();
    }

    // Excluir cliente
    public function excluir($id) {
        // Verificar se o cliente tem vendas
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM vendas WHERE cliente_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return false; // Não pode excluir se tiver vendas
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM clientes WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
// Classe para gerenciar fornecedores
class Fornecedor {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Listar todos os fornecedores
    public function listar() {
        $stmt = $this->pdo->query("SELECT * FROM fornecedores ORDER BY nome");
        return $stmt->fetchAll();
    }

    // Buscar fornecedor por ID
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM fornecedores WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Adicionar fornecedor
    public function adicionar($dados) {
        $stmt = $this->pdo->prepare("
            INSERT INTO fornecedores 
            (nome, cpf_cnpj, email, telefone, endereco, cidade, estado, cep, observacoes) 
            VALUES 
            (:nome, :cpf_cnpj, :email, :telefone, :endereco, :cidade, :estado, :cep, :observacoes)
        ");
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cpf_cnpj', $dados['cpf_cnpj']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':cidade', $dados['cidade']);
        $stmt->bindParam(':estado', $dados['estado']);
        $stmt->bindParam(':cep', $dados['cep']);
        $stmt->bindParam(':observacoes', $dados['observacoes']);
        
        return $stmt->execute();
    }

    // Atualizar fornecedor
    public function atualizar($id, $dados) {
        $stmt = $this->pdo->prepare("
            UPDATE fornecedores SET 
            nome = :nome, 
            cpf_cnpj = :cpf_cnpj, 
            email = :email, 
            telefone = :telefone, 
            endereco = :endereco, 
            cidade = :cidade, 
            estado = :estado, 
            cep = :cep, 
            observacoes = :observacoes 
            WHERE id = :id
        ");
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cpf_cnpj', $dados['cpf_cnpj']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':cidade', $dados['cidade']);
        $stmt->bindParam(':estado', $dados['estado']);
        $stmt->bindParam(':cep', $dados['cep']);
        $stmt->bindParam(':observacoes', $dados['observacoes']);
        
        return $stmt->execute();
    }

    // Excluir fornecedor
    public function excluir($id) {
        // Verificar se o fornecedor tem compras
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM compras WHERE fornecedor_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return false; // Não pode excluir se tiver compras
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM fornecedores WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

// Classe para gerenciar vendas
class Venda {
    private $pdo;
    private $produto;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->produto = new Produto($pdo);
    }

    // Listar todas as vendas
    public function listar() {
        $stmt = $this->pdo->query("
            SELECT v.*, u.nome AS usuario_nome, c.nome AS cliente_nome, 
            DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN clientes c ON v.cliente_id = c.id
            ORDER BY v.data_venda DESC
        ");
        return $stmt->fetchAll();
    }

    // Buscar venda por ID
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("
            SELECT v.*, u.nome AS usuario_nome, c.nome AS cliente_nome,
            DATE_FORMAT(v.data_venda, '%d/%m/%Y %H:%i') AS data_formatada
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN clientes c ON v.cliente_id = c.id
            WHERE v.id = :id LIMIT 1
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Buscar itens de uma venda
    public function buscarItens($venda_id) {
        $stmt = $this->pdo->prepare("
            SELECT i.*, p.nome AS produto_nome, p.codigo AS produto_codigo
            FROM itens_venda i
            LEFT JOIN produtos p ON i.produto_id = p.id
            WHERE i.venda_id = :venda_id
            ORDER BY i.id
        ");
        $stmt->bindParam(':venda_id', $venda_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    // Adicionar venda
    public function adicionar($dados) {
        try {
            // Inicia transação
            $this->pdo->beginTransaction();
            
            // Insere a venda
            $stmt = $this->pdo->prepare("
                INSERT INTO vendas 
                (usuario_id, cliente_id, valor_total, desconto, forma_pagamento, status, observacoes) 
                VALUES 
                (:usuario_id, :cliente_id, :valor_total, :desconto, :forma_pagamento, :status, :observacoes)
            ");
            
            $stmt->bindParam(':usuario_id', $_SESSION['usuario_id'], PDO::PARAM_INT);
            $stmt->bindParam(':cliente_id', $dados['cliente_id'], PDO::PARAM_INT);
            $stmt->bindParam(':valor_total', $dados['valor_total']);
            $stmt->bindParam(':desconto', $dados['desconto']);
            $stmt->bindParam(':forma_pagamento', $dados['forma_pagamento']);
            $stmt->bindParam(':status', $dados['status']);
            $stmt->bindParam(':observacoes', $dados['observacoes']);
            
            $stmt->execute();
            $venda_id = $this->pdo->lastInsertId();
            
            // Insere os itens da venda
            foreach ($dados['itens'] as $item) {
                // Verifica estoque
                if (!$this->produto->verificarEstoque($item['produto_id'], $item['quantidade'])) {
                    throw new Exception("Estoque insuficiente para o produto ID: " . $item['produto_id']);
                }
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO itens_venda 
                    (venda_id, produto_id, quantidade, preco_unitario, subtotal) 
                    VALUES 
                    (:venda_id, :produto_id, :quantidade, :preco_unitario, :subtotal)
                ");
                
                $subtotal = $item['quantidade'] * $item['preco_unitario'];
                
                $stmt->bindParam(':venda_id', $venda_id, PDO::PARAM_INT);
                $stmt->bindParam(':produto_id', $item['produto_id'], PDO::PARAM_INT);
                $stmt->bindParam(':quantidade', $item['quantidade'], PDO::PARAM_INT);
                $stmt->bindParam(':preco_unitario', $item['preco_unitario']);
                $stmt->bindParam(':subtotal', $subtotal);
                
                $stmt->execute();
                
                // Registra movimentação de estoque
                $this->produto->registrarMovimentacao([
                    'produto_id' => $item['produto_id'],
                    'tipo' => 'saida',
                    'quantidade' => $item['quantidade'],
                    'observacao' => 'Venda #' . $venda_id,
                    'origem' => 'venda',
                    'documento_id' => $venda_id
                ]);
            }
            
            // Finaliza transação
            $this->pdo->commit();
            
            // Registrar no log do sistema
            if (isset($GLOBALS['log'])) {
                $cliente_nome = "não identificado";
                if (!empty($dados['cliente_id'])) {
                    $stmt = $this->pdo->prepare("SELECT nome FROM clientes WHERE id = :id LIMIT 1");
                    $stmt->bindParam(':id', $dados['cliente_id']);
                    $stmt->execute();
                    $cliente = $stmt->fetch();
                    if ($cliente) {
                        $cliente_nome = $cliente['nome'];
                    }
                }
                
                $GLOBALS['log']->registrar(
                    'Venda', 
                    "Nova venda #{$venda_id} registrada - Cliente: {$cliente_nome} - Valor: " . 
                    number_format($dados['valor_total'], 2, ',', '.')
                );
            }
            
            return $venda_id;
            
        } catch (Exception $e) {
            // Desfaz transação em caso de erro
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    // Cancelar venda
    public function cancelar($id) {
        try {
            // Inicia transação
            $this->pdo->beginTransaction();
            
            // Busca itens da venda
            $itens = $this->buscarItens($id);
            
            // Atualiza status da venda
            $stmt = $this->pdo->prepare("UPDATE vendas SET status = 'cancelada' WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Estorna estoque dos produtos
            foreach ($itens as $item) {
                // Registra movimentação de estoque (entrada por cancelamento)
                $this->produto->registrarMovimentacao([
                    'produto_id' => $item['produto_id'],
                    'tipo' => 'entrada',
                    'quantidade' => $item['quantidade'],
                    'observacao' => 'Cancelamento da Venda #' . $id,
                    'origem' => 'devolucao',
                    'documento_id' => $id
                ]);
            }
            
            // Finaliza transação
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            // Desfaz transação em caso de erro
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    // Relatório de vendas por período
    public function relatorioVendasPorPeriodo($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                v.id, 
                DATE_FORMAT(v.data_venda, '%d/%m/%Y') AS data, 
                v.valor_total, 
                v.desconto,
                v.forma_pagamento,
                u.nome AS vendedor,
                c.nome AS cliente
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN clientes c ON v.cliente_id = c.id
            WHERE v.data_venda BETWEEN :data_inicio AND :data_fim
            AND v.status = 'finalizada'
            ORDER BY v.data_venda
        ");
        
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Relatório de vendas por vendedor
    public function relatorioVendasPorVendedor($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.nome AS vendedor,
                COUNT(v.id) AS total_vendas,
                SUM(v.valor_total) AS valor_total
            FROM vendas v
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            WHERE v.data_venda BETWEEN :data_inicio AND :data_fim
            AND v.status = 'finalizada'
            GROUP BY v.usuario_id
            ORDER BY valor_total DESC
        ");
        
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Relatório de produtos mais vendidos
    public function relatorioProdutosMaisVendidos($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.codigo,
                p.nome AS produto,
                SUM(i.quantidade) AS quantidade_total,
                SUM(i.subtotal) AS valor_total
            FROM itens_venda i
            LEFT JOIN produtos p ON i.produto_id = p.id
            LEFT JOIN vendas v ON i.venda_id = v.id
            WHERE v.data_venda BETWEEN :data_inicio AND :data_fim
            AND v.status = 'finalizada'
            GROUP BY i.produto_id
            ORDER BY quantidade_total DESC
        ");
        
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
// Classe para gerenciar compras (entrada de produtos)
class Compra {
    private $pdo;
    private $produto;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->produto = new Produto($pdo);
    }

    // Listar todas as compras
    public function listar() {
        $stmt = $this->pdo->query("
            SELECT c.*, f.nome AS fornecedor_nome, u.nome AS usuario_nome, 
            DATE_FORMAT(c.data_compra, '%d/%m/%Y') AS data_formatada
            FROM compras c
            LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
            LEFT JOIN usuarios u ON c.usuario_id = u.id
            ORDER BY c.data_compra DESC
        ");
        return $stmt->fetchAll();
    }

    // Buscar compra por ID
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, f.nome AS fornecedor_nome, u.nome AS usuario_nome,
            DATE_FORMAT(c.data_compra, '%d/%m/%Y') AS data_formatada
            FROM compras c
            LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
            LEFT JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.id = :id LIMIT 1
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Buscar itens de uma compra
    public function buscarItens($compra_id) {
        $stmt = $this->pdo->prepare("
            SELECT i.*, p.nome AS produto_nome, p.codigo AS produto_codigo
            FROM itens_compra i
            LEFT JOIN produtos p ON i.produto_id = p.id
            WHERE i.compra_id = :compra_id
            ORDER BY i.id
        ");
        $stmt->bindParam(':compra_id', $compra_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Adicionar compra
    public function adicionar($dados) {
        try {
            // Inicia transação
            $this->pdo->beginTransaction();
            
            // Insere a compra
            $stmt = $this->pdo->prepare("
                INSERT INTO compras 
                (fornecedor_id, usuario_id, valor_total, status, observacoes) 
                VALUES 
                (:fornecedor_id, :usuario_id, :valor_total, :status, :observacoes)
            ");
            
            $stmt->bindParam(':fornecedor_id', $dados['fornecedor_id'], PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $_SESSION['usuario_id'], PDO::PARAM_INT);
            $stmt->bindParam(':valor_total', $dados['valor_total']);
            $stmt->bindParam(':status', $dados['status']);
            $stmt->bindParam(':observacoes', $dados['observacoes']);
            
            $stmt->execute();
            $compra_id = $this->pdo->lastInsertId();
            
            // Insere os itens da compra
            foreach ($dados['itens'] as $item) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO itens_compra 
                    (compra_id, produto_id, quantidade, preco_unitario, subtotal) 
                    VALUES 
                    (:compra_id, :produto_id, :quantidade, :preco_unitario, :subtotal)
                ");
                
                $subtotal = $item['quantidade'] * $item['preco_unitario'];
                
                $stmt->bindParam(':compra_id', $compra_id, PDO::PARAM_INT);
                $stmt->bindParam(':produto_id', $item['produto_id'], PDO::PARAM_INT);
                $stmt->bindParam(':quantidade', $item['quantidade'], PDO::PARAM_INT);
                $stmt->bindParam(':preco_unitario', $item['preco_unitario']);
                $stmt->bindParam(':subtotal', $subtotal);
                
                $stmt->execute();
                
                // Atualiza preço de custo do produto
                $stmt = $this->pdo->prepare("UPDATE produtos SET preco_custo = :preco_custo WHERE id = :id");
                $stmt->bindParam(':id', $item['produto_id'], PDO::PARAM_INT);
                $stmt->bindParam(':preco_custo', $item['preco_unitario']);
                $stmt->execute();
                
                // Registra movimentação de estoque
                if ($dados['status'] == 'finalizada') {
                    $this->produto->registrarMovimentacao([
                        'produto_id' => $item['produto_id'],
                        'tipo' => 'entrada',
                        'quantidade' => $item['quantidade'],
                        'observacao' => 'Compra #' . $compra_id,
                        'origem' => 'compra',
                        'documento_id' => $compra_id
                    ]);
                }
            }
            
            // Finaliza transação
            $this->pdo->commit();
            return $compra_id;
            
        } catch (Exception $e) {
            // Desfaz transação em caso de erro
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    // Finalizar compra pendente
    public function finalizar($id) {
        try {
            // Inicia transação
            $this->pdo->beginTransaction();
            
            // Busca compra
            $compra = $this->buscarPorId($id);
            if ($compra['status'] != 'pendente') {
                throw new Exception("Esta compra não está pendente.");
            }
            
            // Atualiza status da compra
            $stmt = $this->pdo->prepare("UPDATE compras SET status = 'finalizada' WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Busca itens da compra
            $itens = $this->buscarItens($id);
            
            // Adiciona estoque
            foreach ($itens as $item) {
                $this->produto->registrarMovimentacao([
                    'produto_id' => $item['produto_id'],
                    'tipo' => 'entrada',
                    'quantidade' => $item['quantidade'],
                    'observacao' => 'Finalização da Compra #' . $id,
                    'origem' => 'compra',
                    'documento_id' => $id
                ]);
            }
            
            // Finaliza transação
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            // Desfaz transação em caso de erro
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    // Cancelar compra
    public function cancelar($id) {
        try {
            // Inicia transação
            $this->pdo->beginTransaction();
            
            // Busca compra
            $compra = $this->buscarPorId($id);
            if ($compra['status'] == 'cancelada') {
                throw new Exception("Esta compra já está cancelada.");
            }
            
            // Atualiza status da compra
            $stmt = $this->pdo->prepare("UPDATE compras SET status = 'cancelada' WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Se a compra estava finalizada, estorna o estoque
            if ($compra['status'] == 'finalizada') {
                // Busca itens da compra
                $itens = $this->buscarItens($id);
                
                // Remove estoque
                foreach ($itens as $item) {
                    $this->produto->registrarMovimentacao([
                        'produto_id' => $item['produto_id'],
                        'tipo' => 'saida',
                        'quantidade' => $item['quantidade'],
                        'observacao' => 'Cancelamento da Compra #' . $id,
                        'origem' => 'ajuste_manual',
                        'documento_id' => $id
                    ]);
                }
            }
            
            // Finaliza transação
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            // Desfaz transação em caso de erro
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }
}
// Classe para gerenciar relatórios
class Relatorio {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Relatório de estoque atual
    public function estoqueAtual() {
        $stmt = $this->pdo->query("
            SELECT 
                p.id,
                p.codigo,
                p.nome,
                c.nome AS categoria,
                p.estoque_atual,
                p.estoque_minimo,
                p.preco_custo,
                p.preco_venda,
                (p.preco_venda - p.preco_custo) AS lucro,
                ((p.preco_venda - p.preco_custo) / p.preco_custo * 100) AS margem_lucro,
                (p.estoque_atual * p.preco_custo) AS valor_estoque
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.ativo = TRUE
            ORDER BY p.nome
        ");
        return $stmt->fetchAll();
    }

    // Relatório de produtos abaixo do estoque mínimo
    public function produtosAbaixoEstoqueMinimo() {
        $stmt = $this->pdo->query("
            SELECT 
                p.id,
                p.codigo,
                p.nome,
                c.nome AS categoria,
                p.estoque_atual,
                p.estoque_minimo,
                (p.estoque_minimo - p.estoque_atual) AS quantidade_comprar
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.ativo = TRUE AND p.estoque_atual < p.estoque_minimo
            ORDER BY quantidade_comprar DESC
        ");
        return $stmt->fetchAll();
    }

    // Relatório de faturamento diário
    public function faturamentoDiario($mes, $ano) {
        $stmt = $this->pdo->prepare("
            SELECT 
                DAY(data_venda) AS dia,
                COUNT(id) AS total_vendas,
                SUM(valor_total) AS valor_total
            FROM vendas
            WHERE MONTH(data_venda) = :mes AND YEAR(data_venda) = :ano
            AND status = 'finalizada'
            GROUP BY DAY(data_venda)
            ORDER BY dia
        ");
        
        $stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Relatório de faturamento mensal
    public function faturamentoMensal($ano) {
        $stmt = $this->pdo->prepare("
            SELECT 
                MONTH(data_venda) AS mes,
                COUNT(id) AS total_vendas,
                SUM(valor_total) AS valor_total
            FROM vendas
            WHERE YEAR(data_venda) = :ano
            AND status = 'finalizada'
            GROUP BY MONTH(data_venda)
            ORDER BY mes
        ");
        
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Relatório de produtos mais vendidos
    public function produtosMaisVendidos($data_inicio, $data_fim, $limite = 10) {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.codigo,
                p.nome,
                SUM(i.quantidade) AS quantidade_total,
                SUM(i.subtotal) AS valor_total
            FROM itens_venda i
            LEFT JOIN produtos p ON i.produto_id = p.id
            LEFT JOIN vendas v ON i.venda_id = v.id
            WHERE v.data_venda BETWEEN :data_inicio AND :data_fim
            AND v.status = 'finalizada'
            GROUP BY i.produto_id
            ORDER BY quantidade_total DESC
            LIMIT :limite
        ");
        
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Relatório de movimentações de estoque
    public function movimentacoesEstoque($produto_id = null, $data_inicio = null, $data_fim = null) {
        $sql = "
            SELECT 
                m.id,
                p.codigo AS produto_codigo,
                p.nome AS produto_nome,
                u.nome AS usuario_nome,
                m.tipo,
                m.quantidade,
                m.origem,
                DATE_FORMAT(m.data_movimentacao, '%d/%m/%Y %H:%i') AS data_formatada,
                m.observacao
            FROM movimentacoes_estoque m
            LEFT JOIN produtos p ON m.produto_id = p.id
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($produto_id) {
            $sql .= " AND m.produto_id = :produto_id";
            $params[':produto_id'] = $produto_id;
        }
        
        if ($data_inicio) {
            $sql .= " AND m.data_movimentacao >= :data_inicio";
            $params[':data_inicio'] = $data_inicio;
        }
        
        if ($data_fim) {
            $sql .= " AND m.data_movimentacao <= :data_fim";
            $params[':data_fim'] = $data_fim;
        }
        
        $sql .= " ORDER BY m.data_movimentacao DESC";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Relatório de lucratividade
    public function lucratividade($data_inicio, $data_fim) {
        $stmt = $this->pdo->prepare("
            SELECT 
                v.id AS venda_id,
                DATE_FORMAT(v.data_venda, '%d/%m/%Y') AS data,
                v.valor_total AS receita,
                (
                    SELECT SUM(iv.quantidade * p.preco_custo)
                    FROM itens_venda iv
                    LEFT JOIN produtos p ON iv.produto_id = p.id
                    WHERE iv.venda_id = v.id
                ) AS custo,
                (
                    v.valor_total - (
                        SELECT SUM(iv.quantidade * p.preco_custo)
                        FROM itens_venda iv
                        LEFT JOIN produtos p ON iv.produto_id = p.id
                        WHERE iv.venda_id = v.id
                    )
                ) AS lucro,
                (
                    (v.valor_total - (
                        SELECT SUM(iv.quantidade * p.preco_custo)
                        FROM itens_venda iv
                        LEFT JOIN produtos p ON iv.produto_id = p.id
                        WHERE iv.venda_id = v.id
                    )) / v.valor_total * 100
                ) AS margem_lucro
            FROM vendas v
            WHERE v.data_venda BETWEEN :data_inicio AND :data_fim
            AND v.status = 'finalizada'
            ORDER BY v.data_venda
        ");
        
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// Classe para gerenciar logs do sistema
class LogSistema {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Registrar log
    public function registrar($acao, $detalhes = '') {
        // Verificar se o usuário está logado
        if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['usuario_id_temp'])) {
            // Se nenhum usuário estiver logado e não for um login temporário, não registra
            return false;
        }

        // Usar ID e nome de usuário da sessão ou temporários
        $usuario_id = $_SESSION['usuario_id_temp'] ?? $_SESSION['usuario_id'] ?? 0;
        $usuario_nome = $_SESSION['usuario_nome_temp'] ?? $_SESSION['usuario_nome'] ?? 'Sistema';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $stmt = $this->pdo->prepare("
            INSERT INTO logs_sistema 
            (usuario_id, usuario_nome, acao, detalhes, ip) 
            VALUES 
            (:usuario_id, :usuario_nome, :acao, :detalhes, :ip)
        ");
        
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':usuario_nome', $usuario_nome);
        $stmt->bindParam(':acao', $acao);
        $stmt->bindParam(':detalhes', $detalhes);
        $stmt->bindParam(':ip', $ip);
        
        return $stmt->execute();
    }

    // Listar logs
    public function listar($limite = 100, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                usuario_nome,
                acao,
                detalhes,
                ip,
                DATE_FORMAT(data_hora, '%d/%m/%Y %H:%i:%s') AS data_formatada
            FROM logs_sistema
            ORDER BY data_hora DESC
            LIMIT :limite OFFSET :offset
        ");
        
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Excluir logs antigos
    public function limparAntigos($dias = 30) {
        // Calcula a data limite (hoje - dias)
        $data_limite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
        
        $stmt = $this->pdo->prepare("DELETE FROM logs_sistema WHERE data_hora < :data_limite");
        $stmt->bindParam(':data_limite', $data_limite);
        
        return $stmt->execute();
    }
    // Limpar todos os logs
    public function limparTodos() {
        try {
        $stmt = $this->pdo->prepare("DELETE FROM logs_sistema");
        $resultado = $stmt->execute();
        
        return $resultado;
    } catch (Exception $e) {
        if (function_exists('error_log')) {
            error_log("Erro ao limpar todos os logs: " . $e->getMessage());
        }
        return false;
    }
}
    
    // Contar total de logs
    public function contarTotal() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM logs_sistema");
        return $stmt->fetchColumn();
    }
}