import { callApi, getCurrentUser, showToast } from '../api.js';
import {
  buildWarrantyOptionsMarkup,
  escapeHtml,
  formatMoney,
  getBookingLaborItems,
  getBookingPartItems as getSharedBookingPartItems,
  getBookingServiceIds,
  getBookingServiceNames,
  getCustomerName,
  getNumeric,
  getPartQuantity,
  getPartUnitPrice,
} from './pricing-core.js';

const pageEl = document.getElementById('pricingPage');

if (!pageEl) {
  throw new Error('Pricing page root not found.');
}

const baseUrl = pageEl.dataset.baseUrl || '';
const bookingId = Number(pageEl.dataset.bookingId || 0);
const user = getCurrentUser();

if (!user || !['worker', 'admin'].includes(user.role)) {
  window.location.href = `${baseUrl}/login?role=worker`;
}

if (!bookingId) {
  window.location.href = `${baseUrl}/worker/my-bookings`;
}

const refs = {
  form: document.getElementById('pricingEditorForm'),
  loading: document.getElementById('pricingPageLoading'),
  error: document.getElementById('pricingPageError'),
  content: document.getElementById('pricingEditorContent'),
  costBookingId: document.getElementById('costBookingId'),
  costBookingReference: document.getElementById('costBookingReference'),
  costCustomerName: document.getElementById('costCustomerName'),
  costServiceName: document.getElementById('costServiceName'),
  costBookingStatus: document.getElementById('costBookingStatus'),
  costServiceModeBadge: document.getElementById('costServiceModeBadge'),
  costTruckBadge: document.getElementById('costTruckBadge'),
  costDistanceBadge: document.getElementById('costDistanceBadge'),
  inputGhiChuLinhKien: document.getElementById('inputGhiChuLinhKien'),
  laborItemsContainer: document.getElementById('laborItemsContainer'),
  partItemsContainer: document.getElementById('partItemsContainer'),
  addLaborItemButton: document.getElementById('addLaborItem'),
  addPartItemButton: document.getElementById('addPartItem'),
  laborCatalogSearch: document.getElementById('laborCatalogSearch'),
  laborCatalogSuggestions: document.getElementById('laborCatalogSuggestions'),
  laborCatalogStatus: document.getElementById('laborCatalogStatus'),
  partCatalogSearch: document.getElementById('partCatalogSearch'),
  partCatalogSuggestions: document.getElementById('partCatalogSuggestions'),
  partCatalogStatus: document.getElementById('partCatalogStatus'),
  truckFeeContainer: document.getElementById('truckFeeContainer'),
  inputTienThueXe: document.getElementById('inputTienThueXe'),
  displayPhiDiLai: document.getElementById('displayPhiDiLai'),
  costDistanceHint: document.getElementById('costDistanceHint'),
  laborSubtotal: document.getElementById('laborSubtotal'),
  partsSubtotal: document.getElementById('partsSubtotal'),
  travelSubtotal: document.getElementById('travelSubtotal'),
  truckSummaryRow: document.getElementById('truckSummaryRow'),
  truckSubtotal: document.getElementById('truckSubtotal'),
  costEstimateTotal: document.getElementById('costEstimateTotal'),
  laborCountBadge: document.getElementById('laborCountBadge'),
  partCountBadge: document.getElementById('partCountBadge'),
  costDraftState: document.getElementById('costDraftState'),
  costSummaryHint: document.getElementById('costSummaryHint'),
  submitButton: document.getElementById('btnSubmitCostUpdate'),
};

const state = {
  booking: null,
  laborCatalog: {
    items: [],
    cache: new Map(),
    activeSuggestionIndex: -1,
    fallbackItems: [],
    fallbackCache: new Map(),
    searchRequestId: 0,
  },
  partCatalog: {
    items: [],
    cache: new Map(),
    activeSuggestionIndex: -1,
    fallbackItems: [],
    fallbackCache: new Map(),
    searchRequestId: 0,
  },
};

const getBookingPartItems = (booking) => getSharedBookingPartItems(booking, {
  includeLegacyNote: false,
  emptyWarrantyValue: '',
});

const buildCostItemRowMarkup = (type, item = {}) => {
  const description = escapeHtml(item?.noi_dung || '');
  const isPart = type === 'part';
  const amount = isPart ? getPartUnitPrice(item) : getNumeric(item?.so_tien);
  const amountValue = amount > 0 ? amount : '';
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
  const laborNote = !isPart ? escapeHtml(item?.mo_ta_cong_viec || '') : '';
  const laborMeta = isCatalogLaborItem
    ? (laborNote || 'Từ danh mục tiền công')
    : 'Tu nhap thu cong';

  if (isPart) {
    return `
      <div class="pricing-line-item pricing-part-row" data-line-type="part" data-catalog-part-id="${catalogPartId || ''}">
        <input type="hidden" class="js-line-part-id" value="${catalogPartId || ''}">
        <input type="hidden" class="js-line-service-id" value="${serviceId || ''}">
        <input type="hidden" class="js-line-image" value="${image}">

        <div class="pricing-field">
          <div class="pricing-field-label">Tên linh kiện / Vật tư</div>
          <input type="text" class="pricing-input js-line-description pricing-part-title" value="${description}" placeholder="Bo mạch chủ Samsung" ${isCatalogItem ? 'readonly' : ''}>
          <div class="pricing-part-meta">${isCatalogItem ? 'Từ danh mục linh kiện' : 'Tự nhập thủ công'}</div>
        </div>

        <div class="pricing-field">
          <div class="pricing-field-label">Đơn giá (đ)</div>
          <input type="number" class="pricing-input pricing-input--price js-line-amount" value="${amountValue}" placeholder="650000" ${isCatalogItem ? 'readonly' : ''}>
        </div>

        <div class="pricing-field">
          <div class="pricing-field-label">Số lượng</div>
          <div class="pricing-stepper">
            <button type="button" class="pricing-stepper-btn js-quantity-step" data-step="-1" aria-label="Giảm số lượng">
              <span class="material-symbols-outlined" style="font-size:14px;">remove</span>
            </button>
            <input type="number" class="pricing-input js-line-quantity" value="${quantityValue}" min="1" step="1">
            <button type="button" class="pricing-stepper-btn js-quantity-step" data-step="1" aria-label="Tăng số lượng">
              <span class="material-symbols-outlined" style="font-size:14px;">add</span>
            </button>
          </div>
        </div>

        <div class="pricing-field">
          <div class="pricing-field-label">Bảo hành</div>
          <select class="pricing-select js-line-warranty">${buildWarrantyOptionsMarkup(warrantyValue)}</select>
        </div>

        <button type="button" class="pricing-remove dispatch-line-item__remove" aria-label="Xóa dòng">
          <span class="material-symbols-outlined" style="font-size:14px;">delete</span>
        </button>
      </div>
    `;
  }

  return `
    <div class="pricing-line-item pricing-labor-row" data-line-type="labor" data-catalog-resolution-id="${catalogResolutionId || ''}">
      <input type="hidden" class="js-line-resolution-id" value="${catalogResolutionId || ''}">
      <input type="hidden" class="js-line-cause-id" value="${catalogCauseId || ''}">
      <input type="hidden" class="js-line-service-id" value="${serviceId || ''}">
      <input type="hidden" class="js-line-work-note" value="${laborNote}">
      <div class="pricing-field">
        <div class="pricing-field-label">Tên hạng mục công</div>
        <input type="text" class="pricing-input js-line-description" value="${description}" placeholder="Ví dụ: Vệ sinh dàn lạnh">
      </div>
      <div class="pricing-field">
        <div class="pricing-field-label">Đơn giá (đ)</div>
        <input type="number" class="pricing-input pricing-input--price js-line-amount" value="${amountValue}" placeholder="250000">
      </div>
      <button type="button" class="pricing-remove dispatch-line-item__remove" aria-label="Xóa dòng">
        <span class="material-symbols-outlined" style="font-size:14px;">delete</span>
      </button>
    </div>
  `;
};

