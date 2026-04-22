import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const refs = {
        rail: document.getElementById('repairKnowledgeServices'),
        tree: document.getElementById('repairKnowledgeTree'),
        inspector: document.getElementById('repairKnowledgeInspector'),
        contextTools: document.querySelector('.knowledge-contextbar__tools'),
        search: document.getElementById('knowledgeSearchInput'),
        serviceFilter: document.getElementById('knowledgeServiceFilter'),
        addSymptom: document.getElementById('btnAddSymptom'),
        addCause: document.getElementById('btnAddCause'),
        addResolution: document.getElementById('btnAddResolution'),
        heroServices: document.getElementById('knowledgeHeroServices'),
        heroSymptoms: document.getElementById('knowledgeHeroSymptoms'),
        heroCauses: document.getElementById('knowledgeHeroCauses'),
        heroResolutions: document.getElementById('knowledgeHeroResolutions'),
        statServices: document.getElementById('knowledgeStatServices'),
        statSymptoms: document.getElementById('knowledgeStatSymptoms'),
        statCauses: document.getElementById('knowledgeStatCauses'),
        statResolutions: document.getElementById('knowledgeStatResolutions'),
    };

    const symptom = {
        form: document.getElementById('symptomForm'),
        id: document.getElementById('symptomId'),
        service: document.getElementById('symptomService'),
        name: document.getElementById('symptomName'),
        label: document.getElementById('symptomModalLabel'),
        save: document.getElementById('btnSaveSymptom'),
        modal: new bootstrap.Modal(document.getElementById('symptomModal')),
    };

    const cause = {
        form: document.getElementById('causeForm'),
        id: document.getElementById('causeId'),
        name: document.getElementById('causeName'),
        symptoms: document.getElementById('causeSymptoms'),
        symptomsMeta: document.getElementById('causeSymptomsMeta'),
        label: document.getElementById('causeModalLabel'),
        save: document.getElementById('btnSaveCause'),
        modal: new bootstrap.Modal(document.getElementById('causeModal')),
    };

    const resolution = {
        form: document.getElementById('resolutionForm'),
        id: document.getElementById('resolutionId'),
        cause: document.getElementById('resolutionCause'),
        causeMeta: document.getElementById('resolutionCauseMeta'),
        name: document.getElementById('resolutionName'),
        price: document.getElementById('resolutionPrice'),
        desc: document.getElementById('resolutionDescription'),
        label: document.getElementById('resolutionModalLabel'),
        save: document.getElementById('btnSaveResolution'),
        modal: new bootstrap.Modal(document.getElementById('resolutionModal')),
    };

    const state = {
        items: [],
        visibleItems: [],
        serviceOptions: [],
        symptomOptions: [],
        causeOptions: [],
        selected: { type: 'service', id: null },
        selectedServiceId: null,
        serviceFilterId: '',
        search: '',
        focus: 'symptom',
        causePage: 1,
        causePageSize: 2,
        searchTimer: null,
        servicesExpanded: false,
        pendingFocusScroll: true,
    };

    const defaultButtonHtml = {
        symptom: symptom.save.innerHTML,
        cause: cause.save.innerHTML,
        resolution: resolution.save.innerHTML,
    };

    const applyFriendlyCopy = () => {
        if (refs.search) {
            refs.search.placeholder = 'Tìm dấu hiệu khách mô tả, nguyên nhân hoặc cách xử lý...';
        }

        const toolbarEyebrows = document.querySelectorAll('.knowledge-toolbar__eyebrow');
        if (toolbarEyebrows[1]) {
            toolbarEyebrows[1].textContent = 'Một cây dữ liệu duy nhất';
        }

        const toolbarCopy = document.querySelector('.knowledge-toolbar__copy');
        if (toolbarCopy) {
            toolbarCopy.innerHTML = 'Ở đây bạn không quản lý 4 bảng rời nhau. Bạn đang đi theo cùng một luồng nghiệp vụ: <strong>triệu chứng khách mô tả</strong> dẫn tới <strong>nguyên nhân kỹ thuật</strong>, rồi tới <strong>hướng xử lý và giá tham khảo</strong>.';
        }

        [
            [refs.addSymptom, 'Thêm triệu chứng'],
            [refs.addCause, 'Thêm nguyên nhân'],
            [refs.addResolution, 'Thêm hướng xử lý + giá'],
        ].forEach(([button, label]) => {
            const copyNode = button?.querySelector('span');
            if (copyNode) {
                copyNode.textContent = label;
            }
        });
    };

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    const normalizeText = (value) => (value ?? '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\u0111/g, 'd')
        .replace(/\u0110/g, 'd')
        .toLowerCase()
        .trim();

    const normalizeFocus = (value) => {
        const candidate = (value || '').toString().trim().toLowerCase();
        return ['symptom', 'cause', 'resolution', 'price'].includes(candidate) ? candidate : 'symptom';
    };

    const matchesText = (values, keyword) => {
        if (!keyword) return true;
        const pool = Array.isArray(values) ? values : [values];
        return pool.some((value) => normalizeText(value).includes(keyword));
    };

    const money = (value) => {
        const amount = Number(value);
        if (!Number.isFinite(amount) || amount <= 0) return 'Ch\u01b0a c\u1eadp nh\u1eadt';
        return `${new Intl.NumberFormat('vi-VN').format(amount)} \u0111`;
    };

    const infoBlock = (label, value) => `
        <div class="knowledge-info">
            <span class="knowledge-info__label">${escapeHtml(label)}</span>
            <span class="knowledge-info__value">${escapeHtml(value || '--')}</span>
        </div>
    `;

    const errorMessage = (response, fallback) => {
        const errors = response?.data?.errors;
        if (errors && typeof errors === 'object') {
            const first = Object.values(errors).find((item) => Array.isArray(item) && item.length);
            if (first) return first[0];
        }
        return response?.data?.message || fallback;
    };

    const setButtonLoading = (button, isLoading, defaultHtml) => {
        button.disabled = isLoading;
        button.innerHTML = isLoading
            ? '<i class="fas fa-spinner fa-spin me-2"></i>\u0110ang l\u01b0u...'
            : defaultHtml;
    };

    const extractNameLines = (value) => {
        return [...new Set((value || '')
            .split(/(?:\r?\n|[;,，；])+/)
            .map((item) => item.replace(/\s+/g, ' ').trim())
            .filter(Boolean))];
    };

    const batchCreate = async ({ names, endpoint, buildPayload, fallbackMessage }) => {
        const successes = [];
        const failures = [];

        for (const name of names) {
            const response = await callApi(endpoint, 'POST', buildPayload(name));
            if (response?.ok) {
                successes.push(response.data?.data || null);
                continue;
            }

            failures.push({
                name,
                message: errorMessage(response, fallbackMessage),
            });
        }

        return { successes, failures };
    };

    const showBatchResult = (label, total, successes, failures) => {
        if (successes.length && !failures.length) {
            showToast(`\u0110\u00e3 th\u00eam ${successes.length} ${label}.`);
            return;
        }

        if (successes.length) {
            const failedNames = failures.slice(0, 3).map((item) => item.name).join(', ');
            showToast(`\u0110\u00e3 th\u00eam ${successes.length}/${total} ${label}. L\u1ed7i: ${failedNames}`, 'error');
            return;
        }

        showToast(failures[0]?.message || `Kh\u00f4ng th\u1ec3 th\u00eam ${label}.`, 'error');
    };

    const syncFiltersFromUrl = () => {
        const params = new URLSearchParams(window.location.search);
        state.search = params.get('q') || '';
        state.serviceFilterId = params.get('service_id') || '';
        state.focus = normalizeFocus(params.get('focus'));
        state.pendingFocusScroll = params.has('focus') && state.focus !== 'symptom';
        refs.search.value = state.search;
    };

    const syncUrl = () => {
        const url = new URL(window.location.href);
        state.search ? url.searchParams.set('q', state.search) : url.searchParams.delete('q');
        state.serviceFilterId ? url.searchParams.set('service_id', state.serviceFilterId) : url.searchParams.delete('service_id');
        url.searchParams.set('focus', state.focus);
        window.history.replaceState({}, '', `${url.pathname}${url.search}`);
    };

    const getFocusTargetId = () => {
        switch (state.focus) {
        case 'symptom':
            return 'knowledgeSectionSymptoms';
        case 'cause':
        case 'resolution':
        case 'price':
            return 'knowledgeSectionCauses';
        default:
            return '';
        }
    };

    const scrollToFocusTarget = () => {
        const targetId = getFocusTargetId();
        if (!targetId) return;

        const node = document.getElementById(targetId);
        if (!node) return;

        const offset = window.innerWidth >= 992 ? 164 : 128;
        const top = Math.max(node.getBoundingClientRect().top + window.scrollY - offset, 0);
        window.scrollTo({ top, behavior: 'auto' });
    };

    const cloneResolution = (item) => ({
        ...item,
        service_names: [...(item.service_names || [])],
        service_ids: [...(item.service_ids || [])],
        symptom_names: [...(item.symptom_names || [])],
    });

    const cloneCause = (item) => ({
        ...item,
        service_names: [...(item.service_names || [])],
        service_ids: [...(item.service_ids || [])],
        symptom_names: [...(item.symptom_names || [])],
        symptom_ids: [...(item.symptom_ids || [])],
        symptom_contexts: [...(item.symptom_contexts || [])],
        resolutions: (item.resolutions || []).map(cloneResolution),
    });

    const cloneSymptom = (item) => ({
        ...item,
        nguyen_nhan_names: [...(item.nguyen_nhan_names || [])],
        causes: (item.causes || []).map(cloneCause),
    });

    const filterResolution = (item, keyword) => {
        return matchesText([
            item.ten_huong_xu_ly,
            item.mo_ta_cong_viec,
            item.cause_name,
            ...(item.symptom_names || []),
            ...(item.service_names || []),
        ], keyword) ? cloneResolution(item) : null;
    };

    const filterCause = (item, keyword) => {
        const selfMatch = matchesText([item.ten_nguyen_nhan, ...(item.service_names || []), ...(item.symptom_names || [])], keyword);
        const resolutions = (item.resolutions || []).map((entry) => filterResolution(entry, keyword)).filter(Boolean);
        if (!selfMatch && !resolutions.length) return null;
        const next = cloneCause(item);
        next.resolutions = selfMatch ? (item.resolutions || []).map(cloneResolution) : resolutions;
        next.resolution_count = next.resolutions.length;
        return next;
    };

    const filterSymptom = (item, keyword) => {
        const selfMatch = matchesText([item.ten_trieu_chung, item.service_name, ...(item.nguyen_nhan_names || [])], keyword);
        const causes = (item.causes || []).map((entry) => filterCause(entry, keyword)).filter(Boolean);
        if (!selfMatch && !causes.length) return null;
        const next = cloneSymptom(item);
        next.causes = selfMatch ? (item.causes || []).map(cloneCause) : causes;
        next.nguyen_nhan_count = next.causes.length;
        next.resolution_count = next.causes.reduce((sum, entry) => sum + (entry.resolutions || []).length, 0);
        return next;
    };

    const filterService = (item, keyword) => {
        const selfMatch = matchesText(item.ten_dich_vu, keyword);
        const symptoms = (item.symptoms || []).map((entry) => filterSymptom(entry, keyword)).filter(Boolean);
        if (!selfMatch && !symptoms.length) return null;
        const next = { ...item, symptoms: selfMatch ? (item.symptoms || []).map(cloneSymptom) : symptoms };
        const causeIds = new Set();
        const resolutionIds = new Set();
        next.symptoms.forEach((entry) => {
            (entry.causes || []).forEach((causeEntry) => {
                causeIds.add(Number(causeEntry.id));
                (causeEntry.resolutions || []).forEach((resolutionEntry) => resolutionIds.add(Number(resolutionEntry.id)));
            });
        });
        next.symptom_count = next.symptoms.length;
        next.cause_count = causeIds.size;
        next.resolution_count = resolutionIds.size;
        return next;
    };

    const visibleTree = () => {
        const keyword = normalizeText(state.search);
        return state.items
            .filter((item) => !state.serviceFilterId || String(item.id) === String(state.serviceFilterId))
            .map((item) => keyword ? filterService(item, keyword) : ({ ...item, symptoms: (item.symptoms || []).map(cloneSymptom) }))
            .filter(Boolean);
    };

    const findService = (id, items = state.items) => items.find((item) => String(item.id) === String(id)) || null;

    const getContext = (target = state.selected, items = state.visibleItems.length ? state.visibleItems : state.items) => {
        if (!target?.type || target.id == null) return null;
        for (const serviceItem of items) {
            if (target.type === 'service' && String(serviceItem.id) === String(target.id)) return { type: 'service', service: serviceItem };
            for (const symptomItem of serviceItem.symptoms || []) {
                if (target.type === 'symptom' && String(symptomItem.id) === String(target.id)) return { type: 'symptom', service: serviceItem, symptom: symptomItem };
                for (const causeItem of symptomItem.causes || []) {
                    if (target.type === 'cause' && String(causeItem.id) === String(target.id)) return { type: 'cause', service: serviceItem, symptom: symptomItem, cause: causeItem };
                    for (const resolutionItem of causeItem.resolutions || []) {
                        if (target.type === 'resolution' && String(resolutionItem.id) === String(target.id)) {
                            return { type: 'resolution', service: serviceItem, symptom: symptomItem, cause: causeItem, resolution: resolutionItem };
                        }
                    }
                }
            }
        }
        return null;
    };

    const findNodeById = (type, id) => getContext({ type, id }, state.items);

    const summary = (items) => {
        const causeIds = new Set();
        const resolutionIds = new Set();
        let symptomCount = 0;
        items.forEach((serviceItem) => {
            symptomCount += (serviceItem.symptoms || []).length;
            (serviceItem.symptoms || []).forEach((symptomItem) => {
                (symptomItem.causes || []).forEach((causeItem) => {
                    causeIds.add(Number(causeItem.id));
                    (causeItem.resolutions || []).forEach((resolutionItem) => resolutionIds.add(Number(resolutionItem.id)));
                });
            });
        });
        return { services: items.length, symptoms: symptomCount, causes: causeIds.size, resolutions: resolutionIds.size };
    };

    const dateValue = (value) => {
        const parsed = Date.parse(value || '');
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const aggregateCauses = (serviceItem) => {
        const bag = new Map();
        (serviceItem?.symptoms || []).forEach((symptomItem) => {
            (symptomItem.causes || []).forEach((causeItem) => {
                const key = Number(causeItem.id);
                if (!bag.has(key)) {
                    bag.set(key, { ...cloneCause(causeItem), symptom_ids: [], symptom_names: [], symptom_contexts: [], resolutions: [] });
                }
                const target = bag.get(key);
                if (!target.symptom_ids.includes(symptomItem.id)) {
                    target.symptom_ids.push(symptomItem.id);
                    target.symptom_names.push(symptomItem.ten_trieu_chung);
                    target.symptom_contexts.push({ id: symptomItem.id, ten_trieu_chung: symptomItem.ten_trieu_chung });
                }
                (causeItem.resolutions || []).forEach((resolutionItem) => {
                    if (!target.resolutions.some((entry) => String(entry.id) === String(resolutionItem.id))) {
                        target.resolutions.push(cloneResolution(resolutionItem));
                    }
                });
            });
        });
        return [...bag.values()]
            .map((item) => ({ ...item, linked_symptom_count: item.symptom_ids.length, resolution_count: item.resolutions.length }))
            .sort((a, b) => String(a.ten_nguyen_nhan || '').localeCompare(String(b.ten_nguyen_nhan || ''), 'vi'));
    };

    const paginate = (items, page, pageSize) => {
        const total = items.length;
        const totalPages = Math.max(1, Math.ceil(total / pageSize));
        const safePage = Math.min(Math.max(page, 1), totalPages);
        const start = (safePage - 1) * pageSize;
        return {
            page: safePage,
            pageSize,
            total,
            totalPages,
            start,
            end: Math.min(start + pageSize, total),
            items: items.slice(start, start + pageSize),
        };
    };

    const buildPageNumbers = (currentPage, totalPages) => {
        if (totalPages <= 1) return [1];
        const pages = new Set([1, totalPages, currentPage, currentPage - 1, currentPage + 1]);
        return [...pages]
            .filter((page) => page >= 1 && page <= totalPages)
            .sort((a, b) => a - b);
    };

    const servicePillCount = (serviceItem) => serviceItem.resolution_count || serviceItem.cause_count || serviceItem.symptom_count || 0;

    const servicePreviewLimit = () => {
        if (window.innerWidth >= 1680) return 6;
        if (window.innerWidth >= 1400) return 5;
        if (window.innerWidth >= 1200) return 4;
        if (window.innerWidth >= 992) return 3;
        return 2;
    };

    const visibleServiceItems = (items) => {
        const limit = servicePreviewLimit();
        if (state.servicesExpanded || items.length <= limit) return items;

        const selected = items.find((item) => String(item.id) === String(state.selectedServiceId));
        const preview = items.slice(0, limit);

        if (selected && !preview.some((item) => String(item.id) === String(selected.id))) {
            return [selected, ...preview.slice(0, Math.max(limit - 1, 0))];
        }

        return preview;
    };

    const describeService = (serviceItem) => {
        if (!serviceItem) return '';
        if (!serviceItem.symptom_count && !serviceItem.cause_count && !serviceItem.resolution_count) {
            return 'Dịch vụ này chưa có dữ liệu để nối từ triệu chứng sang nguyên nhân và hướng xử lý.';
        }
        return `Dịch vụ này đang có ${serviceItem.symptom_count || 0} triệu chứng, ${serviceItem.cause_count || 0} nguyên nhân và ${serviceItem.resolution_count || 0} hướng xử lý. Admin chỉ cần đọc theo đúng luồng để hiểu lỗi và cập nhật giá.`;
    };

    const describeCause = (causeItem) => {
        const names = causeItem.symptom_names || [];
        if (!names.length) return 'Nguyên nhân này chưa có triệu chứng liên kết cụ thể.';
        const preview = names.slice(0, 2).join(', ');
        const extra = names.length > 2 ? ` và ${names.length - 2} triệu chứng khác` : '';
        return `Liên kết với ${preview}${extra}.`;
    };

    const causeBadge = (causeItem) => {
        const hasMissingPrice = (causeItem.resolutions || []).some((entry) => Number(entry.gia_tham_khao || 0) <= 0);
        if (hasMissingPrice) {
            return { label: 'Cần cập nhật giá', className: 'is-warning' };
        }
        if ((causeItem.resolution_count || 0) >= 3) {
            return { label: 'Phổ biến', className: 'is-primary' };
        }
        return { label: 'Đang theo dõi', className: 'is-muted' };
    };

    const causeIcon = (causeItem) => {
        const keyword = normalizeText(causeItem?.ten_nguyen_nhan || '');
        if (keyword.includes('dien') || keyword.includes('nguon')) return 'fas fa-bolt';
        if (keyword.includes('ic') || keyword.includes('mach')) return 'fas fa-microchip';
        if (keyword.includes('quat') || keyword.includes('dong co')) return 'fas fa-fan';
        if (keyword.includes('day') || keyword.includes('cam')) return 'fas fa-plug';
        return 'fas fa-screwdriver-wrench';
    };

    const contextualCreateLabel = () => {
        if (state.selected.type === 'cause') return 'Thêm hướng xử lý mới';
        if (state.selected.type === 'symptom') return 'Thêm nguyên nhân mới';
        return 'Thêm mới';
    };

    const contextualCreateHint = () => {
        if (state.selected.type === 'cause') return 'Bạn đang đứng ở một nguyên nhân, thêm ngay hướng xử lý và giá tham khảo.';
        if (state.selected.type === 'symptom') return 'Bạn đang đứng ở một triệu chứng, thêm nguyên nhân gốc để nối tiếp luồng xử lý.';
        return 'Bắt đầu từ triệu chứng, rồi nối tiếp nguyên nhân và hướng xử lý trong cùng một trang.';
    };

    const firstMissingPriceCause = (serviceItem) => aggregateCauses(serviceItem).find((item) =>
        (item.resolutions || []).some((entry) => Number(entry.gia_tham_khao || 0) <= 0)
    ) || null;

    const topSymptomsForService = (serviceItem) => {
        return [...(serviceItem?.symptoms || [])]
            .sort((a, b) => (b.resolution_count || 0) - (a.resolution_count || 0) || (b.nguyen_nhan_count || 0) - (a.nguyen_nhan_count || 0))
            .slice(0, 3);
    };

    const topCausesForService = (serviceItem) => {
        return aggregateCauses(serviceItem)
            .sort((a, b) => (b.resolution_count || 0) - (a.resolution_count || 0) || (b.linked_symptom_count || 0) - (a.linked_symptom_count || 0))
            .slice(0, 3);
    };

    const topResolutionsForService = (serviceItem) => {
        return aggregateCauses(serviceItem)
            .flatMap((causeItem) => (causeItem.resolutions || []).map((entry) => ({
                ...cloneResolution(entry),
                cause_name: causeItem.ten_nguyen_nhan,
            })))
            .sort((a, b) => dateValue(b.updated_at) - dateValue(a.updated_at) || Number(b.gia_tham_khao || 0) - Number(a.gia_tham_khao || 0))
            .slice(0, 3);
    };

    const renderInsightList = (items, type, iconClass, metaBuilder) => {
        if (!items.length) {
            return '<div class="knowledge-empty"><p class="mb-0">Chưa có dữ liệu nổi bật.</p></div>';
        }

        return `<div class="knowledge-side-list">${items.map((item) => `
            <button type="button" class="knowledge-side-item" data-select-type="${type}" data-select-id="${item.id}">
                <span class="knowledge-side-item__copy">
                    <i class="${iconClass}"></i>
                    <span>${escapeHtml(item.ten_trieu_chung || item.ten_nguyen_nhan || item.ten_huong_xu_ly || item.ten_dich_vu || '')}</span>
                </span>
                <span class="knowledge-side-item__meta">${escapeHtml(metaBuilder(item))}</span>
            </button>
        `).join('')}</div>`;
    };

    const populateServiceOptions = () => {
        refs.serviceFilter.innerHTML = '<option value="">T\u1ea5t c\u1ea3 d\u1ecbch v\u1ee5</option>' + state.serviceOptions.map((item) => `<option value="${item.id}">${escapeHtml(item.ten_dich_vu || `D\u1ecbch v\u1ee5 #${item.id}`)}</option>`).join('');
        refs.serviceFilter.value = state.serviceFilterId;
        symptom.service.innerHTML = '<option value="">Ch\u1ecdn d\u1ecbch v\u1ee5</option>' + state.serviceOptions.map((item) => `<option value="${item.id}">${escapeHtml(item.ten_dich_vu || `D\u1ecbch v\u1ee5 #${item.id}`)}</option>`).join('');
    };

    const populateCauseSymptoms = (selectedIds = []) => {
        const selected = new Set(selectedIds.map(String));
        cause.symptoms.innerHTML = state.symptomOptions.map((item) => `<option value="${item.id}" ${selected.has(String(item.id)) ? 'selected' : ''}>${escapeHtml(item.label || item.ten_trieu_chung || `Tri\u1ec7u ch\u1ee9ng #${item.id}`)}</option>`).join('');
        const count = selectedIds.length;
        cause.symptomsMeta.textContent = count ? `${count} tri\u1ec7u ch\u1ee9ng \u0111\u00e3 ch\u1ecdn` : 'Gi\u1eef Ctrl ho\u1eb7c Cmd \u0111\u1ec3 ch\u1ecdn nhi\u1ec1u tri\u1ec7u ch\u1ee9ng.';
    };

    const populateResolutionCauses = (selectedId = '') => {
        resolution.cause.innerHTML = '<option value="">Ch\u1ecdn nguy\u00ean nh\u00e2n</option>' + state.causeOptions.map((item) => `<option value="${item.id}" ${String(selectedId) === String(item.id) ? 'selected' : ''}>${escapeHtml(`${item.ten_nguyen_nhan} \u00b7 ${(item.service_names || []).join(', ')}`)}</option>`).join('');
        const current = state.causeOptions.find((item) => String(item.id) === String(selectedId));
        resolution.causeMeta.textContent = current ? `${current.ten_nguyen_nhan} \u00b7 ${(current.symptom_names || []).join(', ') || 'Ch\u01b0a c\u00f3 tri\u1ec7u ch\u1ee9ng li\u00ean k\u1ebft'}` : 'Ch\u1ecdn nguy\u00ean nh\u00e2n \u0111\u1ec3 g\u1eafn h\u01b0\u1edbng x\u1eed l\u00fd \u0111\u00fang nh\u00e1nh.';
    };

    const renderStats = (items) => {
        const totals = summary(items);
        if (refs.heroServices) refs.heroServices.textContent = totals.services;
        if (refs.heroSymptoms) refs.heroSymptoms.textContent = totals.symptoms;
        if (refs.heroCauses) refs.heroCauses.textContent = totals.causes;
        if (refs.heroResolutions) refs.heroResolutions.textContent = totals.resolutions;
        if (refs.statServices) refs.statServices.textContent = totals.services;
        if (refs.statSymptoms) refs.statSymptoms.textContent = totals.symptoms;
        if (refs.statCauses) refs.statCauses.textContent = totals.causes;
        if (refs.statResolutions) refs.statResolutions.textContent = totals.resolutions;
    };

    const renderRail = (items) => {
        if (!items.length) {
            refs.rail.innerHTML = '<div class="knowledge-empty knowledge-empty--inline"><p class="mb-0">Kh\u00f4ng c\u00f3 d\u1ecbch v\u1ee5 n\u00e0o kh\u1edbp b\u1ed9 l\u1ecdc hi\u1ec7n t\u1ea1i.</p></div>';
            return;
        }
        const previewItems = visibleServiceItems(items);
        const hiddenCount = Math.max(items.length - previewItems.length, 0);

        refs.rail.innerHTML = previewItems.map((item) => `
            <button type="button" class="knowledge-service-pill ${String(state.selectedServiceId) === String(item.id) ? 'is-active' : ''} ${Number(item.trang_thai || 0) === 1 ? '' : 'is-offline'}" data-select-service="${item.id}">
                <span class="knowledge-service-pill__label">${escapeHtml(item.ten_dich_vu || `D\u1ecbch v\u1ee5 #${item.id}`)}</span>
                <span class="knowledge-service-pill__count">${servicePillCount(item)}</span>
            </button>
        `).join('') + (items.length > previewItems.length || state.servicesExpanded ? `
            <button type="button" class="knowledge-service-toggle" data-toggle-services="${state.servicesExpanded ? 'collapse' : 'expand'}">
                <i class="fas ${state.servicesExpanded ? 'fa-chevron-up' : 'fa-chevron-down'}"></i>
                <span>${state.servicesExpanded ? 'Thu g\u1ecdn' : `Xem th\u00eam ${hiddenCount} d\u1ecbch v\u1ee5`}</span>
            </button>
        ` : '');
    };

    const renderWorkspace = (serviceItem) => {
        if (!serviceItem) {
            refs.tree.innerHTML = '<div class="knowledge-empty"><p class="mb-0">Ch\u1ecdn m\u1ed9t d\u1ecbch v\u1ee5 \u1edf thanh tr\u00ean \u0111\u1ec3 xem nguy\u00ean nh\u00e2n, h\u01b0\u1edbng x\u1eed l\u00fd v\u00e0 gi\u00e1 tham kh\u1ea3o.</p></div>';
            return;
        }

        const causes = aggregateCauses(serviceItem);
        const symptomTags = (serviceItem.symptoms || []).length
            ? `${(serviceItem.symptoms || []).map((item) => `
                <button type="button" class="knowledge-tag ${state.selected.type === 'symptom' && String(state.selected.id) === String(item.id) ? 'is-selected' : ''}" data-select-type="symptom" data-select-id="${item.id}">
                    <i class="fas fa-wave-square"></i>
                    <span>${escapeHtml(item.ten_trieu_chung || `Tri\u1ec7u ch\u1ee9ng #${item.id}`)}</span>
                </button>
            `).join('')}
            <button type="button" class="knowledge-tag knowledge-tag--ghost" data-action="add-symptom" data-service-id="${serviceItem.id}">
                <i class="fas fa-plus"></i>
                <span>Th\u00eam tri\u1ec7u ch\u1ee9ng</span>
            </button>`
            : `<button type="button" class="knowledge-tag knowledge-tag--ghost" data-action="add-symptom" data-service-id="${serviceItem.id}">
                <i class="fas fa-plus"></i>
                <span>Th\u00eam tri\u1ec7u ch\u1ee9ng \u0111\u1ea7u ti\u00ean</span>
            </button>`;

        const causeCards = causes.length ? causes.map((item) => {
            const badge = causeBadge(item);
            return `
                <article class="knowledge-cause-card ${state.selected.type === 'cause' && String(state.selected.id) === String(item.id) ? 'is-selected' : ''} ${state.focus === 'cause' ? 'is-focus-spotlight' : ''}" data-select-type="cause" data-select-id="${item.id}">
                    <div class="knowledge-cause-card__header">
                        <div class="knowledge-cause-card__lead">
                            <div class="knowledge-cause-card__icon">
                                <i class="fas fa-microchip"></i>
                            </div>
                            <div class="knowledge-cause-card__copy">
                                <h3 class="knowledge-cause-card__title">${escapeHtml(item.ten_nguyen_nhan || `Nguy\u00ean nh\u00e2n #${item.id}`)}</h3>
                                <p class="knowledge-cause-card__desc">${escapeHtml(describeCause(item))}</p>
                            </div>
                        </div>
                        <span class="knowledge-cause-card__badge ${badge.className}">${escapeHtml(badge.label)}</span>
                    </div>

                    <div class="knowledge-cause-card__symptoms">
                        ${(item.symptom_contexts || []).map((symptomItem) => `
                            <button type="button" class="knowledge-tag ${state.selected.type === 'symptom' && String(state.selected.id) === String(symptomItem.id) ? 'is-selected' : ''}" data-select-type="symptom" data-select-id="${symptomItem.id}">
                                <i class="fas fa-wave-square"></i>
                                <span>${escapeHtml(symptomItem.ten_trieu_chung)}</span>
                            </button>
                        `).join('')}
                    </div>

                    <div class="knowledge-resolution-list">
                        ${(item.resolutions || []).length ? (item.resolutions || []).map((entry) => {
                            const hasPrice = Number(entry.gia_tham_khao || 0) > 0;
                            return `
                                <article class="knowledge-resolution ${state.selected.type === 'resolution' && String(state.selected.id) === String(entry.id) ? 'is-selected' : ''} ${state.focus === 'resolution' || state.focus === 'price' ? 'is-focus-spotlight' : ''} ${state.focus === 'price' ? 'is-price-focus' : ''}" data-select-type="resolution" data-select-id="${entry.id}">
                                    <div class="knowledge-resolution__copy">
                                        <div class="knowledge-resolution__titleline">
                                            <i class="fas fa-circle-check"></i>
                                            <h4 class="knowledge-resolution__title">${escapeHtml(entry.ten_huong_xu_ly || `H\u01b0\u1edbng x\u1eed l\u00fd #${entry.id}`)}</h4>
                                        </div>
                                        <p class="knowledge-resolution__desc">${escapeHtml(entry.mo_ta_cong_viec || 'Ch\u01b0a c\u00f3 m\u00f4 t\u1ea3 c\u00f4ng vi\u1ec7c chi ti\u1ebft.')}</p>
                                    </div>

                                    <div class="knowledge-resolution__side">
                                        <span class="knowledge-resolution__price ${hasPrice ? '' : 'is-empty'}">${escapeHtml(entry.gia_label || money(entry.gia_tham_khao))}</span>
                                        <div class="knowledge-resolution__actions">
                                            <button type="button" class="knowledge-resolution__action" data-action="edit-resolution" data-id="${entry.id}" aria-label="S\u1eeda h\u01b0\u1edbng x\u1eed l\u00fd">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button type="button" class="knowledge-resolution__action is-danger" data-action="delete-resolution" data-id="${entry.id}" aria-label="X\u00f3a h\u01b0\u1edbng x\u1eed l\u00fd">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            `;
                        }).join('') : '<div class="knowledge-empty"><p class="mb-0">Nguy\u00ean nh\u00e2n n\u00e0y ch\u01b0a c\u00f3 h\u01b0\u1edbng x\u1eed l\u00fd.</p></div>'}
                    </div>

                    <div class="knowledge-cause-card__footer">
                        <button type="button" class="knowledge-inline-action" data-action="add-resolution" data-id="${item.id}">
                            <i class="fas fa-plus"></i>
                            <span>Th\u00eam h\u01b0\u1edbng x\u1eed l\u00fd + gi\u00e1</span>
                        </button>
                        <button type="button" class="knowledge-inline-action" data-action="edit-cause" data-id="${item.id}">
                            <i class="fas fa-pen"></i>
                            <span>S\u1eeda</span>
                        </button>
                        <button type="button" class="knowledge-inline-action is-danger" data-action="delete-cause" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                            <span>X\u00f3a</span>
                        </button>
                    </div>
                </article>
            `;
        }).join('') : '<div class="knowledge-empty"><p class="mb-0">Ch\u01b0a c\u00f3 nguy\u00ean nh\u00e2n n\u00e0o kh\u1edbp trong d\u1ecbch v\u1ee5 n\u00e0y.</p></div>';

        refs.tree.innerHTML = `
            <section class="knowledge-workspace__summary">
                <span class="knowledge-workspace__kicker">B\u1ea1n \u0111ang xem c\u00e2y tri th\u1ee9c c\u1ee7a d\u1ecbch v\u1ee5 n\u00e0y</span>
                <h1 class="knowledge-workspace__title">${escapeHtml(serviceItem.ten_dich_vu || `D\u1ecbch v\u1ee5 #${serviceItem.id}`)}</h1>
                <p class="knowledge-workspace__desc">${escapeHtml(describeService(serviceItem))}</p>
                <div class="knowledge-summary-chips">
                    <span class="knowledge-summary-chip"><i class="fas fa-wave-square"></i>${serviceItem.symptom_count || 0} tri\u1ec7u ch\u1ee9ng</span>
                    <span class="knowledge-summary-chip"><i class="fas fa-link"></i>${serviceItem.cause_count || 0} nguy\u00ean nh\u00e2n</span>
                    <span class="knowledge-summary-chip"><i class="fas fa-screwdriver-wrench"></i>${serviceItem.resolution_count || 0} h\u01b0\u1edbng x\u1eed l\u00fd</span>
                </div>
            </section>

            <section id="knowledgeSectionSymptoms" class="knowledge-section ${state.focus === 'symptom' ? 'is-focus-target' : ''}">
                <span class="knowledge-section-label">1. Kh\u00e1ch \u0111ang m\u00f4 t\u1ea3 d\u1ea5u hi\u1ec7u g\u00ec?</span>
                <div class="knowledge-symptom-cloud">${symptomTags}</div>
            </section>

            <section id="knowledgeSectionCauses" class="knowledge-section ${state.focus === 'cause' || state.focus === 'resolution' || state.focus === 'price' ? 'is-focus-target' : ''}">
                <span class="knowledge-section-label">2. L\u1ed7i g\u1ed1c l\u00e0 g\u00ec v\u00e0 n\u00ean x\u1eed l\u00fd th\u1ebf n\u00e0o?</span>
                <div class="knowledge-cause-stack">${causeCards}</div>
            </section>
        `;
    };

    const renderContextCard = (context) => {
        if (context.type === 'service') {
            return `
                <span class="knowledge-side-card__eyebrow">M\u1ee5c \u0111ang ch\u1ecdn</span>
                <h3 class="knowledge-side-card__title">${escapeHtml(context.service.ten_dich_vu)}</h3>
                <p class="knowledge-side-card__desc">${escapeHtml(describeService(context.service))}</p>
                <div class="knowledge-info-grid">
                    ${infoBlock('Tr\u1ea1ng th\u00e1i', Number(context.service.trang_thai || 0) === 1 ? '\u0110ang ho\u1ea1t \u0111\u1ed9ng' : '\u0110\u00e3 \u1ea9n')}
                    ${infoBlock('Tri\u1ec7u ch\u1ee9ng', `${context.service.symptom_count || 0} m\u1ee5c`)}
                    ${infoBlock('Nguy\u00ean nh\u00e2n', `${context.service.cause_count || 0} m\u1ee5c`)}
                    ${infoBlock('H\u01b0\u1edbng x\u1eed l\u00fd', `${context.service.resolution_count || 0} m\u1ee5c`)}
                </div>
                <div class="knowledge-side-actions">
                    <button type="button" class="knowledge-side-action is-primary" data-action="add-symptom" data-service-id="${context.service.id}"><i class="fas fa-plus"></i><span>Th\u00eam tri\u1ec7u ch\u1ee9ng</span></button>
                    <button type="button" class="knowledge-side-action" data-action="add-cause"><i class="fas fa-link"></i><span>Th\u00eam nguy\u00ean nh\u00e2n</span></button>
                    <button type="button" class="knowledge-side-action" data-action="add-resolution"><i class="fas fa-screwdriver-wrench"></i><span>Th\u00eam h\u01b0\u1edbng x\u1eed l\u00fd + gi\u00e1</span></button>
                </div>
            `;
        }

        if (context.type === 'symptom') {
            return `
                <span class="knowledge-side-card__eyebrow">M\u1ee5c \u0111ang ch\u1ecdn</span>
                <h3 class="knowledge-side-card__title">${escapeHtml(context.symptom.ten_trieu_chung)}</h3>
                <p class="knowledge-side-card__desc">${escapeHtml(context.service.ten_dich_vu)}</p>
                <div class="knowledge-info-grid">
                    ${infoBlock('D\u1ecbch v\u1ee5', context.service.ten_dich_vu)}
                    ${infoBlock('Nguy\u00ean nh\u00e2n li\u00ean k\u1ebft', `${context.symptom.nguyen_nhan_count || 0} m\u1ee5c`)}
                    ${infoBlock('Danh s\u00e1ch nguy\u00ean nh\u00e2n', (context.symptom.nguyen_nhan_names || []).join(', ') || 'Ch\u01b0a c\u00f3')}
                    ${infoBlock('C\u1eadp nh\u1eadt l\u1ea7n cu\u1ed1i', context.symptom.updated_label || 'Ch\u01b0a c\u1eadp nh\u1eadt')}
                </div>
                <div class="knowledge-side-actions">
                    <button type="button" class="knowledge-side-action" data-action="edit-symptom" data-id="${context.symptom.id}"><i class="fas fa-pen"></i><span>S\u1eeda tri\u1ec7u ch\u1ee9ng</span></button>
                    <button type="button" class="knowledge-side-action" data-action="add-cause" data-id="${context.symptom.id}"><i class="fas fa-link"></i><span>Th\u00eam nguy\u00ean nh\u00e2n</span></button>
                    <button type="button" class="knowledge-side-action is-danger" data-action="delete-symptom" data-id="${context.symptom.id}"><i class="fas fa-trash"></i><span>X\u00f3a tri\u1ec7u ch\u1ee9ng</span></button>
                </div>
            `;
        }

        if (context.type === 'cause') {
            return `
                <span class="knowledge-side-card__eyebrow">M\u1ee5c \u0111ang ch\u1ecdn</span>
                <h3 class="knowledge-side-card__title">${escapeHtml(context.cause.ten_nguyen_nhan)}</h3>
                <p class="knowledge-side-card__desc">${escapeHtml(describeCause(context.cause))}</p>
                <div class="knowledge-info-grid">
                    ${infoBlock('D\u1ecbch v\u1ee5 li\u00ean quan', (context.cause.service_names || []).join(', ') || context.service.ten_dich_vu)}
                    ${infoBlock('Tri\u1ec7u ch\u1ee9ng li\u00ean k\u1ebft', (context.cause.symptom_names || []).join(', ') || 'Ch\u01b0a c\u00f3')}
                    ${infoBlock('S\u1ed1 h\u01b0\u1edbng x\u1eed l\u00fd', `${context.cause.resolution_count || 0} m\u1ee5c`)}
                    ${infoBlock('C\u1eadp nh\u1eadt l\u1ea7n cu\u1ed1i', context.cause.updated_label || 'Ch\u01b0a c\u1eadp nh\u1eadt')}
                </div>
                <div class="knowledge-side-actions">
                    <button type="button" class="knowledge-side-action is-primary" data-action="add-resolution" data-id="${context.cause.id}"><i class="fas fa-plus"></i><span>Th\u00eam h\u01b0\u1edbng x\u1eed l\u00fd + gi\u00e1</span></button>
                    <button type="button" class="knowledge-side-action" data-action="edit-cause" data-id="${context.cause.id}"><i class="fas fa-pen"></i><span>S\u1eeda nguy\u00ean nh\u00e2n</span></button>
                    <button type="button" class="knowledge-side-action is-danger" data-action="delete-cause" data-id="${context.cause.id}"><i class="fas fa-trash"></i><span>X\u00f3a nguy\u00ean nh\u00e2n</span></button>
                </div>
            `;
        }

        return `
            <span class="knowledge-side-card__eyebrow">M\u1ee5c \u0111ang ch\u1ecdn</span>
            <h3 class="knowledge-side-card__title">${escapeHtml(context.resolution.ten_huong_xu_ly)}</h3>
            <p class="knowledge-side-card__desc">${escapeHtml(context.cause.ten_nguyen_nhan || context.resolution.cause_name || '')}</p>
            <div class="knowledge-info-grid">
                ${infoBlock('Nguy\u00ean nh\u00e2n', context.cause.ten_nguyen_nhan || context.resolution.cause_name || '--')}
                ${infoBlock('D\u1ecbch v\u1ee5 li\u00ean quan', (context.resolution.service_names || []).join(', ') || context.service.ten_dich_vu)}
                ${infoBlock('Gi\u00e1 tham kh\u1ea3o', context.resolution.gia_label || money(context.resolution.gia_tham_khao))}
                ${infoBlock('C\u1eadp nh\u1eadt l\u1ea7n cu\u1ed1i', context.resolution.updated_label || 'Ch\u01b0a c\u1eadp nh\u1eadt')}
            </div>
            <div class="knowledge-side-actions">
                <button type="button" class="knowledge-side-action" data-action="edit-resolution" data-id="${context.resolution.id}"><i class="fas fa-pen"></i><span>S\u1eeda h\u01b0\u1edbng x\u1eed l\u00fd</span></button>
                <button type="button" class="knowledge-side-action is-danger" data-action="delete-resolution" data-id="${context.resolution.id}"><i class="fas fa-trash"></i><span>X\u00f3a h\u01b0\u1edbng x\u1eed l\u00fd</span></button>
            </div>
        `;
    };

    const renderInspector = () => {
        const serviceItem = findService(state.selectedServiceId, state.visibleItems);
        if (!serviceItem) {
            refs.inspector.innerHTML = '<div class="knowledge-side-card"><div class="knowledge-empty"><p class="mb-0">Ch\u1ecdn m\u1ed9t d\u1ecbch v\u1ee5 \u0111\u1ec3 xem insight.</p></div></div>';
            return;
        }

        const context = getContext(state.selected, state.visibleItems) || { type: 'service', service: serviceItem };
        const topSymptoms = topSymptomsForService(serviceItem);
        const topCauses = topCausesForService(serviceItem);
        const topResolutions = topResolutionsForService(serviceItem);

        refs.inspector.innerHTML = `
            <div class="knowledge-side-card">
                ${renderContextCard(context)}
            </div>

            <div class="knowledge-side-card knowledge-side-card--muted">
                <span class="knowledge-side-card__eyebrow">\u0110i\u1ec3m n\u1ed5i b\u1eadt c\u1ee7a d\u1ecbch v\u1ee5</span>
                <div class="knowledge-side-section">
                    <span class="knowledge-side-section__label">Tri\u1ec7u ch\u1ee9ng n\u1ed5i b\u1eadt</span>
                    ${renderInsightList(topSymptoms, 'symptom', 'fas fa-wave-square', (item) => `${item.nguyen_nhan_count || 0} nguy\u00ean nh\u00e2n`)}
                </div>
                <div class="knowledge-side-section">
                    <span class="knowledge-side-section__label">Nguy\u00ean nh\u00e2n ch\u00ednh</span>
                    ${renderInsightList(topCauses, 'cause', 'fas fa-link', (item) => `${item.resolution_count || 0} h\u01b0\u1edbng x\u1eed l\u00fd`)}
                </div>
                <div class="knowledge-side-section">
                    <span class="knowledge-side-section__label">H\u01b0\u1edbng x\u1eed l\u00fd c\u1eadp nh\u1eadt</span>
                    ${renderInsightList(topResolutions, 'resolution', 'fas fa-screwdriver-wrench', (item) => item.gia_label || money(item.gia_tham_khao))}
                </div>
                <div class="knowledge-side-divider"></div>
                <div class="knowledge-side-actions">
                    <button type="button" class="knowledge-side-action" data-action="add-symptom" data-service-id="${serviceItem.id}"><i class="fas fa-plus"></i><span>Th\u00eam tri\u1ec7u ch\u1ee9ng</span></button>
                    <button type="button" class="knowledge-side-action" data-action="add-cause"><i class="fas fa-link"></i><span>Th\u00eam nguy\u00ean nh\u00e2n</span></button>
                    <button type="button" class="knowledge-side-action" data-action="add-resolution"><i class="fas fa-screwdriver-wrench"></i><span>Th\u00eam h\u01b0\u1edbng x\u1eed l\u00fd + gi\u00e1</span></button>
                </div>
            </div>

            <div class="knowledge-side-card knowledge-side-card--accent">
                <span class="knowledge-side-card__eyebrow">L\u01b0u \u00fd cho admin m\u1edbi</span>
                <h3 class="knowledge-side-card__title">Gi\u00e1 lu\u00f4n \u0111i c\u00f9ng h\u01b0\u1edbng x\u1eed l\u00fd</h3>
                <p class="knowledge-side-card__desc">N\u1ebfu b\u1ea1n ch\u01b0a hi\u1ec3u trang n\u00e0y, h\u00e3y nh\u1edb quy t\u1eafc \u0111\u01a1n gi\u1ea3n n\u00e0y: tri\u1ec7u ch\u1ee9ng d\u1eabn t\u1edbi nguy\u00ean nh\u00e2n, nguy\u00ean nh\u00e2n m\u1edbi c\u00f3 h\u01b0\u1edbng x\u1eed l\u00fd, v\u00e0 gi\u00e1 n\u1eb1m trong ch\u00ednh h\u01b0\u1edbng x\u1eed l\u00fd \u0111\u00f3.</p>
                <button type="button" class="knowledge-side-action" data-action="add-resolution">
                    <i class="fas fa-sparkles"></i>
                    <span>M\u1edf form h\u01b0\u1edbng x\u1eed l\u00fd + gi\u00e1</span>
                </button>
            </div>
        `;
    };

    const renderRailV2 = (items) => {
        if (!items.length) {
            refs.rail.innerHTML = '<div class="knowledge-empty knowledge-empty--inline"><p class="mb-0">Không có dịch vụ nào khớp bộ lọc hiện tại.</p></div>';
            if (refs.contextTools) {
                refs.contextTools.innerHTML = `
                    <div class="knowledge-filter-indicator is-empty">
                        <i class="fas fa-sliders"></i>
                        <span>Không có dữ liệu phù hợp</span>
                    </div>
                `;
            }
            return;
        }

        const previewItems = visibleServiceItems(items);
        const hiddenCount = Math.max(items.length - previewItems.length, 0);
        const selectedService = findService(state.selectedServiceId, items) || previewItems[0];
        const filterLabel = state.search ? `Đang lọc: ${state.search}` : `${items.length} dịch vụ đang hiển thị`;

        refs.rail.innerHTML = previewItems.map((item) => `
            <button type="button" class="knowledge-service-pill ${String(state.selectedServiceId) === String(item.id) ? 'is-active' : ''} ${Number(item.trang_thai || 0) === 1 ? '' : 'is-offline'}" data-select-service="${item.id}">
                <span class="knowledge-service-pill__label">${escapeHtml(item.ten_dich_vu || `Dịch vụ #${item.id}`)}</span>
                <span class="knowledge-service-pill__count">${servicePillCount(item)}</span>
            </button>
        `).join('') + (items.length > previewItems.length || state.servicesExpanded ? `
            <button type="button" class="knowledge-service-toggle" data-toggle-services="${state.servicesExpanded ? 'collapse' : 'expand'}">
                <i class="fas ${state.servicesExpanded ? 'fa-chevron-up' : 'fa-chevron-down'}"></i>
                <span>${state.servicesExpanded ? 'Thu gọn' : `Xem thêm ${hiddenCount} dịch vụ`}</span>
            </button>
        ` : '');

        if (refs.contextTools) {
            refs.contextTools.innerHTML = `
                <div class="knowledge-filter-indicator">
                    <i class="fas fa-sliders"></i>
                    <span>${escapeHtml(filterLabel)}</span>
                </div>
                <div class="knowledge-filter-indicator is-soft">
                    <i class="fas fa-layer-group"></i>
                    <span>${escapeHtml(selectedService?.ten_dich_vu || 'Tri thức sửa chữa')}</span>
                </div>
            `;
        }
    };

    const renderWorkspaceV2 = (serviceItem) => {
        if (!serviceItem) {
            refs.tree.innerHTML = '<div class="knowledge-empty"><p class="mb-0">Chọn một dịch vụ ở thanh trên để xem nguyên nhân, hướng xử lý và giá tham khảo.</p></div>';
            return;
        }

        const causes = aggregateCauses(serviceItem);
        const selectedCauseIndex = causes.findIndex((item) => {
            if (state.selected.type === 'cause') return String(item.id) === String(state.selected.id);
            if (state.selected.type === 'resolution') {
                return (item.resolutions || []).some((entry) => String(entry.id) === String(state.selected.id));
            }
            return false;
        });

        if (selectedCauseIndex >= 0) {
            state.causePage = Math.floor(selectedCauseIndex / state.causePageSize) + 1;
        }

        const pageData = paginate(causes, state.causePage, state.causePageSize);
        state.causePage = pageData.page;
        const pageNumbers = buildPageNumbers(pageData.page, pageData.totalPages);
        const rangeLabel = pageData.total
            ? `Hiển thị ${pageData.start + 1}-${pageData.end} trong số ${pageData.total} nguyên nhân`
            : 'Chưa có nguyên nhân nào';

        const symptomTags = (serviceItem.symptoms || []).length
            ? `${(serviceItem.symptoms || []).map((item) => `
                <button type="button" class="knowledge-tag ${state.selected.type === 'symptom' && String(state.selected.id) === String(item.id) ? 'is-selected' : ''}" data-select-type="symptom" data-select-id="${item.id}">
                    <i class="fas fa-stethoscope"></i>
                    <span>${escapeHtml(item.ten_trieu_chung || `Triệu chứng #${item.id}`)}</span>
                </button>
            `).join('')}
            <button type="button" class="knowledge-tag knowledge-tag--ghost" data-action="add-symptom" data-service-id="${serviceItem.id}">
                <i class="fas fa-plus"></i>
                <span>Thêm triệu chứng</span>
            </button>`
            : `<button type="button" class="knowledge-tag knowledge-tag--ghost" data-action="add-symptom" data-service-id="${serviceItem.id}">
                <i class="fas fa-plus"></i>
                <span>Thêm triệu chứng đầu tiên</span>
            </button>`;

        const causeCards = pageData.items.length ? pageData.items.map((item) => {
            const badge = causeBadge(item);
            return `
                <article class="knowledge-cause-card ${state.selected.type === 'cause' && String(state.selected.id) === String(item.id) ? 'is-selected' : ''} ${state.focus === 'cause' ? 'is-focus-spotlight' : ''}" data-select-type="cause" data-select-id="${item.id}">
                    <div class="knowledge-cause-card__header">
                        <div class="knowledge-cause-card__lead">
                            <div class="knowledge-cause-card__icon">
                                <i class="${causeIcon(item)}"></i>
                            </div>
                            <div class="knowledge-cause-card__copy">
                                <h3 class="knowledge-cause-card__title">${escapeHtml(item.ten_nguyen_nhan || `Nguyên nhân #${item.id}`)}</h3>
                                <p class="knowledge-cause-card__desc">${escapeHtml(describeCause(item))}</p>
                            </div>
                        </div>
                        <div class="knowledge-cause-card__header-actions">
                            <span class="knowledge-cause-card__badge ${badge.className}">${escapeHtml(badge.label)}</span>
                            <button type="button" class="knowledge-meta-action" data-action="edit-cause" data-id="${item.id}" aria-label="Sửa nguyên nhân">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="knowledge-meta-action is-danger" data-action="delete-cause" data-id="${item.id}" aria-label="Xóa nguyên nhân">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="knowledge-cause-card__symptoms">
                        ${(item.symptom_contexts || []).map((symptomItem) => `
                            <button type="button" class="knowledge-tag ${state.selected.type === 'symptom' && String(state.selected.id) === String(symptomItem.id) ? 'is-selected' : ''}" data-select-type="symptom" data-select-id="${symptomItem.id}">
                                <i class="fas fa-stethoscope"></i>
                                <span>${escapeHtml(symptomItem.ten_trieu_chung)}</span>
                            </button>
                        `).join('')}
                    </div>

                    <div class="knowledge-resolution-list">
                        ${(item.resolutions || []).length ? (item.resolutions || []).map((entry) => {
                            const hasPrice = Number(entry.gia_tham_khao || 0) > 0;
                            return `
                                <article class="knowledge-resolution ${state.selected.type === 'resolution' && String(state.selected.id) === String(entry.id) ? 'is-selected' : ''} ${state.focus === 'resolution' || state.focus === 'price' ? 'is-focus-spotlight' : ''} ${state.focus === 'price' ? 'is-price-focus' : ''}" data-select-type="resolution" data-select-id="${entry.id}">
                                    <div class="knowledge-resolution__copy">
                                        <div class="knowledge-resolution__titleline">
                                            <i class="fas fa-circle-check"></i>
                                            <h4 class="knowledge-resolution__title">${escapeHtml(entry.ten_huong_xu_ly || `Hướng xử lý #${entry.id}`)}</h4>
                                        </div>
                                        <p class="knowledge-resolution__desc">${escapeHtml(entry.mo_ta_cong_viec || 'Chưa có mô tả công việc chi tiết.')}</p>
                                    </div>

                                    <div class="knowledge-resolution__side">
                                        <span class="knowledge-resolution__price ${hasPrice ? '' : 'is-empty'}">${escapeHtml(entry.gia_label || money(entry.gia_tham_khao))}</span>
                                        <div class="knowledge-resolution__actions">
                                            <button type="button" class="knowledge-meta-action" data-action="edit-resolution" data-id="${entry.id}" aria-label="Sửa hướng xử lý">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button type="button" class="knowledge-meta-action is-danger" data-action="delete-resolution" data-id="${entry.id}" aria-label="Xóa hướng xử lý">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            `;
                        }).join('') : '<div class="knowledge-empty"><p class="mb-0">Nguyên nhân này chưa có hướng xử lý.</p></div>'}
                    </div>

                    <div class="knowledge-cause-card__footer is-dashed">
                        <button type="button" class="knowledge-inline-action is-dashed" data-action="add-resolution" data-id="${item.id}">
                            <i class="fas fa-plus"></i>
                            <span>Thêm hướng xử lý</span>
                        </button>
                    </div>
                </article>
            `;
        }).join('') : '<div class="knowledge-empty"><p class="mb-0">Chưa có nguyên nhân nào khớp trong dịch vụ này.</p></div>';

        const paginationMarkup = pageData.totalPages > 1 ? `
            <div class="knowledge-pagination">
                <span class="knowledge-pagination__summary">${escapeHtml(rangeLabel)}</span>
                <div class="knowledge-pagination__controls">
                    <button type="button" class="knowledge-page-btn" data-action="change-page" data-page="${pageData.page - 1}" ${pageData.page <= 1 ? 'disabled' : ''}>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    ${pageNumbers.map((page, index) => {
                        const previousPage = pageNumbers[index - 1];
                        const gap = previousPage && page - previousPage > 1 ? '<span class="knowledge-page-gap">...</span>' : '';
                        return `${gap}<button type="button" class="knowledge-page-btn ${page === pageData.page ? 'is-active' : ''}" data-action="change-page" data-page="${page}">${page}</button>`;
                    }).join('')}
                    <button type="button" class="knowledge-page-btn" data-action="change-page" data-page="${pageData.page + 1}" ${pageData.page >= pageData.totalPages ? 'disabled' : ''}>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        ` : `<div class="knowledge-pagination"><span class="knowledge-pagination__summary">${escapeHtml(rangeLabel)}</span></div>`;

        refs.tree.innerHTML = `
            <section class="knowledge-canvas-header">
                <div class="knowledge-canvas-header__copy">
                    <span class="knowledge-workspace__kicker">Kho tri thức của dịch vụ đang chọn</span>
                    <h1 class="knowledge-workspace__title">${escapeHtml(serviceItem.ten_dich_vu || `Dịch vụ #${serviceItem.id}`)}</h1>
                    <p class="knowledge-workspace__desc">${escapeHtml(describeService(serviceItem))}</p>
                </div>

                <div class="knowledge-canvas-header__actions">
                    <button type="button" class="knowledge-fab" data-action="add-contextual">
                        <i class="fas fa-plus"></i>
                        <span>${escapeHtml(contextualCreateLabel())}</span>
                    </button>
                    <p class="knowledge-canvas-header__hint">${escapeHtml(contextualCreateHint())}</p>
                </div>
            </section>

            <section class="knowledge-summary-chips">
                <span class="knowledge-summary-chip"><i class="fas fa-stethoscope"></i>${serviceItem.symptom_count || 0} triệu chứng</span>
                <span class="knowledge-summary-chip"><i class="fas fa-link"></i>${serviceItem.cause_count || 0} nguyên nhân</span>
                <span class="knowledge-summary-chip"><i class="fas fa-screwdriver-wrench"></i>${serviceItem.resolution_count || 0} hướng xử lý</span>
            </section>

            <section id="knowledgeSectionSymptoms" class="knowledge-section ${state.focus === 'symptom' ? 'is-focus-target' : ''}">
                <span class="knowledge-section-label">Triệu chứng đã xác định</span>
                <div class="knowledge-symptom-cloud">${symptomTags}</div>
            </section>

            <section id="knowledgeSectionCauses" class="knowledge-section ${state.focus === 'cause' || state.focus === 'resolution' || state.focus === 'price' ? 'is-focus-target' : ''}">
                <span class="knowledge-section-label">Nguyên nhân gốc & hướng xử lý</span>
                <label class="knowledge-inline-search" aria-label="Tìm trong dịch vụ đang chọn">
                    <i class="fas fa-search"></i>
                    <input type="search" id="knowledgeInlineSearch" value="${escapeHtml(state.search)}" placeholder="Tìm kiếm triệu chứng, nguyên nhân, hướng xử lý...">
                </label>
                <div class="knowledge-cause-stack">${causeCards}</div>
                <button type="button" class="knowledge-block-action" data-action="add-cause">
                    <i class="fas fa-circle-plus"></i>
                    <span>Thêm nguyên nhân gốc mới</span>
                </button>
                ${paginationMarkup}
            </section>
        `;
    };

    const renderInspectorV2 = () => {
        const serviceItem = findService(state.selectedServiceId, state.visibleItems);
        if (!serviceItem) {
            refs.inspector.innerHTML = '<div class="knowledge-side-card"><div class="knowledge-empty"><p class="mb-0">Chọn một dịch vụ để xem insight.</p></div></div>';
            return;
        }

        const topSymptoms = topSymptomsForService(serviceItem);
        const topCauses = topCausesForService(serviceItem);
        const topResolutions = topResolutionsForService(serviceItem);
        const missingPriceCause = firstMissingPriceCause(serviceItem);

        refs.inspector.innerHTML = `
            <div class="knowledge-side-card knowledge-side-card--muted">
                <span class="knowledge-side-card__eyebrow">Nội dung sửa đổi nhiều nhất</span>
                <div class="knowledge-side-section">
                    <span class="knowledge-side-section__label">Triệu chứng phổ biến</span>
                    ${renderInsightList(topSymptoms, 'symptom', 'fas fa-circle-exclamation', (item) => `+${item.nguyen_nhan_count || 0} nhánh`)}
                </div>
                <div class="knowledge-side-section">
                    <span class="knowledge-side-section__label">Nguyên nhân chính</span>
                    ${renderInsightList(topCauses, 'cause', 'fas fa-link', (item) => `+${item.resolution_count || 0} hướng xử lý`)}
                </div>
                <div class="knowledge-side-section">
                    <span class="knowledge-side-section__label">Hướng xử lý cập nhật</span>
                    ${renderInsightList(topResolutions, 'resolution', 'fas fa-screwdriver-wrench', (item) => item.gia_label || money(item.gia_tham_khao))}
                </div>
            </div>

            <div class="knowledge-side-card">
                <span class="knowledge-side-card__eyebrow">Tổng quan dịch vụ</span>
                <h3 class="knowledge-side-card__title">${escapeHtml(serviceItem.ten_dich_vu || `Dịch vụ #${serviceItem.id}`)}</h3>
                <div class="knowledge-side-metrics">
                    <div class="knowledge-side-metric">
                        <span>Trạng thái</span>
                        <strong>${escapeHtml(Number(serviceItem.trang_thai || 0) === 1 ? 'Đang hoạt động' : 'Đã ẩn')}</strong>
                    </div>
                    <div class="knowledge-side-metric">
                        <span>Triệu chứng</span>
                        <strong>${serviceItem.symptom_count || 0}</strong>
                    </div>
                    <div class="knowledge-side-metric">
                        <span>Nguyên nhân</span>
                        <strong>${serviceItem.cause_count || 0}</strong>
                    </div>
                    <div class="knowledge-side-metric">
                        <span>Hướng xử lý</span>
                        <strong>${serviceItem.resolution_count || 0}</strong>
                    </div>
                </div>
                <div class="knowledge-side-actions">
                    <button type="button" class="knowledge-side-action" data-action="add-symptom" data-service-id="${serviceItem.id}"><i class="fas fa-plus"></i><span>Thêm triệu chứng</span></button>
                    <button type="button" class="knowledge-side-action" data-action="add-cause"><i class="fas fa-link"></i><span>Thêm nguyên nhân</span></button>
                    <button type="button" class="knowledge-side-action" data-action="add-resolution"><i class="fas fa-screwdriver-wrench"></i><span>Thêm hướng xử lý + giá</span></button>
                </div>
            </div>

            <div class="knowledge-side-card knowledge-side-card--accent">
                <span class="knowledge-side-card__eyebrow">Trợ lý AI</span>
                <h3 class="knowledge-side-card__title">${escapeHtml(missingPriceCause ? 'Có nhánh đang thiếu giá tham khảo' : 'Luồng dữ liệu đang khá ổn')}</h3>
                <p class="knowledge-side-card__desc">${escapeHtml(missingPriceCause
                    ? `Nguyên nhân "${missingPriceCause.ten_nguyen_nhan}" đang có hướng xử lý chưa cập nhật giá. Bạn nên chốt giá tham khảo để đội điều phối dễ báo khách hơn.`
                    : 'Dịch vụ này đã có đủ triệu chứng, nguyên nhân và hướng xử lý cơ bản. Nếu có lỗi mới phát sinh, hãy thêm triệu chứng trước rồi nối tiếp nguyên nhân và giá ở cùng trang này.'
                )}</p>
                <button type="button" class="knowledge-side-action" data-action="${missingPriceCause ? 'select-price-gap' : 'add-contextual'}">
                    <i class="fas ${missingPriceCause ? 'fa-bolt' : 'fa-plus'}"></i>
                    <span>${escapeHtml(missingPriceCause ? 'Xem nhánh cần cập nhật' : contextualCreateLabel())}</span>
                </button>
            </div>
        `;
    };

    const render = () => {
        state.visibleItems = visibleTree();
        const selectedVisible = state.selectedServiceId && state.visibleItems.some((item) => String(item.id) === String(state.selectedServiceId));
        state.selectedServiceId = selectedVisible ? state.selectedServiceId : state.visibleItems[0]?.id ?? null;
        if (!getContext(state.selected, state.visibleItems)) {
            state.selected = state.selectedServiceId ? { type: 'service', id: Number(state.selectedServiceId) } : { type: null, id: null };
        }
        renderStats(state.visibleItems);
        renderRailV2(state.visibleItems);
        renderWorkspaceV2(findService(state.selectedServiceId, state.visibleItems));
        renderInspectorV2();
        syncUrl();
        if (state.pendingFocusScroll) {
            scrollToFocusTarget();
            state.pendingFocusScroll = false;
        }
    };

    const fetchTree = async () => {
        refs.rail.innerHTML = '<div class="knowledge-empty"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 mb-0">\u0110ang t\u1ea3i danh s\u00e1ch d\u1ecbch v\u1ee5...</p></div>';
        refs.tree.innerHTML = '<div class="knowledge-empty"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 mb-0">\u0110ang t\u1ea3i c\u00e2y tri th\u1ee9c s\u1eeda ch\u1eefa...</p></div>';
        refs.inspector.innerHTML = '<div class="knowledge-empty"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 mb-0">\u0110ang t\u1ea3i ng\u1eef c\u1ea3nh...</p></div>';
        try {
            const response = await callApi('/admin/tri-thuc-sua-chua');
            if (!response?.ok) throw new Error(errorMessage(response, 'Kh\u00f4ng th\u1ec3 t\u1ea3i c\u00e2y tri th\u1ee9c s\u1eeda ch\u1eefa.'));
            const data = response.data?.data || {};
            state.items = Array.isArray(data.items) ? data.items : [];
            state.serviceOptions = Array.isArray(data.service_options) ? data.service_options : [];
            state.symptomOptions = Array.isArray(data.symptom_options) ? data.symptom_options : [];
            state.causeOptions = Array.isArray(data.cause_options) ? data.cause_options : [];
            populateServiceOptions();
            populateCauseSymptoms(Array.from(cause.symptoms.selectedOptions || []).map((item) => Number(item.value)));
            populateResolutionCauses(resolution.cause.value || '');
            render();
        } catch (error) {
            const markup = `<div class="knowledge-empty text-danger"><i class="fas fa-circle-exclamation mb-3" style="font-size:1.8rem;"></i><p class="mb-0">${escapeHtml(error.message || 'Kh\u00f4ng th\u1ec3 t\u1ea3i c\u00e2y tri th\u1ee9c s\u1eeda ch\u1eefa.')}</p></div>`;
            refs.rail.innerHTML = markup;
            refs.tree.innerHTML = markup;
            refs.inspector.innerHTML = markup;
            showToast(error.message || 'Kh\u00f4ng th\u1ec3 t\u1ea3i c\u00e2y tri th\u1ee9c s\u1eeda ch\u1eefa.', 'error');
        }
    };

    const confirmDelete = async (title, text, confirmText) => {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title,
                text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'H\u1ee7y',
                confirmButtonColor: '#dc2626',
            });
            return result.isConfirmed;
        }
        return window.confirm(`${title}\n${text}`);
    };

    const openSymptomModal = (context) => {
        symptom.form.reset();
        populateServiceOptions();
        if (context) {
            symptom.id.value = context.symptom.id || '';
            symptom.label.textContent = 'S\u1eeda tri\u1ec7u ch\u1ee9ng';
            symptom.service.value = context.symptom.dich_vu_id ? String(context.symptom.dich_vu_id) : '';
            symptom.name.value = context.symptom.ten_trieu_chung || '';
        } else {
            symptom.id.value = '';
            symptom.label.textContent = 'Th\u00eam tri\u1ec7u ch\u1ee9ng';
            symptom.service.value = state.selectedServiceId ? String(state.selectedServiceId) : '';
        }
        symptom.modal.show();
    };

    const openCauseModal = (context) => {
        cause.form.reset();
        if (context) {
            cause.id.value = context.cause.id || '';
            cause.label.textContent = 'S\u1eeda nguy\u00ean nh\u00e2n';
            cause.name.value = context.cause.ten_nguyen_nhan || '';
            populateCauseSymptoms(context.cause.symptom_ids || []);
        } else {
            cause.id.value = '';
            cause.label.textContent = 'Th\u00eam nguy\u00ean nh\u00e2n';
            populateCauseSymptoms(state.selected.type === 'symptom' ? [state.selected.id] : []);
        }
        cause.modal.show();
    };

    const openResolutionModal = (context, causeId = '') => {
        resolution.form.reset();
        if (context) {
            resolution.id.value = context.resolution.id || '';
            resolution.label.textContent = 'S\u1eeda h\u01b0\u1edbng x\u1eed l\u00fd';
            populateResolutionCauses(context.resolution.nguyen_nhan_id || '');
            resolution.name.value = context.resolution.ten_huong_xu_ly || '';
            resolution.price.value = context.resolution.gia_tham_khao ?? '';
            resolution.desc.value = context.resolution.mo_ta_cong_viec || '';
        } else {
            resolution.id.value = '';
            resolution.label.textContent = 'Th\u00eam h\u01b0\u1edbng x\u1eed l\u00fd';
            populateResolutionCauses(causeId || (state.selected.type === 'cause' ? state.selected.id : ''));
        }
        resolution.modal.show();
    };

    const handleAction = async (button) => {
        const action = button.dataset.action;
        const id = button.dataset.id || button.dataset.serviceId || '';
        const symptomContext = action.includes('symptom') ? findNodeById('symptom', id) : null;
        const causeContext = action.includes('cause') || action.includes('resolution') ? findNodeById('cause', id) : null;
        const resolutionContext = action.includes('resolution') ? findNodeById('resolution', id) : null;

        if (action === 'change-page') {
            const nextPage = Number(button.dataset.page || 1);
            if (!Number.isFinite(nextPage)) return;
            state.causePage = nextPage;
            return render();
        }

        if (action === 'add-contextual') {
            if (state.selected.type === 'cause') return openResolutionModal(null, state.selected.id);
            if (state.selected.type === 'symptom') return openCauseModal(null);
            return openSymptomModal(null);
        }

        if (action === 'select-price-gap') {
            const serviceItem = findService(state.selectedServiceId, state.visibleItems);
            const missingCause = serviceItem ? firstMissingPriceCause(serviceItem) : null;
            if (!missingCause) return;

            const missingResolution = (missingCause.resolutions || []).find((entry) => Number(entry.gia_tham_khao || 0) <= 0);
            state.focus = 'price';
            state.pendingFocusScroll = true;
            state.selected = missingResolution
                ? { type: 'resolution', id: Number(missingResolution.id) }
                : { type: 'cause', id: Number(missingCause.id) };
            return render();
        }

        if (action === 'add-symptom') return openSymptomModal(null);
        if (action === 'edit-symptom') return openSymptomModal(symptomContext);
        if (action === 'add-cause') return openCauseModal(null);
        if (action === 'edit-cause') return openCauseModal(causeContext);
        if (action === 'add-resolution') return openResolutionModal(null, id);
        if (action === 'edit-resolution') return openResolutionModal(resolutionContext);

        if (action === 'delete-symptom' && symptomContext) {
            const ok = await confirmDelete('X\u00f3a tri\u1ec7u ch\u1ee9ng?', `Tri\u1ec7u ch\u1ee9ng "${symptomContext.symptom.ten_trieu_chung}" s\u1ebd b\u1ecb x\u00f3a kh\u1ecfi c\u00e2y tri th\u1ee9c.`, 'X\u00f3a tri\u1ec7u ch\u1ee9ng');
            if (!ok) return;
            const response = await callApi(`/admin/trieu-chung/${symptomContext.symptom.id}`, 'DELETE');
            if (!response?.ok) return showToast(errorMessage(response, 'Kh\u00f4ng th\u1ec3 x\u00f3a tri\u1ec7u ch\u1ee9ng.'), 'error');
            state.selected = { type: 'service', id: state.selectedServiceId };
            showToast(response.data?.message || '\u0110\u00e3 x\u00f3a tri\u1ec7u ch\u1ee9ng');
            return fetchTree();
        }

        if (action === 'delete-cause' && causeContext) {
            const ok = await confirmDelete('X\u00f3a nguy\u00ean nh\u00e2n?', `Nguy\u00ean nh\u00e2n "${causeContext.cause.ten_nguyen_nhan}" v\u00e0 c\u00e1c h\u01b0\u1edbng x\u1eed l\u00fd li\u00ean quan s\u1ebd b\u1ecb x\u00f3a.`, 'X\u00f3a nguy\u00ean nh\u00e2n');
            if (!ok) return;
            const response = await callApi(`/admin/nguyen-nhan/${causeContext.cause.id}`, 'DELETE');
            if (!response?.ok) return showToast(errorMessage(response, 'Kh\u00f4ng th\u1ec3 x\u00f3a nguy\u00ean nh\u00e2n.'), 'error');
            state.selected = { type: 'service', id: state.selectedServiceId };
            showToast(response.data?.message || '\u0110\u00e3 x\u00f3a nguy\u00ean nh\u00e2n');
            return fetchTree();
        }

        if (action === 'delete-resolution' && resolutionContext) {
            const ok = await confirmDelete('X\u00f3a h\u01b0\u1edbng x\u1eed l\u00fd?', `H\u01b0\u1edbng x\u1eed l\u00fd "${resolutionContext.resolution.ten_huong_xu_ly}" s\u1ebd b\u1ecb x\u00f3a.`, 'X\u00f3a h\u01b0\u1edbng x\u1eed l\u00fd');
            if (!ok) return;
            const response = await callApi(`/admin/huong-xu-ly/${resolutionContext.resolution.id}`, 'DELETE');
            if (!response?.ok) return showToast(errorMessage(response, 'Kh\u00f4ng th\u1ec3 x\u00f3a h\u01b0\u1edbng x\u1eed l\u00fd.'), 'error');
            state.selected = { type: 'service', id: state.selectedServiceId };
            showToast(response.data?.message || '\u0110\u00e3 x\u00f3a h\u01b0\u1edbng x\u1eed l\u00fd');
            return fetchTree();
        }
    };

    refs.addSymptom.addEventListener('click', () => openSymptomModal(null));
    refs.addCause.addEventListener('click', () => openCauseModal(null));
    refs.addResolution.addEventListener('click', () => openResolutionModal(null));

    refs.search.addEventListener('input', () => {
        clearTimeout(state.searchTimer);
        state.searchTimer = setTimeout(() => {
            state.search = refs.search.value.trim();
            state.causePage = 1;
            render();
        }, 180);
    });

    refs.serviceFilter.addEventListener('change', () => {
        state.serviceFilterId = refs.serviceFilter.value;
        state.causePage = 1;
        render();
    });

    refs.rail.addEventListener('click', (event) => {
        const toggleButton = event.target.closest('[data-toggle-services]');
        if (toggleButton) {
            state.servicesExpanded = toggleButton.dataset.toggleServices === 'expand';
            render();
            return;
        }

        const button = event.target.closest('[data-select-service]');
        if (!button) return;
        state.selectedServiceId = Number(button.dataset.selectService);
        state.selected = { type: 'service', id: Number(button.dataset.selectService) };
        state.causePage = 1;
        render();
    });

    refs.tree.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-action]');
        if (actionButton) return handleAction(actionButton);
        const selected = event.target.closest('[data-select-type][data-select-id]');
        if (!selected) return;
        state.selected = { type: selected.dataset.selectType, id: Number(selected.dataset.selectId) };
        const context = getContext(state.selected, state.items);
        if (context?.service?.id) state.selectedServiceId = Number(context.service.id);
        render();
    });

    refs.tree.addEventListener('input', (event) => {
        const inlineSearch = event.target.closest('#knowledgeInlineSearch');
        if (!inlineSearch) return;

        clearTimeout(state.searchTimer);
        state.searchTimer = setTimeout(() => {
            state.search = inlineSearch.value.trim();
            refs.search.value = state.search;
            state.causePage = 1;
            render();
        }, 180);
    });

    refs.inspector.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-action]');
        if (actionButton) return handleAction(actionButton);

        const selected = event.target.closest('[data-select-type][data-select-id]');
        if (!selected) return;

        state.selected = { type: selected.dataset.selectType, id: Number(selected.dataset.selectId) };
        const context = getContext(state.selected, state.items);
        if (context?.service?.id) state.selectedServiceId = Number(context.service.id);
        render();
    });

    cause.symptoms.addEventListener('change', () => {
        const count = Array.from(cause.symptoms.selectedOptions || []).length;
        cause.symptomsMeta.textContent = count ? `${count} tri\u1ec7u ch\u1ee9ng \u0111\u00e3 ch\u1ecdn` : 'Gi\u1eef Ctrl ho\u1eb7c Cmd \u0111\u1ec3 ch\u1ecdn nhi\u1ec1u tri\u1ec7u ch\u1ee9ng.';
    });

    resolution.cause.addEventListener('change', () => populateResolutionCauses(resolution.cause.value || ''));

    symptom.form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setButtonLoading(symptom.save, true, defaultButtonHtml.symptom);
        const names = extractNameLines(symptom.name.value);
        const isEdit = Boolean(symptom.id.value);

        if (!names.length) {
            setButtonLoading(symptom.save, false, defaultButtonHtml.symptom);
            return showToast('Vui l\u00f2ng nh\u1eadp \u00edt nh\u1ea5t 1 tri\u1ec7u ch\u1ee9ng.', 'error');
        }

        if (isEdit && names.length !== 1) {
            setButtonLoading(symptom.save, false, defaultButtonHtml.symptom);
            return showToast('Ch\u1ebf \u0111\u1ed9 s\u1eeda ch\u1ec9 h\u1ed7 tr\u1ee3 1 tri\u1ec7u ch\u1ee9ng tr\u00ean 1 d\u00f2ng.', 'error');
        }

        if (isEdit) {
            const response = await callApi(`/admin/trieu-chung/${symptom.id.value}`, 'PUT', {
                dich_vu_id: Number(symptom.service.value),
                ten_trieu_chung: names[0],
            });
            setButtonLoading(symptom.save, false, defaultButtonHtml.symptom);
            if (!response?.ok) return showToast(errorMessage(response, 'Kh\u00f4ng th\u1ec3 l\u01b0u tri\u1ec7u ch\u1ee9ng.'), 'error');
            const data = response.data?.data;
            if (data?.id) {
                state.selected = { type: 'symptom', id: Number(data.id) };
                state.selectedServiceId = Number(data.dich_vu_id || state.selectedServiceId || 0) || state.selectedServiceId;
            }
            symptom.modal.hide();
            showToast(response.data?.message || '\u0110\u00e3 l\u01b0u tri\u1ec7u ch\u1ee9ng');
            return fetchTree();
        }

        const result = await batchCreate({
            names,
            endpoint: '/admin/trieu-chung',
            fallbackMessage: 'Kh\u00f4ng th\u1ec3 th\u00eam tri\u1ec7u ch\u1ee9ng.',
            buildPayload: (name) => ({
                dich_vu_id: Number(symptom.service.value),
                ten_trieu_chung: name,
            }),
        });

        setButtonLoading(symptom.save, false, defaultButtonHtml.symptom);
        if (result.successes.length) {
            const last = result.successes[result.successes.length - 1];
            if (last?.id) {
                state.selected = { type: 'symptom', id: Number(last.id) };
                state.selectedServiceId = Number(last.dich_vu_id || state.selectedServiceId || 0) || state.selectedServiceId;
            }
            await fetchTree();
        }

        if (!result.failures.length) {
            symptom.modal.hide();
            symptom.form.reset();
        } else {
            symptom.name.value = result.failures.map((item) => item.name).join('\n');
            symptom.name.focus();
        }

        showBatchResult('tri\u1ec7u ch\u1ee9ng', names.length, result.successes, result.failures);
    });

    cause.form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setButtonLoading(cause.save, true, defaultButtonHtml.cause);
        const names = extractNameLines(cause.name.value);
        const symptomIds = Array.from(cause.symptoms.selectedOptions || []).map((item) => Number(item.value));
        const isEdit = Boolean(cause.id.value);

        if (!names.length) {
            setButtonLoading(cause.save, false, defaultButtonHtml.cause);
            return showToast('Vui l\u00f2ng nh\u1eadp \u00edt nh\u1ea5t 1 nguy\u00ean nh\u00e2n.', 'error');
        }

        if (isEdit && names.length !== 1) {
            setButtonLoading(cause.save, false, defaultButtonHtml.cause);
            return showToast('Ch\u1ebf \u0111\u1ed9 s\u1eeda ch\u1ec9 h\u1ed7 tr\u1ee3 1 nguy\u00ean nh\u00e2n tr\u00ean 1 d\u00f2ng.', 'error');
        }

        if (isEdit) {
            const response = await callApi(`/admin/nguyen-nhan/${cause.id.value}`, 'PUT', {
                ten_nguyen_nhan: names[0],
                symptom_ids: symptomIds,
            });
            setButtonLoading(cause.save, false, defaultButtonHtml.cause);
            if (!response?.ok) return showToast(errorMessage(response, 'Kh\u00f4ng th\u1ec3 l\u01b0u nguy\u00ean nh\u00e2n.'), 'error');
            const data = response.data?.data;
            if (data?.id) {
                state.selected = { type: 'cause', id: Number(data.id) };
                state.selectedServiceId = Number(data.service_ids?.[0] || state.selectedServiceId || 0) || state.selectedServiceId;
            }
            cause.modal.hide();
            showToast(response.data?.message || '\u0110\u00e3 l\u01b0u nguy\u00ean nh\u00e2n');
            return fetchTree();
        }

        const result = await batchCreate({
            names,
            endpoint: '/admin/nguyen-nhan',
            fallbackMessage: 'Kh\u00f4ng th\u1ec3 th\u00eam nguy\u00ean nh\u00e2n.',
            buildPayload: (name) => ({
                ten_nguyen_nhan: name,
                symptom_ids: symptomIds,
            }),
        });

        setButtonLoading(cause.save, false, defaultButtonHtml.cause);
        if (result.successes.length) {
            const last = result.successes[result.successes.length - 1];
            if (last?.id) {
                state.selected = { type: 'cause', id: Number(last.id) };
                state.selectedServiceId = Number(last.service_ids?.[0] || state.selectedServiceId || 0) || state.selectedServiceId;
            }
            await fetchTree();
        }

        if (!result.failures.length) {
            cause.modal.hide();
            cause.form.reset();
            populateCauseSymptoms(state.selected.type === 'symptom' ? [state.selected.id] : []);
        } else {
            cause.name.value = result.failures.map((item) => item.name).join('\n');
            cause.name.focus();
        }

        showBatchResult('nguy\u00ean nh\u00e2n', names.length, result.successes, result.failures);
    });

    resolution.form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setButtonLoading(resolution.save, true, defaultButtonHtml.resolution);
        const names = extractNameLines(resolution.name.value);
        const isEdit = Boolean(resolution.id.value);
        const sharedPayload = {
            nguyen_nhan_id: Number(resolution.cause.value),
            gia_tham_khao: resolution.price.value !== '' ? Number(resolution.price.value) : null,
            mo_ta_cong_viec: resolution.desc.value.trim(),
        };

        if (!names.length) {
            setButtonLoading(resolution.save, false, defaultButtonHtml.resolution);
            return showToast('Vui l\u00f2ng nh\u1eadp \u00edt nh\u1ea5t 1 h\u01b0\u1edbng x\u1eed l\u00fd.', 'error');
        }

        if (isEdit && names.length !== 1) {
            setButtonLoading(resolution.save, false, defaultButtonHtml.resolution);
            return showToast('Ch\u1ebf \u0111\u1ed9 s\u1eeda ch\u1ec9 h\u1ed7 tr\u1ee3 1 h\u01b0\u1edbng x\u1eed l\u00fd tr\u00ean 1 d\u00f2ng.', 'error');
        }

        if (isEdit) {
            const response = await callApi(`/admin/huong-xu-ly/${resolution.id.value}`, 'PUT', {
                ...sharedPayload,
                ten_huong_xu_ly: names[0],
            });
            setButtonLoading(resolution.save, false, defaultButtonHtml.resolution);
            if (!response?.ok) return showToast(errorMessage(response, 'Kh\u00f4ng th\u1ec3 l\u01b0u h\u01b0\u1edbng x\u1eed l\u00fd.'), 'error');
            const data = response.data?.data;
            if (data?.id) {
                state.selected = { type: 'resolution', id: Number(data.id) };
                state.selectedServiceId = Number(data.primary_service_id || state.selectedServiceId || 0) || state.selectedServiceId;
            }
            resolution.modal.hide();
            showToast(response.data?.message || '\u0110\u00e3 l\u01b0u h\u01b0\u1edbng x\u1eed l\u00fd');
            return fetchTree();
        }

        const result = await batchCreate({
            names,
            endpoint: '/admin/huong-xu-ly',
            fallbackMessage: 'Kh\u00f4ng th\u1ec3 th\u00eam h\u01b0\u1edbng x\u1eed l\u00fd.',
            buildPayload: (name) => ({
                ...sharedPayload,
                ten_huong_xu_ly: name,
            }),
        });

        setButtonLoading(resolution.save, false, defaultButtonHtml.resolution);
        if (result.successes.length) {
            const last = result.successes[result.successes.length - 1];
            if (last?.id) {
                state.selected = { type: 'resolution', id: Number(last.id) };
                state.selectedServiceId = Number(last.primary_service_id || state.selectedServiceId || 0) || state.selectedServiceId;
            }
            await fetchTree();
        }

        if (!result.failures.length) {
            resolution.modal.hide();
            resolution.form.reset();
            populateResolutionCauses('');
        } else {
            resolution.name.value = result.failures.map((item) => item.name).join('\n');
            resolution.name.focus();
        }

        showBatchResult('h\u01b0\u1edbng x\u1eed l\u00fd', names.length, result.successes, result.failures);
    });

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => render(), 120);
    });

    applyFriendlyCopy();
    syncFiltersFromUrl();
    fetchTree();
});
