import { callApi } from '../api.js';
import '../components/booking-modal.js';

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const btnSearch = document.getElementById('btnSearch');
    const workersContainer = document.getElementById('workersContainer');
    const resultsCount = document.getElementById('resultsCount');
    const categoryFiltersList = document.getElementById('categoryFiltersList');
    const paginationContainer = document.getElementById('paginationContainer');
    const sortRadios = document.querySelectorAll('input[name="sortOrder"]');
    const filterDate = document.getElementById('filterDate');
    const filterTimeSlot = document.getElementById('filterTimeSlot');
    const btnApplyTimeFilter = document.getElementById('btnApplyTimeFilter');

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
            searchParams.set('page', 1);
            updateUrlAndFetch();
        });
    });

    if (btnApplyTimeFilter) {
        btnApplyTimeFilter.addEventListener('click', () => {
            const dateVal = filterDate.value;
            const timeVal = filterTimeSlot.value;

            if (dateVal && timeVal) {
                searchParams.set('ngay_hen', dateVal);
                searchParams.set('khung_gio_hen', timeVal);
            } else if (!dateVal && !timeVal) {
                searchParams.delete('ngay_hen');
                searchParams.delete('khung_gio_hen');
            } else {
                alert('Vui lòng chọn cả Ngày hẹn và Khung giờ để lọc thời gian rảnh.');
                return;
            }

            searchParams.set('page', 1);
            updateUrlAndFetch();
        });
    }

    function initSearchInput() {
        if (searchParams.has('q')) {
            searchInput.value = searchParams.get('q');
        }
        if (searchParams.has('sort')) {
            const sortVal = searchParams.get('sort');
            const radio = document.querySelector(`input[name="sortOrder"][value="${sortVal}"]`);
            if (radio) radio.checked = true;
        }
        if (searchParams.has('ngay_hen')) {
            filterDate.value = searchParams.get('ngay_hen');
        } else {
            // Default to today
            const today = new Date().toISOString().split('T')[0];
            filterDate.value = today;
        }
        if (searchParams.has('khung_gio_hen')) {
            filterTimeSlot.value = searchParams.get('khung_gio_hen');
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
        // All category option
        html += `
            <div class="category-filter-item ${!currentCategory ? 'active' : ''}">
                <label class="custom-control-label fw-bold d-flex flex-grow-1" for="cat_all" style="cursor: pointer; margin: 0;">
                    <input class="d-none filter-category custom-control-input" type="radio" name="categoryId" id="cat_all" value="" ${!currentCategory ? 'checked' : ''}>
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined text-lg">category</span>
                        <span>Tất cả Dịch vụ</span>
                    </div>
                </label>
            </div>
        `;

        categories.forEach(cat => {
            const isChecked = currentCategory == cat.id ? 'checked' : '';
            // Giao diện Danh mục xịn xò với Font đậm mượt mà theo Stitch Design System
            html += `
                <div class="category-filter-item ${isChecked ? 'active' : ''}">
                    <label class="custom-control-label fw-bold d-flex flex-grow-1" for="cat_${cat.id}" style="cursor: pointer; margin: 0;">
                        <input class="d-none filter-category" type="radio" name="categoryId" id="cat_${cat.id}" value="${cat.id}" ${isChecked}>
                        <div class="d-flex align-items-center gap-2">
                            <span class="material-symbols-outlined text-lg">hotel_class</span>
                            <span>${cat.ten_dich_vu}</span>
                        </div>
                    </label>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
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
                    <img src="/assets/images/logontu.png" style="width: 100px; opacity: 0.1; filter: grayscale(1); margin-bottom: 20px;">
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
                                <div class="worker-avatar-container">
                                    <img src="${avatarUrl}" class="worker-avatar" onerror="this.src='/assets/images/customer.png'" data-alt="Chuyên gia ${name}">
                                </div>
                                ${statusHtml}
                            </div>
                            <div class="worker-info pt-5 px-3 pb-3"> <!-- Added padding-top to clear the absolute avatar -->
                                <div class="worker-name mb-1">
                                    ${name}
                                    <span class="material-symbols-outlined text-blue-500 text-base ms-1" style="font-variation-settings: 'FILL' 1;">verified</span>
                                </div>
                                <p class="worker-service">${services}</p>

                                <div class="worker-stats mt-auto">
                                    <div class="stat-rating">
                                        <span class="material-symbols-outlined text-warning" style="font-size: 16px; font-variation-settings: 'FILL' 1;">star</span>
                                        ${rating} <span class="text-secondary fw-normal ms-1" style="font-size: 12px">(${worker.tong_so_danh_gia})</span>
                                    </div>
                                    ${distanceText ? `<div class="stat-distance"><span class="material-symbols-outlined" style="font-size: 16px;">location_on</span> ${parseFloat(worker.distance).toFixed(1)} km</div>` : ''}
                                </div>
                            </div>
                        </div>
                    </a>
                </div >
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
