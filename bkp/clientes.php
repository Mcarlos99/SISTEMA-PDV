<?php
require_once 'config.php';
verificarLogin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Adicionar cliente
    if (isset($_POST['adicionar'])) {
        $dados = [
            'nome' => $_POST['nome'],
            'cpf_cnpj' => $_POST['cpf_cnpj'],
            'email' => $_POST['email'],
            'telefone' => $_POST['telefone'],
            'endereco' => $_POST['endereco'],
            'cidade' => $_POST['cidade'],
            'estado' => $_POST['estado'],
            'cep' => $_POST['cep'],
            'observacoes' => $_POST['observacoes']
        ];
        
        // Verificar se já existe cliente com este CPF/CNPJ
        if (!empty($dados['cpf_cnpj']) && $cliente->buscarPorCpfCnpj($dados['cpf_cnpj'])) {
            alerta('Já existe um cliente cadastrado com este CPF/CNPJ!', 'danger');
        } else {
            if ($cliente->adicionar($dados)) {
                alerta('Cliente adicionado com sucesso!', 'success');
            } else {
                alerta('Erro ao adicionar cliente!', 'danger');
            }
        }
    }
    
    // Atualizar cliente
    if (isset($_POST['atualizar'])) {
        $id = $_POST['id'];
        $dados = [
            'nome' => $_POST['nome'],
            'cpf_cnpj' => $_POST['cpf_cnpj'],
            'email' => $_POST['email'],
            'telefone' => $_POST['telefone'],
            'endereco' => $_POST['endereco'],
            'cidade' => $_POST['cidade'],
            'estado' => $_POST['estado'],
            'cep' => $_POST['cep'],
            'observacoes' => $_POST['observacoes']
        ];
        
        // Verificar se já existe outro cliente com este CPF/CNPJ
        $cliente_existente = $cliente->buscarPorCpfCnpj($dados['cpf_cnpj']);
        if (!empty($dados['cpf_cnpj']) && $cliente_existente && $cliente_existente['id'] != $id) {
            alerta('Já existe outro cliente cadastrado com este CPF/CNPJ!', 'danger');
        } else {
            if ($cliente->atualizar($id, $dados)) {
                alerta('Cliente atualizado com sucesso!', 'success');
            } else {
                alerta('Erro ao atualizar cliente!', 'danger');
            }
        }
    }
    
    // Excluir cliente
    if (isset($_POST['excluir'])) {
        $id = $_POST['id'];
        
        if ($cliente->excluir($id)) {
            alerta('Cliente excluído com sucesso!', 'success');
        } else {
            alerta('Não é possível excluir este cliente pois existem vendas vinculadas a ele!', 'danger');
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: clientes.php');
    exit;
}

// Buscar cliente para edição
$cliente_edicao = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $cliente_edicao = $cliente->buscarPorId($id);
}