const populateCostItemRows = (container, type, items = []) => {
  if (!container) return;
  const normalizedItems = items.length ? items : (type === 'labor' ? [{}] : []);
  container.innerHTML = normalizedItems.map((item) => buildCostItemRowMarkup(type, item)).join('');
};

const applyLaborCatalogRowState = (row, item = {}) => {
  if (!row || row.dataset.lineType !== 'labor') {
    return;
  }

  const descriptionInput = row.querySelector('.js-line-description');
  const amountInput = row.querySelector('.js-line-amount');
  const noteInput = row.querySelector('.js-line-work-note');
  const hasCatalogSource = getNumeric(item?.huong_xu_ly_id) > 0;
  let metaEl = row.querySelector('.pricing-part-meta');

  if (descriptionInput) {
    descriptionInput.readOnly = hasCatalogSource;
  }

  if (amountInput) {
    amountInput.readOnly = hasCatalogSource;
  }

  if (noteInput) {
    noteInput.value = item?.mo_ta_cong_viec || '';
  }

  if (!hasCatalogSource) {
    metaEl?.remove();
    return;
  }

  if (!metaEl && descriptionInput) {
    metaEl = document.createElement('div');
    metaEl.className = 'pricing-part-meta';
    descriptionInput.insertAdjacentElement('afterend', metaEl);
  }

  if (metaEl) {
    metaEl.textContent = item?.mo_ta_cong_viec || 'Từ danh mục tiền công';
  }
};

const syncLaborCatalogRows = (container, items = []) => {
  Array.from(container?.querySelectorAll('.pricing-line-item') || []).forEach((row, index) => {
    applyLaborCatalogRowState(row, items[index] || {});
  });
};

const appendCostItemRow = (container, type, item = {}) => {
  container?.insertAdjacentHTML('beforeend', buildCostItemRowMarkup(type, item));
  if (type === 'labor') {
    applyLaborCatalogRowState(container?.lastElementChild || null, item);
  }
};

const ensureMinimumCostRows = (container, type) => {
  if (type === 'labor' && !container?.querySelector('.pricing-line-item')) {
    appendCostItemRow(container, type);
  }
};

const sumDraftLineAmounts = (container) => Array.from(container?.querySelectorAll('.pricing-line-item') || [])
  .reduce((total, row) => {
    const unitPrice = getNumeric(row.querySelector('.js-line-amount')?.value);
    const quantity = Math.max(1, Math.trunc(getNumeric(row.querySelector('.js-line-quantity')?.value || 1)));
    return total + (row.querySelector('.js-line-quantity') ? unitPrice * quantity : unitPrice);
  }, 0);

const countDraftLineRows = (container) => Array.from(container?.querySelectorAll('.pricing-line-item') || []).length;

const collectCostItems = (container, type) => {
  let hasIncomplete = false;
  const items = [];

  Array.from(container?.querySelectorAll('.pricing-line-item') || []).forEach((row) => {
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

    if (description === '' || amount <= 0) {
      hasIncomplete = true;
      return;
    }

    const item = {
      noi_dung: description,
      so_tien: amount,
    };

    if (type === 'labor') {
      item.huong_xu_ly_id = getNumeric(resolutionIdRaw) || null;
      item.nguyen_nhan_id = getNumeric(causeIdRaw) || null;
      item.dich_vu_id = getNumeric(serviceIdRaw) || null;
      item.mo_ta_cong_viec = workNote || null;
    }

    if (type === 'part') {
      item.linh_kien_id = getNumeric(partIdRaw) || null;
      item.dich_vu_id = getNumeric(serviceIdRaw) || null;
      item.hinh_anh = image || '';
      item.so_luong = quantity;
      item.don_gia = amount;
      item.so_tien = amount * quantity;
      item.bao_hanh_thang = warrantyRaw === '' ? null : Math.max(0, Math.trunc(getNumeric(warrantyRaw)));
    }

    items.push(item);
  });

  return { hasIncomplete, items };
};

