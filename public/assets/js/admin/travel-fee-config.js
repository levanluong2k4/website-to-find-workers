import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const DEFAULT_VISIBLE_CONFIG = {
        store_address: '2 \u0110\u01b0\u1eddng Nguy\u1ec5n \u0110\u00ecnh Chi\u1ec3u, V\u0129nh Th\u1ecd, Nha Trang, Kh\u00e1nh H\u00f2a',
        store_hotline: '0905 123 456',
        store_opening_hours: 'Th\u1ee9 2 - CN: 07:00 - 20:00',
        tiers: [
            { from_km: 0, to_km: 1, transport_fee: 0, travel_fee: 0 },
            { from_km: 1, to_km: 5, transport_fee: 50000, travel_fee: 17000 },
        ],
    };

    const DEFAULT_LEGACY_CONFIG = {
        free_distance_km: 1,
        default_per_km: 5000,
        store_transport_fee: 0,
        complaint_window_days: 3,
    };

    const state = {
        previewMode: 'tiered',
        activeDistanceKm: 3,
        legacyConfig: { ...DEFAULT_LEGACY_CONFIG },
    };

    const refs = {
        form: document.getElementById('travelFeeForm'),
        tierList: document.getElementById('travelTierList'),
        storeAddressInput: document.getElementById('travelFeeStoreAddress'),
        storeHotlineInput: document.getElementById('travelFeeStoreHotline'),
        storeOpeningHoursInput: document.getElementById('travelFeeStoreOpeningHours'),
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
    };

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
        syncFieldCopy(refs.storeHotlineInput, {
            label: 'Hotline c\u1eeda h\u00e0ng',
            placeholder: 'VD: 0905 123 456',
            helper: 'Chatbot v\u00e0 trang kh\u00e1ch s\u1ebd d\u00f9ng s\u1ed1 n\u00e0y khi h\u1ecfi hotline.',
        });
        syncFieldCopy(refs.storeOpeningHoursInput, {
            label: 'Khung gi\u1edd m\u1edf c\u1eeda',
            placeholder: 'VD: Th\u1ee9 2 - CN: 07:00 - 20:00',
            helper: 'D\u00f9ng cho chatbot v\u00e0 c\u00e1c th\u00f4ng b\u00e1o gi\u1edd nh\u1eadn \u0111\u01a1n t\u1ea1i c\u1eeda h\u00e0ng.',
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

    const setStatus = (message, tone = 'info') => {
        refs.statusChip.textContent = message;
        refs.statusChip.dataset.tone = tone;
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

    const isEmptyRow = (row) => [row.from_km, row.to_km, row.transport_fee, row.travel_fee]
        .every((value) => String(value ?? '').trim() === '');

    const getFormState = () => ({
        store_address: String(refs.storeAddressInput.value || '').trim(),
        store_hotline: String(refs.storeHotlineInput?.value || '').trim(),
        store_opening_hours: String(refs.storeOpeningHoursInput?.value || '').trim(),
        complaint_window_days: refs.complaintWindowInput?.value ?? '',
        tiers: readRows(),
    });

    const buildPreviewConfig = (rawState = getFormState()) => ({
        store_address: rawState.store_address || 'Chưa nhập địa chỉ cửa hàng',
        store_hotline: rawState.store_hotline || DEFAULT_VISIBLE_CONFIG.store_hotline,
        store_opening_hours: rawState.store_opening_hours || DEFAULT_VISIBLE_CONFIG.store_opening_hours,
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

        if (!rawState.store_address) {
            addError('store_address', 'Vui lòng nhập địa chỉ cửa hàng.');
        }

        if (String(rawState.store_hotline ?? '').trim().length > 50) {
            addError('store_hotline', 'Hotline kh\u00f4ng \u0111\u01b0\u1ee3c v\u01b0\u1ee3t qu\u00e1 50 k\u00fd t\u1ef1.');
        }

        if (String(rawState.store_opening_hours ?? '').trim().length > 100) {
            addError('store_opening_hours', 'Khung gi\u1edd m\u1edf c\u1eeda kh\u00f4ng \u0111\u01b0\u1ee3c v\u01b0\u1ee3t qu\u00e1 100 k\u00fd t\u1ef1.');
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
                if (previous && row.from <= previous.to) {
                    addError(`tiers.${row.index}.from_km`, 'Khoảng này đang chồng lên hoặc chạm điểm cuối của dòng trước.');
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
                store_hotline: rawState.store_hotline,
                store_opening_hours: rawState.store_opening_hours,
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
        setFieldError('store_hotline', errors.store_hotline || '');
        toggleInvalid(refs.storeHotlineInput, Boolean(errors.store_hotline));
        setFieldError('store_opening_hours', errors.store_opening_hours || '');
        toggleInvalid(refs.storeOpeningHoursInput, Boolean(errors.store_opening_hours));
        setFieldError('complaint_window_days', errors.complaint_window_days || '');
        toggleInvalid(refs.complaintWindowInput, Boolean(errors.complaint_window_days));

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

    const matchTier = (distanceKm, config) => config.tiers.find((tier) => (
        distanceKm >= tier.from_km && distanceKm <= tier.to_km
    )) || null;

    const resolveActivePricing = (distanceKm, config) => {
        const normalizedDistance = Math.max(0, roundDistance(distanceKm));
        const tier = matchTier(normalizedDistance, config);

        if (tier) {
            return {
                kind: 'tier',
                label: `${km(tier.from_km)} - ${km(tier.to_km)}`,
                travelFee: tier.travel_fee,
                transportFee: tier.transport_fee,
                tierIndex: tier.index,
                copy: `${km(normalizedDistance)} nằm trong khoảng ${km(tier.from_km)} - ${km(tier.to_km)}, áp dụng phí đi lại ${money(tier.travel_fee)} và phí thuê xe ${money(tier.transport_fee)}.`,
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
            if (button.closest('.tw-bg-white\/10')) {
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

    const buildRuleItems = (config, activePricing) => config.tiers.map((tier) => ({
        title: `${km(tier.from_km)} - ${km(tier.to_km)}`,
        meta: `Phí thuê xe ${money(tier.transport_fee)} · Phí đi lại ${money(tier.travel_fee)}`,
        transportFee: tier.transport_fee,
        travelFee: tier.travel_fee,
        distance: roundDistance((tier.from_km + tier.to_km) / 2),
        isActive: activePricing.kind === 'tier' && activePricing.tierIndex === tier.index,
    }));

    const getSampleDistances = (config) => {
        const distances = config.tiers.flatMap((tier) => ([
            roundDistance(Math.max(tier.from_km, 0)),
            roundDistance((tier.from_km + tier.to_km) / 2),
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
            store_hotline: config.store_hotline ?? DEFAULT_VISIBLE_CONFIG.store_hotline,
            store_opening_hours: config.store_opening_hours ?? DEFAULT_VISIBLE_CONFIG.store_opening_hours,
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
        if (refs.storeHotlineInput) {
            refs.storeHotlineInput.value = String(visibleConfig.store_hotline ?? '');
        }
        if (refs.storeOpeningHoursInput) {
            refs.storeOpeningHoursInput.value = String(visibleConfig.store_opening_hours ?? '');
        }
        if (refs.complaintWindowInput) {
            refs.complaintWindowInput.value = String(config.complaint_window_days ?? DEFAULT_LEGACY_CONFIG.complaint_window_days);
        }
        renderRows(visibleConfig.tiers);

        state.legacyConfig = {
            free_distance_km: Number(config.free_distance_km ?? DEFAULT_LEGACY_CONFIG.free_distance_km),
            default_per_km: Number(config.default_per_km ?? DEFAULT_LEGACY_CONFIG.default_per_km),
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
