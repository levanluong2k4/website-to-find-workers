import { callApi, redirectAuthenticatedUser, showToast } from '/assets/js/api.js';

const config = window.authLoginConfig || {};
const baseUrl = config.baseUrl || window.location.origin;
const registerUrl = config.registerUrl || `${baseUrl}/register`;
const forgotPasswordUrl = config.forgotPasswordUrl || `${baseUrl}/forgot-password`;
const googleRedirectUrl = config.googleRedirectUrl || `${baseUrl}/auth/google/redirect`;
const flashError = config.flashError || '';
const initialRole = ['customer', 'worker'].includes(config.initialRole) ? config.initialRole : 'customer';

const carouselEl = document.getElementById('authCarousel');
const carouselDotsEl = document.getElementById('authCarouselDots');
const showcaseEyebrowEl = document.getElementById('showcaseEyebrow');
const showcaseTitleEl = document.getElementById('showcaseTitle');
const showcaseDescriptionEl = document.getElementById('showcaseDescription');
const showcaseMetricsEl = document.getElementById('showcaseMetrics');
const authTitleEl = document.getElementById('authTitle');
const authDescriptionEl = document.getElementById('authDescription');
const authHintEl = document.getElementById('authHint');
const registerLinkEl = document.getElementById('registerLink');
const googleLoginButton = document.getElementById('googleLoginButton');
const googleAuthEnabled = googleLoginButton?.dataset.googleEnabled === '1';
const googleAuthError = googleLoginButton?.dataset.googleError || '';
const roleButtons = Array.from(document.querySelectorAll('[data-role-option]'));
const submitButton = document.getElementById('btnSubmit');
const loginForm = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('matKhau');
const togglePasswordButton = document.getElementById('togglePasswordButton');
const eyeIcon = document.getElementById('eyeIcon');
const forgotPasswordTrigger = document.getElementById('forgotPasswordTrigger');

