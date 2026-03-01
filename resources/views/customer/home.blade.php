@extends('layouts.app')

@section('title', 'Tìm Thợ Khắp Nơi - Find a Worker')

@push('styles')
<style>
    /* HERO SECTION */
    .hero-section {
        position: relative;
        padding: 8rem 0 10rem 0;
        min-height: 85vh;
        display: flex;
        align-items: center;
        overflow: hidden;
    }

    /* Carousel Background styling */
    .hero-carousel {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
    }

    .hero-carousel .carousel-inner,
    .hero-carousel .carousel-item {
        height: 100%;
    }

    .hero-carousel img,
    .hero-carousel video {
        object-fit: cover;
        width: 100%;
        height: 100%;
        object-position: center;
    }

    /* Overlay để làm nổi bật chữ Text */
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(15, 23, 42, 0.8) 0%, rgba(15, 23, 42, 0.4) 100%);
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
        width: 100%;
    }

    /* BỘ TÌM KIẾM KHỔNG LỒ */
    .search-container {
        max-width: 800px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 50px;
        padding: 8px;
        box-shadow: 0 20px 40px rgba(16, 185, 129, 0.12);
        display: flex;
        align-items: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .search-container:focus-within {
        transform: translateY(-2px);
        box-shadow: 0 25px 50px rgba(16, 185, 129, 0.2);
        border-color: rgba(16, 185, 129, 0.3);
    }

    .search-input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 15px 25px;
        font-size: 1.1rem;
        outline: none;
        color: var(--bs-body-color);
        font-family: 'Inter', sans-serif;
    }

    .search-input::placeholder {
        color: #94A3B8;
    }

    .search-btn {
        background: var(--bs-primary);
        color: white;
        border: none;
        border-radius: 40px;
        padding: 15px 35px;
        font-weight: bold;
        font-family: 'Outfit', sans-serif;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .search-btn:hover {
        background: var(--bs-primary-hover);
        transform: scale(1.02);
        box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
    }

    /* GRID DANH MỤC DỊCH VỤ */
    .service-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 1.5rem;
        margin-top: -3.5rem;
        /* Kéo trồi lên đè phần Hero */
        position: relative;
        z-index: 2;
    }

    @media (min-width: 768px) {
        .service-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }
    }

    .service-card {
        background: white;
        border-radius: var(--border-radius-lg);
        padding: 0;
        text-align: center;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        color: var(--bs-body-color);
        border: 1px solid rgba(0, 0, 0, 0.02);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .service-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-xl);
        border-color: rgba(16, 185, 129, 0.1);
        color: var(--bs-primary);
    }

    .service-image-container {
        width: 100%;
        height: 130px;
        overflow: hidden;
        background: #f8fafc;
    }

    .service-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .service-card:hover .service-image-container img {
        transform: scale(1.1);
    }

    .service-card-body {
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 1;
    }

    .service-card span {
        font-weight: 700;
        font-size: 1.05rem;
    }

    /* TOP WORKERS GRID */
    .section-title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        color: var(--bs-secondary);
        font-size: 2.2rem;
        letter-spacing: -0.02em;
        margin-bottom: 2.5rem;
    }

    .section-title span {
        color: var(--bs-primary);
    }
</style>
@endpush

@section('content')

<!-- Web Component Navbar -->
<app-navbar></app-navbar>

