    </div> <!-- end container-fluid -->
</div> <!-- end main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
    // Clone desktop sidebar into offcanvas for mobile
    document.addEventListener('DOMContentLoaded', function() {
        const desktopSidebar = document.querySelector('.sidebar');
        const offcanvasBody = document.querySelector('#sidebarOffcanvas .offcanvas-body');
        if (desktopSidebar && offcanvasBody) {
            offcanvasBody.innerHTML = desktopSidebar.innerHTML;
        }
    });
</script>
</body>
</html>