const updateCostEstimate = () => {
  const booking = state.booking;
  if (!booking) {
    return;
  }

  const laborTotal = sumDraftLineAmounts(refs.laborItemsContainer);
  const partTotal = sumDraftLineAmounts(refs.partItemsContainer);
  const travelTotal = getNumeric(booking?.phi_di_lai);
  const hasTruckLine = refs.truckFeeContainer.style.display !== 'none';
  const truckTotal = hasTruckLine ? getNumeric(refs.inputTienThueXe.value) : 0;
  const total = travelTotal + laborTotal + partTotal + truckTotal;
  const laborRows = countDraftLineRows(refs.laborItemsContainer);
  const partRows = countDraftLineRows(refs.partItemsContainer);

  refs.laborSubtotal.textContent = formatMoney(laborTotal);
  refs.partsSubtotal.textContent = formatMoney(partTotal);
  refs.travelSubtotal.textContent = formatMoney(travelTotal);
  refs.truckSubtotal.textContent = formatMoney(truckTotal);
  refs.costEstimateTotal.textContent = formatMoney(total);
  refs.laborCountBadge.textContent = `${laborRows} dòng`;
  refs.partCountBadge.textContent = `${partRows} dòng`;

  if (laborTotal <= 0) {
    refs.costDraftState.classList.add('is-attention');
    refs.costDraftState.innerHTML = '<span class="material-symbols-outlined" style="font-size:12px;">warning</span><span>Cần nhập tiền công</span>';
  } else {
    refs.costDraftState.classList.remove('is-attention');
    refs.costDraftState.innerHTML = '<span class="material-symbols-outlined" style="font-size:12px;">check_circle</span><span>Sẵn sàng lưu</span>';
  }

  refs.costSummaryHint.textContent = hasTruckLine
    ? 'Đã cộng tiền công, linh kiện, phí đi lại và phí xe chở của đơn này.'
    : 'Đã cộng tiền công, linh kiện và phí đi lại cố định của đơn này.';
};

const getLaborCatalogKeyword = () => String(refs.laborCatalogSearch?.value || '').trim().toLocaleLowerCase('vi-VN');
const getLaborCatalogItemName = (item) => String(item?.ten_huong_xu_ly || '').trim();
const getLaborCatalogCauseName = (item) => String(item?.nguyen_nhan?.ten_nguyen_nhan || '').trim();
const getLaborCatalogServiceName = (item) => Array.isArray(item?.dich_vus) && item.dich_vus.length
  ? item.dich_vus.map((service) => service?.ten_dich_vu).filter(Boolean).join(', ')
  : getBookingServiceNames(state.booking);

const getVisibleLaborCatalogItems = () => {
  const keyword = getLaborCatalogKeyword();
  const filteredItems = state.laborCatalog.items.filter((item) => {
    const haystack = [
      getLaborCatalogItemName(item),
      getLaborCatalogCauseName(item),
      getLaborCatalogServiceName(item),
      ...(Array.isArray(item?.trieu_chungs) ? item.trieu_chungs.map((symptom) => symptom?.ten_trieu_chung || '') : []),
    ].join(' ').toLocaleLowerCase('vi-VN');

    return haystack.includes(keyword);
  });

  if (!keyword) {
    return filteredItems;
  }

  return filteredItems.slice().sort((itemA, itemB) => {
    const nameA = getLaborCatalogItemName(itemA).toLocaleLowerCase('vi-VN');
    const nameB = getLaborCatalogItemName(itemB).toLocaleLowerCase('vi-VN');
    const prefixDiff = Number(!nameA.startsWith(keyword)) - Number(!nameB.startsWith(keyword));

    if (prefixDiff !== 0) return prefixDiff;

    const matchDiff = nameA.indexOf(keyword) - nameB.indexOf(keyword);
    if (matchDiff !== 0) return matchDiff;

    return nameA.localeCompare(nameB, 'vi');
  });
};

const getKnownLaborCatalogItems = () => {
  const itemMap = new Map();

  [...state.laborCatalog.items, ...state.laborCatalog.fallbackItems].forEach((item) => {
    const resolutionId = getNumeric(item?.id);
    if (resolutionId > 0 && !itemMap.has(resolutionId)) {
      itemMap.set(resolutionId, item);
    }
  });

  return Array.from(itemMap.values());
};

const getDraftLaborIds = () => new Set(
  Array.from(refs.laborItemsContainer?.querySelectorAll('.js-line-resolution-id') || [])
    .map((input) => getNumeric(input?.value))
    .filter((id) => id > 0),
);

const getSuggestionLaborCatalogItems = (visibleItems = getVisibleLaborCatalogItems()) => visibleItems.length
  ? visibleItems
  : state.laborCatalog.fallbackItems;

const setLaborCatalogSuggestionsVisible = (visible) => {
  if (!refs.laborCatalogSuggestions) return;
  refs.laborCatalogSuggestions.hidden = !visible;
};

const hideLaborCatalogSuggestions = () => {
  state.laborCatalog.activeSuggestionIndex = -1;
  if (!refs.laborCatalogSuggestions) return;
  refs.laborCatalogSuggestions.innerHTML = '';
  setLaborCatalogSuggestionsVisible(false);
};

