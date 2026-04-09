import { callApi } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    const bookingModal = document.getElementById('bookingModal');
    const formBooking = document.getElementById('formBooking');
    const loaiDatLichRadios = document.querySelectorAll('input[name="loai_dat_lich"]');
    const atHomeGroup = document.getElementById('atHomeGroup');
    const atStoreGroup = document.getElementById('atStoreGroup');
    const bookingThoInput = document.getElementById('booking_tho_id');
    const bookingSelectedWorkerCard = document.getElementById('bookingSelectedWorkerCard');
    const bookingSelectedWorkerAvatar = document.getElementById('bookingSelectedWorkerAvatar');
    const bookingSelectedWorkerName = document.getElementById('bookingSelectedWorkerName');
    const bookingSelectedWorkerServices = document.getElementById('bookingSelectedWorkerServices');
    const btnBookingGetLocation = document.getElementById('btnBookingGetLocation');
    const bookingLocationStatus = document.getElementById('bookingLocationStatus');
    const btnSubmitBooking = document.getElementById('btnSubmitBooking');
    const bookingServiceTrigger = document.getElementById('bookingServiceTrigger');
    const bookingServiceDropdown = document.getElementById('bookingServiceDropdown');
    const bookingServiceSummary = document.getElementById('bookingServiceSummary');
    const bookingDichVuList = document.getElementById('bookingDichVuList');
    const bookingSelectedServices = document.getElementById('bookingSelectedServices');
    const bookingServiceClear = document.getElementById('bookingServiceClear');
    const bookingServiceIdsContainer = document.getElementById('booking_service_ids_container');
    const bookingTravelFeeSummary = document.getElementById('bookingTravelFeeSummary');
    const bookingTravelFeeText = document.getElementById('bookingTravelFeeText');
    const bookingDistanceText = document.getElementById('bookingDistanceText');
    const bookingTravelFeeHint = document.getElementById('bookingTravelFeeHint');
    const bookingStoreAddressText = document.getElementById('bookingStoreAddressText');
    const bookingStoreTransportHint = document.getElementById('bookingStoreTransportHint');
    const bookingStoreFeeText = document.getElementById('bookingStoreFeeText');
    const bookingTransportCheckbox = document.getElementById('booking_thue_xe_cho');

    let allBookingServices = [];
    let selectedServiceIds = new Set();
    let addressData = [];
    let selectedWorkerContext = null;
    const DEFAULT_FREE_DISTANCE_KM = 1;
    const DEFAULT_TRAVEL_FEE_PER_KM = 5000;
    const DEFAULT_STORE_TRANSPORT_FEE = 0;
    const DEFAULT_STORE_ADDRESS = '2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa';
    const TRAVEL_FEE_PER_KM = DEFAULT_TRAVEL_FEE_PER_KM;
    let storeAddress = DEFAULT_STORE_ADDRESS;
    let storeTransportFee = DEFAULT_STORE_TRANSPORT_FEE;
    let travelFeeConfig = {
        free_distance_km: DEFAULT_FREE_DISTANCE_KM,
        default_per_km: DEFAULT_TRAVEL_FEE_PER_KM,
        store_address: DEFAULT_STORE_ADDRESS,
        store_transport_fee: DEFAULT_STORE_TRANSPORT_FEE,
        tiers: [],
    };
    const DEFAULT_REFERENCE_POINT = {
        lat: 12.2618,
        lng: 109.1995,
        maxDistance: 20,
        label: 'cửa hàng',
    };

    const tinhSelect = document.getElementById('booking_tinh');
    const huyenSelect = document.getElementById('booking_huyen');
    const xaSelect = document.getElementById('booking_xa');
    const bookingImages = document.getElementById('booking_images');
    const bookingVideo = document.getElementById('booking_video');
    const mediaPreview = document.getElementById('mediaPreview');
    const bookingNgayHen = document.getElementById('booking_ngay_hen');

    function formatCurrency(amount) {
        return `${Math.round(Number(amount) || 0).toLocaleString('vi-VN')} ₫`;
    }

    function getReferencePoint() {
        if (
            selectedWorkerContext
            && Number.isFinite(selectedWorkerContext.lat)
            && Number.isFinite(selectedWorkerContext.lng)
        ) {
            return selectedWorkerContext;
        }

        return DEFAULT_REFERENCE_POINT;
    }

    function calculateDistanceKm(fromLat, fromLng, toLat, toLng) {
        const earthRadius = 6371;
        const dLat = ((toLat - fromLat) * Math.PI) / 180;
        const dLng = ((toLng - fromLng) * Math.PI) / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos((fromLat * Math.PI) / 180) * Math.cos((toLat * Math.PI) / 180)
            * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return earthRadius * c;
    }

    function findTravelFeeTier(distanceKm) {
        return (travelFeeConfig.tiers || []).find((tier) => distanceKm >= Number(tier.from_km || 0) && distanceKm <= Number(tier.to_km || 0)) || null;
    }

    function getTierTravelFee(tier) {
        return Number(tier?.travel_fee ?? tier?.fee ?? 0);
    }

    function getTierTransportFee(tier) {
        return Number(tier?.transport_fee ?? 0);
    }

    function normalizeTravelFeeTiers(tiers) {
        return (Array.isArray(tiers) ? tiers : []).map((tier) => ({
            ...tier,
            travel_fee: getTierTravelFee(tier),
            fee: getTierTravelFee(tier),
            transport_fee: getTierTransportFee(tier),
        }));
    }

    function deriveStoreTransportFeeFromTiers(tiers) {
        return normalizeTravelFeeTiers(tiers)
            .map((tier) => tier.transport_fee)
            .find((fee) => Number.isFinite(fee) && fee > 0) ?? DEFAULT_STORE_TRANSPORT_FEE;
    }

    function getFreeDistanceKm() {
        return Math.max(0, Number(travelFeeConfig.free_distance_km ?? DEFAULT_FREE_DISTANCE_KM));
    }

    function resolveTravelFee(distanceKm) {
        if (distanceKm < getFreeDistanceKm()) {
            return 0;
        }

        const tier = findTravelFeeTier(distanceKm);

        if (tier) {
            return getTierTravelFee(tier);
        }

        return Math.round(distanceKm * Number(travelFeeConfig.default_per_km || DEFAULT_TRAVEL_FEE_PER_KM));
    }

    function describeTravelFee(referenceLabel, roundedDistance, fee, distanceKm = roundedDistance) {
        const freeDistanceKm = getFreeDistanceKm();
        if (freeDistanceKm > 0 && distanceKm < freeDistanceKm) {
            return `Táº¡m tÃ­nh theo khoáº£ng cÃ¡ch tá»« ${referenceLabel}: ${roundedDistance} km Ä‘ang Ä‘Æ°á»£c miá»…n phÃ­ vÃ¬ dÆ°á»›i ${freeDistanceKm.toLocaleString('vi-VN', { maximumFractionDigits: 2 })} km.`;
        }

        const tier = findTravelFeeTier(distanceKm);

        if (tier) {
            return `Tạm tính theo bảng khoảng cách từ ${referenceLabel}: ${roundedDistance} km áp dụng mức ${formatCurrency(fee)} cho khoảng ${tier.from_km} - ${tier.to_km} km.`;
        }

        return `Tạm tính theo khoảng cách từ ${referenceLabel}: ${roundedDistance} km × ${Number(travelFeeConfig.default_per_km || DEFAULT_TRAVEL_FEE_PER_KM).toLocaleString('vi-VN')} ₫/km.`;
    }

    function resolveTravelFeeLinear(distanceKm) {
        if (distanceKm < getFreeDistanceKm()) {
            return 0;
        }

        const tier = findTravelFeeTier(distanceKm);
        if (tier) {
            return getTierTravelFee(tier);
        }

        return Math.round(distanceKm * Number(travelFeeConfig.default_per_km || DEFAULT_TRAVEL_FEE_PER_KM));
    }

    function describeTravelFeeLinear(referenceLabel, roundedDistance, distanceKm = roundedDistance) {
        const freeDistanceKm = getFreeDistanceKm();
        if (freeDistanceKm > 0 && distanceKm < freeDistanceKm) {
            return `Tạm tính theo khoảng cách từ ${referenceLabel}: ${roundedDistance} km đang được miễn phí vì dưới ${freeDistanceKm.toLocaleString('vi-VN', { maximumFractionDigits: 2 })} km.`;
        }

        return `Tạm tính theo khoảng cách từ ${referenceLabel}: ${roundedDistance} km × ${Number(travelFeeConfig.default_per_km || DEFAULT_TRAVEL_FEE_PER_KM).toLocaleString('vi-VN')} ₫/km.`;
    }

    function describeTravelTierFee(referenceLabel, roundedDistance, distanceKm = roundedDistance) {
        const freeDistanceKm = getFreeDistanceKm();
        if (freeDistanceKm > 0 && distanceKm < freeDistanceKm) {
            return `Tạm tính theo khoảng cách từ ${referenceLabel}: ${roundedDistance} km đang được miễn phí vì dưới ${freeDistanceKm.toLocaleString('vi-VN', { maximumFractionDigits: 2 })} km.`;
        }

        const tier = findTravelFeeTier(distanceKm);
        if (tier) {
            return `Tạm tính theo bảng khoảng cách từ ${referenceLabel}: ${roundedDistance} km áp dụng mức ${formatCurrency(getTierTravelFee(tier))} cho khoảng ${tier.from_km} - ${tier.to_km} km.`;
        }

        return `Tạm tính theo khoảng cách từ ${referenceLabel}: ${roundedDistance} km × ${Number(travelFeeConfig.default_per_km || DEFAULT_TRAVEL_FEE_PER_KM).toLocaleString('vi-VN')} ₫/km.`;
    }

    function buildStoreTransportText() {
        return bookingTransportCheckbox?.checked
            ? `Đang áp dụng phí thuê xe chở thiết bị hai chiều ${formatCurrency(storeTransportFee)}.`
            : 'Bạn tự mang thiết bị đến cửa hàng nên không phát sinh phí đưa đón.';
    }

    function updateStoreTransportSummary() {
        if (bookingStoreAddressText) {
            bookingStoreAddressText.textContent = storeAddress;
        }
        if (bookingStoreFeeText) {
            bookingStoreFeeText.textContent = formatCurrency(bookingTransportCheckbox?.checked ? storeTransportFee : 0);
        }
        if (bookingStoreTransportHint) {
            bookingStoreTransportHint.textContent = buildStoreTransportText();
        }
    }

    async function loadTravelFeeConfig() {
        try {
            const res = await callApi('/travel-fee-config');
            if (!res.ok) {
                return;
            }

            const config = res.data?.data?.config;
            if (config && typeof config === 'object') {
                const tiers = normalizeTravelFeeTiers(config.tiers);
                const derivedStoreTransportFee = deriveStoreTransportFeeFromTiers(tiers);

                storeAddress = String(config.store_address || DEFAULT_STORE_ADDRESS).trim() || DEFAULT_STORE_ADDRESS;
                storeTransportFee = Number(config.store_transport_fee ?? derivedStoreTransportFee ?? DEFAULT_STORE_TRANSPORT_FEE);
                travelFeeConfig = {
                    free_distance_km: Number(config.free_distance_km ?? DEFAULT_FREE_DISTANCE_KM),
                    default_per_km: Number(config.default_per_km ?? DEFAULT_TRAVEL_FEE_PER_KM),
                    store_address: storeAddress,
                    store_transport_fee: storeTransportFee,
                    tiers,
                };
                updateStoreTransportSummary();
                updateTravelFeeEstimate();
            }
        } catch (error) {
            console.warn('Khong tai duoc cau hinh phi di lai', error);
        }
    }

    function resetTravelFeeEstimate() {
        if (!bookingTravelFeeSummary) return;

        bookingTravelFeeSummary.classList.add('d-none');
        if (bookingTravelFeeText) bookingTravelFeeText.textContent = '0 ₫';
        if (bookingDistanceText) bookingDistanceText.textContent = '0 km';
        if (bookingTravelFeeHint) {
            bookingTravelFeeHint.textContent = 'Hệ thống sẽ tính ngay sau khi bạn lấy vị trí hiện tại.';
            bookingTravelFeeHint.className = 'text-muted';
        }
    }

    function showFreeTravelEstimate() {
        if (!bookingTravelFeeSummary) return;

        bookingTravelFeeSummary.classList.remove('d-none');
        bookingTravelFeeSummary.classList.remove('alert-warning');
        bookingTravelFeeSummary.classList.add('alert-success');

        if (bookingTravelFeeText) bookingTravelFeeText.textContent = '0 ₫';
        if (bookingDistanceText) bookingDistanceText.textContent = 'Miễn phí di chuyển';
        if (bookingTravelFeeHint) {
            bookingTravelFeeHint.textContent = buildStoreTransportText();
            bookingTravelFeeHint.className = 'text-muted';
        }
        updateStoreTransportSummary();
    }

    function updateTravelFeeEstimate() {
        if (!bookingTravelFeeSummary) return;

        const isAtHome = document.getElementById('loai_hom')?.checked;
        if (!isAtHome) {
            showFreeTravelEstimate();
            return;
        }

        const lat = Number(document.getElementById('booking_vi_do')?.value);
        const lng = Number(document.getElementById('booking_kinh_do')?.value);

        if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat === 0 || lng === 0) {
            resetTravelFeeEstimate();
            return;
        }

        const reference = getReferencePoint();
        const distanceKm = calculateDistanceKm(reference.lat, reference.lng, lat, lng);
        const roundedDistance = Number(distanceKm.toFixed(1));
        const fee = resolveTravelFeeLinear(distanceKm);
        const maxDistance = Number(reference.maxDistance || DEFAULT_REFERENCE_POINT.maxDistance);
        const isOutOfRange = roundedDistance > maxDistance;

        bookingTravelFeeSummary.classList.remove('d-none');
        bookingTravelFeeSummary.classList.toggle('alert-warning', isOutOfRange);
        bookingTravelFeeSummary.classList.toggle('alert-success', !isOutOfRange);

        if (bookingTravelFeeText) bookingTravelFeeText.textContent = formatCurrency(fee);
        if (bookingDistanceText) bookingDistanceText.textContent = `${roundedDistance} km`;
        if (bookingTravelFeeHint) {
            bookingTravelFeeHint.textContent = isOutOfRange
                ? `Khoảng cách hiện tại vượt phạm vi phục vụ ${maxDistance} km của ${reference.label}.`
                : `Tạm tính theo khoảng cách từ ${reference.label}: ${roundedDistance} km × ${Number(travelFeeConfig.default_per_km || DEFAULT_TRAVEL_FEE_PER_KM).toLocaleString('vi-VN')} ₫/km.`;
            bookingTravelFeeHint.className = isOutOfRange ? 'text-danger' : 'text-muted';
        }

        if (bookingTravelFeeHint && !isOutOfRange) {
            bookingTravelFeeHint.textContent = describeTravelTierFee(reference.label, roundedDistance, distanceKm);
        }
    }

    function normalizeText(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function closeServiceDropdown() {
        if (!bookingServiceDropdown || !bookingServiceTrigger) return;
        bookingServiceDropdown.classList.add('d-none');
        bookingServiceTrigger.classList.remove('is-open');
        bookingServiceTrigger.setAttribute('aria-expanded', 'false');
    }

    function openServiceDropdown() {
        if (!bookingServiceDropdown || !bookingServiceTrigger || bookingServiceTrigger.disabled) return;
        bookingServiceDropdown.classList.remove('d-none');
        bookingServiceTrigger.classList.add('is-open');
        bookingServiceTrigger.setAttribute('aria-expanded', 'true');
    }

    function toggleServiceDropdown() {
        if (!bookingServiceDropdown) return;
        if (bookingServiceDropdown.classList.contains('d-none')) {
            openServiceDropdown();
        } else {
            closeServiceDropdown();
        }
    }

    function getSelectedServices() {
        return allBookingServices.filter((service) => selectedServiceIds.has(Number(service.id)));
    }

    function syncServiceInputs() {
        if (!bookingServiceIdsContainer) return;

        bookingServiceIdsContainer.innerHTML = '';
        Array.from(selectedServiceIds).forEach((serviceId) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'dich_vu_ids[]';
            input.value = String(serviceId);
            bookingServiceIdsContainer.appendChild(input);
        });
    }

    function renderSelectedServiceChips() {
        if (!bookingSelectedServices) return;

        const selectedServices = getSelectedServices();
        if (selectedServices.length === 0) {
            bookingSelectedServices.classList.add('d-none');
            bookingSelectedServices.innerHTML = '';
            return;
        }

        bookingSelectedServices.classList.remove('d-none');
        bookingSelectedServices.innerHTML = selectedServices.map((service) => `
            <span class="booking-selected-chip">
                <span class="material-symbols-outlined" style="font-size: 1rem;">build_circle</span>
                ${service.ten_dich_vu}
                <button
                    type="button"
                    class="booking-selected-chip-remove"
                    data-service-id="${service.id}"
                    aria-label="Xóa ${service.ten_dich_vu}"
                    title="Xóa ${service.ten_dich_vu}"
                >
                    <span class="material-symbols-outlined" style="font-size: 0.85rem;">close</span>
                </button>
            </span>
        `).join('');

        bookingSelectedServices.querySelectorAll('.booking-selected-chip-remove').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const serviceId = Number(button.dataset.serviceId);
                selectedServiceIds.delete(serviceId);
                handleSelectedServicesChange();
            });
        });
    }

    function updateServiceSummary() {
        if (!bookingServiceSummary) return;

        const selectedServices = getSelectedServices();
        if (selectedServices.length === 0) {
            bookingServiceSummary.textContent = 'Nhấn để mở danh sách dịch vụ kèm hình ảnh.';
            return;
        }

        if (selectedServices.length === 1) {
            bookingServiceSummary.textContent = `Đã chọn: ${selectedServices[0].ten_dich_vu}`;
            return;
        }

        bookingServiceSummary.textContent = `Đã chọn ${selectedServices.length} dịch vụ: ${selectedServices.map((service) => service.ten_dich_vu).join(', ')}`;
    }

    function handleSelectedServicesChange() {
        renderSelectedServiceChips();
        updateServiceSummary();
        syncServiceInputs();
        checkHeavyItemTransport();

        if (!bookingDichVuList) return;

        bookingDichVuList.querySelectorAll('.booking-service-option').forEach((option) => {
            const serviceId = Number(option.dataset.serviceId);
            const isChecked = selectedServiceIds.has(serviceId);
            option.classList.toggle('is-checked', isChecked);

            const checkbox = option.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = isChecked;
            }
        });
    }

    function setSelectedServiceIds(nextIds) {
        selectedServiceIds = new Set(
            Array.from(nextIds)
                .map((id) => Number(id))
                .filter((id) => Number.isInteger(id) && allBookingServices.some((service) => Number(service.id) === id))
        );

        handleSelectedServicesChange();
    }

    function getServiceImage(service) {
        return service.hinh_anh || '/assets/images/logontu.png';
    }

    function getServiceDescription(service) {
        return service.mo_ta || 'Chọn để thêm dịch vụ này vào cùng lịch hẹn.';
    }

    function renderServiceOptions(services, emptyLabel = 'Không có dịch vụ khả dụng') {
        allBookingServices = Array.isArray(services) ? services : [];

        if (bookingServiceTrigger) {
            bookingServiceTrigger.disabled = allBookingServices.length === 0;
        }

        if (!bookingDichVuList) return;

        if (allBookingServices.length === 0) {
            bookingDichVuList.innerHTML = `<div class="booking-service-empty">${emptyLabel}</div>`;
            setSelectedServiceIds([]);
            closeServiceDropdown();
            return;
        }

        const availableServiceIds = new Set(allBookingServices.map((service) => Number(service.id)));
        setSelectedServiceIds(Array.from(selectedServiceIds).filter((id) => availableServiceIds.has(id)));

        bookingDichVuList.innerHTML = allBookingServices.map((service) => {
            const serviceId = Number(service.id);
            const isChecked = selectedServiceIds.has(serviceId);

            return `
                <div class="booking-service-option ${isChecked ? 'is-checked' : ''}" data-service-id="${serviceId}">
                    <img class="booking-service-thumb" src="${getServiceImage(service)}" alt="${service.ten_dich_vu}" onerror="this.src='/assets/images/logontu.png'">
                    <div class="booking-service-meta">
                        <strong>${service.ten_dich_vu}</strong>
                        <small>${getServiceDescription(service)}</small>
                    </div>
                    <label class="container mb-0">
                        <input type="checkbox" value="${serviceId}" ${isChecked ? 'checked' : ''}>
                        <span class="checkmark"></span>
                    </label>
                </div>
            `;
        }).join('');

        bookingDichVuList.querySelectorAll('.booking-service-option').forEach((option) => {
            option.addEventListener('click', (event) => {
                const checkbox = option.querySelector('input[type="checkbox"]');
                if (!checkbox) return;

                if (!event.target.closest('.container')) {
                    checkbox.checked = !checkbox.checked;
                }

                const serviceId = Number(option.dataset.serviceId);
                if (checkbox.checked) {
                    selectedServiceIds.add(serviceId);
                } else {
                    selectedServiceIds.delete(serviceId);
                }

                handleSelectedServicesChange();
            });
        });

        if (allBookingServices.length === 1 && selectedServiceIds.size === 0) {
            setSelectedServiceIds([Number(allBookingServices[0].id)]);
        } else {
            handleSelectedServicesChange();
        }
    }

    function preSelectService(keywordOrList) {
        if (!keywordOrList || allBookingServices.length === 0) return;

        const keywords = Array.isArray(keywordOrList) ? keywordOrList : [keywordOrList];
        const normalizedKeywords = keywords.map(normalizeText).filter(Boolean);
        if (normalizedKeywords.length === 0) return;

        const nextIds = new Set(selectedServiceIds);
        allBookingServices.forEach((service) => {
            const normalizedName = normalizeText(service.ten_dich_vu);
            if (normalizedKeywords.some((keyword) => normalizedName.includes(keyword) || keyword.includes(normalizedName))) {
                nextIds.add(Number(service.id));
            }
        });

        setSelectedServiceIds(nextIds);
    }

    function renderSelectedWorker(worker) {
        const user = worker?.user ?? {};
        const services = user.dich_vus ?? user.dichVus ?? [];
        const serviceNames = services.map((service) => service.ten_dich_vu).filter(Boolean);
        const workerName = user.name || 'th\u1ee3 \u0111\u00e3 ch\u1ecdn';
        const workerLat = Number(worker?.vi_do);
        const workerLng = Number(worker?.kinh_do);
        const workerRadius = Number(worker?.ban_kinh_phuc_vu ?? 10);

        selectedWorkerContext = Number.isFinite(workerLat) && Number.isFinite(workerLng)
            ? {
                lat: workerLat,
                lng: workerLng,
                maxDistance: Number.isFinite(workerRadius) ? workerRadius : 10,
                label: workerName,
            }
            : null;

        if (!bookingSelectedWorkerCard) {
            updateTravelFeeEstimate();
            return;
        }

        bookingSelectedWorkerAvatar.src = user.avatar || '/assets/images/user-default.png';
        bookingSelectedWorkerName.textContent = user.name || 'Thợ sửa chữa';
        bookingSelectedWorkerServices.textContent = serviceNames.length > 0
            ? `Chuyên môn: ${serviceNames.join(', ')}`
            : 'Chưa cập nhật dịch vụ chuyên môn.';
        bookingSelectedWorkerCard.classList.remove('d-none');
        updateTravelFeeEstimate();
    }

    function hideSelectedWorker() {
        selectedWorkerContext = null;

        if (!bookingSelectedWorkerCard) {
            updateTravelFeeEstimate();
            return;
        }

        bookingSelectedWorkerCard.classList.add('d-none');
        bookingSelectedWorkerAvatar.src = '/assets/images/user-default.png';
        bookingSelectedWorkerName.textContent = 'Đang tải thông tin thợ...';
        bookingSelectedWorkerServices.textContent = 'Đang tải chuyên môn...';
        updateTravelFeeEstimate();
    }

    async function loadBookingServices() {
        if (bookingDichVuList) {
            bookingDichVuList.innerHTML = '<div class="booking-service-empty">Đang tải danh mục...</div>';
        }
        if (bookingServiceTrigger) {
            bookingServiceTrigger.disabled = true;
        }

        try {
            const workerId = bookingThoInput?.value;

            if (workerId) {
                const result = await callApi(`/ho-so-tho/${workerId}`, 'GET');
                renderSelectedWorker(result.data);
                const services = result.data?.user?.dich_vus ?? result.data?.user?.dichVus ?? [];

                if (services.length === 0) {
                    renderServiceOptions([], 'Thợ này chưa có dịch vụ khả dụng');
                    return;
                }

                renderServiceOptions(services);
            } else {
                hideSelectedWorker();
                const result = await callApi('/danh-muc-dich-vu', 'GET');
                renderServiceOptions(result.data ?? []);
            }

            preSelectService(window.PRESELECT_SERVICE);
        } catch (error) {
            hideSelectedWorker();
            renderServiceOptions([], 'Lỗi tải danh mục');
        }
    }

    if (bookingServiceTrigger) {
        bookingServiceTrigger.addEventListener('click', toggleServiceDropdown);
    }

    if (bookingServiceClear) {
        bookingServiceClear.addEventListener('click', () => {
            setSelectedServiceIds([]);
        });
    }

    document.addEventListener('click', (event) => {
        const picker = document.getElementById('bookingServicePicker');
        if (!picker || picker.contains(event.target)) return;
        closeServiceDropdown();
    });

    if (loaiDatLichRadios.length > 0) {
        loaiDatLichRadios.forEach((radio) => {
            radio.addEventListener('change', (event) => {
                const soNha = document.getElementById('booking_so_nha');

                if (event.target.value === 'at_home') {
                    atHomeGroup.classList.remove('d-none');
                    atStoreGroup.classList.add('d-none');
                    if (tinhSelect) tinhSelect.required = true;
                    if (huyenSelect) huyenSelect.required = true;
                    if (xaSelect) xaSelect.required = true;
                    if (soNha) soNha.required = true;
                    updateTravelFeeEstimate();
                } else {
                    atHomeGroup.classList.add('d-none');
                    atStoreGroup.classList.remove('d-none');
                    if (tinhSelect) { tinhSelect.required = false; tinhSelect.value = ''; }
                    if (huyenSelect) { huyenSelect.required = false; huyenSelect.value = ''; huyenSelect.disabled = true; }
                    if (xaSelect) { xaSelect.required = false; xaSelect.value = ''; xaSelect.disabled = true; }
                    if (soNha) { soNha.required = false; soNha.value = ''; }
                    document.getElementById('booking_vi_do').value = '';
                    document.getElementById('booking_kinh_do').value = '';
                    document.getElementById('booking_dia_chi').value = '';
                    updateTravelFeeEstimate();
                    bookingLocationStatus.textContent = 'Vui lòng lấy vị trí tự động hoặc chọn địa chỉ thủ công.';
                }

                checkHeavyItemTransport();
                updateAvailableTimeSlots();
            });
        });
    }

    async function loadAddressData() {
        if (addressData.length > 0) return;

        try {
            const res = await fetch('https://provinces.open-api.vn/api/?depth=3');
            addressData = await res.json();

            if (tinhSelect) {
                let html = '<option value="">Tỉnh/Thành phố</option>';
                addressData.forEach((tinh) => {
                    html += `<option value="${tinh.name}" data-code="${tinh.code}">${tinh.name}</option>`;
                });
                tinhSelect.innerHTML = html;
            }
        } catch (error) {
            console.error('Lỗi khi tải dữ liệu tỉnh thành:', error);
            if (bookingLocationStatus) {
                bookingLocationStatus.textContent = 'Lỗi khi tải dữ liệu địa chỉ. Vui lòng thử lại sau.';
            }
        }
    }

    if (tinhSelect) {
        tinhSelect.addEventListener('change', function () {
            huyenSelect.innerHTML = '<option value="">Quận/Huyện</option>';
            xaSelect.innerHTML = '<option value="">Phường/Xã</option>';
            xaSelect.disabled = true;

            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) {
                huyenSelect.disabled = true;
                return;
            }

            const code = selectedOption.getAttribute('data-code');
            const tinh = addressData.find((item) => item.code == code);
            if (tinh && tinh.districts) {
                let html = '<option value="">Quận/Huyện</option>';
                tinh.districts.forEach((huyen) => {
                    html += `<option value="${huyen.name}" data-code="${huyen.code}">${huyen.name}</option>`;
                });
                huyenSelect.innerHTML = html;
                huyenSelect.disabled = false;
            }
        });
    }

    if (huyenSelect) {
        huyenSelect.addEventListener('change', function () {
            xaSelect.innerHTML = '<option value="">Phường/Xã</option>';

            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) {
                xaSelect.disabled = true;
                return;
            }

            const tinhCode = tinhSelect.options[tinhSelect.selectedIndex].getAttribute('data-code');
            const tinh = addressData.find((item) => item.code == tinhCode);
            if (tinh) {
                const code = selectedOption.getAttribute('data-code');
                const huyen = tinh.districts.find((district) => district.code == code);
                if (huyen && huyen.wards) {
                    let html = '<option value="">Phường/Xã</option>';
                    huyen.wards.forEach((xa) => {
                        html += `<option value="${xa.name}">${xa.name}</option>`;
                    });
                    xaSelect.innerHTML = html;
                    xaSelect.disabled = false;
                }
            }
        });
    }

    function getLocalDateString(dateObj) {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function updateAvailableTimeSlots() {
        const khungGioSelect = document.getElementById('booking_khung_gio_hen');
        const isAtHome = document.getElementById('loai_hom')?.checked;
        if (!bookingNgayHen || !khungGioSelect) return;

        Array.from(khungGioSelect.options).forEach((opt, idx) => {
            if (idx > 0) {
                opt.disabled = false;
                opt.hidden = false;
            }
        });

        const dateVal = bookingNgayHen.value;
        if (!dateVal) return;

        const now = new Date();
        const todayStr = getLocalDateString(now);
        if (dateVal > todayStr) {
            return;
        }

        const currentMinutes = now.getHours() * 60 + now.getMinutes();
        let targetIndex = 0;
        if (currentMinutes < 480) targetIndex = 0;
        else if (currentMinutes < 600) targetIndex = 1;
        else if (currentMinutes < 720) targetIndex = 2;
        else if (currentMinutes < 840) targetIndex = 3;
        else targetIndex = 4;

        if (isAtHome) {
            targetIndex += 1;
        }

        let firstEnableValue = '';
        Array.from(khungGioSelect.options).forEach((opt, idx) => {
            if (idx > 0) {
                const actualIndex = idx - 1;
                if (actualIndex < targetIndex) {
                    opt.disabled = true;
                    opt.hidden = true;
                } else if (!firstEnableValue) {
                    firstEnableValue = opt.value;
                }
            }
        });

        if (khungGioSelect.selectedIndex > 0 && khungGioSelect.options[khungGioSelect.selectedIndex].disabled) {
            khungGioSelect.value = firstEnableValue;
            if (!firstEnableValue && targetIndex >= 4) {
                khungGioSelect.value = '';
            }
        }
    }

    if (bookingNgayHen) {
        bookingNgayHen.addEventListener('change', updateAvailableTimeSlots);
    }

    function renderMediaPreview() {
        if (!mediaPreview) return;
        mediaPreview.innerHTML = '';

        if (bookingImages && bookingImages.files.length > 0) {
            Array.from(bookingImages.files).forEach((file, index) => {
                if (index >= 5) return;

                const reader = new FileReader();
                reader.onload = function (loadEvent) {
                    const div = document.createElement('div');
                    div.className = 'position-relative';
                    div.style.width = '60px';
                    div.style.height = '60px';
                    div.innerHTML = `
                        <img src="${loadEvent.target.result}" class="w-100 h-100 object-cover rounded border">
                        <span class="position-absolute top-0 end-0 badge rounded-pill bg-danger" style="margin: -5px -5px 0 0; cursor: pointer; transform: scale(0.7);" onclick="removeImage(${index})">×</span>
                    `;
                    mediaPreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        if (bookingVideo && bookingVideo.files.length > 0) {
            const div = document.createElement('div');
            div.className = 'position-relative';
            div.style.width = '60px';
            div.style.height = '60px';
            div.innerHTML = `
                <div class="w-100 h-100 bg-dark rounded border d-flex align-items-center justify-content-center text-white">
                    <i class="fas fa-video"></i>
                </div>
                <span class="position-absolute top-0 end-0 badge rounded-pill bg-danger" style="margin: -5px -5px 0 0; cursor: pointer; transform: scale(0.7);" onclick="removeVideo()">×</span>
            `;
            mediaPreview.appendChild(div);
        }
    }

    function getVideoDuration(file) {
        return new Promise((resolve) => {
            const video = document.createElement('video');
            video.preload = 'metadata';
            video.onloadedmetadata = function () {
                window.URL.revokeObjectURL(video.src);
                resolve(video.duration);
            };
            video.src = URL.createObjectURL(file);
        });
    }

    function checkHeavyItemTransport() {
        const selectedNames = getSelectedServices().map((service) => normalizeText(service.ten_dich_vu));
        const keywords = ['may giat', 'tu lanh', 'tivi', 'may lanh', 'dieu hoa'];
        const isHeavy = selectedNames.some((name) => keywords.some((keyword) => name.includes(keyword)));

        const isAtStore = document.getElementById('loai_store')?.checked;
        const transportGroup = document.getElementById('transportRentalGroup');
        const transportCheckbox = bookingTransportCheckbox;

        if (transportGroup && transportCheckbox) {
            if (isHeavy && isAtStore) {
                transportGroup.classList.remove('d-none');
            } else {
                transportGroup.classList.add('d-none');
                transportCheckbox.checked = false;
            }
        }

        updateStoreTransportSummary();
    }

    function findBestMatch(targetStr, listStr) {
        if (!targetStr || !listStr || listStr.length === 0) return null;
        let match = '';
        let maxMatchCount = 0;

        const targetWords = normalizeText(targetStr).replace(/(tinh|thanh pho|quan|huyen|thi xa|phuong|xa|thi tran)/g, '').trim();

        listStr.forEach((str) => {
            const itemWords = normalizeText(str).replace(/(tinh|thanh pho|quan|huyen|thi xa|phuong|xa|thi tran)/g, '').trim();
            if (itemWords === targetWords || targetWords.includes(itemWords) || itemWords.includes(targetWords)) {
                if (itemWords.length > maxMatchCount) {
                    match = str;
                    maxMatchCount = itemWords.length;
                }
            }
        });

        return match || listStr[0];
    }

    function autoSelectOptionByText(selectEl, textToFind) {
        if (!selectEl || !textToFind) return;
        const optionsList = Array.from(selectEl.options).map((option) => option.value).filter((value) => value !== '');
        const matchedVal = findBestMatch(textToFind, optionsList);
        if (matchedVal) {
            selectEl.value = matchedVal;
            selectEl.dispatchEvent(new Event('change'));
        }
    }

    if (bookingImages) {
        bookingImages.addEventListener('change', renderMediaPreview);
    }

    if (bookingVideo) {
        bookingVideo.addEventListener('change', async function () {
            if (this.files.length > 0) {
                const duration = await getVideoDuration(this.files[0]);
                if (duration > 20) {
                    alert('Video không được vượt quá 20 giây. Vui lòng chọn video ngắn hơn.');
                    this.value = '';
                }
            }
            renderMediaPreview();
        });
    }

    if (bookingTransportCheckbox) {
        bookingTransportCheckbox.addEventListener('change', () => {
            updateTravelFeeEstimate();
        });
    }

    window.removeImage = function (index) {
        const dt = new DataTransfer();
        const { files } = bookingImages;
        for (let i = 0; i < files.length; i += 1) {
            if (i !== index) {
                dt.items.add(files[i]);
            }
        }
        bookingImages.files = dt.files;
        renderMediaPreview();
    };

    window.removeVideo = function () {
        bookingVideo.value = '';
        renderMediaPreview();
    };

    if (btnBookingGetLocation) {
        btnBookingGetLocation.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Trình duyệt không hỗ trợ định vị.');
                return;
            }

            bookingLocationStatus.textContent = 'Đang lấy tọa độ GPS...';
            btnBookingGetLocation.disabled = true;

            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    document.getElementById('booking_vi_do').value = lat;
                    document.getElementById('booking_kinh_do').value = lng;
                    updateTravelFeeEstimate();

                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`);
                        const data = await res.json();

                        if (data && data.address) {
                            bookingLocationStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Đã lấy vị trí thành công</span>';
                            const addr = data.address;
                            const pName = addr.city || addr.state || addr.province;

                            if (pName) {
                                autoSelectOptionByText(tinhSelect, pName);
                                setTimeout(() => {
                                    const dName = addr.county || addr.district || addr.suburb || addr.town;
                                    if (dName) {
                                        autoSelectOptionByText(huyenSelect, dName);
                                        setTimeout(() => {
                                            const wName = addr.village || addr.suburb || addr.quarter || addr.hamlet || addr.neighbourhood;
                                            if (wName) {
                                                autoSelectOptionByText(xaSelect, wName);
                                            }
                                        }, 100);
                                    }
                                }, 100);
                            }

                            let streetAddress = '';
                            if (addr.house_number) streetAddress += `${addr.house_number} `;
                            if (addr.road) streetAddress += addr.road;
                            if (streetAddress) {
                                document.getElementById('booking_so_nha').value = streetAddress.trim();
                            }
                        } else {
                            bookingLocationStatus.textContent = 'Không thể phân giải địa chỉ. Vui lòng chọn thủ công.';
                        }
                    } catch (error) {
                        bookingLocationStatus.textContent = 'Không kết nối API địa chỉ. Vui lòng chọn thủ công.';
                    }

                    btnBookingGetLocation.disabled = false;
                },
                () => {
                    bookingLocationStatus.textContent = 'Không lấy được vị trí. Vui lòng chọn địa chỉ thủ công.';
                    btnBookingGetLocation.disabled = false;
                }
            );
        });
    }

    if (bookingModal) {
        loadTravelFeeConfig();
        bookingModal.addEventListener('show.bs.modal', async () => {
            loadAddressData();
            updateStoreTransportSummary();

            if (window.WORKER_ID) {
                bookingThoInput.value = window.WORKER_ID;
            } else {
                bookingThoInput.value = '';
            }

            if (bookingNgayHen) {
                const now = new Date();
                const minDate = getLocalDateString(now);
                const next2Days = new Date(now.getTime() + 2 * 24 * 60 * 60 * 1000);
                const maxDate = getLocalDateString(next2Days);
                bookingNgayHen.min = minDate;
                bookingNgayHen.max = maxDate;
                if (!bookingNgayHen.value || bookingNgayHen.value < minDate || bookingNgayHen.value > maxDate) {
                    bookingNgayHen.value = minDate;
                }
            }

            updateAvailableTimeSlots();
            await loadBookingServices();
            updateTravelFeeEstimate();
        });

        bookingModal.addEventListener('hidden.bs.modal', closeServiceDropdown);
    }

    if (formBooking) {
        formBooking.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (selectedServiceIds.size === 0) {
                alert('Vui lòng chọn ít nhất một dịch vụ cần sửa.');
                openServiceDropdown();
                return;
            }

            if (document.getElementById('loai_hom')?.checked) {
                const soNha = document.getElementById('booking_so_nha').value;
                const lat = document.getElementById('booking_vi_do').value;
                const tinh = document.getElementById('booking_tinh').value;
                const huyen = document.getElementById('booking_huyen').value;
                const xa = document.getElementById('booking_xa').value;

                if (!lat) {
                    alert('Vui lòng dùng tính năng lấy vị trí hiện tại để hệ thống tính khoảng cách phục vụ.');
                    return;
                }
                if (!tinh || !huyen || !xa) {
                    alert('Vui lòng chọn đầy đủ Tỉnh/Huyện/Xã.');
                    return;
                }
                if (!soNha) {
                    alert('Vui lòng điền số nhà và tên đường cụ thể.');
                    return;
                }

                document.getElementById('booking_dia_chi').value = `${xa}, ${huyen}, ${tinh}`;
            }

            if (bookingImages && bookingImages.files.length > 5) {
                alert('Bạn chỉ có thể chọn tối đa 5 hình ảnh.');
                return;
            }

            if (bookingVideo && bookingVideo.files.length > 0) {
                const videoDuration = await getVideoDuration(bookingVideo.files[0]);
                if (videoDuration > 20) {
                    alert('Video không được vượt quá 20 giây. Vui lòng chọn video ngắn hơn.');
                    return;
                }
            }

            btnSubmitBooking.disabled = true;
            btnSubmitBooking.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';

            const formData = new FormData(formBooking);
            formData.delete('dich_vu_ids[]');
            Array.from(selectedServiceIds).forEach((serviceId) => {
                formData.append('dich_vu_ids[]', String(serviceId));
            });

            const thueXe = document.getElementById('booking_thue_xe_cho').checked ? 1 : 0;
            formData.set('thue_xe_cho', thueXe);

            if (document.getElementById('loai_hom').checked) {
                const diaChi = `${formData.get('so_nha')}, ${formData.get('dia_chi')}`;
                formData.set('dia_chi', diaChi);
                formData.delete('so_nha');
            }

            if (!formData.get('tho_id')) {
                formData.delete('tho_id');
            }

            try {
                const res = await callApi('/don-dat-lich', 'POST', formData);

                if (!res.ok) {
                    const errData = res.data;
                    if (errData?.errors) {
                        alert(Object.values(errData.errors).flat().join('\n'));
                    } else if (errData?.message) {
                        alert(errData.message);
                    } else {
                        alert(`Có lỗi xảy ra khi đặt lịch. (HTTP ${res.status})`);
                    }
                    return;
                }

                const booking = res.data.data ?? res.data;
                alert(`Đặt lịch thành công! Mã đơn: #${booking.id}`);
                bootstrap.Modal.getInstance(bookingModal).hide();
                formBooking.reset();
                setSelectedServiceIds([]);
                atHomeGroup.classList.remove('d-none');
                atStoreGroup.classList.add('d-none');

                setTimeout(() => {
                    window.location.href = '/customer/my-bookings';
                }, 1000);
            } catch (error) {
                console.error('Booking error:', error);
                alert(error.message || 'Mất kết nối đến máy chủ. Vui lòng thử lại.');
            } finally {
                btnSubmitBooking.disabled = false;
                btnSubmitBooking.innerHTML = 'Xác nhận đặt lịch';
            }
        });
    }
});
