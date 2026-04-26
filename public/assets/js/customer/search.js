import { callApi } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    const workersContainer = document.getElementById('workersContainer');
    const resultsCount = document.getElementById('resultsCount');
    const resultsSubline = document.getElementById('resultsSubline');
    const resultsCategorySpotlight = document.getElementById('resultsCategorySpotlight');
    const resultsScheduleSpotlight = document.getElementById('resultsScheduleSpotlight');
    const categoryFiltersList = document.getElementById('categoryFiltersList');
    const paginationContainer = document.getElementById('paginationContainer');
    const activeFilterChips = document.getElementById('activeFilterChips');
    const activeFiltersSection = document.getElementById('activeFiltersSection');
    const sortRadios = document.querySelectorAll('input[name="sortOrder"]');
    const filterDate = document.getElementById('filterDate');
    const filterTimeSlot = document.getElementById('filterTimeSlot');
    const btnApplyTimeFilter = document.getElementById('btnApplyTimeFilter');
    const btnResetSearchFilters = document.getElementById('btnResetSearchFilters');
    const DEFAULT_SORT = 'jobs';
    const DEFAULT_BOOKING_TIME_SLOTS = ['08:00-10:00', '10:00-12:00', '12:00-14:00', '14:00-17:00'];
    const searchParams = new URLSearchParams(window.location.search);
    let bookingTimeSlots = [...DEFAULT_BOOKING_TIME_SLOTS];
    let categoriesCache = [];

    renderTimeSlotOptions();
    syncControlsFromParams();
    renderActiveFilterChips();
    updateContextualCopy();
    bindStaticEvents();
    loadBookingTimeSlots();
    fetchCategories();
    fetchWorkers();

    function normalizeTimeSlotValue(value) {
        return String(value || '').replace(/\s+/g, '');
    }

    function timeToMinutes(value) {
        const matched = String(value || '').trim().match(/^(\d{2}):(\d{2})$/);
        if (!matched) return null;

        const hour = Number(matched[1]);
        const minute = Number(matched[2]);
        if (!Number.isInteger(hour) || !Number.isInteger(minute) || hour < 0 || hour > 23 || minute < 0 || minute > 59) {
            return null;
        }

        return (hour * 60) + minute;
    }

    function getBookingTimeSlots() {
        const normalizedSlots = (Array.isArray(bookingTimeSlots) && bookingTimeSlots.length
            ? bookingTimeSlots
            : DEFAULT_BOOKING_TIME_SLOTS)
            .map((slot) => normalizeTimeSlotValue(slot))
            .filter(Boolean)
            .filter((slot, index, items) => items.indexOf(slot) === index)
            .sort((left, right) => {
                const leftStart = timeToMinutes(String(left).split('-', 1)[0]);
                const rightStart = timeToMinutes(String(right).split('-', 1)[0]);
                return Number(leftStart ?? 0) - Number(rightStart ?? 0);
            });

        return normalizedSlots.length ? normalizedSlots : [...DEFAULT_BOOKING_TIME_SLOTS];
    }

    function renderTimeSlotOptions() {
        if (!filterTimeSlot) return;

        const currentValue = normalizeTimeSlotValue(searchParams.get('khung_gio_hen') || filterTimeSlot.value);
        const slots = getBookingTimeSlots();

        filterTimeSlot.innerHTML = [
            '<option value="">Chọn khung giờ</option>',
            ...slots.map((slot) => `<option value="${slot}">${slot.replace('-', ' - ')}</option>`),
        ].join('');

        if (slots.includes(currentValue)) {
            filterTimeSlot.value = currentValue;
        }
    }

    async function loadBookingTimeSlots() {
        try {
            const result = await callApi('/travel-fee-config', 'GET');
            if (result?.ok) {
                const config = result.data?.data?.config;
                bookingTimeSlots = Array.isArray(config?.booking_time_slots)
                    ? config.booking_time_slots
                    : [];
            }
        } catch (error) {
            console.warn('Không tải được cấu hình khung giờ đặt lịch.', error);
        } finally {
            renderTimeSlotOptions();
            syncControlsFromParams();
        }
    }

    function bindStaticEvents() {
        filterDate?.setAttribute('min', getTodayString());

        sortRadios.forEach((radio) => {
            radio.addEventListener('change', (event) => {
                const selectedSort = event.target.value;
                if (!selectedSort || selectedSort === DEFAULT_SORT) {
                    searchParams.delete('sort');
                } else {
                    searchParams.set('sort', selectedSort);
                }

                searchParams.set('page', '1');
                updateUrlAndFetch();
            });
        });

        btnApplyTimeFilter?.addEventListener('click', () => {
            const dateVal = filterDate?.value || '';
            const timeVal = filterTimeSlot?.value || '';

            if (dateVal && timeVal) {
                searchParams.set('ngay_hen', dateVal);
                searchParams.set('khung_gio_hen', timeVal);
            } else if (!dateVal && !timeVal) {
                searchParams.delete('ngay_hen');
                searchParams.delete('khung_gio_hen');
            } else {
                window.alert('Vui lòng chọn đủ ngày làm việc và khung giờ trước khi áp dụng.');
                return;
            }

            searchParams.set('page', '1');
            updateUrlAndFetch();
        });

        btnResetSearchFilters?.addEventListener('click', () => {
            clearAllFilters();
            updateUrlAndFetch();
        });

        activeFilterChips?.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-remove-filter]');
            if (!trigger) return;

            removeFilter(trigger.dataset.removeFilter);
            searchParams.set('page', '1');
            updateUrlAndFetch();
        });

        workersContainer?.addEventListener('click', (event) => {
            const clearButton = event.target.closest('[data-action="clear-filters"]');
            if (clearButton) {
                clearAllFilters();
                updateUrlAndFetch();
                return;
            }

            const bookingButton = event.target.closest('[data-book-worker]');
            if (bookingButton) {
                event.preventDefault();
                event.stopPropagation();
                openWorkerBooking(bookingButton.dataset.bookWorker);
                return;
            }

            if (event.target.closest('a, button, input, select, label')) return;

            const card = event.target.closest('.worker-card[data-profile-url]');
            if (card) {
                window.location.href = card.dataset.profileUrl;
            }
        });

        workersContainer?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            if (event.target.closest('a, button, input, select, label')) return;

            const card = event.target.closest('.worker-card[data-profile-url]');
            if (!card) return;

            event.preventDefault();
            window.location.href = card.dataset.profileUrl;
        });

        window.addEventListener('popstate', () => {
            overwriteParamsFromUrl();
            syncControlsFromParams();

            if (categoriesCache.length) {
                renderCategories(categoriesCache);
            }

            renderActiveFilterChips();
            updateContextualCopy();
            fetchWorkers();
        });
    }

    function overwriteParamsFromUrl() {
        Array.from(searchParams.keys()).forEach((key) => searchParams.delete(key));
        const currentUrlParams = new URLSearchParams(window.location.search);
        currentUrlParams.forEach((value, key) => searchParams.set(key, value));
    }

    function syncControlsFromParams() {
        const activeSort = searchParams.get('sort') || DEFAULT_SORT;
        const radioToCheck = document.querySelector(`input[name="sortOrder"][value="${activeSort}"]`)
            || document.querySelector(`input[name="sortOrder"][value="${DEFAULT_SORT}"]`);

        if (radioToCheck) {
            radioToCheck.checked = true;
        }

        if (filterDate) {
            filterDate.value = searchParams.get('ngay_hen') || getTodayString();
        }

        if (filterTimeSlot) {
            filterTimeSlot.value = searchParams.get('khung_gio_hen') || '';
        }
    }

    function updateUrlAndFetch() {
        const query = searchParams.toString();
        const nextUrl = query ? `${window.location.pathname}?${query}` : window.location.pathname;

        window.history.pushState({}, '', nextUrl);
        syncControlsFromParams();

        if (categoriesCache.length) {
            renderCategories(categoriesCache);
        }

        renderActiveFilterChips();
        updateContextualCopy();
        fetchWorkers();
    }

    async function fetchCategories() {
        try {
            const result = await callApi('/danh-muc-dich-vu', 'GET');
            categoriesCache = Array.isArray(result?.data) ? result.data : [];
            renderCategories(categoriesCache);
            renderActiveFilterChips();
            updateContextualCopy();
        } catch (error) {
            console.error('Error fetching categories:', error);
            categoryFiltersList.innerHTML = '<p class="text-danger mb-0">Không tải được danh mục.</p>';
        }
    }

    function renderCategories(categories) {
        const currentCategory = searchParams.get('category_id') || '';
        let html = `
            <label class="category-chip ${currentCategory ? '' : 'active'}">
                <input class="filter-category" type="radio" name="categoryId" value="" ${currentCategory ? '' : 'checked'}>
                <span>Tất cả</span>
            </label>
        `;

        categories.forEach((category) => {
            const isActive = String(category.id) === currentCategory;
            html += `
                <label class="category-chip ${isActive ? 'active' : ''}">
                    <input class="filter-category" type="radio" name="categoryId" value="${category.id}" ${isActive ? 'checked' : ''}>
                    <span>${escapeHtml(category.ten_dich_vu)}</span>
                </label>
            `;
        });

        categoryFiltersList.innerHTML = html;

        categoryFiltersList.querySelectorAll('input[name="categoryId"]').forEach((radio) => {
            radio.addEventListener('change', (event) => {
                const value = event.target.value;

                if (value) {
                    searchParams.set('category_id', value);
                } else {
                    searchParams.delete('category_id');
                }

                searchParams.set('page', '1');
                updateUrlAndFetch();
            });
        });
    }

    function renderActiveFilterChips() {
        if (!activeFiltersSection || !activeFilterChips) return;

        const chips = [];
        const currentCategory = searchParams.get('category_id');
        const currentSort = searchParams.get('sort');
        const workingDate = searchParams.get('ngay_hen');
        const workingTime = searchParams.get('khung_gio_hen');
        const province = searchParams.get('province');
        const hasCoordinates = searchParams.get('lat') && searchParams.get('lng');

        if (currentCategory) {
            chips.push({
                label: getSelectedCategoryLabel(),
                key: 'category',
            });
        }

        if (currentSort && currentSort !== DEFAULT_SORT) {
            chips.push({
                label: sortLabelMap(currentSort),
                key: 'sort',
            });
        }

        if (workingDate && workingTime) {
            chips.push({
                label: `${formatDateChip(workingDate)} • ${workingTime}`,
                key: 'time',
            });
        }

        if (province) {
            chips.push({
                label: province,
                key: 'location',
            });
        } else if (hasCoordinates) {
            chips.push({
                label: currentSort === 'nearest' ? 'Gần nhất' : 'Vị trí hiện tại',
                key: 'location',
            });
        }

        activeFiltersSection.style.display = chips.length ? 'flex' : 'none';
        activeFilterChips.innerHTML = chips.map((chip) => `
            <div class="active-chip">
                <span>${escapeHtml(chip.label)}</span>
                <span class="material-symbols-outlined" data-remove-filter="${chip.key}">close</span>
            </div>
        `).join('');
    }

    function removeFilter(filterKey) {
        if (filterKey === 'category') {
            searchParams.delete('category_id');
            return;
        }

        if (filterKey === 'sort') {
            searchParams.delete('sort');
            return;
        }

        if (filterKey === 'time') {
            searchParams.delete('ngay_hen');
            searchParams.delete('khung_gio_hen');
            return;
        }

        if (filterKey === 'location') {
            searchParams.delete('province');
            searchParams.delete('lat');
            searchParams.delete('lng');
        }
    }

    function clearAllFilters() {
        ['sort', 'category_id', 'ngay_hen', 'khung_gio_hen', 'province', 'lat', 'lng', 'page'].forEach((key) => {
            searchParams.delete(key);
        });
    }

    function updateContextualCopy(total = null) {
        const categoryLabel = getSelectedCategoryLabel();
        const scheduleLabel = getSelectedScheduleLabel();

        if (resultsCategorySpotlight) {
            resultsCategorySpotlight.textContent = categoryLabel;
        }

        if (resultsScheduleSpotlight) {
            resultsScheduleSpotlight.textContent = scheduleLabel;
        }

        if (resultsSubline) {
            resultsSubline.textContent = buildResultsSummary(total);
        }
    }

    async function fetchWorkers() {
        workersContainer.innerHTML = generateSkeletonHTML(4);
        resultsCount.innerHTML = 'Chọn người thợ đúng việc <span>Đang tải danh sách</span>';
        paginationContainer.innerHTML = '';
        updateContextualCopy();

        try {
            const queryString = searchParams.toString();
            const endpoint = queryString ? `/ho-so-tho?${queryString}` : '/ho-so-tho';
            const result = await callApi(endpoint, 'GET');
            const payload = result?.data;
            const workers = Array.isArray(payload?.data) ? payload.data : [];

            renderResultsCount(payload?.total || 0);
            updateContextualCopy(payload?.total || 0);

            if (!workers.length) {
                renderEmptyState();
                paginationContainer.innerHTML = '';
                return;
            }

            renderWorkers(workers);
            renderPagination(payload);
        } catch (error) {
            console.error('Error fetching workers:', error);
            resultsCount.innerHTML = 'Chọn người thợ đúng việc <span>Không tải được dữ liệu</span>';
            updateContextualCopy(0);
            renderErrorState();
            paginationContainer.innerHTML = '';
        }
    }

    function renderResultsCount(total) {
        const count = Number(total) || 0;
        resultsCount.innerHTML = `Chọn người thợ đúng việc <span>${count} hồ sơ phù hợp</span>`;
    }

    function renderWorkers(workers) {
        workersContainer.innerHTML = workers.map((worker) => renderWorkerCard(worker)).join('');
    }

    function renderWorkerCard(worker) {
        const profileUrl = `/customer/worker-profile/${worker.id}`;
        const name = worker?.user?.name || 'Chưa cập nhật';
        const avatarUrl = worker?.user?.avatar || '/assets/images/user-default.png';
        const serviceList = getWorkerServiceList(worker);
        const servicesText = serviceList.join(', ') || 'Dịch vụ đa năng';
        const primaryService = serviceList[0] || 'Dịch vụ tổng hợp';
        const description = getWorkerDescription(worker, servicesText);
        const priceText = formatReferencePrice(worker?.bang_gia_tham_khao);
        const distanceText = formatDistance(worker?.distance);
        const ratingValue = Number(worker?.danh_gia_trung_binh || 0);
        const reviewCount = Number(worker?.tong_so_danh_gia || 0);
        const statusMeta = getStatusMeta(worker?.trang_thai_hoat_dong);

        return `
            <article class="worker-card" data-profile-url="${profileUrl}" tabindex="0" role="link" aria-label="Xem hồ sơ ${escapeHtml(name)}">
                <div class="card-top">
                    <div class="avatar-wrapper">
                        <div class="avatar-img-box">
                            <img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(name)}" onerror="this.src='/assets/images/customer.png'">
                        </div>
                        <div class="verified-icon">
                            <span class="material-symbols-outlined">verified</span>
                        </div>
                    </div>
                    <span class="status-badge ${statusMeta.cssClass}">${escapeHtml(statusMeta.label)}</span>
                </div>
                <div class="card-body">
                    <div class="worker-header">
                        <h3 class="worker-name">${escapeHtml(name)}</h3>
                        <div class="worker-rating">
                            <span class="material-symbols-outlined star">star</span>
                            <span class="score">${ratingValue.toFixed(1)}</span>
                            <span class="count">(${reviewCount})</span>
                        </div>
                    </div>
                    <p class="worker-category">${escapeHtml(primaryService)}</p>
                    <div class="card-footer">
                        ${distanceText ? `
                        <div class="footer-item">
                            <span class="material-symbols-outlined">location_on</span>
                            <span>Cách ${escapeHtml(distanceText)}</span>
                        </div>
                        ` : ''}
                        <div class="footer-item">
                            <span class="material-symbols-outlined">payments</span>
                            <span>${escapeHtml(priceText)}</span>
                        </div>
                    </div>
                    <button class="btn-worker-card-book" data-book-worker="${worker.id}" type="button">
                        <span class="material-symbols-outlined" style="font-size: 1.125rem;">calendar_add_on</span> Đặt Lịch
                    </button>
                </div>
            </article>
        `;
    }

    function renderEmptyState() {
        workersContainer.innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-outlined" style="font-size: 3rem; color: var(--outline-variant); margin-bottom: 1rem;">search_off</span>
                <h3 style="font-family: var(--headline); font-weight: 800; font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--on-surface);">Chưa thấy hồ sơ khớp với bộ lọc</h3>
                <p style="color: var(--on-surface-variant); margin-bottom: 2rem; max-width: 400px; text-align: center;">Hãy đổi danh mục, bỏ bớt bộ lọc thời gian hoặc quay về mặc định để mở rộng danh sách thợ.</p>
                <button class="btn-primary" type="button" data-action="clear-filters">Xóa toàn bộ bộ lọc</button>
            </div>
        `;
    }

    function renderErrorState() {
        workersContainer.innerHTML = `
            <div class="empty-state error-state">
                <span class="material-symbols-outlined" style="font-size: 3rem; color: var(--error); margin-bottom: 1rem;">wifi_off</span>
                <h3 style="font-family: var(--headline); font-weight: 800; font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--on-surface);">Không thể tải danh sách thợ</h3>
                <p style="color: var(--on-surface-variant); margin-bottom: 2rem; max-width: 400px; text-align: center;">Máy chủ đang bận hoặc kết nối chưa ổn định. Vui lòng thử lại sau.</p>
                <button class="btn-primary" type="button" data-action="clear-filters">Tải lại danh sách</button>
            </div>
        `;
    }

    function renderPagination(result) {
        if (!result || result.last_page <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        const pageItems = buildPaginationItems(result.current_page, result.last_page);
        let html = '<div class="premium-pagination" aria-label="Phân trang kết quả">';
        
        // Previous Button
        if (result.current_page > 1) {
            html += `<button class="page-btn nav-btn" data-page="${result.current_page - 1}"><span class="material-symbols-outlined">chevron_left</span></button>`;
        } else {
            html += `<button class="page-btn nav-btn" disabled style="opacity: 0.5; cursor: not-allowed;"><span class="material-symbols-outlined">chevron_left</span></button>`;
        }
        
        // Page Numbers
        pageItems.forEach((item) => {
            if (item === 'ellipsis') {
                html += '<span class="page-btn" style="cursor: default; background: transparent; color: var(--outline);">...</span>';
                return;
            }

            const isActive = item === result.current_page;
            html += `<button class="page-btn ${isActive ? 'active' : ''}" data-page="${item}">${item}</button>`;
        });

        // Next Button
        if (result.current_page < result.last_page) {
            html += `<button class="page-btn nav-btn" data-page="${result.current_page + 1}"><span class="material-symbols-outlined">chevron_right</span></button>`;
        } else {
            html += `<button class="page-btn nav-btn" disabled style="opacity: 0.5; cursor: not-allowed;"><span class="material-symbols-outlined">chevron_right</span></button>`;
        }
        
        html += '</div>';
        paginationContainer.innerHTML = html;

        paginationContainer.querySelectorAll('[data-page]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const nextPage = event.currentTarget.dataset.page;
                if (!nextPage) return;

                searchParams.set('page', nextPage);
                updateUrlAndFetch();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }

    function buildPaginationItems(currentPage, lastPage) {
        if (lastPage <= 7) {
            return Array.from({ length: lastPage }, (_, index) => index + 1);
        }

        const items = [1];
        if (currentPage > 3) items.push('ellipsis');

        const start = Math.max(2, currentPage - 1);
        const end = Math.min(lastPage - 1, currentPage + 1);

        for (let page = start; page <= end; page += 1) {
            items.push(page);
        }

        if (currentPage < lastPage - 2) items.push('ellipsis');
        items.push(lastPage);

        return items;
    }

    function generateSkeletonHTML(count) {
        return Array.from({ length: count }, () => `
            <div class="skeleton-card">
                <div class="skel-avatar"></div>
                <div class="skel-text-lg" style="width: 50%;"></div>
                <div class="skel-text-sm" style="width: 80%;"></div>
                <div class="skel-footer"></div>
            </div>
        `).join('');
    }

    function getWorkerServiceList(worker) {
        const services = Array.isArray(worker?.user?.dich_vus) ? worker.user.dich_vus : [];
        return services
            .map((service) => String(service?.ten_dich_vu || '').trim())
            .filter(Boolean)
            .slice(0, 3);
    }

    function renderServiceTags(serviceList) {
        if (!serviceList.length) {
            return '<span class="worker-service-chip">Dịch vụ tổng hợp</span>';
        }

        return serviceList.map((service) => `
            <span class="worker-service-chip">${escapeHtml(service)}</span>
        `).join('');
    }

    function getWorkerDescription(worker, servicesText) {
        const experience = String(worker?.kinh_nghiem || '').trim();
        if (experience) {
            return experience;
        }

        return `Nhận ${servicesText.toLowerCase()} với quy trình rõ ràng, báo giá minh bạch và hỗ trợ nhanh tại khu vực của bạn.`;
    }

    function getStatusMeta(status) {
        if (status === 'dang_hoat_dong') {
            return {
                label: 'Sẵn sàng',
                cssClass: 'available'
            };
        }

        if (status === 'dang_ban') {
            return {
                label: 'Đang bận',
                cssClass: 'busy'
            };
        }

        return {
            label: 'Tạm nghỉ',
            cssClass: 'offline'
        };
    }

    function renderStars(rating) {
        let html = '';

        for (let star = 1; star <= 5; star += 1) {
            const isFilled = rating >= star - 0.25;
            html += `
                <span class="material-symbols-outlined" style="${isFilled ? '' : "font-variation-settings: 'FILL' 0; color: #cbd5e1;"}">star</span>
            `;
        }

        return html;
    }

    function formatDistance(distance) {
        const numericDistance = Number(distance);
        if (!Number.isFinite(numericDistance) || numericDistance <= 0) {
            return '';
        }

        return `${numericDistance.toFixed(1)} km`;
    }

    function formatReferencePrice(value) {
        const rawValue = String(value || '').trim();
        if (!rawValue) {
            return 'Liên hệ báo giá';
        }

        if (/^từ\s+/i.test(rawValue)) {
            return rawValue;
        }

        const firstNumber = rawValue.match(/\d[\d.,\s]*/);
        if (!firstNumber) {
            return rawValue;
        }

        const amount = Number(firstNumber[0].replace(/[^\d]/g, ''));
        if (!Number.isFinite(amount) || amount <= 0) {
            return rawValue;
        }

        return `Từ ${amount.toLocaleString('vi-VN')}đ`;
    }

    function buildResultsSummary(total) {
        const currentSort = sortLabelMap(searchParams.get('sort') || DEFAULT_SORT).toLowerCase();
        const categoryLabel = getSelectedCategoryLabel().toLowerCase();
        const hasCategory = Boolean(searchParams.get('category_id'));
        const workingDate = searchParams.get('ngay_hen');
        const workingTime = searchParams.get('khung_gio_hen');

        if (!Number.isFinite(Number(total))) {
            return 'Hệ thống đang kiểm tra các hồ sơ phù hợp với bộ lọc hiện tại của bạn.';
        }

        if (Number(total) === 0) {
            return 'Chưa có hồ sơ phù hợp với các điều kiện đang chọn. Hãy nới bộ lọc để mở rộng danh sách.';
        }

        if (workingDate && workingTime) {
            return `${total} hồ sơ đang được ưu tiên theo ${currentSort}, phù hợp với ${categoryLabel} và lịch ${formatDateChip(workingDate)} • ${workingTime}.`;
        }

        if (hasCategory) {
            return `${total} hồ sơ đang được sắp theo ${currentSort} cho danh mục ${categoryLabel}. Bạn có thể thêm lịch hẹn để lọc sát hơn.`;
        }

        return `${total} hồ sơ đang được sắp theo ${currentSort} trong toàn bộ dịch vụ sửa chữa. Chọn danh mục hoặc lịch hẹn để thu hẹp nhanh hơn.`;
    }

    function getSelectedCategoryLabel() {
        const currentCategory = searchParams.get('category_id');
        if (!currentCategory) {
            return 'Tất cả dịch vụ sửa chữa';
        }

        const category = categoriesCache.find((item) => String(item.id) === String(currentCategory));
        return category?.ten_dich_vu || 'Danh mục đã chọn';
    }

    function getSelectedScheduleLabel() {
        const workingDate = searchParams.get('ngay_hen');
        const workingTime = searchParams.get('khung_gio_hen');

        if (workingDate && workingTime) {
            return `${formatDateChip(workingDate)} • ${workingTime}`;
        }

        if (workingDate && !workingTime) {
            return `${formatDateChip(workingDate)} • Chưa chọn khung giờ`;
        }

        return 'Hôm nay • Chưa chọn khung giờ';
    }

    function formatDateChip(value) {
        const date = new Date(`${value}T00:00:00`);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return new Intl.DateTimeFormat('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(date);
    }

    function sortLabelMap(value) {
        if (value === 'rating') return 'Đánh giá cao nhất';
        if (value === 'value') return 'Giá tham khảo tốt';
        if (value === 'nearest') return 'Gần nhất';
        return 'Phổ biến nhất';
    }

    function openWorkerBooking(workerId) {
        if (!workerId) return;

        if (window.BookingWizardModal?.open) {
            window.BookingWizardModal.open({ workerId: Number(workerId) });
            return;
        }

        const targetUrl = new URL('/customer/booking', window.location.origin);
        targetUrl.searchParams.set('worker_id', workerId);
        window.location.href = targetUrl.toString();
    }

    function getTodayString() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const date = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${date}`;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
});