// Template da página
$titulo_pagina = 'Gerenciamento de Clientes';
include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Clientes</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-cliente">
            <i class="fas fa-plus"></i> Novo Cliente
        </button>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF/CNPJ</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Cidade/UF</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $clientes = $cliente->listar();
                        foreach ($clientes as $c) {
                            echo '<tr>';
                            echo '<td>'.$c['nome'].'</td>';
                            echo '<td>'.$c['cpf_cnpj'].'</td>';
                            echo '<td>'.$c['telefone'].'</td>';
                            echo '<td>'.$c['email'].'</td>';
                            echo '<td>'.($c['cidade'] ? $c['cidade'].'/'.$c['estado'] : '-').'</td>';
                            echo '<td>
                                    <a href="?editar='.$c['id'].'" class="btn btn-sm btn-primary me-1"><i class="fas fa-edit"></i></a>
                                    <a href="#" class="btn btn-sm btn-danger btn-excluir" data-id="'.$c['id'].'" data-nome="'.$c['nome'].'"><i class="fas fa-trash"></i></a>
                                  </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cliente -->
<div class="modal fade" id="modal-cliente" tabindex="-1" aria-labelledby="modal-cliente-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-cliente-label"><?php echo $cliente_edicao ? 'Editar Cliente' : 'Novo Cliente'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-cliente" method="post" action="">
                    <?php if ($cliente_edicao): ?>
                        <input type="hidden" name="id" value="<?php echo $cliente_edicao['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo $cliente_edicao ? $cliente_edicao['nome'] : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="cpf_cnpj" class="form-label">CPF/CNPJ</label>
                            <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo $cliente_edicao ? $cliente_edicao['cpf_cnpj'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $cliente_edicao ? $cliente_edicao['email'] : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo $cliente_edicao ? $cliente_edicao['telefone'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo $cliente_edicao ? $cliente_edicao['endereco'] : ''; ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo $cliente_edicao ? $cliente_edicao['cidade'] : ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Selecione</option>
                                <?php
                                $estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                                foreach ($estados as $uf) {
                                    $selected = ($cliente_edicao && $cliente_edicao['estado'] == $uf) ? 'selected' : '';
                                    echo '<option value="'.$uf.'" '.$selected.'>'.$uf.'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="cep" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="cep" name="cep" value="<?php echo $cliente_edicao ? $cliente_edicao['cep'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo $cliente_edicao ? $cliente_edicao['observacoes'] : ''; ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-cliente" class="btn btn-primary" name="<?php echo $cliente_edicao ? 'atualizar' : 'adicionar'; ?>">
                    <?php echo $cliente_edicao ? 'Atualizar' : 'Adicionar'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modal-excluir" tabindex="-1" aria-labelledby="modal-excluir-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-excluir-label">Excluir Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o cliente <strong id="nome-cliente-excluir"></strong>?</p>
                <p class="text-danger">Esta ação não poderá ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <form method="post" action="">
                    <input type="hidden" name="id" id="id-cliente-excluir">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="excluir" class="btn btn-danger">Confirmar Exclusão</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Abrir modal de edição automaticamente se tiver cliente para editar
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($cliente_edicao): ?>
        var modalCliente = new bootstrap.Modal(document.getElementById('modal-cliente'));
        modalCliente.show();
        <?php endif; ?>
        
        // Configurar modal de exclusão
        var botoesExcluir = document.getElementsByClassName('btn-excluir');
        for (var i = 0; i < botoesExcluir.length; i++) {
            botoesExcluir[i].addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                var nome = this.getAttribute('data-nome');
                document.getElementById('id-cliente-excluir').value = id;
                document.getElementById('nome-cliente-excluir').textContent = nome;
                var modalExcluir = new bootstrap.Modal(document.getElementById('modal-excluir'));
                modalExcluir.show();
            });
        }
        
        // Máscara para CPF/CNPJ
        document.getElementById('cpf_cnpj').addEventListener('input', function(e) {
            var x = e.target.value.replace(/\D/g, '');
            if (x.length <= 11) {
                // CPF
                x = x.replace(/(\d{3})(\d)/, '$1.$2');
                x = x.replace(/(\d{3})(\d)/, '$1.$2');
                x = x.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                // CNPJ
                x = x.replace(/^(\d{2})(\d)/, '$1.$2');
                x = x.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                x = x.replace(/\.(\d{3})(\d)/, '.$1/$2');
                x = x.replace(/(\d{4})(\d)/, '$1-$2');
            }
            e.target.value = x;
        });
        
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            var x = e.target.value.replace(/\D/g, '');
            if (x.length <= 10) {
                // Telefone fixo
                x = x.replace(/(\d{2})(\d)/, '($1) $2');
                x = x.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                // Celular
                x = x.replace(/(\d{2})(\d)/, '($1) $2');
                x = x.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = x;
        });
        
        // Máscara para CEP
        document.getElementById('cep').addEventListener('input', function(e) {
            var x = e.target.value.replace(/\D/g, '');
            x = x.replace(/^(\d{5})(\d)/, '$1-$2');
            e.target.value = x;
        });
    });
</script>

<?php include 'footer.php'; ?>