const renderLaborCatalogSuggestions = (visibleItems = getSuggestionLaborCatalogItems()) => {
  const rawKeyword = String(refs.laborCatalogSearch?.value || '').trim();
  if (!rawKeyword) {
    hideLaborCatalogSuggestions();
    return;
  }

  if (!visibleItems.length) {
    refs.laborCatalogSuggestions.innerHTML = '<div class="pricing-catalog-empty">Không tìm thấy hướng xử lý phù hợp với từ khóa đang nhập.</div>';
    setLaborCatalogSuggestionsVisible(true);
    return;
  }

  const suggestionItems = visibleItems.slice(0, 6);
  const draftLaborIds = getDraftLaborIds();
  if (state.laborCatalog.activeSuggestionIndex >= suggestionItems.length) {
    state.laborCatalog.activeSuggestionIndex = suggestionItems.length - 1;
  }

  refs.laborCatalogSuggestions.innerHTML = suggestionItems.map((item, index) => {
    const resolutionId = getNumeric(item?.id);
    const hasPrice = getNumeric(item?.gia_tham_khao) > 0;
    const isInDraft = draftLaborIds.has(resolutionId);
    const isDisabled = !hasPrice || isInDraft;
    const statusBadge = isInDraft
      ? '<span class="pricing-suggestion-badge">Da co trong bang</span>'
      : !hasPrice
        ? '<span class="pricing-suggestion-badge">Chua co gia</span>'
        : '';

    return `
      <button
        type="button"
        class="pricing-suggestion js-labor-catalog-suggestion ${index === state.laborCatalog.activeSuggestionIndex ? 'is-active' : ''} ${isInDraft ? 'is-selected' : ''}"
        data-resolution-id="${resolutionId}"
        ${isDisabled ? 'disabled' : ''}
      >
        <span class="pricing-thumb">
          <span class="material-symbols-outlined">handyman</span>
        </span>
        <span>
          <span class="pricing-suggestion-title">${escapeHtml(getLaborCatalogItemName(item) || 'Hướng xử lý')}</span>
          <span class="pricing-suggestion-meta">${escapeHtml([getLaborCatalogCauseName(item), getLaborCatalogServiceName(item)].filter(Boolean).join(' | '))}</span>
        </span>
        <span>
          <strong class="pricing-suggestion-price">${hasPrice ? formatMoney(item?.gia_tham_khao) : 'Chua co gia'}</strong>
          ${statusBadge}
        </span>
      </button>
    `;
  }).join('');

  setLaborCatalogSuggestionsVisible(true);
};

const renderLaborCatalogResults = () => {
  const rawKeyword = String(refs.laborCatalogSearch?.value || '').trim();
  const visibleItems = getVisibleLaborCatalogItems();
  const suggestionItems = getSuggestionLaborCatalogItems(visibleItems);
  const isShowingFallback = !visibleItems.length && suggestionItems.length > 0;

  if (!state.laborCatalog.items.length) {
    if (!state.booking) {
      refs.laborCatalogStatus.textContent = 'Mở đơn để tải danh mục tiền công theo dịch vụ.';
    } else if (isShowingFallback) {
      refs.laborCatalogStatus.textContent = `Không có danh mục tiền công theo dịch vụ. Đang gợi ý từ toàn bộ catalog theo từ khóa "${rawKeyword}".`;
    } else if (rawKeyword) {
      refs.laborCatalogStatus.textContent = 'Không có danh mục theo dịch vụ. Tiếp tục nhập để tìm trên toàn bộ hướng xử lý.';
    } else {
      refs.laborCatalogStatus.textContent = 'Nhập hướng xử lý để hiện gợi ý tiền công từ catalog.';
    }
  } else {
    refs.laborCatalogStatus.textContent = !rawKeyword
      ? 'Nhập hướng xử lý để chọn nhanh tiền công theo đúng dịch vụ của đơn.'
      : visibleItems.length
        ? `Có ${visibleItems.length} gợi ý phù hợp. Chọn 1 gợi ý để thêm nhanh vào bảng tiền công.`
        : isShowingFallback
          ? 'Không thấy hướng xử lý khớp trong dịch vụ của đơn. Đang gợi ý từ toàn bộ catalog.'
          : `Không tìm thấy hướng xử lý khớp với từ khóa "${rawKeyword}".`;
  }

  renderLaborCatalogSuggestions(suggestionItems);
};

const loadFallbackLaborSuggestions = async () => {
  const rawKeyword = String(refs.laborCatalogSearch?.value || '').trim();
  const visibleItems = getVisibleLaborCatalogItems();

  if (!rawKeyword || visibleItems.length) {
    state.laborCatalog.fallbackItems = [];
    renderLaborCatalogResults();
    return;
  }

  const cacheKey = rawKeyword.toLocaleLowerCase('vi-VN');
  if (state.laborCatalog.fallbackCache.has(cacheKey)) {
    state.laborCatalog.fallbackItems = state.laborCatalog.fallbackCache.get(cacheKey) || [];
    renderLaborCatalogResults();
    return;
  }

  const requestId = state.laborCatalog.searchRequestId;
  try {
    const params = new URLSearchParams({ keyword: rawKeyword });
    const response = await callApi(`/huong-xu-ly?${params.toString()}`, 'GET');
    if (requestId !== state.laborCatalog.searchRequestId) return;

    if (!response.ok) {
      throw new Error(response.data?.message || 'Không thể tìm hướng xử lý.');
    }

    const items = Array.isArray(response.data) ? response.data : [];
    state.laborCatalog.fallbackItems = items;
    state.laborCatalog.fallbackCache.set(cacheKey, items);
    renderLaborCatalogResults();
  } catch (error) {
    if (requestId !== state.laborCatalog.searchRequestId) return;
    state.laborCatalog.fallbackItems = [];
    renderLaborCatalogResults();
  }
};

const refreshLaborCatalogSearch = async () => {
  state.laborCatalog.searchRequestId += 1;
  renderLaborCatalogResults();
  await loadFallbackLaborSuggestions();
};

const selectLaborCatalogSuggestion = (resolutionId) => {
  const selectedItem = getKnownLaborCatalogItems().find((item) => getNumeric(item?.id) === resolutionId);
  if (!selectedItem || getNumeric(selectedItem?.gia_tham_khao) <= 0) {
    return;
  }

  const draftLaborIds = getDraftLaborIds();
  if (draftLaborIds.has(resolutionId)) {
    showToast('Hạng mục công này đã có trong bảng chi phí.', 'error');
    refs.laborCatalogSearch.focus();
    return;
  }

  appendCostItemRow(refs.laborItemsContainer, 'labor', {
    huong_xu_ly_id: resolutionId,
    nguyen_nhan_id: getNumeric(selectedItem?.nguyen_nhan_id),
    dich_vu_id: getNumeric(selectedItem?.dich_vus?.[0]?.id),
    mo_ta_cong_viec: selectedItem?.mo_ta_cong_viec || '',
    noi_dung: selectedItem?.ten_huong_xu_ly || 'Hướng xử lý',
    so_tien: getNumeric(selectedItem?.gia_tham_khao),
  });

  refs.laborCatalogSearch.value = '';
  state.laborCatalog.activeSuggestionIndex = -1;
  state.laborCatalog.fallbackItems = [];
  hideLaborCatalogSuggestions();
  renderLaborCatalogResults();
  updateCostEstimate();
  refs.laborCatalogSearch.focus();
};

