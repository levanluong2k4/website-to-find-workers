export function createCompleteBookingModalController({
  baseUrl,
  refs,
  getAllBookings,
  hasUpdatedPricing,
  openCostModal,
  showToast,
  escapeHtml,
  formatMoney,
  getCustomerName,
  getBookingServiceNames,
  getBookingTotal,
  getBookingPaymentMethod,
  afterSubmit,
}) {
  const {
    form,
    modalInstance,
    bookingIdInput,
    customerName,
    serviceName,
    bookingTotal,
    statusBadge,
    paymentMethodTitle,
    paymentMethodHint,
    paymentMethodBadge,
    paymentMethodInputs,
    paymentOptions,
    pricingAlert,
    workflowList,
    imageUploadPreview,
    videoUploadPreview,
    imageInput,
    videoInput,
    submitButton,
  } = refs;

  let initialized = false;

  const getSelectedPaymentMethod = () => paymentMethodInputs.find((input) => input.checked)?.value === 'transfer' ? 'transfer' : 'cod';

  const syncPaymentMethodUi = (paymentMethod = getSelectedPaymentMethod()) => {
    const isTransfer = paymentMethod === 'transfer';

    paymentOptions.forEach((option) => {
      const input = option.querySelector('input[name="phuong_thuc_thanh_toan"]');
      option.classList.toggle('is-active', input?.checked === true);
    });

    if (paymentMethodTitle) {
      paymentMethodTitle.textContent = isTransfer ? 'Chuyển khoản online' : 'Tiền mặt';
    }

    if (paymentMethodHint) {
      paymentMethodHint.textContent = isTransfer
        ? 'Sau khi bạn xác nhận hoàn thành, khách bắt buộc phải vào tài khoản để thanh toán trực tuyến. Đơn chỉ hoàn tất khi giao dịch thành công.'
        : 'Khi bạn xác nhận hoàn thành, hệ thống sẽ ghi nhận đơn đã hoàn tất ngay với phương thức tiền mặt.';
    }

    if (paymentMethodBadge) {
      paymentMethodBadge.textContent = isTransfer ? 'Chờ khách chuyển khoản' : 'Hoàn tất ngay';
    }

    if (statusBadge) {
      statusBadge.textContent = isTransfer ? 'Chờ khách thanh toán' : 'Hoàn tất ngay';
    }

    if (submitButton) {
      submitButton.innerHTML = isTransfer
        ? '<span class="material-symbols-outlined">payments</span>Gửi yêu cầu chuyển khoản'
        : '<span class="material-symbols-outlined">task_alt</span>Xác nhận hoàn thành';
    }

    if (workflowList) {
      workflowList.innerHTML = `
        <div class="dispatch-workflow__item is-done">
          <span class="dispatch-workflow__icon material-symbols-outlined">check</span>
          <span>Đã bắt đầu sửa</span>
        </div>
        <div class="dispatch-workflow__item is-done">
          <span class="dispatch-workflow__icon material-symbols-outlined">check</span>
          <span>Đã cập nhật chi phí</span>
        </div>
        <div class="dispatch-workflow__item is-current">
          <span class="dispatch-workflow__icon material-symbols-outlined">priority_high</span>
          <span>${isTransfer ? 'Chuẩn bị chuyển đơn sang chờ khách thanh toán online' : 'Chuẩn bị chốt đơn tiền mặt ngay sau khi xác nhận'}</span>
        </div>
      `;
    }
  };

  const renderImagePreview = () => {
    const files = Array.from(imageInput?.files || []);
    imageUploadPreview.innerHTML = files.slice(0, 5).map((file) => `
      <div class="dispatch-preview-card">
        <img src="${URL.createObjectURL(file)}" alt="${escapeHtml(file.name)}">
      </div>
    `).join('');
  };

  const renderVideoPreview = () => {
    const file = videoInput?.files?.[0];

    if (!file) {
      videoUploadPreview.innerHTML = '';
      return;
    }

    videoUploadPreview.innerHTML = `
      <div class="dispatch-video-preview">
        <span class="material-symbols-outlined">movie</span>
        <div>
          <div>${escapeHtml(file.name)}</div>
          <small>${(file.size / 1024 / 1024).toFixed(1)} MB</small>
        </div>
      </div>
    `;
  };

  const open = (id) => {
    const booking = getAllBookings().find((item) => item.id === id);

    if (!booking) {
      showToast('Không tìm thấy đơn cần hoàn thành.', 'error');
      return;
    }

    if (!hasUpdatedPricing(booking)) {
      showToast('Bạn phải cập nhật giá trước khi báo hoàn thành.', 'error');
      openCostModal(id);
      return;
    }

    bookingIdInput.value = booking.id;
    customerName.textContent = getCustomerName(booking);
    serviceName.textContent = getBookingServiceNames(booking);
    bookingTotal.textContent = formatMoney(getBookingTotal(booking));
    pricingAlert.style.display = 'none';
    form.reset();
    const initialPaymentMethod = getBookingPaymentMethod(booking);
    paymentMethodInputs.forEach((input) => {
      input.checked = input.value === initialPaymentMethod;
    });
    syncPaymentMethodUi(initialPaymentMethod);
    imageUploadPreview.innerHTML = '';
    videoUploadPreview.innerHTML = '';
    modalInstance?.show();
  };

  const submit = async (event) => {
    event.preventDefault();

    const bookingId = bookingIdInput.value;
    const paymentMethod = getSelectedPaymentMethod();
    const originalButtonHtml = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = paymentMethod === 'transfer'
      ? '<span class="material-symbols-outlined">progress_activity</span>Đang gửi yêu cầu'
      : '<span class="material-symbols-outlined">progress_activity</span>Đang xác nhận hoàn thành';

    try {
      const formData = new FormData();
      formData.append('_method', 'POST');
      formData.append('phuong_thuc_thanh_toan', paymentMethod);

      Array.from(imageInput.files || []).forEach((file) => {
        formData.append('hinh_anh_ket_qua[]', file);
      });

      const videoFile = videoInput.files?.[0];
      if (videoFile) {
        formData.append('video_ket_qua', videoFile);
      }

      const token = localStorage.getItem('access_token');
      const response = await fetch(`${baseUrl}/api/bookings/${bookingId}/request-payment`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
        body: formData,
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Không thể gửi yêu cầu thanh toán.');
      }

      showToast(data.message || 'Đã cập nhật trạng thái hoàn thành cho đơn hàng.');
      modalInstance?.hide();
      form.reset();
      imageUploadPreview.innerHTML = '';
      videoUploadPreview.innerHTML = '';
      await afterSubmit({ bookingId, paymentMethod });
    } catch (error) {
      showToast(error.message || 'Lỗi kết nối khi báo hoàn thành.', 'error');
    } finally {
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonHtml;
    }
  };

  const init = () => {
    if (initialized) {
      return;
    }

    initialized = true;
    imageInput?.addEventListener('change', renderImagePreview);
    videoInput?.addEventListener('change', renderVideoPreview);
    paymentMethodInputs.forEach((input) => {
      input.addEventListener('change', () => syncPaymentMethodUi(input.value));
    });
    form?.addEventListener('submit', submit);
  };

  return {
    init,
    open,
    syncPaymentMethodUi,
  };
}
