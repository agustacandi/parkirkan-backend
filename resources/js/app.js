import './bootstrap';
import ApexCharts from 'apexcharts';

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

    const parkingTrendsEl = document.getElementById('parkingTrendsChart');
    if (parkingTrendsEl) {
        const labels = JSON.parse(parkingTrendsEl.dataset.labels || '[]');
        const values = JSON.parse(parkingTrendsEl.dataset.values || '[]').map(v => Number(v) || 0);

        const options = {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            series: [
                { name: 'Parkir', data: values },
            ],
            xaxis: {
                categories: labels,
                labels: {
                    style: { colors: '#64748b', fontWeight: 600 },
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: {
                    style: { colors: '#94a3b8' },
                },
            },
            grid: {
                strokeDashArray: 4,
                borderColor: 'rgba(148,163,184,0.25)',
            },
            plotOptions: {
                bar: {
                    borderRadius: 10,
                    columnWidth: '50%',
                },
            },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: (val) => `${val} parkir`,
                },
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.3,
                    opacityFrom: 0.95,
                    opacityTo: 0.85,
                    stops: [0, 90, 100],
                },
            },
            colors: ['#2563eb'],
        };

        const chart = new ApexCharts(parkingTrendsEl, options);
        chart.render();
    }
});