const roleContent = {
  customer: {
    accent: '#0ea5e9',
    accentStrong: '#0369a1',
    accentSoft: 'rgba(240, 249, 255, 0.96)',
    accentBorder: 'rgba(14, 165, 233, 0.18)',
    eyebrow: 'Khách hàng đặt dịch vụ',
    title: 'Sửa chữa nhanh, linh kiện rõ nguồn gốc và theo dõi minh bạch.',
    description: 'Đăng nhập để quay lại đúng luồng đang cần, từ lịch hẹn mới cho đến các đơn đang xử lý và đánh giá sau sửa chữa.',
    authTitle: 'Đăng nhập để tiếp tục theo dõi đơn sửa chữa.',
    authDescription: 'Chọn vai trò khách hàng để xem carousel về dịch vụ, linh kiện và độ uy tín của cửa hàng ngay tại màn hình đăng nhập.',
    authHint: 'Nếu đây là lần đầu đăng nhập bằng Google, hệ thống sẽ tạo tài khoản đúng theo vai trò khách hàng bạn đang chọn.',
    metrics: [
      { value: 'Linh kiện', text: 'Có báo giá trước khi thay và luôn ghi rõ nguồn gốc cho từng đơn.' },
      { value: 'Uy tín', text: 'Theo dõi đánh giá, lịch sử xử lý và kết quả sửa chữa ngay trong tài khoản.' },
      { value: 'Tận nơi', text: 'Đặt lịch nhanh cho các dịch vụ điện lạnh, gia dụng và thiết bị điện tử.' },
    ],
    slides: [
      {
        image: '/assets/images/carousel/tulanh.jpg',
        badge: 'Dịch vụ tại nhà',
        title: 'Đặt lịch sửa điện lạnh và gia dụng chỉ trong vài bước.',
        description: 'Từ tủ lạnh, lò vi sóng đến máy giặt, mọi yêu cầu đều có thể tạo nhanh và theo dõi lại ngay sau khi đăng nhập.',
        chips: ['Có thợ xác nhận', 'Theo dõi tiến độ', 'Lịch hẹn rõ giờ'],
      },
      {
        image: '/assets/images/carousel/suamaygiat.jpg',
        badge: 'Linh kiện minh bạch',
        title: 'Biết trước chi phí, xem rõ linh kiện và quyết định dễ hơn.',
        description: 'Mỗi đơn sửa đều có lịch sử báo giá, phí linh kiện và ghi chú xử lý để khách chủ động kiểm tra trước khi đồng ý.',
        chips: ['Báo giá trước', 'Có ảnh kết quả', 'Bảo hành rõ ràng'],
      },
      {
        image: '/assets/images/banner.png',
        badge: 'Uy tín cửa hàng',
        title: 'Tất cả phản hồi, đánh giá và thông tin cửa hàng được gom trong một nơi.',
        description: 'Đăng nhập để xem lại các đơn cũ, lịch sử hỗ trợ và mức độ hài lòng trước khi tiếp tục đặt dịch vụ mới.',
        chips: ['Xem đánh giá', 'Quản lý đơn cũ', 'Thanh toán tiện hơn'],
      },
    ],
  },
  worker: {
    accent: '#22c55e',
    accentStrong: '#15803d',
    accentSoft: 'rgba(240, 253, 244, 0.96)',
    accentBorder: 'rgba(34, 197, 94, 0.2)',
    eyebrow: 'Thợ đăng nhập làm việc',
    title: 'Carousel tuyển dụng, ưu đãi và chính sách tốt dành riêng cho thợ.',
    description: 'Khi chọn vai trò thợ, phần trái chuyển sang nội dung tuyển dụng, thu nhập, phúc lợi và các chính sách hỗ trợ từ cửa hàng.',
    authTitle: 'Đăng nhập để nhận việc và theo dõi lịch làm việc.',
    authDescription: 'Chọn vai trò thợ để mở đúng luồng làm việc và xem những nội dung tuyển dụng, ưu đãi, chính sách ở carousel bên trái.',
    authHint: 'Nếu đây là lần đầu đăng nhập bằng Google, hệ thống sẽ tạo tài khoản đúng theo vai trò thợ bạn đang chọn.',
    metrics: [
      { value: 'Tuyển dụng', text: 'Mở rộng đội ngũ kỹ thuật viên với luồng nhận việc rõ ràng, minh bạch.' },
      { value: 'Ưu đãi', text: 'Nhiều chính sách hỗ trợ đơn mới, thưởng hiệu suất và nhắc việc thông minh.' },
      { value: 'Phúc lợi', text: 'Theo dõi lịch, đánh giá và doanh thu từ cùng một tài khoản đăng nhập.' },
    ],
    slides: [
      {
        image: '/assets/images/carousel/nhanvien.jpg',
        badge: 'Tuyển dụng kỹ thuật viên',
        title: 'Gia nhập đội ngũ thợ chuyên nghiệp với quy trình nhận việc rõ ràng.',
        description: 'Đăng nhập đúng vai trò để quay lại bảng việc, xem các ca mới và theo dõi toàn bộ đơn được giao trong ngày.',
        chips: ['Nhận việc nhanh', 'Bảng việc tập trung', 'Xác thực an toàn'],
      },
      {
        image: '/assets/images/carousel/sile_tuyendung1.png',
        badge: 'Ưu đãi cho thợ',
        title: 'Ưu đãi thu nhập và hỗ trợ để bạn tập trung vào chuyên môn.',
        description: 'Carousel hiển thị các thông điệp về thưởng, hỗ trợ vận hành và các lợi ích khi đồng hành cùng cửa hàng.',
        chips: ['Hỗ trợ đơn mới', 'Thưởng hiệu suất', 'Giảm thao tác thừa'],
      },
      {
        image: '/assets/images/carousel/tuyendung3.jpg',
        badge: 'Chính sách tốt',
        title: 'Chính sách rõ ràng giúp thợ tự tin nhận đơn và cập nhật trạng thái.',
        description: 'Từ lịch làm việc đến đánh giá của khách, mọi thứ đều quay lại đúng vai trò ngay sau khi đăng nhập.',
        chips: ['Theo dõi đánh giá', 'Quản lý doanh thu', 'Lịch làm việc liền mạch'],
      },
    ],
  },
};

let selectedRole = initialRole;
let currentSlideIndex = 0;
let carouselTimer = null;

const escapeHtml = (value) => String(value ?? '')
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;')
  .replaceAll("'", '&#39;');

const updateQueryRole = (role) => {
  const nextUrl = new URL(window.location.href);
  nextUrl.searchParams.set('role', role);
  window.history.replaceState({}, '', nextUrl.toString());
};

const setSubmitLoading = (isLoading) => {
  submitButton.disabled = isLoading;
  submitButton.innerHTML = isLoading
    ? '<span class="auth-spinner"></span> Đang xử lý đăng nhập...'
    : 'Tiếp tục đăng nhập <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>';
};

const renderMetrics = (metrics) => {
  showcaseMetricsEl.innerHTML = metrics.map((metric) => `
    <article class="metric-pill">
      <strong>${escapeHtml(metric.value)}</strong>
      <span>${escapeHtml(metric.text)}</span>
    </article>
  `).join('');
};

