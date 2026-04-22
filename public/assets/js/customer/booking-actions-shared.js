export const getBookingServices = (booking) => Array.isArray(booking?.dich_vus) ? booking.dich_vus : [];

export const getBookingPaymentMethod = (booking) => booking?.phuong_thuc_thanh_toan === 'transfer' ? 'transfer' : 'cod';

export const isCashPaymentBooking = (booking) => getBookingPaymentMethod(booking) === 'cod';

export const getBookingRebookPayload = (booking) => {
  const services = getBookingServices(booking);
  const serviceIds = services
    .map((service) => Number(service?.id || 0))
    .filter((serviceId) => Number.isInteger(serviceId) && serviceId > 0);
  const firstServiceName = services[0]?.ten_dich_vu || '';
  const workerId = Number(booking?.tho?.id || booking?.tho_id || 0);

  return {
    workerId: workerId > 0 ? workerId : null,
    serviceIds,
    serviceName: firstServiceName,
  };
};

export const openRebookBooking = (
  booking,
  {
    bookingWizard = window.BookingWizardModal,
    targetPath = '/customer/booking',
  } = {},
) => {
  const payload = getBookingRebookPayload(booking);

  if (bookingWizard?.open) {
    bookingWizard.open(payload);
    return;
  }

  const targetUrl = new URL(targetPath, window.location.origin);

  if (payload.workerId) {
    targetUrl.searchParams.set('worker_id', String(payload.workerId));
  }

  if (payload.serviceIds.length) {
    targetUrl.searchParams.set('service_ids', payload.serviceIds.join(','));
  } else if (payload.serviceName) {
    targetUrl.searchParams.set('service_name', payload.serviceName);
  }

  window.location.href = targetUrl.toString();
};

export const buildOnlineGatewayOptions = ({ isLocalPaymentSandbox = false } = {}) => ({
  momo: 'Ví MoMo',
  zalopay: 'Ví ZaloPay',
  vnpay: 'VNPAY / Thẻ ngân hàng',
  ...(isLocalPaymentSandbox ? { test: 'Thanh toán test nội bộ' } : {}),
});

export const showCashPaymentInstructions = async ({ swal }) => {
  await swal.fire({
    title: 'Thanh toán tiền mặt',
    html: `
      <div style="text-align:left; line-height:1.65;">
        <p style="margin-bottom:0.75rem;">Thợ đã chốt đơn này với phương thức <strong>tiền mặt</strong>.</p>
        <p style="margin-bottom:0.75rem;">Bạn thanh toán trực tiếp cho thợ sau khi kiểm tra kết quả sửa chữa.</p>
        <p style="margin:0;">Nếu đơn chưa hoàn tất ngay, vui lòng liên hệ thợ hoặc cửa hàng để được hỗ trợ đối soát.</p>
      </div>
    `,
    icon: 'info',
    confirmButtonText: 'Đã hiểu',
  });
};

export const selectOnlineGateway = async ({
  isLocalPaymentSandbox = false,
  swal,
}) => {
  const gatewayOptions = buildOnlineGatewayOptions({ isLocalPaymentSandbox });
  const gatewayKeys = Object.keys(gatewayOptions);
  const result = await swal.fire({
    title: 'Chọn ví điện tử',
    input: 'radio',
    inputOptions: gatewayOptions,
    inputValue: gatewayKeys[0] || 'momo',
    inputValidator: (value) => (!value ? 'Vui lòng chọn ví hoặc cổng thanh toán.' : undefined),
    showCancelButton: true,
    confirmButtonText: 'Mở thanh toán',
    cancelButtonText: 'Quay lại',
  });

  return result.isConfirmed ? result.value : null;
};