<!-- Hero Section -->
<section class="hero-section">
    <!-- Carousel Background -->
    <div id="heroCarousel" class="carousel slide carousel-fade hero-carousel" data-bs-ride="carousel" data-bs-interval="4000">
        <div class="carousel-inner">
            <!-- Item 1: Video -->
            <div class="carousel-item active">
                <video autoplay muted loop playsinline poster="{{ asset('assets/images/banner.png') }}">
                    <source src="{{ asset('assets/images/videobanner.mp4') }}" type="video/mp4">
                </video>
            </div>

        </div>

        <!-- Controls (Tùy chọn hiển thị mờ đè lên) -->
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" style="z-index: 3; width: 5%;">
            <span class="carousel-control-prev-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" style="z-index: 3; width: 5%;">
            <span class="carousel-control-next-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- Dark Overlay -->
    <div class="hero-overlay"></div>

    <div class="container hero-content">
        <h1 class="brand-font fw-extrabold mb-3 text-white" style="font-size: 4rem; letter-spacing: -1.5px; text-shadow: 0 4px 15px rgba(0,0,0,0.6);">
            Sửa Chữa Mọi Thứ<br>
            <span style="color: var(--bs-warning);">Nhanh Chóng & An Toàn</span>
        </h1>
        <p class="text-white opacity-75 fs-4 mb-5" style="max-width: 700px; margin: 0 auto; text-shadow: 0 2px 5px rgba(0,0,0,0.5);">Trải nghiệm dịch vụ chuyên nghiệp. Kết nối ngay lập tức với hàng nghìn thợ kỹ thuật uy tín tại khu vực của bạn.</p>

        <div class="search-container">
            <!-- Location Picker Dropdown -->
            <div class="dropdown location-dropdown border-end pe-2">
                <button class="btn btn-link text-decoration-none dropdown-toggle text-dark d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 15px 15px; font-weight: 500;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="var(--bs-primary)" class="me-2" viewBox="0 0 16 16">
                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
                    </svg>
                    <span id="selectedLocationText" class="text-truncate" style="max-width: 120px;">Toàn quốc</span>
                </button>
                <ul class="dropdown-menu shadow border-0" style="border-radius: 12px; min-width: 250px;">
                    <!-- Get Current Location -->
                    <li>
                        <button class="dropdown-item py-2 fw-semibold text-primary d-flex align-items-center" id="btnGetLocation" type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M8 16a.5.5 0 0 1-.5-.5v-1.293l-4.146-4.147a.5.5 0 0 1 .708-.708L8 13.293l3.938-3.938a.5.5 0 0 1 .708.708L8.5 14.207V15.5a.5.5 0 0 1-.5.5zM1 7a.5.5 0 0 1 .5-.5h1.293l4.147-4.146a.5.5 0 1 1 .708.708L3.707 7H2a.5.5 0 0 1-.5-.5zm13.5.5a.5.5 0 0 1-.5.5h-1.293l-4.147 4.146a.5.5 0 0 1-.708-.708L12.293 8H14a.5.5 0 0 1 .5-.5zM8 1a.5.5 0 0 1 .5.5v1.293l4.146 4.147a.5.5 0 0 1-.708.708L8 3.707 4.062 7.645a.5.5 0 1 1-.708-.708L7.5 2.793V1.5A.5.5 0 0 1 8 1z" />
                            </svg>
                            Sử dụng vị trí hiện tại
                        </button>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <!-- Tỉnh thành (Tạm Mock tĩnh) -->
                    <li>
                        <h6 class="dropdown-header">Hoặc chọn tỉnh/thành có thợ</h6>
                    </li>
                    <li><button class="dropdown-item py-2 location-item" type="button" data-province="Hồ Chí Minh">TP. Hồ Chí Minh</button></li>
                    <li><button class="dropdown-item py-2 location-item" type="button" data-province="Hà Nội">Hà Nội</button></li>
                    <li><button class="dropdown-item py-2 location-item" type="button" data-province="Đà Nẵng">Đà Nẵng</button></li>
                </ul>
            </div>

            <span class="ps-3 text-muted">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                </svg>
            </span>
            <input type="text" class="search-input" placeholder="Bạn đang cần thợ sửa gì? (VD: sửa máy lạnh...)">
            <button class="search-btn d-none d-md-block">Tìm Thợ Ngay</button>
        </div>
    </div>
</section>

<!-- Service Categories (Nổi lên mấp mé mép) -->
<section class="container mb-5">
    <div class="service-grid" id="categoryContainer">
        <!-- Render bằng AJAX API sau. Tạm thời MOCK tĩnh để xem Giao diện -->
        <a href="#" class="service-card">
            <div class="service-image-container">
                <img src="{{ asset('assets/images/suamaylanh.png') }}" alt="Sửa Máy Lạnh">
            </div>
            <div class="service-card-body">
                <span>Sửa Máy Lạnh</span>
            </div>
        </a>
        <a href="#" class="service-card">
            <div class="service-image-container">
                <img src="{{ asset('assets/images/suaongnuoc.png') }}" alt="Sửa Ống Nước">
            </div>
            <div class="service-card-body">
                <span>Sửa Ống Nước</span>
            </div>
        </a>
        <a href="#" class="service-card">
            <div class="service-image-container">
                <img src="{{ asset('assets/images/suadien.png') }}" alt="Sửa Điện Gia Dụng">
            </div>
            <div class="service-card-body">
                <span>Sửa Điện</span>
            </div>
        </a>
        <a href="#" class="service-card">
            <div class="service-image-container">
                <img src="{{ asset('assets/images/suaxemay.png') }}" alt="Sửa Xe Máy">
            </div>
            <div class="service-card-body">
                <span>Sửa Xe Máy</span>
            </div>
        </a>
        <a href="#" class="service-card">
            <div class="service-image-container">
                <img src="{{ asset('assets/images/thoson.png') }}" alt="Thợ Sơn">
            </div>
            <div class="service-card-body">
                <span>Thợ Sơn</span>
            </div>
        </a>
        <a href="#" class="service-card">
            <div class="service-image-container">
                <img src="{{ asset('assets/images/xaydung.png') }}" alt="Xây Dựng">
            </div>
            <div class="service-card-body">
                <span>Xây Dựng</span>
            </div>
        </a>
    </div>
