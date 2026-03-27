// api.js - Core API Wrapper (Lõi gọi API thay thế Axios)

const BASE_URL = window.location.origin + '/api';

/**
 * Hàm gọi API dùng chung, tự động lấy Token hiện tại
 * @param {string} endpoint - VD: '/auth/login'
 * @param {string} method - 'GET', 'POST', 'PUT', 'DELETE'
 * @param {object|null} bodyData - Data truyền xuống backend
 * @returns Promise
 */
export async function callApi(endpoint, method = 'GET', bodyData = null) {
    let token = localStorage.getItem('access_token');

    // Tránh trường hợp token bị lưu thành chuỗi "undefined" hoặc "null"
    if (token === 'undefined' || token === 'null' || !token) {
        token = null;
    }

    // Cấu hình Headers mặc định
    const headers = {
        'Accept': 'application/json',
    };

    // Nếu KHÔNG phải FormData thì ép gửi kiểu JSON
    if (!(bodyData instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const options = {
        method: method,
        headers: headers,
    };

    if (bodyData && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase())) {
        options.body = (bodyData instanceof FormData) ? bodyData : JSON.stringify(bodyData);
    }

    try {
        const response = await fetch(`${BASE_URL}${endpoint}`, options);

        // Bắt lỗi 401 Unauthorized -> Đá sang vòng Login
        if (response.status === 401) {
            // Không tự động redirect hay throw lỗi "hết hạn" nếu đang gọi API Login
            if (endpoint.includes('/login')) {
                const errorData = await response.json();
                return {
                    status: response.status,
                    ok: response.ok,
                    data: errorData
                };
            }

            localStorage.removeItem('access_token');
            localStorage.removeItem('user');
            // Chuyển hướng nhưng không văng lỗi khi đang đứng ở Login
            if (!window.location.pathname.includes('/login')) {
                window.location.href = '/login';
            }
            throw new Error("Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại!");
        }

        const data = await response.json();

        if (response.status === 403 && data?.requires_phone_verification) {
            const verifyUrl = data.phone_verification_url || '/verify-phone';
            if (!window.location.pathname.includes('/verify-phone')) {
                window.location.href = verifyUrl;
            }
        }

        // Trả về kèm Status để component biết cách xử lý lỗi 400, 422...
        return {
            status: response.status,
            ok: response.ok,
            data: data
        };

    } catch (error) {
        console.error("Lỗi gọi API:", error);
        throw error;
    }
}

/**
 * Các hàm tiện ích (Utils)
 */
export function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

export function saveUserSession(token, user) {
    localStorage.setItem('access_token', token);
    localStorage.setItem('user', JSON.stringify(user));
}

export function getCurrentUser() {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
}

export function resolveHomePathByRole(role) {
    if (role === 'admin') return '/admin/dashboard';
    if (role === 'worker') return '/worker/dashboard';
    if (role === 'customer') return '/customer/home';
    return '/';
}

export function redirectAuthenticatedUser() {
    const token = localStorage.getItem('access_token');
    const user = getCurrentUser();

    if (!token || token === 'undefined' || token === 'null' || !user?.role) {
        return null;
    }

    const targetPath = resolveHomePathByRole(user.role);

    if (window.location.pathname !== targetPath) {
        window.location.replace(targetPath);
    }

    return targetPath;
}

export function logout() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('user');
    window.location.href = '/login';
}

export function requireRole(role) {
    const user = getCurrentUser();
    if (!user) {
        window.location.href = '/login';
        return;
    }

    if (user.role !== role && user.role !== 'admin') {
        // Có thể redirect dựa vào role hoặc về /
        alert('Bạn không có quyền truy cập trang này!');
        if (user.role === 'customer') window.location.href = '/customer/home';
        else if (user.role === 'worker') window.location.href = '/worker/dashboard';
        else if (user.role === 'admin') window.location.href = '/admin/dashboard';
        else window.location.href = '/';
    }
}

// Global Notification Helpers
export const showToast = (message, type = 'success') => {
    let bgColor = type === 'success' ? '#10b981' : '#ef4444';
    if (typeof Toastify !== 'undefined') {
        Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            style: {
                background: bgColor,
                borderRadius: '8px',
                fontFamily: 'Roboto, sans-serif',
                fontWeight: '500',
                boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)'
            }
        }).showToast();
    } else {
        alert(message);
    }
};

export const confirmAction = async (title, text, confirmBtnText = 'Xác nhận') => {
    if (typeof Swal !== 'undefined') {
        return await Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0f172a',
            cancelButtonColor: '#e2e8f0',
            confirmButtonText: confirmBtnText,
            cancelButtonText: '<span style="color:#0f172a">Hủy</span>',
            customClass: {
                confirmButton: 'btn btn-primary px-4 border-0',
                cancelButton: 'btn btn-light px-4 border ms-2 text-dark'
            },
            buttonsStyling: false
        });
    } else {
        return { isConfirmed: confirm(text || title) };
    }
};
