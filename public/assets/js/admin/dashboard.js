import { callApi, requireRole } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Kiểm tra quyền Admin (sẽ chuyển về trang chủ nếu không có quyền)
    requireRole('admin');

    // 2. Tải dữ liệu
    fetchStats();

    // 3. Sự kiện
    const btnRefresh = document.getElementById('btnRefresh');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            const icon = btnRefresh.querySelector('i');
            icon.classList.add('fa-spin');

            // Re-pulse skeleton
            document.querySelectorAll('.loading-pulse').forEach(el => {
                el.classList.remove('d-none');
            });
            document.querySelectorAll('h3').forEach(el => {
                if (el.id && el.id !== 'statCommission' && el.id !== 'statRevenue') {
                    // Just a visual hack, innerHTML gets overwritten anyway
                }
            });

            fetchStats().finally(() => {
                setTimeout(() => icon.classList.remove('fa-spin'), 500);
            });
        });
    }
});

async function fetchStats() {
    try {
        const response = await callApi('/admin/dashboard', 'GET');

        if (response?.ok && response.data?.data) {
            const data = response.data.data;

            // Animate number updates
            animateValue('statCustomers', data.users.customers);
            animateValue('statWorkers', data.users.workers);
            animateValue('statBookings', data.bookings.total);
            const pendingWorkerProfiles = document.getElementById('pendingWorkerProfilesCount');
            if (pendingWorkerProfiles) {
                pendingWorkerProfiles.textContent = data.users.pending_worker_profiles ?? 0;
            }

            // Format money dynamically
            document.getElementById('statCommission').innerHTML = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(data.revenue.system_commission);
            document.getElementById('statRevenue').innerText = new Intl.NumberFormat('vi-VN').format(data.revenue.total_revenue);

            const countCompleted = document.getElementById('statCompletedBookings');
            if (countCompleted && data.bookings.completed > 0) {
                countCompleted.classList.remove('d-none');
                countCompleted.innerHTML = `<i class="fas fa-check-circle me-1"></i>${data.bookings.completed} hoàn thành`;
            }
        }
    } catch (error) {
        console.error("Lỗi tải thông tin Dashboard:", error);
    }
}

// Hàm hiệu ứng chạy số
function animateValue(id, end, duration = 1000) {
    const obj = document.getElementById(id);
    if (!obj) return;

    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);

        // easing (easeOutQuart)
        const ease = 1 - Math.pow(1 - progress, 4);

        obj.innerHTML = Math.floor(ease * end);

        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            obj.innerHTML = end; // Ensure exact final value
        }
    };
    window.requestAnimationFrame(step);
}
