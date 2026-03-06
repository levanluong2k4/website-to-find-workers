import { callApi, requireRole, showToast } from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    requireRole('admin');

    const tbody = document.getElementById('servicesTableBody');
    const form = document.getElementById('serviceForm');
    const modal = new bootstrap.Modal(document.getElementById('serviceModal'));

    const fetchDatas = async () => {
        try {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="text-muted mt-2 mb-0">Đang tải...</p></td></tr>`;
            const res = await callApi('/danh-muc-dich-vu'); // Public Endpoint

            if (res.ok && res.data) {
                renderTable(res.data);
            }
        } catch (error) {
            console.error('Fetch error:', error);
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Lỗi kết nối máy chủ!</td></tr>`;
        }
    };

    const renderTable = (services) => {
        if (!services || services.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted"><p class="mb-0 fw-semibold">Không có dịch vụ nào.</p></td></tr>`;
            return;
        }

        tbody.innerHTML = services.map(srv => {
            const iconUrl = srv.icon_url || '/assets/images/placeholder.png'; // Fallback
            return `
                <tr>
                    <td class="ps-4 fw-bold text-muted">#${srv.id}</td>
                    <td><img src="${iconUrl}" alt="${srv.ten_dich_vu}" class="img-thu-nail"></td>
                    <td class="fw-bold text-dark">${srv.ten_dich_vu}</td>
                    <td class="text-muted text-truncate" style="max-width: 250px;">${srv.mo_ta || '--'}</td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-primary btn-edit me-1" data-id="${srv.id}" data-name="${srv.ten_dich_vu}" data-desc="${srv.mo_ta || ''}" data-icon="${srv.icon_url || ''}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${srv.id}" data-name="${srv.ten_dich_vu}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        attachListeners();
    };

    const attachListeners = () => {
        // Edit 
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tr = e.currentTarget;
                document.getElementById('serviceModalLabel').innerText = 'Chỉnh sửa Dịch vụ';
                document.getElementById('serviceId').value = tr.getAttribute('data-id');
                document.getElementById('serviceName').value = tr.getAttribute('data-name');
                document.getElementById('serviceDesc').value = tr.getAttribute('data-desc');
                document.getElementById('serviceIcon').value = tr.getAttribute('data-icon');
                modal.show();
            });
        });

        // Delete
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.getAttribute('data-id');
                const name = e.currentTarget.getAttribute('data-name');

                Swal.fire({
                    title: 'Xóa Dịch Vụ?',
                    text: `Bạn có chắc muốn xóa vĩnh viễn "${name}"? Thao tác này không thể hoàn tác nếu đã có dữ liệu.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-trash me-2"></i>Xóa ngay',
                    cancelButtonText: 'Hủy'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const res = await callApi(`/danh-muc-dich-vu/${id}`, 'DELETE');
                            if (res.ok) {
                                showToast('Đã xóa thành công', 'success');
                                fetchDatas();
                            } else {
                                showToast(res.message || 'Có lỗi xảy ra', 'error');
                            }
                        } catch (err) {
                            showToast('Lỗi xoá dữ liệu', 'error');
                        }
                    }
                });
            });
        });
    };

    // Thêm mới button reset form
    document.getElementById('btnAddService').addEventListener('click', () => {
        document.getElementById('serviceModalLabel').innerText = 'Thêm Dịch vụ Mới';
        document.getElementById('serviceForm').reset();
        document.getElementById('serviceId').value = '';
    });

    // Form Submit (Thêm hoặc Sửa)
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('serviceId').value;
        const payload = {
            ten_dich_vu: document.getElementById('serviceName').value,
            mo_ta: document.getElementById('serviceDesc').value,
            icon_url: document.getElementById('serviceIcon').value,
        };

        const btnSave = document.getElementById('btnSaveService');
        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...';

        try {
            let res;
            if (id) {
                // Sửa
                res = await callApi(`/danh-muc-dich-vu/${id}`, 'PUT', payload);
            } else {
                // Thêm mới
                res = await callApi(`/danh-muc-dich-vu`, 'POST', payload);
            }

            if (res.ok) {
                showToast(res.message, 'success');
                modal.hide();
                fetchDatas();
            } else {
                showToast(res.message || 'Lỗi lưu', 'error');
                if (res.errors) console.log(res.errors);
            }
        } catch (error) {
            showToast('Lỗi mạng', 'error');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fas fa-save me-2"></i>Lưu Dịch Vụ';
        }
    });

    // Init
    fetchDatas();
});