const loadLaborCatalogForBooking = async () => {
  const serviceIds = getBookingServiceIds(state.booking);
  const cacheKey = serviceIds.slice().sort((a, b) => a - b).join(',');

  state.laborCatalog.activeSuggestionIndex = -1;
  state.laborCatalog.fallbackItems = [];
  hideLaborCatalogSuggestions();

  if (!serviceIds.length) {
    state.laborCatalog.items = [];
    renderLaborCatalogResults();
    return;
  }

  if (state.laborCatalog.cache.has(cacheKey)) {
    state.laborCatalog.items = state.laborCatalog.cache.get(cacheKey) || [];
    renderLaborCatalogResults();
    return;
  }

  refs.laborCatalogStatus.textContent = 'Đang tải danh mục tiền công theo dịch vụ của đơn...';

  try {
    const params = new URLSearchParams();
    serviceIds.forEach((serviceId) => params.append('dich_vu_ids[]', serviceId));
    const response = await callApi(`/huong-xu-ly?${params.toString()}`, 'GET');

    if (!response.ok) {
      throw new Error(response.data?.message || 'Không thể tải danh mục tiền công.');
    }

    const items = Array.isArray(response.data) ? response.data : [];
    state.laborCatalog.items = items;
    state.laborCatalog.cache.set(cacheKey, items);
    renderLaborCatalogResults();
  } catch (error) {
    state.laborCatalog.items = [];
    renderLaborCatalogResults();
    showToast(error.message || 'Lỗi khi tải hướng xử lý theo dịch vụ.', 'error');
  }
};

const getPartCatalogKeyword = () => String(refs.partCatalogSearch?.value || '').trim().toLocaleLowerCase('vi-VN');
const getPartCatalogItemName = (item) => String(item?.ten_linh_kien || '').trim();
const getPartCatalogServiceName = (item) => item?.dich_vu?.ten_dich_vu || getBookingServiceNames(state.booking);

const getVisiblePartCatalogItems = () => {
  const keyword = getPartCatalogKeyword();
  const filteredItems = state.partCatalog.items.filter((item) => getPartCatalogItemName(item)
    .toLocaleLowerCase('vi-VN')
    .includes(keyword));

  if (!keyword) {
    return filteredItems;
  }

  return filteredItems.slice().sort((itemA, itemB) => {
    const nameA = getPartCatalogItemName(itemA).toLocaleLowerCase('vi-VN');
    const nameB = getPartCatalogItemName(itemB).toLocaleLowerCase('vi-VN');
    const prefixDiff = Number(!nameA.startsWith(keyword)) - Number(!nameB.startsWith(keyword));

    if (prefixDiff !== 0) return prefixDiff;

    const matchDiff = nameA.indexOf(keyword) - nameB.indexOf(keyword);
    if (matchDiff !== 0) return matchDiff;

    return nameA.localeCompare(nameB, 'vi');
  });
};

const getKnownPartCatalogItems = () => {
  const itemMap = new Map();

  [...state.partCatalog.items, ...state.partCatalog.fallbackItems].forEach((item) => {
    const partId = getNumeric(item?.id);
    if (partId > 0 && !itemMap.has(partId)) {
      itemMap.set(partId, item);
    }
  });

  return Array.from(itemMap.values());
};

const getDraftPartIds = () => new Set(
  Array.from(refs.partItemsContainer?.querySelectorAll('.js-line-part-id') || [])
    .map((input) => getNumeric(input?.value))
    .filter((id) => id > 0),
);

const getSuggestionPartCatalogItems = (visibleItems = getVisiblePartCatalogItems()) => visibleItems.length
  ? visibleItems
  : state.partCatalog.fallbackItems;

const setPartCatalogSuggestionsVisible = (visible) => {
  if (!refs.partCatalogSuggestions) return;
  refs.partCatalogSuggestions.hidden = !visible;
};

const hidePartCatalogSuggestions = () => {
  state.partCatalog.activeSuggestionIndex = -1;
  if (!refs.partCatalogSuggestions) return;
  refs.partCatalogSuggestions.innerHTML = '';
  setPartCatalogSuggestionsVisible(false);
};

const renderPartCatalogSuggestions = (visibleItems = getSuggestionPartCatalogItems()) => {
  const rawKeyword = String(refs.partCatalogSearch?.value || '').trim();
  if (!rawKeyword) {
    hidePartCatalogSuggestions();
    return;
  }

  if (!visibleItems.length) {
    refs.partCatalogSuggestions.innerHTML = '<div class="pricing-catalog-empty">Không tìm thấy linh kiện phù hợp với từ khóa đang nhập.</div>';
    setPartCatalogSuggestionsVisible(true);
    return;
  }

  const suggestionItems = visibleItems.slice(0, 6);
  const draftPartIds = getDraftPartIds();
  if (state.partCatalog.activeSuggestionIndex >= suggestionItems.length) {
    state.partCatalog.activeSuggestionIndex = suggestionItems.length - 1;
  }

  refs.partCatalogSuggestions.innerHTML = suggestionItems.map((item, index) => {
    const partId = getNumeric(item?.id);
    const hasPrice = getNumeric(item?.gia) > 0;
    const isInDraft = draftPartIds.has(partId);
    const isDisabled = !hasPrice || isInDraft;
    const statusBadge = isInDraft
      ? '<span class="pricing-suggestion-badge">Đã có trong bảng</span>'
      : !hasPrice
        ? '<span class="pricing-suggestion-badge">Chưa có giá</span>'
        : '';

    return `
      <button
        type="button"
        class="pricing-suggestion js-part-catalog-suggestion ${index === state.partCatalog.activeSuggestionIndex ? 'is-active' : ''} ${isInDraft ? 'is-selected' : ''}"
        data-part-id="${partId}"
        ${isDisabled ? 'disabled' : ''}
      >
        <span class="pricing-thumb">
          ${item?.hinh_anh
            ? `<img src="${escapeHtml(item.hinh_anh)}" alt="${escapeHtml(getPartCatalogItemName(item) || 'Linh kiện')}">`
            : '<span class="material-symbols-outlined">image_not_supported</span>'}
        </span>
        <span>
          <span class="pricing-suggestion-title">${escapeHtml(getPartCatalogItemName(item) || 'Linh kiện')}</span>
          <span class="pricing-suggestion-meta">${escapeHtml(getPartCatalogServiceName(item))}</span>
        </span>
        <span>
          <strong class="pricing-suggestion-price">${hasPrice ? formatMoney(item?.gia) : 'Chưa có giá'}</strong>
          ${statusBadge}
        </span>
      </button>
    `;
  }).join('');

  setPartCatalogSuggestionsVisible(true);
};

