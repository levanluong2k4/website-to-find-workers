import { callApi, showToast } from '../api.js';
import {
  buildWarrantyOptionsMarkup as sharedBuildWarrantyOptionsMarkup,
  escapeHtml,
  formatMoney,
  getBookingLaborItems,
  getBookingPartItems,
  getBookingServiceIds,
  getBookingServiceNames,
  getCustomerName,
  getNumeric,
  getPartQuantity,
  getPartUnitPrice,
} from './pricing-core.js';

export function createPricingModalController({
  getAllBookings,
  afterSubmit,
}) {
  const form = document.getElementById('formUpdateCosts');
  const modalEl = document.getElementById('modalCosts');
  const modalInstance = modalEl && typeof bootstrap !== 'undefined'
    ? new bootstrap.Modal(modalEl)
    : null;

  const bookingIdInput = document.getElementById('costBookingId');
  const inputTienThueXe = document.getElementById('inputTienThueXe');
  const inputGhiChuLinhKien = document.getElementById('inputGhiChuLinhKien');
  const laborItemsContainer = document.getElementById('laborItemsContainer');
  const partItemsContainer = document.getElementById('partItemsContainer');
  const addLaborItemButton = document.getElementById('addLaborItem');
  const laborSymptomSelect = document.getElementById('laborSymptomSelect');
  const laborCauseSelect = document.getElementById('laborCauseSelect');
  const laborResolutionSelect = document.getElementById('laborResolutionSelect');
  const laborSymptomPicker = document.getElementById('laborSymptomPicker');
  const laborSymptomTrigger = document.getElementById('laborSymptomTrigger');
  const laborSymptomTriggerLabel = document.getElementById('laborSymptomTriggerLabel');
  const laborSymptomPanel = document.getElementById('laborSymptomPanel');
  const laborSymptomSearch = document.getElementById('laborSymptomSearch');
  const laborSymptomOptions = document.getElementById('laborSymptomOptions');
  const laborCausePicker = document.getElementById('laborCausePicker');
  const laborCauseTrigger = document.getElementById('laborCauseTrigger');
  const laborCauseTriggerLabel = document.getElementById('laborCauseTriggerLabel');
  const laborCausePanel = document.getElementById('laborCausePanel');
  const laborCauseSearch = document.getElementById('laborCauseSearch');
  const laborCauseOptions = document.getElementById('laborCauseOptions');
  const laborCatalogStatus = document.getElementById('laborCatalogStatus');
  const laborResolutionPrice = document.getElementById('laborResolutionPrice');
  const addPartItemButton = document.getElementById('addPartItem');
  const partCatalogSearch = document.getElementById('partCatalogSearch');
  const partCatalogSuggestions = document.getElementById('partCatalogSuggestions');
  const partCatalogResults = document.getElementById('partCatalogResults');
  const partCatalogStatus = document.getElementById('partCatalogStatus');
  const addSelectedPartsButton = document.getElementById('addSelectedParts');
  const truckFeeContainer = document.getElementById('truckFeeContainer');
  const displayPhiDiLai = document.getElementById('displayPhiDiLai');
  const costEstimateTotal = document.getElementById('costEstimateTotal');
  const laborSubtotal = document.getElementById('laborSubtotal');
  const partsSubtotal = document.getElementById('partsSubtotal');
  const travelSubtotal = document.getElementById('travelSubtotal');
  const truckSummaryRow = document.getElementById('truckSummaryRow');
  const truckSubtotal = document.getElementById('truckSubtotal');
  const costCustomerName = document.getElementById('costCustomerName');
  const costServiceName = document.getElementById('costServiceName');
  const costDistanceHint = document.getElementById('costDistanceHint');
  const costBookingReference = document.getElementById('costBookingReference');
  const costServiceModeBadge = document.getElementById('costServiceModeBadge');
  const costTruckBadge = document.getElementById('costTruckBadge');
  const costDistanceBadge = document.getElementById('costDistanceBadge');
  const laborCountBadge = document.getElementById('laborCountBadge');
  const partCountBadge = document.getElementById('partCountBadge');
  const costDraftState = document.getElementById('costDraftState');
  const costSummaryHint = document.getElementById('costSummaryHint');
  const costWizardKicker = document.getElementById('costWizardKicker');
  const costWizardTitle = document.getElementById('costWizardTitle');
  const costWizardCopy = document.getElementById('costWizardCopy');
  const costWizardStepBadge = document.getElementById('costWizardStepBadge');
  const costWizardProgressFill = document.getElementById('costWizardProgressFill');
  const costStepTriggers = Array.from(document.querySelectorAll('[data-cost-step-trigger]'));
  const costStepPanels = Array.from(document.querySelectorAll('[data-cost-step-panel]'));
  const btnCostWizardPrev = document.getElementById('btnCostWizardPrev');
  const btnCostWizardNext = document.getElementById('btnCostWizardNext');
  const btnSubmitCostUpdate = document.getElementById('btnSubmitCostUpdate');

  let initialized = false;
  let currentCostBooking = null;
  let currentCostStep = 1;

  const laborCatalogState = {
    items: [],
    cache: new Map(),
    selectedSymptomId: null,
    selectedCauseId: null,
    selectedResolutionId: null,
  };
  const laborSearchablePickerState = {
    symptom: { items: [], keyword: '' },
    cause: { items: [], keyword: '' },
  };
  const partCatalogState = {
    items: [],
    cache: new Map(),
    selectedIds: new Set(),
    activeSuggestionIndex: -1,
    fallbackItems: [],
    fallbackCache: new Map(),
    searchRequestId: 0,
  };
  const pricingWizardSteps = {
    1: {
      kicker: 'B??c 1 tr�n 2',
      title: 'Ch?n ti?n c�ng',
      copy: 'Ch?n tri?u ch?ng, nguy�n nh�n v� h??ng x? l� ?? h�nh th�nh c�c d�ng ti?n c�ng tr??c khi sang b??c linh ki?n.',
    },
    2: {
      kicker: 'B??c 2 tr�n 2',
      title: 'Th�m linh ki?n',
      copy: 'Ch?n linh ki?n t? danh m?c ho?c th�m th? c�ng, r?i l?u b�o gi� ngay trong modal n�y.',
    },
  };
  const laborSearchablePickers = {
    symptom: {
      rootEl: laborSymptomPicker,
      triggerEl: laborSymptomTrigger,
      triggerLabelEl: laborSymptomTriggerLabel,
      panelEl: laborSymptomPanel,
      searchEl: laborSymptomSearch,
      optionsEl: laborSymptomOptions,
      selectEl: laborSymptomSelect,
      placeholder: 'Ch?n tri?u ch?ng',
      emptyText: 'Kh�ng t�m th?y tri?u ch?ng ph� h?p.',
      getLabel: (item) => item?.ten_trieu_chung || 'Tri?u ch?ng',
    },
    cause: {
      rootEl: laborCausePicker,
      triggerEl: laborCauseTrigger,
      triggerLabelEl: laborCauseTriggerLabel,
      panelEl: laborCausePanel,
      searchEl: laborCauseSearch,
      optionsEl: laborCauseOptions,
      selectEl: laborCauseSelect,
      placeholder: 'Ch?n nguy�n nh�n',
      emptyText: 'Kh�ng t�m th?y nguy�n nh�n ph� h?p.',
      getLabel: (item) => item?.ten_nguyen_nhan || 'Nguy�n nh�n',
    },
  };

  const normalizeDropdownSearchText = (value = '') => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('vi-VN')
    .trim();

  const buildWarrantyOptionsMarkup = (value = '') => sharedBuildWarrantyOptionsMarkup(value);

  const buildCostItemRowMarkup = (type, item = {}) => {
    const description = escapeHtml(item?.noi_dung || '');
    const isPart = type === 'part';
    const amount = isPart ? getPartUnitPrice(item) : getNumeric(item?.so_tien);
    const amountValue = amount > 0 ? amount : '';
    const formattedAmountValue = amount > 0 ? Number(amount).toLocaleString('vi-VN') : '0';
    const catalogResolutionId = isPart ? 0 : getNumeric(item?.huong_xu_ly_id);
    const catalogCauseId = isPart ? 0 : getNumeric(item?.nguyen_nhan_id);
    const catalogPartId = isPart ? getNumeric(item?.linh_kien_id) : 0;
    const serviceId = getNumeric(item?.dich_vu_id);
    const image = isPart ? escapeHtml(item?.hinh_anh || '') : '';
    const isCatalogItem = isPart && catalogPartId > 0;
    const isCatalogLaborItem = !isPart && catalogResolutionId > 0;
    const quantityValue = isPart ? getPartQuantity(item) : '';
    const warrantyValue = isPart && item?.bao_hanh_thang !== null && item?.bao_hanh_thang !== undefined
      ? getNumeric(item.bao_hanh_thang)
      : '';
    const partMeta = isCatalogItem ? 'T? danh m?c linh ki?n' : 'T? nh?p th? c�ng';
    const laborNote = !isPart ? escapeHtml(item?.mo_ta_cong_viec || '') : '';
    const laborSymptom = !isPart ? escapeHtml(item?.ten_trieu_chung || item?.trieu_chung || '') : '';
    const laborCause = !isPart
      ? escapeHtml(item?.ten_nguyen_nhan || item?.nguyen_nhan?.ten_nguyen_nhan || '')
      : '';
    const laborMeta = [laborSymptom, laborCause, laborNote].filter(Boolean).join(' � ')
      || (isCatalogLaborItem ? 'T? danh m?c ti?n c�ng' : 'D? li?u ti?n c�ng ?� l?u');

    if (isPart) {
      return `
        <div class="dispatch-line-item dispatch-pricing-v2-part-card" data-line-type="${type}" data-catalog-part-id="${catalogPartId || ''}">
          <input type="hidden" class="js-line-part-id" value="${catalogPartId || ''}">
          <input type="hidden" class="js-line-service-id" value="${serviceId || ''}">
          <input type="hidden" class="js-line-image" value="${image}">
          <div class="dispatch-pricing-v2-part-card-inner">
            <div class="dispatch-pricing-v2-part-main">
              <div class="dispatch-pricing-v2-field-label">T�n linh ki?n / V?t t?</div>
              <input type="text" class="dispatch-pricing-v2-input-dark js-line-description dispatch-pricing-v2-inline-input dispatch-pricing-v2-part-title" value="${description}" placeholder="Bo m?ch ch? Samsung" ${isCatalogItem ? 'readonly' : ''}>
              <div class="dispatch-pricing-v2-part-meta">${escapeHtml(partMeta)}</div>
            </div>
            <div class="dispatch-pricing-v2-part-col">
              <div class="dispatch-pricing-v2-field-label">??n gi� (?)</div>
              <input type="number" class="dispatch-pricing-v2-input-dark js-line-amount dispatch-pricing-v2-inline-input dispatch-pricing-v2-inline-input--price" value="${amountValue}" placeholder="650000" ${isCatalogItem ? 'readonly' : ''}>
            </div>
            <div class="dispatch-pricing-v2-part-col">
              <div class="dispatch-pricing-v2-field-label">S? l??ng</div>
              <div class="dispatch-pricing-v2-stepper">
                <button type="button" class="dispatch-pricing-v2-stepper-btn js-quantity-step" data-step="-1" aria-label="Gi?m s? l??ng">
                  <span class="material-symbols-outlined" style="font-size: 14px;">remove</span>
                </button>
                <input type="number" class="dispatch-pricing-v2-input-dark js-line-quantity dispatch-pricing-v2-inline-input" min="1" step="1" value="${quantityValue}" placeholder="1">
                <button type="button" class="dispatch-pricing-v2-stepper-btn js-quantity-step" data-step="1" aria-label="T?ng s? l??ng">
                  <span class="material-symbols-outlined" style="font-size: 14px;">add</span>
                </button>
              </div>
            </div>
            <div class="dispatch-pricing-v2-part-col">
              <div class="dispatch-pricing-v2-field-label">B?o h�nh</div>
              <select class="js-line-warranty dispatch-pricing-v2-select">
                ${buildWarrantyOptionsMarkup(warrantyValue)}
              </select>
            </div>
            <button type="button" class="dispatch-pricing-v2-part-remove dispatch-line-item__remove" aria-label="X�a d�ng">
              <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
            </button>
          </div>
        </div>
      `;
    }

    return `
      <div class="dispatch-line-item dispatch-pricing-v2-labor-row" data-line-type="${type}" data-catalog-resolution-id="${catalogResolutionId || ''}">
        <input type="hidden" class="js-line-resolution-id" value="${catalogResolutionId || ''}">
        <input type="hidden" class="js-line-cause-id" value="${catalogCauseId || ''}">
        <input type="hidden" class="js-line-service-id" value="${serviceId || ''}">
        <input type="hidden" class="js-line-work-note" value="${laborNote}">
        <input type="hidden" class="js-line-amount" value="${amountValue}">
        <div class="dispatch-pricing-v2-labor-main">
          <div class="dispatch-pricing-v2-field-label">T�n h?ng m?c c�ng</div>
          <input type="text" class="dispatch-pricing-v2-input-dark js-line-description dispatch-pricing-v2-inline-input" value="${description}" placeholder="Ch?n h??ng x? l� t? danh m?c" readonly>
          <div class="dispatch-pricing-v2-labor-row-meta">${laborMeta}</div>
        </div>
        <div class="dispatch-pricing-v2-labor-col dispatch-pricing-v2-labor-col--price">
          <div class="dispatch-pricing-v2-field-label">??n gi� (?)</div>
          <div class="dispatch-pricing-v2-labor-price">
            <span>${formattedAmountValue}</span>
            <span class="dispatch-pricing-v2-labor-price__suffix">?</span>
          </div>
        </div>
        <button type="button" class="dispatch-pricing-v2-labor-remove dispatch-line-item__remove" aria-label="X�a d�ng">
          <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
        </button>
      </div>
    `;
  };

  const populateCostItemRows = (container, type, items = []) => {
    if (container) {
      container.innerHTML = items.map((item) => buildCostItemRowMarkup(type, item)).join('');
    }
  };

  const appendCostItemRow = (container, type, item = {}) => {
    container?.insertAdjacentHTML('beforeend', buildCostItemRowMarkup(type, item));
  };

  const ensureMinimumCostRows = () => {};

  const sumDraftLineAmounts = (container) => Array.from(container?.querySelectorAll('.dispatch-line-item') || [])
    .reduce((total, row) => {
      const unitPrice = getNumeric(row.querySelector('.js-line-amount')?.value);
      const quantity = Math.max(1, Math.trunc(getNumeric(row.querySelector('.js-line-quantity')?.value || 1)));
      return total + (row.querySelector('.js-line-quantity') ? unitPrice * quantity : unitPrice);
    }, 0);

  const countDraftLineRows = (container) => Array.from(container?.querySelectorAll('.dispatch-line-item') || []).length;

  const collectCostItems = (container, type) => {
    let hasIncomplete = false;
    const items = [];

    Array.from(container?.querySelectorAll('.dispatch-line-item') || []).forEach((row) => {
      const description = row.querySelector('.js-line-description')?.value.trim() || '';
      const amountRaw = row.querySelector('.js-line-amount')?.value || '';
      const amount = getNumeric(amountRaw);
      const quantityRaw = row.querySelector('.js-line-quantity')?.value || '';
      const quantity = Math.max(1, Math.trunc(getNumeric(quantityRaw || 1)));
      const warrantyRaw = row.querySelector('.js-line-warranty')?.value || '';
      const resolutionIdRaw = row.querySelector('.js-line-resolution-id')?.value || '';
      const causeIdRaw = row.querySelector('.js-line-cause-id')?.value || '';
      const partIdRaw = row.querySelector('.js-line-part-id')?.value || '';
      const serviceIdRaw = row.querySelector('.js-line-service-id')?.value || '';
      const image = row.querySelector('.js-line-image')?.value || '';
      const workNote = row.querySelector('.js-line-work-note')?.value || '';
      const hasAnyValue = description !== '' || amountRaw !== '' || warrantyRaw !== '' || resolutionIdRaw !== '';

      if (!hasAnyValue) {
        return;
      }

      if (description === '' || amountRaw === '' || amount <= 0) {
        hasIncomplete = true;
        return;
      }

      const item = { noi_dung: description, so_tien: amount };

      if (type === 'labor') {
        item.huong_xu_ly_id = getNumeric(resolutionIdRaw) || null;
        item.nguyen_nhan_id = getNumeric(causeIdRaw) || null;
        item.dich_vu_id = getNumeric(serviceIdRaw) || null;
        item.mo_ta_cong_viec = workNote || null;
      }

      if (type === 'part') {
        item.so_luong = quantity;
        item.don_gia = amount;
        item.so_tien = amount * quantity;
      }

      if (type === 'part' && warrantyRaw !== '') {
        item.bao_hanh_thang = Math.max(0, Math.trunc(getNumeric(warrantyRaw)));
      }

      if (type === 'part' && getNumeric(partIdRaw) > 0) {
        item.linh_kien_id = getNumeric(partIdRaw);
      }

      if (type === 'part' && getNumeric(serviceIdRaw) > 0) {
        item.dich_vu_id = getNumeric(serviceIdRaw);
      }

      if (type === 'part' && image) {
        item.hinh_anh = image;
      }

      items.push(item);
    });

    return { items, hasIncomplete };
  };

  const getDraftLaborIds = () => new Set(
    Array.from(laborItemsContainer?.querySelectorAll('.js-line-resolution-id') || [])
      .map((input) => getNumeric(input?.value))
      .filter((id) => id > 0),
  );

  const getLaborCatalogSymptoms = () => {
    const symptomMap = new Map();

    laborCatalogState.items.forEach((item) => {
      (Array.isArray(item?.trieu_chungs) ? item.trieu_chungs : []).forEach((symptom) => {
        const symptomId = getNumeric(symptom?.id);
        if (symptomId <= 0 || symptomMap.has(symptomId)) {
          return;
        }

        symptomMap.set(symptomId, {
          id: symptomId,
          ten_trieu_chung: symptom?.ten_trieu_chung || 'Tri?u ch?ng',
          dich_vu_id: getNumeric(symptom?.dich_vu_id) || null,
        });
      });
    });

    return Array.from(symptomMap.values()).sort((left, right) => (
      String(left.ten_trieu_chung || '').localeCompare(String(right.ten_trieu_chung || ''), 'vi')
    ));
  };

  const getLaborCatalogItemsBySymptom = () => {
    const selectedSymptomId = getNumeric(laborCatalogState.selectedSymptomId);

    if (selectedSymptomId <= 0) {
      return laborCatalogState.items;
    }

    return laborCatalogState.items.filter((item) => (
      Array.isArray(item?.trieu_chungs)
        && item.trieu_chungs.some((symptom) => getNumeric(symptom?.id) === selectedSymptomId)
    ));
  };

  const getLaborCatalogCauses = () => {
    if (getNumeric(laborCatalogState.selectedSymptomId) <= 0) {
      return [];
    }

    const causeMap = new Map();

    getLaborCatalogItemsBySymptom().forEach((item) => {
      const causeId = getNumeric(item?.nguyen_nhan?.id || item?.nguyen_nhan_id);
      if (causeId <= 0 || causeMap.has(causeId)) {
        return;
      }

      causeMap.set(causeId, {
        id: causeId,
        ten_nguyen_nhan: item?.nguyen_nhan?.ten_nguyen_nhan || 'Nguy�n nh�n',
      });
    });

    return Array.from(causeMap.values()).sort((left, right) => (
      String(left.ten_nguyen_nhan || '').localeCompare(String(right.ten_nguyen_nhan || ''), 'vi')
    ));
  };

  const getLaborCatalogResolutions = () => {
    const selectedCauseId = getNumeric(laborCatalogState.selectedCauseId);
    if (selectedCauseId <= 0) {
      return [];
    }

    return getLaborCatalogItemsBySymptom()
      .filter((item) => getNumeric(item?.nguyen_nhan?.id || item?.nguyen_nhan_id) === selectedCauseId)
      .sort((left, right) => (
        String(left?.ten_huong_xu_ly || '').localeCompare(String(right?.ten_huong_xu_ly || ''), 'vi')
      ));
  };

  const getSelectedLaborResolution = () => (
    getLaborCatalogResolutions().find((item) => getNumeric(item?.id) === getNumeric(laborCatalogState.selectedResolutionId))
  );

  const getLaborSearchablePickerState = (type) => laborSearchablePickerState[type] || null;
  const getLaborSearchablePickerConfig = (type) => laborSearchablePickers[type] || null;

  const closeLaborSearchablePicker = (type, { resetKeyword = true } = {}) => {
    const picker = getLaborSearchablePickerConfig(type);
    const state = getLaborSearchablePickerState(type);

    if (!picker) {
      return;
    }

    picker.panelEl?.setAttribute('hidden', 'hidden');
    picker.triggerEl?.setAttribute('aria-expanded', 'false');
    picker.rootEl?.classList.remove('is-open');

    if (resetKeyword && state) {
      state.keyword = '';
      if (picker.searchEl) {
        picker.searchEl.value = '';
      }
    }
  };

  const closeAllLaborSearchablePickers = (exceptType = null) => {
    Object.keys(laborSearchablePickers).forEach((type) => {
      if (type !== exceptType) {
        closeLaborSearchablePicker(type);
      }
    });
  };

  const getVisibleLaborSearchablePickerItems = (type) => {
    const picker = getLaborSearchablePickerConfig(type);
    const state = getLaborSearchablePickerState(type);

    if (!picker || !state) {
      return [];
    }

    const keyword = normalizeDropdownSearchText(state.keyword);
    if (!keyword) {
      return state.items;
    }

    return state.items.filter((item) => (
      normalizeDropdownSearchText(picker.getLabel(item)).includes(keyword)
    ));
  };

  const renderLaborSearchablePickerOptions = (type) => {
    const picker = getLaborSearchablePickerConfig(type);
    const state = getLaborSearchablePickerState(type);

    if (!picker?.optionsEl || !state) {
      return;
    }

    const items = getVisibleLaborSearchablePickerItems(type);
    const selectedValue = String(picker.selectEl?.value || '');

    if (!items.length) {
      picker.optionsEl.innerHTML = `<div class="dispatch-search-picker__empty">${escapeHtml(picker.emptyText)}</div>`;
      return;
    }

    picker.optionsEl.innerHTML = items.map((item) => {
      const optionValue = String(getNumeric(item?.id));
      const isSelected = optionValue !== '0' && optionValue === selectedValue;

      return `
        <button type="button" class="dispatch-search-picker__option ${isSelected ? 'is-selected' : ''}" data-picker-type="${type}" data-picker-value="${optionValue}" role="option" aria-selected="${isSelected ? 'true' : 'false'}">
          ${escapeHtml(picker.getLabel(item))}
        </button>
      `;
    }).join('');
  };

  const syncLaborSearchablePicker = (type, items = [], selectedId = null, { disabled = false } = {}) => {
    const picker = getLaborSearchablePickerConfig(type);
    const state = getLaborSearchablePickerState(type);

    if (!picker || !state) {
      return;
    }

    state.items = Array.isArray(items) ? items.slice() : [];
    const selectedItem = state.items.find((item) => getNumeric(item?.id) === getNumeric(selectedId));

    if (picker.triggerLabelEl) {
      picker.triggerLabelEl.textContent = selectedItem ? picker.getLabel(selectedItem) : picker.placeholder;
    }

    if (picker.triggerEl) {
      picker.triggerEl.disabled = disabled;
    }

    if (picker.selectEl) {
      picker.selectEl.disabled = disabled;
    }

    if (disabled) {
      closeLaborSearchablePicker(type);
    }

    renderLaborSearchablePickerOptions(type);
  };

  const openLaborSearchablePicker = (type) => {
    const picker = getLaborSearchablePickerConfig(type);

    if (!picker?.panelEl || !picker.triggerEl || picker.triggerEl.disabled) {
      return;
    }

    closeAllLaborSearchablePickers(type);
    picker.panelEl.removeAttribute('hidden');
    picker.rootEl?.classList.add('is-open');
    picker.triggerEl.setAttribute('aria-expanded', 'true');
    renderLaborSearchablePickerOptions(type);

    window.requestAnimationFrame(() => {
      picker.searchEl?.focus();
      picker.searchEl?.select();
    });
  };

  const toggleLaborSearchablePicker = (type) => {
    const picker = getLaborSearchablePickerConfig(type);

    if (!picker?.panelEl) {
      return;
    }

    if (picker.panelEl.hasAttribute('hidden')) {
      openLaborSearchablePicker(type);
      return;
    }

    closeLaborSearchablePicker(type);
  };

  const applyLaborSearchablePickerSelection = (type, value) => {
    const picker = getLaborSearchablePickerConfig(type);

    if (!picker?.selectEl) {
      return;
    }

    picker.selectEl.value = String(value || '');
    closeLaborSearchablePicker(type);
    picker.selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    picker.triggerEl?.focus();
  };

  const updateLaborCatalogPicker = () => {
    const symptoms = getLaborCatalogSymptoms();
    const currentSymptomId = getNumeric(laborCatalogState.selectedSymptomId);
    if (currentSymptomId > 0 && !symptoms.some((symptom) => getNumeric(symptom.id) === currentSymptomId)) {
      laborCatalogState.selectedSymptomId = null;
    }

    const causes = getLaborCatalogCauses();
    const currentCauseId = getNumeric(laborCatalogState.selectedCauseId);
    if (currentCauseId > 0 && !causes.some((cause) => getNumeric(cause.id) === currentCauseId)) {
      laborCatalogState.selectedCauseId = null;
    }

    const resolutions = getLaborCatalogResolutions();
    const currentResolutionId = getNumeric(laborCatalogState.selectedResolutionId);
    if (currentResolutionId > 0 && !resolutions.some((item) => getNumeric(item.id) === currentResolutionId)) {
      laborCatalogState.selectedResolutionId = null;
    }

    if (laborSymptomSelect) {
      laborSymptomSelect.innerHTML = [
        '<option value="">Ch?n tri?u ch?ng</option>',
        ...symptoms.map((symptom) => (
          `<option value="${symptom.id}" ${getNumeric(laborCatalogState.selectedSymptomId) === getNumeric(symptom.id) ? 'selected' : ''}>${escapeHtml(symptom.ten_trieu_chung || 'Tri?u ch?ng')}</option>`
        )),
      ].join('');
      laborSymptomSelect.disabled = symptoms.length === 0;
    }
    syncLaborSearchablePicker('symptom', symptoms, laborCatalogState.selectedSymptomId, { disabled: symptoms.length === 0 });

    if (laborCauseSelect) {
      laborCauseSelect.innerHTML = [
        '<option value="">Ch?n nguy�n nh�n</option>',
        ...causes.map((cause) => (
          `<option value="${cause.id}" ${getNumeric(laborCatalogState.selectedCauseId) === getNumeric(cause.id) ? 'selected' : ''}>${escapeHtml(cause.ten_nguyen_nhan || 'Nguy�n nh�n')}</option>`
        )),
      ].join('');
      laborCauseSelect.disabled = causes.length === 0;
    }
    syncLaborSearchablePicker('cause', causes, laborCatalogState.selectedCauseId, { disabled: causes.length === 0 });

    if (laborResolutionSelect) {
      laborResolutionSelect.innerHTML = [
        '<option value="">Ch?n h??ng x? l�</option>',
        ...resolutions.map((resolution) => (
          `<option value="${resolution.id}" ${getNumeric(laborCatalogState.selectedResolutionId) === getNumeric(resolution.id) ? 'selected' : ''}>${escapeHtml(resolution.ten_huong_xu_ly || 'H??ng x? l�')}</option>`
        )),
      ].join('');
      laborResolutionSelect.disabled = resolutions.length === 0;
    }

    const selectedResolution = getSelectedLaborResolution();
    const alreadyAdded = selectedResolution && getDraftLaborIds().has(getNumeric(selectedResolution.id));
    if (addLaborItemButton) {
      addLaborItemButton.disabled = !selectedResolution || getNumeric(selectedResolution?.gia_tham_khao) <= 0 || alreadyAdded;
    }

    if (!laborCatalogState.items.length) {
      if (laborCatalogStatus) {
        laborCatalogStatus.textContent = currentCostBooking
          ? 'Ch?a c� danh m?c ti?n c�ng cho nh�m d?ch v? c?a ??n n�y.'
          : 'M? ??n ?? t?i danh m?c ti?n c�ng.';
      }
      if (laborResolutionPrice) {
        laborResolutionPrice.textContent = 'C?n ??ng b? danh m?c h??ng x? l� ?? th? ch?n t? dropdown.';
      }
      return;
    }

    if (!laborCatalogState.selectedSymptomId) {
      laborCatalogStatus.textContent = 'Ch?n tri?u ch?ng tr??c ?? l?c nguy�n nh�n t??ng ?ng.';
      laborResolutionPrice.textContent = `H? th?ng ?ang c� ${laborCatalogState.items.length} h??ng x? l� theo d?ch v? c?a ??n.`;
      return;
    }

    if (!laborCatalogState.selectedCauseId) {
      laborCatalogStatus.textContent = 'Ti?p t?c ch?n nguy�n nh�n ?? thu h?p danh s�ch h??ng x? l�.';
      laborResolutionPrice.textContent = `${causes.length} nguy�n nh�n ph� h?p v?i tri?u ch?ng ?ang ch?n.`;
      return;
    }

    if (!selectedResolution) {
      laborCatalogStatus.textContent = 'Ch?n h??ng x? l� ?? th�m ?�ng d�ng ti?n c�ng.';
      laborResolutionPrice.textContent = `${resolutions.length} h??ng x? l� ?ang kh?p v?i tri?u ch?ng v� nguy�n nh�n.`;
      return;
    }

    laborCatalogStatus.textContent = selectedResolution.mo_ta_cong_viec
      ? selectedResolution.mo_ta_cong_viec
      : 'H??ng x? l� n�y ch?a c� m� t? c�ng vi?c chi ti?t.';
    laborResolutionPrice.textContent = alreadyAdded
      ? 'H??ng x? l� n�y ?� c� trong b?ng ti?n c�ng.'
      : `Gi� tham kh?o: ${formatMoney(selectedResolution.gia_tham_khao)}. Ch?n "Th�m ti?n c�ng" ?? ??a v�o b?ng.`;
  };

  const loadLaborCatalogForBooking = async (booking) => {
    const activeBookingId = getNumeric(booking?.id);
    const serviceIds = getBookingServiceIds(booking);
    const cacheKey = serviceIds.slice().sort((a, b) => a - b).join(',');

    laborCatalogState.selectedSymptomId = null;
    laborCatalogState.selectedCauseId = null;
    laborCatalogState.selectedResolutionId = null;

    if (!serviceIds.length) {
      laborCatalogState.items = [];
      updateLaborCatalogPicker();
      return;
    }

    if (laborCatalogState.cache.has(cacheKey)) {
      if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
        return;
      }
      laborCatalogState.items = laborCatalogState.cache.get(cacheKey) || [];
      updateLaborCatalogPicker();
      return;
    }

    laborCatalogState.items = [];
    updateLaborCatalogPicker();
    laborCatalogStatus.textContent = '?ang t?i danh m?c tri?u ch?ng, nguy�n nh�n v� h??ng x? l�...';
    laborResolutionPrice.textContent = 'H? th?ng ?ang chu?n b? dropdown ti?n c�ng cho ??n n�y.';

    try {
      const params = new URLSearchParams();
      serviceIds.forEach((serviceId) => params.append('dich_vu_ids[]', serviceId));
      const response = await callApi(`/huong-xu-ly?${params.toString()}`, 'GET');

      if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
        return;
      }

      if (!response.ok) {
        throw new Error(response.data?.message || 'Kh�ng th? t?i danh m?c ti?n c�ng.');
      }

      laborCatalogState.items = Array.isArray(response.data) ? response.data : [];
      laborCatalogState.cache.set(cacheKey, laborCatalogState.items);
      updateLaborCatalogPicker();
    } catch (error) {
      if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
        return;
      }

      laborCatalogState.items = [];
      updateLaborCatalogPicker();
      showToast(error.message || 'L?i khi t?i h??ng x? l� theo d?ch v?.', 'error');
    }
  };

  const addSelectedLaborCatalogItem = () => {
    const selectedResolution = getSelectedLaborResolution();

    if (!selectedResolution) {
      showToast('Vui l�ng ch?n ??y ?? tri?u ch?ng, nguy�n nh�n v� h??ng x? l�.', 'error');
      return;
    }

    const resolutionId = getNumeric(selectedResolution.id);
    if (resolutionId <= 0 || getNumeric(selectedResolution.gia_tham_khao) <= 0) {
      showToast('H??ng x? l� n�y ch?a c� gi� tham kh?o ?? th�m v�o ti?n c�ng.', 'error');
      return;
    }

    if (getDraftLaborIds().has(resolutionId)) {
      showToast('H??ng x? l� n�y ?� c� trong b?ng ti?n c�ng.', 'error');
      updateLaborCatalogPicker();
      return;
    }

    const selectedSymptom = getLaborCatalogSymptoms().find((symptom) => (
      getNumeric(symptom.id) === getNumeric(laborCatalogState.selectedSymptomId)
    ));

    appendCostItemRow(laborItemsContainer, 'labor', {
      huong_xu_ly_id: resolutionId,
      nguyen_nhan_id: getNumeric(selectedResolution?.nguyen_nhan?.id || selectedResolution?.nguyen_nhan_id),
      dich_vu_id: getNumeric(selectedSymptom?.dich_vu_id || selectedResolution?.dich_vus?.[0]?.id),
      mo_ta_cong_viec: selectedResolution.mo_ta_cong_viec || '',
      ten_trieu_chung: selectedSymptom?.ten_trieu_chung || '',
      ten_nguyen_nhan: selectedResolution?.nguyen_nhan?.ten_nguyen_nhan || '',
      noi_dung: selectedResolution.ten_huong_xu_ly || 'H??ng x? l�',
      so_tien: getNumeric(selectedResolution.gia_tham_khao),
    });

    laborCatalogState.selectedResolutionId = null;
    updateLaborCatalogPicker();
    updateCostEstimate();
  };

  const updateSelectedPartsButtonState = () => {
    const selectedCount = partCatalogState.selectedIds.size;

    if (!addSelectedPartsButton) {
      return;
    }

    addSelectedPartsButton.disabled = selectedCount === 0;
    addSelectedPartsButton.innerHTML = `
      <span class="material-symbols-outlined">playlist_add</span>
      ${selectedCount > 0 ? `Th�m ${selectedCount} linh ki?n` : 'Th�m linh ki?n ?� ch?n'}
    `;
  };

  const getPartCatalogKeyword = () => String(partCatalogSearch?.value || '').trim().toLocaleLowerCase('vi-VN');
  const getPartCatalogItemName = (item) => String(item?.ten_linh_kien || '').trim();
  const getPartCatalogServiceName = (item) => item?.dich_vu?.ten_dich_vu || (currentCostBooking ? getBookingServiceNames(currentCostBooking) : 'D?ch v?');

  const getVisiblePartCatalogItems = () => {
    const keyword = getPartCatalogKeyword();
    const filteredItems = partCatalogState.items.filter((item) => getPartCatalogItemName(item)
      .toLocaleLowerCase('vi-VN')
      .includes(keyword));

    if (!keyword) {
      return filteredItems;
    }

    return filteredItems.slice().sort((left, right) => {
      const nameA = getPartCatalogItemName(left).toLocaleLowerCase('vi-VN');
      const nameB = getPartCatalogItemName(right).toLocaleLowerCase('vi-VN');
      const prefixDiff = Number(!nameA.startsWith(keyword)) - Number(!nameB.startsWith(keyword));

      if (prefixDiff !== 0) {
        return prefixDiff;
      }

      const matchDiff = nameA.indexOf(keyword) - nameB.indexOf(keyword);
      if (matchDiff !== 0) {
        return matchDiff;
      }

      const lengthDiff = nameA.length - nameB.length;
      if (lengthDiff !== 0) {
        return lengthDiff;
      }

      return nameA.localeCompare(nameB, 'vi');
    });
  };

  const getKnownPartCatalogItems = () => {
    const itemMap = new Map();

    [...partCatalogState.items, ...partCatalogState.fallbackItems].forEach((item) => {
      const partId = getNumeric(item?.id);
      if (partId > 0 && !itemMap.has(partId)) {
        itemMap.set(partId, item);
      }
    });

    return Array.from(itemMap.values());
  };

  const getSuggestionPartCatalogItems = (visibleItems = getVisiblePartCatalogItems()) => (
    visibleItems.length ? visibleItems : partCatalogState.fallbackItems
  );

  const hasLoadedFallbackSuggestionsForKeyword = (keyword) => {
    const cacheKey = String(keyword || '').trim().toLocaleLowerCase('vi-VN');
    return cacheKey !== '' && partCatalogState.fallbackCache.has(cacheKey);
  };

  const setPartCatalogSuggestionsVisible = (visible) => {
    if (!partCatalogSuggestions) {
      return;
    }

    partCatalogSuggestions.hidden = !visible;

    if (partCatalogSearch) {
      partCatalogSearch.setAttribute('aria-expanded', visible ? 'true' : 'false');
    }
  };

  const hidePartCatalogSuggestions = () => {
    partCatalogState.activeSuggestionIndex = -1;

    if (!partCatalogSuggestions) {
      return;
    }

    partCatalogSuggestions.innerHTML = '';
    setPartCatalogSuggestionsVisible(false);
  };

  const renderPartCatalogSuggestions = (visibleItems = getSuggestionPartCatalogItems()) => {
    if (!partCatalogSuggestions) {
      return;
    }

    const rawKeyword = String(partCatalogSearch?.value || '').trim();
    if (!rawKeyword) {
      hidePartCatalogSuggestions();
      return;
    }

    if (!visibleItems.length) {
      partCatalogSuggestions.innerHTML = '<div class="dispatch-part-suggestion-empty">Kh�ng t�m th?y linh ki?n ph� h?p v?i t? kh�a ?ang nh?p.</div>';
      setPartCatalogSuggestionsVisible(true);
      return;
    }

    const suggestionItems = visibleItems.slice(0, 6);
    if (partCatalogState.activeSuggestionIndex >= suggestionItems.length) {
      partCatalogState.activeSuggestionIndex = suggestionItems.length - 1;
    }

    partCatalogSuggestions.innerHTML = suggestionItems.map((item, index) => {
      const partId = getNumeric(item?.id);
      const hasPrice = getNumeric(item?.gia) > 0;
      const isSelected = partCatalogState.selectedIds.has(partId);
      const serviceName = getPartCatalogServiceName(item);

      return `
        <button
          type="button"
          class="dispatch-part-suggestion js-part-catalog-suggestion ${index === partCatalogState.activeSuggestionIndex ? 'is-active' : ''} ${isSelected ? 'is-selected' : ''} ${hasPrice ? '' : 'is-disabled'}"
          data-part-id="${partId}"
          data-index="${index}"
          ${hasPrice ? '' : 'disabled'}
        >
          <span class="dispatch-part-suggestion__thumb">
            ${item?.hinh_anh
              ? `<img src="${escapeHtml(item.hinh_anh)}" alt="${escapeHtml(getPartCatalogItemName(item) || 'Linh ki?n')}">`
              : '<span class="material-symbols-outlined">image_not_supported</span>'}
          </span>
          <span class="dispatch-part-suggestion__body">
            <span class="dispatch-part-suggestion__title">${escapeHtml(getPartCatalogItemName(item) || 'Linh ki?n')}</span>
            <span class="dispatch-part-suggestion__meta">${escapeHtml(serviceName)}</span>
          </span>
          <span class="dispatch-part-suggestion__aside">
            <strong class="dispatch-part-suggestion__price">${hasPrice ? formatMoney(item?.gia) : 'Ch?a c� gi�'}</strong>
            ${isSelected ? '<span class="dispatch-part-suggestion__badge">?� ch?n</span>' : ''}
          </span>
        </button>
      `;
    }).join('');

    setPartCatalogSuggestionsVisible(true);
  };

  const renderPartCatalogResults = () => {
    if (!partCatalogResults || !partCatalogStatus) {
      return;
    }

    const rawKeyword = String(partCatalogSearch?.value || '').trim();
    const visibleItems = getVisiblePartCatalogItems();
    const suggestionItems = getSuggestionPartCatalogItems(visibleItems);
    const isShowingFallback = !visibleItems.length && suggestionItems.length > 0;

    if (!partCatalogState.items.length) {
      if (!currentCostBooking) {
        partCatalogStatus.textContent = 'M? ??n ?? t?i danh m?c linh ki?n ?�ng theo d?ch v? c?a ??n.';
      } else if (isShowingFallback) {
        partCatalogStatus.textContent = `D?ch v? c?a ??n n�y ch?a c� linh ki?n m?u. ?ang g?i � ${suggestionItems.length} linh ki?n t? to�n b? kho theo t? kh�a "${rawKeyword}".`;
      } else if (rawKeyword && hasLoadedFallbackSuggestionsForKeyword(rawKeyword)) {
        partCatalogStatus.textContent = `Kh�ng t�m th?y linh ki?n n�o trong to�n b? kho theo t? kh�a "${rawKeyword}".`;
      } else if (rawKeyword) {
        partCatalogStatus.textContent = 'D?ch v? c?a ??n n�y ch?a c� linh ki?n m?u. Ti?p t?c nh?p ?? t�m tr�n to�n b? kho linh ki?n.';
      } else {
        partCatalogStatus.textContent = 'D?ch v? c?a ??n n�y ch?a c� linh ki?n m?u ho?c ch?a ??ng b? danh m?c.';
      }

      partCatalogResults.innerHTML = '';
      renderPartCatalogSuggestions(suggestionItems);
      updateSelectedPartsButtonState();
      return;
    }

    partCatalogStatus.textContent = visibleItems.length
      ? `?ang hi?n th? ${visibleItems.length}/${partCatalogState.items.length} linh ki?n ph� h?p v?i d?ch v? c?a ??n.`
      : isShowingFallback
        ? `Kh�ng th?y linh ki?n kh?p trong d?ch v? c?a ??n. ?ang g?i � ${suggestionItems.length} linh ki?n t? to�n b? kho theo t? kh�a "${rawKeyword}".`
        : hasLoadedFallbackSuggestionsForKeyword(rawKeyword)
          ? `Kh�ng t�m th?y linh ki?n kh?p v?i t? kh�a "${rawKeyword}" trong d?ch v? c?a ??n ho?c to�n b? kho.`
          : `Kh�ng t�m th?y linh ki?n kh?p v?i t? kh�a "${partCatalogSearch?.value || ''}".`;

    partCatalogResults.innerHTML = visibleItems.map((item) => {
      const partId = getNumeric(item?.id);
      const hasPrice = getNumeric(item?.gia) > 0;
      const isSelected = partCatalogState.selectedIds.has(partId);
      const serviceName = getPartCatalogServiceName(item);

      return `
        <label class="dispatch-part-option ${isSelected ? 'is-selected' : ''} ${hasPrice ? '' : 'is-disabled'}">
          <input type="checkbox" class="dispatch-part-option__check js-part-catalog-check" value="${partId}" ${isSelected ? 'checked' : ''} ${hasPrice ? '' : 'disabled'}>
          <div class="dispatch-part-option__thumb">
            ${item?.hinh_anh
              ? `<img src="${escapeHtml(item.hinh_anh)}" alt="${escapeHtml(item?.ten_linh_kien || 'Linh ki?n')}">`
              : '<span class="material-symbols-outlined">image_not_supported</span>'}
          </div>
          <div class="dispatch-part-option__body">
            <div class="dispatch-part-option__title">${escapeHtml(item?.ten_linh_kien || 'Linh ki?n')}</div>
            <div class="dispatch-part-option__meta">${escapeHtml(serviceName)}</div>
          </div>
          <div class="dispatch-part-option__price">${hasPrice ? formatMoney(item?.gia) : 'Ch?a c� gi�'}</div>
        </label>
      `;
    }).join('');

    renderPartCatalogSuggestions(suggestionItems);
    updateSelectedPartsButtonState();
  };

  const loadFallbackPartSuggestions = async () => {
    const rawKeyword = String(partCatalogSearch?.value || '').trim();
    const visibleItems = getVisiblePartCatalogItems();

    if (!rawKeyword || visibleItems.length) {
      partCatalogState.fallbackItems = [];
      renderPartCatalogResults();
      return;
    }

    const cacheKey = rawKeyword.toLocaleLowerCase('vi-VN');
    if (partCatalogState.fallbackCache.has(cacheKey)) {
      partCatalogState.fallbackItems = partCatalogState.fallbackCache.get(cacheKey) || [];
      renderPartCatalogResults();
      return;
    }

    const requestId = partCatalogState.searchRequestId;

    try {
      const params = new URLSearchParams({ keyword: rawKeyword });
      const response = await callApi(`/linh-kien?${params.toString()}`, 'GET');

      if (requestId !== partCatalogState.searchRequestId) {
        return;
      }

      if (!response.ok) {
        throw new Error(response.data?.message || 'Kh�ng th? t�m linh ki?n.');
      }

      const items = Array.isArray(response.data) ? response.data : [];
      partCatalogState.fallbackItems = items;
      partCatalogState.fallbackCache.set(cacheKey, items);
      renderPartCatalogResults();
    } catch {
      if (requestId !== partCatalogState.searchRequestId) {
        return;
      }

      partCatalogState.fallbackItems = [];
      renderPartCatalogResults();
    }
  };

  const refreshPartCatalogSearch = async () => {
    partCatalogState.searchRequestId += 1;
    renderPartCatalogResults();
    await loadFallbackPartSuggestions();
  };

  const setPartCatalogSelectionState = (partId, isSelected) => {
    if (partId <= 0) {
      return;
    }

    if (isSelected) {
      partCatalogState.selectedIds.add(partId);
    } else {
      partCatalogState.selectedIds.delete(partId);
    }
  };

  const selectPartCatalogSuggestion = (partId) => {
    const selectedItem = getKnownPartCatalogItems().find((item) => getNumeric(item?.id) === partId);
    if (!selectedItem || getNumeric(selectedItem?.gia) <= 0) {
      return;
    }

    setPartCatalogSelectionState(partId, true);

    if (partCatalogSearch) {
      partCatalogSearch.value = getPartCatalogItemName(selectedItem);
    }

    partCatalogState.activeSuggestionIndex = -1;
    renderPartCatalogResults();
    hidePartCatalogSuggestions();

    const selectedCheckbox = partCatalogResults?.querySelector(`.js-part-catalog-check[value="${partId}"]`);
    selectedCheckbox?.closest('.dispatch-part-option')?.scrollIntoView({
      block: 'nearest',
      behavior: 'smooth',
    });
  };

  const loadPartCatalogForBooking = async (booking) => {
    const activeBookingId = getNumeric(booking?.id);
    const serviceIds = getBookingServiceIds(booking);
    const cacheKey = serviceIds.slice().sort((a, b) => a - b).join(',');

    partCatalogState.selectedIds = new Set();
    partCatalogState.activeSuggestionIndex = -1;
    partCatalogState.fallbackItems = [];
    partCatalogState.searchRequestId += 1;
    hidePartCatalogSuggestions();
    if (partCatalogResults) {
      partCatalogResults.innerHTML = '';
    }
    updateSelectedPartsButtonState();

    if (!serviceIds.length) {
      partCatalogState.items = [];
      renderPartCatalogResults();
      return;
    }

    if (partCatalogState.cache.has(cacheKey)) {
      if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
        return;
      }
      partCatalogState.items = partCatalogState.cache.get(cacheKey) || [];
      renderPartCatalogResults();
      return;
    }

    if (partCatalogStatus) {
      partCatalogStatus.textContent = '?ang t?i danh m?c linh ki?n theo d?ch v? c?a ??n...';
    }

    try {
      const params = new URLSearchParams();
      serviceIds.forEach((serviceId) => params.append('dich_vu_ids[]', serviceId));
      const response = await callApi(`/linh-kien?${params.toString()}`, 'GET');

      if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
        return;
      }

      if (!response.ok) {
        throw new Error(response.data?.message || 'Kh�ng th? t?i danh m?c linh ki?n.');
      }

      const items = Array.isArray(response.data) ? response.data : [];
      partCatalogState.items = items;
      partCatalogState.cache.set(cacheKey, items);
      renderPartCatalogResults();
    } catch (error) {
      if (getNumeric(currentCostBooking?.id) !== activeBookingId) {
        return;
      }

      partCatalogState.items = [];
      renderPartCatalogResults();
      showToast(error.message || 'L?i khi t?i linh ki?n theo d?ch v?.', 'error');
    }
  };

  const addSelectedCatalogPartsToDraft = () => {
    const selectedParts = getKnownPartCatalogItems().filter((item) => partCatalogState.selectedIds.has(getNumeric(item?.id)));

    if (!selectedParts.length) {
      showToast('Vui l�ng ch?n �t nh?t 1 linh ki?n trong danh m?c.', 'error');
      return;
    }

    const existingIds = new Set(Array.from(partItemsContainer?.querySelectorAll('.js-line-part-id') || [])
      .map((input) => getNumeric(input?.value))
      .filter((id) => id > 0));

    let addedCount = 0;

    selectedParts.forEach((item) => {
      const partId = getNumeric(item?.id);
      const partPrice = getNumeric(item?.gia);

      if (partId <= 0 || partPrice <= 0 || existingIds.has(partId)) {
        return;
      }

      appendCostItemRow(partItemsContainer, 'part', {
        linh_kien_id: partId,
        dich_vu_id: getNumeric(item?.dich_vu_id),
        hinh_anh: item?.hinh_anh || '',
        noi_dung: item?.ten_linh_kien || 'Linh ki?n',
        don_gia: partPrice,
        so_luong: 1,
        so_tien: partPrice,
        bao_hanh_thang: '',
      });

      existingIds.add(partId);
      addedCount += 1;
    });

    if (addedCount === 0) {
      showToast('C�c linh ki?n ?� ch?n ?� c� s?n trong b?ng chi ph� ho?c ch?a c� gi� ni�m y?t.', 'error');
      return;
    }

    partCatalogState.selectedIds = new Set();
    renderPartCatalogResults();
    updateCostEstimate();
  };

  const updateCostEstimate = () => {
    const booking = currentCostBooking;
    if (!booking) {
      costEstimateTotal.textContent = formatMoney(0);
      laborSubtotal.textContent = formatMoney(0);
      partsSubtotal.textContent = formatMoney(0);
      travelSubtotal.textContent = formatMoney(0);
      truckSubtotal.textContent = formatMoney(0);
      laborCountBadge.textContent = '0 d�ng';
      partCountBadge.textContent = '0 d�ng';
      costDraftState.textContent = 'C?n nh?p ti?n c�ng';
      costDraftState.dataset.state = 'attention';
      costSummaryHint.textContent = '?� c?ng ti?n c�ng, linh ki?n, ph� ?i l?i v� ph� xe ch? n?u c�.';
      return;
    }

    const laborTotal = sumDraftLineAmounts(laborItemsContainer);
    const partTotal = sumDraftLineAmounts(partItemsContainer);
    const travelTotal = getNumeric(booking?.phi_di_lai);
    const hasTruckLine = truckFeeContainer.style.display !== 'none';
    const truckTotal = hasTruckLine ? getNumeric(inputTienThueXe.value) : 0;
    const total = travelTotal + laborTotal + partTotal + truckTotal;
    const laborRows = countDraftLineRows(laborItemsContainer);
    const partRows = countDraftLineRows(partItemsContainer);

    laborSubtotal.textContent = formatMoney(laborTotal);
    partsSubtotal.textContent = formatMoney(partTotal);
    travelSubtotal.textContent = formatMoney(travelTotal);
    truckSubtotal.textContent = formatMoney(truckTotal);
    costEstimateTotal.textContent = formatMoney(total);
    laborCountBadge.textContent = `${laborRows} d�ng`;
    partCountBadge.textContent = `${partRows} d�ng`;

    if (laborTotal <= 0) {
      costDraftState.textContent = 'C?n nh?p ti?n c�ng';
      costDraftState.dataset.state = 'attention';
    } else {
      costDraftState.textContent = 'S?n s�ng l?u';
      costDraftState.dataset.state = 'ready';
    }

    costSummaryHint.textContent = hasTruckLine
      ? '?� c?ng ti?n c�ng, linh ki?n, ph� ?i l?i v� ph� xe ch? c?a ??n n�y.'
      : '?� c?ng ti?n c�ng, linh ki?n v� ph� ?i l?i c? ??nh c?a ??n n�y.';
  };

  const syncCostWizardUi = () => {
    const totalSteps = Object.keys(pricingWizardSteps).length;
    const currentStepConfig = pricingWizardSteps[currentCostStep] || pricingWizardSteps[1];

    if (costWizardKicker) {
      costWizardKicker.textContent = currentStepConfig.kicker;
    }

    if (costWizardTitle) {
      costWizardTitle.textContent = currentStepConfig.title;
    }

    if (costWizardCopy) {
      costWizardCopy.textContent = currentStepConfig.copy;
    }

    if (costWizardStepBadge) {
      costWizardStepBadge.textContent = `${currentCostStep} / ${totalSteps}`;
    }

    if (costWizardProgressFill) {
      costWizardProgressFill.style.width = `${(currentCostStep / totalSteps) * 100}%`;
    }

    costStepPanels.forEach((panel) => {
      const step = Number(panel.dataset.costStepPanel || 1);
      const isActive = step === currentCostStep;
      panel.hidden = !isActive;
      panel.classList.toggle('is-active', isActive);
    });

    costStepTriggers.forEach((trigger) => {
      const step = Number(trigger.dataset.costStepTrigger || 1);
      const isActive = step === currentCostStep;
      const isComplete = step < currentCostStep;

      trigger.classList.toggle('is-active', isActive);
      trigger.classList.toggle('is-complete', isComplete);
      trigger.setAttribute('aria-current', isActive ? 'step' : 'false');
    });

    btnCostWizardPrev?.classList.toggle('d-none', currentCostStep === 1);
    btnCostWizardNext?.classList.toggle('d-none', currentCostStep >= totalSteps);
    btnSubmitCostUpdate?.classList.toggle('d-none', currentCostStep !== totalSteps);
  };

  const focusCostWizardStep = () => {
    if (currentCostStep === 1) {
      laborSymptomTrigger?.focus();
      return;
    }

    partCatalogSearch?.focus();
  };

  const validateCostWizardStep = (step) => {
    if (step !== 1) {
      return true;
    }

    const laborState = collectCostItems(laborItemsContainer, 'labor');

    if (!laborState.items.length) {
      showToast('Vui l�ng ch?n �t nh?t 1 h??ng x? l� ?? th�m ti?n c�ng tr??c khi ti?p t?c.', 'error');
      laborSymptomTrigger?.focus();
      return false;
    }

    if (laborState.hasIncomplete) {
      showToast('Danh m?c ti?n c�ng ?ang c� d�ng ch?a h?p l?, vui l�ng ki?m tra l?i.', 'error');
      laborSymptomTrigger?.focus();
      return false;
    }

    return true;
  };

  const setCostWizardStep = (step, { validateForward = false, focus = true } = {}) => {
    const totalSteps = Object.keys(pricingWizardSteps).length;
    const nextStep = Math.min(totalSteps, Math.max(1, Number(step) || 1));

    if (nextStep > currentCostStep && validateForward) {
      for (let wizardStep = currentCostStep; wizardStep < nextStep; wizardStep += 1) {
        if (!validateCostWizardStep(wizardStep)) {
          return false;
        }
      }
    }

    currentCostStep = nextStep;
    syncCostWizardUi();

    if (focus) {
      window.requestAnimationFrame(focusCostWizardStep);
    }

    return true;
  };

  const resetPartCatalogUi = () => {
    partCatalogState.selectedIds = new Set();
    partCatalogState.activeSuggestionIndex = -1;
    partCatalogState.fallbackItems = [];
    if (partCatalogResults) {
      partCatalogResults.innerHTML = '';
    }
    hidePartCatalogSuggestions();
    updateSelectedPartsButtonState();
  };

  const hydrateCostModal = (booking) => {
    currentCostBooking = booking;
    currentCostStep = 1;
    bookingIdInput.value = booking.id;

    if (inputGhiChuLinhKien) {
      inputGhiChuLinhKien.value = booking.ghi_chu_linh_kien || '';
    }

    if (partCatalogSearch) {
      partCatalogSearch.value = '';
    }

    costBookingReference.textContent = `??n #${String(booking.id).padStart(4, '0')}`;
    costCustomerName.textContent = getCustomerName(booking);
    costServiceName.textContent = getBookingServiceNames(booking);
    costServiceModeBadge.textContent = booking.loai_dat_lich === 'at_home' ? 'S?a t?i nh�' : 'S?a t?i c?a h�ng';
    costTruckBadge.textContent = booking.thue_xe_cho ? 'C� thu� xe ch?' : 'Kh�ng thu� xe ch?';
    costDistanceBadge.textContent = booking.loai_dat_lich === 'at_home'
      ? `${getNumeric(booking.khoang_cach).toFixed(1)} km ph?c v?`
      : 'Kh�ng ph�t sinh ph� ?i l?i';
    displayPhiDiLai.textContent = formatMoney(getNumeric(booking.phi_di_lai));
    costDistanceHint.textContent = booking.loai_dat_lich === 'at_home'
      ? `H? th?ng ?� ch?t ph� ?i l?i theo qu�ng ???ng ${getNumeric(booking.khoang_cach).toFixed(1)} km.`
      : 'Kh�ch t? mang thi?t b? ??n c?a h�ng n�n kh�ng ph�t sinh kho?ng c�ch ph?c v?.';

    populateCostItemRows(laborItemsContainer, 'labor', getBookingLaborItems(booking));
    populateCostItemRows(partItemsContainer, 'part', getBookingPartItems(booking));

    if (booking.thue_xe_cho) {
      truckFeeContainer.style.display = '';
      truckSummaryRow.style.display = '';
      inputTienThueXe.value = getNumeric(booking.tien_thue_xe);
    } else {
      truckFeeContainer.style.display = 'none';
      truckSummaryRow.style.display = 'none';
      inputTienThueXe.value = 0;
    }

    resetPartCatalogUi();
    updateCostEstimate();
    syncCostWizardUi();
    void loadLaborCatalogForBooking(booking);
    void loadPartCatalogForBooking(booking);
  };

  const reset = () => {
    currentCostBooking = null;
    currentCostStep = 1;
    bookingIdInput.value = '';
    laborCatalogState.items = [];
    laborCatalogState.selectedSymptomId = null;
    laborCatalogState.selectedCauseId = null;
    laborCatalogState.selectedResolutionId = null;
    laborSearchablePickerState.symptom.keyword = '';
    laborSearchablePickerState.cause.keyword = '';

    if (inputGhiChuLinhKien) {
      inputGhiChuLinhKien.value = '';
    }
    if (partCatalogSearch) {
      partCatalogSearch.value = '';
    }

    inputTienThueXe.value = 0;
    truckFeeContainer.style.display = 'none';
    truckSummaryRow.style.display = 'none';
    resetPartCatalogUi();
    closeAllLaborSearchablePickers();
    updateLaborCatalogPicker();
    updateCostEstimate();
    syncCostWizardUi();
  };

  const open = (id) => {
    const booking = getAllBookings().find((item) => getNumeric(item?.id) === getNumeric(id));

    if (!booking) {
      showToast('Kh�ng t�m th?y ??n ?? c?p nh?t gi�.', 'error');
      return;
    }

    hydrateCostModal(booking);
    modalInstance?.show();
  };

  const submit = async (event) => {
    event.preventDefault();

    if (currentCostStep < Object.keys(pricingWizardSteps).length) {
      setCostWizardStep(currentCostStep + 1, { validateForward: true });
      return;
    }

    const bookingId = bookingIdInput.value;
    const submitButton = form?.querySelector('button[type="submit"]');
    const originalLabel = submitButton?.innerHTML || '';
    const laborState = collectCostItems(laborItemsContainer, 'labor');
    const partState = collectCostItems(partItemsContainer, 'part');

    if (!laborState.items.length) {
      showToast('Vui l�ng nh?p �t nh?t 1 d�ng ti?n c�ng.', 'error');
      return;
    }

    if (laborState.hasIncomplete || partState.hasIncomplete) {
      showToast('Vui l�ng ?i?n ?? n?i dung v� s? ti?n cho c�c d�ng chi ph� ?ang nh?p.', 'error');
      return;
    }

    const payload = {
      tien_cong: laborState.items.reduce((total, item) => total + getNumeric(item.so_tien), 0),
      phi_linh_kien: partState.items.reduce((total, item) => total + getNumeric(item.so_tien), 0),
      chi_tiet_tien_cong: laborState.items,
      chi_tiet_linh_kien: partState.items,
      ghi_chu_linh_kien: inputGhiChuLinhKien?.value || '',
    };

    if (truckFeeContainer.style.display !== 'none') {
      payload.tien_thue_xe = inputTienThueXe.value || 0;
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<span class="material-symbols-outlined">progress_activity</span>?ang l?u';
    }

    try {
      const response = await callApi(`/don-dat-lich/${bookingId}/update-costs`, 'PUT', payload);

      if (!response.ok) {
        const firstValidationError = response.data?.errors
          ? Object.values(response.data.errors).flat()[0]
          : null;
        throw new Error(firstValidationError || response.data?.message || 'Kh�ng th? c?p nh?t chi ph�.');
      }

      showToast('?� c?p nh?t chi ph� th�nh c�ng.');
      modalInstance?.hide();
      await afterSubmit?.({ bookingId, payload, booking: currentCostBooking });
    } catch (error) {
      showToast(error.message || 'L?i k?t n?i khi c?p nh?t gi�.', 'error');
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = originalLabel;
      }
    }
  };

  const init = () => {
    if (initialized) {
      return;
    }

    initialized = true;
    syncCostWizardUi();

    costStepTriggers.forEach((trigger) => {
      trigger.addEventListener('click', () => {
        const step = Number(trigger.dataset.costStepTrigger || 1);
        setCostWizardStep(step, { validateForward: step > currentCostStep });
      });
    });

    btnCostWizardPrev?.addEventListener('click', () => {
      setCostWizardStep(currentCostStep - 1);
    });

    btnCostWizardNext?.addEventListener('click', () => {
      setCostWizardStep(currentCostStep + 1, { validateForward: true });
    });

    modalEl?.addEventListener('shown.bs.modal', () => {
      focusCostWizardStep();
    });

    modalEl?.addEventListener('hidden.bs.modal', reset);

    Object.entries(laborSearchablePickers).forEach(([type, picker]) => {
      picker.triggerEl?.addEventListener('click', () => {
        toggleLaborSearchablePicker(type);
      });

      picker.searchEl?.addEventListener('input', () => {
        const state = getLaborSearchablePickerState(type);
        if (!state) {
          return;
        }

        state.keyword = picker.searchEl?.value || '';
        renderLaborSearchablePickerOptions(type);
      });

      picker.searchEl?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          closeLaborSearchablePicker(type);
          picker.triggerEl?.focus();
          return;
        }

        if (event.key === 'Enter') {
          const firstOption = picker.optionsEl?.querySelector('.dispatch-search-picker__option');
          if (!firstOption) {
            return;
          }

          event.preventDefault();
          applyLaborSearchablePickerSelection(type, firstOption.getAttribute('data-picker-value') || '');
        }
      });

      picker.optionsEl?.addEventListener('click', (event) => {
        const targetElement = event.target instanceof Element
          ? event.target
          : event.target?.parentElement || null;
        const option = targetElement?.closest('.dispatch-search-picker__option');
        if (!option) {
          return;
        }

        applyLaborSearchablePickerSelection(type, option.getAttribute('data-picker-value') || '');
      });
    });

    document.addEventListener('click', (event) => {
      const target = event.target;

      if (!(target instanceof Element)) {
        return;
      }

      const clickedInsidePicker = Object.values(laborSearchablePickers)
        .some((picker) => picker.rootEl?.contains(target));

      if (!clickedInsidePicker) {
        closeAllLaborSearchablePickers();
      }
    });

    laborSymptomSelect?.addEventListener('change', () => {
      laborCatalogState.selectedSymptomId = laborSymptomSelect.value || null;
      laborCatalogState.selectedCauseId = null;
      laborCatalogState.selectedResolutionId = null;
      updateLaborCatalogPicker();
    });

    laborCauseSelect?.addEventListener('change', () => {
      laborCatalogState.selectedCauseId = laborCauseSelect.value || null;
      laborCatalogState.selectedResolutionId = null;
      updateLaborCatalogPicker();
    });

    laborResolutionSelect?.addEventListener('change', () => {
      laborCatalogState.selectedResolutionId = laborResolutionSelect.value || null;
      updateLaborCatalogPicker();
    });

    addLaborItemButton?.addEventListener('click', addSelectedLaborCatalogItem);

    addPartItemButton?.addEventListener('click', () => {
      appendCostItemRow(partItemsContainer, 'part');
      updateCostEstimate();
    });

    partCatalogSearch?.addEventListener('input', async () => {
      partCatalogState.activeSuggestionIndex = -1;
      await refreshPartCatalogSearch();
    });

    partCatalogSearch?.addEventListener('focus', async () => {
      if (String(partCatalogSearch.value || '').trim()) {
        await refreshPartCatalogSearch();
      }
    });

    partCatalogSearch?.addEventListener('blur', () => {
      window.setTimeout(() => {
        if (document.activeElement !== partCatalogSearch) {
          hidePartCatalogSuggestions();
        }
      }, 120);
    });

    partCatalogSearch?.addEventListener('keydown', (event) => {
      const visibleItems = getSuggestionPartCatalogItems().slice(0, 6);

      if (event.key === 'Escape') {
        hidePartCatalogSuggestions();
        return;
      }

      if (event.key === 'Enter') {
        event.preventDefault();

        if (!visibleItems.length) {
          hidePartCatalogSuggestions();
          return;
        }

        const fallbackIndex = partCatalogState.activeSuggestionIndex >= 0 ? partCatalogState.activeSuggestionIndex : 0;
        const selectedItem = visibleItems[fallbackIndex];
        if (selectedItem) {
          selectPartCatalogSuggestion(getNumeric(selectedItem.id));
        }
        return;
      }

      if (!visibleItems.length) {
        return;
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        partCatalogState.activeSuggestionIndex = (partCatalogState.activeSuggestionIndex + 1 + visibleItems.length) % visibleItems.length;
        renderPartCatalogSuggestions(getSuggestionPartCatalogItems());
      }

      if (event.key === 'ArrowUp') {
        event.preventDefault();
        partCatalogState.activeSuggestionIndex = partCatalogState.activeSuggestionIndex <= 0
          ? visibleItems.length - 1
          : partCatalogState.activeSuggestionIndex - 1;
        renderPartCatalogSuggestions(getSuggestionPartCatalogItems());
      }
    });

    partCatalogSuggestions?.addEventListener('mousedown', (event) => {
      if (event.target.closest('.js-part-catalog-suggestion')) {
        event.preventDefault();
      }
    });

    partCatalogSuggestions?.addEventListener('click', (event) => {
      const suggestion = event.target.closest('.js-part-catalog-suggestion');
      if (!suggestion || suggestion.hasAttribute('disabled')) {
        return;
      }

      selectPartCatalogSuggestion(getNumeric(suggestion.dataset.partId));
    });

    partCatalogResults?.addEventListener('change', (event) => {
      const input = event.target.closest('.js-part-catalog-check');
      if (!input) {
        return;
      }

      const partId = getNumeric(input.value);
      setPartCatalogSelectionState(partId, input.checked);
      updateSelectedPartsButtonState();
      renderPartCatalogSuggestions(getSuggestionPartCatalogItems());
      input.closest('.dispatch-part-option')?.classList.toggle('is-selected', input.checked);
    });

    addSelectedPartsButton?.addEventListener('click', addSelectedCatalogPartsToDraft);

    [laborItemsContainer, partItemsContainer].forEach((container) => {
      container?.addEventListener('input', updateCostEstimate);
      container?.addEventListener('change', (event) => {
        const quantityInput = event.target.closest('.js-line-quantity');
        if (!quantityInput) {
          return;
        }

        quantityInput.value = String(Math.max(1, Math.trunc(getNumeric(quantityInput.value || 1))));
        updateCostEstimate();
      });

      container?.addEventListener('click', (event) => {
        const quantityStepButton = event.target.closest('.js-quantity-step');
        if (quantityStepButton) {
          const lineItem = quantityStepButton.closest('.dispatch-line-item');
          const quantityInput = lineItem?.querySelector('.js-line-quantity');
          if (quantityInput) {
            const step = Math.trunc(getNumeric(quantityStepButton.dataset.step || 0));
            const nextValue = Math.max(1, Math.trunc(getNumeric(quantityInput.value || 1)) + step);
            quantityInput.value = String(nextValue);
            updateCostEstimate();
          }
          return;
        }

        const removeButton = event.target.closest('.dispatch-line-item__remove');
        if (!removeButton) {
          return;
        }

        const type = removeButton.closest('.dispatch-line-item')?.dataset.lineType === 'part' ? 'part' : 'labor';
        removeButton.closest('.dispatch-line-item')?.remove();
        ensureMinimumCostRows(container, type);
        updateCostEstimate();

        if (type === 'labor') {
          updateLaborCatalogPicker();
        }
      });
    });

    inputTienThueXe?.addEventListener('input', updateCostEstimate);
    form?.addEventListener('submit', submit);
  };

  return {
    init,
    open,
  };
}
