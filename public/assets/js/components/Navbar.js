import { getCurrentUser, logout, callApi, showToast } from '../api.js';

class AppNavbar extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        this.render();
        this.setupEvents();
    }

    render() {
        const user = getCurrentUser();

        let rightMenuHtml = '';

        if (user) {
            // Đã đăng nhập
            const roleLabel = user.role === 'admin' ? 'Quản trị viên' : (user.role === 'customer' ? 'Khách hàng' : 'Thợ / Đối tác');
            const homeLink = user.role === 'admin' ? '/admin/dashboard' : (user.role === 'customer' ? '/customer/home' : '/worker/dashboard');

            // Chuông thông báo (chỉ dành cho Worker)
            const notificationHtml = user.role === 'worker' ? `
                <div class="dropdown me-3" id="notificationDropdownContainer">
                    <button class="btn btn-light position-relative p-2 border-0 shadow-none rounded-circle cursor-pointer" type="button" id="btnNotification" style="background: rgba(255,255,255,0.8); border: 1px solid rgba(0,0,0,0.05); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bell fs-5 text-secondary"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notificationBadge" style="font-size: 0.65rem; border: 2px solid white;">
                            0
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0" id="notificationMenu" style="border-radius: var(--border-radius-md); min-width: 320px; position: absolute; top: 100%; right: 0; overflow: hidden; z-index: 1050;">
                        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold" style="color: #0f172a;"><i class="fas fa-bell me-2 text-warning"></i>Thông báo mới</h6>
                            <button class="btn btn-sm btn-link text-decoration-none py-0 px-0 fw-semibold" id="btnMarkAllRead" style="font-size: 0.8rem;">Đã đọc tất cả</button>
                        </div>
                        <div id="notificationList" style="max-height: 350px; overflow-y: auto;">
                            <!-- Items go here -->
                        </div>
                        <div class="p-2 border-top text-center bg-light">
                            <a href="/worker/dashboard" class="text-decoration-none small fw-bold text-primary">Xem Bảng Việc Làm</a>
                        </div>
                    </div>
                </div>
            ` : '';

            rightMenuHtml = `
                <div class="d-flex align-items-center gap-1">
                    ${user.role === 'customer' ? `<button class="btn btn-warning shadow-sm me-3" style="padding: 0.5rem 1rem;" id="btnPostJob">Đăng việc ngay</button>` : ''}
                    ${notificationHtml}
                    <div class="dropdown">
                        <button class="btn btn-light d-flex align-items-center gap-2 p-1 border-0 shadow-none text-start" type="button" id="navbarUserDropdown" style="border-radius: 50px; background: rgba(255,255,255,0.8); border: 1px solid rgba(0,0,0,0.05);">
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--bs-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-family: 'Outfit', sans-serif;">
                                ${user.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="d-none d-md-block pe-3">
                                <div class="fw-bold fs-6 lh-1" style="color: var(--bs-body-color);">${user.name}</div>
                                <small class="text-muted-custom" style="font-size: 0.75rem;">${roleLabel}</small>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" id="navbarUserMenu" style="border-radius: var(--border-radius-md); min-width: 200px; position: absolute; top: 100%; right: 0;">
                            <li><a class="dropdown-item py-2" href="${homeLink}"><i class="fas fa-home me-2 text-muted"></i>Bảng điều khiển</a></li>
                            ${user.role === 'admin'
                    ? `<li><a class="dropdown-item py-2" href="/admin/dashboard"><i class="fas fa-chart-pie me-2 text-muted"></i>Tổng quan</a></li>
                       <li><a class="dropdown-item py-2" href="/admin/users"><i class="fas fa-users-cog me-2 text-muted"></i>Thành viên</a></li>
                       <li><a class="dropdown-item py-2" href="/admin/services"><i class="fas fa-list me-2 text-muted"></i>Dịch vụ</a></li>
                       <li><a class="dropdown-item py-2" href="/admin/assistant-soul"><i class="fas fa-robot me-2 text-muted"></i>ASSISTANT SOUL</a></li>
                       <li><a class="dropdown-item py-2" href="/admin/bookings"><i class="fas fa-clipboard-check me-2 text-muted"></i>Đơn hàng</a></li>`
                    : (user.role === 'customer'
                        ? `<li><a class="dropdown-item py-2" href="/customer/my-bookings"><i class="fas fa-history me-2 text-muted"></i>Lịch sử đặt thợ</a></li>`
                        : `<li><a class="dropdown-item py-2" href="/worker/profile"><i class="fas fa-user me-2 text-muted"></i>Hồ sơ Của Tôi</a></li>
                                   <li><a class="dropdown-item py-2" href="/worker/analytics"><i class="fas fa-chart-line me-2 text-muted"></i>Thống kê Thu nhập</a></li>
                                   <li><a class="dropdown-item py-2" href="/worker/reviews"><i class="fas fa-star me-2 text-muted"></i>Đánh giá của tôi</a></li>
                                   <li><a class="dropdown-item py-2" href="/worker/calendar"><i class="fas fa-calendar-alt me-2 text-muted"></i>Lịch làm việc</a></li>
                                `)}
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger fw-bold cursor-pointer" id="btnLogout">Đăng xuất</a></li>
                        </ul>
                    </div>
                </div>
            `;
        } else {
            // Khách vãng lai (Guest)
            rightMenuHtml = `
                <div class="d-flex align-items-center gap-2">
                    <a href="/login?role=customer" class="btn btn-warning shadow-sm d-none d-md-block" style="padding: 0.5rem 1rem;">Đăng việc ngay</a>
                    <a href="/" class="btn btn-primary shadow-sm" style="padding: 0.5rem 1rem;">Đăng Nhập</a>
                </div>
            `;
        }

        let centerMenuHtml = '';
        if (user && user.role === 'admin') {
            centerMenuHtml = `
                <li class="nav-item">
                    <a class="nav-link px-3 active fw-bold" href="/admin/dashboard" style="color: #1E293B;">Thống kê</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/admin/users" style="color: #64748B;">Cộng đồng</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/admin/bookings" style="color: #64748B;">Lịch sử Đơn</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/admin/services" style="color: #64748B;">Dịch vụ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/admin/assistant-soul" style="color: #64748B;">ASSISTANT SOUL</a>
                </li>
            `;
        } else if (user && user.role === 'worker') {
            centerMenuHtml = `
                <li class="nav-item">
                    <a class="nav-link px-3 active fw-bold" href="/worker/dashboard" style="color: #1E293B;">Bảng Việc Làm</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/worker/my-bookings" style="color: #134994ff;">Việc Của Tôi</a>
                </li>
            `;
        } else {
            centerMenuHtml = `
                <li class="nav-item">
                    <a class="nav-link px-3 active" href="/customer/home" style="color: #1E293B;">Trang Chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="#" style="color: #1d5199ff;">Dịch Vụ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="#" style="color: #1153b1ff;">Hỗ Trợ</a>
                </li>
            `;
        }

        this.innerHTML = `
            <nav class="navbar navbar-expand-lg sticky-top w-100" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(0,0,0,0.05); z-index: 1000;">
                <div class="container py-1">
                    <a class="navbar-brand d-flex align-items-center gap-2" href="${user ? (user.role === 'admin' ? '/admin/dashboard' : (user.role === 'worker' ? '/worker/dashboard' : '/customer/home')) : '/customer/home'}">
                        <img src="/assets/images/logontu.png" alt="Logo NTU" height="48" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
                        <span class="fs-4 fw-bold brand-font" style="color: var(--bs-primary); letter-spacing: -1px;">Thợ Tốt</span>
                    </a>
                    
                    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarMain">
                        <ul class="navbar-nav mx-auto mb-2 mb-lg-0 fw-semibold">
                            ${centerMenuHtml}
                        </ul>
                        
                        ${rightMenuHtml}
                    </div>
                </div>
            </nav>
        `;
    }

    setupEvents() {
        // Xử lý thủ công sự kiện Dropdown đảm bảo tương thích 100% trong Web Component
        const btnDropdown = this.querySelector('#navbarUserDropdown');
        const menuDropdown = this.querySelector('#navbarUserMenu');

        if (btnDropdown && menuDropdown) {
            btnDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                menuDropdown.classList.toggle('show');
            });

            // Tự đóng menu khi click ra ngoài
            document.addEventListener('click', (e) => {
                if (!menuDropdown.contains(e.target) && !btnDropdown.contains(e.target)) {
                    menuDropdown.classList.remove('show');
                }
            });
        }

        const btnLogout = this.querySelector('#btnLogout');
        if (btnLogout) {
            btnLogout.addEventListener('click', () => {
                logout();
            });
        }

        const btnPostJob = this.querySelector('#btnPostJob');
        if (btnPostJob) {
            btnPostJob.addEventListener('click', () => {
                // Sẽ xử lý nhảy qua trang Đăng Việc sau
                window.location.href = '/customer/post-job';
            });
        }

        const user = getCurrentUser();
        if (user && user.role === 'worker') {
            this.setupNotifications();
        }
    }

    setupNotifications() {
        const btnNotification = this.querySelector('#btnNotification');
        const notificationMenu = this.querySelector('#notificationMenu');
        const badge = this.querySelector('#notificationBadge');
        const list = this.querySelector('#notificationList');
        const btnMarkAllRead = this.querySelector('#btnMarkAllRead');
        let currentCount = 0;

        if (btnNotification && notificationMenu) {
            btnNotification.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationMenu.classList.toggle('show');
            });
            document.addEventListener('click', (e) => {
                if (!notificationMenu.contains(e.target) && !btnNotification.contains(e.target)) {
                    notificationMenu.classList.remove('show');
                }
            });
        }

        const fetchNotifs = async () => {
            try {
                const res = await callApi('/notifications/unread');
                if (res.ok && res.data) {
                    const count = res.data.unread_count;
                    const notifs = res.data.notifications;

                    if (count > 0) {
                        badge.classList.remove('d-none');
                        badge.innerText = count > 99 ? '99+' : count;

                        // Play sound or toast if NEW notification arrived
                        if (count > currentCount) {
                            showToast('Bạn có đơn đặt lịch mới từ khách hàng!', 'success');
                            // Fallback beep for urgency
                            try {
                                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                                const oscillator = audioCtx.createOscillator();
                                const gainNode = audioCtx.createGain();
                                oscillator.connect(gainNode);
                                gainNode.connect(audioCtx.destination);
                                oscillator.type = 'sine';
                                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); // 880Hz (A5)
                                gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
                                gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
                                oscillator.start();
                                oscillator.stop(audioCtx.currentTime + 0.5);
                            } catch (e) { }
                        }
                    } else {
                        badge.classList.add('d-none');
                    }
                    currentCount = count;

                    // Render list
                    if (notifs.length > 0) {
                        let html = '';
                        notifs.forEach(n => {
                            const d = n.data;
                            html += `
                                <a href="/worker/dashboard" class="dropdown-item p-3 border-bottom notification-item bg-white" data-id="${n.id}" style="white-space: normal; transition: background 0.2s;">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle text-primary flex-shrink-0">
                                            <i class="fas fa-calendar-check mt-1"></i>
                                        </div>
                                        <div>
                                            <p class="mb-1 text-dark fw-bold lh-sm border-0" style="font-size: 0.9rem;">${d.message}</p>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-clock text-warning me-1"></i>${d.thoi_gian_hen ? d.thoi_gian_hen.substring(0, 16) : 'Sớm nhất'}</span>
                                                <small class="text-primary fw-bold" style="font-size: 0.75rem;">${d.dich_vu_name}</small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            `;
                        });
                        list.innerHTML = html;

                        // Add click event to mark as read
                        this.querySelectorAll('.notification-item').forEach(el => {
                            el.addEventListener('click', async (e) => {
                                e.preventDefault();
                                const id = el.getAttribute('data-id');
                                await callApi(`/notifications/${id}/read`, 'POST');
                                window.location.href = el.getAttribute('href');
                            });
                        });
                    } else {
                        list.innerHTML = '<div class="p-4 text-center text-muted"><i class="fas fa-check-circle mb-2 fs-3 text-success opacity-50"></i><br><small>Bạn đã xem hết thông báo!</small></div>';
                    }
                }
            } catch (e) {
                console.error("Polling error", e);
            }
        };

        if (btnMarkAllRead) {
            btnMarkAllRead.addEventListener('click', async (e) => {
                e.stopPropagation();
                try {
                    await callApi('/notifications/read-all', 'POST');
                    fetchNotifs();
                } catch (e) { }
            });
        }

        // Delay starting polling to let other UI scripts finish
        setTimeout(() => {
            fetchNotifs();
            // Start polling every 10 seconds
            setInterval(fetchNotifs, 10000);
        }, 1000);
    }
}

customElements.define('app-navbar', AppNavbar);