const renderPartCatalogResults = () => {
  const rawKeyword = String(refs.partCatalogSearch?.value || '').trim();
  const visibleItems = getVisiblePartCatalogItems();
  const suggestionItems = getSuggestionPartCatalogItems(visibleItems);
  const isShowingFallback = !visibleItems.length && suggestionItems.length > 0;

  if (!state.partCatalog.items.length) {
    if (!state.booking) {
      refs.partCatalogStatus.textContent = 'Mở đơn để tải danh mục linh kiện đúng theo dịch vụ.';
    } else if (isShowingFallback) {
      refs.partCatalogStatus.textContent = `Không có danh mục theo dịch vụ. Đang gợi ý linh kiện toàn kho theo từ khóa "${rawKeyword}".`;
    } else if (rawKeyword) {
      refs.partCatalogStatus.textContent = 'Không có danh mục theo dịch vụ. Tiếp tục nhập để tìm trên toàn bộ kho linh kiện.';
    } else {
      refs.partCatalogStatus.textContent = 'Nhập tên linh kiện để hiện gợi ý từ kho theo dịch vụ của đơn.';
    }
  } else {
    refs.partCatalogStatus.textContent = !rawKeyword
      ? 'Nhập tên linh kiện để hiện gợi ý theo đúng dịch vụ của đơn.'
      : visibleItems.length
        ? `Có ${visibleItems.length} gợi ý phù hợp. Chọn 1 gợi ý để thêm nhanh vào bảng chi phí.`
        : isShowingFallback
          ? 'Không thấy linh kiện khớp trong dịch vụ của đơn. Đang gợi ý từ toàn bộ kho.'
          : `Không tìm thấy linh kiện khớp với từ khóa "${rawKeyword}".`;
  }

  renderPartCatalogSuggestions(suggestionItems);
};

const loadFallbackPartSuggestions = async () => {
  const rawKeyword = String(refs.partCatalogSearch?.value || '').trim();
  const visibleItems = getVisiblePartCatalogItems();

  if (!rawKeyword || visibleItems.length) {
    state.partCatalog.fallbackItems = [];
    renderPartCatalogResults();
    return;
  }

  const cacheKey = rawKeyword.toLocaleLowerCase('vi-VN');
  if (state.partCatalog.fallbackCache.has(cacheKey)) {
    state.partCatalog.fallbackItems = state.partCatalog.fallbackCache.get(cacheKey) || [];
    renderPartCatalogResults();
    return;
  }

  const requestId = state.partCatalog.searchRequestId;
  try {
    const params = new URLSearchParams({ keyword: rawKeyword });
    const response = await callApi(`/linh-kien?${params.toString()}`, 'GET');
    if (requestId !== state.partCatalog.searchRequestId) return;

    if (!response.ok) {
      throw new Error(response.data?.message || 'Không thể tìm linh kiện.');
    }

    const items = Array.isArray(response.data) ? response.data : [];
    state.partCatalog.fallbackItems = items;
    state.partCatalog.fallbackCache.set(cacheKey, items);
    renderPartCatalogResults();
  } catch (error) {
    if (requestId !== state.partCatalog.searchRequestId) return;
    state.partCatalog.fallbackItems = [];
    renderPartCatalogResults();
  }
};

const refreshPartCatalogSearch = async () => {
  state.partCatalog.searchRequestId += 1;
  renderPartCatalogResults();
  await loadFallbackPartSuggestions();
};

const selectPartCatalogSuggestion = (partId) => {
  const selectedItem = getKnownPartCatalogItems().find((item) => getNumeric(item?.id) === partId);
  if (!selectedItem || getNumeric(selectedItem?.gia) <= 0) {
    return;
  }

  const draftPartIds = getDraftPartIds();
  if (draftPartIds.has(partId)) {
    showToast('Linh kiện này đã có trong bảng chi phí.', 'error');
    refs.partCatalogSearch.focus();
    return;
  }

  appendCostItemRow(refs.partItemsContainer, 'part', {
    linh_kien_id: partId,
    dich_vu_id: getNumeric(selectedItem?.dich_vu_id),
    hinh_anh: selectedItem?.hinh_anh || '',
    noi_dung: selectedItem?.ten_linh_kien || 'Linh kiện',
    don_gia: getNumeric(selectedItem?.gia),
    so_luong: 1,
    so_tien: getNumeric(selectedItem?.gia),
    bao_hanh_thang: '',
  });

  refs.partCatalogSearch.value = '';
  state.partCatalog.activeSuggestionIndex = -1;
  state.partCatalog.fallbackItems = [];
  hidePartCatalogSuggestions();
  renderPartCatalogResults();
  updateCostEstimate();
  refs.partCatalogSearch.focus();
};

