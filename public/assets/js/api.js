// api.js - shared API helpers

const BASE_URL = `${window.location.origin}/api`;
const INVALID_STORAGE_TOKEN_VALUES = new Set(['', 'undefined', 'null']);

function getStoredAccessToken() {
    const token = localStorage.getItem('access_token');

    if (!token || INVALID_STORAGE_TOKEN_VALUES.has(token)) {
        return null;
    }

    return token;
}

function clearStoredSession() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('user');
}

function redirectToLogin() {
    if (!window.location.pathname.includes('/login')) {
        window.location.replace('/login');
    }
}

function buildAuthError(message = 'Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại!') {
    const error = new Error(message);
    error.isAuthError = true;

    return error;
}

async function readJsonResponse(response) {
    if (response.status === 204) {
        return null;
    }

    return response.json();
}

export async function callApi(endpoint, method = 'GET', bodyData = null) {
    const token = getStoredAccessToken();
    const headers = {
        Accept: 'application/json',
    };

    if (!(bodyData instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const options = {
        method,
        headers,
    };

    if (bodyData && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase())) {
        options.body = bodyData instanceof FormData ? bodyData : JSON.stringify(bodyData);
    }

    try {
        const response = await fetch(`${BASE_URL}${endpoint}`, options);

        if (response.status === 401) {
            if (endpoint.includes('/login')) {
                const errorData = await readJsonResponse(response);

                return {
                    status: response.status,
                    ok: response.ok,
                    data: errorData,
                };
            }

            clearStoredSession();
            redirectToLogin();
            throw buildAuthError();
        }

        const data = await readJsonResponse(response);

        if (response.status === 403 && data?.requires_phone_verification) {
            const verifyUrl = data.phone_verification_url || '/verify-phone';

            if (!window.location.pathname.includes('/verify-phone')) {
                window.location.replace(verifyUrl);
            }
        }

        return {
            status: response.status,
            ok: response.ok,
            data,
        };
    } catch (error) {
        console.error('Lỗi gọi API:', error);
        throw error;
    }
}

export async function downloadApiFile(endpoint, fallbackFilename = 'export.csv') {
    const token = getStoredAccessToken();
    const headers = {
        Accept: 'text/csv,application/octet-stream,application/json',
    };

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(`${BASE_URL}${endpoint}`, {
        method: 'GET',
        headers,
    });

    if (response.status === 401) {
        clearStoredSession();
        redirectToLogin();
        throw buildAuthError();
    }

    if (!response.ok) {
        const errorText = await response.text();

        try {
            const parsed = JSON.parse(errorText);
            throw new Error(parsed?.message || 'Không thể tải file');
        } catch {
            throw new Error('Không thể tải file');
        }
    }

    const blob = await response.blob();
    const contentDisposition = response.headers.get('Content-Disposition') || '';
    const matchedFilename = contentDisposition.match(/filename="?([^"]+)"?/i)?.[1];
    const filename = matchedFilename || fallbackFilename;
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}

export function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

export function saveUserSession(token, user) {
    localStorage.setItem('access_token', token);
    localStorage.setItem('user', JSON.stringify(user));
}

export function getCurrentUser() {
    const userStr = localStorage.getItem('user');

    if (!userStr) {
        return null;
    }

    try {
        return JSON.parse(userStr);
    } catch {
        clearStoredSession();
        return null;
    }
}

export function resolveHomePathByRole(role) {
    if (role === 'admin') return '/admin/dashboard';
    if (role === 'worker') return '/worker/dashboard';
    if (role === 'customer') return '/customer/home';

    return '/';
}

export function redirectAuthenticatedUser() {
    const token = getStoredAccessToken();
    const user = getCurrentUser();

    if (!token || !user?.role) {
        return null;
    }

    const targetPath = resolveHomePathByRole(user.role);

    if (window.location.pathname !== targetPath) {
        window.location.replace(targetPath);
    }

    return targetPath;
}

export function logout() {
    clearStoredSession();
    window.location.replace('/login');
}

export function requireRole(role) {
    const token = getStoredAccessToken();
    const user = getCurrentUser();

    if (!token || !user) {
        clearStoredSession();
        redirectToLogin();
        return false;
    }

    if (user.role !== role && user.role !== 'admin') {
        alert('Bạn không có quyền truy cập trang này!');

        if (user.role === 'customer') window.location.href = '/customer/home';
        else if (user.role === 'worker') window.location.href = '/worker/dashboard';
        else if (user.role === 'admin') window.location.href = '/admin/dashboard';
        else window.location.href = '/';

        return false;
    }

    return true;
}

export function isAuthError(error) {
    return Boolean(error?.isAuthError);
}

export const showToast = (message, type = 'success') => {
    const bgColor = type === 'success' ? '#10b981' : '#ef4444';

    if (typeof Toastify !== 'undefined') {
        Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: 'top',
            position: 'right',
            stopOnFocus: true,
            style: {
                background: bgColor,
                borderRadius: '8px',
                fontFamily: "'Be Vietnam Pro', sans-serif",
                fontWeight: '500',
                boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)',
            },
        }).showToast();
    } else {
        alert(message);
    }
};

export const confirmAction = async (title, text, confirmBtnText = 'Xác nhận') => {
    if (typeof Swal !== 'undefined') {
        return Swal.fire({
            title,
            text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0f172a',
            cancelButtonColor: '#e2e8f0',
            confirmButtonText: confirmBtnText,
            cancelButtonText: '<span style="color:#0f172a">Hủy</span>',
            customClass: {
                confirmButton: 'btn btn-primary px-4 border-0',
                cancelButton: 'btn btn-light px-4 border ms-2 text-dark',
            },
            buttonsStyling: false,
        });
    }

    return { isConfirmed: confirm(text || title) };
};
