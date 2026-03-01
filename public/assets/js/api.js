// api.js - Core API Wrapper (Lõi gọi API thay thế Axios)

const BASE_URL = 'http://127.0.0.1:8000/api';

/**
 * Hàm gọi API dùng chung, tự động lấy Token hiện tại
 * @param {string} endpoint - VD: '/auth/login'
 * @param {string} method - 'GET', 'POST', 'PUT', 'DELETE'
 * @param {object|null} bodyData - Data truyền xuống backend
 * @returns Promise
 */
export async function callApi(endpoint, method = 'GET', bodyData = null) {
    const token = localStorage.getItem('access_token');

    // Cấu hình Headers (Luôn set JSON + dán Token nếu có)
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    };
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const options = {
        method: method,
        headers: headers,
    };

    if (bodyData && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(bodyData);
    }

    try {
        const response = await fetch(`${BASE_URL}${endpoint}`, options);

        // Bắt lỗi 401 Unauthorized -> Đá sang vòng Login
        if (response.status === 401) {
            localStorage.removeItem('access_token');
            localStorage.removeItem('user');
            // Chuyển hướng nhưng không văng lỗi khi đang đứng ở Login
            if (!window.location.pathname.includes('login.html')) {
                window.location.href = '/frontend/pages/login.html';
            }
            throw new Error("Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại!");
        }

        const data = await response.json();

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

export function logout() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('user');
    window.location.href = '/frontend/pages/login.html';
}
