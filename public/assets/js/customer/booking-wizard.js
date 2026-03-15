import { callApi, getCurrentUser, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('bookingWizardModal');
    if (!root) return;

    const STORE_ADDRESS = '2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa';
    const STORE_REFERENCE = { lat: 12.2618, lng: 109.1995, maxDistance: 20, label: 'cửa hàng' };
    const TRAVEL_FEE_PER_KM = 5000;
    const TIME_SLOTS = ['08:00-10:00', '10:00-12:00', '12:00-14:00', '14:00-17:00'];
    const STEP_META = {
        1: ['Bước 1 trên 5', 'ĐẶT LỊCH SỬA CHỮA', 'Chọn một hoặc nhiều dịch vụ phù hợp để bắt đầu lịch hẹn với Thợ Tốt NTU.'],
        2: ['Bước 2 trên 5', 'CHỌN HÌNH THỨC SỬA CHỮA', 'Xác định kỹ thuật viên sẽ đến tận nơi hay bạn mang thiết bị đến cửa hàng.'],
        3: ['Bước 3 trên 5', 'ĐỊA CHỈ SỬA CHỮA', 'Cung cấp vị trí chính xác để hệ thống điều phối kỹ thuật viên và ước tính phí di chuyển.'],
        4: ['Bước 4 trên 5', 'CHỌN NGÀY VÀ GIỜ', 'Chọn thời điểm thuận tiện nhất trong ba ngày gần nhất mà hệ thống đang mở lịch.'],
        5: ['Bước 5 trên 5', 'MÔ TẢ & HÌNH ẢNH', 'Bổ sung mô tả, hình ảnh và video để thợ chuẩn bị dụng cụ sát với tình trạng thực tế.'],
    };
    const params = new URLSearchParams(window.location.search);
    const isStandalone = root.dataset.standalone === '1';
    const standalonePrefill = { workerId: params.get('worker_id') || '', serviceName: params.get('service_name') || '' };
    const state = {
        addressData: [], currentStep: 1, workerId: null, worker: null, prefillServiceName: '', services: [], serviceIds: [],
        repairMode: null, tinh: '', huyen: '', xa: '', soNha: '', lat: '', lng: '', travelFee: 0, distanceKm: null,
        travelMessage: 'Sẽ tính sau khi bạn chọn vị trí.', date: '', timeSlot: '', description: '', images: [], video: null,
        transportRequested: false, isOutOfRange: false, isOpen: false,
    };
    const $ = (id) => document.getElementById(id);
    const refs = {
        form: $('bookingWizardForm'), main: root.querySelector('.booking-wizard-main'), close: $('bookingWizardCloseButton'),
        title: $('bookingWizardTitle'), copy: $('bookingWizardCopy'), kicker: $('bookingWizardKicker'), badge: $('bookingWizardStepBadge'),
        progress: $('bookingWizardProgressFill'), prev: $('bookingWizardPrevButton'), next: $('bookingWizardNextButton'),
        servicesWrap: $('bookingWizardServices'), servicesEmpty: $('bookingWizardServicesEmpty'), workerBanner: $('bookingWizardWorkerBanner'),
        workerAvatar: $('bookingWizardWorkerAvatar'), workerName: $('bookingWizardWorkerName'), workerMeta: $('bookingWizardWorkerMeta'),
        atHome: $('bookingWizardAtHomePanel'), atStore: $('bookingWizardAtStorePanel'), transportWrap: $('bookingWizardTransportWrap'),
        transportToggle: $('bookingWizardTransportToggle'), getLocation: $('bookingWizardGetLocation'), locationStatus: $('bookingWizardLocationStatus'),
        tinh: $('bookingWizardTinh'), huyen: $('bookingWizardHuyen'), xa: $('bookingWizardXa'), soNha: $('bookingWizardSoNha'),
        dateCards: $('bookingWizardDateCards'), timeSlots: $('bookingWizardTimeSlots'), description: $('bookingWizardDescription'),
        uploadZone: $('bookingWizardUploadZone'), mediaPicker: $('bookingWizardMediaPicker'), imagesInput: $('bookingWizardImages'),
        videoInput: $('bookingWizardVideo'), preview: $('bookingWizardPreview'), success: $('bookingWizardSuccess'), successCode: $('bookingWizardSuccessCode'),
        hiddenWorkerId: $('bookingWizardWorkerId'), hiddenServiceId: $('bookingWizardServiceId'), hiddenRepairMode: $('bookingWizardRepairMode'),
        hiddenLat: $('bookingWizardLat'), hiddenLng: $('bookingWizardLng'), hiddenDiaChi: $('bookingWizardDiaChi'), hiddenDate: $('bookingWizardDate'),
        hiddenTimeSlot: $('bookingWizardTimeSlot'), hiddenStoreTransport: $('bookingWizardStoreTransport'),
        summaryTitle: $('bookingSummaryTitle'), summarySheet: $('bookingSummarySheet'),
        sumServiceThumb: $('bookingSummaryServiceThumb'), sumWorkerThumb: $('bookingSummaryWorkerThumb'), sumModeMark: $('bookingSummaryModeMark'),
        sumServiceCard: $('bookingSummaryServiceCard'), sumServiceValue: $('bookingSummaryServiceValue'), sumServiceMeta: $('bookingSummaryServiceMeta'),
        sumWorkerCard: $('bookingSummaryWorkerCard'), sumWorkerValue: $('bookingSummaryWorkerValue'), sumWorkerMeta: $('bookingSummaryWorkerMeta'),
        sumModeCard: $('bookingSummaryModeCard'), sumTimeCard: $('bookingSummaryTimeCard'), sumAddressCard: $('bookingSummaryAddressCard'),
        sumModeValue: $('bookingSummaryModeValue'), sumModeMeta: $('bookingSummaryModeMeta'), sumTimeValue: $('bookingSummaryTimeValue'),
        sumTimeMeta: $('bookingSummaryTimeMeta'), sumAddressValue: $('bookingSummaryAddressValue'), sumAddressMeta: $('bookingSummaryAddressMeta'),
        sumTravelCard: $('bookingSummaryTravelCard'),
        sumTravelFee: $('bookingSummaryTravelFee'), sumTravelMeta: $('bookingSummaryTravelMeta'),
    };
    const panels = Array.from(root.querySelectorAll('[data-step-panel]'));
    const stepButtons = Array.from(root.querySelectorAll('[data-step-target]'));
    const repairCards = Array.from(root.querySelectorAll('[data-repair-mode]'));
    const norm = (v) => String(v || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const money = (v) => `${Math.round(Number(v) || 0).toLocaleString('vi-VN')} ₫`;
    const selectedServices = () => state.services.filter((item) => state.serviceIds.includes(Number(item.id)));
    const heavyService = () => selectedServices().some((service) => ['may giat', 'tu lanh', 'tivi', 'may lanh', 'dieu hoa'].some((k) => norm(service.ten_dich_vu).includes(k)));
    const localDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const humanDate = (value) => value ? new Intl.DateTimeFormat('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(`${value}T00:00:00`)) : 'Chưa chọn ngày';
    const sleep = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));
    const simplifyAdminName = (value) => norm(value)
        .replace(/^(tinh|thanh pho|tp|quan|huyen|thi xa|thi tran|phuong|xa)\s+/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    const pickOptionValue = (select, text) => {
        const rawText = norm(text);
        const simpleText = simplifyAdminName(text);
        const options = Array.from(select.options).map((option) => option.value).filter(Boolean);
        return options.find((value) => {
            const rawValue = norm(value);
            const simpleValue = simplifyAdminName(value);
            return rawValue === rawText
                || simpleValue === simpleText
                || rawValue.includes(rawText)
                || rawText.includes(rawValue)
                || simpleValue.includes(simpleText)
                || simpleText.includes(simpleValue);
        }) || '';
    };
    const ensureAuth = () => { try { const user = getCurrentUser(); if (user && ['customer', 'admin'].includes(user.role)) return true; } catch (error) {} window.location.href = '/login'; return false; };
    const distKm = (a, b, c, d) => { const r = 6371, dLat = ((c - a) * Math.PI) / 180, dLng = ((d - b) * Math.PI) / 180; const x = Math.sin(dLat / 2) ** 2 + Math.cos((a * Math.PI) / 180) * Math.cos((c * Math.PI) / 180) * Math.sin(dLng / 2) ** 2; return r * (2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x))); };

    function renderProvinceOptions() {
        refs.tinh.innerHTML = '<option value="">Chọn tỉnh / thành phố</option>' + state.addressData.map((t) => `<option value="${t.name}" data-code="${t.code}">${t.name}</option>`).join('');
        refs.huyen.innerHTML = '<option value="">Chọn quận / huyện</option>';
        refs.xa.innerHTML = '<option value="">Chọn phường / xã</option>';
        refs.huyen.disabled = true;
        refs.xa.disabled = true;
    }

    function resetState(prefill = {}) {
        const addressData = state.addressData;
        Object.assign(state, {
            addressData, currentStep: 1, workerId: prefill.workerId ? Number(prefill.workerId) : null, worker: null,
            prefillServiceName: String(prefill.serviceName || ''), services: [], serviceIds: [], repairMode: null,
            tinh: '', huyen: '', xa: '', soNha: '', lat: '', lng: '', travelFee: 0, distanceKm: null, travelMessage: 'Sẽ tính sau khi bạn chọn vị trí.',
            date: '', timeSlot: '', description: '', images: [], video: null, transportRequested: false, isOutOfRange: false,
        });
        refs.form.reset();
        refs.success.classList.add('d-none');
        refs.workerBanner.classList.add('d-none');
        refs.sumWorkerCard.classList.add('d-none');
        refs.locationStatus.textContent = 'Vui lòng lấy vị trí hiện tại hoặc nhập địa chỉ thủ công.';
        refs.preview.innerHTML = '';
        if (state.addressData.length) renderProvinceOptions();
    }

    function syncHidden() {
        refs.hiddenWorkerId.value = state.workerId ? String(state.workerId) : '';
        refs.hiddenServiceId.value = state.serviceIds[0] ? String(state.serviceIds[0]) : '';
        refs.hiddenRepairMode.value = state.repairMode || '';
        refs.hiddenLat.value = state.lat || '';
        refs.hiddenLng.value = state.lng || '';
        refs.hiddenDate.value = state.date || '';
        refs.hiddenTimeSlot.value = state.timeSlot || '';
        refs.hiddenStoreTransport.value = state.transportRequested ? '1' : '0';
        refs.hiddenDiaChi.value = state.repairMode === 'at_store' ? STORE_ADDRESS : (state.xa && state.huyen && state.tinh ? `${state.xa}, ${state.huyen}, ${state.tinh}` : '');
    }

    function updateHeader() {
        const [kicker, title, copy] = STEP_META[state.currentStep];
        refs.kicker.textContent = kicker;
        refs.title.textContent = title;
        refs.copy.textContent = copy;
        refs.badge.textContent = `${state.currentStep} / 5`;
        refs.progress.style.width = `${state.currentStep * 20}%`;
        refs.prev.classList.toggle('d-none', state.currentStep === 1);
        refs.next.textContent = state.currentStep === 5 ? 'Xác nhận đặt lịch' : 'Tiếp tục';
        stepButtons.forEach((button) => button.classList.toggle('is-active', Number(button.dataset.stepTarget) === state.currentStep));
        repairCards.forEach((card) => card.classList.toggle('is-selected', card.dataset.repairMode === state.repairMode));
    }

    function updateAddressPanels() {
        const atHome = state.repairMode === 'at_home';
        const atStore = state.repairMode === 'at_store';
        refs.atHome.classList.toggle('d-none', !atHome);
        refs.atStore.classList.toggle('d-none', !atStore);
        const showTransport = atStore && heavyService();
        refs.transportWrap.classList.toggle('d-none', !showTransport);
        if (!showTransport) state.transportRequested = false;
    }

    function updateSummary() {
        const services = selectedServices();
        const names = services.slice(0, 2).map((service) => service.ten_dich_vu).join(', ');
        const extra = Math.max(services.length - 2, 0);
        const hasService = services.length > 0;
        const hasWorker = Boolean(state.worker);
        const hasMode = Boolean(state.repairMode);
        const hasTime = Boolean(state.date && state.timeSlot);
        const hasAddress = Boolean(
            state.repairMode === 'at_store'
            || (state.soNha && state.xa && state.huyen && state.tinh)
        );
        const hasTravel = Boolean(
            state.repairMode === 'at_store'
            || state.travelFee > 0
            || (state.repairMode === 'at_home' && (state.lat || state.lng))
        );
        const hasContent = Boolean(
            hasService
            || hasWorker
            || hasMode
            || hasTime
            || hasAddress
            || hasTravel
        );

        refs.summaryTitle.classList.toggle('d-none', !hasContent);
        refs.summarySheet.classList.toggle('d-none', !hasContent);
        refs.sumTravelCard.classList.toggle('d-none', !hasTravel);

        refs.sumServiceCard.classList.toggle('d-none', !hasService);
        refs.sumServiceValue.textContent = hasService ? `${names}${extra ? ` +${extra}` : ''}` : '';
        refs.sumServiceMeta.textContent = services.length > 1 ? `${services.length} dịch vụ trong cùng một lịch hẹn.` : (services[0]?.mo_ta || '');
        refs.sumServiceThumb.src = services[0]?.hinh_anh || '/assets/images/logontu.png';

        refs.sumWorkerCard.classList.toggle('d-none', !hasWorker);
        if (hasWorker) {
            refs.sumWorkerThumb.src = state.worker?.user?.avatar || '/assets/images/user-default.png';
            refs.sumWorkerValue.textContent = state.worker?.user?.name || 'Thợ sửa chữa';
            refs.sumWorkerMeta.textContent = state.worker?.user?.dich_vus?.map((service) => service.ten_dich_vu).join(', ')
                || state.worker?.user?.dichVus?.map((service) => service.ten_dich_vu).join(', ')
                || refs.sumWorkerMeta.textContent
                || '';
        }

        refs.sumModeCard.classList.toggle('d-none', !hasMode);
        refs.sumModeValue.textContent = state.repairMode === 'at_home' ? 'Sửa tại nhà' : 'Mang đến cửa hàng';
        refs.sumModeMeta.textContent = state.repairMode === 'at_home'
            ? 'Kỹ thuật viên đến tận nơi.'
            : (state.transportRequested ? 'Có thuê xe chở thiết bị hai chiều.' : 'Bạn tự mang thiết bị đến cửa hàng.');
        refs.sumModeMark.textContent = state.repairMode === 'at_home' ? '🏠' : '🏪';

        refs.sumTimeCard.classList.toggle('d-none', !hasTime);
        refs.sumTimeValue.textContent = hasTime ? `${humanDate(state.date)} • ${state.timeSlot.replace('-', ' - ')}` : '';
        refs.sumTimeMeta.textContent = hasTime ? 'Khung giờ bạn đã chọn.' : '';

        refs.sumAddressCard.classList.toggle('d-none', !hasAddress);
        refs.sumAddressValue.textContent = state.repairMode === 'at_store'
            ? STORE_ADDRESS
            : `${state.soNha}, ${state.xa}, ${state.huyen}, ${state.tinh}`;
        refs.sumAddressMeta.textContent = state.repairMode === 'at_store'
            ? 'Địa chỉ tiếp nhận thiết bị.'
            : (state.lat && state.lng ? 'Địa chỉ đã gắn GPS.' : 'Địa chỉ nhập thủ công.');

        refs.sumTravelFee.textContent = money(state.travelFee);
        refs.sumTravelMeta.textContent = state.travelMessage;
    }

    function updateTravelEstimate() {
        if (state.repairMode !== 'at_home') {
            state.travelFee = 0;
            state.distanceKm = 0;
            state.isOutOfRange = false;
            state.travelMessage = 'Bạn chọn mang thiết bị đến cửa hàng nên không phát sinh phí đi lại.';
            updateSummary();
            return;
        }
        const lat = Number(state.lat);
        const lng = Number(state.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat === 0 || lng === 0) {
            state.travelFee = 0;
            state.distanceKm = null;
            state.isOutOfRange = false;
            state.travelMessage = 'Sẽ tính sau khi bạn chọn vị trí.';
            updateSummary();
            return;
        }
        const refPoint = Number.isFinite(Number(state.worker?.vi_do)) && Number.isFinite(Number(state.worker?.kinh_do))
            ? { lat: Number(state.worker.vi_do), lng: Number(state.worker.kinh_do), maxDistance: Number(state.worker?.ban_kinh_phuc_vu ?? 10), label: state.worker?.user?.name || 'thợ đã chọn' }
            : STORE_REFERENCE;
        const distanceKm = distKm(refPoint.lat, refPoint.lng, lat, lng);
        const rounded = Number(distanceKm.toFixed(1));
        state.travelFee = Math.round(distanceKm * TRAVEL_FEE_PER_KM);
        state.distanceKm = rounded;
        state.isOutOfRange = rounded > Number(refPoint.maxDistance || STORE_REFERENCE.maxDistance);
        state.travelMessage = state.isOutOfRange ? `Khoảng cách hiện tại vượt phạm vi phục vụ ${refPoint.maxDistance} km của ${refPoint.label}.` : `Tạm tính ${rounded} km × ${TRAVEL_FEE_PER_KM.toLocaleString('vi-VN')} ₫/km từ ${refPoint.label}.`;
        updateSummary();
    }

    function goToStep(step, skipAnimation = false) {
        const previous = state.currentStep;
        state.currentStep = step;
        panels.forEach((panel) => panel.classList.remove('is-active', 'step-enter-forward', 'step-enter-backward'));
        const active = panels.find((panel) => Number(panel.dataset.stepPanel) === step);
        if (active) {
            active.classList.add('is-active');
            if (!skipAnimation && previous !== step) {
                const className = step > previous ? 'step-enter-forward' : 'step-enter-backward';
                active.classList.add(className);
                active.addEventListener('animationend', () => active.classList.remove(className), { once: true });
            }
        }
        updateHeader();
        refs.main?.scrollTo({ top: 0, behavior: skipAnimation ? 'auto' : 'smooth' });
    }

    function renderDateCards() {
        const labels = ['Hôm nay', 'Ngày mai', 'Ngày mốt'];
        refs.dateCards.innerHTML = [0, 1, 2].map((offset) => {
            const date = new Date();
            date.setHours(0, 0, 0, 0);
            date.setDate(date.getDate() + offset);
            const value = localDate(date);
            return `<button type="button" class="booking-date-card ${value === state.date ? 'is-selected' : ''}" data-date-value="${value}"><span class="booking-date-label">${labels[offset]}</span><span class="booking-date-weekday">${new Intl.DateTimeFormat('vi-VN', { weekday: 'long' }).format(date)}</span><span class="booking-date-day">${String(date.getDate()).padStart(2, '0')}</span><span class="booking-date-month">Tháng ${date.getMonth() + 1}</span></button>`;
        }).join('');
        refs.dateCards.querySelectorAll('[data-date-value]').forEach((button) => button.addEventListener('click', () => {
            state.date = button.dataset.dateValue;
            renderDateCards();
            renderTimeSlots();
            syncHidden();
            updateSummary();
        }));
    }

    function availableTimeSlots() {
        const slots = TIME_SLOTS.map((slot) => ({ value: slot, disabled: false }));
        if (!state.date) return slots;
        const now = new Date();
        if (state.date > localDate(now)) return slots;
        const mins = now.getHours() * 60 + now.getMinutes();
        let target = mins < 480 ? 0 : mins < 600 ? 1 : mins < 720 ? 2 : mins < 840 ? 3 : 4;
        if (state.repairMode === 'at_home') target += 1;
        return slots.map((slot, index) => ({ ...slot, disabled: index < target }));
    }

    function renderTimeSlots() {
        refs.timeSlots.innerHTML = availableTimeSlots().map((slot) => `<button type="button" class="booking-time-slot ${slot.value === state.timeSlot ? 'is-selected' : ''} ${slot.disabled ? 'is-disabled' : ''}" data-time-slot="${slot.value}" ${slot.disabled ? 'disabled' : ''}>${slot.value.replace('-', ' - ')}</button>`).join('');
        refs.timeSlots.querySelectorAll('[data-time-slot]').forEach((button) => button.addEventListener('click', () => {
            if (button.disabled) return;
            state.timeSlot = button.dataset.timeSlot;
            renderTimeSlots();
            syncHidden();
            updateSummary();
        }));
    }

    function renderServices() {
        refs.servicesEmpty.classList.toggle('d-none', state.services.length > 0);
        refs.servicesWrap.classList.toggle('d-none', state.services.length === 0);
        if (!state.services.length) return;
        refs.servicesWrap.innerHTML = state.services.map((service) => {
            const serviceId = Number(service.id);
            return `<button type="button" class="booking-service-card ${state.serviceIds.includes(serviceId) ? 'is-selected' : ''}" data-service-id="${serviceId}"><div class="booking-service-card-check">✓</div><div class="booking-service-card-media"><img src="${service.hinh_anh || '/assets/images/logontu.png'}" alt="${service.ten_dich_vu}" onerror="this.src='/assets/images/logontu.png'"></div><div class="booking-service-card-body"><div class="booking-service-card-title">${service.ten_dich_vu}</div><p class="booking-service-card-copy">${service.mo_ta || 'Dịch vụ sửa chữa chuyên sâu, kỹ thuật viên sẽ kiểm tra và xử lý theo tình trạng thực tế.'}</p></div></button>`;
        }).join('');
        refs.servicesWrap.querySelectorAll('[data-service-id]').forEach((button) => button.addEventListener('click', () => {
            const serviceId = Number(button.dataset.serviceId);
            state.serviceIds = state.serviceIds.includes(serviceId) ? state.serviceIds.filter((id) => id !== serviceId) : [...state.serviceIds, serviceId];
            state.transportRequested = false;
            refs.transportToggle.checked = false;
            renderServices();
            syncHidden();
            updateAddressPanels();
            updateTravelEstimate();
            updateSummary();
        }));
    }

    async function autoFillAdministrativeAddress(geoData) {
        const addr = geoData?.address;
        if (!addr) return false;

        const displayParts = String(geoData?.display_name || '')
            .split(',')
            .map((part) => part.trim())
            .filter(Boolean);
        const uniqueCandidates = (items) => [...new Set(items.filter(Boolean))];

        const provinceCandidates = uniqueCandidates([
            addr.city,
            addr.state,
            addr.province,
            addr.region,
            addr['ISO3166-2-lvl4'],
            ...displayParts,
        ]);

        const districtCandidates = uniqueCandidates([
            addr.city_district,
            addr.county,
            addr.district,
            addr.state_district,
            addr.borough,
            addr.town,
            addr.city,
            addr.suburb,
            addr.municipality,
            ...displayParts,
        ]);

        const wardCandidates = uniqueCandidates([
            addr.city_block,
            addr.borough,
            addr.suburb,
            addr.quarter,
            addr.neighbourhood,
            addr.village,
            addr.hamlet,
            addr.residential,
            addr.allotments,
            ...displayParts,
        ]);

        let matchedProvince = '';
        for (const candidate of provinceCandidates) {
            matchedProvince = pickOptionValue(refs.tinh, candidate);
            if (matchedProvince) break;
        }
        if (!matchedProvince) return false;

        refs.tinh.value = matchedProvince;
        refs.tinh.dispatchEvent(new Event('change'));
        await sleep(80);

        let matchedDistrict = '';
        for (const candidate of districtCandidates) {
            matchedDistrict = pickOptionValue(refs.huyen, candidate);
            if (matchedDistrict) break;
        }
        if (matchedDistrict) {
            refs.huyen.value = matchedDistrict;
            refs.huyen.dispatchEvent(new Event('change'));
            await sleep(80);
        }

        let matchedWard = '';
        for (const candidate of wardCandidates) {
            matchedWard = pickOptionValue(refs.xa, candidate);
            if (matchedWard) break;
        }
        if (matchedWard) {
            refs.xa.value = matchedWard;
            refs.xa.dispatchEvent(new Event('change'));
        }

        return Boolean(matchedProvince && matchedDistrict && matchedWard);
    }

    async function loadAddressData() {
        if (state.addressData.length) {
            renderProvinceOptions();
            return;
        }
        try {
            const response = await fetch('https://provinces.open-api.vn/api/?depth=3');
            state.addressData = await response.json();
            renderProvinceOptions();
        } catch (error) {
            refs.locationStatus.textContent = 'Lỗi khi tải dữ liệu địa chỉ. Vui lòng thử lại sau.';
        }
    }

    async function loadWorkerAndServices() {
        try {
            if (state.workerId) {
                const result = await callApi(`/ho-so-tho/${state.workerId}`, 'GET');
                if (!result.ok) throw new Error(result.data?.message || 'Không tìm thấy thợ đã chọn.');
                state.worker = result.data;
                state.services = Array.isArray(state.worker?.user?.dich_vus ?? state.worker?.user?.dichVus) ? (state.worker.user.dich_vus || state.worker.user.dichVus) : [];
                const userData = state.worker.user || {};
                const serviceNames = state.services.map((service) => service.ten_dich_vu).join(', ') || 'Chưa cập nhật chuyên môn';
                refs.workerBanner.classList.remove('d-none');
                refs.sumWorkerCard.classList.remove('d-none');
                refs.workerAvatar.src = userData.avatar || '/assets/images/user-default.png';
                refs.workerName.textContent = userData.name || 'Thợ sửa chữa';
                refs.workerMeta.textContent = `Chuyên môn: ${serviceNames}`;
                refs.sumWorkerValue.textContent = userData.name || 'Thợ sửa chữa';
                refs.sumWorkerMeta.textContent = serviceNames;
            } else {
                const result = await callApi('/danh-muc-dich-vu', 'GET');
                if (!result.ok) throw new Error(result.data?.message || 'Không tải được danh mục dịch vụ.');
                state.services = Array.isArray(result.data) ? result.data : [];
            }
            if (state.prefillServiceName) {
                const target = norm(state.prefillServiceName);
                const matched = state.services.find((service) => {
                    const name = norm(service.ten_dich_vu);
                    return name.includes(target) || target.includes(name);
                });
                if (matched) state.serviceIds = [Number(matched.id)];
            }
            if (!state.serviceIds.length && state.services.length === 1) state.serviceIds = [Number(state.services[0].id)];
            renderServices();
            syncHidden();
            updateAddressPanels();
            updateTravelEstimate();
            updateSummary();
        } catch (error) {
            refs.servicesWrap.classList.add('d-none');
            refs.servicesEmpty.classList.remove('d-none');
            showToast(error.message || 'Không tải được danh mục dịch vụ.', 'error');
        }
    }

    function validateStep(step) {
        if (step === 1 && !state.serviceIds.length) return { valid: false, message: 'Vui lòng chọn ít nhất một dịch vụ để tiếp tục.' };
        if (step === 2 && !state.repairMode) return { valid: false, message: 'Vui lòng chọn hình thức sửa chữa.' };
        if (step === 3 && state.repairMode === 'at_home') {
            if (!state.lat || !state.lng) return { valid: false, message: 'Vui lòng lấy vị trí hiện tại để hệ thống tính phí di chuyển.' };
            if (state.isOutOfRange) return { valid: false, message: 'Vị trí hiện tại đang vượt phạm vi phục vụ. Vui lòng chọn địa chỉ gần hơn hoặc mang đến cửa hàng.' };
            if (!state.tinh || !state.huyen || !state.xa) return { valid: false, message: 'Vui lòng chọn đầy đủ Tỉnh / Huyện / Xã.' };
            if (!state.soNha.trim()) return { valid: false, message: 'Vui lòng nhập địa chỉ chi tiết.' };
        }
        if (step === 4 && !state.date) return { valid: false, message: 'Vui lòng chọn ngày hẹn.' };
        if (step === 4 && !state.timeSlot) return { valid: false, message: 'Vui lòng chọn khung giờ.' };
        return { valid: true };
    }

    async function submitBooking() {
        for (let step = 1; step <= 5; step += 1) {
            const validation = validateStep(step);
            if (!validation.valid) {
                goToStep(step);
                showToast(validation.message, 'error');
                return;
            }
        }
        refs.next.disabled = true;
        refs.next.textContent = 'Đang xử lý...';
        syncHidden();
        const formData = new FormData(refs.form);
        formData.delete('dich_vu_ids[]');
        state.serviceIds.forEach((serviceId) => formData.append('dich_vu_ids[]', String(serviceId)));
        formData.set('dich_vu_id', String(state.serviceIds[0]));
        formData.set('loai_dat_lich', state.repairMode);
        formData.set('ngay_hen', state.date);
        formData.set('khung_gio_hen', state.timeSlot);
        formData.set('mo_ta_van_de', state.description || '');
        formData.set('thue_xe_cho', state.transportRequested ? '1' : '0');
        if (state.repairMode === 'at_home') formData.set('dia_chi', `${state.soNha}, ${refs.hiddenDiaChi.value}`);
        else {
            formData.set('dia_chi', STORE_ADDRESS);
            formData.set('vi_do', '');
            formData.set('kinh_do', '');
        }
        if (!state.workerId) formData.delete('tho_id');
        try {
            const result = await callApi('/don-dat-lich', 'POST', formData);
            if (!result.ok) {
                const errorData = result.data;
                showToast(errorData?.errors ? Object.values(errorData.errors).flat().join('\n') : (errorData?.message || 'Có lỗi xảy ra khi đặt lịch.'), 'error');
                return;
            }
            const booking = result.data?.data ?? result.data;
            refs.successCode.textContent = `TT-${String(booking.id).padStart(5, '0')}`;
            refs.success.classList.remove('d-none');
        } catch (error) {
            showToast(error.message || 'Mất kết nối đến máy chủ. Vui lòng thử lại.', 'error');
        } finally {
            refs.next.disabled = false;
            updateHeader();
        }
    }

    async function openModal(options = {}) {
        if (!ensureAuth()) return;
        resetState({ workerId: options.workerId ?? (isStandalone ? standalonePrefill.workerId : ''), serviceName: options.serviceName ?? (isStandalone ? standalonePrefill.serviceName : '') });
        renderDateCards();
        renderTimeSlots();
        updateAddressPanels();
        syncHidden();
        updateSummary();
        goToStep(1, true);
        root.classList.remove('d-none');
        root.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => root.classList.add('is-open'));
        document.body.style.overflow = 'hidden';
        document.body.classList.add('booking-wizard-open');
        state.isOpen = true;
        await loadAddressData();
        await loadWorkerAndServices();
    }

    function closeModal() {
        if (!state.isOpen) return;
        if (isStandalone) {
            window.location.href = '/customer/home';
            return;
        }
        refs.success.classList.add('d-none');
        root.classList.remove('is-open');
        root.setAttribute('aria-hidden', 'true');
        state.isOpen = false;
        window.setTimeout(() => {
            root.classList.add('d-none');
            document.body.style.overflow = '';
            document.body.classList.remove('booking-wizard-open');
        }, 280);
    }

    window.BookingWizardModal = { open: openModal, close: closeModal };

    stepButtons.forEach((button) => button.addEventListener('click', () => {
        const step = Number(button.dataset.stepTarget);
        if (step <= state.currentStep) goToStep(step);
    }));
    repairCards.forEach((card) => card.addEventListener('click', () => {
        state.repairMode = card.dataset.repairMode;
        if (state.repairMode === 'at_store') {
            state.lat = '';
            state.lng = '';
            refs.locationStatus.textContent = 'Không cần lấy vị trí khi mang thiết bị đến cửa hàng.';
        }
        if (availableTimeSlots().find((slot) => slot.value === state.timeSlot && slot.disabled)) state.timeSlot = '';
        syncHidden();
        updateHeader();
        updateAddressPanels();
        renderTimeSlots();
        updateTravelEstimate();
        updateSummary();
    }));
    refs.prev.addEventListener('click', () => { if (state.currentStep > 1) goToStep(state.currentStep - 1); });
    refs.next.addEventListener('click', async () => {
        if (state.currentStep === 5) {
            await submitBooking();
            return;
        }
        const validation = validateStep(state.currentStep);
        if (!validation.valid) {
            showToast(validation.message, 'error');
            return;
        }
        goToStep(state.currentStep + 1);
    });
    refs.close.addEventListener('click', closeModal);
    root.addEventListener('click', (event) => { if (event.target === root) closeModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && state.isOpen) closeModal(); });
    refs.transportToggle.addEventListener('change', () => { state.transportRequested = refs.transportToggle.checked; syncHidden(); updateSummary(); });
    refs.tinh.addEventListener('change', () => {
        state.tinh = refs.tinh.value;
        state.huyen = '';
        state.xa = '';
        refs.huyen.innerHTML = '<option value="">Chọn quận / huyện</option>';
        refs.xa.innerHTML = '<option value="">Chọn phường / xã</option>';
        refs.xa.disabled = true;
        const selectedCode = refs.tinh.options[refs.tinh.selectedIndex]?.getAttribute('data-code');
        const tinh = state.addressData.find((item) => String(item.code) === String(selectedCode));
        refs.huyen.innerHTML += (tinh?.districts || []).map((huyen) => `<option value="${huyen.name}" data-code="${huyen.code}">${huyen.name}</option>`).join('');
        refs.huyen.disabled = !(tinh?.districts || []).length;
        syncHidden();
        updateSummary();
    });
    refs.huyen.addEventListener('change', () => {
        state.huyen = refs.huyen.value;
        state.xa = '';
        const tinhCode = refs.tinh.options[refs.tinh.selectedIndex]?.getAttribute('data-code');
        const huyenCode = refs.huyen.options[refs.huyen.selectedIndex]?.getAttribute('data-code');
        const wards = state.addressData.find((item) => String(item.code) === String(tinhCode))?.districts?.find((district) => String(district.code) === String(huyenCode))?.wards || [];
        refs.xa.innerHTML = '<option value="">Chọn phường / xã</option>' + wards.map((xa) => `<option value="${xa.name}">${xa.name}</option>`).join('');
        refs.xa.disabled = wards.length === 0;
        syncHidden();
        updateSummary();
    });
    refs.xa.addEventListener('change', () => { state.xa = refs.xa.value; syncHidden(); updateSummary(); });
    refs.soNha.addEventListener('input', () => { state.soNha = refs.soNha.value; updateSummary(); });
    refs.description.addEventListener('input', () => { state.description = refs.description.value; });
    refs.getLocation.addEventListener('click', () => {
        if (!navigator.geolocation) {
            showToast('Trình duyệt không hỗ trợ định vị.', 'error');
            return;
        }
        refs.locationStatus.textContent = 'Đang lấy tọa độ GPS...';
        refs.getLocation.disabled = true;
        navigator.geolocation.getCurrentPosition(async (position) => {
            state.lat = String(position.coords.latitude);
            state.lng = String(position.coords.longitude);
            syncHidden();
            updateTravelEstimate();
            refs.locationStatus.textContent = 'Đã lấy vị trí thành công.';
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${state.lat}&lon=${state.lng}&addressdetails=1`);
                const geoData = await response.json();
                const addr = geoData?.address;
                if (addr) {
                    const filledFullAddress = await autoFillAdministrativeAddress(geoData);
                    const street = `${addr.house_number || ''} ${addr.road || ''}`.trim();
                    if (street) {
                        state.soNha = street;
                        refs.soNha.value = street;
                    }
                    refs.locationStatus.textContent = filledFullAddress
                        ? 'Đã lấy vị trí thành công và tự chọn đầy đủ tỉnh, huyện, xã.'
                        : 'Đã lấy vị trí thành công. Hệ thống đã tự điền được một phần địa chỉ, vui lòng kiểm tra lại.';
                }
            } catch (error) {}
            refs.getLocation.disabled = false;
            updateSummary();
        }, () => {
            refs.locationStatus.textContent = 'Không lấy được vị trí. Vui lòng thử lại hoặc nhập địa chỉ thủ công.';
            refs.getLocation.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    });
    const syncFiles = () => {
        const images = new DataTransfer();
        state.images.forEach((file) => images.items.add(file));
        refs.imagesInput.files = images.files;
        const video = new DataTransfer();
        if (state.video) video.items.add(state.video);
        refs.videoInput.files = video.files;
        refs.preview.innerHTML = state.images.map((file, index) => `<div class="booking-preview-tile"><img src="${URL.createObjectURL(file)}" alt="Ảnh mô tả ${index + 1}"><button type="button" class="booking-preview-remove" data-remove-image="${index}">×</button></div>`).join('') + (state.video ? '<div class="booking-preview-tile"><div class="booking-preview-video">VIDEO</div><button type="button" class="booking-preview-remove" data-remove-video="1">×</button></div>' : '');
        refs.preview.querySelectorAll('[data-remove-image]').forEach((button) => button.addEventListener('click', () => { state.images.splice(Number(button.dataset.removeImage), 1); syncFiles(); }));
        refs.preview.querySelectorAll('[data-remove-video]').forEach((button) => button.addEventListener('click', () => { state.video = null; syncFiles(); }));
    };
    refs.uploadZone.addEventListener('click', () => refs.mediaPicker.click());
    refs.mediaPicker.addEventListener('change', async () => {
        for (const file of Array.from(refs.mediaPicker.files || [])) {
            if (file.type.startsWith('image/')) {
                if (state.images.length >= 5) { showToast('Bạn chỉ có thể tải tối đa 5 ảnh.', 'error'); continue; }
                state.images.push(file);
                continue;
            }
            if (file.type.startsWith('video/')) {
                if (state.video) { showToast('Chỉ cho phép một video cho mỗi lịch hẹn.', 'error'); continue; }
                const duration = await new Promise((resolve) => {
                    const video = document.createElement('video');
                    video.preload = 'metadata';
                    video.onloadedmetadata = () => { URL.revokeObjectURL(video.src); resolve(video.duration); };
                    video.src = URL.createObjectURL(file);
                });
                if (duration > 20) { showToast('Video không được vượt quá 20 giây.', 'error'); continue; }
                state.video = file;
            }
        }
        syncFiles();
        refs.mediaPicker.value = '';
    });
    ['dragenter', 'dragover'].forEach((eventName) => refs.uploadZone.addEventListener(eventName, (event) => { event.preventDefault(); refs.uploadZone.classList.add('is-dragover'); }));
    ['dragleave', 'drop'].forEach((eventName) => refs.uploadZone.addEventListener(eventName, (event) => { event.preventDefault(); refs.uploadZone.classList.remove('is-dragover'); }));
    refs.uploadZone.addEventListener('drop', (event) => {
        const transfer = new DataTransfer();
        Array.from(event.dataTransfer?.files || []).forEach((file) => transfer.items.add(file));
        refs.mediaPicker.files = transfer.files;
        refs.mediaPicker.dispatchEvent(new Event('change'));
    });
    renderDateCards();
    renderTimeSlots();
    updateHeader();
    updateAddressPanels();
    syncHidden();
    updateSummary();
    if (isStandalone) openModal();
});
