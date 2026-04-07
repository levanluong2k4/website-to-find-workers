import { callApi, getCurrentUser, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('bookingWizardModal');
    if (!root) return;

    const STORE_ADDRESS = '2 Đường Nguyễn Đình Chiểu, Vĩnh Thọ, Nha Trang, Khánh Hòa';
    const STORE_REFERENCE = { lat: 12.2618, lng: 109.1995, maxDistance: 20, label: 'cửa hàng' };
    const TRAVEL_FEE_PER_KM = 5000;
    let travelFeeConfig = {
        default_per_km: TRAVEL_FEE_PER_KM,
        tiers: [],
    };
    const TIME_SLOTS = ['08:00-10:00', '10:00-12:00', '12:00-14:00', '14:00-17:00'];
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
        symptomCatalog: [], symptomCatalogKey: '', symptomCatalogPromise: null, selectedSymptomId: null, busySlotsByDate: {},
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
    const findTravelFeeTier = (distanceKm) => (travelFeeConfig.tiers || []).find((tier) => distanceKm >= Number(tier.from_km || 0) && distanceKm <= Number(tier.to_km || 0)) || null;
    const resolveTravelFee = (distanceKm) => {
        const tier = findTravelFeeTier(distanceKm);

        if (tier) {
            return Number(tier.fee || 0);
        }

        return Math.round(distanceKm * Number(travelFeeConfig.default_per_km || TRAVEL_FEE_PER_KM));
    };
    const buildTravelFeeMessage = ({ roundedDistance, refLabel, isOutOfRange, maxDistance, manualSuffix = '' }) => {
        if (isOutOfRange) {
            return `Khoảng cách hiện tại vượt phạm vi phục vụ ${maxDistance} km của ${refLabel}.`;
        }

        const tier = findTravelFeeTier(roundedDistance);
        if (tier) {
            return `Tạm tính ${roundedDistance} km từ ${refLabel}${manualSuffix}: áp dụng ${money(tier.fee)} cho khoảng ${tier.from_km} - ${tier.to_km} km.`;
        }

        return `Tạm tính ${roundedDistance} km × ${Number(travelFeeConfig.default_per_km || TRAVEL_FEE_PER_KM).toLocaleString('vi-VN')} ₫/km từ ${refLabel}${manualSuffix}.`;
    };
    const loadTravelFeeConfig = async () => {
        try {
            const res = await callApi('/travel-fee-config');
            if (!res.ok) {
                return;
            }

            const config = res.data?.data?.config;
            if (config && typeof config === 'object') {
                travelFeeConfig = {
                    default_per_km: Number(config.default_per_km || TRAVEL_FEE_PER_KM),
                    tiers: Array.isArray(config.tiers) ? config.tiers : [],
                };
                if (state.repairMode === 'at_home' && state.lat && state.lng) {
                    updateTravelEstimate();
                } else {
                    updateSummary();
                }
            }
        } catch (error) {
            console.warn('Khong tai duoc cau hinh phi di lai', error);
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

    function getSymptomCatalogServiceKey() {
        return [...state.serviceIds]
            .map((id) => Number(id))
            .filter((id) => Number.isFinite(id) && id > 0)
            .sort((a, b) => a - b)
            .join(',');
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
        return 'Lien he bao gia';
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
            ? 'Khoang gia se hien khi ban chon mot trieu chung phu hop.'
            : hasAnyPrice
                ? `Theo ${resolutionCount || 0} huong xu ly${causeCount ? ` va ${causeCount} nguyen nhan lien quan` : ''}.${causePreview ? ` Thuong gap: ${causePreview}.` : ''}`
                : 'Trieu chung nay da co trong danh muc nhung chua duoc khai bao gia tham khao.';

        if (refs.problemPriceCard) refs.problemPriceCard.classList.toggle('d-none', !hasSymptom);
        if (refs.problemPriceValue) refs.problemPriceValue.textContent = priceLabel;
        if (refs.problemPriceMeta) refs.problemPriceMeta.textContent = metaText;
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
        if (!refs.problemSuggest || !refs.problemSuggestList || !refs.problemSuggestCopy) return;

        const queryText = getSymptomQueryText(refs.description?.value);
        const loading = Boolean(options.loading);
        refs.problemSuggest.classList.toggle('d-none', queryText === '');

        if (queryText === '') {
            refs.problemSuggestList.innerHTML = '';
            return;
        }

        if (loading) {
            refs.problemSuggestCopy.textContent = 'Dang doi chieu mo ta voi trieu chung co san...';
            refs.problemSuggestList.innerHTML = '<div class="booking-problem-chip-empty">Dang tim goi y phu hop...</div>';
            return;
        }

        refs.problemSuggestCopy.textContent = items.length
            ? 'Bam vao goi y de dien nhanh mo ta va xem khoang gia tham khao.'
            : 'Khong tim thay trieu chung khop voi noi dung ban vua nhap.';

        refs.problemSuggestList.innerHTML = items.length
            ? items.map((item) => {
                const isActive = Number(item?.id || 0) === Number(state.selectedSymptomId || 0);
                const serviceMeta = state.serviceIds.length > 1 && item?.dich_vu_name
                    ? `<small>${escapeHtml(item.dich_vu_name)}</small>`
                    : '';
                return `<button type="button" class="booking-problem-chip ${isActive ? 'is-active' : ''}" data-problem-symptom-id="${Number(item?.id || 0)}">${escapeHtml(item?.ten_trieu_chung || 'Trieu chung')}${serviceMeta}</button>`;
            }).join('')
            : '<div class="booking-problem-chip-empty">Thu mo ta ngan gon hon hoac chon dich vu cu the de he thong goi y chinh xac hon.</div>';

        refs.problemSuggestList.querySelectorAll('[data-problem-symptom-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const symptomId = Number(button.getAttribute('data-problem-symptom-id') || 0);
                const symptom = (Array.isArray(state.symptomCatalog) ? state.symptomCatalog : [])
                    .find((item) => Number(item?.id || 0) === symptomId);
                if (!symptom) return;

                state.selectedSymptomId = symptomId;
                state.description = symptom.ten_trieu_chung || '';
                if (refs.description) {
                    refs.description.value = state.description;
                    refs.description.focus();
                    const textLength = refs.description.value.length;
                    refs.description.setSelectionRange(textLength, textLength);
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
                throw new Error(response.data?.message || 'Khong tai duoc danh muc trieu chung.');
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
            if (refs.problemSuggest) refs.problemSuggest.classList.remove('d-none');
            if (refs.problemSuggestCopy) refs.problemSuggestCopy.textContent = error.message || 'Khong tai duoc goi y trieu chung.';
            if (refs.problemSuggestList) refs.problemSuggestList.innerHTML = '<div class="booking-problem-chip-empty">Tam thoi khong tai duoc goi y. Vui long thu lai sau.</div>';
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
            date: '', timeSlot: '', description: '', images: [], video: null, transportRequested: false, isOutOfRange: false, locationSource: '', busySlotsByDate: {},
        });
        refs.form.reset();
        refs.success.classList.add('d-none');
        refs.workerBanner.classList.add('d-none');
        refs.sumWorkerCard.classList.add('d-none');
        refs.locationStatus.textContent = 'Vui lòng lấy vị trí hiện tại hoặc nhập địa chỉ thủ công.';
        refs.preview.innerHTML = '';
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
        const refPoint = hasWorkerReferenceCoordinates()
            ? { lat: Number(state.worker.vi_do), lng: Number(state.worker.kinh_do), maxDistance: Number(state.worker?.ban_kinh_phuc_vu ?? 10), label: state.worker?.user?.name || 'thợ đã chọn' }
            : STORE_REFERENCE;
        const distanceKm = distKm(refPoint.lat, refPoint.lng, lat, lng);
        const rounded = Number(distanceKm.toFixed(1));
        state.travelFee = resolveTravelFee(distanceKm);
        state.distanceKm = rounded;
        state.isOutOfRange = rounded > Number(refPoint.maxDistance || STORE_REFERENCE.maxDistance);
        state.travelMessage = buildTravelFeeMessage({
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
            throw new Error(result.data?.message || 'Khong tai duoc lich ban cua tho.');
        }

        state.busySlotsByDate = Object.fromEntries(Object.entries(result.data?.busy_slots || {}).map(([date, slots]) => [
            date,
            Array.isArray(slots) ? slots.map((slot) => normalizeTimeSlotValue(slot)).filter(Boolean) : [],
        ]));
    }

    function getSlotDisableReason(slot, index, targetIndex) {
        const busySlots = Array.isArray(state.busySlotsByDate?.[state.date]) ? state.busySlotsByDate[state.date] : [];

        if (busySlots.includes(slot)) {
            return 'Tho da co lich vao thoi diem nay';
        }

        if (index < targetIndex) {
            return 'Khung gio nay da qua';
        }

        return '';
    }

    function availableTimeSlots() {
        const slots = TIME_SLOTS.map((slot) => ({ value: slot, disabled: false, reason: '' }));
        if (!state.date) return slots;
        const now = new Date();
        const mins = now.getHours() * 60 + now.getMinutes();
        let target = mins < 480 ? 0 : mins < 600 ? 1 : mins < 720 ? 2 : mins < 840 ? 3 : 4;
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
                showToast(button.dataset.disabledReason || 'Khung gio nay khong con kha dung.', 'error');
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
            refs.transportToggle.checked = false;
            renderProblemReferencePrice(null);
            renderServices();
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
            if (state.workerId) {
                try {
                    await fetchWorkerBusySlots();
                } catch (busySlotsError) {
                    state.busySlotsByDate = {};
                    showToast(busySlotsError.message || 'Khong tai duoc lich ban cua tho.', 'error');
                }
                clearDisabledTimeSlot();
                renderTimeSlots();
            }
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
        state.description = refs.description.value;
        const selected = getSelectedSymptomSuggestion();
        const queryText = getSymptomQueryText(state.description);
        const isExactSelected = selected && getSymptomQueryText(selected.ten_trieu_chung) === queryText;

        if (!isExactSelected) {
            state.selectedSymptomId = null;
            renderProblemReferencePrice(null);
        }

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

    STORE_REFERENCE.maxDistance = 8;

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
            travelMessage: 'Se tinh sau khi ban chon vi tri hoac nhap du dia chi.',
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
            busySlotsByDate: {},
        });
        refs.form.reset();
        refs.success.classList.add('d-none');
        refs.workerBanner.classList.add('d-none');
        refs.sumWorkerCard.classList.add('d-none');
        refs.locationStatus.textContent = 'Vui long lay vi tri hien tai hoac nhap du dia chi de he thong tinh phi di chuyen.';
        refs.preview.innerHTML = '';
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
            state.travelFee = 0;
            state.distanceKm = 0;
            state.isOutOfRange = false;
            state.locationSource = '';
            state.travelMessage = 'Ban chon mang thiet bi den cua hang nen khong phat sinh phi di lai.';
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
            state.travelMessage = 'Se tinh sau khi ban chon vi tri hoac nhap du dia chi.';
            syncHidden();
            updateSummary();
            return;
        }

        const workerMaxDistance = Number(state.worker?.ban_kinh_phuc_vu ?? 8);
        const refPoint = hasWorkerReferenceCoordinates()
            ? {
                lat: Number(state.worker.vi_do),
                lng: Number(state.worker.kinh_do),
                maxDistance: Math.min(workerMaxDistance, 8),
                label: state.worker?.user?.name || 'tho da chon',
            }
            : { ...STORE_REFERENCE, maxDistance: 8, label: 'cua hang' };

        const distanceKm = distKm(refPoint.lat, refPoint.lng, lat, lng);
        const rounded = Number(distanceKm.toFixed(1));
        state.travelFee = resolveTravelFee(distanceKm);
        state.distanceKm = rounded;
        state.isOutOfRange = rounded > Number(refPoint.maxDistance || 8);
        state.travelMessage = state.isOutOfRange
            ? `Dia chi dang vuot qua pham vi phuc vu ${refPoint.maxDistance} km tu ${refPoint.label}.`
            : buildTravelFeeMessage({
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
                'Se tinh sau khi ban chon vi tri hoac nhap du dia chi.',
                'Vui long lay vi tri hien tai hoac nhap du dia chi de he thong tinh phi di chuyen.'
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
        state.travelMessage = 'Dang cap nhat phi di lai theo dia chi ban chon...';
        syncHidden();
        updateSummary();
        refs.locationStatus.textContent = 'Dang xac dinh toa do theo dia chi ban chon...';

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=vn&q=${encodeURIComponent(homeAddressLabel())}`);
            const data = await response.json();

            if (lookupId !== addressLookupRequestId) return;

            const location = Array.isArray(data) ? data[0] : null;
            if (!location?.lat || !location?.lon) {
                resetTravelEstimate(
                    'Chua the tinh phi di lai vi khong tim thay toa do phu hop cho dia chi nay.',
                    'Khong tim thay toa do phu hop cho dia chi ban nhap. Vui long kiem tra lai hoac dung GPS.'
                );
                return;
            }

            state.lat = String(location.lat);
            state.lng = String(location.lon);
            state.locationSource = 'manual';
            syncHidden();
            updateTravelEstimate();
            refs.locationStatus.textContent = state.isOutOfRange
                ? 'Dia chi dang vuot qua 8 km nen he thong khong cho phep tiep tuc.'
                : 'Da cap nhat phi di lai theo dia chi ban nhap.';
        } catch (error) {
            if (lookupId !== addressLookupRequestId) return;

            resetTravelEstimate(
                'Chua the tinh phi di lai vi khong xac dinh duoc toa do dia chi.',
                'Chua the xac dinh toa do tu dia chi nay. Vui long thu lai hoac dung GPS.'
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
                'Se tinh sau khi ban chon vi tri hoac nhap du dia chi.',
                'Vui long lay vi tri hien tai hoac nhap du dia chi de he thong tinh phi di chuyen.'
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
        if (step === 1 && !state.serviceIds.length) return { valid: false, message: 'Vui long chon it nhat mot dich vu de tiep tuc.' };
        if (step === 2 && !state.repairMode) return { valid: false, message: 'Vui long chon hinh thuc sua chua.' };
        if (step === 3 && state.repairMode === 'at_home') {
            if (!state.lat || !state.lng) return { valid: false, message: 'Vui long lay vi tri hien tai hoac nhap du dia chi de he thong tinh phi di chuyen.' };
            if (state.isOutOfRange) return { valid: false, message: 'Dia chi cua ban vuot qua 8 km. Vui long chon dia chi gan hon hoac mang thiet bi den cua hang.' };
            if (!state.tinh || !state.huyen || !state.xa) return { valid: false, message: 'Vui long chon day du Tinh / Huyen / Xa.' };
            if (!state.soNha.trim()) return { valid: false, message: 'Vui long nhap dia chi chi tiet.' };
        }
        if (step === 4 && !state.date) return { valid: false, message: 'Vui long chon ngay hen.' };
        if (step === 4 && !state.timeSlot) return { valid: false, message: 'Vui long chon khung gio.' };
        if (step === 4 && availableTimeSlots().find((slot) => slot.value === state.timeSlot && slot.disabled)) {
            return { valid: false, message: 'Khung gio da chon khong con kha dung, vui long chon lai.' };
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
        refs.tinh.innerHTML = '<option value="">Chon tinh / thanh pho</option>' + state.addressData.map((tinh) => `<option value="${tinh.name}" data-code="${tinh.code}">${tinh.name}</option>`).join('');
        refs.huyen.innerHTML = isMergedAddressMode()
            ? '<option value="">Khong ap dung sau sap nhap</option>'
            : '<option value="">Chon quan / huyen</option>';
        refs.xa.innerHTML = '<option value="">Chon phuong / xa</option>';
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
        refs.hiddenDiaChi.value = state.repairMode === 'at_store' ? STORE_ADDRESS : composeAdminAddress();
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
        refs.sumServiceMeta.textContent = services.length > 1 ? `${services.length} dich vu trong cung mot lich hen.` : (services[0]?.mo_ta || '');
        refs.sumServiceThumb.src = services[0]?.hinh_anh || '/assets/images/logontu.png';

        refs.sumWorkerCard.classList.toggle('d-none', !hasWorker);
        if (hasWorker) {
            refs.sumWorkerThumb.src = state.worker?.user?.avatar || '/assets/images/user-default.png';
            refs.sumWorkerValue.textContent = state.worker?.user?.name || 'Tho sua chua';
            refs.sumWorkerMeta.textContent = state.worker?.user?.dich_vus?.map((service) => service.ten_dich_vu).join(', ')
                || state.worker?.user?.dichVus?.map((service) => service.ten_dich_vu).join(', ')
                || refs.sumWorkerMeta.textContent
                || '';
        }

        refs.sumModeCard.classList.toggle('d-none', !hasMode);
        refs.sumModeValue.textContent = state.repairMode === 'at_home' ? 'Sua tai nha' : 'Mang den cua hang';
        refs.sumModeMeta.textContent = state.repairMode === 'at_home'
            ? 'Ky thuat vien den tan noi.'
            : (state.transportRequested ? 'Co thue xe cho thiet bi hai chieu.' : 'Ban tu mang thiet bi den cua hang.');
        refs.sumModeMark.textContent = state.repairMode === 'at_home' ? 'NHA' : 'SHOP';

        refs.sumTimeCard.classList.toggle('d-none', !hasTime);
        refs.sumTimeValue.textContent = hasTime ? `${humanDate(state.date)} • ${state.timeSlot.replace('-', ' - ')}` : '';
        refs.sumTimeMeta.textContent = hasTime ? 'Khung gio ban da chon.' : '';

        refs.sumAddressCard.classList.toggle('d-none', !hasAddress);
        refs.sumAddressValue.textContent = state.repairMode === 'at_store' ? STORE_ADDRESS : composeFullHomeAddress();
        refs.sumAddressMeta.textContent = state.repairMode === 'at_store'
            ? 'Dia chi tiep nhan thiet bi.'
            : (state.lat && state.lng ? 'Dia chi da gan GPS.' : 'Dia chi nhap thu cong.');

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
            travelMessage: 'Se tinh sau khi ban chon vi tri hoac nhap du dia chi.',
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
            busySlotsByDate: {},
        });
        refs.form.reset();
        refs.success.classList.add('d-none');
        refs.workerBanner.classList.add('d-none');
        refs.sumWorkerCard.classList.add('d-none');
        refs.locationStatus.textContent = 'Vui long lay vi tri hien tai hoac nhap du dia chi de he thong tinh phi di chuyen.';
        refs.preview.innerHTML = '';
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
                'Se tinh sau khi ban chon vi tri hoac nhap du dia chi.',
                'Vui long lay vi tri hien tai hoac nhap du dia chi de he thong tinh phi di chuyen.'
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
        state.travelMessage = 'Dang cap nhat phi di lai theo dia chi ban chon...';
        syncHidden();
        updateSummary();
        refs.locationStatus.textContent = 'Dang xac dinh toa do theo dia chi ban chon...';

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=vn&q=${encodeURIComponent(composeFullHomeAddress())}`);
            const data = await response.json();

            if (lookupId !== addressLookupRequestId) return;

            const location = Array.isArray(data) ? data[0] : null;
            if (!location?.lat || !location?.lon) {
                resetTravelEstimate(
                    'Chua the tinh phi di lai vi khong tim thay toa do phu hop cho dia chi nay.',
                    'Khong tim thay toa do phu hop cho dia chi ban nhap. Vui long kiem tra lai hoac dung GPS.'
                );
                return;
            }

            state.lat = String(location.lat);
            state.lng = String(location.lon);
            state.locationSource = 'manual';
            syncHidden();
            updateTravelEstimate();
            refs.locationStatus.textContent = state.isOutOfRange
                ? 'Dia chi dang vuot qua 8 km nen he thong khong cho phep tiep tuc.'
                : 'Da cap nhat phi di lai theo dia chi ban nhap.';
        } catch (error) {
            if (lookupId !== addressLookupRequestId) return;

            resetTravelEstimate(
                'Chua the tinh phi di lai vi khong xac dinh duoc toa do dia chi.',
                'Chua the xac dinh toa do tu dia chi nay. Vui long thu lai hoac dung GPS.'
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
                'Se tinh sau khi ban chon vi tri hoac nhap du dia chi.',
                'Vui long lay vi tri hien tai hoac nhap du dia chi de he thong tinh phi di chuyen.'
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
            refs.locationStatus.textContent = 'Loi khi tai du lieu dia chi. Vui long thu lai sau.';
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
        if (step === 1 && !state.serviceIds.length) return { valid: false, message: 'Vui long chon it nhat mot dich vu de tiep tuc.' };
        if (step === 2 && !state.repairMode) return { valid: false, message: 'Vui long chon hinh thuc sua chua.' };
        if (step === 3 && state.repairMode === 'at_home') {
            if (!state.lat || !state.lng) return { valid: false, message: 'Vui long lay vi tri hien tai hoac nhap du dia chi de he thong tinh phi di chuyen.' };
            if (state.isOutOfRange) return { valid: false, message: 'Dia chi cua ban vuot qua 8 km. Vui long chon dia chi gan hon hoac mang thiet bi den cua hang.' };
            if (isMergedAddressMode()) {
                if (!state.tinh || !state.xa) return { valid: false, message: 'Vui long chon day du Tinh / Thanh pho va Phuong / Xa.' };
            } else if (!state.tinh || !state.huyen || !state.xa) {
                return { valid: false, message: 'Vui long chon day du Tinh / Huyen / Xa.' };
            }
            if (!state.soNha.trim()) return { valid: false, message: 'Vui long nhap dia chi chi tiet.' };
        }
        if (step === 4 && !state.date) return { valid: false, message: 'Vui long chon ngay hen.' };
        if (step === 4 && !state.timeSlot) return { valid: false, message: 'Vui long chon khung gio.' };
        if (step === 4 && availableTimeSlots().find((slot) => slot.value === state.timeSlot && slot.disabled)) {
            return { valid: false, message: 'Khung gio da chon khong con kha dung, vui long chon lai.' };
        }
        return { valid: true };
    }

    refs.tinh.addEventListener('change', () => {
        if (!isMergedAddressMode()) return;

        state.huyen = '';
        refs.huyen.innerHTML = '<option value="">Khong ap dung sau sap nhap</option>';
        refs.huyen.disabled = true;

        const province = getSelectedProvinceData();
        const wards = Array.isArray(province?.wards) ? province.wards : [];
        refs.xa.innerHTML = '<option value="">Chon phuong / xa</option>' + wards.map((xa) => `<option value="${xa.name}">${xa.name}</option>`).join('');
        refs.xa.disabled = wards.length === 0;

        syncHidden();
        updateSummary();
        queueAddressRecalculation();
    });

    repairCards.forEach((card) => card.addEventListener('click', () => {
        if (state.repairMode === 'at_store') {
            refs.locationStatus.textContent = 'Khong can lay vi tri khi mang thiet bi den cua hang.';
            return;
        }

        refs.locationStatus.textContent = hasRequiredHomeAddressSelection()
            ? 'He thong se tu cap nhat phi di chuyen khi ban thay doi dia chi.'
            : 'Vui long lay vi tri hien tai hoac nhap du dia chi de he thong tinh phi di chuyen.';
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
