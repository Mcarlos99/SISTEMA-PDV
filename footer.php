</div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-3 mt-auto">
        <div class="container text-center">
            <small>Mauro Carlos - |94 98170-9809| - Sistema PDV v1.0 | &copy; <?php echo date('Y'); ?></small>
        </div>
    </footer>


    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Script para inicializar DataTables -->
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
                },
                "pageLength": 25
            });
        });
    </script>
</body>
</html>
