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
        updatedMeta.textContent = `C\u1eadp nh\u1eadt b\u1edfi ${updatedBy || 'h\u1ec7 th\u1ed1ng'} l\u00fac ${dateText}`;
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
        saveButton.textContent = saving ? '\u0110ang l\u01b0u...' : 'L\u01b0u c\u1ea5u h\u00ecnh';
    };

    const loadConfig = async () => {
        setStatus('\u0110ang t\u1ea3i c\u1ea5u h\u00ecnh...');

        try {
            const res = await callApi('/admin/assistant-soul', 'GET');
            if (!res.ok) {
                throw new Error(res.data?.message || 'Kh\u00f4ng t\u1ea3i \u0111\u01b0\u1ee3c c\u1ea5u h\u00ecnh');
            }

            const data = res.data?.data || {};
            fillForm(data.config || {});
            setStatus(data.has_override ? '\u0110ang d\u00f9ng c\u1ea5u h\u00ecnh t\u00f9y ch\u1ec9nh' : '\u0110ang d\u00f9ng c\u1ea5u h\u00ecnh m\u1eb7c \u0111\u1ecbnh', 'success');
            setUpdatedMeta(data);
        } catch (error) {
            setStatus('T\u1ea3i c\u1ea5u h\u00ecnh th\u1ea5t b\u1ea1i', 'danger');
            showToast(error.message || 'Kh\u00f4ng t\u1ea3i \u0111\u01b0\u1ee3c ASSISTANT SOUL', 'error');
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setSaving(true);

        try {
            const res = await callApi('/admin/assistant-soul', 'PUT', buildPayload());
            if (!res.ok) {
                throw new Error(res.data?.message || 'Kh\u00f4ng l\u01b0u \u0111\u01b0\u1ee3c c\u1ea5u h\u00ecnh');
            }

            const data = res.data?.data || {};
            fillForm(data.config || {});
            setStatus('\u0110\u00e3 l\u01b0u c\u1ea5u h\u00ecnh t\u00f9y ch\u1ec9nh', 'success');
            setUpdatedMeta(data);
            showToast('\u0110\u00e3 c\u1eadp nh\u1eadt ASSISTANT SOUL', 'success');
        } catch (error) {
            setStatus('L\u01b0u c\u1ea5u h\u00ecnh th\u1ea5t b\u1ea1i', 'danger');
            showToast(error.message || 'Kh\u00f4ng l\u01b0u \u0111\u01b0\u1ee3c ASSISTANT SOUL', 'error');
        } finally {
            setSaving(false);
        }
    });

    resetButton.addEventListener('click', async () => {
        const result = await Swal.fire({
            title: 'Kh\u00f4i ph\u1ee5c m\u1eb7c \u0111\u1ecbnh?',
            text: 'To\u00e0n b\u1ed9 c\u1ea5u h\u00ecnh t\u00f9y ch\u1ec9nh hi\u1ec7n t\u1ea1i s\u1ebd b\u1ecb g\u1ee1 b\u1ecf.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Kh\u00f4i ph\u1ee5c',
            cancelButtonText: 'H\u1ee7y',
            confirmButtonColor: '#dc2626',
        });

        if (!result.isConfirmed) {
            return;
        }

        setSaving(true);

        try {
            const res = await callApi('/admin/assistant-soul', 'DELETE');
            if (!res.ok) {
                throw new Error(res.data?.message || 'Kh\u00f4ng kh\u00f4i ph\u1ee5c \u0111\u01b0\u1ee3c c\u1ea5u h\u00ecnh');
            }

            const data = res.data?.data || {};
            fillForm(data.config || {});
            setStatus('\u0110\u00e3 kh\u00f4i ph\u1ee5c c\u1ea5u h\u00ecnh m\u1eb7c \u0111\u1ecbnh', 'success');
            setUpdatedMeta(data);
            showToast('\u0110\u00e3 kh\u00f4i ph\u1ee5c ASSISTANT SOUL m\u1eb7c \u0111\u1ecbnh', 'success');
        } catch (error) {
            setStatus('Kh\u00f4i ph\u1ee5c th\u1ea5t b\u1ea1i', 'danger');
            showToast(error.message || 'Kh\u00f4ng kh\u00f4i ph\u1ee5c \u0111\u01b0\u1ee3c c\u1ea5u h\u00ecnh', 'error');
        } finally {
            setSaving(false);
        }
    });

    loadConfig();
});
