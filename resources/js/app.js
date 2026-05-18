import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    // --- 1. Toggle Sidebar Mobile ---
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    function toggleSidebar() {
        if (!sidebar || !sidebarOverlay) return;

        const isClosed = sidebar.classList.contains('-translate-x-full');

        if (isClosed) {
            // Buka Sidebar
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
            // Sedikit jeda agar display:block teraplikasi sebelum transisi opacity
            setTimeout(() => sidebarOverlay.classList.remove('opacity-0'), 10);
            document.body.style.overflow = 'hidden'; // Mencegah background di-scroll
        } else {
            // Tutup Sidebar
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('opacity-0');
            // Tunggu animasi transisi selesai sebelum di-hidden sepenuhnya
            setTimeout(() => sidebarOverlay.classList.add('hidden'), 300);
            document.body.style.overflow = ''; // Mengembalikan fungsi scroll
        }
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // --- 2. Kontrol Global (Tombol Escape) untuk Sidebar & Modal ---
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            // Tutup Sidebar jika sedang terbuka di mobile
            if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }

            // Tutup semua modal yang mungkin sedang terbuka (Import / Delete)
            const modals = document.querySelectorAll('div[id$="Modal"]');
            modals.forEach(modal => {
                if (!modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                }
            });
        }
    });

    // --- 3. Auto-hide Alert/Toast (Pesan Sukses/Error) ---
    // Mencari elemen dengan role="alert" (yang kita buat di layout admin.blade.php)
    const alerts = document.querySelectorAll('[role="alert"]');

    alerts.forEach(alert => {
        // Hilangkan notifikasi otomatis setelah 5 detik
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';

            // Hapus elemen dari DOM setelah animasi transisi selesai
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
