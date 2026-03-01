import { callApi } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const btnSearch = document.getElementById('btnSearch');
    const workersContainer = document.getElementById('workersContainer');
    const resultsCount = document.getElementById('resultsCount');
    const categoryFiltersList = document.getElementById('categoryFiltersList');
    const paginationContainer = document.getElementById('paginationContainer');
    const sortRadios = document.querySelectorAll('input[name="sortOrder"]');

    // State
    const searchParams = new URLSearchParams(window.location.search);
    let currentPage = parseInt(searchParams.get('page')) || 1;

    // init
    initSearchInput();
    fetchCategories();
    fetchWorkers();

    // Event Listeners
    btnSearch.addEventListener('click', () => {
        const keyword = searchInput.value.trim();
        const province = document.querySelector('.search-container').getAttribute('data-search-location');
        const lat = document.querySelector('.search-container').getAttribute('data-search-lat');
        const lng = document.querySelector('.search-container').getAttribute('data-search-lng');

        // Reset old params except sort & category
        searchParams.delete('q');
        searchParams.delete('province');
        searchParams.delete('lat');
        searchParams.delete('lng');

        if (keyword) searchParams.set('q', keyword);
        if (lat && lng) {
            searchParams.set('lat', lat);
            searchParams.set('lng', lng);
        } else if (province) {
            searchParams.set('province', province);
        }

        searchParams.set('page', 1);
        updateUrlAndFetch();
    });

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            btnSearch.click();
        }
    });

    sortRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            searchParams.set('sort', e.target.value);
            // If sort is 'nearest', check if we have lat/lng in URL
            if (e.target.value === 'nearest' && !searchParams.get('lat')) {
                alert('Vui lòng cấp quyền truy cập vị trí trên thanh tìm kiếm để sắp xếp theo khoảng cách.');
                return;
            }
            searchParams.set('page', 1);
            updateUrlAndFetch();
        });
    });

    function initSearchInput() {
        if (searchParams.has('q')) {
            searchInput.value = searchParams.get('q');
        }
        if (searchParams.has('sort')) {
            const sortVal = searchParams.get('sort');
            const radio = document.querySelector(`input[name="sortOrder"][value="${sortVal}"]`);
            if (radio) radio.checked = true;
        }
    }

    function updateUrlAndFetch() {
        const newUrl = `${window.location.pathname}?${searchParams.toString()}`;
        window.history.pushState({}, '', newUrl);
        fetchWorkers();
    }

    // --- Xử lý Chọn Vị trí (Location Selector) ---
    const btnGetLocation = document.getElementById('btnGetLocation');
    const locationText = document.getElementById('selectedLocationText');
    const locationItems = document.querySelectorAll('.location-item');
    const searchContainer = document.querySelector('.search-container');

    // Handle Tỉnh/Thành selection
    if (locationItems) {
        locationItems.forEach(item => {
            item.addEventListener('click', (e) => {
                const province = e.target.getAttribute('data-province');
                locationText.textContent = province;
                searchContainer.setAttribute('data-search-location', province);
                searchContainer.removeAttribute('data-search-lat');
                searchContainer.removeAttribute('data-search-lng');
            });
        });
    }

    // Handle Geolocation API
    if (btnGetLocation) {
        btnGetLocation.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert("Trình duyệt của bạn không hỗ trợ định vị vị trí.");
                return;
            }

            locationText.textContent = "Đang lấy vị trí...";

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    locationText.textContent = "Vị trí của bạn";
                    locationText.classList.add('text-primary');

                    searchContainer.setAttribute('data-search-lat', lat);
                    searchContainer.setAttribute('data-search-lng', lng);
                    searchContainer.removeAttribute('data-search-location');
                },
                (error) => {
                    console.error("Lỗi định vị:", error);
                    let errMsg = "Không thể lấy vị trí. ";
                    if (error.code == 1) errMsg += "Vui lòng cấp quyền truy cập vị trí.";
                    else if (error.code == 2) errMsg += "Lạc tín hiệu GPS.";
                    else if (error.code == 3) errMsg += "Hết thời gian chờ.";

                    alert(errMsg);
                    locationText.textContent = "Toàn quốc";
                }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
            );
        });
    }

    async function fetchCategories() {
        try {
            const result = await callApi('/danh-muc-dich-vu', 'GET');
            if (result.data) {
                renderCategories(result.data);
            }
        } catch (error) {
            console.error('Error fetching categories:', error);
            categoryFiltersList.innerHTML = '<p class="text-danger">Không tải được danh mục</p>';
        }
    }

    function renderCategories(categories) {
        let html = '';
        const currentCategory = searchParams.get('category_id');

        // All category option
        html += `
            <div class="form-check mb-3">
                <input class="form-check-input filter-category custom-control-input" type="radio" name="categoryId" id="cat_all" value="" ${!currentCategory ? 'checked' : ''} style="transform: scale(1.2); margin-top: 0.35rem;">
                <label class="form-check-label custom-control-label fw-bold d-flex align-items-center" for="cat_all" style="cursor: pointer;">
                    <span class="rounded bg-light d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                        <svg width="16" height="16" fill="var(--bs-gray-600)" viewBox="0 0 16 16"><path d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/></svg>
                    </span>
                    Tất cả Dịch vụ
                </label>
            </div>
        `;

        categories.forEach(cat => {
            const isChecked = currentCategory == cat.id ? 'checked' : '';
            // Giao diện Danh mục xịn xò với Font đậm mượt mà
            html += `
                <div class="form-check mb-3">
                    <input class="form-check-input filter-category custom-control-input" type="radio" name="categoryId" id="cat_${cat.id}" value="${cat.id}" ${isChecked} style="transform: scale(1.2); margin-top: 0.35rem;">
                    <label class="form-check-label custom-control-label fw-bold d-flex align-items-center" for="cat_${cat.id}" style="cursor: pointer;">
                        <span class="rounded bg-light d-flex align-items-center justify-content-center me-2 text-primary fw-bold" style="width: 30px; height: 30px; font-size: 12px;">
                            ${cat.ten_danh_muc.charAt(0).toUpperCase()}
                        </span>
                        ${cat.ten_danh_muc}
                    </label>
                </div>
            `;
        });

        categoryFiltersList.innerHTML = html;

        // Add events to new radios
        document.querySelectorAll('input[name="categoryId"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.value) {
                    searchParams.set('category_id', e.target.value);
                } else {
                    searchParams.delete('category_id');
                }
                searchParams.set('page', 1);
                updateUrlAndFetch();
            });
        });
    }

    async function fetchWorkers() {
        // Show loading state
        workersContainer.innerHTML = generateSkeletonHTML(6);
        resultsCount.textContent = 'Đang tìm kiếm...';

        try {
            const queryString = searchParams.toString();
            const result = await callApi(`/ho-so-tho?${queryString}`, 'GET');

            // Lưu ý: với fetch() gọi từ callApi, format trả về là: result = { status: 200, data: { current_page: 1, data: [...], total: 20 } }
            if (result && result.data && result.data.data && result.data.data.length > 0) {
                renderWorkers(result.data.data);
                resultsCount.innerHTML = `Tìm thấy <span class="text-primary">${result.data.total}</span> chuyên gia phù hợp`;
                renderPagination(result.data);
            } else {
                workersContainer.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <img src="/assets/images/logo.png" style="width: 100px; opacity: 0.1; filter: grayscale(1); margin-bottom: 20px;">
                        <h4 class="text-muted fw-bold">Không tìm thấy thợ phù hợp</h4>
                        <p class="text-muted-custom">Vui lòng thử thay đổi từ khóa hoặc bộ lọc tìm kiếm.</p>
                        <button class="btn btn-outline-primary mt-3 px-4 rounded-pill" onclick="window.location.href='/customer/search'">Xóa bộ lọc</button>
                    </div>
                `;
                resultsCount.textContent = '0 kết quả';
                paginationContainer.innerHTML = '';
            }

        } catch (error) {
            console.error('Error fetching workers:', error);
            workersContainer.innerHTML = `<div class="col-12 alert alert-danger shadow-sm border-0 rounded-4">Lỗi kết nối đến máy chủ. Không thể tải danh sách thợ.</div>`;
            resultsCount.textContent = 'Lỗi kết nối';
        }
    }

    function renderWorkers(workers) {
        let html = '';
        workers.forEach(worker => {
            const name = worker.user ? worker.user.name : 'Unknown';
            const avatarUrl = (worker.user && worker.user.avatar) ? worker.user.avatar : '/assets/images/user-default.png';
            const distanceText = worker.distance ? `<div class="stat-item fw-bold text-primary"><svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg> ${parseFloat(worker.distance).toFixed(1)} km</div>` : '';

            let services = 'Chưa xác định';
            if (worker.user && worker.user.dich_vus && worker.user.dich_vus.length > 0) {
                services = worker.user.dich_vus.map(d => d.ten_dich_vu).join(', ');
            } else {
                services = worker.kinh_nghiem || 'Thợ đa năng';
            }

            let statusHtml = '';
            if (worker.trang_thai_hoat_dong === 'dang_hoat_dong') {
                statusHtml = '<span class="worker-badge-status status-active"><i class="fas fa-circle text-success" style="font-size: 8px;"></i> Sẵn sàng</span>';
            } else if (worker.trang_thai_hoat_dong === 'dang_ban') {
                statusHtml = '<span class="worker-badge-status status-busy"><i class="fas fa-circle text-warning" style="font-size: 8px;"></i> Đang bận</span>';
            } else {
                statusHtml = '<span class="worker-badge-status" style="background: rgba(0,0,0,0.5); color: white;">Nghỉ</span>';
            }

            const rating = parseFloat(worker.danh_gia_trung_binh).toFixed(1);

            html += `
                <div class="col-md-6 col-xl-4">
                    <a href="/customer/worker-profile/${worker.id}" class="text-decoration-none">
                        <div class="worker-card">
                            <div class="worker-banner">
                                <img src="${avatarUrl}" class="worker-avatar" onerror="this.src='/assets/images/customer.png'">
                                ${statusHtml}
                            </div>
                            <div class="worker-info bg-white">
                                <div class="worker-name">
                                    ${name}
                                    <svg class="verified-badge" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
                                </div>
                                <p class="text-muted small mb-0 text-truncate" title="${services}">${services}</p>
                                
                                <div class="worker-stats mt-auto">
                                    <div class="stat-item text-warning fw-bold">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/></svg>
                                        ${rating} <span class="text-muted fw-normal">(${worker.tong_so_danh_gia})</span>
                                    </div>
                                    ${distanceText}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            `;
        });
        workersContainer.innerHTML = html;
    }

    function renderPagination(result) {
        if (result.last_page <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let html = '<ul class="pagination pagination-lg border-0 shadow-sm" style="border-radius: 50px; overflow: hidden;">';

        // Prev
        const prevDisabled = result.current_page === 1 ? 'disabled' : '';
        html += `<li class="page-item ${prevDisabled}"><a class="page-link border-0 fw-bold" href="#" data-page="${result.current_page - 1}">Trước</a></li>`;

        // Pages
        for (let i = 1; i <= result.last_page; i++) {
            const active = result.current_page === i ? 'active' : '';
            html += `<li class="page-item ${active}"><a class="page-link border-0 fw-bold" href="#" data-page="${i}">${i}</a></li>`;
        }

        // Next
        const nextDisabled = result.current_page === result.last_page ? 'disabled' : '';
        html += `<li class="page-item ${nextDisabled}"><a class="page-link border-0 fw-bold" href="#" data-page="${result.current_page + 1}">Sau</a></li>`;

        html += '</ul>';
        paginationContainer.innerHTML = html;

        // Add events
        paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (e.target.parentElement.classList.contains('disabled') || e.target.parentElement.classList.contains('active')) return;

                const page = e.target.getAttribute('data-page');
                searchParams.set('page', page);
                updateUrlAndFetch();
                window.scrollTo({ top: 300, behavior: 'smooth' }); // scroll to top of list
            });
        });
    }

    function generateSkeletonHTML(count) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="col-md-6 col-xl-4 skeleton-item">
                    <div class="worker-card">
                        <div class="worker-banner skeleton-box"></div>
                        <div class="worker-info bg-white">
                            <div class="skeleton-box mb-2" style="width: 70%; height: 24px;"></div>
                            <div class="skeleton-box mb-3" style="width: 50%; height: 16px;"></div>
                            <div class="skeleton-box w-100 mt-auto" style="height: 38px;"></div>
                        </div>
                    </div>
                </div>
            `;
        }
        return html;
    }
});
