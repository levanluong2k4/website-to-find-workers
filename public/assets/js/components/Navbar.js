import { getCurrentUser, logout } from '../api.js';

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
            const roleLabel = user.role === 'customer' ? 'Khách hàng' : 'Thợ / Đối tác';
            const homeLink = user.role === 'customer' ? '/customer/home' : '/worker/dashboard';
            
            rightMenuHtml = `
                <div class="d-flex align-items-center gap-3">
                    ${user.role === 'customer' ? `<button class="btn btn-warning shadow-sm" style="padding: 0.5rem 1rem;" id="btnPostJob">Đăng việc ngay</button>` : ''}
                    <div class="dropdown">
                        <div class="d-flex align-items-center gap-2 cursor-pointer p-1" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 50px; background: rgba(255,255,255,0.8); border: 1px solid rgba(0,0,0,0.05);">
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--bs-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-family: 'Outfit', sans-serif;">
                                ${user.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="d-none d-md-block pe-3">
                                <div class="fw-bold fs-6 lh-1" style="color: var(--bs-body-color);">${user.name}</div>
                                <small class="text-muted-custom" style="font-size: 0.75rem;">${roleLabel}</small>
                            </div>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: var(--border-radius-md); min-width: 200px;">
                            <li><a class="dropdown-item py-2" href="${homeLink}">Bảng điều khiển</a></li>
                            ${user.role === 'customer' ? `<li><a class="dropdown-item py-2" href="#">Lịch sử đặt thợ</a></li>` : `<li><a class="dropdown-item py-2" href="/worker/profile">Hồ sơ Của Tôi</a></li>`}
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

        this.innerHTML = `
            <nav class="navbar navbar-expand-lg sticky-top w-100" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(0,0,0,0.05); z-index: 1000;">
                <div class="container py-1">
                    <a class="navbar-brand d-flex align-items-center gap-2" href="/customer/home">
                        <img src="/assets/images/logo.png" alt="Logo" height="40" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
                        <span class="fs-4 fw-bold brand-font" style="color: var(--bs-primary); letter-spacing: -1px;">FindWorker</span>
                    </a>
                    
                    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarMain">
                        <ul class="navbar-nav mx-auto mb-2 mb-lg-0 fw-semibold">
                            <li class="nav-item">
                                <a class="nav-link px-3 active" href="/customer/home" style="color: #1E293B;">Trang Chủ</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link px-3" href="#" style="color: #64748B;">Dịch Vụ</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link px-3" href="#" style="color: #64748B;">Hỗ Trợ</a>
                            </li>
                        </ul>
                        
                        ${rightMenuHtml}
                    </div>
                </div>
            </nav>
        `;
    }

    setupEvents() {
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
    }
}

customElements.define('app-navbar', AppNavbar);
