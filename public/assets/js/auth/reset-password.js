import { callApi, showToast } from '/assets/js/api.js';

const config = window.resetPasswordConfig || {};
const requestedRole = ['customer', 'worker'].includes(config.requestedRole) ? config.requestedRole : '';
const form = document.getElementById('resetPasswordForm');
const emailInput = document.getElementById('resetPasswordEmail');
const passwordInput = document.getElementById('resetPasswordValue');
const confirmationInput = document.getElementById('resetPasswordConfirmation');
const submitButton = document.getElementById('resetPasswordSubmit');
const stateBox = document.getElementById('resetPasswordState');

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

const buildLoginUrl = () => {
  const loginUrl = new URL(config.loginUrl || `${window.location.origin}/login`, window.location.origin);

  if (requestedRole) {
    loginUrl.searchParams.set('role', requestedRole);
  }

  return loginUrl.toString();
};

const setState = (message, type = 'success') => {
  stateBox.textContent = message;
  stateBox.className = `password-state is-visible ${type === 'error' ? 'is-error' : 'is-success'}`;
};

const setLoading = (isLoading) => {
  submitButton.disabled = isLoading;
  submitButton.innerHTML = isLoading
    ? '<span class="password-spinner"></span> Đang lưu mật khẩu mới...'
    : 'Lưu mật khẩu mới <span class="material-symbols-outlined" style="font-size:1rem;">check_circle</span>';
};

emailInput.value = config.email || '';

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const email = emailInput.value.trim();
  const password = passwordInput.value;
  const passwordConfirmation = confirmationInput.value;
  const token = config.token || '';

  if (!email || !token) {
    const message = 'Liên kết đặt lại mật khẩu không hợp lệ.';
    setState(message, 'error');
    showToast(message, 'error');
    return;
  }

  if (password !== passwordConfirmation) {
    const message = 'Xác nhận mật khẩu không khớp.';
    setState(message, 'error');
    showToast(message, 'error');
    return;
  }

  setLoading(true);
  stateBox.className = 'password-state';

  try {
    const response = await callApi(config.apiEndpoint || '/reset-password', 'POST', {
      email,
      token,
      password,
      password_confirmation: passwordConfirmation,
    });

    if (!response.ok) {
      const message = resolveMessage(response.data, 'Không thể đặt lại mật khẩu.');
      setState(message, 'error');
      showToast(message, 'error');
      return;
    }

    const message = resolveMessage(response.data, 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.');
    setState(message, 'success');
    showToast(message);
    form.reset();
    emailInput.value = email;

    window.setTimeout(() => {
      window.location.href = buildLoginUrl();
    }, 1200);
  } catch (error) {
    const message = error?.message || 'Lỗi kết nối máy chủ.';
    setState(message, 'error');
    showToast(message, 'error');
  } finally {
    setLoading(false);
  }
});
