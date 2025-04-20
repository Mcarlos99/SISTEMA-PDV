</div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white py-3 mt-auto">
    <div class="container-fluid text-center">
        <div class="d-flex justify-content-between align-items-center flex-column flex-md-row">
            <div class="mb-2 mb-md-0">
                <small>Mauro Carlos - <i class="fas fa-phone-alt me-1"></i>94 98170-9809</small>
            </div>
            <div>
                <small>Sistema PDV v1.0 | &copy; <?php echo date('Y'); ?></small>
            </div>
        </div>
    </div>
</footer>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>

<!-- Script para inicializar DataTables -->
<script>
    $(document).ready(function() {
        // Inicializar DataTables com responsividade
        //$('.datatable').DataTable({
          $('.datatable').not('#tabelaProdutos,#tabelaUsuarios,#tabela-produtos-vendidos,#tabelaHistorico,#tabelaComandas,#tabelaProdutosComanda,#tabelaMovimentacoes,#tabela-busca-produtos,#tabelaCategorias,#tabelaEstoqueBaixo,#tabelaProdutos,#tabelaMovimentacoes,#tabela-estoque-atual,#tabela-lucratividade').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            },
            "pageLength": 25,
            "responsive": true,
            "stateSave": true,
            "autoWidth": false // Impede que a tabela force larguras específicas
        });
        
        // Sidebar toggle para mobile
        $('.sidebar-toggle').on('click', function() {
            $('body').toggleClass('sidebar-open');
        });
        
        // Sidebar collapse para desktop
        $('#sidebarCollapseBtn').on('click', function() {
            $('body').toggleClass('sidebar-collapsed');
            
            // Importante: força o redimensionamento das tabelas após o colapso
            setTimeout(function() {
                $($.fn.dataTable.tables(true)).DataTable().responsive.recalc();
            }, 300);
        });
        
        // Fechar alertas automaticamente após 5 segundos
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Corrige problemas de largura em inputs e campos de formulário
        $('input, select, textarea').on('focus', function() {
            $(this).closest('.table-responsive').css('overflow-x', 'visible');
        }).on('blur', function() {
            $(this).closest('.table-responsive').css('overflow-x', 'auto');
        });
        
        // Ajustar tamanhos de tabelas em abas quando elas tornam-se visíveis
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $($.fn.dataTable.tables(true)).DataTable().responsive.recalc();
        });
        
        // Fix para elementos demasiado largos
        $('*').css('max-width', '100%');
        
        // Para dispositivos móveis, mostra um botão para fechar o menu
        if (window.innerWidth < 992) {
            $('.sidebar').append('<button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 d-lg-none" id="closeSidebar"><i class="fas fa-times"></i></button>');
            
            $('#closeSidebar').on('click', function() {
                $('body').removeClass('sidebar-open');
            });
            
            // Fecha sidebar quando clica em um link (em mobile)
            $('.sidebar .nav-link').on('click', function() {
                $('body').removeClass('sidebar-open');
            });
        }
    });
    
    // Ajuste responsivo em redimensionamento de tela
    $(window).resize(function() {
        if (window.innerWidth < 992) {
            $('body').removeClass('sidebar-collapsed');
        }
        
        // Atualiza tabelas no redimensionamento
        $($.fn.dataTable.tables(true)).DataTable().responsive.recalc();
    });
</script>

</body>
</html>