export const escapeHtml = (value = '') => String(value ?? '').replace(/[&<>"']/g, (char) => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#039;',
}[char]));

export const formatMoney = (value) => new Intl.NumberFormat('vi-VN', {
  style: 'currency',
  currency: 'VND',
  maximumFractionDigits: 0,
}).format(Number(value || 0));

export const getNumeric = (value) => Number(value || 0);

export const getBookingServices = (booking) => Array.isArray(booking?.dich_vus) ? booking.dich_vus : [];

export const getBookingServiceNames = (booking, { fallback = 'Dịch vụ sửa chữa' } = {}) => {
  const services = getBookingServices(booking)
    .map((service) => service?.ten_dich_vu)
    .filter(Boolean);

  return services.length ? services.join(', ') : fallback;
};

export const getCustomerName = (booking, { fallback = 'Khách hàng' } = {}) => booking?.khach_hang?.name || fallback;

export const getBookingServiceIds = (booking) => {
  const relationIds = getBookingServices(booking)
    .map((service) => getNumeric(service?.id))
    .filter((id) => id > 0);

  if (relationIds.length) {
    return Array.from(new Set(relationIds));
  }

  const legacyId = getNumeric(booking?.dich_vu_id);

  return legacyId > 0 ? [legacyId] : [];
};

export const getStoredCostItems = (booking, key) => Array.isArray(booking?.[key]) ? booking[key].filter(Boolean) : [];

export const getLegacyFirstLine = (value = '', fallback = 'Linh kiện thay thế') => {
  const firstLine = String(value || '')
    .split(/\r\n|\r|\n/)
    .map((line) => line.trim())
    .find(Boolean);

  return firstLine || fallback;
};

export const getBookingLaborItems = (booking, { serviceNameFallback = 'Dịch vụ sửa chữa' } = {}) => {
  const items = getStoredCostItems(booking, 'chi_tiet_tien_cong');

  if (items.length) {
    return items;
  }

  if (getNumeric(booking?.tien_cong) > 0) {
    return [{
      noi_dung: getBookingServiceNames(booking, { fallback: serviceNameFallback }),
      so_tien: getNumeric(booking?.tien_cong),
    }];
  }

  return [];
};

export const getBookingPartItems = (
  booking,
  {
    noteFallback = 'Linh kiện thay thế',
    includeLegacyNote = true,
    emptyWarrantyValue = null,
  } = {},
) => {
  const items = getStoredCostItems(booking, 'chi_tiet_linh_kien');

  if (items.length) {
    return items;
  }

  const hasLegacyNote = includeLegacyNote && String(booking?.ghi_chu_linh_kien || '').trim() !== '';
  const partFee = getNumeric(booking?.phi_linh_kien);

  if (partFee > 0 || hasLegacyNote) {
    return [{
      noi_dung: getLegacyFirstLine(booking?.ghi_chu_linh_kien, noteFallback),
      don_gia: partFee,
      so_luong: 1,
      so_tien: partFee,
      bao_hanh_thang: emptyWarrantyValue,
    }];
  }

  return [];
};

export const getPartQuantity = (item) => {
  const quantity = Math.trunc(getNumeric(item?.so_luong || 1));

  return quantity > 0 ? quantity : 1;
};

export const getPartUnitPrice = (item) => {
  const explicitUnitPrice = getNumeric(item?.don_gia);

  if (explicitUnitPrice > 0) {
    return explicitUnitPrice;
  }

  const quantity = getPartQuantity(item);
  const total = getNumeric(item?.so_tien);

  return quantity > 0 ? total / quantity : total;
};

export const buildWarrantyOptionsMarkup = (value = '', { placeholder = 'Bảo hành' } = {}) => {
  const normalizedValue = value === '' ? '' : Math.max(0, Math.trunc(getNumeric(value)));
  const presets = ['', 0, 1, 3, 6, 12, 24];

  if (normalizedValue !== '' && !presets.includes(normalizedValue)) {
    presets.push(normalizedValue);
  }

  return presets
    .filter((option, index, array) => array.indexOf(option) === index)
    .sort((left, right) => {
      if (left === '') {
        return -1;
      }

      if (right === '') {
        return 1;
      }

      return Number(left) - Number(right);
    })
    .map((option) => {
      const selected = option === normalizedValue ? 'selected' : '';
      const label = option === ''
        ? placeholder
        : option === 0
          ? '0 Tháng'
          : `${option} Tháng`;

      return `<option value="${option}" ${selected}>${label}</option>`;
    })
    .join('');
};
