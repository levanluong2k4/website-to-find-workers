import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const DEFAULT_PER_KM = 5000;
    const SAMPLE_DISTANCES = [1, 3, 5, 8];

    const form = document.getElementById('travelFeeForm');
    const tierList = document.getElementById('travelTierList');
    const sampleGrid = document.getElementById('travelFeeSampleGrid');
    const tierPreview = document.getElementById('travelFeeTierPreview');
    const defaultPerKmInput = document.getElementById('travelFeeDefaultPerKm');
    const statusChip = document.getElementById('travelFeeStatusChip');
    const updatedChip = document.getElementById('travelFeeUpdatedChip');
    const modeChip = document.getElementById('travelFeeModeChip');
    const saveButton = document.getElementById('btnSaveTravelFee');
    const addTierButton = document.getElementById('btnAddTravelTier');
    const resetButton = document.getElementById('btnResetTravelFeeForm');

    const money = (value) => `${Math.round(Number(value) || 0).toLocaleString('vi-VN')} đ`;
    const km = (value) => `${Number(value || 0).toLocaleString('vi-VN', { maximumFractionDigits: 2 })} km`;

    const setStatus = (message, tone = 'info') => {
        statusChip.textContent = message;
        statusChip.style.background = tone === 'success' ? '#dcfce7' : (tone === 'danger' ? '#fee2e2' : '#eff6ff');
        statusChip.style.color = tone === 'success' ? '#166534' : (tone === 'danger' ? '#991b1b' : '#1d4ed8');
    };

    const setUpdatedMeta = (payload) => {
        if (!payload?.updated_at && !payload?.updated_by) {
            updatedChip.classList.add('d-none');
            updatedChip.textContent = '';
            return;
        }

        const updatedAt = payload.updated_at ? new Date(payload.updated_at).toLocaleString('vi-VN') : '--';
        updatedChip.textContent = `Cập nhật bởi ${payload.updated_by || 'hệ thống'} lúc ${updatedAt}`;
        updatedChip.classList.remove('d-none');
    };

    const createTierRowMarkup = (tier = {}, index = 0) => `
        <div class="travel-tier-row" data-tier-row>
            <div>
                <div class="travel-tier-row__index">${index + 1}</div>
                <label class="form-label fw-semibold mb-2">Từ km</label>
                <div class="input-group">
                    <input
                        type="number"
                        min="0"
                        step="0.1"
                        class="form-control"
                        data-tier-from
                        value="${tier.from_km ?? ''}"
                        placeholder="0"
                    >
                    <span class="input-group-text">km</span>
                </div>
            </div>
            <div>
                <label class="form-label fw-semibold mb-2">Đến km</label>
                <div class="input-group">
                    <input
                        type="number"
                        min="0"
                        step="0.1"
                        class="form-control"
                        data-tier-to
                        value="${tier.to_km ?? ''}"
                        placeholder="2"
                    >
                    <span class="input-group-text">km</span>
                </div>
            </div>
            <div>
                <label class="form-label fw-semibold mb-2">Phí cố định</label>
                <div class="input-group">
                    <input
                        type="number"
                        min="0"
                        step="1000"
                        class="form-control"
                        data-tier-fee
                        value="${tier.fee ?? ''}"
                        placeholder="15000"
                    >
                    <span class="input-group-text">đ</span>
                </div>
            </div>
            <div class="text-md-end">
                <button type="button" class="btn btn-outline-danger w-100 w-md-auto" data-remove-tier>
                    <i class="fas fa-trash me-2"></i>Xóa
                </button>
            </div>
        </div>
    `;

    const readTierRows = () => Array.from(tierList.querySelectorAll('[data-tier-row]'))
        .map((row) => ({
            from_km: row.querySelector('[data-tier-from]')?.value ?? '',
            to_km: row.querySelector('[data-tier-to]')?.value ?? '',
            fee: row.querySelector('[data-tier-fee]')?.value ?? '',
        }))
        .filter((tier) => [tier.from_km, tier.to_km, tier.fee].some((value) => String(value).trim() !== ''))
        .map((tier) => ({
            from_km: Number(tier.from_km || 0),
            to_km: Number(tier.to_km || 0),
            fee: Number(tier.fee || 0),
        }))
        .sort((left, right) => {
            if (left.from_km === right.from_km) {
                return left.to_km - right.to_km;
            }

            return left.from_km - right.from_km;
        });

    const buildPayload = () => ({
        default_per_km: Number(defaultPerKmInput.value || DEFAULT_PER_KM),
        tiers: readTierRows(),
    });

    const resolveFee = (distanceKm, config) => {
        const tiers = Array.isArray(config?.tiers) ? config.tiers : [];
        const matchedTier = tiers.find((tier) => distanceKm >= Number(tier.from_km || 0) && distanceKm <= Number(tier.to_km || 0));

        if (matchedTier) {
            return Number(matchedTier.fee || 0);
        }

        return Math.round(distanceKm * Number(config?.default_per_km || DEFAULT_PER_KM));
    };

    const renderPreview = (config) => {
        const tiers = Array.isArray(config?.tiers) ? config.tiers : [];
        const modeLabel = tiers.length > 0 ? `Bậc khoảng cách (${tiers.length})` : 'Đơn giá / km';
        modeChip.textContent = modeLabel;

        sampleGrid.innerHTML = SAMPLE_DISTANCES.map((distanceKm) => `
            <div class="travel-preview-card">
                <div class="travel-preview-card__label">${km(distanceKm)}</div>
                <div class="travel-preview-card__value">${money(resolveFee(distanceKm, config))}</div>
            </div>
        `).join('');

        if (!tiers.length) {
            tierPreview.innerHTML = `
                <div class="travel-tier-empty">
                    Chưa có khoảng nào được cấu hình. Hệ thống đang dùng ${money(config?.default_per_km || DEFAULT_PER_KM)} / km.
                </div>
            `;
            return;
        }

        tierPreview.innerHTML = tiers.map((tier) => `
            <div class="travel-preview-item">
                <div>
                    <div class="travel-preview-item__title">${km(tier.from_km)} - ${km(tier.to_km)}</div>
                    <div class="travel-preview-item__meta">Khoảng này sẽ ưu tiên hơn đơn giá mặc định.</div>
                </div>
                <strong style="color:#0f172a;">${money(tier.fee)}</strong>
            </div>
        `).join('');
    };

    const renderTierRows = (tiers = []) => {
        if (!tiers.length) {
            tierList.innerHTML = createTierRowMarkup({}, 0);
            renderPreview(buildPayload());
            return;
        }

        tierList.innerHTML = tiers.map((tier, index) => createTierRowMarkup(tier, index)).join('');
        renderPreview({
            default_per_km: Number(defaultPerKmInput.value || DEFAULT_PER_KM),
            tiers,
        });
    };

    const fillForm = (config = {}) => {
        defaultPerKmInput.value = Number(config.default_per_km || DEFAULT_PER_KM);
        renderTierRows(Array.isArray(config.tiers) ? config.tiers : []);
    };

    const setSaving = (isSaving) => {
        saveButton.disabled = isSaving;
        addTierButton.disabled = isSaving;
        resetButton.disabled = isSaving;
        saveButton.innerHTML = isSaving
            ? '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...'
            : '<i class="fas fa-save me-2"></i>Lưu cấu hình';
    };

    const loadConfig = async () => {
        setStatus('Đang tải cấu hình...');

        try {
            const res = await callApi('/admin/travel-fee-config');
            if (!res.ok) {
                throw new Error(res.data?.message || 'Không tải được cấu hình phí đi lại');
            }

            const payload = res.data?.data || {};
            fillForm(payload.config || {});
            renderPreview(payload.config || {});
            setUpdatedMeta(payload);
            setStatus(payload.has_override ? 'Đang dùng cấu hình tùy chỉnh' : 'Đang dùng mặc định 5000 đ/km', 'success');
        } catch (error) {
            setStatus('Tải cấu hình thất bại', 'danger');
            showToast(error.message || 'Không tải được cấu hình phí đi lại', 'error');
        }
    };

    addTierButton.addEventListener('click', () => {
        const rows = readTierRows();
        rows.push({ from_km: '', to_km: '', fee: '' });
        renderTierRows(rows);
    });

    resetButton.addEventListener('click', () => {
        defaultPerKmInput.value = DEFAULT_PER_KM;
        renderTierRows([]);
        setStatus('Đã đưa form về mặc định cục bộ, bấm Lưu để áp dụng.', 'info');
    });

    tierList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-tier]');
        if (!button) {
            return;
        }

        const rows = Array.from(tierList.querySelectorAll('[data-tier-row]'));
        if (rows.length <= 1) {
            renderTierRows([]);
            return;
        }

        button.closest('[data-tier-row]')?.remove();
        renderTierRows(readTierRows());
    });

    tierList.addEventListener('input', () => {
        renderPreview(buildPayload());
    });

    defaultPerKmInput.addEventListener('input', () => {
        renderPreview(buildPayload());
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setSaving(true);

        try {
            const res = await callApi('/admin/travel-fee-config', 'PUT', buildPayload());
            if (!res.ok) {
                throw new Error(res.data?.message || 'Không lưu được cấu hình phí đi lại');
            }

            const payload = res.data?.data || {};
            fillForm(payload.config || {});
            renderPreview(payload.config || {});
            setUpdatedMeta(payload);
            setStatus('Đã lưu cấu hình phí đi lại', 'success');
            showToast(res.data?.message || 'Đã cập nhật phí đi lại');
        } catch (error) {
            setStatus('Lưu cấu hình thất bại', 'danger');
            showToast(error.message || 'Không lưu được cấu hình phí đi lại', 'error');
        } finally {
            setSaving(false);
        }
    });

    loadConfig();
});
