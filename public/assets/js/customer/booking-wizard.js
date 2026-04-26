import { callApi, getCurrentUser, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('bookingWizardModal');
    if (!root) return;

    const DEFAULT_STORE_ADDRESS = '2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa';
    const STORE_REFERENCE = { lat: 12.2618, lng: 109.1995, maxDistance: 20, label: 'cửa hàng' };
    const DEFAULT_STORE_LATITUDE = 12.2618;
    const DEFAULT_STORE_LONGITUDE = 109.1995;
    const DEFAULT_MAX_SERVICE_DISTANCE_KM = 8;
    const DEFAULT_FREE_DISTANCE_KM = 1;
    const TRAVEL_FEE_PER_KM = 5000;
    const DEFAULT_STORE_TRANSPORT_FEE = 0;
    let storeAddress = DEFAULT_STORE_ADDRESS;
    let storeTransportFee = DEFAULT_STORE_TRANSPORT_FEE;
    let travelFeeConfig = {
        free_distance_km: DEFAULT_FREE_DISTANCE_KM,
        default_per_km: TRAVEL_FEE_PER_KM,
        store_address: DEFAULT_STORE_ADDRESS,
        store_latitude: DEFAULT_STORE_LATITUDE,
        store_longitude: DEFAULT_STORE_LONGITUDE,
        max_service_distance_km: DEFAULT_MAX_SERVICE_DISTANCE_KM,
        store_transport_fee: DEFAULT_STORE_TRANSPORT_FEE,
        tiers: [],
    };
    const DEFAULT_BOOKING_TIME_SLOTS = ['08:00-10:00', '10:00-12:00', '12:00-14:00', '14:00-17:00'];
    let bookingTimeSlots = [...DEFAULT_BOOKING_TIME_SLOTS];
    const BOOKING_WINDOW_DAYS = 7;
    const STEP_META = {
        1: ['Bước 1 trên 5', 'ĐẶT LỊCH SỬA CHỮA', 'Chọn một hoặc nhiều dịch vụ phù hợp để bắt đầu lịch hẹn với Thợ Tốt NTU.'],
        2: ['Bước 2 trên 5', 'CHỌN HÌNH THỨC SỬA CHỮA', 'Xác định kỹ thuật viên sẽ đến tận nơi hay bạn mang thiết bị đến cửa hàng.'],
        3: ['Bước 3 trên 5', 'ĐỊA CHỈ SỬA CHỮA', 'Cung cấp vị trí chính xác để hệ thống điều phối kỹ thuật viên và ước tính phí di chuyển.'],
        4: ['Bước 4 trên 5', 'CHỌN NGÀY VÀ GIỜ', `Chọn thời điểm thuận tiện nhất trong ${BOOKING_WINDOW_DAYS} ngày tới mà hệ thống đang mở lịch.`],
        5: ['Bước 5 trên 5', 'MÔ TẢ & HÌNH ẢNH', 'Bổ sung mô tả, hình ảnh và video để thợ chuẩn bị dụng cụ sát với tình trạng thực tế.'],
    };
    const params = new URLSearchParams(window.location.search);
    const normalizePrefillServiceIds = (value) => {
        const values = Array.isArray(value) ? value : [value];

        return values
            .flatMap((item) => String(item || '').split(','))
            .map((item) => Number(String(item || '').trim()))
            .filter((item) => Number.isInteger(item) && item > 0)
            .filter((item, index, array) => array.indexOf(item) === index);
    };
    const isStandalone = root.dataset.standalone === '1';
    const standalonePrefill = {
        workerId: params.get('worker_id') || '',
        serviceName: params.get('service_name') || '',
        serviceIds: normalizePrefillServiceIds([
            params.get('service_ids'),
            params.get('dich_vu_id'),
            ...params.getAll('dich_vu_ids[]'),
            ...params.getAll('service_ids[]'),
        ]),
    };
    const state = {
        addressData: [], currentStep: 1, workerId: null, worker: null, prefillServiceName: '', prefillServiceIds: [], services: [], serviceIds: [],
        repairMode: null, tinh: '', huyen: '', xa: '', soNha: '', lat: '', lng: '', travelFee: 0, distanceKm: null,
        travelMessage: 'Sẽ tính sau khi bạn chọn vị trí.', date: '', timeSlot: '', description: '', images: [], video: null,
        transportRequested: false, isOutOfRange: false, isOpen: false, locationSource: '',
        symptomCatalog: [], symptomCatalogKey: '', symptomCatalogPromise: null, selectedSymptomId: null, selectedSymptomIds: {},
        serviceProblemInputs: {}, activeProblemServiceId: null, busySlotsByDate: {},
    };
    const $ = (id) => document.getElementById(id);
    const refs = {
        form: $('bookingWizardForm'), main: root.querySelector('.booking-wizard-main'), close: $('bookingWizardCloseButton'),
        title: $('bookingWizardTitle'), copy: $('bookingWizardCopy'), kicker: $('bookingWizardKicker'), badge: $('bookingWizardStepBadge'),
        progress: $('bookingWizardProgressFill'), prev: $('bookingWizardPrevButton'), next: $('bookingWizardNextButton'),
        servicesWrap: $('bookingWizardServices'), servicesEmpty: $('bookingWizardServicesEmpty'), workerBanner: $('bookingWizardWorkerBanner'),
        workerAvatar: $('bookingWizardWorkerAvatar'), workerName: $('bookingWizardWorkerName'), workerMeta: $('bookingWizardWorkerMeta'),
        atHome: $('bookingWizardAtHomePanel'), atStore: $('bookingWizardAtStorePanel'), transportWrap: $('bookingWizardTransportWrap'),
        storeAddress: $('bookingWizardStoreAddress'), storeTransportNote: $('bookingWizardStoreTransportNote'),
        transportToggle: $('bookingWizardTransportToggle'), getLocation: $('bookingWizardGetLocation'), locationStatus: $('bookingWizardLocationStatus'),
        tinh: $('bookingWizardTinh'), huyen: $('bookingWizardHuyen'), xa: $('bookingWizardXa'), soNha: $('bookingWizardSoNha'),
        dateCards: $('bookingWizardDateCards'), timeSlots: $('bookingWizardTimeSlots'), description: $('bookingWizardDescription'), problemFields: $('bookingWizardProblemFields'),
        problemSuggest: $('bookingWizardProblemSuggest'), problemSuggestCopy: $('bookingWizardProblemSuggestCopy'), problemSuggestList: $('bookingWizardProblemSuggestList'),
        problemPriceCard: $('bookingWizardProblemPriceCard'), problemPriceValue: $('bookingWizardProblemPriceValue'), problemPriceMeta: $('bookingWizardProblemPriceMeta'),
        uploadZone: $('bookingWizardUploadZone'), mediaPicker: $('bookingWizardMediaPicker'), imagesInput: $('bookingWizardImages'),
        videoInput: $('bookingWizardVideo'), preview: $('bookingWizardPreview'), success: $('bookingWizardSuccess'), successCode: $('bookingWizardSuccessCode'),
        hiddenWorkerId: $('bookingWizardWorkerId'), hiddenRepairMode: $('bookingWizardRepairMode'),
        hiddenLat: $('bookingWizardLat'), hiddenLng: $('bookingWizardLng'), hiddenDiaChi: $('bookingWizardDiaChi'), hiddenDate: $('bookingWizardDate'),
        hiddenTimeSlot: $('bookingWizardTimeSlot'), hiddenStoreTransport: $('bookingWizardStoreTransport'),
        summaryTitle: $('bookingSummaryTitle'), summarySheet: $('bookingSummarySheet'),
        sumServiceThumb: $('bookingSummaryServiceThumb'), sumWorkerThumb: $('bookingSummaryWorkerThumb'), sumModeMark: $('bookingSummaryModeMark'),
        sumServiceCard: $('bookingSummaryServiceCard'), sumServiceValue: $('bookingSummaryServiceValue'), sumServiceMeta: $('bookingSummaryServiceMeta'),
        sumWorkerCard: $('bookingSummaryWorkerCard'), sumWorkerValue: $('bookingSummaryWorkerValue'), sumWorkerMeta: $('bookingSummaryWorkerMeta'),
        sumModeCard: $('bookingSummaryModeCard'), sumTimeCard: $('bookingSummaryTimeCard'), sumAddressCard: $('bookingSummaryAddressCard'),
        sumModeValue: $('bookingSummaryModeValue'), sumModeMeta: $('bookingSummaryModeMeta'), sumTimeValue: $('bookingSummaryTimeValue'),
        sumTimeMeta: $('bookingSummaryTimeMeta'), sumAddressValue: $('bookingSummaryAddressValue'), sumAddressMeta: $('bookingSummaryAddressMeta'),
        sumTravelCard: $('bookingSummaryTravelCard'), sumReferencePriceCard: $('bookingSummaryReferencePriceCard'),
        sumTravelFee: $('bookingSummaryTravelFee'), sumTravelMeta: $('bookingSummaryTravelMeta'),
        sumReferencePrice: $('bookingSummaryReferencePrice'), sumReferenceMeta: $('bookingSummaryReferenceMeta'),
    };
    const legacyDescriptionField = refs.description?.closest('.booking-field') || null;
    if (legacyDescriptionField) {
        legacyDescriptionField.hidden = true;
    }
    const panels = Array.from(root.querySelectorAll('[data-step-panel]'));
    const stepButtons = Array.from(root.querySelectorAll('[data-step-target]'));
    const repairCards = Array.from(root.querySelectorAll('[data-repair-mode]'));
    const compactViewportState = { frame: 0 };
    const norm = (v) => String(v || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const money = (v) => `${Math.round(Number(v) || 0).toLocaleString('vi-VN')} ₫`;
    const selectedServices = () => state.services.filter((item) => state.serviceIds.includes(Number(item.id)));
    const heavyService = () => selectedServices().some((service) => ['may giat', 'tu lanh', 'tivi', 'may lanh', 'dieu hoa'].some((k) => norm(service.ten_dich_vu).includes(k)));
    const localDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const normalizeTimeSlotValue = (value) => String(value || '').replace(/\s+/g, '');
    const timeToMinutes = (value) => {
        const matched = String(value || '').trim().match(/^(\d{2}):(\d{2})$/);
        if (!matched) {
            return null;
        }

        const hour = Number(matched[1]);
        const minute = Number(matched[2]);
        if (!Number.isInteger(hour) || !Number.isInteger(minute) || hour < 0 || hour > 23 || minute < 0 || minute > 59) {
            return null;
        }

        return (hour * 60) + minute;
    };
    const parseBookingTimeSlot = (value) => {
        const normalized = normalizeTimeSlotValue(value);
        const matched = normalized.match(/^(\d{2}:\d{2})-(\d{2}:\d{2})$/);
        if (!matched) {
            return null;
        }

        const startMinutes = timeToMinutes(matched[1]);
        const endMinutes = timeToMinutes(matched[2]);
        if (startMinutes === null || endMinutes === null || endMinutes <= startMinutes) {
            return null;
        }

        return {
            value: normalized,
            startMinutes,
            endMinutes,
        };
    };
    const getBookingTimeSlots = () => (
        Array.isArray(bookingTimeSlots) && bookingTimeSlots.length
            ? bookingTimeSlots
            : DEFAULT_BOOKING_TIME_SLOTS
    );
    const getBookingTimeSlotDefinitions = () => {
        const parsedSlots = getBookingTimeSlots()
            .map((slot) => parseBookingTimeSlot(slot))
            .filter(Boolean)
            .sort((left, right) => left.startMinutes - right.startMinutes || left.endMinutes - right.endMinutes);

        return parsedSlots.length
            ? parsedSlots
            : DEFAULT_BOOKING_TIME_SLOTS
                .map((slot) => parseBookingTimeSlot(slot))
                .filter(Boolean);
    };
    const hasWorkerReferenceCoordinates = () => {
        const rawLat = state.worker?.vi_do;
        const rawLng = state.worker?.kinh_do;

        if (rawLat === null || rawLat === undefined || rawLng === null || rawLng === undefined) {
            return false;
        }

        if (String(rawLat).trim() === '' || String(rawLng).trim() === '') {
            return false;
        }

        const lat = Number(rawLat);
        const lng = Number(rawLng);

        return Number.isFinite(lat)
            && Number.isFinite(lng)
            && lat >= -90
            && lat <= 90
            && lng >= -180
            && lng <= 180
            && !(lat === 0 && lng === 0);
    };
    const getConfiguredMaxServiceDistanceKm = () => Math.max(
        0,
        Number(travelFeeConfig.max_service_distance_km ?? STORE_REFERENCE.maxDistance ?? DEFAULT_MAX_SERVICE_DISTANCE_KM)
    );
    const getHomeReferencePoint = () => {
        const configuredMaxDistance = getConfiguredMaxServiceDistanceKm();

        if (hasWorkerReferenceCoordinates()) {
            const workerMaxDistance = Number(state.worker?.ban_kinh_phuc_vu ?? configuredMaxDistance);

            return {
                lat: Number(state.worker.vi_do),
                lng: Number(state.worker.kinh_do),
                maxDistance: Math.min(
                    Number.isFinite(workerMaxDistance) ? workerMaxDistance : configuredMaxDistance,
                    configuredMaxDistance
                ),
                label: state.worker?.user?.name || 'thợ đã chọn',
            };
        }

        return {
            ...STORE_REFERENCE,
            maxDistance: configuredMaxDistance,
            label: 'cửa hàng',
        };
    };
    const formatServiceDistanceKm = (value) => Number(value || 0).toLocaleString('vi-VN', { maximumFractionDigits: 2 });
    const buildOutOfRangeValidationMessage = () => {
        const refPoint = getHomeReferencePoint();

        return `Địa chỉ của bạn vượt quá ${formatServiceDistanceKm(refPoint.maxDistance)} km. Vui lòng chọn địa chỉ gần hơn hoặc mang thiết bị đến cửa hàng.`;
    };
    const relativeDateLabel = (offset) => {
        if (offset === 0) return 'Hôm nay';
        if (offset === 1) return 'Ngày mai';
        return `${offset} ngày nữa`;
    };
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
    const findTravelFeeTier = (distanceKm) => (travelFeeConfig.tiers || []).find((tier, index, tiers) => (
        distanceKm >= Number(tier.from_km || 0)
        && (
            distanceKm < Number(tier.to_km || 0)
            || (index === tiers.length - 1 && distanceKm <= Number(tier.to_km || 0))
        )
    )) || null;
    const getTierTravelFee = (tier) => Number(tier?.travel_fee ?? tier?.fee ?? 0);
    const getTierTransportFee = (tier) => Number(tier?.transport_fee ?? 0);
    const getTierDisplayUpperBound = (tier, isLastTier = false) => {
        const fromKm = Number(tier?.from_km || 0);
        const toKm = Number(tier?.to_km || 0);

        if (isLastTier) {
            return toKm;
        }

        return Math.max(fromKm, Number((toKm - 0.01).toFixed(2)));
    };
    const formatTierRangeLabel = (tier) => {
        const tierIndex = (travelFeeConfig.tiers || []).indexOf(tier);
        const isLastTier = tierIndex === (travelFeeConfig.tiers || []).length - 1;

        return `${formatServiceDistanceKm(tier?.from_km || 0)} - ${formatServiceDistanceKm(getTierDisplayUpperBound(tier, isLastTier))} km`;
    };
    const normalizeTravelFeeTiers = (tiers) => (Array.isArray(tiers) ? tiers : []).map((tier) => ({
        ...tier,
        travel_fee: getTierTravelFee(tier),
        fee: getTierTravelFee(tier),
        transport_fee: getTierTransportFee(tier),
    }));
    const deriveStoreTransportFeeFromTiers = (tiers) => normalizeTravelFeeTiers(tiers)
        .map((tier) => tier.transport_fee)
        .find((fee) => Number.isFinite(fee) && fee > 0) ?? DEFAULT_STORE_TRANSPORT_FEE;
    const getFreeDistanceKm = () => Math.max(0, Number(travelFeeConfig.free_distance_km ?? DEFAULT_FREE_DISTANCE_KM));
    const resolveTravelFee = (distanceKm) => {
        if (distanceKm < getFreeDistanceKm()) {
            return 0;
        }

        return Math.round(distanceKm * Number(travelFeeConfig.default_per_km || TRAVEL_FEE_PER_KM));
    };
    const buildTravelFeeMessage = ({ distanceKm, roundedDistance, refLabel, isOutOfRange, maxDistance, manualSuffix = '' }) => {
        if (isOutOfRange) {
            return `Khoảng cách hiện tại vượt phạm vi phục vụ ${maxDistance} km của ${refLabel}.`;
        }

        const normalizedDistanceKm = Number.isFinite(distanceKm) ? distanceKm : roundedDistance;
        const freeDistanceKm = getFreeDistanceKm();
        if (freeDistanceKm > 0 && normalizedDistanceKm < freeDistanceKm) {
            return `Táº¡m tÃ­nh ${roundedDistance} km tá»« ${refLabel}${manualSuffix}: miá»…n phÃ­ di chuyá»ƒn vÃ¬ dÆ°á»›i ${freeDistanceKm.toLocaleString('vi-VN', { maximumFractionDigits: 2 })} km.`;
        }

        return `Tạm tính ${roundedDistance} km × ${Number(travelFeeConfig.default_per_km || TRAVEL_FEE_PER_KM).toLocaleString('vi-VN')} ₫/km từ ${refLabel}${manualSuffix}.`;
    };
    const buildStoreTransportMessage = () => (state.transportRequested
        ? `Bạn đã chọn thuê xe chở thiết bị hai chiều, phụ phí dự kiến ${money(storeTransportFee)}.`
        : 'Bạn tự mang thiết bị đến cửa hàng nên không phát sinh phí đưa đón.');
    const resolveTravelFeeLinear = (distanceKm) => {
        if (distanceKm < getFreeDistanceKm()) {
            return 0;
        }

        const tier = findTravelFeeTier(distanceKm);
        if (tier) {
            return getTierTravelFee(tier);
        }

        return Math.round(distanceKm * Number(travelFeeConfig.default_per_km || TRAVEL_FEE_PER_KM));
    };
    const buildTravelFeeMessageLinear = ({ distanceKm, roundedDistance, refLabel, isOutOfRange, maxDistance, manualSuffix = '' }) => {
        if (isOutOfRange) {
            return `Khoảng cách hiện tại vượt phạm vi phục vụ ${maxDistance} km của ${refLabel}.`;
        }

        const normalizedDistanceKm = Number.isFinite(distanceKm) ? distanceKm : roundedDistance;
        const freeDistanceKm = getFreeDistanceKm();
        if (freeDistanceKm > 0 && normalizedDistanceKm < freeDistanceKm) {
            return `Tạm tính ${roundedDistance} km từ ${refLabel}${manualSuffix}: miễn phí di chuyển vì dưới ${freeDistanceKm.toLocaleString('vi-VN', { maximumFractionDigits: 2 })} km.`;
        }

        return `Tạm tính ${roundedDistance} km × ${Number(travelFeeConfig.default_per_km || TRAVEL_FEE_PER_KM).toLocaleString('vi-VN')} ₫/km từ ${refLabel}${manualSuffix}.`;
    };
    const buildTravelTierMessage = ({ distanceKm, roundedDistance, refLabel, isOutOfRange, maxDistance, manualSuffix = '' }) => {
        if (isOutOfRange) {
            return `Khoảng cách hiện tại vượt phạm vi phục vụ ${maxDistance} km của ${refLabel}.`;
        }

        const normalizedDistanceKm = Number.isFinite(distanceKm) ? distanceKm : roundedDistance;
        const freeDistanceKm = getFreeDistanceKm();
        if (freeDistanceKm > 0 && normalizedDistanceKm < freeDistanceKm) {
            return `Tạm tính ${roundedDistance} km từ ${refLabel}${manualSuffix}: miễn phí di chuyển vì dưới ${freeDistanceKm.toLocaleString('vi-VN', { maximumFractionDigits: 2 })} km.`;
        }

        const tier = findTravelFeeTier(normalizedDistanceKm);
        if (tier) {
            return `Tạm tính ${roundedDistance} km từ ${refLabel}${manualSuffix}: áp dụng ${money(getTierTravelFee(tier))} cho khoảng ${formatTierRangeLabel(tier)}.`;
        }

        return `Tạm tính ${roundedDistance} km × ${Number(travelFeeConfig.default_per_km || TRAVEL_FEE_PER_KM).toLocaleString('vi-VN')} ₫/km từ ${refLabel}${manualSuffix}.`;
    };
    const applyStoreConfigToView = () => {
        if (refs.storeAddress) {
            refs.storeAddress.textContent = storeAddress;
        }
        if (refs.storeTransportNote) {
            refs.storeTransportNote.textContent = buildStoreTransportMessage();
        }
    };
    const loadTravelFeeConfig = async () => {
        try {
            const res = await callApi('/travel-fee-config');
            if (!res.ok) {
                return;
            }

            const config = res.data?.data?.config;
            if (config && typeof config === 'object') {
                const tiers = normalizeTravelFeeTiers(config.tiers);
                const derivedStoreTransportFee = deriveStoreTransportFeeFromTiers(tiers);
                const storeLatitude = Number(config.store_latitude ?? DEFAULT_STORE_LATITUDE);
                const storeLongitude = Number(config.store_longitude ?? DEFAULT_STORE_LONGITUDE);
                const maxServiceDistanceKm = Number(config.max_service_distance_km ?? DEFAULT_MAX_SERVICE_DISTANCE_KM);

                storeAddress = String(config.store_address || DEFAULT_STORE_ADDRESS).trim() || DEFAULT_STORE_ADDRESS;
                storeTransportFee = Number(config.store_transport_fee ?? derivedStoreTransportFee ?? DEFAULT_STORE_TRANSPORT_FEE);
                travelFeeConfig = {
                    free_distance_km: Number(config.free_distance_km ?? DEFAULT_FREE_DISTANCE_KM),
                    default_per_km: Number(config.default_per_km ?? TRAVEL_FEE_PER_KM),
                    store_address: storeAddress,
                    store_latitude: Number.isFinite(storeLatitude) ? storeLatitude : DEFAULT_STORE_LATITUDE,
                    store_longitude: Number.isFinite(storeLongitude) ? storeLongitude : DEFAULT_STORE_LONGITUDE,
                    max_service_distance_km: Number.isFinite(maxServiceDistanceKm) ? maxServiceDistanceKm : DEFAULT_MAX_SERVICE_DISTANCE_KM,
                    store_transport_fee: storeTransportFee,
                    tiers,
                };
                bookingTimeSlots = (Array.isArray(config.booking_time_slots) ? config.booking_time_slots : [])
                    .map((slot) => normalizeTimeSlotValue(slot))
                    .filter(Boolean);
                STORE_REFERENCE.lat = travelFeeConfig.store_latitude;
                STORE_REFERENCE.lng = travelFeeConfig.store_longitude;
                STORE_REFERENCE.maxDistance = travelFeeConfig.max_service_distance_km;
                applyStoreConfigToView();
                renderTimeSlots();
                if (state.repairMode === 'at_home' && state.lat && state.lng) {
                    updateTravelEstimate();
                } else {
                    syncHidden();
                    updateSummary();
                }
            }
        } catch (error) {
            console.warn('Không tải được cấu hình phí đi lại', error);
        }
    };
    const homeAddressLabel = () => [state.soNha.trim(), state.xa, state.huyen, state.tinh].filter(Boolean).join(', ');
    const hasCompleteHomeAddress = () => Boolean(state.tinh && state.huyen && state.xa && state.soNha.trim());
    let addressLookupTimer = null;
    let addressLookupRequestId = 0;
    let suppressAddressGeocode = false;
    let symptomSuggestTimer = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getServiceProblemText(serviceId) {
        return String(state.serviceProblemInputs?.[String(serviceId)] || '');
    }

    function setServiceProblemText(serviceId, value) {
        if (!serviceId) return;
        if (!state.serviceProblemInputs || typeof state.serviceProblemInputs !== 'object') {
            state.serviceProblemInputs = {};
        }

        state.serviceProblemInputs[String(serviceId)] = String(value || '');
    }

    function getActiveProblemService() {
        const activeServiceId = Number(state.activeProblemServiceId || 0);
        return selectedServices().find((service) => Number(service.id) === activeServiceId) || selectedServices()[0] || null;
    }

    function syncActiveProblemContext() {
        const activeService = getActiveProblemService();
        state.activeProblemServiceId = activeService ? Number(activeService.id) : null;
        state.description = activeService ? getServiceProblemText(activeService.id) : '';
        state.selectedSymptomId = activeService
            ? (Number(state.selectedSymptomIds?.[String(activeService.id)] || 0) || null)
            : null;

        if (refs.description) {
            refs.description.value = state.description;
        }
    }

    function syncProblemInputsWithSelectedServices() {
        const selectedIds = selectedServices().map((service) => String(service.id));
        const nextProblems = {};
        const nextSymptoms = {};

        selectedIds.forEach((serviceId) => {
            nextProblems[serviceId] = String(state.serviceProblemInputs?.[serviceId] || '');
            if (Number(state.selectedSymptomIds?.[serviceId] || 0) > 0) {
                nextSymptoms[serviceId] = Number(state.selectedSymptomIds[serviceId]);
            }
        });

        state.serviceProblemInputs = nextProblems;
        state.selectedSymptomIds = nextSymptoms;

        if (!selectedIds.includes(String(state.activeProblemServiceId || ''))) {
            state.activeProblemServiceId = selectedIds[0] ? Number(selectedIds[0]) : null;
        }

        syncActiveProblemContext();
    }

    function getCombinedProblemDescription() {
        const problems = selectedServices()
            .map((service) => ({
                serviceName: String(service.ten_dich_vu || 'Dịch vụ').trim(),
                description: getServiceProblemText(service.id).trim(),
            }))
            .filter((item) => item.description !== '');

        if (!problems.length) {
            return '';
        }

        if (problems.length === 1 && selectedServices().length === 1) {
            return problems[0].description;
        }

        return problems
            .map((item) => `${item.serviceName}: ${item.description}`)
            .join('\n');
    }

    function syncRenderedProblemFieldState() {
        if (!refs.problemFields) return;

        refs.problemFields.querySelectorAll('[data-problem-service-card]').forEach((card) => {
            const serviceId = Number(card.getAttribute('data-problem-service-card') || 0);
            const isActive = Number(state.activeProblemServiceId || 0) === serviceId;
            const badge = card.querySelector('[data-problem-active-badge]');

            card.classList.toggle('is-active', isActive);
            if (badge) {
                badge.classList.toggle('d-none', !isActive);
            }
        });
    }

    function getProblemAssistRefs(serviceId = state.activeProblemServiceId) {
        const targetServiceId = Number(serviceId || 0);
        if (!refs.problemFields || targetServiceId <= 0) {
            return null;
        }

        const card = refs.problemFields.querySelector(`[data-problem-service-card="${targetServiceId}"]`);
        if (!card) {
            return null;
        }

        return {
            card,
            suggest: card.querySelector('[data-problem-suggest]'),
            suggestCopy: card.querySelector('[data-problem-suggest-copy]'),
            suggestList: card.querySelector('[data-problem-suggest-list]'),
            priceCard: card.querySelector('[data-problem-price-card]'),
            priceValue: card.querySelector('[data-problem-price-value]'),
            priceMeta: card.querySelector('[data-problem-price-meta]'),
        };
    }

    function clearProblemAssistDisplays() {
        if (!refs.problemFields) {
            return;
        }

        refs.problemFields.querySelectorAll('[data-problem-suggest]').forEach((element) => {
            element.classList.add('d-none');
        });
        refs.problemFields.querySelectorAll('[data-problem-suggest-list]').forEach((element) => {
            element.innerHTML = '';
        });
        refs.problemFields.querySelectorAll('[data-problem-price-card]').forEach((element) => {
            element.classList.add('d-none');
        });
    }

    function renderProblemFields() {
        if (!refs.problemFields) return;

        const services = selectedServices();
        if (!services.length) {
            refs.problemFields.innerHTML = '';
            refs.problemFields.classList.add('d-none');
            syncActiveProblemContext();
            renderProblemSuggestions([]);
            renderProblemReferencePrice(null);
            queueViewportFit();
            return;
        }

        refs.problemFields.classList.remove('d-none');
        refs.problemFields.innerHTML = services.map((service, index) => {
            const serviceId = Number(service.id);
            const isActive = Number(state.activeProblemServiceId || 0) === serviceId;
            const serviceDescription = escapeHtml(getServiceProblemText(serviceId));
            const serviceName = escapeHtml(service.ten_dich_vu || `Dịch vụ ${index + 1}`);

            return `
                <div class="booking-problem-card ${isActive ? 'is-active' : ''}" data-problem-service-card="${serviceId}">
                    <div class="booking-problem-card-head">
                        <div>
                            <div class="booking-problem-card-kicker">Dịch vụ ${index + 1}</div>
                            <div class="booking-problem-card-title">${serviceName}</div>
                            <div class="booking-problem-card-copy">Mô tả riêng sự cố của dịch vụ này để thợ chuẩn bị đúng hướng xử lý.</div>
                        </div>
                        <div class="booking-problem-card-badge ${isActive ? '' : 'd-none'}" data-problem-active-badge>Đang nhập</div>
                    </div>
                    <label class="booking-field">
                        <span>Mô tả sự cố</span>
                        <textarea rows="5" data-problem-service-input="${serviceId}" placeholder="Ví dụ: thiết bị hoạt động yếu, phát ra tiếng ồn lạ hoặc không lên nguồn.">${serviceDescription}</textarea>
                    </label>
                    <div class="booking-problem-assist" data-problem-assist>
                        <div class="booking-problem-suggest d-none" data-problem-suggest>
                            <div class="booking-problem-suggest-head">
                                <div class="booking-problem-suggest-title">Triệu chứng gợi ý</div>
                                <div class="booking-problem-suggest-copy" data-problem-suggest-copy>Bấm vào gợi ý để điền nhanh mô tả cho dịch vụ này.</div>
                            </div>
                            <div class="booking-problem-chip-list" data-problem-suggest-list></div>
                        </div>
                        <div class="booking-problem-price d-none" data-problem-price-card>
                            <div class="booking-problem-price-label">Giá tham khảo</div>
                            <div class="booking-problem-price-value" data-problem-price-value>Liên hệ báo giá</div>
                            <div class="booking-problem-price-meta" data-problem-price-meta>Khoảng giá sẽ hiện khi bạn chọn một triệu chứng phù hợp.</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        refs.problemFields.querySelectorAll('[data-problem-service-input]').forEach((input) => {
            const serviceId = Number(input.getAttribute('data-problem-service-input') || 0);

            input.addEventListener('focus', () => {
                state.activeProblemServiceId = serviceId;
                syncActiveProblemContext();
                syncRenderedProblemFieldState();
                renderProblemReferencePrice(getSelectedSymptomSuggestion());
                refreshProblemAssist();
            });

            input.addEventListener('input', () => {
                state.activeProblemServiceId = serviceId;
                setServiceProblemText(serviceId, input.value);
                syncActiveProblemContext();
                syncRenderedProblemFieldState();

                const selected = getSelectedSymptomSuggestion();
                const queryText = getSymptomQueryText(input.value);
                const isExactSelected = selected && getSymptomQueryText(selected.ten_trieu_chung) === queryText;

                if (!isExactSelected) {
                    delete state.selectedSymptomIds[String(serviceId)];
                    state.selectedSymptomId = null;
                    renderProblemReferencePrice(null);
                }

                scheduleProblemAssist();
            });
        });

        queueViewportFit();
    }

    function getSymptomCatalogServiceKey() {
        const activeServiceId = Number(state.activeProblemServiceId || 0);
        return activeServiceId > 0 ? String(activeServiceId) : '';
    }

    function getSymptomQueryText(value = refs.description?.value || '') {
        return norm(String(value || '').replace(/\s+/g, ' ').trim());
    }

    function formatReferencePriceRange(min, max) {
        const normalizedMin = Number(min || 0);
        const normalizedMax = Number(max || 0);
        if (normalizedMin > 0 && normalizedMax > 0) {
            return normalizedMin === normalizedMax ? money(normalizedMin) : `${money(normalizedMin)} - ${money(normalizedMax)}`;
        }
        return 'Liên hệ báo giá';
    }

    function getSelectedSymptomSuggestion() {
        return (Array.isArray(state.symptomCatalog) ? state.symptomCatalog : [])
            .find((item) => Number(item?.id || 0) === Number(state.selectedSymptomId || 0)) || null;
    }

    function renderProblemReferencePrice(symptom) {
        const hasSymptom = Boolean(symptom);
        const priceMin = Number(symptom?.gia_tham_khao_tu || 0);
        const priceMax = Number(symptom?.gia_tham_khao_den || 0);
        const causeCount = Array.isArray(symptom?.nguyen_nhan_names) ? symptom.nguyen_nhan_names.length : 0;
        const resolutionCount = Number(symptom?.huong_xu_ly_count || 0);
        const causePreview = Array.isArray(symptom?.nguyen_nhan_names) ? symptom.nguyen_nhan_names.slice(0, 2).join(', ') : '';
        const hasAnyPrice = priceMin > 0 || priceMax > 0;
        const priceLabel = formatReferencePriceRange(priceMin, priceMax);
        const metaText = !hasSymptom
            ? 'Khoảng giá sẽ hiện khi bạn chọn một triệu chứng phù hợp.'
            : hasAnyPrice
                ? `Theo ${resolutionCount || 0} hướng xử lý${causeCount ? ` và ${causeCount} nguyên nhân liên quan` : ''}.${causePreview ? ` Thường gặp: ${causePreview}.` : ''}`
                : 'Triệu chứng này đã có trong danh mục nhưng chưa được khai báo giá tham khảo.';

        if (refs.problemFields) {
            refs.problemFields.querySelectorAll('[data-problem-price-card]').forEach((element) => {
                element.classList.add('d-none');
            });
        }
        const assistRefs = getProblemAssistRefs();
        if (assistRefs?.priceCard) assistRefs.priceCard.classList.toggle('d-none', !hasSymptom);
        if (assistRefs?.priceValue) assistRefs.priceValue.textContent = priceLabel;
        if (assistRefs?.priceMeta) assistRefs.priceMeta.textContent = metaText;
        if (refs.sumReferencePriceCard) refs.sumReferencePriceCard.classList.toggle('d-none', !hasSymptom);
        if (refs.sumReferencePrice) refs.sumReferencePrice.textContent = priceLabel;
        if (refs.sumReferenceMeta) refs.sumReferenceMeta.textContent = metaText;
    }

    function rankSymptomSuggestion(item, query) {
        const symptomText = getSymptomQueryText(item?.ten_trieu_chung || '');
        const queryText = getSymptomQueryText(query);
        if (!symptomText || !queryText) return -1;
        if (symptomText === queryText) return 400;
        if (symptomText.startsWith(queryText)) return 320;
        if (symptomText.includes(queryText)) return 250;

        const secondaryFields = [
            item?.dich_vu_name || '',
            ...(Array.isArray(item?.nguyen_nhan_names) ? item.nguyen_nhan_names : []),
            ...(Array.isArray(item?.huong_xu_ly_names) ? item.huong_xu_ly_names : []),
        ].map((value) => getSymptomQueryText(value)).filter(Boolean);

        return secondaryFields.some((field) => field.includes(queryText)) ? 140 : -1;
    }

    function findMatchingSymptomSuggestions(query) {
        const queryText = getSymptomQueryText(query);
        if (!queryText) return [];

        return (Array.isArray(state.symptomCatalog) ? state.symptomCatalog : [])
            .map((item) => ({ item, score: rankSymptomSuggestion(item, queryText) }))
            .filter((entry) => entry.score >= 0)
            .sort((entryA, entryB) => {
                if (entryB.score !== entryA.score) return entryB.score - entryA.score;
                return String(entryA.item?.ten_trieu_chung || '').localeCompare(String(entryB.item?.ten_trieu_chung || ''), 'vi');
            })
            .slice(0, 8)
            .map((entry) => entry.item);
    }

    function renderProblemSuggestions(items, options = {}) {
        const queryText = getSymptomQueryText(refs.description?.value);
        const loading = Boolean(options.loading);
        const activeService = getActiveProblemService();
        const assistRefs = getProblemAssistRefs(activeService?.id);
        if (!assistRefs?.suggest || !assistRefs?.suggestList || !assistRefs?.suggestCopy) return;

        if (refs.problemFields) {
            refs.problemFields.querySelectorAll('[data-problem-suggest]').forEach((element) => {
                element.classList.add('d-none');
            });
            refs.problemFields.querySelectorAll('[data-problem-suggest-list]').forEach((element) => {
                if (element !== assistRefs.suggestList) {
                    element.innerHTML = '';
                }
            });
        }

        assistRefs.suggest.classList.toggle('d-none', queryText === '');

        if (queryText === '') {
            assistRefs.suggestList.innerHTML = '';
            return;
        }

        if (loading) {
            assistRefs.suggestCopy.textContent = 'Đang đối chiếu mô tả với triệu chứng có sẵn...';
            assistRefs.suggestList.innerHTML = '<div class="booking-problem-chip-empty">Đang tìm gợi ý phù hợp...</div>';
            return;
        }

        assistRefs.suggestCopy.textContent = items.length
            ? `Gợi ý cho ${activeService?.ten_dich_vu || 'dịch vụ đang chọn'}. Bấm vào để điền nhanh mô tả và xem khoảng giá tham khảo.`
            : `Không tìm thấy triệu chứng khớp với ${activeService?.ten_dich_vu || 'dịch vụ đang chọn'}.`;

        assistRefs.suggestList.innerHTML = items.length
            ? items.map((item) => {
                const isActive = Number(item?.id || 0) === Number(state.selectedSymptomId || 0);
                const serviceMeta = state.serviceIds.length > 1 && item?.dich_vu_name
                    ? `<small>${escapeHtml(item.dich_vu_name)}</small>`
                    : '';
                return `<button type="button" class="booking-problem-chip ${isActive ? 'is-active' : ''}" data-problem-symptom-id="${Number(item?.id || 0)}">${escapeHtml(item?.ten_trieu_chung || 'Triệu chứng')}${serviceMeta}</button>`;
            }).join('')
            : '<div class="booking-problem-chip-empty">Thử mô tả ngắn gọn hơn hoặc chọn dịch vụ cụ thể để hệ thống gợi ý chính xác hơn.</div>';

        assistRefs.suggestList.querySelectorAll('[data-problem-symptom-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const symptomId = Number(button.getAttribute('data-problem-symptom-id') || 0);
                const symptom = (Array.isArray(state.symptomCatalog) ? state.symptomCatalog : [])
                    .find((item) => Number(item?.id || 0) === symptomId);
                if (!symptom) return;

                const currentService = getActiveProblemService();
                if (!currentService) return;

                state.activeProblemServiceId = Number(currentService.id);
                state.selectedSymptomId = symptomId;
                state.selectedSymptomIds[String(currentService.id)] = symptomId;
                setServiceProblemText(currentService.id, symptom.ten_trieu_chung || '');
                syncActiveProblemContext();
                renderProblemFields();

                const activeInput = refs.problemFields?.querySelector(`[data-problem-service-input="${Number(currentService.id)}"]`);
                if (activeInput) {
                    activeInput.focus();
                    const textLength = activeInput.value.length;
                    activeInput.setSelectionRange(textLength, textLength);
                }

                renderProblemSuggestions(findMatchingSymptomSuggestions(state.description));
                renderProblemReferencePrice(symptom);
                queueViewportFit();
            });
        });
    }

    function resetProblemAssistState() {
        state.symptomCatalog = [];
        state.symptomCatalogKey = '';
        state.symptomCatalogPromise = null;
        state.selectedSymptomId = null;
        if (symptomSuggestTimer) {
            window.clearTimeout(symptomSuggestTimer);
            symptomSuggestTimer = null;
        }
        clearProblemAssistDisplays();
        renderProblemSuggestions([]);
        renderProblemReferencePrice(null);
    }

    async function ensureSymptomCatalogLoaded() {
        const serviceKey = getSymptomCatalogServiceKey();
        if (!serviceKey) {
            state.symptomCatalog = [];
            state.symptomCatalogKey = '';
            state.symptomCatalogPromise = null;
            return [];
        }

        if (state.symptomCatalogKey === serviceKey && state.symptomCatalog.length > 0) {
            return state.symptomCatalog;
        }

        if (state.symptomCatalogKey === serviceKey && state.symptomCatalogPromise) {
            return state.symptomCatalogPromise;
        }

        state.symptomCatalogKey = serviceKey;
        state.symptomCatalogPromise = (async () => {
            const response = await callApi(`/huong-xu-ly?group_by_symptom=1&dich_vu_ids=${encodeURIComponent(serviceKey)}`, 'GET');
            if (!response.ok) {
                throw new Error(response.data?.message || 'Không tải được danh mục triệu chứng.');
            }

            const payload = Array.isArray(response.data) ? response.data : [];
            state.symptomCatalog = payload;
            return payload;
        })();

        try {
            return await state.symptomCatalogPromise;
        } finally {
            state.symptomCatalogPromise = null;
        }
    }

    async function refreshProblemAssist() {
        const query = refs.description?.value || '';
        const queryText = getSymptomQueryText(query);
        state.description = query;

        if (!queryText) {
            state.selectedSymptomId = null;
            renderProblemSuggestions([]);
            renderProblemReferencePrice(null);
            return;
        }

        if (!state.serviceIds.length) {
            state.selectedSymptomId = null;
            renderProblemSuggestions([]);
            renderProblemReferencePrice(null);
            return;
        }

        renderProblemSuggestions([], { loading: true });

        try {
            await ensureSymptomCatalogLoaded();
            const suggestions = findMatchingSymptomSuggestions(query);
            const selected = getSelectedSymptomSuggestion();
            const isSelectedStillExact = selected && getSymptomQueryText(selected.ten_trieu_chung) === queryText;
            if (!isSelectedStillExact) {
                state.selectedSymptomId = null;
            }
            const exactMatch = suggestions.find((item) => getSymptomQueryText(item?.ten_trieu_chung || '') === queryText) || null;
            const activeSymptom = isSelectedStillExact ? selected : exactMatch;
            if (activeSymptom) {
                state.selectedSymptomId = Number(activeSymptom.id || 0);
            }

            renderProblemSuggestions(suggestions);
            renderProblemReferencePrice(activeSymptom);
        } catch (error) {
            state.selectedSymptomId = null;
            const assistRefs = getProblemAssistRefs();
            if (assistRefs?.suggest) assistRefs.suggest.classList.remove('d-none');
            if (assistRefs?.suggestCopy) assistRefs.suggestCopy.textContent = error.message || 'Không tải được gợi ý triệu chứng.';
            if (assistRefs?.suggestList) assistRefs.suggestList.innerHTML = '<div class="booking-problem-chip-empty">Tạm thời không tải được gợi ý. Vui lòng thử lại sau.</div>';
            renderProblemReferencePrice(null);
        } finally {
            queueViewportFit();
        }
    }

    function scheduleProblemAssist() {
        if (symptomSuggestTimer) {
            window.clearTimeout(symptomSuggestTimer);
            symptomSuggestTimer = null;
        }

        symptomSuggestTimer = window.setTimeout(() => {
            refreshProblemAssist();
        }, 220);
    }

    const queueViewportFit = () => {
        if (!isStandalone || !refs.main) return;
        if (compactViewportState.frame) window.cancelAnimationFrame(compactViewportState.frame);
        compactViewportState.frame = window.requestAnimationFrame(() => {
            compactViewportState.frame = 0;
            root.classList.remove('is-fit-compact');
            if (!state.isOpen || window.innerWidth < 1200) return;
            if (refs.main.scrollHeight - refs.main.clientHeight > 8) root.classList.add('is-fit-compact');
        });
    };

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
            prefillServiceName: String(prefill.serviceName || ''), prefillServiceIds: normalizePrefillServiceIds(prefill.serviceIds), services: [], serviceIds: [], repairMode: null,
            tinh: '', huyen: '', xa: '', soNha: '', lat: '', lng: '', travelFee: 0, distanceKm: null, travelMessage: 'Sẽ tính sau khi bạn chọn vị trí.',
            date: '', timeSlot: '', description: '', images: [], video: null, transportRequested: false, isOutOfRange: false, locationSource: '',
            selectedSymptomIds: {}, serviceProblemInputs: {}, activeProblemServiceId: null, busySlotsByDate: {},
        });
        refs.form.reset();
        refs.success.classList.add('d-none');
        refs.workerBanner.classList.add('d-none');
        refs.sumWorkerCard.classList.add('d-none');
        refs.locationStatus.textContent = 'Vui lòng lấy vị trí hiện tại hoặc nhập địa chỉ thủ công.';
        refs.preview.innerHTML = '';
        if (refs.problemFields) {
            refs.problemFields.innerHTML = '';
            refs.problemFields.classList.add('d-none');
        }
        if (addressLookupTimer) {
            window.clearTimeout(addressLookupTimer);
            addressLookupTimer = null;
        }
        addressLookupRequestId += 1;
        suppressAddressGeocode = false;
        if (state.addressData.length) renderProvinceOptions();
    }

    function syncHidden() {
        refs.hiddenWorkerId.value = state.workerId ? String(state.workerId) : '';
        refs.hiddenRepairMode.value = state.repairMode || '';
        refs.hiddenLat.value = state.lat || '';
        refs.hiddenLng.value = state.lng || '';
        refs.hiddenDate.value = state.date || '';
        refs.hiddenTimeSlot.value = state.timeSlot || '';
        refs.hiddenStoreTransport.value = state.transportRequested ? '1' : '0';
        refs.hiddenDiaChi.value = state.repairMode === 'at_store' ? storeAddress : (state.xa && state.huyen && state.tinh ? `${state.xa}, ${state.huyen}, ${state.tinh}` : '');
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
        if (refs.transportToggle) refs.transportToggle.checked = state.transportRequested;
        applyStoreConfigToView();
        queueViewportFit();
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
        const hasTravel = Boolean(state.repairMode === 'at_store' || state.repairMode === 'at_home');
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
            ? storeAddress
            : `${state.soNha}, ${state.xa}, ${state.huyen}, ${state.tinh}`;
        refs.sumAddressMeta.textContent = state.repairMode === 'at_store'
            ? 'Địa chỉ tiếp nhận thiết bị.'
            : (state.lat && state.lng ? 'Địa chỉ đã gắn GPS.' : 'Địa chỉ nhập thủ công.');

        refs.sumTravelFee.textContent = money(state.travelFee);
        refs.sumTravelMeta.textContent = state.travelMessage;
    }

    function updateTravelEstimate() {
        if (state.repairMode !== 'at_home') {
            state.travelFee = state.transportRequested ? storeTransportFee : 0;
            state.distanceKm = 0;
            state.isOutOfRange = false;
            state.travelMessage = buildStoreTransportMessage();
            applyStoreConfigToView();
            syncHidden();
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
        const refPoint = hasWorkerReferenceCoordinates()
            ? { lat: Number(state.worker.vi_do), lng: Number(state.worker.kinh_do), maxDistance: Number(state.worker?.ban_kinh_phuc_vu ?? 10), label: state.worker?.user?.name || 'thợ đã chọn' }
            : STORE_REFERENCE;
        const distanceKm = distKm(refPoint.lat, refPoint.lng, lat, lng);
        const rounded = Number(distanceKm.toFixed(1));
        state.travelFee = resolveTravelFeeLinear(distanceKm);
        state.distanceKm = rounded;
        state.isOutOfRange = rounded > Number(refPoint.maxDistance || STORE_REFERENCE.maxDistance);
        state.travelMessage = buildTravelTierMessage({
            distanceKm,
            roundedDistance: rounded,
            refLabel: refPoint.label,
            isOutOfRange: state.isOutOfRange,
            maxDistance: refPoint.maxDistance,
        });
        updateSummary();
    }

    function resetTravelEstimate(message, locationStatus = '') {
        state.lat = '';
        state.lng = '';
        state.locationSource = '';
        state.travelFee = 0;
        state.distanceKm = null;
        state.isOutOfRange = false;
        state.travelMessage = message;
        syncHidden();
        if (locationStatus) refs.locationStatus.textContent = locationStatus;
        updateSummary();
    }

    async function geocodeManualAddress() {
        if (state.repairMode !== 'at_home') return;

        if (!hasCompleteHomeAddress()) {
            resetTravelEstimate(
                'Sáº½ tÃ­nh sau khi báº¡n chá»n vá»‹ trÃ­ hoáº·c nháº­p Ä‘á»§ Ä‘á»‹a chá»‰.',
                'Vui lÃ²ng láº¥y vá»‹ trÃ­ hiá»‡n táº¡i hoáº·c nháº­p Ä‘á»§ Ä‘á»‹a chá»‰ Ä‘á»ƒ há»‡ thá»‘ng tÃ­nh phÃ­ di chuyá»ƒn.'
            );
            return;
        }

        const lookupId = ++addressLookupRequestId;
        state.lat = '';
        state.lng = '';
        state.locationSource = '';
        state.travelFee = 0;
        state.distanceKm = null;
        state.isOutOfRange = false;
        state.travelMessage = 'Äang cáº­p nháº­t phÃ­ Ä‘i láº¡i theo Ä‘á»‹a chá»‰ báº¡n chá»n...';
        syncHidden();
        updateSummary();
        refs.locationStatus.textContent = 'Äang xÃ¡c Ä‘á»‹nh tá»a Ä‘á»™ theo Ä‘á»‹a chá»‰ báº¡n chá»n...';

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=vn&q=${encodeURIComponent(homeAddressLabel())}`);
            const data = await response.json();

            if (lookupId !== addressLookupRequestId) return;

            const location = Array.isArray(data) ? data[0] : null;
            if (!location?.lat || !location?.lon) {
                resetTravelEstimate(
                    'ChÆ°a thá»ƒ tÃ­nh phÃ­ Ä‘i láº¡i vÃ¬ khÃ´ng tÃ¬m tháº¥y tá»a Ä‘á»™ phÃ¹ há»£p cho Ä‘á»‹a chá»‰ nÃ y.',
                    'KhÃ´ng tÃ¬m tháº¥y tá»a Ä‘á»™ phÃ¹ há»£p cho Ä‘á»‹a chá»‰ báº¡n nháº­p. Vui lÃ²ng kiá»ƒm tra láº¡i hoáº·c dÃ¹ng GPS.'
                );
                return;
            }

            state.lat = String(location.lat);
            state.lng = String(location.lon);
            state.locationSource = 'manual';
            syncHidden();
            updateTravelEstimate();
            refs.locationStatus.textContent = 'ÄÃ£ cáº­p nháº­t pháº¡m vi vÃ  phÃ­ Ä‘i láº¡i theo Ä‘á»‹a chá»‰ báº¡n nháº­p.';
            if (!state.isOutOfRange) {
                state.travelMessage = `${state.travelMessage.slice(0, -1)} theo Ä‘á»‹a chá»‰ báº¡n nháº­p.`;
                updateSummary();
            }
        } catch (error) {
            if (lookupId !== addressLookupRequestId) return;

            resetTravelEstimate(
                'ChÆ°a thá»ƒ tÃ­nh phÃ­ Ä‘i láº¡i vÃ¬ khÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c tá»a Ä‘á»™ Ä‘á»‹a chá»‰.',
                'ChÆ°a thá»ƒ xÃ¡c Ä‘á»‹nh tá»a Ä‘á»™ tá»« Ä‘á»‹a chá»‰ nÃ y. Vui lÃ²ng thá»­ láº¡i hoáº·c dÃ¹ng GPS.'
            );
        }
    }

    function queueAddressRecalculation() {
        if (suppressAddressGeocode || state.repairMode !== 'at_home') return;

        if (addressLookupTimer) {
            window.clearTimeout(addressLookupTimer);
            addressLookupTimer = null;
        }

        addressLookupRequestId += 1;

        if (!hasCompleteHomeAddress()) {
            resetTravelEstimate(
                'Sáº½ tÃ­nh sau khi báº¡n chá»n vá»‹ trÃ­ hoáº·c nháº­p Ä‘á»§ Ä‘á»‹a chá»‰.',
                'Vui lÃ²ng láº¥y vá»‹ trÃ­ hiá»‡n táº¡i hoáº·c nháº­p Ä‘á»§ Ä‘á»‹a chá»‰ Ä‘á»ƒ há»‡ thá»‘ng tÃ­nh phÃ­ di chuyá»ƒn.'
            );
            return;
        }

        addressLookupTimer = window.setTimeout(() => {
            geocodeManualAddress();
        }, 500);
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
        queueViewportFit();
    }

    function clearDisabledTimeSlot() {
        if (availableTimeSlots().find((slot) => slot.value === state.timeSlot && slot.disabled)) state.timeSlot = '';
    }

    function renderDateCards() {
        refs.dateCards.innerHTML = Array.from({ length: BOOKING_WINDOW_DAYS }, (_, offset) => {
            const date = new Date();
            date.setHours(0, 0, 0, 0);
            date.setDate(date.getDate() + offset);
            const value = localDate(date);
            return `<button type="button" class="booking-date-card ${value === state.date ? 'is-selected' : ''}" data-date-value="${value}"><span class="booking-date-label">${relativeDateLabel(offset)}</span><span class="booking-date-weekday">${new Intl.DateTimeFormat('vi-VN', { weekday: 'long' }).format(date)}</span><span class="booking-date-day">${String(date.getDate()).padStart(2, '0')}</span><span class="booking-date-month">Tháng ${date.getMonth() + 1}</span></button>`;
        }).join('');
        refs.dateCards.querySelectorAll('[data-date-value]').forEach((button) => button.addEventListener('click', () => {
            state.date = button.dataset.dateValue;
            clearDisabledTimeSlot();
            renderDateCards();
            renderTimeSlots();
            syncHidden();
            updateSummary();
        }));
        queueViewportFit();
    }

    async function fetchWorkerBusySlots() {
        if (!state.workerId) {
            state.busySlotsByDate = {};
            return;
        }

        const dateFrom = localDate(new Date());
        const dateToDate = new Date();
        dateToDate.setHours(0, 0, 0, 0);
        dateToDate.setDate(dateToDate.getDate() + BOOKING_WINDOW_DAYS - 1);
        const dateTo = localDate(dateToDate);
        const result = await callApi(`/ho-so-tho/${state.workerId}/busy-slots?date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`, 'GET');

        if (!result.ok) {
            throw new Error(result.data?.message || 'Không tải được lịch bận của thợ.');
        }

        state.busySlotsByDate = Object.fromEntries(Object.entries(result.data?.busy_slots || {}).map(([date, slots]) => [
            date,
            Array.isArray(slots) ? slots.map((slot) => normalizeTimeSlotValue(slot)).filter(Boolean) : [],
        ]));
    }

    function getSlotDisableReason(slot, index, targetIndex) {
        const busySlots = Array.isArray(state.busySlotsByDate?.[state.date]) ? state.busySlotsByDate[state.date] : [];

        if (busySlots.includes(slot)) {
            return 'Thợ đã có lịch vào thời điểm này';
        }

        if (index < targetIndex) {
            return 'Khung giờ này đã qua';
        }

        return '';
    }

    function availableTimeSlots() {
        const slots = getBookingTimeSlotDefinitions().map((slot) => ({ value: slot.value, disabled: false, reason: '' }));
        if (!state.date) return slots;
        const now = new Date();
        const mins = now.getHours() * 60 + now.getMinutes();
        let target = slots.reduce((count, slot) => {
            const parsedSlot = parseBookingTimeSlot(slot.value);
            return parsedSlot && parsedSlot.startMinutes <= mins ? count + 1 : count;
        }, 0);
        if (state.repairMode === 'at_home') target += 1;

        return slots.map((slot, index) => {
            const reason = getSlotDisableReason(slot.value, index, state.date > localDate(now) ? 0 : target);
            return {
                ...slot,
                disabled: reason !== '',
                reason,
            };
        });
    }

    function renderTimeSlots() {
        refs.timeSlots.innerHTML = availableTimeSlots().map((slot) => `
            <button
                type="button"
                class="booking-time-slot ${slot.value === state.timeSlot ? 'is-selected' : ''} ${slot.disabled ? 'is-disabled' : ''} ${slot.reason ? 'has-reason' : ''}"
                data-time-slot="${slot.value}"
                data-disabled="${slot.disabled ? '1' : '0'}"
                data-disabled-reason="${slot.reason || ''}"
                aria-disabled="${slot.disabled ? 'true' : 'false'}"
                title="${slot.reason || ''}"
            >
                <span class="booking-time-slot-label">${slot.value.replace('-', ' - ')}</span>
                ${slot.reason ? `<span class="booking-time-slot-hint">${slot.reason}</span>` : ''}
            </button>
        `).join('');
        refs.timeSlots.querySelectorAll('[data-time-slot]').forEach((button) => button.addEventListener('click', () => {
            if (button.dataset.disabled === '1') {
                showToast(button.dataset.disabledReason || 'Khung giờ này không còn khả dụng.', 'error');
                return;
            }
            state.timeSlot = button.dataset.timeSlot;
            renderTimeSlots();
            syncHidden();
            updateSummary();
        }));
        queueViewportFit();
    }

    function renderServices() {
        refs.servicesEmpty.classList.toggle('d-none', state.services.length > 0);
        refs.servicesWrap.classList.toggle('d-none', state.services.length === 0);
        if (!state.services.length) {
            queueViewportFit();
            return;
        }
        refs.servicesWrap.innerHTML = state.services.map((service) => {
            const serviceId = Number(service.id);
            return `<button type="button" class="booking-service-card ${state.serviceIds.includes(serviceId) ? 'is-selected' : ''}" data-service-id="${serviceId}"><div class="booking-service-card-check">✓</div><div class="booking-service-card-media"><img src="${service.hinh_anh || '/assets/images/logontu.png'}" alt="${service.ten_dich_vu}" onerror="this.src='/assets/images/logontu.png'"></div><div class="booking-service-card-body"><div class="booking-service-card-title">${service.ten_dich_vu}</div><p class="booking-service-card-copy">${service.mo_ta || 'Dịch vụ sửa chữa chuyên sâu, kỹ thuật viên sẽ kiểm tra và xử lý theo tình trạng thực tế.'}</p></div></button>`;
        }).join('');
        refs.servicesWrap.querySelectorAll('[data-service-id]').forEach((button) => button.addEventListener('click', () => {
            const serviceId = Number(button.dataset.serviceId);
            state.serviceIds = state.serviceIds.includes(serviceId) ? state.serviceIds.filter((id) => id !== serviceId) : [...state.serviceIds, serviceId];
            state.transportRequested = false;
            state.symptomCatalog = [];
            state.symptomCatalogKey = '';
            state.symptomCatalogPromise = null;
            state.selectedSymptomId = null;
            syncProblemInputsWithSelectedServices();
            refs.transportToggle.checked = false;
            renderProblemReferencePrice(null);
            renderServices();
            renderProblemFields();
            syncHidden();
            updateAddressPanels();
            updateTravelEstimate();
            updateSummary();
            scheduleProblemAssist();
        }));
        queueViewportFit();
    }

    async function autoFillAdministrativeAddress(geoData) {
        const addr = geoData?.address;
        if (!addr) return false;
        suppressAddressGeocode = true;

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
        if (!matchedProvince) {
            suppressAddressGeocode = false;
            return false;
        }

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

        suppressAddressGeocode = false;

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
            if (state.prefillServiceIds.length) {
                state.serviceIds = state.services
                    .map((service) => Number(service.id))
                    .filter((serviceId) => state.prefillServiceIds.includes(serviceId));
            }
            if (!state.serviceIds.length && state.prefillServiceName) {
                const target = norm(state.prefillServiceName);
                const matched = state.services.find((service) => {
                    const name = norm(service.ten_dich_vu);
                    return name.includes(target) || target.includes(name);
                });
                if (matched) state.serviceIds = [Number(matched.id)];
            }
            if (!state.serviceIds.length && state.services.length === 1) state.serviceIds = [Number(state.services[0].id)];
            syncProblemInputsWithSelectedServices();
            if (state.workerId) {
                try {
                    await fetchWorkerBusySlots();
                } catch (busySlotsError) {
                    state.busySlotsByDate = {};
                    showToast(busySlotsError.message || 'Không tải được lịch bận của thợ.', 'error');
                }
                clearDisabledTimeSlot();
                renderTimeSlots();
            }
            renderServices();
            renderProblemFields();
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
        if (step === 4 && availableTimeSlots().find((slot) => slot.value === state.timeSlot && slot.disabled)) {
            return { valid: false, message: 'Khung giờ đã chọn không còn khả dụng, vui lòng chọn lại.' };
        }
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
        formData.set('loai_dat_lich', state.repairMode);
        formData.set('ngay_hen', state.date);
        formData.set('khung_gio_hen', normalizeTimeSlotValue(state.timeSlot));
        formData.set('mo_ta_van_de', getCombinedProblemDescription());
        formData.set('thue_xe_cho', state.transportRequested ? '1' : '0');
        if (state.repairMode === 'at_home') formData.set('dia_chi', `${state.soNha}, ${refs.hiddenDiaChi.value}`);
        else {
            formData.set('dia_chi', storeAddress);
            formData.set('vi_do', '');
            formData.set('kinh_do', '');
        }
        if (!state.workerId) formData.delete('tho_id');
        try {
            const result = await callApi('/don-dat-lich', 'POST', formData);
            if (!result.ok) {
                const errorData = result.data;
                if ((result.status === 409 || result.status === 422) && state.workerId) {
                    try {
                        await fetchWorkerBusySlots();
                        clearDisabledTimeSlot();
                        renderTimeSlots();
                        updateSummary();
                    } catch (busySlotsError) {
                        state.busySlotsByDate = {};
                    }
                }
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
        resetState({
            workerId: options.workerId ?? (isStandalone ? standalonePrefill.workerId : ''),
            serviceName: options.serviceName ?? (isStandalone ? standalonePrefill.serviceName : ''),
            serviceIds: options.serviceIds ?? (isStandalone ? standalonePrefill.serviceIds : []),
        });
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
        queueViewportFit();
        await loadAddressData();
        await loadWorkerAndServices();
        queueViewportFit();
    }

    function closeModal() {
        if (!state.isOpen) return;
        if (isStandalone) {
            window.location.href = '/customer/home';
            return;
        }
        refs.success.classList.add('d-none');
        root.classList.remove('is-open');
        root.classList.remove('is-fit-compact');
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
            if (addressLookupTimer) {
                window.clearTimeout(addressLookupTimer);
                addressLookupTimer = null;
            }
            addressLookupRequestId += 1;
            state.lat = '';
            state.lng = '';
            state.locationSource = '';
            refs.locationStatus.textContent = 'Không cần lấy vị trí khi mang thiết bị đến cửa hàng.';
        } else if (!hasCompleteHomeAddress()) {
            refs.locationStatus.textContent = 'Vui lÃ²ng láº¥y vá»‹ trÃ­ hiá»‡n táº¡i hoáº·c nháº­p Ä‘á»§ Ä‘á»‹a chá»‰ Ä‘á»ƒ há»‡ thá»‘ng tÃ­nh phÃ­ di chuyá»ƒn.';
        } else {
            refs.locationStatus.textContent = 'Há»‡ thá»‘ng sáº½ tá»± cáº­p nháº­t phÃ­ di chuyá»ƒn khi báº¡n thay Ä‘á»•i Ä‘á»‹a chá»‰.';
        }
        clearDisabledTimeSlot();
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
    refs.transportToggle.addEventListener('change', () => { state.transportRequested = refs.transportToggle.checked; updateTravelEstimate(); });
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
        queueAddressRecalculation();
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
        queueAddressRecalculation();
    });
    refs.xa.addEventListener('change', () => { state.xa = refs.xa.value; syncHidden(); updateSummary(); queueAddressRecalculation(); });
    refs.soNha.addEventListener('input', () => { state.soNha = refs.soNha.value; syncHidden(); updateSummary(); queueAddressRecalculation(); });
    refs.description.addEventListener('input', () => {
        const activeService = getActiveProblemService();
        state.description = refs.description.value;
        if (activeService) {
            setServiceProblemText(activeService.id, refs.description.value);
            const activeInput = refs.problemFields?.querySelector(`[data-problem-service-input="${Number(activeService.id)}"]`);
            if (activeInput && activeInput.value !== refs.description.value) {
                activeInput.value = refs.description.value;
            }
        }
        const selected = getSelectedSymptomSuggestion();
        const queryText = getSymptomQueryText(state.description);
        const isExactSelected = selected && getSymptomQueryText(selected.ten_trieu_chung) === queryText;

        if (!isExactSelected) {
            state.selectedSymptomId = null;
            if (activeService) {
                delete state.selectedSymptomIds[String(activeService.id)];
            }
            renderProblemReferencePrice(null);
        }

        syncRenderedProblemFieldState();
        scheduleProblemAssist();
    });
    refs.getLocation.addEventListener('click', () => {
        if (!navigator.geolocation) {
            showToast('Trình duyệt không hỗ trợ định vị.', 'error');
            return;
        }
        refs.locationStatus.textContent = 'Đang lấy tọa độ GPS...';
        if (addressLookupTimer) {
            window.clearTimeout(addressLookupTimer);
            addressLookupTimer = null;
        }
        addressLookupRequestId += 1;
        refs.getLocation.disabled = true;
        navigator.geolocation.getCurrentPosition(async (position) => {
            state.lat = String(position.coords.latitude);
            state.lng = String(position.coords.longitude);
            state.locationSource = 'gps';
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
        queueViewportFit();
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

    STORE_REFERENCE.maxDistance = DEFAULT_MAX_SERVICE_DISTANCE_KM;

    function resetState(prefill = {}) {
        const addressData = state.addressData;
        Object.assign(state, {
            addressData,
            currentStep: 1,
            workerId: prefill.workerId ? Number(prefill.workerId) : null,
            worker: null,
            prefillServiceName: String(prefill.serviceName || ''),
            prefillServiceIds: normalizePrefillServiceIds(prefill.serviceIds),
            services: [],
            serviceIds: [],
            repairMode: null,
            tinh: '',
            huyen: '',
            xa: '',
            soNha: '',
            lat: '',
            lng: '',
            travelFee: 0,
            distanceKm: null,
            travelMessage: 'Sẽ tính sau khi bạn chọn vị trí hoặc nhập đủ địa chỉ.',
            date: '',
            timeSlot: '',
            description: '',
            images: [],
            video: null,
            transportRequested: false,
            isOutOfRange: false,
            isOpen: false,
            locationSource: '',
            symptomCatalog: [],
            symptomCatalogKey: '',
            symptomCatalogPromise: null,
            selectedSymptomId: null,
            selectedSymptomIds: {},
            serviceProblemInputs: {},
            activeProblemServiceId: null,
            busySlotsByDate: {},
        });
        refs.form.reset();
        refs.success.classList.add('d-none');
        refs.workerBanner.classList.add('d-none');
        refs.sumWorkerCard.classList.add('d-none');
        refs.locationStatus.textContent = 'Vui lòng lấy vị trí hiện tại hoặc nhập đủ địa chỉ để hệ thống tính phí di chuyển.';
        refs.preview.innerHTML = '';
        if (refs.problemFields) {
            refs.problemFields.innerHTML = '';
            refs.problemFields.classList.add('d-none');
        }
        resetProblemAssistState();
        if (addressLookupTimer) {
            window.clearTimeout(addressLookupTimer);
            addressLookupTimer = null;
        }
        addressLookupRequestId += 1;
        suppressAddressGeocode = false;
        if (state.addressData.length) renderProvinceOptions();
    }

    function updateTravelEstimate() {
        if (state.repairMode !== 'at_home') {
            state.travelFee = state.transportRequested ? storeTransportFee : 0;
            state.distanceKm = 0;
            state.isOutOfRange = false;
            state.locationSource = '';
            state.travelMessage = buildStoreTransportMessage();
            applyStoreConfigToView();
            syncHidden();
            updateSummary();
            return;
        }

        const lat = Number(state.lat);
        const lng = Number(state.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat === 0 || lng === 0) {
            state.travelFee = 0;
            state.distanceKm = null;
            state.isOutOfRange = false;
            state.travelMessage = 'Sẽ tính sau khi bạn chọn vị trí hoặc nhập đủ địa chỉ.';
            syncHidden();
            updateSummary();
            return;
        }

        const refPoint = getHomeReferencePoint();

        const distanceKm = distKm(refPoint.lat, refPoint.lng, lat, lng);
        const rounded = Number(distanceKm.toFixed(1));
        state.travelFee = resolveTravelFeeLinear(distanceKm);
        state.distanceKm = rounded;
        state.isOutOfRange = rounded > Number(refPoint.maxDistance || getConfiguredMaxServiceDistanceKm());
        state.travelMessage = state.isOutOfRange
            ? `Địa chỉ đang vượt quá phạm vi phục vụ ${refPoint.maxDistance} km từ ${refPoint.label}.`
            : buildTravelTierMessage({
                distanceKm,
                roundedDistance: rounded,
                refLabel: refPoint.label,
                isOutOfRange: false,
                maxDistance: refPoint.maxDistance,
                manualSuffix: state.locationSource === 'manual' ? ' theo dia chi ban nhap' : '',
            });
        syncHidden();
        updateSummary();
    }

    function resetTravelEstimate(message, locationStatus = '') {
        state.lat = '';
        state.lng = '';
        state.locationSource = '';
        state.travelFee = 0;
        state.distanceKm = null;
        state.isOutOfRange = false;
        state.travelMessage = message;
        syncHidden();
        if (locationStatus) refs.locationStatus.textContent = locationStatus;
        updateSummary();
    }

    async function geocodeManualAddress() {
        if (state.repairMode !== 'at_home') return;

        if (!hasCompleteHomeAddress()) {
            resetTravelEstimate(
                'Sẽ tính sau khi bạn chọn vị trí hoặc nhập đủ địa chỉ.',
                'Vui lòng lấy vị trí hiện tại hoặc nhập đủ địa chỉ để hệ thống tính phí di chuyển.'
            );
            return;
        }

        const lookupId = ++addressLookupRequestId;
        state.lat = '';
        state.lng = '';
        state.locationSource = '';
        state.travelFee = 0;
        state.distanceKm = null;
        state.isOutOfRange = false;
        state.travelMessage = 'Đang cập nhật phí đi lại theo địa chỉ bạn chọn...';
        syncHidden();
        updateSummary();
        refs.locationStatus.textContent = 'Đang xác định tọa độ theo địa chỉ bạn chọn...';

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=vn&q=${encodeURIComponent(homeAddressLabel())}`);
            const data = await response.json();

            if (lookupId !== addressLookupRequestId) return;

            const location = Array.isArray(data) ? data[0] : null;
            if (!location?.lat || !location?.lon) {
                resetTravelEstimate(
                    'Chưa thể tính phí đi lại vì không tìm thấy tọa độ phù hợp cho địa chỉ này.',
                    'Không tìm thấy tọa độ phù hợp cho địa chỉ bạn nhập. Vui lòng kiểm tra lại hoặc dùng GPS.'
                );
                return;
            }

            state.lat = String(location.lat);
            state.lng = String(location.lon);
            state.locationSource = 'manual';
            syncHidden();
            updateTravelEstimate();
            refs.locationStatus.textContent = state.isOutOfRange
                ? `Địa chỉ đang vượt quá ${formatServiceDistanceKm(getHomeReferencePoint().maxDistance)} km nên hệ thống không cho phép tiếp tục.`
                : 'Đã cập nhật phí đi lại theo địa chỉ bạn nhập.';
        } catch (error) {
            if (lookupId !== addressLookupRequestId) return;

            resetTravelEstimate(
                'Chưa thể tính phí đi lại vì không xác định được tọa độ địa chỉ.',
                'Chưa thể xác định tọa độ từ địa chỉ này. Vui lòng thử lại hoặc dùng GPS.'
            );
        }
    }

    function queueAddressRecalculation() {
        if (suppressAddressGeocode || state.repairMode !== 'at_home') return;

        if (addressLookupTimer) {
            window.clearTimeout(addressLookupTimer);
            addressLookupTimer = null;
        }

        addressLookupRequestId += 1;

        if (!hasCompleteHomeAddress()) {
            resetTravelEstimate(
                'Sẽ tính sau khi bạn chọn vị trí hoặc nhập đủ địa chỉ.',
                'Vui lòng lấy vị trí hiện tại hoặc nhập đủ địa chỉ để hệ thống tính phí di chuyển.'
            );
            return;
        }

        addressLookupTimer = window.setTimeout(() => {
            geocodeManualAddress();
        }, 500);
    }

    function autoFillAdministrativeAddress(geoData) {
        const addr = geoData?.address;
        if (!addr) return false;

        suppressAddressGeocode = true;

        const uniqueCandidates = (items) => [...new Set(items.filter(Boolean).map((item) => String(item).trim()).filter(Boolean))];
        const normalizeAdminText = (value) => norm(value)
            .replace(/[.,()/-]/g, ' ')
            .replace(/\b(tp|tp\.|q|q\.|h|h\.|p|p\.|x|x\.|tx|tx\.|tt|tt\.)\b/g, ' ')
            .replace(/^(tinh|thanh pho|quan|huyen|thi xa|thi tran|phuong|xa)\s+/g, '')
            .replace(/\s+/g, ' ')
            .trim();
        const pickOptionFromText = (select, text) => {
            const haystack = normalizeAdminText(text);
            if (!haystack) return '';

            return Array.from(select.options)
                .map((option) => option.value)
                .filter(Boolean)
                .find((value) => {
                    const normalizedValue = normalizeAdminText(value);
                    return normalizedValue && (haystack.includes(normalizedValue) || normalizedValue.includes(haystack));
                }) || '';
        };
        const displayParts = String(geoData?.display_name || '')
            .split(',')
            .map((part) => part.trim())
            .filter(Boolean);
        const combinedAddressText = [
            geoData?.display_name || '',
            ...Object.values(addr || {}),
        ].filter(Boolean).join(', ');

        const provinceCandidates = uniqueCandidates([
            addr.state,
            addr.province,
            addr.region,
            addr.city,
            addr.county,
            addr['ISO3166-2-lvl4'],
            ...displayParts,
        ]);

        const districtCandidates = uniqueCandidates([
            addr.city_district,
            addr.county,
            addr.district,
            addr.state_district,
            addr.municipality,
            addr.city,
            addr.town,
            addr.borough,
            ...displayParts,
        ]);

        const wardCandidates = uniqueCandidates([
            addr.ward,
            addr.township,
            addr.suburb,
            addr.city_block,
            addr.quarter,
            addr.neighbourhood,
            addr.locality,
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
        if (!matchedProvince) matchedProvince = pickOptionFromText(refs.tinh, combinedAddressText);
        if (!matchedProvince) {
            suppressAddressGeocode = false;
            return false;
        }

        refs.tinh.value = matchedProvince;
        refs.tinh.dispatchEvent(new Event('change'));

        let matchedDistrict = '';
        for (const candidate of districtCandidates) {
            matchedDistrict = pickOptionValue(refs.huyen, candidate);
            if (matchedDistrict) break;
        }
        if (!matchedDistrict) matchedDistrict = pickOptionFromText(refs.huyen, combinedAddressText);
        if (matchedDistrict) {
            refs.huyen.value = matchedDistrict;
            refs.huyen.dispatchEvent(new Event('change'));
        }

        let matchedWard = '';
        for (const candidate of wardCandidates) {
            matchedWard = pickOptionValue(refs.xa, candidate);
            if (matchedWard) break;
        }
        if (!matchedWard) matchedWard = pickOptionFromText(refs.xa, combinedAddressText);
        if (matchedWard) {
            refs.xa.value = matchedWard;
            refs.xa.dispatchEvent(new Event('change'));
        }

        suppressAddressGeocode = false;

        return Boolean(matchedProvince && matchedDistrict && matchedWard);
    }

    function validateStep(step) {
        if (step === 1 && !state.serviceIds.length) return { valid: false, message: 'Vui lòng chọn ít nhất một dịch vụ để tiếp tục.' };
        if (step === 2 && !state.repairMode) return { valid: false, message: 'Vui lòng chọn hình thức sửa chữa.' };
        if (step === 3 && state.repairMode === 'at_home') {
            if (!state.lat || !state.lng) return { valid: false, message: 'Vui lòng lấy vị trí hiện tại hoặc nhập đủ địa chỉ để hệ thống tính phí di chuyển.' };
            if (state.isOutOfRange) return { valid: false, message: buildOutOfRangeValidationMessage() };
            if (!state.tinh || !state.huyen || !state.xa) return { valid: false, message: 'Vui lòng chọn đầy đủ Tỉnh / Huyện / Xã.' };
            if (!state.soNha.trim()) return { valid: false, message: 'Vui lòng nhập địa chỉ chi tiết.' };
        }
        if (step === 4 && !state.date) return { valid: false, message: 'Vui lòng chọn ngày hẹn.' };
        if (step === 4 && !state.timeSlot) return { valid: false, message: 'Vui lòng chọn khung giờ.' };
        if (step === 4 && availableTimeSlots().find((slot) => slot.value === state.timeSlot && slot.disabled)) {
            return { valid: false, message: 'Khung giờ đã chọn không còn khả dụng, vui lòng chọn lại.' };
        }
        return { valid: true };
    }

    const ADDRESS_API_BASE = 'https://provinces.open-api.vn/api/v2';
    const districtField = refs.huyen?.closest('.booking-field');
    const isMergedAddressMode = () => state.addressApiVersion === 'v2';
    const getSelectedProvinceData = () => {
        const selectedCode = refs.tinh?.options?.[refs.tinh.selectedIndex]?.getAttribute('data-code');
        return state.addressData.find((item) => String(item.code) === String(selectedCode));
    };
    const composeAdminAddress = () => [state.xa, isMergedAddressMode() ? '' : state.huyen, state.tinh].filter(Boolean).join(', ');
    const composeFullHomeAddress = () => [state.soNha.trim(), state.xa, isMergedAddressMode() ? '' : state.huyen, state.tinh].filter(Boolean).join(', ');
    const hasRequiredHomeAddressSelection = () => Boolean(state.tinh && state.xa && state.soNha.trim() && (isMergedAddressMode() || state.huyen));
    const setDistrictFieldVisibility = () => {
        if (!districtField) return;
        districtField.classList.toggle('d-none', isMergedAddressMode());
    };
    const uniqueAdminCandidates = (items) => [...new Set(items.filter(Boolean).map((item) => String(item).trim()).filter(Boolean))];
    const normalizeAdminText = (value) => norm(value)
        .replace(/[.,()/-]/g, ' ')
        .replace(/\b(tp|tp\.|q|q\.|h|h\.|p|p\.|x|x\.|tx|tx\.|tt|tt\.)\b/g, ' ')
        .replace(/^(tinh|thanh pho|quan|huyen|thi xa|thi tran|phuong|xa)\s+/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    const pickOptionFromText = (select, text) => {
        const haystack = normalizeAdminText(text);
        if (!haystack) return '';

        return Array.from(select.options)
            .map((option) => option.value)
            .filter(Boolean)
            .find((value) => {
                const normalizedValue = normalizeAdminText(value);
                return normalizedValue && (haystack.includes(normalizedValue) || normalizedValue.includes(haystack));
            }) || '';
    };
    const buildAdminCandidates = (geoData) => {
        const addr = geoData?.address || {};
        const displayParts = String(geoData?.display_name || '')
            .split(',')
            .map((part) => part.trim())
            .filter(Boolean);

        return {
            provinceCandidates: uniqueAdminCandidates([
                addr.state,
                addr.province,
                addr.region,
                addr.city,
                addr.county,
                addr['ISO3166-2-lvl4'],
                ...displayParts,
            ]),
            districtCandidates: uniqueAdminCandidates([
                addr.city_district,
                addr.county,
                addr.district,
                addr.state_district,
                addr.municipality,
                addr.city,
                addr.town,
                addr.borough,
                ...displayParts,
            ]),
            wardCandidates: uniqueAdminCandidates([
                addr.ward,
                addr.township,
                addr.suburb,
                addr.city_block,
                addr.quarter,
                addr.neighbourhood,
                addr.locality,
                addr.village,
                addr.hamlet,
                addr.residential,
                addr.allotments,
                ...displayParts,
            ]),
            combinedAddressText: [
                geoData?.display_name || '',
                ...Object.values(addr || {}),
            ].filter(Boolean).join(', '),
        };
    };

    async function resolveCurrentWardName(legacyName) {
        if (!legacyName) return '';

        try {
            const response = await fetch(`${ADDRESS_API_BASE}/w/from-legacy/?legacy_name=${encodeURIComponent(legacyName)}`);
            if (!response.ok) return '';

            const data = await response.json();
            if (!Array.isArray(data)) return '';

            const preferred = data.find((item) => item?.ward?.name && pickOptionValue(refs.xa, item.ward.name));
            return preferred?.ward?.name || data[0]?.ward?.name || '';
        } catch (error) {
            return '';
        }
    }

    function renderProvinceOptions() {
        refs.tinh.innerHTML = '<option value="">Chọn tỉnh / thành phố</option>' + state.addressData.map((tinh) => `<option value="${tinh.name}" data-code="${tinh.code}">${tinh.name}</option>`).join('');
        refs.huyen.innerHTML = isMergedAddressMode()
            ? '<option value="">Không áp dụng sau sáp nhập</option>'
            : '<option value="">Chọn quận / huyện</option>';
        refs.xa.innerHTML = '<option value="">Chọn phường / xã</option>';
        refs.huyen.disabled = isMergedAddressMode();
        refs.xa.disabled = true;
        setDistrictFieldVisibility();
    }

    function syncHidden() {
        refs.hiddenWorkerId.value = state.workerId ? String(state.workerId) : '';
        refs.hiddenRepairMode.value = state.repairMode || '';
        refs.hiddenLat.value = state.lat || '';
        refs.hiddenLng.value = state.lng || '';
        refs.hiddenDate.value = state.date || '';
        refs.hiddenTimeSlot.value = state.timeSlot || '';
        refs.hiddenStoreTransport.value = state.transportRequested ? '1' : '0';
        refs.hiddenDiaChi.value = state.repairMode === 'at_store' ? storeAddress : composeAdminAddress();
    }

    function updateSummary() {
        const services = selectedServices();
        const names = services.slice(0, 2).map((service) => service.ten_dich_vu).join(', ');
        const extra = Math.max(services.length - 2, 0);
        const hasService = services.length > 0;
        const hasWorker = Boolean(state.worker);
        const hasMode = Boolean(state.repairMode);
        const hasTime = Boolean(state.date && state.timeSlot);
        const hasAddress = Boolean(state.repairMode === 'at_store' || hasRequiredHomeAddressSelection());
        const hasTravel = Boolean(state.repairMode === 'at_store' || state.repairMode === 'at_home');
        const hasContent = Boolean(hasService || hasWorker || hasMode || hasTime || hasAddress || hasTravel);

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
        refs.sumModeMark.textContent = state.repairMode === 'at_home' ? 'NHA' : 'SHOP';

        refs.sumTimeCard.classList.toggle('d-none', !hasTime);
        refs.sumTimeValue.textContent = hasTime ? `${humanDate(state.date)} • ${state.timeSlot.replace('-', ' - ')}` : '';
        refs.sumTimeMeta.textContent = hasTime ? 'Khung giờ bạn đã chọn.' : '';

        refs.sumAddressCard.classList.toggle('d-none', !hasAddress);
        refs.sumAddressValue.textContent = state.repairMode === 'at_store' ? storeAddress : composeFullHomeAddress();
        refs.sumAddressMeta.textContent = state.repairMode === 'at_store'
            ? 'Địa chỉ tiếp nhận thiết bị.'
            : (state.lat && state.lng ? 'Địa chỉ đã gắn GPS.' : 'Địa chỉ nhập thủ công.');

        refs.sumTravelFee.textContent = money(state.travelFee);
        refs.sumTravelMeta.textContent = state.travelMessage;
    }

    function resetState(prefill = {}) {
        const addressData = state.addressData;
        const addressApiVersion = state.addressApiVersion || 'v2';
        Object.assign(state, {
            addressData,
            addressApiVersion,
            currentStep: 1,
            workerId: prefill.workerId ? Number(prefill.workerId) : null,
            worker: null,
            prefillServiceName: String(prefill.serviceName || ''),
            prefillServiceIds: normalizePrefillServiceIds(prefill.serviceIds),
            services: [],
            serviceIds: [],
            repairMode: null,
            tinh: '',
            huyen: '',
            xa: '',
            soNha: '',
            lat: '',
            lng: '',
            travelFee: 0,
            distanceKm: null,
            travelMessage: 'Sẽ tính sau khi bạn chọn vị trí hoặc nhập đủ địa chỉ.',
            date: '',
            timeSlot: '',
            description: '',
            images: [],
            video: null,
            transportRequested: false,
            isOutOfRange: false,
            isOpen: false,
            locationSource: '',
            symptomCatalog: [],
            symptomCatalogKey: '',
            symptomCatalogPromise: null,
            selectedSymptomId: null,
            selectedSymptomIds: {},
            serviceProblemInputs: {},
            activeProblemServiceId: null,
            busySlotsByDate: {},
        });
        refs.form.reset();
        refs.success.classList.add('d-none');
        refs.workerBanner.classList.add('d-none');
        refs.sumWorkerCard.classList.add('d-none');
        refs.locationStatus.textContent = 'Vui lòng lấy vị trí hiện tại hoặc nhập đủ địa chỉ để hệ thống tính phí di chuyển.';
        refs.preview.innerHTML = '';
        if (refs.problemFields) {
            refs.problemFields.innerHTML = '';
            refs.problemFields.classList.add('d-none');
        }
        resetProblemAssistState();
        if (addressLookupTimer) {
            window.clearTimeout(addressLookupTimer);
            addressLookupTimer = null;
        }
        addressLookupRequestId += 1;
        suppressAddressGeocode = false;
        if (state.addressData.length) renderProvinceOptions();
    }

    async function geocodeManualAddress() {
        if (state.repairMode !== 'at_home') return;

        if (!hasRequiredHomeAddressSelection()) {
            resetTravelEstimate(
                'Sẽ tính sau khi bạn chọn vị trí hoặc nhập đủ địa chỉ.',
                'Vui lòng lấy vị trí hiện tại hoặc nhập đủ địa chỉ để hệ thống tính phí di chuyển.'
            );
            return;
        }

        const lookupId = ++addressLookupRequestId;
        state.lat = '';
        state.lng = '';
        state.locationSource = '';
        state.travelFee = 0;
        state.distanceKm = null;
        state.isOutOfRange = false;
        state.travelMessage = 'Đang cập nhật phí đi lại theo địa chỉ bạn chọn...';
        syncHidden();
        updateSummary();
        refs.locationStatus.textContent = 'Đang xác định tọa độ theo địa chỉ bạn chọn...';

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=vn&q=${encodeURIComponent(composeFullHomeAddress())}`);
            const data = await response.json();

            if (lookupId !== addressLookupRequestId) return;

            const location = Array.isArray(data) ? data[0] : null;
            if (!location?.lat || !location?.lon) {
                resetTravelEstimate(
                    'Chưa thể tính phí đi lại vì không tìm thấy tọa độ phù hợp cho địa chỉ này.',
                    'Không tìm thấy tọa độ phù hợp cho địa chỉ bạn nhập. Vui lòng kiểm tra lại hoặc dùng GPS.'
                );
                return;
            }

            state.lat = String(location.lat);
            state.lng = String(location.lon);
            state.locationSource = 'manual';
            syncHidden();
            updateTravelEstimate();
            refs.locationStatus.textContent = state.isOutOfRange
                ? `Địa chỉ đang vượt quá ${formatServiceDistanceKm(getHomeReferencePoint().maxDistance)} km nên hệ thống không cho phép tiếp tục.`
                : 'Đã cập nhật phí đi lại theo địa chỉ bạn nhập.';
        } catch (error) {
            if (lookupId !== addressLookupRequestId) return;

            resetTravelEstimate(
                'Chưa thể tính phí đi lại vì không xác định được tọa độ địa chỉ.',
                'Chưa thể xác định tọa độ từ địa chỉ này. Vui lòng thử lại hoặc dùng GPS.'
            );
        }
    }

    function queueAddressRecalculation() {
        if (suppressAddressGeocode || state.repairMode !== 'at_home') return;

        if (addressLookupTimer) {
            window.clearTimeout(addressLookupTimer);
            addressLookupTimer = null;
        }

        addressLookupRequestId += 1;

        if (!hasRequiredHomeAddressSelection()) {
            resetTravelEstimate(
                'Sẽ tính sau khi bạn chọn vị trí hoặc nhập đủ địa chỉ.',
                'Vui lòng lấy vị trí hiện tại hoặc nhập đủ địa chỉ để hệ thống tính phí di chuyển.'
            );
            return;
        }

        addressLookupTimer = window.setTimeout(() => {
            geocodeManualAddress();
        }, 500);
    }

    async function loadAddressData() {
        if (state.addressData.length) {
            renderProvinceOptions();
            return;
        }

        try {
            const response = await fetch(`${ADDRESS_API_BASE}/?depth=2`);
            const payload = await response.json();
            state.addressData = Array.isArray(payload) ? payload : [];
            state.addressApiVersion = state.addressData.some((item) => Array.isArray(item?.wards)) ? 'v2' : 'v1';
            renderProvinceOptions();
        } catch (error) {
            refs.locationStatus.textContent = 'Lỗi khi tải dữ liệu địa chỉ. Vui lòng thử lại sau.';
        }
    }

    async function autoFillAdministrativeAddress(geoData) {
        const addr = geoData?.address;
        if (!addr) return false;

        suppressAddressGeocode = true;

        const {
            provinceCandidates,
            districtCandidates,
            wardCandidates,
            combinedAddressText,
        } = buildAdminCandidates(geoData);

        let matchedProvince = '';
        for (const candidate of provinceCandidates) {
            matchedProvince = pickOptionValue(refs.tinh, candidate);
            if (matchedProvince) break;
        }
        if (!matchedProvince) matchedProvince = pickOptionFromText(refs.tinh, combinedAddressText);
        if (!matchedProvince) {
            suppressAddressGeocode = false;
            return false;
        }

        refs.tinh.value = matchedProvince;
        refs.tinh.dispatchEvent(new Event('change'));

        let matchedDistrict = '';
        if (!isMergedAddressMode()) {
            for (const candidate of districtCandidates) {
                matchedDistrict = pickOptionValue(refs.huyen, candidate);
                if (matchedDistrict) break;
            }
            if (!matchedDistrict) matchedDistrict = pickOptionFromText(refs.huyen, combinedAddressText);
            if (matchedDistrict) {
                refs.huyen.value = matchedDistrict;
                refs.huyen.dispatchEvent(new Event('change'));
            }
        } else {
            state.huyen = districtCandidates[0] || addr.city || addr.county || '';
        }

        let matchedWard = '';
        for (const candidate of wardCandidates) {
            matchedWard = pickOptionValue(refs.xa, candidate);
            if (matchedWard) break;
        }

        if (!matchedWard) {
            for (const candidate of wardCandidates) {
                const currentWardName = await resolveCurrentWardName(candidate);
                matchedWard = pickOptionValue(refs.xa, currentWardName);
                if (matchedWard) break;
            }
        }

        if (!matchedWard) matchedWard = pickOptionFromText(refs.xa, combinedAddressText);
        if (matchedWard) {
            refs.xa.value = matchedWard;
            refs.xa.dispatchEvent(new Event('change'));
        }

        suppressAddressGeocode = false;

        return Boolean(matchedProvince && matchedWard && (isMergedAddressMode() || matchedDistrict));
    }

    function validateStep(step) {
        if (step === 1 && !state.serviceIds.length) return { valid: false, message: 'Vui lòng chọn ít nhất một dịch vụ để tiếp tục.' };
        if (step === 2 && !state.repairMode) return { valid: false, message: 'Vui lòng chọn hình thức sửa chữa.' };
        if (step === 3 && state.repairMode === 'at_home') {
            if (!state.lat || !state.lng) return { valid: false, message: 'Vui lòng lấy vị trí hiện tại hoặc nhập đủ địa chỉ để hệ thống tính phí di chuyển.' };
            if (state.isOutOfRange) return { valid: false, message: buildOutOfRangeValidationMessage() };
            if (isMergedAddressMode()) {
                if (!state.tinh || !state.xa) return { valid: false, message: 'Vui lòng chọn đầy đủ Tỉnh / Thành phố và Phường / Xã.' };
            } else if (!state.tinh || !state.huyen || !state.xa) {
                return { valid: false, message: 'Vui lòng chọn đầy đủ Tỉnh / Huyện / Xã.' };
            }
            if (!state.soNha.trim()) return { valid: false, message: 'Vui lòng nhập địa chỉ chi tiết.' };
        }
        if (step === 4 && !state.date) return { valid: false, message: 'Vui lòng chọn ngày hẹn.' };
        if (step === 4 && !state.timeSlot) return { valid: false, message: 'Vui lòng chọn khung giờ.' };
        if (step === 4 && availableTimeSlots().find((slot) => slot.value === state.timeSlot && slot.disabled)) {
            return { valid: false, message: 'Khung giờ đã chọn không còn khả dụng, vui lòng chọn lại.' };
        }
        return { valid: true };
    }

    refs.tinh.addEventListener('change', () => {
        if (!isMergedAddressMode()) return;

        state.huyen = '';
        refs.huyen.innerHTML = '<option value="">Không áp dụng sau sáp nhập</option>';
        refs.huyen.disabled = true;

        const province = getSelectedProvinceData();
        const wards = Array.isArray(province?.wards) ? province.wards : [];
        refs.xa.innerHTML = '<option value="">Chọn phường / xã</option>' + wards.map((xa) => `<option value="${xa.name}">${xa.name}</option>`).join('');
        refs.xa.disabled = wards.length === 0;

        syncHidden();
        updateSummary();
        queueAddressRecalculation();
    });

    repairCards.forEach((card) => card.addEventListener('click', () => {
        if (state.repairMode === 'at_store') {
            refs.locationStatus.textContent = 'Không cần lấy vị trí khi mang thiết bị đến cửa hàng.';
            return;
        }

        refs.locationStatus.textContent = hasRequiredHomeAddressSelection()
            ? 'Hệ thống sẽ tự cập nhật phí di chuyển khi bạn thay đổi địa chỉ.'
            : 'Vui lòng lấy vị trí hiện tại hoặc nhập đủ địa chỉ để hệ thống tính phí di chuyển.';
    }));

    renderDateCards();
    renderTimeSlots();
    updateHeader();
    updateAddressPanels();
    syncHidden();
    updateSummary();
    loadTravelFeeConfig();
    window.addEventListener('resize', queueViewportFit);
    if (isStandalone) openModal();
});
