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
            impressao_automatica = :impressao_automatica
            WHERE id = :id
        ");
        
        // Converter checkbox para booleano
        $estoque_negativo = isset($dados['estoque_negativo']) ? 1 : 0;
        $alerta_estoque = isset($dados['alerta_estoque']) ? 1 : 0;
        $impressao_automatica = isset($dados['impressao_automatica']) ? 1 : 0;
        
        $stmt->bindParam(':id', $dados['id']);
        $stmt->bindParam(':itens_por_pagina', $dados['itens_por_pagina']);
        $stmt->bindParam(':tema', $dados['tema']);
        $stmt->bindParam(':moeda', $dados['moeda']);
        $stmt->bindParam(':formato_data', $dados['formato_data']);
        $stmt->bindParam(':estoque_negativo', $estoque_negativo);
        $stmt->bindParam(':alerta_estoque', $alerta_estoque);
        $stmt->bindParam(':impressao_automatica', $impressao_automatica);
        
        $result = $stmt->execute();
        
        // Registrar no log do sistema
        if ($result && isset($GLOBALS['log'])) {
            $GLOBALS['log']->registrar('Configuração', "Configurações do sistema atualizadas");
        }
        
        return $result;
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
        $stmt->bindParam(':documento_id', $dados['documento_id'] ?? null, PDO::PARAM_INT);
        
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