const loadPartCatalogForBooking = async () => {
  const serviceIds = getBookingServiceIds(state.booking);
  const cacheKey = serviceIds.slice().sort((a, b) => a - b).join(',');

  state.partCatalog.activeSuggestionIndex = -1;
  state.partCatalog.fallbackItems = [];
  hidePartCatalogSuggestions();

  if (!serviceIds.length) {
    state.partCatalog.items = [];
    renderPartCatalogResults();
    return;
  }

  if (state.partCatalog.cache.has(cacheKey)) {
    state.partCatalog.items = state.partCatalog.cache.get(cacheKey) || [];
    renderPartCatalogResults();
    return;
  }

  refs.partCatalogStatus.textContent = 'Đang tải danh mục linh kiện theo dịch vụ của đơn...';

  try {
    const params = new URLSearchParams();
    serviceIds.forEach((serviceId) => params.append('dich_vu_ids[]', serviceId));
    const response = await callApi(`/linh-kien?${params.toString()}`, 'GET');

    if (!response.ok) {
      throw new Error(response.data?.message || 'Không thể tải danh mục linh kiện.');
    }

    const items = Array.isArray(response.data) ? response.data : [];
    state.partCatalog.items = items;
    state.partCatalog.cache.set(cacheKey, items);
    renderPartCatalogResults();
  } catch (error) {
    state.partCatalog.items = [];
    renderPartCatalogResults();
    showToast(error.message || 'Lỗi khi tải linh kiện theo dịch vụ.', 'error');
  }
};

const hydrateBooking = (booking) => {
  state.booking = booking;
  refs.costBookingId.value = booking.id;
  refs.costBookingReference.textContent = `Đơn #${String(booking.id).padStart(4, '0')}`;
  refs.costCustomerName.textContent = getCustomerName(booking);
  refs.costServiceName.textContent = getBookingServiceNames(booking);
  refs.costBookingStatus.textContent = 'Đang sửa';
  refs.displayPhiDiLai.textContent = formatMoney(getNumeric(booking?.phi_di_lai));
  refs.costDistanceHint.textContent = booking.loai_dat_lich === 'at_home'
    ? `Phí đi lại tính theo quãng đường ${getNumeric(booking.khoang_cach).toFixed(1)} km.`
    : 'Khách tự mang thiết bị đến cửa hàng nên không phát sinh quãng đường phục vụ.';

  refs.costServiceModeBadge.innerHTML = `
    <span class="material-symbols-outlined">home_repair_service</span>
    <span>${booking.loai_dat_lich === 'at_home' ? 'Sửa tại nhà' : 'Sửa tại cửa hàng'}</span>
  `;
  refs.costTruckBadge.innerHTML = `
    <span class="material-symbols-outlined">local_shipping</span>
    <span>${booking.thue_xe_cho ? 'Có xe chở thiết bị' : 'Không thuê xe chở'}</span>
  `;
  refs.costDistanceBadge.textContent = booking.loai_dat_lich === 'at_home'
    ? `${getNumeric(booking.khoang_cach).toFixed(1)} km phục vụ`
    : 'Phí đi lại tự động';

  populateCostItemRows(refs.laborItemsContainer, 'labor', getBookingLaborItems(booking));
  syncLaborCatalogRows(refs.laborItemsContainer, getBookingLaborItems(booking));
  populateCostItemRows(refs.partItemsContainer, 'part', getBookingPartItems(booking));

  if (booking.thue_xe_cho) {
    refs.truckFeeContainer.style.display = '';
    refs.truckSummaryRow.style.display = '';
    refs.inputTienThueXe.value = getNumeric(booking.tien_thue_xe);
  } else {
    refs.truckFeeContainer.style.display = 'none';
    refs.truckSummaryRow.style.display = 'none';
    refs.inputTienThueXe.value = 0;
  }

  refs.loading.hidden = true;
  refs.error.hidden = true;
  refs.content.hidden = false;
  updateCostEstimate();
};

const loadBooking = async () => {
  refs.loading.hidden = false;
  refs.error.hidden = true;
  refs.content.hidden = true;

  try {
    const response = await callApi(`/don-dat-lich/${bookingId}`, 'GET');
    if (!response.ok) {
      throw new Error(response.data?.message || 'Không tải được đơn sửa chữa.');
    }

    const booking = response.data?.data || response.data;
    if (!booking?.id) {
      throw new Error('Không tìm thấy đơn sửa chữa.');
    }

    hydrateBooking(booking);
    await loadLaborCatalogForBooking();
    await loadPartCatalogForBooking();
  } catch (error) {
    refs.loading.hidden = true;
    refs.error.hidden = false;
    refs.error.textContent = error.message || 'Không tải được dữ liệu đơn sửa chữa.';
    showToast(error.message || 'Không tải được dữ liệu đơn.', 'error');
  }
};

refs.addLaborItemButton?.addEventListener('click', () => {
  appendCostItemRow(refs.laborItemsContainer, 'labor');
  updateCostEstimate();
});

refs.addPartItemButton?.addEventListener('click', () => {
  appendCostItemRow(refs.partItemsContainer, 'part');
  updateCostEstimate();
});

refs.laborCatalogSearch?.addEventListener('input', async () => {
  state.laborCatalog.activeSuggestionIndex = -1;
  await refreshLaborCatalogSearch();
});

refs.laborCatalogSearch?.addEventListener('focus', async () => {
  if (String(refs.laborCatalogSearch.value || '').trim()) {
    await refreshLaborCatalogSearch();
  }
});

refs.laborCatalogSearch?.addEventListener('blur', () => {
  window.setTimeout(() => {
    if (document.activeElement !== refs.laborCatalogSearch) {
      hideLaborCatalogSuggestions();
    }
  }, 120);
});

refs.laborCatalogSearch?.addEventListener('keydown', (event) => {
  const visibleItems = getSuggestionLaborCatalogItems().slice(0, 6);

  if (event.key === 'Escape') {
    hideLaborCatalogSuggestions();
    return;
  }

  if (event.key === 'Enter') {
    event.preventDefault();

    if (!visibleItems.length) {
      hideLaborCatalogSuggestions();
      return;
    }

    const index = state.laborCatalog.activeSuggestionIndex >= 0 ? state.laborCatalog.activeSuggestionIndex : 0;
    const selectedItem = visibleItems[index];
    if (selectedItem) {
      selectLaborCatalogSuggestion(getNumeric(selectedItem.id));
    }
    return;
  }

  if (!visibleItems.length) {
    return;
  }

  if (event.key === 'ArrowDown') {
    event.preventDefault();
    state.laborCatalog.activeSuggestionIndex = (state.laborCatalog.activeSuggestionIndex + 1 + visibleItems.length) % visibleItems.length;
    renderLaborCatalogSuggestions(getSuggestionLaborCatalogItems());
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault();
    state.laborCatalog.activeSuggestionIndex = state.laborCatalog.activeSuggestionIndex <= 0
      ? visibleItems.length - 1
      : state.laborCatalog.activeSuggestionIndex - 1;
    renderLaborCatalogSuggestions(getSuggestionLaborCatalogItems());
  }
});

