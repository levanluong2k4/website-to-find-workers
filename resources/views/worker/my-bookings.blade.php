@extends('layouts.app')
@section('title', 'Lịch làm việc - Thợ Tốt NTU')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&family=Public+Sans:wght@700;800;900&family=Material+Symbols+Outlined" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('assets/css/worker/my-bookings.css') }}">
@endpush

@section('content')
<div
  id="workerMyBookingsPage"
  class="dispatch-page"
  data-base-url="{{ url('/') }}"
  data-route-worker-marker-image="{{ asset('assets/images/shipper.png') }}">
  <x-worker-sidebar />

  <main class="worker-main">
    <div class="dispatch-shell">
      <div class="dispatch-board">
        <section class="dispatch-board-topbar" aria-label="Điều hướng lịch làm việc">
          <div class="dispatch-board-topbar__inner">
            <div class="dispatch-board-topbar__nav">
              <div class="dispatch-board-topbar__title">Lịch làm việc</div>
              <div class="dispatch-board-topbar__tabs" role="tablist">
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="pending">Nhận việc</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="upcoming">Sắp tới</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="inprogress">Đang sửa</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="payment">Chưa thanh toán</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="done">Hoàn thành</button>
                <button type="button" class="dispatch-board-topbar__tab" data-board-status="cancelled">Đã hủy</button>
              </div>
            </div>

            <div class="dispatch-board-topbar__actions">
              <div class="dispatch-board-topbar__notification">
                <button
                  type="button"
                  class="dispatch-board-topbar__icon-btn"
                  id="dispatchTopNotificationButton"
                  aria-label="Thông báo"
                  aria-expanded="false">
                  <span class="material-symbols-outlined">notifications</span>
                  <span class="dispatch-board-topbar__notification-badge is-hidden" id="dispatchTopNotificationBadge">0</span>
                </button>

                <div class="dispatch-board-topbar__notification-menu" id="dispatchTopNotificationMenu">
                  <div class="dispatch-board-topbar__notification-head">
                    <div>
                      <h3>Thông báo</h3>
                      <p>Cập nhật mới nhất từ các đơn bạn đang xử lý.</p>
                    </div>
                    <button type="button" class="dispatch-board-topbar__notification-mark" id="dispatchTopNotificationMarkAll">Đã đọc hết</button>
                  </div>

                  <div class="dispatch-board-topbar__notification-list" id="dispatchTopNotificationList">
                    <div class="dispatch-board-topbar__notification-empty">
                      <span class="material-symbols-outlined">notifications_off</span>
                      <p>Chưa có thông báo nào.</p>
                    </div>
                  </div>

                  <div class="dispatch-board-topbar__notification-foot">
                    <a href="/worker/my-bookings">Xem lịch làm việc</a>
                  </div>
                </div>
              </div>

              <a href="/worker/profile" class="dispatch-board-topbar__avatar" id="dispatchTopAvatar" aria-label="Mở hồ sơ">
                TT
              </a>
            </div>
          </div>
        </section>

        <section class="dispatch-board-layout">
          <div class="dispatch-board-main">
            <section class="dispatch-board-intro" aria-label="Tóm tắt màn lịch làm việc">
              <div class="dispatch-board-intro__eyebrow" id="dispatchBoardIntroEyebrow">Lịch làm việc</div>

              <div class="dispatch-board-intro__body">
                <div class="dispatch-board-intro__copy">
                  <h1 class="dispatch-board-intro__title" id="dispatchBoardIntroTitle">Đang tải lịch làm việc</h1>
                  <p class="dispatch-board-intro__subtitle" id="dispatchBoardIntroSubtitle">
                    Hệ thống đang chuẩn bị danh sách lịch sửa chữa phù hợp với trạng thái bạn đang xem.
                  </p>
                </div>

                <div class="dispatch-board-intro__meta">
                  <span class="dispatch-board-intro__chip dispatch-board-intro__chip--status" id="dispatchBoardStatusChip">Đang đồng bộ</span>
                  <span class="dispatch-board-intro__chip" id="dispatchBoardScopeChip">Phạm vi: tất cả</span>
                </div>
              </div>
            </section>

            <section class="dispatch-board__controls">
              <div class="dispatch-scope-toggle" role="tablist" aria-label="Lọc theo ngày">
                <button type="button" class="dispatch-scope-toggle__btn is-active" data-booking-scope="all">Tất cả</button>
                <button type="button" class="dispatch-scope-toggle__btn" data-booking-scope="today">Hôm nay</button>
              </div>

              <div class="dispatch-board__controls-meta" id="dispatchBoardControlsMeta">Đang tải lịch làm việc</div>
            </section>

            <section id="bookingsContainer" class="dispatch-board-grid">
              <div class="dispatch-board-empty">
                <div>
                  <span class="material-symbols-outlined">hourglass_top</span>
                  <h3>Đang tải lịch làm việc</h3>
                  <p>Hệ thống đang lấy danh sách đơn sửa chữa của bạn.</p>
                </div>
              </div>
            </section>

            <div class="dispatch-board__pagination-wrap" id="bookingPaginationWrap" hidden>
              <div class="dispatch-pagination" id="bookingPagination" aria-label="Phân trang lịch làm việc"></div>
            </div>
          </div>

          <aside class="dispatch-board-side">
            <section class="dispatch-board-route" id="routePreviewSection" hidden>
              <div class="dispatch-board-route__frame">
                <span class="dispatch-board-route__label">Bản đồ tiếp theo</span>
                <span class="dispatch-board-route__badge" id="routePreviewBadge">Sửa tại nhà</span>
              </div>

              <div class="dispatch-board-route__overlay">
                <div class="dispatch-board-route__content">
                  <div class="dispatch-board-route__icon">
                    <span class="material-symbols-outlined">directions_car</span>
                  </div>
                  <div>
                    <div class="dispatch-board-route__eyebrow">Tuyến đang ưu tiên</div>
                    <h3 class="dispatch-board-route__title" id="routePreviewTitle">Đang tìm điểm đến phù hợp</h3>
                    <p class="dispatch-board-route__location" id="routePreviewLocation">Địa chỉ sẽ hiển thị tại đây khi có lịch phù hợp.</p>
                    <p class="dispatch-board-route__meta" id="routePreviewMeta">Tuyến đường tiếp theo sẽ hiện tại đây khi có đơn sửa tại nhà.</p>
                  </div>
                </div>

                <button type="button" class="dispatch-board-route__action" id="routePreviewAction">
                  <span class="material-symbols-outlined">map</span>
                  Xem đường đi
                </button>
              </div>
            </section>
          </aside>
        </section>
      </div>
    </div>
  </main>
