import { callApi, getCurrentUser, logout } from '../api.js';

const user = getCurrentUser();
if (!user || !['worker', 'admin'].includes(user.role)) {
    logout();
}

document.addEventListener('DOMContentLoaded', async () => {
    const statTongDoanhThu = document.getElementById('statTongDoanhThu');
    const statDoanhThuThangNay = document.getElementById('statDoanhThuThangNay');
    const statDonHoanThanh = document.getElementById('statDonHoanThanh');
    const statDonHuy = document.getElementById('statDonHuy');
    const ctx = document.getElementById('revenueChart');

    let revenueChartInstance = null;

    const formatMoney = (amount) => {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
    };

    const loadStats = async () => {
        try {
            const res = await callApi('/worker/stats', 'GET');
            if (res.ok && res.data) {
                const data = res.data;

                // Cập nhật số liệu
                statTongDoanhThu.innerText = formatMoney(data.tong_doanh_thu);
                statDoanhThuThangNay.innerText = formatMoney(data.doanh_thu_thang_nay);
                statDonHoanThanh.innerText = data.don_hoan_thanh_thang_nay;
                statDonHuy.innerText = data.don_huy_thang_nay;

                // Vẽ Biểu đồ
                renderChart(data.chart_data);
            }
        } catch (error) {
            console.error('Lỗi tải thống kê:', error);
            alert('Không thể tải dữ liệu thống kê lúc này.');
        }
    };

    const renderChart = (chartData) => {
        if (!ctx || !chartData || chartData.length === 0) return;

        // chartData là mảng array từ ngày cũ nhất đến ngày mới nhất, ta cần đảo ngược lại nếu backend trả về giảm dần
        // Backend đang trả về vòng lặp for ($i=6; $i>=0; $i--) nên đã tự động tăng dần theo thời gian.

        const labels = chartData.map(item => item.date);
        const dataValues = chartData.map(item => item.revenue);

        if (revenueChartInstance) {
            revenueChartInstance.destroy();
        }

        revenueChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: dataValues,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value, index, values) {
                                if (value === 0) return '0 ₫';
                                return (value / 1000) + 'k ₫';
                            }
                        },
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
    };

    loadStats();
});
