/* ============================================================
   AssessVUE — Admin JavaScript
   ============================================================ */

// Sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main    = document.querySelector('.main-content');
    const isMobile = window.innerWidth <= 991;

    if (isMobile) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('expanded');
    }
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 991) {
        const sidebar = document.getElementById('sidebar');
        const toggle  = document.querySelector('.sidebar-toggle');
        if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('mobile-open');
        }
    }
});

// Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert.alert-success').forEach(a => {
        const bs = bootstrap.Alert.getOrCreateInstance(a);
        bs && bs.close();
    });
}, 3500);

// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});