</section>

<!-- Khối Giới thiệu Nhân sự chuyên nghiệp -->
<section class="container mb-5 mt-5">
    <div class="row align-items-center bg-white rounded-4 shadow-sm overflow-hidden" style="border: 1px solid rgba(0,0,0,0.05);">
        <div class="col-lg-6 p-0">
            <img src="{{ asset('assets/images/nhansu.png') }}" alt="Đội ngũ nhân sự chuyên nghiệp" class="img-fluid w-100 h-100 object-fit-cover" style="min-height: 400px;">
        </div>
        <div class="col-lg-6 p-5">
            <h2 class="brand-font fw-bold mb-4" style="color: var(--bs-secondary); font-size: 2.2rem;">
                Đội ngũ thợ chuẩn <span style="color: var(--bs-primary);">5 Sao</span>
            </h2>
            <p class="text-muted-custom fs-5 mb-4">
                Tất cả đối tác kỹ thuật trên nền tảng FindWorker đều trải qua quy trình xác minh danh tính và kiểm tra tay nghề khắt khe ngặt nghèo nhất.
            </p>
            <ul class="list-unstyled mb-4">
                <li class="mb-3 d-flex align-items-center">
                    <svg class="me-3" width="24" height="24" fill="var(--bs-primary)" viewBox="0 0 16 16">
                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                    </svg>
                    <span class="fs-5 fw-semibold">100% Lý lịch tư pháp trong sạch.</span>
                </li>
                <li class="mb-3 d-flex align-items-center">
                    <svg class="me-3" width="24" height="24" fill="var(--bs-primary)" viewBox="0 0 16 16">
                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                    </svg>
                    <span class="fs-5 fw-semibold">Thay thế linh kiện chính hãng.</span>
                </li>
                <li class="mb-3 d-flex align-items-center">
                    <svg class="me-3" width="24" height="24" fill="var(--bs-primary)" viewBox="0 0 16 16">
                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
                    </svg>
                    <span class="fs-5 fw-semibold">Thái độ phục vụ lễ phép, tận tâm.</span>
                </li>
            </ul>
        </div>
    </div>
</section>

<!-- Khối Quy mô Toàn Quốc -->
<section class="container mb-5 mt-5">
    <div class="row align-items-center justify-content-between flex-lg-row-reverse bg-white rounded-4 shadow-sm overflow-hidden" style="border: 1px solid rgba(0,0,0,0.05);">
        <div class="col-lg-6 p-0 text-center bg-light">
            <img src="{{ asset('assets/images/anhtho.png') }}" alt="Mạng lưới thợ toàn quốc" class="img-fluid" style="max-height: 450px; object-fit: contain;">
        </div>
        <div class="col-lg-6 p-5">
            <h2 class="brand-font fw-bold mb-4" style="color: var(--bs-secondary); font-size: 2.2rem;">
                Mạng lưới phủ sóng <span style="color: var(--bs-warning);">Toàn Quốc</span>
            </h2>
            <p class="text-muted-custom fs-5 mb-4">
                Từ Bắc chí Nam, tại bất kỳ ngõ ngách nào từ Hà Nội đến Cà Mau, chúng tôi luôn có những thợ lành nghề sẵn sàng phục vụ gia đình bạn 24/7.
            </p>
            <div class="d-flex gap-4">
                <div>
                    <h3 class="fw-extrabold brand-font text-primary mb-1">63+</h3>
                    <p class="text-muted mb-0">Tỉnh thành</p>
                </div>
                <div>
                    <h3 class="fw-extrabold brand-font text-success mb-1">10,000+</h3>
                    <p class="text-muted mb-0">Thợ đối tác</p>
                </div>
                <div>
                    <h3 class="fw-extrabold brand-font text-warning mb-1">500,000+</h3>
                    <p class="text-muted mb-0">Hộ gia đình</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Khối Chi tiết một số Dịch vụ chuyên dụng -->