</div>

<div class="modal fade dispatch-modal dispatch-modal--pricing-v2" id="modalCosts" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content dispatch-modal__content dispatch-modal__content--pricing-v2">
      <div class="dispatch-modal__header dispatch-modal__header--pricing-v2">
        <div class="dispatch-pricing-v2-header-row">
          <div class="dispatch-pricing-v2-header-brand">
            <div class="dispatch-modal__title-accent">Pricing Desk</div>
          </div>
          <div class="dispatch-pricing-v2-header-copy">
            <h2 class="dispatch-modal__title mb-0">Cập nhật bảng giá sửa chữa</h2>
            <p class="dispatch-modal__subtitle m-0">Điền rõ từng hạng mục để khách dễ kiểm tra, còn bạn dễ rà soát tổng tiền trước khi gửi yêu cầu thanh toán.</p>
          </div>
          <button type="button" class="dispatch-modal__close dispatch-modal__close--v2" data-bs-dismiss="modal" aria-label="Đóng">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
        <div class="dispatch-pricing-v2-header-meta">
          <div class="dispatch-pricing-v2-banner-chip" id="costServiceModeBadge" data-state="travel">Sửa tại nhà</div>
          <div class="dispatch-pricing-v2-banner-chip" id="costTruckBadge" data-state="muted">Không thuê xe chở</div>
          <div id="costDistanceContainer">
            <div class="dispatch-pricing-v2-banner-chip" id="costDistanceBadge" data-state="travel">Phí đi lại tự động</div>
          </div>
        </div>
        <div class="dispatch-pricing-v2-banner">
          <div class="dispatch-pricing-v2-banner-main">
            <div class="dispatch-pricing-v2-banner-item dispatch-pricing-v2-banner-item--compact">
              <span id="costBookingReference">Đơn #0000</span>
            </div>
            <div class="dispatch-pricing-v2-banner-item">
              <span class="material-symbols-outlined">person</span>
              <span id="costCustomerName">Khách hàng</span>
            </div>
            <div class="dispatch-pricing-v2-banner-item">
              <span class="material-symbols-outlined">construction</span>
              <span id="costServiceName">Dịch vụ sửa chữa</span>
            </div>
            <div class="dispatch-pricing-v2-banner-status">Đang sửa</div>
          </div>
        </div>
      </div>

      <div class="dispatch-modal__body dispatch-modal__body--pricing-v2 p-0">
        <form id="formUpdateCosts" class="dispatch-pricing-form h-100 d-flex flex-column">
          <input type="hidden" id="costBookingId">
          <input type="hidden" id="inputGhiChuLinhKien" value="">

          <div class="dispatch-pricing-v2-main flex-grow-1">
            <div class="dispatch-pricing-v2-content-grid">
              <div class="dispatch-pricing-v2-editor">
                <div class="dispatch-pricing-v2-wizard-head">
                  <div>
                    <div class="dispatch-pricing-v2-wizard-kicker" id="costWizardKicker">Bước 1 trên 2</div>
                    <h3 class="dispatch-pricing-v2-wizard-title" id="costWizardTitle">Cập nhật tiền công</h3>
                    <p class="dispatch-pricing-v2-wizard-copy" id="costWizardCopy">Chọn triệu chứng, nguyên nhân và hướng xử lý để thêm đúng các dòng tiền công cho đơn đang sửa.</p>
                  </div>
                  <div class="dispatch-pricing-v2-wizard-badge" id="costWizardStepBadge">1 / 2</div>
                </div>

                <div class="dispatch-pricing-v2-progress">
                  <div class="dispatch-pricing-v2-progress-track">
                    <div class="dispatch-pricing-v2-progress-fill" id="costWizardProgressFill"></div>
                  </div>
                  <div class="dispatch-pricing-v2-flow" id="costWizardFlow">
                    <button type="button" class="dispatch-pricing-v2-flow-step is-active" data-cost-step-trigger="1">
                      <span class="dispatch-pricing-v2-flow-step__index">1</span>
                      <span class="dispatch-pricing-v2-flow-step__label">Tiền công</span>
                    </button>
                    <div class="dispatch-pricing-v2-flow-divider"></div>
                    <button type="button" class="dispatch-pricing-v2-flow-step" data-cost-step-trigger="2">
                      <span class="dispatch-pricing-v2-flow-step__index">2</span>
                      <span class="dispatch-pricing-v2-flow-step__label">Phụ phí</span>
                    </button>
                  </div>
                </div>

                <div class="dispatch-pricing-v2-step-panel is-active" data-cost-step-panel="1">
                  <section class="dispatch-pricing-v2-section dispatch-pricing-v2-section--labor">
                    <div class="dispatch-pricing-v2-section-head">
                      <div class="dispatch-pricing-v2-section-copy">
                        <div class="dispatch-pricing-v2-section-ordinal">
                          <span class="dispatch-pricing-v2-section-number">01</span>
                          <div class="dispatch-pricing-v2-section-heading-group">
                            <div class="dispatch-pricing-v2-section-kicker">Tiền công</div>
                            <span class="dispatch-pricing-v2-section-count" id="laborCountBadge">0 dòng</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="dispatch-pricing-v2-labor-catalog">
                      <div class="dispatch-pricing-v2-cascade-grid">
                        <label class="dispatch-pricing-v2-picker-field">
                          <span class="dispatch-pricing-v2-field-label">Triệu chứng</span>
                          <div class="dispatch-search-picker" id="laborSymptomPicker">
                            <button type="button" class="dispatch-search-picker__trigger" id="laborSymptomTrigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="laborSymptomPanel">
                              <span class="dispatch-search-picker__label" id="laborSymptomTriggerLabel">Chọn triệu chứng</span>
                              <span class="material-symbols-outlined dispatch-search-picker__icon">expand_more</span>
                            </button>
                            <div class="dispatch-search-picker__panel" id="laborSymptomPanel" hidden>
                              <div class="dispatch-search-picker__searchbox">
                                <span class="material-symbols-outlined">search</span>
                                <input type="search" class="dispatch-search-picker__search" id="laborSymptomSearch" placeholder="Tìm triệu chứng">
                              </div>
                              <div class="dispatch-search-picker__options" id="laborSymptomOptions" role="listbox" aria-label="Danh sách triệu chứng"></div>
                            </div>
                          </div>
                          <select class="dispatch-pricing-v2-select dispatch-pricing-v2-select--picker dispatch-pricing-v2-select--native" id="laborSymptomSelect">
                            <option value="">Chọn triệu chứng</option>
                          </select>
                        </label>
                        <label class="dispatch-pricing-v2-picker-field">
                          <span class="dispatch-pricing-v2-field-label">Nguyên nhân</span>
                          <div class="dispatch-search-picker" id="laborCausePicker">
                            <button type="button" class="dispatch-search-picker__trigger" id="laborCauseTrigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="laborCausePanel" disabled>
                              <span class="dispatch-search-picker__label" id="laborCauseTriggerLabel">Chọn nguyên nhân</span>
                              <span class="material-symbols-outlined dispatch-search-picker__icon">expand_more</span>
                            </button>
                            <div class="dispatch-search-picker__panel" id="laborCausePanel" hidden>
                              <div class="dispatch-search-picker__searchbox">
                                <span class="material-symbols-outlined">search</span>
                                <input type="search" class="dispatch-search-picker__search" id="laborCauseSearch" placeholder="Tìm nguyên nhân">
                              </div>
                              <div class="dispatch-search-picker__options" id="laborCauseOptions" role="listbox" aria-label="Danh sách nguyên nhân"></div>
                            </div>
                          </div>
                          <select class="dispatch-pricing-v2-select dispatch-pricing-v2-select--picker dispatch-pricing-v2-select--native" id="laborCauseSelect" disabled>
                            <option value="">Chọn nguyên nhân</option>
                          </select>
                        </label>
                        <label class="dispatch-pricing-v2-picker-field">
                          <span class="dispatch-pricing-v2-field-label">Hướng xử lý</span>
                          <select class="dispatch-pricing-v2-select dispatch-pricing-v2-select--picker" id="laborResolutionSelect" disabled>
                            <option value="">Chọn hướng xử lý</option>
                          </select>
                        </label>
                      </div>
                      <div class="dispatch-pricing-v2-labor-catalog-footer">
                        <div class="dispatch-pricing-v2-labor-note">
                          <div class="dispatch-pricing-v2-labor-note__title" id="laborCatalogStatus">Chọn triệu chứng để hệ thống lọc tiền công đúng với đơn này.</div>
                          <div class="dispatch-pricing-v2-labor-note__meta" id="laborResolutionPrice">Giá tham khảo và mô tả xử lý sẽ hiện ở đây.</div>
                        </div>
                        <button type="button" class="dispatch-pricing-v2-inline-add dispatch-pricing-v2-inline-add--primary" id="addLaborItem" disabled>
                          <span class="material-symbols-outlined">playlist_add</span>
                          Thêm tiền công
                        </button>
                      </div>
                    </div>
                    <div class="dispatch-pricing-v2-labor-list" id="laborItemsContainer"></div>
                  </section>
                </div>

                <div class="dispatch-pricing-v2-step-panel" data-cost-step-panel="2" hidden>
                  <section class="dispatch-pricing-v2-section dispatch-pricing-v2-section--handoff">
                    <div class="dispatch-pricing-v2-section-head">
                      <div class="dispatch-pricing-v2-section-copy">
                        <div class="dispatch-pricing-v2-section-ordinal">
                          <span class="dispatch-pricing-v2-section-number">02</span>
                          <div class="dispatch-pricing-v2-section-heading-group">
                            <div class="dispatch-pricing-v2-section-kicker">Linh kiện do admin xử lý</div>
                            <span class="dispatch-pricing-v2-section-count" id="partCountBadge">0 dòng</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="dispatch-pricing-v2-handoff-card">
                      <div class="dispatch-pricing-v2-handoff-card__icon">
                        <span class="material-symbols-outlined">inventory_2</span>
                      </div>
                      <div class="dispatch-pricing-v2-handoff-card__copy">
                        <h3>Thợ không cập nhật linh kiện ở màn hình này</h3>
                        <p>Admin sẽ thêm hoặc chỉnh sửa linh kiện tại trang chi tiết đơn trong khu vực quản trị. Phần tổng linh kiện ở cột bên phải vẫn hiển thị theo dữ liệu hiện có của đơn.</p>
                      </div>
                    </div>
                  </section>

                  <section class="dispatch-pricing-v2-section dispatch-pricing-v2-section--fees">
                    <div class="dispatch-pricing-v2-section-copy dispatch-pricing-v2-section-copy--fees">
                      <div class="dispatch-pricing-v2-section-ordinal">
                        <span class="dispatch-pricing-v2-section-number">03</span>
                        <div class="dispatch-pricing-v2-section-heading-group">
                          <div class="dispatch-pricing-v2-section-kicker">Phí phụ thêm</div>
                        </div>
                      </div>
                    </div>
                    <div class="dispatch-pricing-v2-fees-section">
                      <div class="dispatch-pricing-v2-fee-card" id="truckFeeContainer" style="display:none;">
                        <h3 class="dispatch-pricing-v2-fee-title">Phí thuê xe chở</h3>
                        <div class="dispatch-pricing-v2-fee-input-wrap">
                          <input type="number" class="dispatch-pricing-v2-fee-input" id="inputTienThueXe" min="0" value="0" placeholder="Nhập số tiền...">
                          <span class="dispatch-pricing-v2-fee-prefix">đ</span>
                        </div>
                      </div>
                      <div class="dispatch-pricing-v2-fee-card dispatch-pricing-v2-fee-card--readonly">
                        <h3 class="dispatch-pricing-v2-fee-title">Phí đi lại cố định</h3>
                        <div class="dispatch-pricing-v2-fee-readonly">
                          <strong class="dispatch-pricing-v2-fee-value" id="displayPhiDiLai">0 đ</strong>
                          <span class="dispatch-pricing-v2-fee-chip">Tự tính</span>
                        </div>
                        <p class="dispatch-pricing-v2-fee-hint m-0" id="costDistanceHint">Hệ thống tính tự động theo quãng đường phục vụ.</p>
                      </div>
                    </div>
                  </section>
                </div>
              </div>

              <aside class="dispatch-pricing-v2-summary">
                <div class="dispatch-pricing-v2-summary-card">
                  <div class="dispatch-pricing-v2-summary-eyebrow">Bản xem trước gửi khách</div>
                  <div class="dispatch-pricing-v2-summary-header">
                    <h3 class="dispatch-pricing-v2-summary-title">Tóm tắt chi phí</h3>
                    <span class="dispatch-pricing-v2-summary-status" id="costDraftState">Sẵn sàng lưu</span>
                  </div>

                  <div class="dispatch-pricing-v2-summary-list">
                    <div class="dispatch-pricing-v2-summary-row">
                      <span>Tổng tiền công</span>
                      <strong id="laborSubtotal">0 đ</strong>
                    </div>
                    <div class="dispatch-pricing-v2-summary-row">
                      <span>Linh kiện & Vật tư</span>
                      <strong id="partsSubtotal">0 đ</strong>
                    </div>
                    <div class="dispatch-pricing-v2-summary-row" id="travelSummaryRow">
                      <span>Phí đi lại (Cố định)</span>
                      <strong id="travelSubtotal">0 đ</strong>
                    </div>
                    <div class="dispatch-pricing-v2-summary-row" id="truckSummaryRow" style="display:none;">
                      <span>Phí thuê xe</span>
                      <strong id="truckSubtotal">0 đ</strong>
                    </div>
                  </div>

                  <div class="dispatch-pricing-v2-total-card">
                    <div class="dispatch-pricing-v2-total-card__label">
                      <span class="material-symbols-outlined">receipt_long</span>
                      <span>Tổng cộng tất cả chi phí</span>
                    </div>
                    <div class="dispatch-pricing-v2-total-card__value" id="costEstimateTotal">0 đ</div>
                    <div class="dispatch-pricing-v2-total-card__hint" id="costSummaryHint">Bao gồm công, linh kiện và các phụ phí của đơn này.</div>
                  </div>
                </div>
              </aside>
            </div>
          </div>
          <div class="dispatch-pricing-v2-footer">
            <div class="dispatch-pricing-v2-footer-actions">
              <button type="button" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--ghost" data-bs-dismiss="modal">Hủy</button>
              <button type="button" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--ghost d-none" id="btnCostWizardPrev">
                <span class="material-symbols-outlined">arrow_back</span>
                Quay lại
              </button>
              <button type="button" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--primary" id="btnCostWizardNext">
                <span class="material-symbols-outlined">arrow_forward</span>
                Tiếp tục
              </button>
              <button type="submit" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--primary d-none" id="btnSubmitCostUpdate">
                <span class="material-symbols-outlined">save</span>
                Lưu chi phí
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade dispatch-modal" id="modalViewDetails" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content dispatch-modal__content">
      <div class="dispatch-modal__header d-flex justify-content-between align-items-start gap-3">
        <div>
          <div class="dispatch-modal__eyebrow">Booking Intelligence</div>
          <h2 class="dispatch-modal__title">Chi tiết đơn sửa chữa</h2>
          <p class="dispatch-modal__subtitle">Tổng hợp đầy đủ thông tin khách hàng, yêu cầu sửa chữa, hình ảnh ban đầu và breakdown chi phí của đơn.</p>
        </div>
        <button type="button" class="dispatch-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="dispatch-modal__body">
        <div id="bookingDetailContent">
          <div class="dispatch-empty">
            <span class="material-symbols-outlined">hourglass_top</span>
            <h3>Đang tải chi tiết đơn</h3>
            <p>Vui lòng chờ trong giây lát.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade dispatch-modal" id="modalRouteGuide" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content dispatch-modal__content">
      <div class="dispatch-modal__header d-flex justify-content-between align-items-start gap-3">
        <div>
          <div class="dispatch-modal__eyebrow">Navigation Assist</div>
          <h2 class="dispatch-modal__title">Đường đi tới khách hàng</h2>
          <p class="dispatch-modal__subtitle">Theo dõi GPS hiện tại, cập nhật quãng đường còn lại và mở chỉ đường lái xe tới địa chỉ của khách hàng.</p>
        </div>
        <button type="button" class="dispatch-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="dispatch-modal__body">
        <div class="dispatch-route-grid">
          <section>
            <div class="dispatch-route-map-shell">
              <div
                id="routeMapCanvas"
                class="dispatch-route-map-canvas"
                aria-label="Bản đồ chỉ đường tới khách hàng"
              ></div>

              <div class="dispatch-route-map-fallback" id="routeMapFallback" hidden>
                <span class="material-symbols-outlined">route</span>
                <strong id="routeMapFallbackTitle">Đang chờ vị trí hiện tại</strong>
                <p id="routeMapFallbackText">Cho phép truy cập GPS để hệ thống hiển thị bản đồ chỉ đường từ vị trí của bạn tới nhà khách hàng.</p>
              </div>
            </div>

            <div class="dispatch-route-toolbar">
              <button type="button" class="dispatch-btn dispatch-btn--secondary" id="routeRefreshLocationBtn">
                <span class="material-symbols-outlined">my_location</span>
                Làm mới vị trí
              </button>
              <a href="#" target="_blank" rel="noopener" class="dispatch-btn dispatch-btn--primary" id="routeOpenExternalBtn">
                <span class="material-symbols-outlined">navigation</span>
                Mở bản đồ ngoài
              </a>
            </div>
          </section>

          <aside>
            <div class="dispatch-route-card">
              <div class="dispatch-route-card__eyebrow">Điểm đến</div>
              <h3 class="dispatch-route-card__title" id="routeServiceName">Đơn sửa chữa</h3>
              <p class="dispatch-route-card__address" id="routeDestinationAddress">Đang tải địa chỉ khách hàng...</p>
              <div class="dispatch-route-coords" id="routeDestinationCoords">Tọa độ đích sẽ hiển thị tại đây.</div>
            </div>

            <div class="dispatch-route-stats">
              <div class="dispatch-route-stat">
                <span class="dispatch-route-stat__label">Quãng đường còn lại</span>
                <strong class="dispatch-route-stat__value" id="routeDistanceValue">--</strong>
                <span class="dispatch-route-stat__hint" id="routeDistanceHint">Cho phép GPS để hệ thống tính khoảng cách còn lại.</span>
              </div>

              <div class="dispatch-route-stat">
                <span class="dispatch-route-stat__label">ETA dự kiến</span>
                <strong class="dispatch-route-stat__value" id="routeEtaValue">--</strong>
                <span class="dispatch-route-stat__hint" id="routeEtaHint">Thời gian đến nơi sẽ hiển thị theo dữ liệu tuyến đường thực.</span>
              </div>

              <div class="dispatch-route-stat">
                <span class="dispatch-route-stat__label">Vị trí hiện tại</span>
                <strong class="dispatch-route-stat__value dispatch-route-stat__value--small" id="routeCurrentCoords">Đang chờ vị trí hiện tại...</strong>
                <span class="dispatch-route-stat__hint" id="routeLastUpdated">Chưa có lần cập nhật nào.</span>
              </div>
            </div>

            <div class="dispatch-route-status" id="routeTrackingStatus" data-tone="info">
              Mở modal để bắt đầu theo dõi vị trí realtime.
            </div>

            <div class="dispatch-inline-note mt-3" id="routeMapStatus">
              Bản đồ chỉ đường sẽ được tải sau khi hệ thống nhận được vị trí hiện tại của bạn.
            </div>

            <div class="dispatch-route-card dispatch-route-card--subtle mt-3">
              <div class="dispatch-route-card__eyebrow">Mã đơn đang dẫn đường</div>
              <div class="dispatch-route-code" id="routeBookingCode">#0000</div>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade dispatch-modal" id="modalCompleteBooking" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content dispatch-modal__content">
      <div class="dispatch-modal__header d-flex justify-content-between align-items-start gap-3">
        <div>
          <div class="dispatch-modal__eyebrow">Completion Flow</div>
          <h2 class="dispatch-modal__title">Hoàn thành sửa chữa</h2>
          <p class="dispatch-modal__subtitle">Xác nhận lại quy trình, chọn phương thức thanh toán và tải lên minh chứng hoàn thành trước khi gửi yêu cầu cho khách.</p>
        </div>
        <button type="button" class="dispatch-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="dispatch-modal__body">
        <form id="formCompleteBooking">
          <input type="hidden" id="completeBookingId">

          <div class="dispatch-booking-hero">
            <div class="dispatch-booking-hero__row">
              <div>
                <p class="dispatch-booking-hero__customer" id="completeCustomerName">Khách hàng</p>
                <p class="dispatch-booking-hero__service" id="completeServiceName">Dịch vụ sửa chữa</p>
              </div>
              <div class="text-end">
                <span class="dispatch-pill dispatch-pill--status dispatch-pill--payment" id="completeStatusBadge">Sẵn sàng báo hoàn thành</span>
                <div class="dispatch-summary-box__value mt-2" id="completeBookingTotal">0 đ</div>
              </div>
            </div>
          </div>

          <div class="dispatch-complete-grid">
            <div class="dispatch-panel">
              <h3 class="dispatch-panel__title">Bước 1. Kiểm tra quy trình</h3>

              <div class="dispatch-workflow">
                <div class="dispatch-workflow__list" id="completeWorkflowList">
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
                    <span>Chuẩn bị gửi yêu cầu thanh toán</span>
                  </div>
                </div>
              </div>

              <div class="dispatch-alert" id="completePricingAlert" style="display:none;">
                <span class="material-symbols-outlined">warning</span>
                <span>Bạn cần cập nhật chi phí trước khi báo hoàn thành đơn.</span>
              </div>

              <div class="dispatch-inline-note mt-4">
                Hãy tải lên hình ảnh rõ ràng về linh kiện đã thay hoặc thiết bị đã vận hành ổn định để khách dễ xác nhận hơn.
              </div>
            </div>

            <div class="dispatch-panel">
              <h3 class="dispatch-panel__title">Bước 2. Chọn phương thức thanh toán</h3>

              <div class="dispatch-radio-grid">
                <label class="dispatch-pay-option" id="completePaymentOptionCod">
                  <input type="radio" name="phuong_thuc_thanh_toan" value="cod" checked>
                  <span class="dispatch-pay-option__card">
                    <span class="material-symbols-outlined">payments</span>
                    <span class="dispatch-pay-option__copy">
                      <strong>Tiền mặt</strong>
                      <small>Thợ xác nhận hoàn thành là đơn được chốt ngay.</small>
                    </span>
                  </span>
                </label>

                <label class="dispatch-pay-option" id="completePaymentOptionTransfer">
                  <input type="radio" name="phuong_thuc_thanh_toan" value="transfer">
                  <span class="dispatch-pay-option__card">
                    <span class="material-symbols-outlined">account_balance_wallet</span>
                    <span class="dispatch-pay-option__copy">
                      <strong>Chuyển khoản</strong>
                      <small>Khách phải thanh toán online xong thì đơn mới hoàn tất.</small>
                    </span>
                  </span>
                </label>
              </div>

              <div class="dispatch-readonly dispatch-readonly--accent mt-3">
                <div class="dispatch-readonly__value">
                  <div>
                    <strong id="completePaymentMethodTitle">Tiền mặt</strong>
                    <div class="dispatch-summary-tile__hint mt-2" id="completePaymentMethodHint">
                      Khi bạn xác nhận hoàn thành, đơn sẽ chuyển thành hoàn tất ngay với ghi nhận đã thu tiền mặt.
                    </div>
                  </div>
                  <span class="dispatch-pill dispatch-pill--payment" id="completePaymentMethodBadge">Hoàn tất ngay</span>
                </div>
              </div>

              <h3 class="dispatch-panel__title mt-4">Bước 3. Minh chứng hoàn thành</h3>

              <div class="dispatch-upload-grid">
                <div class="dispatch-upload-area">
                  <h4 class="dispatch-upload-area__title">Hình ảnh sửa chữa</h4>
                  <p class="dispatch-upload-area__hint">Tối đa 5 ảnh. Ưu tiên ảnh toàn cảnh, ảnh linh kiện mới và ảnh máy hoạt động ổn định.</p>
                  <input type="file" class="dispatch-file-input" id="inputHinhAnhKetQua" name="hinh_anh_ket_qua[]" multiple accept="image/*">
                  <div id="imageUploadPreview" class="dispatch-preview-grid"></div>
                </div>

                <div class="dispatch-upload-area">
                  <h4 class="dispatch-upload-area__title">Video vận hành</h4>
                  <p class="dispatch-upload-area__hint">Tùy chọn. Tải lên video chạy thử sau sửa chữa để khách dễ đối chiếu.</p>
                  <input type="file" class="dispatch-file-input" id="inputVideoKetQua" name="video_ket_qua" accept="video/*">
                  <div id="videoUploadPreview" class="dispatch-preview-grid"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="dispatch-modal__footer">
            <button type="button" class="dispatch-btn dispatch-btn--ghost" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" class="dispatch-btn dispatch-btn--primary" id="btnSubmitCompleteForm">
              <span class="material-symbols-outlined">task_alt</span>
              Xác nhận hoàn thành
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module" src="{{ asset('assets/js/worker/my-bookings.js') }}"></script>
@endpush
