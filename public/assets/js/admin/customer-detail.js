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
            profile?.relationship_label || 'Khach hang',
            profile?.joined_label ? `Tham gia ${profile.joined_label}` : null,
            profile?.phone || 'Chua co SDT',
        ].filter(Boolean);

        refs.title.textContent = profile?.name || 'Khach hang';
        refs.subtitle.textContent = subtitleParts.join(' - ');
        refs.actions.innerHTML = `
            ${profile?.history_url ? `<a class="customer-360-action customer-360-action--primary" href="${escapeHtml(profile.history_url)}">Lich su don</a>` : ''}
            ${profile?.feedback_url ? `<a class="customer-360-action" href="${escapeHtml(profile.feedback_url)}">Khieu nai / phan hoi</a>` : ''}
            ${profile?.phone ? `<a class="customer-360-action" href="tel:${escapeHtml(profile.phone)}">Goi khach</a>` : ''}
        `;
    };

    const renderProfile = (profile) => {
        refs.profile.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Thong tin khach hang</h2>
                    <p class="customer-360-panel__copy">Thong tin lien he co ban va trang thai hien tai cua khach hang.</p>
                </div>
            </div>
            <div class="customer-360-profile-card">
                ${buildAvatar(profile)}
                <div>
                    <h3 class="customer-360-profile-name">${escapeHtml(profile?.name || 'Khach hang')}</h3>
                    <div class="customer-360-profile-meta">
                        ${escapeHtml(profile?.code || '--')}<br>
                        ${escapeHtml(profile?.phone || 'Chua co SDT')}<br>
                        ${escapeHtml(profile?.email || 'Chua co email')}
                    </div>
                    <div class="customer-360-pill-row">
                        <span class="customer-360-pill ${toneClass(relationshipTone(profile?.relationship_status))}">${escapeHtml(profile?.relationship_label || 'Khach hang')}</span>
                    </div>
                </div>
            </div>
            <div class="customer-360-profile-grid">
                <div class="customer-360-profile-field">
                    <span class="customer-360-field-label">Ngay tham gia</span>
                    <div class="customer-360-field-value">${escapeHtml(profile?.joined_label || '--')}</div>
                </div>
                <div class="customer-360-profile-field">
                    <span class="customer-360-field-label">Lan dat gan nhat</span>
                    <div class="customer-360-field-value">${escapeHtml(profile?.last_booking_service || 'Chua co lich su dat dich vu')}</div>
                </div>
                <div class="customer-360-profile-field">
                    <span class="customer-360-field-label">Dia chi tai khoan</span>
                    <div class="customer-360-field-value">${escapeHtml(profile?.default_address || 'Chua co dia chi')}</div>
                </div>
                <div class="customer-360-profile-field">
                    <span class="customer-360-field-label">Dia chi gan nhat</span>
                    <div class="customer-360-field-value">${escapeHtml(profile?.latest_address || 'Chua co dia chi')}</div>
                </div>
            </div>
        `;
    };

    const renderStats = (summary) => {
        const stats = [
            ['Tong don', formatNumber(summary?.order_count || 0), 'Tong so booking cua khach'],
            ['Dang xu ly', formatNumber(summary?.active_booking_count || 0), 'Don dang trong qua trinh thuc hien'],
            ['Hoan thanh', formatNumber(summary?.completed_booking_count || 0), 'Don da hoan tat'],
            ['Da huy', formatNumber(summary?.canceled_booking_count || 0), 'So don da huy'],
            ['Tong chi tieu', formatMoney(summary?.total_spent || 0), 'Tinh tren don hoan thanh'],
            ['Danh gia', summary?.average_rating === null ? '--' : `${summary.average_rating}/5`, `${formatNumber(summary?.total_reviews || 0)} review`],
        ];

        refs.stats.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Thong ke nhanh</h2>
                    <p class="customer-360-panel__copy">Tong hop nhanh de admin nam duoc tan suat su dung dich vu cua khach.</p>
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
                    <h2 class="customer-360-panel__title">Lich su booking</h2>
                    <p class="customer-360-panel__copy">Cac don gan day cua khach hang de admin tra cuu nhanh.</p>
                </div>
            </div>
            ${Array.isArray(bookings) && bookings.length ? `
                <ul class="customer-360-booking-list">
                    ${bookings.map((booking) => `
                        <li class="customer-360-booking-item">
                            <div class="customer-360-booking-top">
                                <div>
                                    <div class="customer-360-booking-code">${escapeHtml(booking.code || '--')}</div>
                                    <div class="customer-360-booking-name">${escapeHtml(booking.service_label || 'Don dat lich')}</div>
                                </div>
                                <span class="customer-360-pill ${toneClass(booking.status_tone || 'info')}">${escapeHtml(booking.status_label || '--')}</span>
                            </div>
                            <div class="customer-360-booking-meta">
                                ${escapeHtml(booking.schedule_label || '--')}<br>
                                ${escapeHtml(booking.worker_name || 'Chua gan tho')}<br>
                                ${escapeHtml(booking.payment_label || 'Chua cap nhat thanh toan')}
                            </div>
                            <div class="customer-360-booking-foot">
                                <span class="customer-360-booking-amount">${formatMoney(booking.total_amount || 0)}</span>
                                <a class="customer-360-link-inline" href="${escapeHtml(booking.detail_url || '#')}">Xem chi tiet don</a>
                            </div>
                        </li>
                    `).join('')}
                </ul>
            ` : buildEmpty('Khach hang nay chua co booking nao.')}
        `;
    };

    const renderNotes = (payload) => {
        const notes = Array.isArray(payload?.notes) ? payload.notes : [];

        refs.notes.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Ghi chu noi bo</h2>
                    <p class="customer-360-panel__copy">Admin luu lai thong tin can nho nhu da goi, can lien he lai hoac luu y van hanh.</p>
                </div>
            </div>
            <form class="customer-360-inline-form" id="customer360NoteForm">
                <select class="customer-360-select" id="customer360NoteCategory">
                    <option value="van_hanh">Van hanh</option>
                    <option value="cskh">Cham soc</option>
                    <option value="ke_toan">Ke toan</option>
                </select>
                <textarea class="customer-360-textarea" id="customer360NoteContent" placeholder="Nhap ghi chu noi bo cho khach hang nay..."></textarea>
                <div class="customer-360-form-actions">
                    <button type="submit" class="customer-360-button">Them ghi chu</button>
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
                `).join('') : '<div class="customer-360-empty">Chua co ghi chu noi bo nao cho khach hang nay.</div>'}
            </div>
        `;

        const noteForm = document.getElementById('customer360NoteForm');
        const noteCategory = document.getElementById('customer360NoteCategory');
        const noteContent = document.getElementById('customer360NoteContent');

        noteForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const content = noteContent?.value?.trim();
            if (!content) {
                showToast('Nhap noi dung ghi chu truoc khi luu', 'error');
                return;
            }

            try {
                const response = await callApi(`/admin/customers/${payload?.profile?.id}/notes`, 'POST', {
                    category: noteCategory?.value || 'van_hanh',
                    content,
                });

                if (!response?.ok) {
                    throw new Error(response?.data?.message || 'Khong the luu ghi chu');
                }

                noteContent.value = '';
                noteCategory.value = 'van_hanh';
                await loadCustomerDetail();
                showToast(response.data?.message || 'Da them ghi chu');
            } catch (error) {
                showToast(error.message || 'Khong the luu ghi chu', 'error');
            }
        });
    };

    const buildRatingLabel = (rating) => `${Math.max(0, Math.min(5, Math.round(Number(rating || 0))))}/5 sao`;

    const renderReviews = (reviews) => {
        refs.reviews.innerHTML = `
            <div class="customer-360-panel__head">
                <div>
                    <h2 class="customer-360-panel__title">Phan hoi gan day</h2>
                    <p class="customer-360-panel__copy">Review va nhan xet gan day cua khach hang de admin nam tinh hinh nhanh.</p>
                </div>
            </div>
            ${Array.isArray(reviews) && reviews.length ? `
                <ul class="customer-360-review-list">
                    ${reviews.map((review) => `
                        <li class="customer-360-review">
                            <div class="customer-360-review-top">
                                <div>
                                    <div class="customer-360-review-code">${escapeHtml(review.booking_code || '--')}</div>
                                    <div class="customer-360-review-service">${escapeHtml(review.service_label || 'Phan hoi khach hang')}</div>
                                </div>
                                <span class="customer-360-pill ${toneClass(Number(review.rating || 0) <= 2 ? 'warning' : 'success')}">${escapeHtml(buildRatingLabel(review.rating))}</span>
                            </div>
                            <div class="customer-360-review-meta">${escapeHtml(review.created_label || '--')} - ${escapeHtml(review.worker_name || 'Chua gan tho')}</div>
                            <p class="customer-360-review-quote">${escapeHtml(review.comment || 'Khach khong de lai nhan xet.')}</p>
                            <a class="customer-360-link-inline" href="${escapeHtml(review.detail_url || '#')}">Xem don lien quan</a>
                        </li>
                    `).join('')}
                </ul>
            ` : buildEmpty('Khach hang nay chua co phan hoi nao.')}
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
                    <h2 class="customer-360-panel__title">Timeline hoat dong</h2>
                    <p class="customer-360-panel__copy">Tom tat nhung moc quan trong cua khach hang tren he thong.</p>
                </div>
            </div>
            ${Array.isArray(timeline) && timeline.length ? `
                <ul class="customer-360-timeline">
                    ${timeline.map((item) => `
                        <li class="customer-360-timeline-item tone-${escapeHtml(item.tone || 'muted')}">
                            <span class="customer-360-timeline-dot">${escapeHtml(timelineBadge(item.kind))}</span>
                            <div class="customer-360-timeline-title">${escapeHtml(item.title || 'Cap nhat')}</div>
                            <div class="customer-360-timeline-time">${escapeHtml(item.time_label || '--')}</div>
                            <div class="customer-360-timeline-copy">${escapeHtml(item.detail || 'Khong co mo ta chi tiet.')}</div>
                            ${item.booking_url ? `<a class="customer-360-link-inline" href="${escapeHtml(item.booking_url)}">Xem don lien quan</a>` : ''}
                        </li>
                    `).join('')}
                </ul>
            ` : buildEmpty('Chua co du lieu timeline cho khach hang nay.')}
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
        refs.profile.innerHTML = buildEmpty('Dang tai ho so khach hang...');
        refs.stats.innerHTML = buildEmpty('Dang tai thong ke...');
        refs.recentBookings.innerHTML = buildEmpty('Dang tai lich su booking...');
        refs.notes.innerHTML = buildEmpty('Dang tai ghi chu noi bo...');
        refs.reviews.innerHTML = buildEmpty('Dang tai phan hoi gan day...');
        refs.timeline.innerHTML = buildEmpty('Dang tai timeline...');
    };

    const renderError = (message) => {
        refs.title.textContent = 'Khong tai duoc ho so';
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
                throw new Error(response?.data?.message || 'Khong the tai chi tiet khach hang');
            }

            renderDetail(response.data?.data || {});
        } catch (error) {
            console.error('Load customer detail failed:', error);
            renderError(error.message || 'Khong the tai chi tiet khach hang');
            showToast(error.message || 'Khong the tai chi tiet khach hang', 'error');
        }
    }

    loadCustomerDetail();
});
