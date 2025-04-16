// Script para testar comunicação AJAX simples
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar botão de teste ao final da página
    const btnContainer = document.createElement('div');
    btnContainer.style.position = 'fixed';
    btnContainer.style.bottom = '20px';
    btnContainer.style.right = '20px';
    btnContainer.style.zIndex = '9999';
    
    const testButton = document.createElement('button');
    testButton.className = 'btn btn-warning';
    testButton.innerHTML = 'Testar AJAX';
    testButton.onclick = testarAjax;
    
    btnContainer.appendChild(testButton);
    document.body.appendChild(btnContainer);
    
    // Função para testar AJAX
    function testarAjax() {
        console.log('Iniciando teste AJAX...');
        
        // Desativar cache
        $.ajaxSetup({
            cache: false
        });
        
        // Caminho para o arquivo de teste
        const ajaxUrl = 'teste_ajax.php';
        console.log('URL do teste:', ajaxUrl);
        
        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Teste AJAX bem-sucedido!', response);
                alert('Teste AJAX bem-sucedido! Veja o console para detalhes.');
            },
            error: function(xhr, status, error) {
                console.error('Erro no teste AJAX:', status, error);
                console.log('Resposta completa:', xhr.responseText);
                alert('Erro no teste AJAX. Verifique o console para detalhes.');
            }
        });
    }
});