refs.laborCatalogSuggestions?.addEventListener('mousedown', (event) => {
  if (event.target.closest('.js-labor-catalog-suggestion')) {
    event.preventDefault();
  }
});

refs.laborCatalogSuggestions?.addEventListener('click', (event) => {
  const suggestion = event.target.closest('.js-labor-catalog-suggestion');
  if (!suggestion || suggestion.hasAttribute('disabled')) {
    return;
  }

  selectLaborCatalogSuggestion(getNumeric(suggestion.dataset.resolutionId));
});

refs.partCatalogSearch?.addEventListener('input', async () => {
  state.partCatalog.activeSuggestionIndex = -1;
  await refreshPartCatalogSearch();
});

refs.partCatalogSearch?.addEventListener('focus', async () => {
  if (String(refs.partCatalogSearch.value || '').trim()) {
    await refreshPartCatalogSearch();
  }
});

refs.partCatalogSearch?.addEventListener('blur', () => {
  window.setTimeout(() => {
    if (document.activeElement !== refs.partCatalogSearch) {
      hidePartCatalogSuggestions();
    }
  }, 120);
});

refs.partCatalogSearch?.addEventListener('keydown', (event) => {
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

    const index = state.partCatalog.activeSuggestionIndex >= 0 ? state.partCatalog.activeSuggestionIndex : 0;
    const selectedItem = visibleItems[index];
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
    state.partCatalog.activeSuggestionIndex = (state.partCatalog.activeSuggestionIndex + 1 + visibleItems.length) % visibleItems.length;
    renderPartCatalogSuggestions(getSuggestionPartCatalogItems());
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault();
    state.partCatalog.activeSuggestionIndex = state.partCatalog.activeSuggestionIndex <= 0
      ? visibleItems.length - 1
      : state.partCatalog.activeSuggestionIndex - 1;
    renderPartCatalogSuggestions(getSuggestionPartCatalogItems());
  }
});

refs.partCatalogSuggestions?.addEventListener('mousedown', (event) => {
  if (event.target.closest('.js-part-catalog-suggestion')) {
    event.preventDefault();
  }
});

refs.partCatalogSuggestions?.addEventListener('click', (event) => {
  const suggestion = event.target.closest('.js-part-catalog-suggestion');
  if (!suggestion || suggestion.hasAttribute('disabled')) {
    return;
  }

  selectPartCatalogSuggestion(getNumeric(suggestion.dataset.partId));
});

[refs.laborItemsContainer, refs.partItemsContainer].forEach((container) => {
  container?.addEventListener('input', updateCostEstimate);
  container?.addEventListener('change', (event) => {
    const quantityInput = event.target.closest('.js-line-quantity');
    if (!quantityInput) return;

    quantityInput.value = String(Math.max(1, Math.trunc(getNumeric(quantityInput.value || 1))));
    updateCostEstimate();
  });

  container?.addEventListener('click', (event) => {
    const quantityStepButton = event.target.closest('.js-quantity-step');
    if (quantityStepButton) {
      const lineItem = quantityStepButton.closest('.pricing-line-item');
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
    if (!removeButton) return;

    const type = removeButton.closest('.pricing-line-item')?.dataset.lineType === 'part' ? 'part' : 'labor';
    removeButton.closest('.pricing-line-item')?.remove();
    ensureMinimumCostRows(container, type);
    renderLaborCatalogSuggestions(getSuggestionLaborCatalogItems());
    renderPartCatalogSuggestions(getSuggestionPartCatalogItems());
    updateCostEstimate();
  });
});

refs.inputTienThueXe?.addEventListener('input', updateCostEstimate);

refs.form?.addEventListener('submit', async (event) => {
  event.preventDefault();

  const submitButton = refs.submitButton;
  const originalLabel = submitButton?.innerHTML || '';
  const laborState = collectCostItems(refs.laborItemsContainer, 'labor');
  const partState = collectCostItems(refs.partItemsContainer, 'part');

  if (!laborState.items.length) {
    showToast('Vui lòng nhập ít nhất 1 dòng tiền công.', 'error');
    return;
  }

  if (laborState.hasIncomplete || partState.hasIncomplete) {
    showToast('Vui lòng điền đủ nội dung và số tiền cho các dòng chi phí đang nhập.', 'error');
    return;
  }

  const payload = {
    tien_cong: laborState.items.reduce((total, item) => total + getNumeric(item.so_tien), 0),
    phi_linh_kien: partState.items.reduce((total, item) => total + getNumeric(item.so_tien), 0),
    chi_tiet_tien_cong: laborState.items,
    chi_tiet_linh_kien: partState.items,
    ghi_chu_linh_kien: refs.inputGhiChuLinhKien?.value || '',
  };

  if (refs.truckFeeContainer.style.display !== 'none') {
    payload.tien_thue_xe = refs.inputTienThueXe.value || 0;
  }

  if (submitButton) {
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="material-symbols-outlined" style="font-size:14px;">progress_activity</span><span>Đang lưu</span>';
  }

  try {
    const response = await callApi(`/don-dat-lich/${bookingId}/update-costs`, 'PUT', payload);

    if (!response.ok) {
      const firstValidationError = response.data?.errors
        ? Object.values(response.data.errors).flat()[0]
        : null;
      throw new Error(firstValidationError || response.data?.message || 'Không thể cập nhật chi phí.');
    }

    showToast('Đã cập nhật chi phí thành công.');
    window.setTimeout(() => {
      window.location.href = `${baseUrl}/worker/my-bookings`;
    }, 500);
  } catch (error) {
    showToast(error.message || 'Lỗi kết nối khi cập nhật giá.', 'error');
  } finally {
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML = originalLabel;
    }
  }
});

loadBooking();