const renderCarousel = () => {
  const roleMeta = roleContent[selectedRole];
  const slides = roleMeta.slides;

  carouselEl.innerHTML = slides.map((slide, index) => `
    <article class="carousel-slide ${index === currentSlideIndex ? 'is-active' : ''}" data-carousel-slide="${index}">
      <div class="carousel-slide__image">
        <img src="${escapeHtml(slide.image)}" alt="${escapeHtml(slide.title)}">
      </div>
      <div class="carousel-slide__overlay"></div>
      <div class="carousel-slide__content">
        <span class="carousel-slide__badge">
          <span class="material-symbols-outlined" style="font-size:.92rem;">auto_awesome</span>
          ${escapeHtml(slide.badge)}
        </span>
        <h2>${escapeHtml(slide.title)}</h2>
        <p>${escapeHtml(slide.description)}</p>
        <div class="carousel-slide__chips">
          ${slide.chips.map((chip) => `<span>${escapeHtml(chip)}</span>`).join('')}
        </div>
      </div>
    </article>
  `).join('');

  carouselDotsEl.innerHTML = slides.map((slide, index) => `
    <button type="button" class="${index === currentSlideIndex ? 'is-active' : ''}" data-carousel-dot="${index}" aria-label="Xem slide ${index + 1}"></button>
  `).join('');

  carouselDotsEl.querySelectorAll('[data-carousel-dot]').forEach((button) => {
    button.addEventListener('click', () => {
      currentSlideIndex = Number(button.dataset.carouselDot || 0);
      renderCarousel();
      startCarousel();
    });
  });
};

const startCarousel = () => {
  window.clearInterval(carouselTimer);
  const slides = roleContent[selectedRole].slides;
  carouselTimer = window.setInterval(() => {
    currentSlideIndex = (currentSlideIndex + 1) % slides.length;
    renderCarousel();
  }, 4800);
};

const applyRole = (role, updateHistory = true) => {
  selectedRole = roleContent[role] ? role : 'customer';
  const roleMeta = roleContent[selectedRole];

  document.documentElement.style.setProperty('--auth-accent', roleMeta.accent);
  document.documentElement.style.setProperty('--auth-accent-strong', roleMeta.accentStrong);
  document.documentElement.style.setProperty('--auth-accent-soft', roleMeta.accentSoft);
  document.documentElement.style.setProperty('--auth-accent-border', roleMeta.accentBorder);

  roleButtons.forEach((button) => {
    const isActive = button.dataset.roleOption === selectedRole;
    button.classList.toggle('is-active', isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });

  showcaseEyebrowEl.textContent = roleMeta.eyebrow;
  showcaseTitleEl.textContent = roleMeta.title;
  showcaseDescriptionEl.textContent = roleMeta.description;
  authTitleEl.textContent = roleMeta.authTitle;
  authDescriptionEl.textContent = roleMeta.authDescription;
  authHintEl.textContent = roleMeta.authHint;
  renderMetrics(roleMeta.metrics);

  currentSlideIndex = 0;
  renderCarousel();
  startCarousel();

  registerLinkEl.href = `${registerUrl}?role=${encodeURIComponent(selectedRole)}`;
  if (googleAuthEnabled) {
    googleLoginButton.href = `${googleRedirectUrl}?role=${encodeURIComponent(selectedRole)}`;
  }

  if (updateHistory) {
    updateQueryRole(selectedRole);
  }
};

roleButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const nextRole = button.dataset.roleOption;
    if (!nextRole || nextRole === selectedRole) return;
    applyRole(nextRole);
  });
});

togglePasswordButton.addEventListener('click', () => {
  const isPassword = passwordInput.type === 'password';
  passwordInput.type = isPassword ? 'text' : 'password';
  eyeIcon.textContent = isPassword ? 'visibility' : 'visibility_off';
});

forgotPasswordTrigger.addEventListener('click', () => {
  const nextUrl = new URL(forgotPasswordUrl, window.location.origin);
  const email = emailInput.value.trim();

  if (selectedRole) {
    nextUrl.searchParams.set('role', selectedRole);
  }

  if (email) {
    nextUrl.searchParams.set('email', email);
  }

  window.location.href = nextUrl.toString();
});

if (!googleAuthEnabled) {
  googleLoginButton.addEventListener('click', (event) => {
    event.preventDefault();
    showToast(googleAuthError || 'Đăng nhập Google chưa được cấu hình.', 'error');
  });
}

if (flashError) {
  showToast(flashError, 'error');
}

redirectAuthenticatedUser();
applyRole(selectedRole, false);

loginForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const email = emailInput.value.trim();
  const password = passwordInput.value;

  setSubmitLoading(true);

  try {
    const response = await callApi('/login', 'POST', { email, password, role: selectedRole });

    if (response.ok) {
      if (response.data?.debug_otp) {
        sessionStorage.setItem('debug_otp', response.data.debug_otp);
      }

      showToast('Đã gửi mã OTP thành công!');
      window.setTimeout(() => {
        window.location.href = `${baseUrl}/otp?email=${encodeURIComponent(email)}&role=${encodeURIComponent(selectedRole)}`;
      }, 900);
      return;
    }

    showToast(response.data?.message || 'Email hoặc mật khẩu không đúng!', 'error');
  } catch (error) {
    console.error('Login error:', error);
    showToast(error.message || 'Lỗi kết nối máy chủ', 'error');
  }

  setSubmitLoading(false);
});