import { getCurrentUser, logout, callApi } from '../api.js';

class AppNavbar extends HTMLElement {
    constructor() {
        super();
        this.cleanupFns = [];
        this.notificationPollId = null;
        this.notificationBootTimeoutId = null;
        this.notificationToastShownIds = new Set();
        this.handleUserUpdated = this.handleUserUpdated.bind(this);
    }

    connectedCallback() {
        this.render();
        this.setupEvents();
        window.addEventListener('user-updated', this.handleUserUpdated);
    }

    disconnectedCallback() {
        this.cleanupEvents();
        this.clearNotificationPolling();
        window.removeEventListener('user-updated', this.handleUserUpdated);
    }

    handleUserUpdated() {
        this.render();
        this.setupEvents();
    }

    render() {
        const user = getCurrentUser();
        const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
        const isCurrentPath = (...paths) => paths.some((path) => {
            const normalizedPath = (path || '').replace(/\/+$/, '') || '/';
            return currentPath === normalizedPath || (normalizedPath !== '/' && currentPath.startsWith(`${normalizedPath}/`));
        });
        const navLinkClass = (isActive) => `nav-link px-3 app-navbar-link${isActive ? ' active fw-bold' : ''}`;
        const navLinkStyle = (defaultColor = '#64748B') => `--app-nav-color: ${defaultColor};`;

        let rightMenuHtml = '';
        let mobileUserDropdownHtml = '';
        let mobileNotificationHtml = '';
        let desktopNotificationHtml = '';

        if (user) {
            const roleLabel = user.role === 'admin'
                ? 'Quản trị viên'
                : (user.role === 'customer' ? 'Khách hàng' : 'Thợ / Đối tác');
            const homeLink = user.role === 'admin'
                ? '/admin/dashboard'
                : (user.role === 'customer' ? '/customer/home' : '/worker/dashboard');
            const isUserMenuSection = (user.role === 'customer' && isCurrentPath('/customer/profile', '/customer/my-bookings'))
                || (user.role === 'worker' && isCurrentPath('/worker/profile', '/worker/analytics', '/worker/reviews', '/worker/calendar'));
            const initials = this.getInitials(user.name);
            const avatarUrl = this.resolveAvatarUrl(user.avatar);
            const avatarHtml = `
                <div class="app-navbar-avatar">
                    ${avatarUrl
                    ? `<img src="${avatarUrl}" alt="${this.escapeHtml(user.name || 'Avatar')}" class="app-navbar-avatar-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                       <span class="app-navbar-avatar-fallback" style="display:none;">${initials}</span>`
                    : `<span class="app-navbar-avatar-fallback">${initials}</span>`}
                </div>
            `;

            const legacyNotificationHtml = user.role === 'worker' ? `
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
                        <div id="notificationList" style="max-height: 350px; overflow-y: auto;"></div>
                        <div class="p-2 border-top text-center bg-light">
                            <a href="/worker/dashboard" class="text-decoration-none small fw-bold text-primary">Xem Bảng Việc Làm</a>
                        </div>
                    </div>
                </div>
            ` : '';

            const notificationOverview = this.resolveNotificationOverview(user);
            desktopNotificationHtml = this.buildNotificationDropdownHtml({
                suffix: 'Desktop',
                viewAllHref: notificationOverview.href,
                viewAllLabel: notificationOverview.label,
                wrapClass: 'app-navbar-desktop-notification-wrap',
            });
            mobileNotificationHtml = this.buildNotificationDropdownHtml({
                suffix: 'Mobile',
                viewAllHref: notificationOverview.href,
                viewAllLabel: notificationOverview.label,
                wrapClass: 'app-navbar-mobile-notification-wrap',
            });

            const userMenuHtml = user.role === 'admin'
                ? `
                    <li><a class="dropdown-item py-2${isCurrentPath('/admin/dashboard') ? ' active' : ''}" href="/admin/dashboard"><i class="fas fa-chart-pie me-2 text-muted"></i>Tổng quan</a></li>
                    <li><a class="dropdown-item py-2${isCurrentPath('/admin/users') ? ' active' : ''}" href="/admin/users"><i class="fas fa-users-cog me-2 text-muted"></i>Thành viên</a></li>
                    <li><a class="dropdown-item py-2${isCurrentPath('/admin/services') ? ' active' : ''}" href="/admin/services"><i class="fas fa-list me-2 text-muted"></i>Dịch vụ</a></li>
                    <li><a class="dropdown-item py-2${isCurrentPath('/admin/travel-fee-config') ? ' active' : ''}" href="/admin/travel-fee-config"><i class="fas fa-route me-2 text-muted"></i>Phí đi lại</a></li>
                    <li><a class="dropdown-item py-2${isCurrentPath('/admin/assistant-soul') ? ' active' : ''}" href="/admin/assistant-soul"><i class="fas fa-robot me-2 text-muted"></i>ASSISTANT SOUL</a></li>
                    <li><a class="dropdown-item py-2${isCurrentPath('/admin/bookings') ? ' active' : ''}" href="/admin/bookings"><i class="fas fa-clipboard-check me-2 text-muted"></i>Đơn hàng</a></li>
                `
                : (user.role === 'customer'
                    ? `
                        <li><a class="dropdown-item py-2${isCurrentPath('/customer/my-bookings') ? ' active' : ''}" href="/customer/my-bookings"><i class="fas fa-history me-2 text-muted"></i>Đơn của tôi</a></li>
                        <li><a class="dropdown-item py-2${isCurrentPath('/customer/profile') ? ' active' : ''}" href="/customer/profile"><i class="fas fa-user-circle me-2 text-muted"></i>Hồ sơ</a></li>
                    `
                    : `
                        <li><a class="dropdown-item py-2${isCurrentPath('/worker/profile') ? ' active' : ''}" href="/worker/profile"><i class="fas fa-user me-2 text-muted"></i>Hồ sơ của tôi</a></li>
                        <li><a class="dropdown-item py-2${isCurrentPath('/worker/analytics') ? ' active' : ''}" href="/worker/analytics"><i class="fas fa-chart-line me-2 text-muted"></i>Thống kê thu nhập</a></li>
                        <li><a class="dropdown-item py-2${isCurrentPath('/worker/reviews') ? ' active' : ''}" href="/worker/reviews"><i class="fas fa-star me-2 text-muted"></i>Đánh giá của tôi</a></li>
                        <li><a class="dropdown-item py-2${isCurrentPath('/worker/calendar') ? ' active' : ''}" href="/worker/calendar"><i class="fas fa-calendar-alt me-2 text-muted"></i>Lịch làm việc</a></li>
                    `);

            mobileUserDropdownHtml = `
                <div class="dropdown app-navbar-user-menu-wrap app-navbar-mobile-user-wrap">
                    <button class="btn d-flex align-items-center justify-content-center p-1 border-0 shadow-none app-navbar-user-trigger app-navbar-user-trigger-mobile${isUserMenuSection ? ' is-active' : ''}" type="button" id="navbarUserDropdownMobile" aria-label="Tài khoản">
                        ${avatarHtml}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 app-navbar-dropdown app-navbar-dropdown-mobile" id="navbarUserMenuMobile" style="position: absolute; top: 100%; right: 0;">
                        <li><a class="dropdown-item py-2${currentPath === homeLink ? ' active' : ''}" href="${homeLink}"><i class="fas fa-home me-2 text-muted"></i>Bảng điều khiển</a></li>
                        ${userMenuHtml}
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger fw-bold cursor-pointer" data-navbar-logout="1">Đăng xuất</a></li>
                    </ul>
                </div>
            `;

            rightMenuHtml = `
                <div class="app-navbar-actions d-flex align-items-center gap-1">
                    ${user.role === 'customer' ? `
                        <button class="btn app-navbar-cta me-3" id="btnCustomerBooking" type="button">
                            <span class="app-navbar-cta-label">Đặt lịch ngay</span>
                            <span class="app-navbar-cta-icon" aria-hidden="true">
                                <svg viewBox="0 0 16 19" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M7 18C7 18.5523 7.44772 19 8 19C8.55228 19 9 18.5523 9 18H7ZM8.70711 0.292893C8.31658 -0.0976311 7.68342 -0.0976311 7.29289 0.292893L0.928932 6.65685C0.538408 7.04738 0.538408 7.68054 0.928932 8.07107C1.31946 8.46159 1.95262 8.46159 2.34315 8.07107L8 2.41421L13.6569 8.07107C14.0474 8.46159 14.6805 8.46159 15.0711 8.07107C15.4616 7.68054 15.4616 7.04738 15.0711 6.65685L8.70711 0.292893ZM9 18L9 1H7L7 18H9Z"></path>
                                </svg>
                            </span>
                        </button>
                    ` : ''}
                    <div class="dropdown app-navbar-user-menu-wrap app-navbar-desktop-user-wrap">
                        <button class="btn d-flex align-items-center gap-2 p-1 border-0 shadow-none text-start app-navbar-user-trigger${isUserMenuSection ? ' is-active' : ''}" type="button" id="navbarUserDropdownDesktop">
                            ${avatarHtml}
                            <div class="d-none d-md-block pe-3">
                                <div class="fw-bold fs-6 lh-1 app-navbar-user-name" style="color: var(--bs-body-color);">${this.escapeHtml(user.name || 'Người dùng')}</div>
                                <small class="text-muted-custom app-navbar-user-role" style="font-size: 0.75rem;">${roleLabel}</small>
                            </div>
                            <div class="app-navbar-user-mobile-copy">
                                <div class="app-navbar-user-mobile-name">${this.escapeHtml(user.name || 'NgÆ°á»i dÃ¹ng')}</div>
                                <small class="app-navbar-user-mobile-role">${roleLabel}</small>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 app-navbar-dropdown" id="navbarUserMenuDesktop" style="position: absolute; top: 100%; right: 0;">
                            <li><a class="dropdown-item py-2${currentPath === homeLink ? ' active' : ''}" href="${homeLink}"><i class="fas fa-home me-2 text-muted"></i>Bảng điều khiển</a></li>
                            ${userMenuHtml}
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger fw-bold cursor-pointer" data-navbar-logout="1">Đăng xuất</a></li>
                        </ul>
                    </div>
                    ${desktopNotificationHtml}
                </div>
            `;
        } else {
            rightMenuHtml = `
                <div class="app-navbar-actions d-flex align-items-center gap-2">
                    <a href="/login?role=customer" class="btn btn-outline-success d-none d-md-block app-navbar-ghost">Đăng việc ngay</a>
                    <a href="/select-role" class="btn btn-primary">Đăng Nhập</a>
                </div>
            `;
        }

        let centerMenuHtml = '';
        if (user && user.role === 'admin') {
            centerMenuHtml = `
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/admin/dashboard'))}" href="/admin/dashboard" style="${navLinkStyle()}">Thống kê</a>
                </li>
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/admin/users'))}" href="/admin/users" style="${navLinkStyle()}">Cộng đồng</a>
                </li>
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/admin/bookings'))}" href="/admin/bookings" style="${navLinkStyle()}">Lịch sử đơn</a>
                </li>
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/admin/dispatch'))}" href="/admin/dispatch" style="${navLinkStyle()}">Điều phối thợ</a>
                </li>
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/admin/services'))}" href="/admin/services" style="${navLinkStyle()}">Dịch vụ</a>
                </li>
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/admin/travel-fee-config'))}" href="/admin/travel-fee-config" style="${navLinkStyle()}">Phí đi lại</a>
                </li>
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/admin/assistant-soul'))}" href="/admin/assistant-soul" style="${navLinkStyle()}">ASSISTANT SOUL</a>
                </li>
            `;
        } else if (user && user.role === 'worker') {
            centerMenuHtml = `
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/worker/dashboard'))}" href="/worker/dashboard" style="${navLinkStyle()}">Bảng việc làm</a>
                </li>
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/worker/my-bookings'))}" href="/worker/my-bookings" style="${navLinkStyle()}">Việc của tôi</a>
                </li>
            `;
        } else {
            centerMenuHtml = `
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/', '/customer/home'))}" href="/customer/home" style="${navLinkStyle()}">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/customer/search', '/customer/worker-profile'))}" href="/customer/search" style="${navLinkStyle()}">Dịch vụ</a>
                </li>
            `;
        }

        if (!user || user.role === 'customer') {
            centerMenuHtml += `
                <li class="nav-item">
                    <a class="${navLinkClass(isCurrentPath('/customer/linh-kien'))}" href="/customer/linh-kien" style="${navLinkStyle()}">Linh kiện</a>
                </li>
            `;
        }

        this.innerHTML = `
            <style>
                .app-navbar-wrap {
                    position: fixed;
                    top: 0.85rem;
                    left: 0;
                    right: 0;
                    z-index: 1050;
                    padding: 0 1rem;
                    display: flex;
                    justify-content: center;
                    pointer-events: none;
                    transition: padding 0.3s ease;
                }

                .app-navbar-spacer {
                    height: 6.4rem;
                }

                @media (max-width: 991.98px) {
                    .app-navbar-wrap {
                        top: 0.5rem;
                        padding: 0 0.5rem;
                    }

                    .app-navbar-spacer {
                        height: 5.6rem;
                    }
                }

                .app-navbar-toggle {
                    display: none !important;
                    width: 44px;
                    height: 44px;
                    align-items: center;
                    justify-content: center;
                }

                .app-navbar-topbar-actions {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }

                .app-navbar-mobile-user-wrap {
                    display: none;
                }

                .app-navbar-mobile-notification-wrap {
                    display: none;
                }

                @media (max-width: 991.98px) {
                    .app-navbar-topbar-actions {
                        margin-left: auto;
                    }

                    .app-navbar-toggle {
                        display: flex !important;
                    }

                    .app-navbar-mobile-user-wrap {
                        display: block;
                    }

                    .app-navbar-mobile-notification-wrap {
                        display: block;
                    }

                    .app-navbar-desktop-user-wrap {
                        display: none !important;
                    }

                    .app-navbar-desktop-notification-wrap {
                        display: none !important;
                    }
                }

                .app-navbar-shell {
                    pointer-events: auto;
                    width: 100%;
                    max-width: 1440px;
                    background: rgba(255, 255, 255, 0.94) !important;
                    backdrop-filter: blur(24px);
                    -webkit-backdrop-filter: blur(24px);
                    border: 1px solid rgba(226, 232, 240, 0.9) !important;
                    border-radius: 30px;
                    padding: 0.7rem 1.35rem !important;
                    box-shadow: 0 18px 46px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.85);
                    transition: all 0.3s ease;
                }

                @media (max-width: 991.98px) {
                    .app-navbar-shell {
                        border-radius: 24px;
                        padding: 0.5rem !important;
                    }
                }

                .app-navbar-brand {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.85rem;
                    text-decoration: none;
                    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
                }

                .app-navbar-brand:hover {
                    transform: scale(1.02);
                }

                .app-navbar-brand-mark {
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: radial-gradient(circle at 30% 30%, #2d8cff 0%, #1E78EA 55%, #155fcc 100%);
                    box-shadow: 0 10px 18px rgba(30, 120, 234, 0.24);
                    flex-shrink: 0;
                }

                .app-navbar-brand-mark img {
                    width: 23px;
                    height: 23px;
                    object-fit: contain;
                    filter: brightness(0) invert(1);
                }

                .app-navbar-brand-text {
                    display: inline-flex;
                    align-items: baseline;
                    gap: 0.18rem;
                    font-size: 1.18rem;
                    font-weight: 800;
                    letter-spacing: -0.03em;
                    color: #0f172a;
                }

                .app-navbar-brand-text span {
                    color: #1E78EA;
                }

                .app-navbar-menu-pill {
                    background: rgba(241, 245, 249, 0.82);
                    border: 1px solid rgba(226, 232, 240, 0.9);
                    border-radius: 999px;
                    padding: 0.3rem;
                    margin: 0 1.6rem;
                    display: flex;
                    align-items: center;
                    gap: 0.25rem;
                    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92);
                }

                .app-navbar-menu-pill .nav-item {
                    display: flex;
                }

                .app-navbar-actions {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    margin-left: auto;
                }

                @media (max-width: 991.98px) {
                    .app-navbar-menu-pill {
                        background: transparent;
                        border: none;
                        border-radius: 0;
                        padding: 1rem 0;
                        margin: 0;
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 0.5rem;
                    }

                    .app-navbar-actions {
                        width: 100%;
                        flex-direction: column;
                        align-items: stretch;
                        gap: 0.75rem;
                        padding-top: 0.5rem;
                    }

                    .app-navbar-actions > a,
                    .app-navbar-actions > button,
                    .app-navbar-user-menu-wrap {
                        width: 100%;
                    }

                    .app-navbar-actions > .app-navbar-notification-wrap {
                        width: auto;
                        align-self: flex-start;
                        margin-right: 0 !important;
                    }
                }

                .app-navbar-link {
                    color: #64748B !important;
                    white-space: nowrap;
                    justify-content: center;
                    text-align: center;
                    padding: 0.45rem 1rem !important;
                    border-radius: 999px;
                    font-weight: 700;
                    font-size: 0.9rem;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }

                .app-navbar-link:hover {
                    color: #1E78EA !important;
                    background: rgba(255, 255, 255, 0.72);
                }

                .app-navbar-link.active {
                    color: #1E78EA !important;
                    background: #ffffff;
                    box-shadow: 0 6px 16px rgba(30, 120, 234, 0.12);
                }

                @media (max-width: 991.98px) {
                    .app-navbar-link {
                        width: 100%;
                        border-radius: 12px;
                    }
                }

                .app-navbar-cta {
                    display: inline-flex !important;
                    align-items: center;
                    justify-content: center;
                    gap: 0.6rem;
                    padding: 0.55rem 1.5rem !important;
                    border-radius: 999px !important;
                    background: #0ea5e9 !important;
                    color: #ffffff !important;
                    font-weight: 700 !important;
                    font-size: 0.95rem;
                    border: none !important;
                    box-shadow: 0 8px 24px -6px rgba(14, 165, 233, 0.4) !important;
                    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
                }

                .app-navbar-cta:hover {
                    background: #0284c7 !important;
                    transform: translateY(-2px);
                    box-shadow: 0 12px 28px -8px rgba(14, 165, 233, 0.6) !important;
                    color: #ffffff !important;
                }

                .app-navbar-cta-icon {
                    display: inline-flex;
                    transition: transform 0.3s ease;
                }

                .app-navbar-cta:hover .app-navbar-cta-icon {
                    transform: translateX(4px);
                }

                @media (max-width: 991.98px) {
                    .app-navbar-cta {
                        width: 100%;
                        justify-content: center;
                        margin: 1rem 0 0.5rem 0 !important;
                    }
                }

                .app-navbar-ghost {
                    border-radius: 999px !important;
                    border: 1px solid rgba(14, 165, 233, 0.2) !important;
                    color: #0ea5e9 !important;
                    background: rgba(14, 165, 233, 0.05) !important;
                    font-weight: 600 !important;
                    padding: 0.55rem 1.25rem !important;
                    transition: all 0.3s ease;
                }

                .app-navbar-ghost:hover {
                    background: rgba(14, 165, 233, 0.1) !important;
                    color: #0284c7 !important;
                    border-color: rgba(14, 165, 233, 0.3) !important;
                    transform: translateY(-1px);
                }

                .app-navbar-user-trigger {
                    border-radius: 999px !important;
                    background: #ffffff !important;
                    border: 1px solid rgba(226, 232, 240, 0.8) !important;
                    padding: 0.35rem 1rem 0.35rem 0.35rem !important;
                    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
                    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
                }

                .app-navbar-user-trigger-mobile {
                    width: 44px;
                    height: 44px;
                    padding: 0.2rem !important;
                    justify-content: center;
                }

                @media (max-width: 991.98px) {
                    .app-navbar-user-trigger {
                        padding-right: 0.8rem !important;
                        border-radius: 12px !important;
                        width: 100%;
                        justify-content: flex-start;
                    }

                    .app-navbar-user-mobile-copy {
                        display: flex !important;
                        flex-direction: column;
                    }

                    .app-navbar-user-trigger-mobile {
                        width: 44px;
                        height: 44px;
                        padding: 0.2rem !important;
                        border-radius: 999px !important;
                    }

                    .app-navbar-user-trigger-mobile .app-navbar-avatar {
                        width: 34px;
                        height: 34px;
                    }
                }

                .app-navbar-user-trigger:hover,
                .app-navbar-user-trigger.is-active {
                    background: #f8fafc !important;
                    border-color: rgba(14, 165, 233, 0.3) !important;
                    box-shadow: 0 4px 15px rgba(14, 165, 233, 0.1);
                    transform: translateY(-1px);
                }

                .app-navbar-user-name {
                    letter-spacing: -0.01em;
                    color: #1e293b;
                }

                .app-navbar-user-role {
                    font-weight: 600;
                    color: #64748b;
                }

                .app-navbar-user-mobile-copy {
                    display: none;
                    min-width: 0;
                }

                .app-navbar-user-mobile-name {
                    font-weight: 700;
                    color: #1e293b;
                    line-height: 1.1;
                }

                .app-navbar-user-mobile-role {
                    color: #64748b;
                    font-weight: 600;
                    line-height: 1.2;
                }

                .app-navbar-avatar {
                    width: 38px;
                    height: 38px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);
                    color: #fff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-family: 'Inter', sans-serif;
                    overflow: hidden;
                    flex-shrink: 0;
                    box-shadow: 0 2px 6px rgba(14, 165, 233, 0.3);
                }

                .app-navbar-avatar-image {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .app-navbar-avatar-fallback {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .app-navbar-dropdown {
                    border-radius: 20px !important;
                    min-width: 240px !important;
                    margin-top: 1rem !important;
                    padding: 0.5rem !important;
                    border: 1px solid rgba(226, 232, 240, 0.8) !important;
                    box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.1) !important;
                    background: rgba(255, 255, 255, 0.98) !important;
                    backdrop-filter: blur(10px);
                }

                .app-navbar-dropdown .dropdown-item {
                    border-radius: 12px;
                    font-weight: 500;
                    color: #475569;
                    padding: 0.75rem 1rem;
                    transition: all 0.2s ease;
                }

                .app-navbar-dropdown .dropdown-item:hover {
                    background: #f0f9ff;
                    color: #0ea5e9;
                    transform: translateX(4px);
                }

                .app-navbar-dropdown .dropdown-item.active,
                .app-navbar-dropdown .dropdown-item:active {
                    background: #e0f2fe;
                    color: #0ea5e9;
                    font-weight: 600;
                }

                .app-navbar-dropdown .dropdown-divider {
                    margin: 0.5rem 0;
                    border-color: rgba(226, 232, 240, 0.5);
                }

                .app-navbar-dropdown .dropdown-item.text-danger:hover {
                    background: #fef2f2;
                    color: #dc2626 !important;
                }
                
                .app-navbar-notification-wrap {
                    position: relative;
                    flex-shrink: 0;
                }

                .app-navbar-notification-trigger {
                    width: 44px;
                    height: 44px;
                    border-radius: 999px !important;
                    background: #ffffff !important;
                    border: 1px solid rgba(226, 232, 240, 0.8) !important;
                    color: #334155 !important;
                    display: inline-flex !important;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
                }

                .app-navbar-notification-trigger:hover,
                .app-navbar-notification-trigger.is-active {
                    border-color: rgba(14, 165, 233, 0.3) !important;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 15px rgba(14, 165, 233, 0.1);
                }

                .app-navbar-notification-badge {
                    position: absolute;
                    top: -0.15rem;
                    right: -0.15rem;
                    min-width: 1.2rem;
                    height: 1.2rem;
                    padding: 0 0.3rem;
                    border-radius: 999px;
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    color: #ffffff;
                    font-size: 0.65rem;
                    font-weight: 800;
                    line-height: 1.2rem;
                    border: 2px solid #ffffff;
                    text-align: center;
                    box-shadow: 0 10px 18px rgba(220, 38, 38, 0.22);
                }

                .app-navbar-notification-menu {
                    width: min(360px, calc(100vw - 1rem)) !important;
                    min-width: min(360px, calc(100vw - 1rem)) !important;
                    margin-top: 1rem !important;
                    padding: 0 !important;
                    overflow: hidden;
                }

                .app-navbar-notification-head,
                .app-navbar-notification-foot {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 0.75rem;
                    padding: 0.95rem 1rem;
                    background: linear-gradient(180deg, rgba(248, 250, 252, 0.94) 0%, rgba(255, 255, 255, 0.98) 100%);
                }

                .app-navbar-notification-head {
                    border-bottom: 1px solid rgba(226, 232, 240, 0.9);
                }

                .app-navbar-notification-foot {
                    border-top: 1px solid rgba(226, 232, 240, 0.9);
                    justify-content: center;
                }

                .app-navbar-notification-title {
                    display: flex;
                    flex-direction: column;
                    gap: 0.15rem;
                }

                .app-navbar-notification-title strong {
                    color: #0f172a;
                    font-size: 0.95rem;
                    line-height: 1.2;
                }

                .app-navbar-notification-title small {
                    color: #64748b;
                    font-weight: 600;
                }

                .app-navbar-mark-read {
                    border: 0;
                    background: transparent;
                    color: #0284c7;
                    font-size: 0.8rem;
                    font-weight: 700;
                    padding: 0;
                }

                .app-navbar-notification-list {
                    max-height: 360px;
                    overflow-y: auto;
                    background: rgba(255, 255, 255, 0.98);
                }

                .app-navbar-notification-empty {
                    padding: 1.5rem 1rem;
                    text-align: center;
                    color: #64748b;
                }

                .app-navbar-notification-empty i {
                    font-size: 1.35rem;
                    margin-bottom: 0.5rem;
                    color: #22c55e;
                }

                .app-navbar-notification-item {
                    display: block;
                    padding: 0.95rem 1rem;
                    text-decoration: none;
                    color: inherit;
                    border-bottom: 1px solid rgba(226, 232, 240, 0.7);
                    transition: background 0.2s ease;
                }

                .app-navbar-notification-item:last-child {
                    border-bottom: 0;
                }

                .app-navbar-notification-item:hover {
                    background: #f8fbff;
                }

                .app-navbar-notification-item.is-unread {
                    background: linear-gradient(90deg, rgba(224, 242, 254, 0.55) 0%, rgba(255, 255, 255, 0.98) 55%);
                }

                .app-navbar-notification-row {
                    display: grid;
                    grid-template-columns: 2.5rem minmax(0, 1fr);
                    gap: 0.75rem;
                    align-items: start;
                }

                .app-navbar-notification-icon {
                    width: 2.5rem;
                    height: 2.5rem;
                    border-radius: 14px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1rem;
                    background: rgba(14, 165, 233, 0.12);
                    color: #0284c7;
                }

                .app-navbar-notification-icon.is-warning {
                    background: rgba(245, 158, 11, 0.14);
                    color: #d97706;
                }

                .app-navbar-notification-icon.is-success {
                    background: rgba(34, 197, 94, 0.14);
                    color: #16a34a;
                }

                .app-navbar-notification-icon.is-danger {
                    background: rgba(239, 68, 68, 0.14);
                    color: #dc2626;
                }

                .app-navbar-notification-copy {
                    min-width: 0;
                }

                .app-navbar-notification-copy strong {
                    display: flex;
                    align-items: center;
                    gap: 0.45rem;
                    font-size: 0.9rem;
                    color: #0f172a;
                    line-height: 1.35;
                }

                .app-navbar-notification-copy p {
                    margin: 0.35rem 0 0;
                    color: #475569;
                    font-size: 0.83rem;
                    line-height: 1.45;
                }

                .app-navbar-notification-meta {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.4rem;
                    margin-top: 0.55rem;
                }

                .app-navbar-notification-chip {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.3rem;
                    border-radius: 999px;
                    background: #f8fafc;
                    border: 1px solid rgba(226, 232, 240, 0.9);
                    padding: 0.22rem 0.55rem;
                    font-size: 0.72rem;
                    font-weight: 700;
                    color: #475569;
                }

                .app-navbar-notification-unread-dot {
                    width: 0.5rem;
                    height: 0.5rem;
                    border-radius: 999px;
                    background: #0ea5e9;
                    flex-shrink: 0;
                }

                .app-navbar-notification-foot a {
                    font-weight: 700;
                    text-decoration: none;
                }

                @media (max-width: 991.98px) {
                    .app-navbar-notification-menu {
                        width: min(360px, calc(100vw - 1.25rem)) !important;
                        min-width: min(360px, calc(100vw - 1.25rem)) !important;
                        margin-top: 0.85rem !important;
                    }
                }
            </style>
            
            <div class="app-navbar-wrap">
                <nav class="navbar navbar-expand-lg app-navbar-shell" aria-label="Main Navigation">
                    <a class="navbar-brand app-navbar-brand me-lg-4" href="${user ? (user.role === 'admin' ? '/admin/dashboard' : (user.role === 'worker' ? '/worker/dashboard' : '/customer/home')) : '/customer/home'}">
                        <span class="app-navbar-brand-mark">
                            <img src="/assets/images/logontu.png" alt="Logo NTU">
                        </span>
                        <span class="app-navbar-brand-text">THỢ <span>NTU</span></span>
                    </a>

                    <div class="app-navbar-topbar-actions">
                        ${mobileNotificationHtml}
                        ${mobileUserDropdownHtml}
                        <button class="navbar-toggler border-0 shadow-none bg-light rounded-circle p-2 app-navbar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon" style="width: 1.25em; height: 1.25em;"></span>
                        </button>
                    </div>

                    <div class="collapse navbar-collapse" id="navbarMain">
                        <ul class="navbar-nav mx-auto app-navbar-menu-pill">
                            ${centerMenuHtml}
                        </ul>
                        ${rightMenuHtml}
                    </div>
                </nav>
            </div>
            <div class="app-navbar-spacer" aria-hidden="true"></div>
        `;
    }

    setupEvents() {
        this.cleanupEvents();
        this.clearNotificationPolling();

        [
            ['#navbarUserDropdownDesktop', '#navbarUserMenuDesktop'],
            ['#navbarUserDropdownMobile', '#navbarUserMenuMobile'],
        ].forEach(([buttonSelector, menuSelector]) => {
            const btnDropdown = this.querySelector(buttonSelector);
            const menuDropdown = this.querySelector(menuSelector);

            if (!btnDropdown || !menuDropdown) {
                return;
            }

            const handleDropdownClick = (event) => {
                event.stopPropagation();
                this.querySelectorAll('.app-navbar-dropdown.show').forEach((menu) => {
                    if (menu !== menuDropdown) {
                        menu.classList.remove('show');
                    }
                });
                menuDropdown.classList.toggle('show');
            };

            const handleDocumentClick = (event) => {
                if (!menuDropdown.contains(event.target) && !btnDropdown.contains(event.target)) {
                    menuDropdown.classList.remove('show');
                }
            };

            btnDropdown.addEventListener('click', handleDropdownClick);
            document.addEventListener('click', handleDocumentClick);
            this.cleanupFns.push(() => btnDropdown.removeEventListener('click', handleDropdownClick));
            this.cleanupFns.push(() => document.removeEventListener('click', handleDocumentClick));
        });

        this.querySelectorAll('[data-navbar-logout]').forEach((btnLogout) => {
            const handleLogout = () => logout();
            btnLogout.addEventListener('click', handleLogout);
            this.cleanupFns.push(() => btnLogout.removeEventListener('click', handleLogout));
        });

        const btnCustomerBooking = this.querySelector('#btnCustomerBooking');
        if (btnCustomerBooking) {
            const handleCustomerBooking = () => {
                window.location.href = '/customer/booking';
            };
            btnCustomerBooking.addEventListener('click', handleCustomerBooking);
            this.cleanupFns.push(() => btnCustomerBooking.removeEventListener('click', handleCustomerBooking));
        }

        const user = getCurrentUser();
        if (user) {
            this.setupNotifications(user);
        }
    }

    cleanupEvents() {
        this.cleanupFns.forEach((cleanup) => cleanup());
        this.cleanupFns = [];
    }

    clearNotificationPolling() {
        if (this.notificationBootTimeoutId) {
            window.clearTimeout(this.notificationBootTimeoutId);
            this.notificationBootTimeoutId = null;
        }

        if (this.notificationPollId) {
            window.clearInterval(this.notificationPollId);
            this.notificationPollId = null;
        }
    }

    setupNotificationsLegacy() {
        const btnNotification = this.querySelector('#btnNotification');
        const notificationMenu = this.querySelector('#notificationMenu');
        const badge = this.querySelector('#notificationBadge');
        const list = this.querySelector('#notificationList');
        const btnMarkAllRead = this.querySelector('#btnMarkAllRead');
        let currentCount = 0;

        if (!btnNotification || !notificationMenu || !badge || !list) {
            return;
        }

        const handleNotificationClick = (event) => {
            event.stopPropagation();
            notificationMenu.classList.toggle('show');
        };

        const handleDocumentClick = (event) => {
            if (!notificationMenu.contains(event.target) && !btnNotification.contains(event.target)) {
                notificationMenu.classList.remove('show');
            }
        };

        btnNotification.addEventListener('click', handleNotificationClick);
        document.addEventListener('click', handleDocumentClick);
        this.cleanupFns.push(() => btnNotification.removeEventListener('click', handleNotificationClick));
        this.cleanupFns.push(() => document.removeEventListener('click', handleDocumentClick));

        const fetchNotifs = async () => {
            try {
                const res = await callApi('/notifications/unread');
                if (!res.ok || !res.data) {
                    return;
                }

                const count = res.data.unread_count || 0;
                const notifs = Array.isArray(res.data.notifications) ? res.data.notifications : [];

                if (count > 0) {
                    badge.classList.remove('d-none');
                    badge.innerText = count > 99 ? '99+' : count;

                    if (count > currentCount) {
                        showToast('Bạn có đơn đặt lịch mới từ khách hàng!', 'success');
                        try {
                            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                            const oscillator = audioCtx.createOscillator();
                            const gainNode = audioCtx.createGain();
                            oscillator.connect(gainNode);
                            gainNode.connect(audioCtx.destination);
                            oscillator.type = 'sine';
                            oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
                            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
                            gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
                            oscillator.start();
                            oscillator.stop(audioCtx.currentTime + 0.5);
                        } catch (error) {
                            console.error('Audio notification failed', error);
                        }
                    }
                } else {
                    badge.classList.add('d-none');
                }

                currentCount = count;

                if (notifs.length > 0) {
                    list.innerHTML = notifs.map((notification) => {
                        const data = notification.data || {};
                        return `
                            <a href="/worker/dashboard" class="dropdown-item p-3 border-bottom notification-item bg-white" data-id="${notification.id}" style="white-space: normal; transition: background 0.2s;">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle text-primary flex-shrink-0">
                                        <i class="fas fa-calendar-check mt-1"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1 text-dark fw-bold lh-sm border-0" style="font-size: 0.9rem;">${this.escapeHtml(data.message || 'Thông báo mới')}</p>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-clock text-warning me-1"></i>${data.thoi_gian_hen ? this.escapeHtml(data.thoi_gian_hen.substring(0, 16)) : 'Sớm nhất'}</span>
                                            <small class="text-primary fw-bold" style="font-size: 0.75rem;">${this.escapeHtml(data.dich_vu_name || '')}</small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        `;
                    }).join('');

                    this.querySelectorAll('.notification-item').forEach((item) => {
                        item.addEventListener('click', async (event) => {
                            event.preventDefault();
                            const id = item.getAttribute('data-id');
                            await callApi(`/notifications/${id}/read`, 'POST');
                            window.location.href = item.getAttribute('href');
                        });
                    });
                } else {
                    list.innerHTML = '<div class="p-4 text-center text-muted"><i class="fas fa-check-circle mb-2 fs-3 text-success opacity-50"></i><br><small>Bạn đã xem hết thông báo!</small></div>';
                }
            } catch (error) {
                console.error('Polling error', error);
            }
        };

        if (btnMarkAllRead) {
            const handleMarkAllRead = async (event) => {
                event.stopPropagation();
                try {
                    await callApi('/notifications/read-all', 'POST');
                    fetchNotifs();
                } catch (error) {
                    console.error('Mark all read failed', error);
                }
            };

            btnMarkAllRead.addEventListener('click', handleMarkAllRead);
            this.cleanupFns.push(() => btnMarkAllRead.removeEventListener('click', handleMarkAllRead));
        }

        this.notificationBootTimeoutId = window.setTimeout(() => {
            fetchNotifs();
            this.notificationPollId = window.setInterval(fetchNotifs, 10000);
        }, 1000);
    }

    buildNotificationDropdownHtml({ suffix, viewAllHref, viewAllLabel, wrapClass = '' }) {
        return `
            <div class="dropdown app-navbar-notification-wrap ${wrapClass}">
                <button class="btn position-relative p-0 border-0 shadow-none app-navbar-notification-trigger" type="button" id="btnNotification${suffix}" aria-label="Thông báo đơn hàng">
                    <i class="fas fa-bell"></i>
                    <span class="app-navbar-notification-badge d-none" id="notificationBadge${suffix}">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end border-0 app-navbar-dropdown app-navbar-notification-menu" id="notificationMenu${suffix}" style="position: absolute; top: 100%; right: 0;">
                    <div class="app-navbar-notification-head">
                        <div class="app-navbar-notification-title">
                            <strong>Thông báo đơn hàng</strong>
                            <small>Cập nhật mới nhất của đơn hàng</small>
                        </div>
                        <button class="app-navbar-mark-read" id="btnMarkAllRead${suffix}" type="button">Đã đọc hết</button>
                    </div>
                    <div class="app-navbar-notification-list" id="notificationList${suffix}">
                        <div class="app-navbar-notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <div>Chưa có thông báo nào.</div>
                        </div>
                    </div>
                    <div class="app-navbar-notification-foot">
                        <a href="${viewAllHref}">${viewAllLabel}</a>
                    </div>
                </div>
            </div>
        `;
    }

    resolveNotificationOverview(user) {
        if (user?.role === 'worker') {
            return { href: '/worker/jobs', label: 'Xem danh sách đơn' };
        }

        if (user?.role === 'admin') {
            return { href: '/admin/bookings', label: 'Xem đơn hàng' };
        }

        return { href: '/customer/my-bookings', label: 'Xem đơn của tôi' };
    }

    setupNotifications(user) {
        this.restoreNotificationToastState(user);

        const controls = ['Desktop', 'Mobile']
            .map((suffix) => ({
                suffix,
                button: this.querySelector(`#btnNotification${suffix}`),
                menu: this.querySelector(`#notificationMenu${suffix}`),
                badge: this.querySelector(`#notificationBadge${suffix}`),
                list: this.querySelector(`#notificationList${suffix}`),
                markAllButton: this.querySelector(`#btnMarkAllRead${suffix}`),
            }))
            .filter((control) => control.button && control.menu && control.badge && control.list);
        let currentCount = 0;

        if (!controls.length) {
            return;
        }

        controls.forEach((control) => {
            const handleNotificationClick = (event) => {
                event.stopPropagation();
                this.querySelectorAll('.app-navbar-dropdown.show').forEach((menu) => {
                    if (menu !== control.menu) {
                        menu.classList.remove('show');
                    }
                });
                control.menu.classList.toggle('show');
                control.button.classList.toggle('is-active', control.menu.classList.contains('show'));
            };

            const handleDocumentClick = (event) => {
                if (!control.menu.contains(event.target) && !control.button.contains(event.target)) {
                    control.menu.classList.remove('show');
                    control.button.classList.remove('is-active');
                }
            };

            control.button.addEventListener('click', handleNotificationClick);
            document.addEventListener('click', handleDocumentClick);
            this.cleanupFns.push(() => control.button.removeEventListener('click', handleNotificationClick));
            this.cleanupFns.push(() => document.removeEventListener('click', handleDocumentClick));

            if (control.markAllButton) {
                const handleMarkAllRead = async (event) => {
                    event.stopPropagation();
                    try {
                        await callApi('/notifications/read-all', 'POST');
                        currentCount = 0;
                        await fetchNotifications();
                    } catch (error) {
                        console.error('Mark all read failed', error);
                    }
                };

                control.markAllButton.addEventListener('click', handleMarkAllRead);
                this.cleanupFns.push(() => control.markAllButton.removeEventListener('click', handleMarkAllRead));
            }
        });

        const fetchNotifications = async () => {
            try {
                const res = await callApi('/notifications/unread');
                if (!res.ok || !res.data) {
                    return;
                }

                const unreadCount = Number(res.data.unread_count || 0);
                const notifications = Array.isArray(res.data.notifications) ? res.data.notifications : [];
                const normalizedNotifications = notifications.map((notification) => ({
                    ...notification,
                    data: notification.data || {},
                    link: this.normalizeNotificationLink(notification.data?.link, user),
                }));

                controls.forEach((control) => {
                    if (unreadCount > 0) {
                        control.badge.classList.remove('d-none');
                        control.badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                    } else {
                        control.badge.classList.add('d-none');
                    }

                    control.list.innerHTML = this.renderNotificationItems(normalizedNotifications);
                });

                this.querySelectorAll('[data-notification-item]').forEach((item) => {
                    item.addEventListener('click', async (event) => {
                        event.preventDefault();
                        await this.markNotificationAsRead(item.getAttribute('data-notification-id'));
                        const destination = item.getAttribute('href');
                        if (destination) {
                            window.location.href = destination;
                        }
                    });
                });

                normalizedNotifications
                    .filter((notification) => !notification.read_at && !this.notificationToastShownIds.has(notification.id))
                    .slice(0, currentCount === 0 ? 3 : 2)
                    .forEach((notification) => {
                        this.notificationToastShownIds.add(notification.id);
                        this.persistNotificationToastState(user);
                        this.showNotificationToast(notification);
                    });

                currentCount = unreadCount;
            } catch (error) {
                console.error('Polling error', error);
            }
        };

        this.notificationBootTimeoutId = window.setTimeout(() => {
            fetchNotifications();
            this.notificationPollId = window.setInterval(fetchNotifications, 10000);
        }, 1000);
    }

    renderNotificationItems(notifications) {
        if (!notifications.length) {
            return `
                <div class="app-navbar-notification-empty">
                    <i class="fas fa-check-circle"></i>
                    <div>Bạn đã xem hết thông báo.</div>
                </div>
            `;
        }

        return notifications.map((notification) => {
            const data = notification.data || {};
            const visual = this.getNotificationVisual(data.type || 'booking_status_updated');
            const chips = [
                data.booking_code || (data.booking_id ? `#${data.booking_id}` : ''),
                data.status_label || '',
                data.service_name || data.dich_vu_name || '',
                this.formatNotificationTime(notification.created_at),
            ].filter(Boolean).slice(0, 3);

            return `
                <a
                    href="${this.escapeHtml(notification.link)}"
                    class="app-navbar-notification-item${notification.read_at ? '' : ' is-unread'}"
                    data-notification-item="1"
                    data-notification-id="${this.escapeHtml(notification.id || '')}">
                    <div class="app-navbar-notification-row">
                        <span class="app-navbar-notification-icon ${visual.className}">
                            <i class="${visual.icon}"></i>
                        </span>
                        <div class="app-navbar-notification-copy">
                            <strong>
                                ${notification.read_at ? '' : '<span class="app-navbar-notification-unread-dot"></span>'}
                                ${this.escapeHtml(data.title || 'Thông báo mới')}
                            </strong>
                            <p>${this.escapeHtml(data.message || 'Hệ thống vừa cập nhật đơn đặt lịch của bạn.')}</p>
                            <div class="app-navbar-notification-meta">
                                ${chips.map((chip) => `<span class="app-navbar-notification-chip">${this.escapeHtml(chip)}</span>`).join('')}
                            </div>
                        </div>
                    </div>
                </a>
            `;
        }).join('');
    }

    normalizeNotificationLink(link, user) {
        if (typeof link === 'string' && link.trim() !== '') {
            return link;
        }

        return this.resolveNotificationOverview(user).href;
    }

    async markNotificationAsRead(notificationId) {
        if (!notificationId) {
            return;
        }

        try {
            await callApi(`/notifications/${notificationId}/read`, 'POST');
        } catch (error) {
            console.error('Mark notification as read failed', error);
        }
    }

    showNotificationToast(notification) {
        const data = notification.data || {};
        const toastText = [data.title || 'Thông báo mới', data.message || 'Đơn đặt lịch của bạn vừa được cập nhật.']
            .filter(Boolean)
            .join(' - ');

        if (typeof Toastify === 'undefined') {
            return;
        }

        Toastify({
            text: toastText,
            duration: 6000,
            close: true,
            gravity: 'bottom',
            position: 'right',
            stopOnFocus: true,
            onClick: async () => {
                await this.markNotificationAsRead(notification.id);
                if (notification.link) {
                    window.location.href = notification.link;
                }
            },
            style: {
                background: 'linear-gradient(135deg, #0f172a 0%, #0284c7 100%)',
                borderRadius: '16px',
                fontFamily: 'Roboto, sans-serif',
                fontWeight: '600',
                boxShadow: '0 18px 36px rgba(15, 23, 42, 0.28)',
            },
        }).showToast();
    }

    getNotificationToastStorageKey(user) {
        return `app-navbar-notification-toast-seen:${user?.id || 'guest'}`;
    }

    restoreNotificationToastState(user) {
        try {
            const rawValue = sessionStorage.getItem(this.getNotificationToastStorageKey(user));
            const parsed = rawValue ? JSON.parse(rawValue) : [];
            this.notificationToastShownIds = new Set(Array.isArray(parsed) ? parsed : []);
        } catch (error) {
            this.notificationToastShownIds = new Set();
        }
    }

    persistNotificationToastState(user) {
        try {
            sessionStorage.setItem(
                this.getNotificationToastStorageKey(user),
                JSON.stringify(Array.from(this.notificationToastShownIds).slice(-200))
            );
        } catch (error) {
            console.error('Persist notification toast state failed', error);
        }
    }

    getNotificationVisual(type) {
        switch (type) {
        case 'new_booking':
            return { icon: 'fas fa-briefcase', className: 'is-warning' };
        case 'booking_completed':
            return { icon: 'fas fa-circle-check', className: 'is-success' };
        case 'booking_cancelled':
            return { icon: 'fas fa-ban', className: 'is-danger' };
        case 'booking_payment_requested':
            return { icon: 'fas fa-wallet', className: 'is-warning' };
        case 'booking_in_progress':
            return { icon: 'fas fa-screwdriver-wrench', className: '' };
        default:
            return { icon: 'fas fa-bell', className: '' };
        }
    }

    formatNotificationTime(value) {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return date.toLocaleString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    getInitials(name = '') {
        const normalized = String(name || '').trim();
        if (!normalized) {
            return 'U';
        }

        return normalized
            .split(/\s+/)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join('');
    }

    resolveAvatarUrl(avatar) {
        if (!avatar) {
            return '';
        }

        if (/^https?:\/\//i.test(avatar) || avatar.startsWith('/')) {
            return avatar;
        }

        return `/storage/${avatar}`;
    }

    escapeHtml(value = '') {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

if (!customElements.get('app-navbar')) {
    customElements.define('app-navbar', AppNavbar);
}
