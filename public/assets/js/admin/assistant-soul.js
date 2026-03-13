import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const form = document.getElementById('assistantSoulForm');
    const statusChip = document.getElementById('assistantSoulStatus');
    const updatedMeta = document.getElementById('assistantSoulUpdatedMeta');
    const saveButton = document.getElementById('btnSaveAssistantSoul');
    const resetButton = document.getElementById('btnResetAssistantSoul');

    const fields = {
        name: document.getElementById('assistantSoulName'),
        role: document.getElementById('assistantSoulRole'),
        outputStyle: document.getElementById('assistantSoulOutputStyle'),
        identityRules: document.getElementById('assistantSoulIdentityRules'),
        requiredRules: document.getElementById('assistantSoulRequiredRules'),
        responseGoals: document.getElementById('assistantSoulResponseGoals'),
        textOrder: document.getElementById('assistantSoulTextOrder'),
        jsonKeys: document.getElementById('assistantSoulJsonKeys'),
        serviceProcess: document.getElementById('assistantSoulServiceProcess'),
        emergencyKeywords: document.getElementById('assistantSoulEmergencyKeywords'),
        emergencyLines: document.getElementById('assistantSoulEmergencyLines'),
        fallbackPriceLine: document.getElementById('assistantSoulFallbackPriceLine'),
        priceLineTemplate: document.getElementById('assistantSoulPriceLineTemplate'),
    };

    const linesToArray = (value) => (value || '')
        .split('\n')
        .map((item) => item.trim())
        .filter(Boolean);

    const arrayToLines = (items) => Array.isArray(items) ? items.join('\n') : '';

    const setStatus = (message, variant = 'info') => {
        statusChip.textContent = message;
        statusChip.style.background = variant === 'success' ? '#dcfce7' : (variant === 'danger' ? '#fee2e2' : '#eff6ff');
        statusChip.style.color = variant === 'success' ? '#166534' : (variant === 'danger' ? '#991b1b' : '#1d4ed8');
    };

    const setUpdatedMeta = (payload) => {
        const updatedAt = payload?.updated_at;
        const updatedBy = payload?.updated_by;

        if (!updatedAt && !updatedBy) {
            updatedMeta.classList.add('d-none');
            updatedMeta.textContent = '';
            return;
        }

        const dateText = updatedAt ? new Date(updatedAt).toLocaleString('vi-VN') : '--';
        updatedMeta.textContent = `Cap nhat boi ${updatedBy || 'he thong'} luc ${dateText}`;
        updatedMeta.classList.remove('d-none');
    };

    const fillForm = (config) => {
        fields.name.value = config?.name || '';
        fields.role.value = config?.role || '';
        fields.outputStyle.value = config?.output_style || '';
        fields.identityRules.value = arrayToLines(config?.identity_rules);
        fields.requiredRules.value = arrayToLines(config?.required_rules);
        fields.responseGoals.value = arrayToLines(config?.response_goals);
        fields.textOrder.value = arrayToLines(config?.assistant_text_order);
        fields.jsonKeys.value = arrayToLines(config?.json_keys);
        fields.serviceProcess.value = arrayToLines(config?.service_process);
        fields.emergencyKeywords.value = arrayToLines(config?.emergency_keywords);
        fields.emergencyLines.value = arrayToLines(config?.emergency_response?.lines);
        fields.fallbackPriceLine.value = config?.emergency_response?.fallback_price_line || '';
        fields.priceLineTemplate.value = config?.emergency_response?.price_line_template || '';
    };

    const buildPayload = () => ({
        name: fields.name.value.trim(),
        role: fields.role.value.trim(),
        output_style: fields.outputStyle.value.trim(),
        identity_rules: linesToArray(fields.identityRules.value),
        required_rules: linesToArray(fields.requiredRules.value),
        response_goals: linesToArray(fields.responseGoals.value),
        assistant_text_order: linesToArray(fields.textOrder.value),
        json_keys: linesToArray(fields.jsonKeys.value),
        service_process: linesToArray(fields.serviceProcess.value),
        emergency_keywords: linesToArray(fields.emergencyKeywords.value),
        emergency_response: {
            lines: linesToArray(fields.emergencyLines.value),
            fallback_price_line: fields.fallbackPriceLine.value.trim(),
            price_line_template: fields.priceLineTemplate.value.trim(),
        },
    });

    const setSaving = (saving) => {
        saveButton.disabled = saving;
        resetButton.disabled = saving;
        saveButton.textContent = saving ? 'Dang luu...' : 'Luu cau hinh';
    };

    const loadConfig = async () => {
        setStatus('Dang tai cau hinh...');

        try {
            const res = await callApi('/admin/assistant-soul', 'GET');
            if (!res.ok) {
                throw new Error(res.data?.message || 'Khong tai duoc cau hinh');
            }

            const data = res.data?.data || {};
            fillForm(data.config || {});
            setStatus(data.has_override ? 'Dang dung cau hinh tuy chinh' : 'Dang dung cau hinh mac dinh', 'success');
            setUpdatedMeta(data);
        } catch (error) {
            setStatus('Tai cau hinh that bai', 'danger');
            showToast(error.message || 'Khong tai duoc ASSISTANT SOUL', 'error');
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setSaving(true);

        try {
            const res = await callApi('/admin/assistant-soul', 'PUT', buildPayload());
            if (!res.ok) {
                throw new Error(res.data?.message || 'Khong luu duoc cau hinh');
            }

            const data = res.data?.data || {};
            fillForm(data.config || {});
            setStatus('Da luu cau hinh tuy chinh', 'success');
            setUpdatedMeta(data);
            showToast('Da cap nhat ASSISTANT SOUL', 'success');
        } catch (error) {
            setStatus('Luu cau hinh that bai', 'danger');
            showToast(error.message || 'Khong luu duoc ASSISTANT SOUL', 'error');
        } finally {
            setSaving(false);
        }
    });

    resetButton.addEventListener('click', async () => {
        const result = await Swal.fire({
            title: 'Khoi phuc mac dinh?',
            text: 'Toan bo cau hinh tuy chinh hien tai se bi go bo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Khoi phuc',
            cancelButtonText: 'Huy',
            confirmButtonColor: '#dc2626',
        });

        if (!result.isConfirmed) {
            return;
        }

        setSaving(true);

        try {
            const res = await callApi('/admin/assistant-soul', 'DELETE');
            if (!res.ok) {
                throw new Error(res.data?.message || 'Khong khoi phuc duoc cau hinh');
            }

            const data = res.data?.data || {};
            fillForm(data.config || {});
            setStatus('Da khoi phuc cau hinh mac dinh', 'success');
            setUpdatedMeta(data);
            showToast('Da khoi phuc ASSISTANT SOUL mac dinh', 'success');
        } catch (error) {
            setStatus('Khoi phuc that bai', 'danger');
            showToast(error.message || 'Khong khoi phuc duoc cau hinh', 'error');
        } finally {
            setSaving(false);
        }
    });

    loadConfig();
});
