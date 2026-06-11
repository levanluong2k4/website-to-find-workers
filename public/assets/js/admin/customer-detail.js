import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const root = document.getElementById('customer360App');
    const customerId = root?.dataset.customerId;

    if (!root || !customerId) {
        return;
    }

    const refs = {
        title: document.getElementById('customer360Title'),
        subtitle: document.getElementById('customer360Subtitle'),
        actions: document.getElementById('customer360HeaderActions'),
        profile: document.getElementById('customer360Profile'),
        stats: document.getElementById('customer360Stats'),
        recentBookings: document.getElementById('customer360RecentBookings'),
        timeline: document.getElementById('customer360Timeline'),
        notes: document.getElementById('customer360Notes'),
        reviews: document.getElementById('customer360Reviews'),
    };

    const currency = new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    });
    const number = new Intl.NumberFormat('vi-VN');

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const initials = (name) => String(name || 'KH')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'KH';

    const formatMoney = (value) => currency.format(Number(value || 0));
    const formatNumber = (value) => number.format(Number(value || 0));

    const buildAvatar = (profile) => {
        if (profile?.avatar) {
            return `
                <div class="customer-360-avatar">
                    <img src="${escapeHtml(profile.avatar)}" alt="${escapeHtml(profile.name)}" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                    <span style="display:none;">${escapeHtml(initials(profile.name))}</span>
                </div>
            `;
        }

        return `<div class="customer-360-avatar">${escapeHtml(initials(profile?.name))}</div>`;
    };

    const buildEmpty = (message) => `<div class="customer-360-empty">${escapeHtml(message)}</div>`;

    const toneClass = (tone) => {
        switch (tone) {
            case 'success':
                return 'customer-360-pill--success';
            case 'warning':
                return 'customer-360-pill--warning';
            case 'danger':
                return 'customer-360-pill--danger';
            case 'muted':
                return 'customer-360-pill--muted';
            default:
                return 'customer-360-pill--info';
        }
    };

    const relationshipTone = (status) => {
        switch (status) {
            case 'active_booking':
                return 'info';
            case 'new_customer':
                return 'warning';
            case 'inactive':
                return 'muted';
            default:
                return 'success';
        }
    };

    const renderHeader = (profile) => {
        const subtitleParts = [
            profile?.relationship_label || 'Khách hàng',
            profile?.joined_label ? `Tham gia ${profile.joined_label}` : null,
            profile?.phone || 'Chưa có SĐT',
        ].filter(Boolean);

        refs.title.textContent = profile?.name || 'Khách hàng';
        refs.subtitle.textContent = subtitleParts.join(' - ');
        refs.actions.innerHTML = `
            ${profile?.history_url ? `<a class="customer-360-action customer-360-action--primary" href="${escapeHtml(profile.history_url)}">Lịch sử đơn</a>` : ''}
            ${profile?.feedback_url ? `<a class="customer-360-action" href="${escapeHtml(profile.feedback_url)}">Khiếu nại / phản hồi</a>` : ''}
            ${profile?.phone ? `<a class="customer-360-action" href="tel:${escapeHtml(profile.phone)}">Gọi khách</a>` : ''}
        `;
    };

    const renderProfile = (profile) => {
        refs.profile.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Thông tin khách hàng</h2>
                    <p class="customer-360-panel__copy">Thông tin liên hệ cơ bản và trạng thái hiện tại của khách hàng.</p>
                </div>
            </div>
            <div class="customer-360-profile-card">
                ${buildAvatar(profile)}
                <div>
                    <h3 class="customer-360-profile-name">${escapeHtml(profile?.name || 'Khách hàng')}</h3>
                    <div class="customer-360-profile-meta">
                        ${escapeHtml(profile?.code || '--')}<br>
                        ${escapeHtml(profile?.phone || 'Chưa có SĐT')}<br>
                        ${escapeHtml(profile?.email || 'Chưa có email')}
                    </div>
                    <div class="customer-360-pill-row">
                        <span class="customer-360-pill ${toneClass(relationshipTone(profile?.relationship_status))}">${escapeHtml(profile?.relationship_label || 'Khách hàng')}</span>
                    </div>
                </div>
            </div>
            <div class="customer-360-profile-grid">
                <div class="customer-360-profile-field">
                    <span class="customer-360-field-label">Ngày tham gia</span>
                    <div class="customer-360-field-value">${escapeHtml(profile?.joined_label || '--')}</div>
                </div>
                <div class="customer-360-profile-field">
                    <span class="customer-360-field-label">Lần đặt gần nhất</span>
                    <div class="customer-360-field-value">${escapeHtml(profile?.last_booking_service || 'Chưa có lịch sử đặt dịch vụ')}</div>
                </div>
                <div class="customer-360-profile-field">
                    <span class="customer-360-field-label">Địa chỉ tài khoản</span>
                    <div class="customer-360-field-value">${escapeHtml(profile?.default_address || 'Chưa có địa chỉ')}</div>
                </div>
                <div class="customer-360-profile-field">
                    <span class="customer-360-field-label">Địa chỉ gần nhất</span>
                    <div class="customer-360-field-value">${escapeHtml(profile?.latest_address || 'Chưa có địa chỉ')}</div>
                </div>
            </div>
        `;
    };

    const renderStats = (summary) => {
        const stats = [
            ['Tổng đơn', formatNumber(summary?.order_count || 0), 'Tổng số booking của khách'],
            ['Đang xử lý', formatNumber(summary?.active_booking_count || 0), 'Đơn đang trong quá trình thực hiện'],
            ['Hoàn thành', formatNumber(summary?.completed_booking_count || 0), 'Đơn đã hoàn tất'],
            ['Đã hủy', formatNumber(summary?.canceled_booking_count || 0), 'Số đơn đã hủy'],
            ['Tổng chi tiêu', formatMoney(summary?.total_spent || 0), 'Tính trên đơn hoàn thành'],
            ['Đánh giá', summary?.average_rating === null ? '--' : `${summary.average_rating}/5`, `${formatNumber(summary?.total_reviews || 0)} review`],
        ];

        refs.stats.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Thống kê nhanh</h2>
                    <p class="customer-360-panel__copy">Tổng hợp nhanh để admin nắm được tần suất sử dụng dịch vụ của khách.</p>
                </div>
            </div>
            <div class="customer-360-stats-grid">
                ${stats.map(([label, value, meta]) => `
                    <article class="customer-360-stat-tile">
                        <div>
                            <span class="customer-360-stat-label">${escapeHtml(label)}</span>
                            <div class="customer-360-stat-value">${escapeHtml(value)}</div>
                        </div>
                        <div class="customer-360-stat-meta">${escapeHtml(meta)}</div>
                    </article>
                `).join('')}
            </div>
        `;
    };

    const renderRecentBookings = (bookings) => {
        refs.recentBookings.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Lịch sử booking</h2>
                    <p class="customer-360-panel__copy">Các đơn gần đây của khách hàng để admin tra cứu nhanh.</p>
                </div>
            </div>
            ${Array.isArray(bookings) && bookings.length ? `
                <ul class="customer-360-booking-list">
                    ${bookings.map((booking) => `
                        <li class="customer-360-booking-item">
                            <div class="customer-360-booking-top">
                                <div>
                                    <div class="customer-360-booking-code">${escapeHtml(booking.code || '--')}</div>
                                    <div class="customer-360-booking-name">${escapeHtml(booking.service_label || 'Đơn đặt lịch')}</div>
                                </div>
                                <span class="customer-360-pill ${toneClass(booking.status_tone || 'info')}">${escapeHtml(booking.status_label || '--')}</span>
                            </div>
                            <div class="customer-360-booking-meta">
                                ${escapeHtml(booking.schedule_label || '--')}<br>
                                ${escapeHtml(booking.worker_name || 'Chưa gắn thợ')}<br>
                                ${escapeHtml(booking.payment_label || 'Chưa cập nhật thanh toán')}
                            </div>
                            <div class="customer-360-booking-foot">
                                <span class="customer-360-booking-amount">${formatMoney(booking.total_amount || 0)}</span>
                                <a class="customer-360-link-inline" href="${escapeHtml(booking.detail_url || '#')}">Xem chi tiết đơn</a>
                            </div>
                        </li>
                    `).join('')}
                </ul>
            ` : buildEmpty('Khách hàng này chưa có booking nào.')}
        `;
    };

    const renderNotes = (payload) => {
        const notes = Array.isArray(payload?.notes) ? payload.notes : [];

        refs.notes.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Ghi chú nội bộ</h2>
                    <p class="customer-360-panel__copy">Admin lưu lại thông tin cần nhớ như đã gọi, cần liên hệ lại hoặc lưu ý vận hành.</p>
                </div>
            </div>
            <form class="customer-360-inline-form" id="customer360NoteForm">
                <select class="customer-360-select" id="customer360NoteCategory">
                    <option value="van_hanh">Vận hành</option>
                    <option value="cskh">Chăm sóc</option>
                    <option value="ke_toan">Kế toán</option>
                </select>
                <textarea class="customer-360-textarea" id="customer360NoteContent" placeholder="Nhập ghi chú nội bộ cho khách hàng này..."></textarea>
                <div class="customer-360-form-actions">
                    <button type="submit" class="customer-360-button">Thêm ghi chú</button>
                </div>
            </form>
            <div class="customer-360-note-list">
                ${notes.length ? notes.map((note) => `
                    <article class="customer-360-note">
                        <div class="customer-360-note-top">
                            <div>
                                <span class="customer-360-pill ${toneClass(note.category === 'ke_toan' ? 'warning' : (note.category === 'cskh' ? 'info' : 'muted'))}">${escapeHtml(note.category_label || '--')}</span>
                            </div>
                            <div class="customer-360-note-meta">${escapeHtml(note.created_label || '--')}<br>${escapeHtml(note.admin_name || 'Admin')}</div>
                        </div>
                        <div class="customer-360-note-copy">${escapeHtml(note.content || '')}</div>
                    </article>
                `).join('') : '<div class="customer-360-empty">Chưa có ghi chú nội bộ nào cho khách hàng này.</div>'}
            </div>
        `;

        const noteForm = document.getElementById('customer360NoteForm');
        const noteCategory = document.getElementById('customer360NoteCategory');
        const noteContent = document.getElementById('customer360NoteContent');

        noteForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const content = noteContent?.value?.trim();
            if (!content) {
                showToast('Nhập nội dung ghi chú trước khi lưu', 'error');
                return;
            }

            try {
                const response = await callApi(`/admin/customers/${payload?.profile?.id}/notes`, 'POST', {
                    category: noteCategory?.value || 'van_hanh',
                    content,
                });

                if (!response?.ok) {
                    throw new Error(response?.data?.message || 'Không thể lưu ghi chú');
                }

                noteContent.value = '';
                noteCategory.value = 'van_hanh';
                await loadCustomerDetail();
                showToast(response.data?.message || 'Đã thêm ghi chú');
            } catch (error) {
                showToast(error.message || 'Không thể lưu ghi chú', 'error');
            }
        });
    };

    const buildRatingLabel = (rating) => `${Math.max(0, Math.min(5, Math.round(Number(rating || 0))))}/5 sao`;

    const renderReviews = (reviews) => {
        refs.reviews.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Phản hồi gần đây</h2>
                    <p class="customer-360-panel__copy">Review và nhận xét gần đây của khách hàng để admin nắm tình hình nhanh.</p>
                </div>
            </div>
            ${Array.isArray(reviews) && reviews.length ? `
                <ul class="customer-360-review-list">
                    ${reviews.map((review) => `
                        <li class="customer-360-review">
                            <div class="customer-360-review-top">
                                <div>
                                    <div class="customer-360-review-code">${escapeHtml(review.booking_code || '--')}</div>
                                    <div class="customer-360-review-service">${escapeHtml(review.service_label || 'Phản hồi khách hàng')}</div>
                                </div>
                                <span class="customer-360-pill ${toneClass(Number(review.rating || 0) <= 2 ? 'warning' : 'success')}">${escapeHtml(buildRatingLabel(review.rating))}</span>
                            </div>
                            <div class="customer-360-review-meta">${escapeHtml(review.created_label || '--')} - ${escapeHtml(review.worker_name || 'Chưa gắn thợ')}</div>
                            <p class="customer-360-review-quote">${escapeHtml(review.comment || 'Khách không để lại nhận xét.')}</p>
                            <a class="customer-360-link-inline" href="${escapeHtml(review.detail_url || '#')}">Xem đơn liên quan</a>
                        </li>
                    `).join('')}
                </ul>
            ` : buildEmpty('Khách hàng này chưa có phản hồi nào.')}
        `;
    };

    const timelineBadge = (kind) => {
        switch (kind) {
            case 'join':
                return 'TK';
            case 'booking':
                return 'DH';
            case 'schedule':
                return 'LH';
            case 'complete':
                return 'HT';
            case 'cancel':
                return 'HY';
            case 'review':
                return 'DG';
            default:
                return 'KH';
        }
    };

    const renderTimeline = (timeline) => {
        refs.timeline.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Timeline hoạt động</h2>
                    <p class="customer-360-panel__copy">Tóm tắt những mốc quan trọng của khách hàng trên hệ thống.</p>
                </div>
            </div>
            ${Array.isArray(timeline) && timeline.length ? `
                <ul class="customer-360-timeline">
                    ${timeline.map((item) => `
                        <li class="customer-360-timeline-item tone-${escapeHtml(item.tone || 'muted')}">
                            <span class="customer-360-timeline-dot">${escapeHtml(timelineBadge(item.kind))}</span>
                            <div class="customer-360-timeline-title">${escapeHtml(item.title || 'Cập nhật')}</div>
                            <div class="customer-360-timeline-time">${escapeHtml(item.time_label || '--')}</div>
                            <div class="customer-360-timeline-copy">${escapeHtml(item.detail || 'Không có mô tả chi tiết.')}</div>
                            ${item.booking_url ? `<a class="customer-360-link-inline" href="${escapeHtml(item.booking_url)}">Xem đơn liên quan</a>` : ''}
                        </li>
                    `).join('')}
                </ul>
            ` : buildEmpty('Chưa có dữ liệu timeline cho khách hàng này.')}
        `;
    };

    const renderDetail = (payload) => {
        renderHeader(payload?.profile || {});
        renderProfile(payload?.profile || {});
        renderStats(payload?.summary || {});
        renderRecentBookings(payload?.recent_bookings || []);
        renderNotes(payload || {});
        renderReviews(payload?.reviews || []);
        renderTimeline(payload?.timeline || []);
    };

    const renderLoading = () => {
        refs.profile.innerHTML = buildEmpty('Đang tải hồ sơ khách hàng...');
        refs.stats.innerHTML = buildEmpty('Đang tải thống kê...');
        refs.recentBookings.innerHTML = buildEmpty('Đang tải lịch sử booking...');
        refs.notes.innerHTML = buildEmpty('Đang tải ghi chú nội bộ...');
        refs.reviews.innerHTML = buildEmpty('Đang tải phản hồi gần đây...');
        refs.timeline.innerHTML = buildEmpty('Đang tải timeline...');
    };

    const renderError = (message) => {
        refs.title.textContent = 'Không tải được hồ sơ';
        refs.subtitle.textContent = message;
        refs.actions.innerHTML = '';
        refs.profile.innerHTML = buildEmpty(message);
        refs.stats.innerHTML = '';
        refs.recentBookings.innerHTML = '';
        refs.notes.innerHTML = '';
        refs.reviews.innerHTML = '';
        refs.timeline.innerHTML = '';
    };

    async function loadCustomerDetail() {
        renderLoading();

        try {
            const response = await callApi(`/admin/customers/${customerId}`, 'GET');

            if (!response?.ok) {
                throw new Error(response?.data?.message || 'Không thể tải chi tiết khách hàng');
            }

            renderDetail(response.data?.data || {});
        } catch (error) {
            console.error('Load customer detail failed:', error);
            renderError(error.message || 'Không thể tải chi tiết khách hàng');
            showToast(error.message || 'Không thể tải chi tiết khách hàng', 'error');
        }
    }

    loadCustomerDetail();
});
