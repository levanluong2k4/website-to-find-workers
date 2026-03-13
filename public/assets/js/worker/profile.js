import { callApi, getCurrentUser, logout } from '../api.js';

const user = getCurrentUser();
if (!user || !['worker', 'admin'].includes(user.role)) {
    logout();
}

document.addEventListener('DOMContentLoaded', async () => {
    const workerName = document.getElementById('workerName');
    const workerJoinDate = document.getElementById('workerJoinDate');
    const workerAvatar = document.getElementById('workerAvatar');
    const statRating = document.getElementById('statRating');
    const statReviewCount = document.getElementById('statReviewCount');
    const statCompleted = document.getElementById('statCompleted');

    const formProfile = document.getElementById('formWorkerProfile');
    const inputTrangThai = document.getElementById('inputTrangThai');
    const inputKinhNghiem = document.getElementById('inputKinhNghiem');
    const inputChungChi = document.getElementById('inputChungChi');
    const btnUpdateProfile = document.getElementById('btnUpdateProfile');
    const serviceCheckboxContainer = document.getElementById('serviceCheckboxContainer');

    const uploadAvatar = document.getElementById('uploadAvatar');

    // Mặc định avatar nếu chưa có
    if (!user.avatar) {
        workerAvatar.src = '/assets/images/user-default.png';
    } else {
        workerAvatar.src = user.avatar;
    }

    // Xử lý upload ảnh đại diện
    if (uploadAvatar) {
        uploadAvatar.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                // Giả định có showToast() từ api.js (nếu chưa có thì cảnh báo alert tạm)
                if (typeof showToast === 'function') showToast("File ảnh quá lớn, vui lòng chọn file dưới 5MB", "error");
                else alert("File ảnh quá lớn, vui lòng chọn file dưới 5MB");
                return;
            }

            const formData = new FormData();
            formData.append('avatar', file);

            try {
                if (typeof showToast === 'function') showToast("Đang tải ảnh lên...", "info");

                const res = await callApi('/user/avatar', 'POST', formData);

                if (res.ok) {
                    workerAvatar.src = res.data.avatar_url;
                    user.avatar = res.data.avatar_url; // Cập nhật local object
                    localStorage.setItem('user', JSON.stringify(user));
                    if (typeof showToast === 'function') showToast("Cập nhật ảnh đại diện thành công!");
                } else {
                    if (typeof showToast === 'function') showToast(res.data.message || "Tải ảnh thất bại", "error");
                    else alert(res.data.message || "Tải ảnh thất bại");
                }
            } catch (error) {
                if (typeof showToast === 'function') showToast("Lỗi kết nối khi tải ảnh", "error");
                else alert("Lỗi kết nối khi tải ảnh");
            } finally {
                uploadAvatar.value = ''; // Reset thẻ input file
            }
        });
    }

    // Tải danh sách danh mục dịch vụ
    const loadServices = async (selectedIds = []) => {
        try {
            const res = await callApi('/danh-muc-dich-vu', 'GET');
            if (res.ok && res.data) {
                serviceCheckboxContainer.innerHTML = '';
                res.data.forEach(srv => {
                    const isChecked = selectedIds.includes(srv.id) ? 'checked' : '';
                    serviceCheckboxContainer.innerHTML += `
                        <div class="col-md-6">
                            <div class="form-check custom-control">
                                <input class="form-check-input service-checkbox" type="checkbox" value="${srv.id}" id="srv_${srv.id}" ${isChecked}>
                                <label class="form-check-label user-select-none" for="srv_${srv.id}">
                                    ${srv.ten_dich_vu}
                                </label>
                            </div>
                        </div>
                    `;
                });
            }
        } catch (error) {
            serviceCheckboxContainer.innerHTML = '<span class="text-danger">Lỗi tải danh sách dịch vụ.</span>';
        }
    };

    // 1. Tải dữ liệu hồ sơ
    const loadProfile = async () => {
        try {
            // Lấy thông tin user cơ bản
            workerName.innerText = user.name;
            const joinDate = new Date(user.created_at).toLocaleDateString('vi-VN');
            workerJoinDate.innerText = joinDate;

            // Fetch hồ sơ thợ (API tìm theo user_id)
            const resHoSo = await callApi(`/ho-so-tho/${user.id}`, 'GET');

            let selectedServiceIds = [];

            if (resHoSo.ok && resHoSo.data) {
                const hoSo = resHoSo.data;
                // Đổ dữ liệu vào Form
                inputTrangThai.value = hoSo.dang_hoat_dong ? "1" : "0";
                inputKinhNghiem.value = hoSo.kinh_nghiem || '';
                inputChungChi.value = hoSo.chung_chi || '';

                // Cập nhật thống kê
                statRating.innerText = parseFloat(hoSo.danh_gia_trung_binh || 0).toFixed(1);
                statReviewCount.innerText = parseInt(hoSo.tong_so_danh_gia || 0);

                if (hoSo.user && hoSo.user.dich_vus) {
                    selectedServiceIds = hoSo.user.dich_vus.map(d => d.id);
                }
            }

            await loadServices(selectedServiceIds);

            // Lấy số đơn hoàn thành
            const resBookings = await callApi('/don-dat-lich', 'GET');
            if (resBookings.ok && resBookings.data) {
                let list = resBookings.data.data ? resBookings.data.data : (Array.isArray(resBookings.data) ? resBookings.data : []);
                const completedCount = list.filter(b => b.trang_thai === 'da_xong').length;
                statCompleted.innerText = completedCount;
            }

        } catch (error) {
            console.error('Error fetching profile:', error);
        }
    };

    // 2. Lắng nghe submit form
    if (formProfile) {
        formProfile.addEventListener('submit', async (e) => {
            e.preventDefault();

            // UI state
            const originalText = btnUpdateProfile.innerText;
            btnUpdateProfile.disabled = true;
            btnUpdateProfile.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang lưu...';

            const checkboxes = document.querySelectorAll('.service-checkbox:checked');
            const dich_vu_ids = Array.from(checkboxes).map(cb => parseInt(cb.value));

            const payload = {
                dang_hoat_dong: inputTrangThai.value === "1",
                kinh_nghiem: inputKinhNghiem.value,
                chung_chi: inputChungChi.value,
                dich_vu_ids: dich_vu_ids
            };

            try {
                const res = await callApi('/ho-so-tho', 'PUT', payload);
                if (res.ok) {
                    alert('Đã cập nhật hồ sơ thành công!');
                } else {
                    alert(res.data?.message || 'Có lỗi xảy ra, vui lòng thử lại.');
                }

            } catch (error) {
                console.error('Lỗi khi cập nhật:', error);
                alert('Có lỗi xảy ra, vui lòng thử lại sau.');
            } finally {
                btnUpdateProfile.disabled = false;
                btnUpdateProfile.innerText = originalText;
            }
        });
    }

    // Init
    loadProfile();
});
