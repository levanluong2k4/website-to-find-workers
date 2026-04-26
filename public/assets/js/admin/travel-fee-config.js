import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const DEFAULT_VISIBLE_CONFIG = {
        store_address: '2 \u0110\u01b0\u1eddng Nguy\u1ec5n \u0110\u00ecnh Chi\u1ec3u, V\u0129nh Th\u1ecd, Nha Trang, Kh\u00e1nh H\u00f2a',
        store_latitude: 12.2618,
        store_longitude: 109.1995,
        store_hotline: '0905 123 456',
        store_opening_hours: 'Th\u1ee9 2 - CN: 07:00 - 20:00',
        booking_time_slots: ['08:00-10:00', '10:00-12:00', '12:00-14:00', '14:00-17:00'],
        max_service_distance_km: 8,
        tiers: [
            { from_km: 0, to_km: 1, transport_fee: 0, travel_fee: 0 },
            { from_km: 1, to_km: 5, transport_fee: 50000, travel_fee: 17000 },
        ],
    };

    const DEFAULT_LEGACY_CONFIG = {
        free_distance_km: 1,
        max_service_distance_km: 8,
        default_per_km: 5000,
        store_latitude: 12.2618,
        store_longitude: 109.1995,
        store_transport_fee: 0,
        complaint_window_days: 3,
    };

    const state = {
        previewMode: 'tiered',
        activeDistanceKm: 3,
        legacyConfig: { ...DEFAULT_LEGACY_CONFIG },
        coverageMap: {
            map: null,
            tileLayer: null,
            marker: null,
            circle: null,
        },
    };

    const refs = {
        form: document.getElementById('travelFeeForm'),
        tierList: document.getElementById('travelTierList'),
        storeAddressInput: document.getElementById('travelFeeStoreAddress'),
        storeLatitudeInput: document.getElementById('travelFeeStoreLatitude'),
        storeLongitudeInput: document.getElementById('travelFeeStoreLongitude'),
        storeHotlineInput: document.getElementById('travelFeeStoreHotline'),
        storeOpeningHoursInput: document.getElementById('travelFeeStoreOpeningHours'),
        bookingSlotList: document.getElementById('travelFeeBookingSlotList'),
        addBookingSlotButton: document.getElementById('btnAddBookingTimeSlot'),
        maxServiceDistanceInput: document.getElementById('travelFeeMaxServiceDistance'),
        complaintWindowInput: document.getElementById('travelFeeComplaintWindowDays'),
        addTierButton: document.getElementById('btnAddTravelTier'),
        resetButton: document.getElementById('btnResetTravelFeeForm'),
        saveButton: document.getElementById('btnSaveTravelFee'),
        statusChip: document.getElementById('travelFeeStatusChip'),
        updatedChip: document.getElementById('travelFeeUpdatedChip'),
        modeChip: document.getElementById('travelFeeModeChip'),
        modeButtons: Array.from(document.querySelectorAll('[data-preview-mode]')),
        slider: document.getElementById('travelFeeDistanceSlider'),
        distanceInput: document.getElementById('travelFeeDistanceNumber'),
        distanceBadge: document.getElementById('travelFeeDistanceBadge'),
        activeRuleLabel: document.getElementById('travelFeeActiveRuleLabel'),
        activePrice: document.getElementById('travelFeeActivePrice'),
        activeTransportMeta: document.getElementById('travelFeeActiveTransportMeta'),
        activeRuleCopy: document.getElementById('travelFeeActiveRuleCopy'),
        transportPreview: document.getElementById('travelFeeTransportPreview'),
        travelPreview: document.getElementById('travelFeeTravelPreview'),
        rangePreview: document.getElementById('travelFeeRangePreview'),
        storeAddressPreview: document.getElementById('travelFeeStoreAddressPreview'),
        rulePreview: document.getElementById('travelFeeRulePreview'),
        sampleGrid: document.getElementById('travelFeeSampleGrid'),
        coverageMapCanvas: document.getElementById('travelFeeCoverageMap'),
        coverageFallback: document.getElementById('travelFeeCoverageFallback'),
        coverageRadius: document.getElementById('travelFeeCoverageRadius'),
        coverageHint: document.getElementById('travelFeeCoverageHint'),
        coverageAddress: document.getElementById('travelFeeCoverageAddress'),
        coverageCoordinates: document.getElementById('travelFeeCoverageCoordinates'),
    };

    const ensureHookNode = (id, tagName = 'div', className = 'tw-hidden') => {
        const existingNode = document.getElementById(id);
        if (existingNode) {
            return existingNode;
        }

        const node = document.createElement(tagName);
        node.id = id;
        node.className = className;
        document.body.appendChild(node);
        return node;
    };

    refs.statusChip = refs.statusChip || ensureHookNode('travelFeeStatusChip', 'span', 'tfc-status-chip tw-hidden');
    refs.updatedChip = refs.updatedChip || ensureHookNode('travelFeeUpdatedChip', 'span', 'd-none');
    refs.modeChip = refs.modeChip || ensureHookNode('travelFeeModeChip', 'span', 'tw-hidden');
    refs.storeAddressPreview = refs.storeAddressPreview || ensureHookNode('travelFeeStoreAddressPreview', 'span', 'tw-hidden');
    refs.activeRuleCopy = refs.activeRuleCopy || ensureHookNode('travelFeeActiveRuleCopy', 'div', 'tw-hidden');
    refs.activeTransportMeta = refs.activeTransportMeta || ensureHookNode('travelFeeActiveTransportMeta', 'div', 'tw-hidden');
    refs.rulePreview = refs.rulePreview || ensureHookNode('travelFeeRulePreview', 'div', 'tw-hidden');
    refs.sampleGrid = refs.sampleGrid || ensureHookNode('travelFeeSampleGrid', 'div', 'tw-hidden');

    refs.coverageMapCanvas?.parentElement?.querySelector('img[alt="Map Visualization"]')?.remove();
    refs.coverageMapCanvas?.parentElement
        ?.querySelector('.tw-absolute.tw-inset-0.tw-bg-gradient-to-t.tw-from-surface-container-highest\\/80.tw-to-transparent.tw-flex.tw-items-end.tw-p-6')
        ?.remove();

    const syncFieldCopy = (input, options = {}) => {
        const wrapper = input?.closest('.tw-space-y-2');
        if (!wrapper) {
            return;
        }

        const labelNode = wrapper.querySelector('label');
        const helperNode = wrapper.querySelector('p.tw-text-xs');

        if (options.label && labelNode) {
            labelNode.textContent = options.label;
        }

        if (options.helper && helperNode) {
            helperNode.textContent = options.helper;
        }

        if (typeof options.placeholder === 'string' && input) {
            input.placeholder = options.placeholder;
        }
    };

    const syncStaticFieldCopy = () => {
        syncFieldCopy(refs.storeAddressInput, {
            placeholder: 'VD: 2 \u0110\u01b0\u1eddng Nguy\u1ec5n \u0110\u00ecnh Chi\u1ec3u, V\u0129nh Th\u1ecd, Nha Trang',
            helper: 'Hi\u1ec3n cho kh\u00e1ch khi h\u1ecfi \u0111\u1ecba ch\u1ec9 c\u1eeda h\u00e0ng v\u00e0 link b\u1ea3n \u0111\u1ed3.',
        });
        syncFieldCopy(refs.storeLatitudeInput, {
            label: 'Vi do cua hang',
            placeholder: 'VD: 12.2618',
            helper: 'Nhap toa do GPS cua cua hang de he thong tinh khoang cach chinh xac.',
        });
        syncFieldCopy(refs.storeLongitudeInput, {
            label: 'Kinh do cua hang',
            placeholder: 'VD: 109.1995',
            helper: 'Nhap toa do GPS cung cap voi vi do o tren.',
        });
        syncFieldCopy(refs.storeHotlineInput, {
            label: 'Hotline c\u1eeda h\u00e0ng',
            placeholder: 'VD: 0905 123 456',
            helper: 'Chatbot v\u00e0 trang kh\u00e1ch s\u1ebd d\u00f9ng s\u1ed1 n\u00e0y khi h\u1ecfi hotline.',
        });
        syncFieldCopy(refs.maxServiceDistanceInput, {
            label: 'Pham vi phuc vu toi da (km)',
            placeholder: 'VD: 8',
            helper: 'Gioi han toi da cho sua tai nha tu cua hang hoac tu tho duoc chi dinh.',
        });
        syncFieldCopy(refs.complaintWindowInput, {
            helper: 'D\u00f9ng cho quy t\u1eafc h\u1ed7 tr\u1ee3 sau s\u1eeda ch\u1eefa v\u00e0 khi\u1ebfu n\u1ea1i.',
        });
    };

    syncStaticFieldCopy();

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[character] || character));

    const parseNumber = (value) => {
        const normalized = String(value ?? '').trim().replace(',', '.');
        if (normalized === '') {
            return null;
        }

        const parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : null;
    };

    const roundDistance = (value) => Number((Number(value) || 0).toFixed(1));
    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
    const money = (value) => `${Math.round(Number(value) || 0).toLocaleString('vi-VN')} đ`;
    const km = (value) => `${Number(value || 0).toLocaleString('vi-VN', { maximumFractionDigits: 2 })} km`;
    const tierDisplayUpperBound = (tier, isLastTier = false) => {
        const fromKm = Number(tier?.from_km || 0);
        const toKm = Number(tier?.to_km || 0);

        if (isLastTier) {
            return toKm;
        }

        return Math.max(fromKm, Number((toKm - 0.01).toFixed(2)));
    };
    const formatTierRange = (tier, isLastTier = false) => `${km(tier?.from_km)} - ${km(tierDisplayUpperBound(tier, isLastTier))}`;
    const formatCoordinate = (value) => Number(value || 0).toLocaleString('vi-VN', {
        minimumFractionDigits: 5,
        maximumFractionDigits: 5,
    });

    const setStatus = (message, tone = 'info') => {
        refs.statusChip.textContent = message;
        refs.statusChip.dataset.tone = tone;
    };

    const setCoverageFallback = (message = '', isVisible = false) => {
        if (!refs.coverageFallback) {
            return;
        }

        refs.coverageFallback.textContent = message;
        refs.coverageFallback.style.display = isVisible ? 'flex' : 'none';
    };

    const buildCoverageMarkerIcon = () => {
        if (!window.L) {
            return null;
        }

        return window.L.divIcon({
            className: 'tfc-coverage-marker-shell',
            html: `
                <span class="tfc-coverage-marker" aria-label="Cua hang">
                    <span class="material-symbols-outlined tfc-coverage-marker__icon" aria-hidden="true">storefront</span>
                </span>
            `,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
        });
    };

    const ensureCoverageMap = (lat, lng) => {
        if (!refs.coverageMapCanvas || !window.L) {
            return null;
        }

        if (!state.coverageMap.map) {
            state.coverageMap.map = window.L.map(refs.coverageMapCanvas, {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: true,
                dragging: true,
                touchZoom: true,
                doubleClickZoom: true,
                boxZoom: true,
                keyboard: true,
                zoomSnap: 0.25,
            });

            state.coverageMap.tileLayer = window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(state.coverageMap.map);
        }

        if (!state.coverageMap.marker) {
            state.coverageMap.marker = window.L.marker([lat, lng], {
                icon: buildCoverageMarkerIcon(),
            }).addTo(state.coverageMap.map);
        }

        if (!state.coverageMap.circle) {
            state.coverageMap.circle = window.L.circle([lat, lng], {
                radius: 0,
                color: '#0f6adb',
                weight: 2,
                opacity: 0.95,
                fillColor: '#56c4ff',
                fillOpacity: 0.2,
            }).addTo(state.coverageMap.map);
        }

        return state.coverageMap.map;
    };

    const updateCoverageMap = (config) => {
        const latitude = Number(config.store_latitude ?? state.legacyConfig.store_latitude);
        const longitude = Number(config.store_longitude ?? state.legacyConfig.store_longitude);
        const radiusKm = Math.max(0, Number(config.max_service_distance_km ?? state.legacyConfig.max_service_distance_km ?? 0));
        const hasFiniteCoordinates = Number.isFinite(latitude) && Number.isFinite(longitude);
        const address = String(config.store_address || DEFAULT_VISIBLE_CONFIG.store_address || '').trim() || 'Dia chi cua hang';

        if (refs.coverageRadius) {
            refs.coverageRadius.textContent = km(radiusKm);
        }

        if (refs.coverageAddress) {
            refs.coverageAddress.textContent = address;
        }

        if (refs.coverageCoordinates) {
            refs.coverageCoordinates.textContent = hasFiniteCoordinates
                ? `${formatCoordinate(latitude)}, ${formatCoordinate(longitude)}`
                : 'Lat --, Lng --';
        }

        if (refs.coverageHint) {
            refs.coverageHint.textContent = hasFiniteCoordinates
                ? `Vong tron dang mo phong pham vi sua tai nha trong ${km(radiusKm)} quanh cua hang.`
                : 'Nhap day du vi do va kinh do de xem ban do vung phuc vu.';
        }

        if (!refs.coverageMapCanvas) {
            return;
        }

        if (!window.L) {
            setCoverageFallback('Khong tai duoc thu vien ban do OpenStreetMap.', true);
            return;
        }

        if (!hasFiniteCoordinates) {
            setCoverageFallback('Chua co toa do hop le de hien thi tam ban do.', true);
            return;
        }

        const map = ensureCoverageMap(latitude, longitude);
        if (!map || !state.coverageMap.marker || !state.coverageMap.circle) {
            setCoverageFallback('Khong khoi tao duoc ban do.', true);
            return;
        }

        const existingTooltip = state.coverageMap.marker.getTooltip();
        if (existingTooltip) {
            existingTooltip.setContent(address);
        } else {
            state.coverageMap.marker.bindTooltip(address, {
                direction: 'top',
                offset: [0, -20],
                opacity: 1,
                className: 'tfc-coverage-tooltip',
            });
        }

        setCoverageFallback('', false);

        const latLng = [latitude, longitude];
        const radiusMeters = Math.max(0, radiusKm * 1000);
        state.coverageMap.marker.setLatLng(latLng);
        state.coverageMap.circle.setLatLng(latLng);
        state.coverageMap.circle.setRadius(radiusMeters);

        if (radiusMeters > 0) {
            map.fitBounds(window.L.latLng(latitude, longitude).toBounds(Math.max(radiusMeters * 2.4, 800)).pad(0.15), {
                animate: false,
                padding: [24, 24],
            });
        } else {
            map.setView(latLng, 15, { animate: false });
        }

        window.setTimeout(() => {
            map.invalidateSize();
        }, 0);
    };

    const setUpdatedMeta = (payload = {}) => {
        if (!payload.updated_at && !payload.updated_by) {
            refs.updatedChip.classList.add('d-none');
            refs.updatedChip.textContent = '';
            return;
        }

        const updatedAt = payload.updated_at ? new Date(payload.updated_at).toLocaleString('vi-VN') : '--';
        refs.updatedChip.textContent = `Cập nhật bởi ${payload.updated_by || 'hệ thống'} lúc ${updatedAt}`;
        refs.updatedChip.classList.remove('d-none');
    };

    const readRows = () => Array.from(refs.tierList.querySelectorAll('[data-tier-row]')).map((row, index) => ({
        index,
        from_km: row.querySelector('[data-tier-from]')?.value ?? '',
        to_km: row.querySelector('[data-tier-to]')?.value ?? '',
        transport_fee: row.querySelector('[data-tier-transport-fee]')?.value ?? '',
        travel_fee: row.querySelector('[data-tier-travel-fee]')?.value ?? '',
    }));

    const readBookingSlots = () => Array.from(refs.bookingSlotList?.querySelectorAll('[data-booking-slot-row]') || []).map((row, index) => ({
        index,
        start: row.querySelector('[data-booking-slot-start]')?.value ?? '',
        end: row.querySelector('[data-booking-slot-end]')?.value ?? '',
    }));

    const isEmptyRow = (row) => [row.from_km, row.to_km, row.transport_fee, row.travel_fee]
        .every((value) => String(value ?? '').trim() === '');

    const isEmptyBookingSlotRow = (row) => [row.start, row.end]
        .every((value) => String(value ?? '').trim() === '');

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

    const normalizeBookingSlotValue = (start, end) => {
        const normalizedStart = String(start || '').trim();
        const normalizedEnd = String(end || '').trim();
        return normalizedStart && normalizedEnd ? `${normalizedStart}-${normalizedEnd}` : '';
    };

    const getFormState = () => ({
        store_address: String(refs.storeAddressInput.value || '').trim(),
        store_latitude: refs.storeLatitudeInput?.value ?? '',
        store_longitude: refs.storeLongitudeInput?.value ?? '',
        store_hotline: String(refs.storeHotlineInput?.value || '').trim(),
        booking_time_slots: readBookingSlots(),
        max_service_distance_km: refs.maxServiceDistanceInput?.value ?? '',
        complaint_window_days: refs.complaintWindowInput?.value ?? '',
        tiers: readRows(),
    });

    const buildPreviewConfig = (rawState = getFormState()) => ({
        store_address: rawState.store_address || 'Chưa nhập địa chỉ cửa hàng',
        store_latitude: parseNumber(rawState.store_latitude) ?? state.legacyConfig.store_latitude,
        store_longitude: parseNumber(rawState.store_longitude) ?? state.legacyConfig.store_longitude,
        store_hotline: rawState.store_hotline || DEFAULT_VISIBLE_CONFIG.store_hotline,
        booking_time_slots: rawState.booking_time_slots
            .map((row) => normalizeBookingSlotValue(row.start, row.end))
            .filter(Boolean),
        max_service_distance_km: parseNumber(rawState.max_service_distance_km) ?? state.legacyConfig.max_service_distance_km,
        tiers: rawState.tiers
            .map((row) => ({
                index: row.index,
                from_km: parseNumber(row.from_km),
                to_km: parseNumber(row.to_km),
                transport_fee: parseNumber(row.transport_fee),
                travel_fee: parseNumber(row.travel_fee),
                raw: row,
            }))
            .filter((row) => !isEmptyRow(row.raw))
            .filter((row) => (
                row.from_km !== null
                && row.to_km !== null
                && row.transport_fee !== null
                && row.travel_fee !== null
                && row.from_km >= 0
                && row.to_km > row.from_km
                && row.transport_fee >= 0
                && row.travel_fee >= 0
            ))
            .sort((left, right) => left.from_km - right.from_km || left.to_km - right.to_km)
            .map(({ index, from_km, to_km, transport_fee, travel_fee }) => ({
                index,
                from_km,
                to_km,
                transport_fee,
                travel_fee,
                fee: travel_fee,
            })),
    });

    const inferLegacyConfig = (config) => {
        const firstFreeTier = config.tiers.find((tier) => tier.from_km === 0 && tier.travel_fee === 0);
        const firstPositiveTransportTier = config.tiers.find((tier) => tier.transport_fee > 0);

        return {
            ...state.legacyConfig,
            free_distance_km: firstFreeTier ? Number(firstFreeTier.to_km) : state.legacyConfig.free_distance_km,
            store_transport_fee: firstPositiveTransportTier
                ? Number(firstPositiveTransportTier.transport_fee)
                : state.legacyConfig.store_transport_fee,
        };
    };

    const validate = (rawState = getFormState()) => {
        const errors = {};
        const addError = (key, message) => {
            if (!errors[key]) {
                errors[key] = message;
            }
        };

        const storeLatitude = parseNumber(rawState.store_latitude);
        const storeLongitude = parseNumber(rawState.store_longitude);
        const maxServiceDistanceKm = parseNumber(rawState.max_service_distance_km);

        if (!rawState.store_address) {
            addError('store_address', 'Vui lòng nhập địa chỉ cửa hàng.');
        }

        if (String(rawState.store_latitude ?? '').trim() === '') {
            addError('store_latitude', 'Vui long nhap vi do cua hang.');
        } else if (storeLatitude === null) {
            addError('store_latitude', 'Vi do cua hang khong hop le.');
        } else if (storeLatitude < -90 || storeLatitude > 90) {
            addError('store_latitude', 'Vi do phai nam trong khoang -90 den 90.');
        }

        if (String(rawState.store_longitude ?? '').trim() === '') {
            addError('store_longitude', 'Vui long nhap kinh do cua hang.');
        } else if (storeLongitude === null) {
            addError('store_longitude', 'Kinh do cua hang khong hop le.');
        } else if (storeLongitude < -180 || storeLongitude > 180) {
            addError('store_longitude', 'Kinh do phai nam trong khoang -180 den 180.');
        }

        if (String(rawState.store_hotline ?? '').trim().length > 50) {
            addError('store_hotline', 'Hotline kh\u00f4ng \u0111\u01b0\u1ee3c v\u01b0\u1ee3t qu\u00e1 50 k\u00fd t\u1ef1.');
        }

        const bookingSlotRows = rawState.booking_time_slots.map((row) => ({
            ...row,
            startMinutes: timeToMinutes(row.start),
            endMinutes: timeToMinutes(row.end),
            value: normalizeBookingSlotValue(row.start, row.end),
        }));
        const validBookingSlots = [];

        bookingSlotRows.forEach((row) => {
            if (isEmptyBookingSlotRow(row)) {
                return;
            }

            if (!String(row.start || '').trim()) {
                addError(`booking_time_slots.${row.index}.start`, 'Chọn giờ bắt đầu.');
            } else if (row.startMinutes === null) {
                addError(`booking_time_slots.${row.index}.start`, 'Giờ bắt đầu không hợp lệ.');
            }

            if (!String(row.end || '').trim()) {
                addError(`booking_time_slots.${row.index}.end`, 'Chọn giờ kết thúc.');
            } else if (row.endMinutes === null) {
                addError(`booking_time_slots.${row.index}.end`, 'Giờ kết thúc không hợp lệ.');
            }

            if (row.startMinutes !== null && row.endMinutes !== null && row.endMinutes <= row.startMinutes) {
                addError(`booking_time_slots.${row.index}.end`, 'Giờ kết thúc phải lớn hơn giờ bắt đầu.');
            }

            if (!errors[`booking_time_slots.${row.index}.start`] && !errors[`booking_time_slots.${row.index}.end`]) {
                validBookingSlots.push(row);
            }
        });

        if (!validBookingSlots.length) {
            addError('booking_time_slots', 'Vui lòng cấu hình ít nhất 1 khung giờ khách có thể đặt.');
        }

        const seenBookingSlots = new Set();
        [...validBookingSlots]
            .sort((left, right) => left.startMinutes - right.startMinutes || left.endMinutes - right.endMinutes)
            .forEach((row, sortedIndex, rows) => {
                if (seenBookingSlots.has(row.value)) {
                    addError(`booking_time_slots.${row.index}.start`, 'Khung giờ này đang bị trùng.');
                    return;
                }

                seenBookingSlots.add(row.value);

                if (sortedIndex === 0) {
                    return;
                }

                const previous = rows[sortedIndex - 1];
                if (row.startMinutes < previous.endMinutes) {
                    addError(`booking_time_slots.${row.index}.start`, 'Khung giờ này đang chồng lấn với khung trước.');
                }
            });

        if (String(rawState.max_service_distance_km ?? '').trim() === '') {
            addError('max_service_distance_km', 'Vui long nhap pham vi phuc vu toi da.');
        } else if (maxServiceDistanceKm === null) {
            addError('max_service_distance_km', 'Pham vi phuc vu toi da khong hop le.');
        } else if (maxServiceDistanceKm < 0 || maxServiceDistanceKm > 1000) {
            addError('max_service_distance_km', 'Pham vi phuc vu toi da phai tu 0 den 1000 km.');
        }

        const complaintWindowDays = parseNumber(rawState.complaint_window_days);
        if (String(rawState.complaint_window_days ?? '').trim() === '') {
            addError('complaint_window_days', 'Vui long nhap so ngay khieu nai.');
        } else if (complaintWindowDays === null || !Number.isInteger(complaintWindowDays)) {
            addError('complaint_window_days', 'So ngay khieu nai phai la so nguyen.');
        } else if (complaintWindowDays < 1 || complaintWindowDays > 30) {
            addError('complaint_window_days', 'So ngay khieu nai phai tu 1 den 30.');
        }

        const rows = rawState.tiers.map((row) => ({
            ...row,
            from: parseNumber(row.from_km),
            to: parseNumber(row.to_km),
            transportFeeValue: parseNumber(row.transport_fee),
            travelFeeValue: parseNumber(row.travel_fee),
        }));

        const validRows = [];

        rows.forEach((row) => {
            if (isEmptyRow(row)) {
                return;
            }

            if (String(row.from_km).trim() === '') {
                addError(`tiers.${row.index}.from_km`, 'Nhập mốc bắt đầu.');
            } else if (row.from === null) {
                addError(`tiers.${row.index}.from_km`, 'Mốc bắt đầu không hợp lệ.');
            } else if (row.from < 0) {
                addError(`tiers.${row.index}.from_km`, 'Mốc bắt đầu không được âm.');
            }

            if (String(row.to_km).trim() === '') {
                addError(`tiers.${row.index}.to_km`, 'Nhập mốc kết thúc.');
            } else if (row.to === null) {
                addError(`tiers.${row.index}.to_km`, 'Mốc kết thúc không hợp lệ.');
            } else if (row.to < 0) {
                addError(`tiers.${row.index}.to_km`, 'Mốc kết thúc không được âm.');
            }

            if (String(row.transport_fee).trim() === '') {
                addError(`tiers.${row.index}.transport_fee`, 'Nhập phí thuê xe.');
            } else if (row.transportFeeValue === null) {
                addError(`tiers.${row.index}.transport_fee`, 'Phí thuê xe không hợp lệ.');
            } else if (row.transportFeeValue < 0) {
                addError(`tiers.${row.index}.transport_fee`, 'Phí thuê xe không được âm.');
            }

            if (String(row.travel_fee).trim() === '') {
                addError(`tiers.${row.index}.travel_fee`, 'Nhập phí đi lại.');
            } else if (row.travelFeeValue === null) {
                addError(`tiers.${row.index}.travel_fee`, 'Phí đi lại không hợp lệ.');
            } else if (row.travelFeeValue < 0) {
                addError(`tiers.${row.index}.travel_fee`, 'Phí đi lại không được âm.');
            }

            if (row.from !== null && row.to !== null && row.to <= row.from) {
                addError(`tiers.${row.index}.to_km`, 'Mốc kết thúc phải lớn hơn mốc bắt đầu.');
            }

            if (
                !errors[`tiers.${row.index}.from_km`]
                && !errors[`tiers.${row.index}.to_km`]
                && !errors[`tiers.${row.index}.transport_fee`]
                && !errors[`tiers.${row.index}.travel_fee`]
            ) {
                validRows.push(row);
            }
        });

        let previous = null;
        [...validRows]
            .sort((left, right) => left.from - right.from || left.to - right.to)
            .forEach((row) => {
                if (previous && row.from < previous.to) {
                    addError(`tiers.${row.index}.from_km`, 'Khoảng này đang chồng lên khoảng trước.');
                }

                previous = row;
            });

        const preview = buildPreviewConfig(rawState);
        const legacyConfig = inferLegacyConfig(preview);

        return {
            errors,
            preview,
            payload: {
                store_address: rawState.store_address,
                store_latitude: Number(storeLatitude ?? state.legacyConfig.store_latitude),
                store_longitude: Number(storeLongitude ?? state.legacyConfig.store_longitude),
                store_hotline: rawState.store_hotline,
                booking_time_slots: validBookingSlots
                    .filter((row) => !errors[`booking_time_slots.${row.index}.start`] && !errors[`booking_time_slots.${row.index}.end`])
                    .map((row) => row.value),
                max_service_distance_km: Number(maxServiceDistanceKm ?? state.legacyConfig.max_service_distance_km),
                free_distance_km: legacyConfig.free_distance_km,
                default_per_km: state.legacyConfig.default_per_km,
                store_transport_fee: legacyConfig.store_transport_fee,
                complaint_window_days: Number(complaintWindowDays || state.legacyConfig.complaint_window_days || 3),
                tiers: preview.tiers.map((tier) => ({
                    from_km: tier.from_km,
                    to_km: tier.to_km,
                    transport_fee: tier.transport_fee,
                    travel_fee: tier.travel_fee,
                })),
            },
            isValid: Object.keys(errors).length === 0,
        };
    };

    const setFieldError = (key, message = '') => {
        // Search both the form and the tier list (which may be a table outside the form in the DOM)
        const node = document.querySelector(`[data-error-for="${key}"]`);
        if (node) {
            node.textContent = message;
        }
    };

    const toggleInvalid = (input, isInvalid) => {
        if (!input) {
            return;
        }

        input.classList.toggle('is-invalid', isInvalid);
        if (isInvalid) {
            input.style.borderColor = '#ba1a1a';
            input.style.borderWidth = '1.5px';
        } else {
            input.style.borderColor = '';
            input.style.borderWidth = '';
        }
    };

    const applyErrors = (errors = {}) => {
        setFieldError('store_address', errors.store_address || '');
        toggleInvalid(refs.storeAddressInput, Boolean(errors.store_address));
        setFieldError('store_latitude', errors.store_latitude || '');
        toggleInvalid(refs.storeLatitudeInput, Boolean(errors.store_latitude));
        setFieldError('store_longitude', errors.store_longitude || '');
        toggleInvalid(refs.storeLongitudeInput, Boolean(errors.store_longitude));
        setFieldError('store_hotline', errors.store_hotline || '');
        toggleInvalid(refs.storeHotlineInput, Boolean(errors.store_hotline));
        setFieldError('booking_time_slots', errors.booking_time_slots || '');
        setFieldError('max_service_distance_km', errors.max_service_distance_km || '');
        toggleInvalid(refs.maxServiceDistanceInput, Boolean(errors.max_service_distance_km));
        setFieldError('complaint_window_days', errors.complaint_window_days || '');
        toggleInvalid(refs.complaintWindowInput, Boolean(errors.complaint_window_days));

        refs.bookingSlotList?.querySelectorAll('[data-booking-slot-row]').forEach((row) => {
            const index = Number(row.dataset.bookingSlotIndex);
            [
                ['start', '[data-booking-slot-start]'],
                ['end', '[data-booking-slot-end]'],
            ].forEach(([field, selector]) => {
                const key = `booking_time_slots.${index}.${field}`;
                setFieldError(key, errors[key] || '');
                toggleInvalid(row.querySelector(selector), Boolean(errors[key]));
            });
        });

        refs.tierList.querySelectorAll('[data-tier-row]').forEach((row) => {
            const index = Number(row.dataset.tierIndex);
            [
                ['from_km', '[data-tier-from]'],
                ['to_km', '[data-tier-to]'],
                ['transport_fee', '[data-tier-transport-fee]'],
                ['travel_fee', '[data-tier-travel-fee]'],
            ].forEach(([field, selector]) => {
                const key = `tiers.${index}.${field}`;
                setFieldError(key, errors[key] || '');
                toggleInvalid(row.querySelector(selector), Boolean(errors[key]));
            });
        });
    };

    const createBookingSlotRowMarkup = (slot = {}, index = 0) => `
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-[1fr_1fr_auto] tw-gap-3 tw-items-start tw-p-4 tw-rounded-2xl tw-bg-surface-container-low tw-border tw-border-outline-variant/60" data-booking-slot-row data-booking-slot-index="${index}">
            <div class="tw-space-y-1">
                <label class="tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Bắt đầu</label>
                <input
                    type="time"
                    value="${escapeHtml(slot.start ?? '')}"
                    data-booking-slot-start
                    class="tw-w-full tw-bg-surface-container-lowest tw-border tw-border-outline-variant tw-rounded-xl tw-px-3 tw-py-3 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-primary/30"
                />
                <div class="tfc-field-error" data-error-for="booking_time_slots.${index}.start"></div>
            </div>
            <div class="tw-space-y-1">
                <label class="tw-text-[10px] tw-font-bold tw-uppercase tw-tracking-widest tw-text-slate-500">Kết thúc</label>
                <input
                    type="time"
                    value="${escapeHtml(slot.end ?? '')}"
                    data-booking-slot-end
                    class="tw-w-full tw-bg-surface-container-lowest tw-border tw-border-outline-variant tw-rounded-xl tw-px-3 tw-py-3 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-primary/30"
                />
                <div class="tfc-field-error" data-error-for="booking_time_slots.${index}.end"></div>
            </div>
            <div class="tw-flex md:tw-pt-6">
                <button type="button" data-remove-booking-slot aria-label="Xóa khung giờ"
                    class="tw-inline-flex tw-items-center tw-justify-center tw-h-[48px] tw-w-[48px] tw-rounded-full tw-text-error/70 hover:tw-text-error hover:tw-bg-error-container/20 tw-transition-colors">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
        </div>
    `;

    const renderBookingSlotRows = (slots = []) => {
        const normalizedSlots = (slots.length ? slots : DEFAULT_VISIBLE_CONFIG.booking_time_slots)
            .map((slot) => {
                if (typeof slot === 'string') {
                    const [start = '', end = ''] = String(slot).split('-', 2);
                    return { start, end };
                }

                return {
                    start: slot.start ?? '',
                    end: slot.end ?? '',
                };
            });

        refs.bookingSlotList.innerHTML = normalizedSlots
            .map((slot, index) => createBookingSlotRowMarkup(slot, index))
            .join('');
    };

    const createTierRowMarkup = (tier = {}, index = 0) => `
        <tr class="hover:tw-bg-surface-bright tw-transition-colors group" data-tier-row data-tier-index="${index}">
            <td class="tw-px-4 tw-py-3">
                <input
                    id="travelTierFrom${index}"
                    type="number" min="0" step="0.1"
                    data-tier-from
                    value="${escapeHtml(tier.from_km ?? '')}"
                    placeholder="0"
                    class="tw-w-full tw-bg-surface-container-low tw-border tw-border-outline-variant tw-rounded-xl tw-px-3 tw-py-2 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-primary/30"
                />
                <div class="tfc-field-error" data-error-for="tiers.${index}.from_km"></div>
            </td>
            <td class="tw-px-4 tw-py-3">
                <input
                    id="travelTierTo${index}"
                    type="number" min="0" step="0.1"
                    data-tier-to
                    value="${escapeHtml(tier.to_km ?? '')}"
                    placeholder="1"
                    class="tw-w-full tw-bg-surface-container-low tw-border tw-border-outline-variant tw-rounded-xl tw-px-3 tw-py-2 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-primary/30"
                />
                <div class="tfc-field-error" data-error-for="tiers.${index}.to_km"></div>
            </td>
            <td class="tw-px-4 tw-py-3">
                <input
                    id="travelTierTransportFee${index}"
                    type="number" min="0" step="1000"
                    data-tier-transport-fee
                    value="${escapeHtml(tier.transport_fee ?? '')}"
                    placeholder="50000"
                    class="tw-w-full tw-bg-surface-container-low tw-border tw-border-outline-variant tw-rounded-xl tw-px-3 tw-py-2 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-primary/30"
                />
                <div class="tfc-field-error" data-error-for="tiers.${index}.transport_fee"></div>
            </td>
            <td class="tw-px-4 tw-py-3">
                <input
                    id="travelTierTravelFee${index}"
                    type="number" min="0" step="1000"
                    data-tier-travel-fee
                    value="${escapeHtml(tier.travel_fee ?? tier.fee ?? '')}"
                    placeholder="17000"
                    class="tw-w-full tw-bg-surface-container-low tw-border tw-border-outline-variant tw-rounded-xl tw-px-3 tw-py-2 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-primary/30"
                />
                <div class="tfc-field-error" data-error-for="tiers.${index}.travel_fee"></div>
            </td>
            <td class="tw-px-4 tw-py-3 tw-text-center">
                <button type="button" data-remove-tier aria-label="Xóa khoảng"
                    class="tw-text-error/70 hover:tw-text-error hover:tw-bg-error-container/20 tw-p-2 tw-rounded-full tw-transition-colors">
                    <span class="material-symbols-outlined tw-text-xl">delete</span>
                </button>
            </td>
        </tr>
    `;

    const renderRows = (tiers = []) => {
        refs.tierList.innerHTML = (tiers.length ? tiers : DEFAULT_VISIBLE_CONFIG.tiers)
            .map((tier, index) => createTierRowMarkup(tier, index))
            .join('');
    };

    const matchTier = (distanceKm, config) => config.tiers.find((tier, index, tiers) => (
        distanceKm >= tier.from_km
        && (
            distanceKm < tier.to_km
            || (index === tiers.length - 1 && distanceKm <= tier.to_km)
        )
    )) || null;

    const resolveActivePricing = (distanceKm, config) => {
        const normalizedDistance = Math.max(0, roundDistance(distanceKm));
        const tier = matchTier(normalizedDistance, config);
        const tierIndex = tier ? config.tiers.indexOf(tier) : -1;
        const isLastTier = tierIndex === config.tiers.length - 1;

        if (tier) {
            return {
                kind: 'tier',
                label: formatTierRange(tier, isLastTier),
                travelFee: tier.travel_fee,
                transportFee: tier.transport_fee,
                tierIndex,
                copy: `${km(normalizedDistance)} nằm trong khoảng ${formatTierRange(tier, isLastTier)}, áp dụng phí đi lại ${money(tier.travel_fee)} và phí thuê xe ${money(tier.transport_fee)}.`,
            };
        }

        const fallbackTravelFee = state.previewMode === 'travel_fee'
            ? Math.round(normalizedDistance * Number(state.legacyConfig.default_per_km || 0))
            : 0;

        return {
            kind: fallbackTravelFee > 0 ? 'fallback' : 'none',
            label: fallbackTravelFee > 0 ? 'Fallback phí đi lại' : 'Chưa có khoảng phù hợp',
            travelFee: fallbackTravelFee,
            transportFee: Number(state.legacyConfig.store_transport_fee || 0),
            tierIndex: null,
            copy: fallbackTravelFee > 0
                ? `${km(normalizedDistance)} chưa rơi vào khoảng nào, hệ thống đang mô phỏng fallback theo cấu hình cũ ${money(state.legacyConfig.default_per_km)} / km.`
                : `${km(normalizedDistance)} chưa thuộc khoảng nào trong bảng. Hãy thêm hoặc mở rộng một khoảng để áp dụng giá.`,
        };
    };

    const syncModeUI = () => {
        refs.modeButtons.forEach((button) => {
            const isActive = button.dataset.previewMode === state.previewMode;
            button.classList.toggle('is-active', isActive);
            // Stitch toggle: active = white bg, inactive = transparent+opacity
            if (button.parentElement?.className?.includes('tw-bg-white/10')) {
                button.className = isActive
                    ? 'tw-px-3 tw-py-1 tw-text-[10px] tw-font-bold tw-bg-white tw-text-primary tw-rounded-full'
                    : 'tw-px-3 tw-py-1 tw-text-[10px] tw-font-bold tw-text-white tw-opacity-60';
            }
        });

        if (refs.modeChip) {
            refs.modeChip.textContent = state.previewMode === 'tiered' ? 'Bậc khoảng cách' : 'Phí đi lại';
            refs.modeChip.dataset.tone = state.previewMode === 'tiered' ? 'success' : 'info';
        }
    };

    const syncDistanceControls = (config) => {
        const lastTier = config.tiers[config.tiers.length - 1];
        const maxDistance = Math.max(20, lastTier ? Number(lastTier.to_km) + 6 : 0, state.activeDistanceKm + 2);

        state.activeDistanceKm = clamp(roundDistance(state.activeDistanceKm), 0, maxDistance);
        refs.slider.max = String(maxDistance);
        refs.distanceInput && (refs.distanceInput.max = String(maxDistance));
        refs.slider.value = String(state.activeDistanceKm);
        refs.distanceInput && (refs.distanceInput.value = String(state.activeDistanceKm));
        // distanceBadge contains a child <span>km</span>, update only the text node
        if (refs.distanceBadge) {
            const textNode = [...refs.distanceBadge.childNodes].find(n => n.nodeType === Node.TEXT_NODE);
            if (textNode) {
                textNode.textContent = `${Number(state.activeDistanceKm).toLocaleString('vi-VN', { maximumFractionDigits: 2 })} `;
            } else {
                refs.distanceBadge.textContent = km(state.activeDistanceKm);
            }
        }
    };

    const buildRuleItems = (config, activePricing) => config.tiers.map((tier, index, tiers) => ({
        title: formatTierRange(tier, index === tiers.length - 1),
        meta: `Phí thuê xe ${money(tier.transport_fee)} · Phí đi lại ${money(tier.travel_fee)}`,
        transportFee: tier.transport_fee,
        travelFee: tier.travel_fee,
        distance: roundDistance((Number(tier.from_km || 0) + tierDisplayUpperBound(tier, index === tiers.length - 1)) / 2),
        isActive: activePricing.kind === 'tier' && activePricing.tierIndex === index,
    }));

    const getSampleDistances = (config) => {
        const distances = config.tiers.flatMap((tier, index, tiers) => ([
            roundDistance(Math.max(tier.from_km, 0)),
            roundDistance((Number(tier.from_km || 0) + tierDisplayUpperBound(tier, index === tiers.length - 1)) / 2),
        ]));

        if (!distances.length) {
            return [0.5, 3, 8, 15];
        }

        return [...new Set(distances.filter((value) => Number.isFinite(value) && value >= 0))]
            .sort((left, right) => left - right)
            .slice(0, 6);
    };

    const highlightRows = (activePricing) => {
        refs.tierList.querySelectorAll('[data-tier-row]').forEach((row) => {
            row.classList.toggle(
                'is-active',
                activePricing.kind === 'tier' && Number(row.dataset.tierIndex) === activePricing.tierIndex
            );
        });
    };

    const renderPreview = () => {
        const config = validate(getFormState()).preview;
        syncDistanceControls(config);
        syncModeUI();

        if (refs.storeAddressPreview) refs.storeAddressPreview.textContent = config.store_address;
        updateCoverageMap(config);

        const activePricing = resolveActivePricing(state.activeDistanceKm, config);
        if (refs.transportPreview) refs.transportPreview.textContent = money(activePricing.transportFee);
        if (refs.travelPreview) refs.travelPreview.textContent = money(activePricing.travelFee);
        if (refs.rangePreview) refs.rangePreview.textContent = activePricing.label;
        if (refs.activeRuleLabel) refs.activeRuleLabel.textContent = state.previewMode === 'tiered' ? 'Bậc đang áp dụng' : 'Phí đi lại đang áp dụng';
        if (refs.activePrice) refs.activePrice.textContent = money(activePricing.travelFee);
        if (refs.activeTransportMeta) refs.activeTransportMeta.textContent = `Phí thuê xe: ${money(activePricing.transportFee)}`;
        if (refs.activeRuleCopy) refs.activeRuleCopy.textContent = activePricing.copy;

        const ruleItems = buildRuleItems(config, activePricing);
        if (refs.rulePreview) {
            refs.rulePreview.innerHTML = ruleItems.length
                ? ruleItems.map((item) => `
                    <button type="button" class="travel-rule-item ${item.isActive ? 'is-active' : ''}" data-preview-distance="${item.distance}">
                        <div>
                            <p class="travel-rule-item__title">${escapeHtml(item.title)}</p>
                            <p class="travel-rule-item__meta">${escapeHtml(item.meta)}</p>
                        </div>
                        <div class="travel-rule-item__values">
                            <span class="travel-price-badge">Thuê xe ${escapeHtml(money(item.transportFee))}</span>
                            <span class="travel-price-badge travel-price-badge--primary">Đi lại ${escapeHtml(money(item.travelFee))}</span>
                        </div>
                    </button>
                `).join('')
                : '<div class="travel-empty-state">Chưa có khoảng nào được cấu hình.</div>';
        }

        if (refs.sampleGrid) {
            refs.sampleGrid.innerHTML = getSampleDistances(config).map((distance) => {
                const samplePricing = resolveActivePricing(distance, config);
                const isActive = roundDistance(distance) === roundDistance(state.activeDistanceKm);
                return `
                    <button type="button" class="travel-sample-card ${isActive ? 'is-active' : ''}" data-preview-distance="${distance}">
                        <span class="travel-sample-card__distance">${escapeHtml(km(distance))}</span>
                        <span class="travel-sample-card__price">${escapeHtml(money(samplePricing.travelFee))}</span>
                        <span class="travel-sample-card__sub">Thuê xe ${escapeHtml(money(samplePricing.transportFee))}</span>
                        <span class="travel-sample-card__note">${escapeHtml(samplePricing.label)}</span>
                    </button>
                `;
            }).join('');
        }

        highlightRows(activePricing);
    };

    const refresh = () => {
        const result = validate(getFormState());
        applyErrors(result.errors);
        renderPreview();
        return result;
    };

    const fill = (config = {}, preview = null) => {
        const visibleConfig = {
            store_address: config.store_address || DEFAULT_VISIBLE_CONFIG.store_address,
            store_latitude: Number(config.store_latitude ?? DEFAULT_VISIBLE_CONFIG.store_latitude),
            store_longitude: Number(config.store_longitude ?? DEFAULT_VISIBLE_CONFIG.store_longitude),
            store_hotline: config.store_hotline ?? DEFAULT_VISIBLE_CONFIG.store_hotline,
            booking_time_slots: Array.isArray(config.booking_time_slots) && config.booking_time_slots.length
                ? config.booking_time_slots
                : DEFAULT_VISIBLE_CONFIG.booking_time_slots,
            max_service_distance_km: Number(config.max_service_distance_km ?? DEFAULT_VISIBLE_CONFIG.max_service_distance_km),
            tiers: Array.isArray(config.tiers) && config.tiers.length
                ? config.tiers.map((tier) => ({
                    from_km: tier.from_km,
                    to_km: tier.to_km,
                    transport_fee: tier.transport_fee ?? 0,
                    travel_fee: tier.travel_fee ?? tier.fee ?? 0,
                }))
                : DEFAULT_VISIBLE_CONFIG.tiers,
        };

        refs.storeAddressInput.value = visibleConfig.store_address;
        if (refs.storeLatitudeInput) {
            refs.storeLatitudeInput.value = String(visibleConfig.store_latitude);
        }
        if (refs.storeLongitudeInput) {
            refs.storeLongitudeInput.value = String(visibleConfig.store_longitude);
        }
        if (refs.storeHotlineInput) {
            refs.storeHotlineInput.value = String(visibleConfig.store_hotline ?? '');
        }
        if (refs.maxServiceDistanceInput) {
            refs.maxServiceDistanceInput.value = String(visibleConfig.max_service_distance_km);
        }
        if (refs.complaintWindowInput) {
            refs.complaintWindowInput.value = String(config.complaint_window_days ?? DEFAULT_LEGACY_CONFIG.complaint_window_days);
        }
        renderBookingSlotRows(visibleConfig.booking_time_slots);
        renderRows(visibleConfig.tiers);

        state.legacyConfig = {
            free_distance_km: Number(config.free_distance_km ?? DEFAULT_LEGACY_CONFIG.free_distance_km),
            max_service_distance_km: Number(config.max_service_distance_km ?? DEFAULT_LEGACY_CONFIG.max_service_distance_km),
            default_per_km: Number(config.default_per_km ?? DEFAULT_LEGACY_CONFIG.default_per_km),
            store_latitude: Number(config.store_latitude ?? DEFAULT_LEGACY_CONFIG.store_latitude),
            store_longitude: Number(config.store_longitude ?? DEFAULT_LEGACY_CONFIG.store_longitude),
            store_transport_fee: Number(config.store_transport_fee ?? preview?.store?.transport_fee ?? DEFAULT_LEGACY_CONFIG.store_transport_fee),
            complaint_window_days: Number(config.complaint_window_days ?? DEFAULT_LEGACY_CONFIG.complaint_window_days),
        };
    };

    const reset = () => {
        fill(DEFAULT_VISIBLE_CONFIG);
        state.previewMode = 'tiered';
        state.activeDistanceKm = 3;
        state.legacyConfig = { ...DEFAULT_LEGACY_CONFIG };
        refresh();
    };

    const setSaving = (isSaving) => {
        if (refs.saveButton) refs.saveButton.disabled = isSaving;
        if (refs.addTierButton) refs.addTierButton.disabled = isSaving;
        if (refs.addBookingSlotButton) refs.addBookingSlotButton.disabled = isSaving;
        if (refs.resetButton) refs.resetButton.disabled = isSaving;
        if (refs.saveButton) {
            refs.saveButton.innerHTML = isSaving
                ? '<span class="material-symbols-outlined tw-text-sm tw-animate-spin">sync</span> Đang lưu...'
                : 'Lưu thay đổi';
        }
    };

    const load = async () => {
        setStatus('Đang tải cấu hình...', 'info');

        try {
            const response = await callApi('/admin/travel-fee-config');
            if (!response.ok) {
                throw new Error(response.data?.message || 'Không tải được cấu hình phí vận chuyển.');
            }

            const payload = response.data?.data || {};
            fill(payload.config || DEFAULT_VISIBLE_CONFIG, payload.preview || null);
            setUpdatedMeta(payload);
            state.previewMode = Array.isArray(payload.config?.tiers) && payload.config.tiers.length ? 'tiered' : 'travel_fee';
            state.activeDistanceKm = payload.config?.tiers?.length
                ? roundDistance((Number(payload.config.tiers[0].from_km || 0) + Number(payload.config.tiers[0].to_km || 0)) / 2)
                : 3;
            refresh();
            setStatus(payload.has_override ? 'Đang dùng cấu hình tùy chỉnh' : 'Đang dùng cấu hình mặc định', 'success');
        } catch (error) {
            reset();
            setStatus('Tải cấu hình thất bại', 'danger');
            showToast(error.message || 'Không tải được cấu hình phí vận chuyển.', 'error');
        }
    };

    refs.addTierButton.addEventListener('click', () => {
        const rows = readRows().map(({ from_km, to_km, transport_fee, travel_fee }) => ({
            from_km,
            to_km,
            transport_fee,
            travel_fee,
        }));
        rows.push({ from_km: '', to_km: '', transport_fee: '', travel_fee: '' });
        renderRows(rows);
        refresh();
    });

    refs.addBookingSlotButton?.addEventListener('click', () => {
        const slots = readBookingSlots().map(({ start, end }) => ({ start, end }));
        slots.push({ start: '', end: '' });
        renderBookingSlotRows(slots);
        refresh();
    });

    refs.resetButton.addEventListener('click', () => {
        reset();
        setStatus('Đã đưa biểu mẫu về bộ khoảng mặc định cục bộ. Bấm Lưu cấu hình để áp dụng.', 'info');
    });

    refs.form.addEventListener('input', (event) => {
        if (event.target === refs.slider || event.target === refs.distanceInput) {
            return;
        }

        refresh();
    });

    refs.tierList.addEventListener('focusin', (event) => {
        const row = event.target.closest('[data-tier-row]');
        if (!row) {
            return;
        }

        const fromKm = parseNumber(row.querySelector('[data-tier-from]')?.value);
        const toKm = parseNumber(row.querySelector('[data-tier-to]')?.value);

        if (fromKm !== null && toKm !== null && toKm > fromKm) {
            state.previewMode = 'tiered';
            state.activeDistanceKm = roundDistance((fromKm + toKm) / 2);
            renderPreview();
        }
    });

    refs.tierList.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-tier]');
        if (!removeButton) {
            return;
        }

        const rows = readRows().map(({ from_km, to_km, transport_fee, travel_fee }) => ({
            from_km,
            to_km,
            transport_fee,
            travel_fee,
        }));
        const index = Number(removeButton.closest('[data-tier-row]')?.dataset.tierIndex ?? -1);
        rows.splice(index, 1);
        renderRows(rows.length ? rows : DEFAULT_VISIBLE_CONFIG.tiers);
        refresh();
    });

    refs.bookingSlotList?.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-booking-slot]');
        if (!removeButton) {
            return;
        }

        const slots = readBookingSlots().map(({ start, end }) => ({ start, end }));
        const index = Number(removeButton.closest('[data-booking-slot-row]')?.dataset.bookingSlotIndex ?? -1);
        slots.splice(index, 1);
        renderBookingSlotRows(slots.length ? slots : [{ start: '', end: '' }]);
        refresh();
    });

    refs.modeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            state.previewMode = button.dataset.previewMode || 'travel_fee';
            renderPreview();
        });
    });

    const changeDistance = (value) => {
        const parsedValue = parseNumber(value);
        if (parsedValue === null) {
            return;
        }

        state.activeDistanceKm = Math.max(0, roundDistance(parsedValue));
        renderPreview();
    };

    refs.slider.addEventListener('input', (event) => changeDistance(event.target.value));
    refs.distanceInput.addEventListener('input', (event) => changeDistance(event.target.value));

    [refs.rulePreview, refs.sampleGrid].forEach((container) => {
        container.addEventListener('click', (event) => {
            const button = event.target.closest('[data-preview-distance]');
            if (!button) {
                return;
            }

            state.activeDistanceKm = Math.max(0, roundDistance(button.dataset.previewDistance));
            renderPreview();
        });
    });

    refs.form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const result = refresh();
        if (!result.isValid) {
            setStatus('Biểu mẫu còn lỗi, vui lòng kiểm tra lại các trường màu đỏ.', 'danger');
            showToast('Vui lòng kiểm tra lại các trường đang báo lỗi.', 'error');
            return;
        }

        setSaving(true);

        try {
            const response = await callApi('/admin/travel-fee-config', 'PUT', result.payload);
            if (!response.ok) {
                if (response.status === 422 && response.data?.errors) {
                    const serverErrors = Object.fromEntries(
                        Object.entries(response.data.errors).map(([key, value]) => [
                            key,
                            Array.isArray(value) ? value[0] : String(value),
                        ])
                    );
                    applyErrors(serverErrors);
                    setStatus('Biểu mẫu còn lỗi từ phía máy chủ.', 'danger');
                    showToast(response.data?.message || 'Dữ liệu chưa hợp lệ.', 'error');
                    return;
                }

                throw new Error(response.data?.message || 'Không lưu được cấu hình phí vận chuyển.');
            }

            const payload = response.data?.data || {};
            fill(payload.config || DEFAULT_VISIBLE_CONFIG, payload.preview || null);
            setUpdatedMeta(payload);
            state.previewMode = Array.isArray(payload.config?.tiers) && payload.config.tiers.length ? 'tiered' : 'travel_fee';
            refresh();
            setStatus('Đã lưu cấu hình phí vận chuyển.', 'success');
            showToast(response.data?.message || 'Đã cập nhật cấu hình.');
        } catch (error) {
            setStatus('Lưu cấu hình thất bại', 'danger');
            showToast(error.message || 'Không lưu được cấu hình phí vận chuyển.', 'error');
        } finally {
            setSaving(false);
        }
    });

    reset();
    load();
});