<section class="container mb-5 mt-5 py-5 text-center">
    <h2 class="brand-font fw-bold mb-3 section-title">Dịch Vụ Nổi Bật Tận Nhà</h2>
    <p class="text-muted-custom fs-5 mb-5 mx-auto" style="max-width: 700px;">Bắt bệnh chính xác, khắc phục triệt để mọi lỗi hỏng hóc thiết bị trong gia đình.</p>

    <div class="row g-4 justify-content-center">
        <!-- Dịch vụ Tủ lạnh -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden card-glass transition-all">
                <img src="{{ asset('assets/images/gioithieusuatulanh.png') }}" class="card-img-top" alt="Sửa tủ lạnh tại nhà" style="height: 250px; object-fit: cover;">
                <div class="card-body p-4 text-start">
                    <h4 class="fw-bold mb-3 text-dark">Chuyên Gia Sửa Tủ Lạnh</h4>
                    <p class="text-muted">Khắc phục nhanh tình trạng tủ lạnh không đông đá, rò rỉ nước, máy nén kêu to. Nạp gas, thay lốc chính hãng bảo hành dài hạn.</p>
                </div>
            </div>
        </div>

        <!-- Dịch vụ Tivi -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden card-glass transition-all">
                <img src="{{ asset('assets/images/gioithieusuativi.png') }}" class="card-img-top" alt="Sửa tivi tại nhà" style="height: 250px; object-fit: cover;">
                <div class="card-body p-4 text-start">
                    <h4 class="fw-bold mb-3 text-dark">Bệnh Việc Sửa Tivi</h4>
                    <p class="text-muted">Thay màn hình tivi bị vỡ, sửa lỗi sọc màn hình, mất tiếng, không nhận tín hiệu. Hỗ trợ tất cả các dòng Samsung, Sony, LG đời mới nhất.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer Đơn giản -->
<footer class="mt-5 pt-5 pb-3 border-top" style="background: white;">
    <div class="container text-center text-muted-custom">
        <h4 class="brand-font fw-bold" style="color: var(--bs-secondary);">FindWorker</h4>
        <p>Nền tảng kết nối thợ chuyên nghiệp hàng đầu 2026</p>
        <small>© 2026 DATN Project. All rights reserved.</small>
    </div>
</footer>

@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/components/Navbar.js') }}"></script>
<script type="module">
    import {
        callApi
    } from "{{ asset('assets/js/api.js') }}";

    document.addEventListener('DOMContentLoaded', () => {
        const btnGetLocation = document.getElementById('btnGetLocation');
        const locationText = document.getElementById('selectedLocationText');
        const locationItems = document.querySelectorAll('.location-item');

        // Handle Tỉnh/Thành selection
        locationItems.forEach(item => {
            item.addEventListener('click', (e) => {
                const province = e.target.getAttribute('data-province');
                locationText.textContent = province;
                // Store selected province somewhere (e.g. data-attribute or localStorage) for later search
                document.querySelector('.search-container').setAttribute('data-search-location', province);
                document.querySelector('.search-container').removeAttribute('data-search-lat');
                document.querySelector('.search-container').removeAttribute('data-search-lng');
            });
        });

        // Handle Geolocation
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

                    // Store coordinates for the search API
                    const searchContainer = document.querySelector('.search-container');
                    searchContainer.setAttribute('data-search-lat', lat);
                    searchContainer.setAttribute('data-search-lng', lng);
                    searchContainer.removeAttribute('data-search-location');

                    // Optional: Reverse geocoding to get actual street/city name here using Google Maps/Nominatim API
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

        // Handle Search Button Click on Home Page
        const btnSearch = document.querySelector('.search-btn');
        const searchInput = document.querySelector('.search-input');
        const searchContainer = document.querySelector('.search-container');

        btnSearch.addEventListener('click', () => {
            const keyword = searchInput.value.trim();
            const province = searchContainer.getAttribute('data-search-location');
            const lat = searchContainer.getAttribute('data-search-lat');
            const lng = searchContainer.getAttribute('data-search-lng');

            const params = new URLSearchParams();
            if (keyword) params.append('q', keyword);

            // Prefer lat/lng if available for "Nearest" sorting later, else province
            if (lat && lng) {
                params.append('lat', lat);
                params.append('lng', lng);
            } else if (province) {
                params.append('province', province);
            }

            // Redirect to search page
            window.location.href = `/customer/search?${params.toString()}`;
        });

        // Handle Enter key on input
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                btnSearch.click();
            }
        });
    });
</script>
<!-- Import tooltips JS for Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endpush