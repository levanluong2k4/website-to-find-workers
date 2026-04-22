import { callApi, showToast } from '/assets/js/api.js';

const config = window.forgotPasswordConfig || {};
const requestedRole = ['customer', 'worker', 'admin'].includes(config.requestedRole) ? config.requestedRole : '';
const form = document.getElementById('forgotPasswordForm');
const emailInput = document.getElementById('forgotPasswordEmail');
const submitButton = document.getElementById('forgotPasswordSubmit');
const stateBox = document.getElementById('forgotPasswordState');
const debugBox = document.getElementById('forgotPasswordDebug');
const debugMessage = document.getElementById('forgotPasswordDebugMessage');
const debugLink = document.getElementById('forgotPasswordDebugLink');

const resolveMessage = (payload, fallback) => {
  if (payload?.message) {
    return payload.message;
  }

  if (payload?.errors) {
    const firstError = Object.values(payload.errors)[0];
    if (Array.isArray(firstError) && firstError.length) {
      return firstError[0];
    }
  }

  return fallback;
};

const setState = (message, type = 'success') => {
  stateBox.textContent = message;
  stateBox.className = `password-state is-visible ${type === 'error' ? 'is-error' : 'is-success'}`;
};

const setLoading = (isLoading) => {
  submitButton.disabled = isLoading;
  submitButton.innerHTML = isLoading
    ? '<span class="password-spinner"></span> Đang gửi liên kết...'
    : 'Gửi liên kết đặt lại <span class="material-symbols-outlined" style="font-size:1rem;">north_east</span>';
};

emailInput.value = config.prefillEmail || '';

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const email = emailInput.value.trim();
  if (!email) {
    setState('Vui lòng nhập email.', 'error');
    showToast('Vui lòng nhập email.', 'error');
    return;
  }

  setLoading(true);
  stateBox.className = 'password-state';
  debugBox.className = 'password-debug';
  debugLink.hidden = true;
  debugLink.removeAttribute('href');

  try {
    const response = await callApi(config.apiEndpoint || '/forgot-password', 'POST', {
      email,
      role: requestedRole || undefined,
    });

    if (!response.ok) {
      const message = resolveMessage(response.data, 'Không thể gửi liên kết đặt lại mật khẩu.');
      setState(message, 'error');
      showToast(message, 'error');
      return;
    }

    const message = resolveMessage(response.data, 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi liên kết đặt lại mật khẩu.');
    setState(message, 'success');
    showToast(message);

    if (response.data?.debug_reset_url) {
      debugBox.className = 'password-debug is-visible';
      debugMessage.textContent = 'Môi trường local đã trả về liên kết reset trực tiếp để bạn kiểm tra nhanh.';
      debugLink.href = response.data.debug_reset_url;
      debugLink.hidden = false;
    }
  } catch (error) {
    const message = error?.message || 'Lỗi kết nối máy chủ.';
    setState(message, 'error');
    showToast(message, 'error');
  } finally {
    setLoading(false);
  }
});
