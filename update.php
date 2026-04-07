<?php
$file = 'resources/views/worker/my-bookings.blade.php';
$content = file_get_contents($file);

// Normalize line endings
$content = str_replace("\r\n", "\n", $content);

$NEW_MODAL = <<<EOT
<div class="modal fade dispatch-modal dispatch-modal--pricing-v2" id="modalCosts" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
    <div class="modal-content dispatch-modal__content dispatch-modal__content--pricing-v2">
      <div class="dispatch-modal__header dispatch-modal__header--pricing-v2 d-flex justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <span class="dispatch-modal__title-accent d-none d-md-inline">Repair Quota</span>
          <div class="dispatch-modal__header-divider d-none d-md-block"></div>
          <div>
            <h2 class="dispatch-modal__title mb-0">Cập nhật bảng giá sửa chữa</h2>
            <p class="dispatch-modal__subtitle d-none d-md-block m-0 mt-1" style="font-size: 0.85rem">Điền rõ từng hạng mục để khách dễ kiểm tra, còn bạn dễ rà soát tổng tiền.</p>
          </div>
        </div>
        <button type="button" class="dispatch-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="dispatch-modal__body dispatch-modal__body--pricing-v2 p-0">
        <form id="formUpdateCosts" class="dispatch-pricing-form h-100 d-flex flex-column" onsubmit="event.preventDefault(); return false;">
          <input type="hidden" id="costBookingId">
          
          <div class="dispatch-pricing-v2-main p-4 flex-grow-1">
            <!-- Context Header Section -->
            <section class="dispatch-pricing-v2-context mb-4">
              <div class="dispatch-pricing-v2-context-inner">
                <span class="dispatch-pricing-v2-context-eyebrow" id="costBookingReference">Đơn #0000</span>
                <h1 class="dispatch-pricing-v2-context-title" id="costCustomerName">Khách hàng</h1>
                <div class="dispatch-pricing-v2-context-meta">
                  <div class="dispatch-pricing-v2-meta-item">
                    <span class="material-symbols-outlined">home_repair_service</span>
                    <span id="costServiceName">Dịch vụ sửa chữa</span>
                  </div>
                  <div class="dispatch-pricing-v2-meta-item">
                    <span class="material-symbols-outlined">settings</span>
                    <span id="costServiceModeBadge" data-state="travel">Sửa tại nhà</span>
                  </div>
                  <div class="dispatch-pricing-v2-meta-item">
                    <span class="material-symbols-outlined">local_shipping</span>
                    <span id="costTruckBadge" data-state="muted">Không thuê xe chở</span>
                  </div>
                  <div class="dispatch-pricing-v2-meta-item" id="costDistanceContainer">
                    <span class="material-symbols-outlined">directions_car</span>
                    <span id="costDistanceBadge" data-state="travel">Phí đi lại tự động</span>
                  </div>
                </div>
              </div>
            </section>

            <!-- Custom 2 column layout for desktop inside body -->
            <div class="dispatch-pricing-v2-content-grid">
              <!-- LEFT COLUMN: Editor -->
              <div class="dispatch-pricing-v2-editor">
                
                <section class="dispatch-pricing-v2-section">
                  <div class="dispatch-pricing-v2-section-head">
                    <h3 class="dispatch-pricing-v2-section-title">Nội dung công việc (Tiền công)</h3>
                    <div class="d-flex align-items-center gap-3">
                       <span class="badge bg-secondary rounded-pill" id="laborCountBadge">0 dòng</span>
                       <button type="button" class="dispatch-pricing-v2-add-btn" id="addLaborItem">
                         <span class="material-symbols-outlined" style="font-size: 18px;">add_circle</span>
                         Thêm dòng công
                       </button>
                    </div>
                  </div>
                  <div class="dispatch-pricing-v2-labor-table-wrapper table-responsive">
                    <table class="dispatch-pricing-v2-table mb-0">
                      <thead>
                        <tr>
                          <th>Work Description</th>
                          <th class="text-end" style="width: 200px;">Cost (VND)</th>
                          <th style="width: 50px;"></th>
                        </tr>
                      </thead>
                      <tbody id="laborItemsContainer"></tbody>
                    </table>
                  </div>
                </section>

                <section class="dispatch-pricing-v2-section">
                  <div class="dispatch-pricing-v2-section-head mb-0">
                     <h3 class="dispatch-pricing-v2-section-title">Linh kiện & Phụ tùng</h3>
                     <span class="badge bg-secondary rounded-pill" id="partCountBadge">0 dòng</span>
                  </div>
                  <div class="dispatch-pricing-v2-searchbox">
                    <span class="material-symbols-outlined dispatch-pricing-v2-search-icon">search</span>
                    <input type="search" class="dispatch-pricing-v2-search-input" id="partCatalogSearch" placeholder="Tìm linh kiện theo tên hoặc ID..." autocomplete="off">
                    <button type="button" class="btn btn-sm btn-primary d-none" id="addSelectedParts">Thêm lựa chọn</button>
                    <div class="dispatch-part-catalog__suggestions" id="partCatalogSuggestions" hidden style="top: 100%; left: 0; position: absolute; width: 100%; z-index: 10; background: #fff; border: 1px solid #ddd; border-top: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 0 0 8px 8px;"></div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                     <span class="dispatch-part-catalog__status text-muted small" id="partCatalogStatus">Mở đơn để tải danh mục linh kiện.</span>
                     <button type="button" class="dispatch-pricing-v2-add-btn" id="addPartItem">Thêm dòng thủ công</button>
                  </div>
                  <div id="partCatalogResults"></div>
                  <div class="dispatch-pricing-v2-parts-list" id="partItemsContainer"></div>
                </section>

                <section class="dispatch-pricing-v2-section dispatch-pricing-v2-fees-section">
                  <div class="dispatch-pricing-v2-fee-card" id="truckFeeContainer" style="display:none;">
                    <h3 class="dispatch-pricing-v2-fee-title">Phí xe chở (Hired Logistic)</h3>
                    <div class="dispatch-pricing-v2-fee-input-wrap">
                      <span class="dispatch-pricing-v2-fee-prefix">đ</span>
                      <input type="number" class="dispatch-pricing-v2-fee-input" id="inputTienThueXe" min="0" value="0">
                    </div>
                  </div>
                  <div class="dispatch-pricing-v2-fee-card">
                    <h3 class="dispatch-pricing-v2-fee-title">Phí đi lại (Travel Fees)</h3>
                    <div class="d-flex justify-content-between align-items-center py-2">
                       <span class="dispatch-pricing-v2-fee-label">Standard Travel Charge</span>
                       <strong class="dispatch-pricing-v2-fee-value" id="displayPhiDiLai">0 đ</strong>
                    </div>
                    <p class="dispatch-pricing-v2-fee-hint m-0 text-muted" id="costDistanceHint">Hệ thống tính tự động theo quãng đường phục vụ.</p>
                  </div>
                </section>
                
                <section class="dispatch-pricing-v2-section mb-5">
                   <h3 class="dispatch-pricing-v2-section-title mb-3">Ghi chú cho khách hàng</h3>
                   <textarea class="dispatch-pricing-v2-textarea" id="inputGhiChuLinhKien" placeholder="Ví dụ: Đã thay bo nguồn mới, chạy thử ổn định..." rows="3"></textarea>
                </section>

              </div>

              <!-- RIGHT COLUMN / BOTTOM: Summary -->
              <div class="dispatch-pricing-v2-summary">
                <div class="dispatch-pricing-v2-summary-card">
                   <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
                      <h3 class="dispatch-pricing-v2-summary-title">Tóm tắt chi phí</h3>
                      <span class="dispatch-pricing-v2-summary-status" id="costDraftState">Sẵn sàng lưu</span>
                   </div>
                   
                   <div class="dispatch-pricing-v2-summary-list">
                      <div class="dispatch-pricing-v2-summary-row">
                         <span>Tổng tiền công</span>
                         <strong id="laborSubtotal">0 đ</strong>
                      </div>
                      <div class="dispatch-pricing-v2-summary-row">
                         <span>Tổng linh kiện</span>
                         <strong id="partsSubtotal">0 đ</strong>
                      </div>
                      <div class="dispatch-pricing-v2-summary-row" id="travelSummaryRow" style="display:none;">
                         <span>Phí đi lại cố định</span>
                         <strong id="travelSubtotal">0 đ</strong>
                      </div>
                      <div class="dispatch-pricing-v2-summary-row" id="truckSummaryRow" style="display:none;">
                         <span>Phí xe chở</span>
                         <strong id="truckSubtotal">0 đ</strong>
                      </div>
                   </div>

                   <hr class="my-4" style="border-color: rgba(148, 163, 184, 0.2);">

                   <div class="dispatch-pricing-v2-total-callout">
                      <div class="dispatch-pricing-v2-total-label"><span class="material-symbols-outlined align-middle me-1">calculate</span> Tổng cộng tất cả chi phí</div>
                      <div class="dispatch-pricing-v2-total-value" id="costEstimateTotal">0 đ</div>
                      <div class="small text-muted mt-2" id="costSummaryHint">Bao gồm công, linh kiện, và phí phụ thêm.</div>
                   </div>

                </div>
              </div>
            </div>
          </div>
          <!-- Sticky Footer -->
          <footer class="dispatch-pricing-v2-footer">
             <div class="container-fluid">
                <div class="d-flex justify-content-end align-items-center gap-3">
                   <button type="button" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--ghost" data-bs-dismiss="modal">Hủy</button>
                   <button type="button" class="dispatch-pricing-v2-btn dispatch-pricing-v2-btn--primary" id="btnSubmitCostUpdate">
                      <span class="material-symbols-outlined">save</span> Lưu chi phí
                   </button>
                </div>
             </div>
          </footer>
        </form>
      </div>
    </div>
  </div>
</div>
EOT;

$NEW_CSS = <<<EOT
  /* PRICING V2 STYLES (Industrial Premium) */
  .dispatch-modal__content--pricing-v2 { background: #fbf9f8; border-radius: 24px; border: 0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
  .dispatch-modal__header--pricing-v2 { background: #ffffff; border-bottom: 1px solid rgba(195, 198, 214, 0.3); padding: 20px 24px; }
  .dispatch-modal__title-accent { color: #0040a1; font-family: 'Inter', sans-serif; font-weight: 800; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; }
  .dispatch-modal__header-divider { width: 1px; height: 24px; background: rgba(195, 198, 214, 0.4); }
  .dispatch-modal__title { color: #1c1b1b; font-family: 'Inter', sans-serif; font-weight: 800; font-size: 1.4rem; }
  .dispatch-modal__close--v2 { width: 44px; height: 44px; border-radius: 12px; }
  .dispatch-pricing-form { position: relative; background: #fbf9f8; }
  .dispatch-pricing-v2-main { background: transparent; padding-bottom: 80px; }
  .dispatch-pricing-v2-context { background: #ffffff; border-radius: 16px; padding: 24px; border: 1px solid rgba(195,198,214,0.3); border-left: 4px solid #0056d2; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); }
  .dispatch-pricing-v2-context-eyebrow { display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #526772; margin-bottom: 6px; }
  .dispatch-pricing-v2-context-title { font-family: 'Inter', sans-serif; font-weight: 800; font-size: 1.6rem; color: #1c1b1b; margin: 0 0 16px 0; }
  .dispatch-pricing-v2-context-meta { display: flex; flex-wrap: wrap; gap: 16px; }
  .dispatch-pricing-v2-meta-item { display: flex; align-items: center; gap: 6px; color: #424654; font-weight: 600; font-size: 0.9rem; background: #f6f3f2; padding: 6px 12px; border-radius: 99px; border: 1px solid rgba(195,198,214,0.2); }
  .dispatch-pricing-v2-meta-item .material-symbols-outlined { color: #0056d2; font-size: 18px; }
  .dispatch-pricing-v2-content-grid { display: grid; grid-template-columns: 1fr; gap: 32px; }
  @media (min-width: 992px) { .dispatch-pricing-v2-content-grid { grid-template-columns: 1fr 380px; } }
  .dispatch-pricing-v2-section { margin-bottom: 36px; }
  .dispatch-pricing-v2-section-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
  .dispatch-pricing-v2-section-title { font-family: 'Inter', sans-serif; font-weight: 800; font-size: 1.15rem; color: #1c1b1b; margin: 0; }
  .dispatch-pricing-v2-add-btn { display: flex; align-items: center; gap: 6px; color: #0056d2; font-weight: 700; font-size: 0.85rem; background: transparent; border: none; cursor: pointer; padding: 4px 8px; border-radius: 6px; }
  .dispatch-pricing-v2-add-btn:hover { background: rgba(0,86,210,0.05); }
  .dispatch-pricing-v2-labor-table-wrapper { background: #ffffff; border-radius: 12px; border: 1px solid rgba(195, 198, 214, 0.2); overflow: hidden; }
  .dispatch-pricing-v2-table { width: 100%; border-collapse: collapse; }
  .dispatch-pricing-v2-table th { background: #f6f3f2; padding: 12px 16px; color: #526772; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid rgba(195,198,214,0.2); }
  .dispatch-pricing-v2-table td { padding: 12px 16px; border-bottom: 1px solid rgba(195, 198, 214, 0.15); vertical-align: middle; }
  .dispatch-pricing-v2-table tr:last-child td { border-bottom: none; }
  .dispatch-pricing-v2-searchbox { background: #ffffff; border: 1px solid rgba(195,198,214,0.4); border-radius: 10px; display: flex; align-items: center; padding: 0 16px; height: 48px; margin-top: 12px; position: relative; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
  .dispatch-pricing-v2-searchbox:focus-within { border-color: #0056d2; box-shadow: 0 0 0 3px rgba(0, 86, 210, 0.15); }
  .dispatch-pricing-v2-search-icon { color: #94a3b8; margin-right: 12px; }
  .dispatch-pricing-v2-search-input { background: transparent; border: none; width: 100%; outline: none; font-weight: 600; color: #1c1b1b; font-size: 0.95rem; }
  .dispatch-pricing-v2-part-card { background: #ffffff; border: 1px solid rgba(195, 198, 214, 0.3); border-radius: 12px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
  .dispatch-pricing-v2-part-card-inner { display: flex; flex-direction: column; gap: 16px; }
  @media (min-width: 768px) { .dispatch-pricing-v2-part-card-inner { flex-direction: row; align-items: center; justify-content: space-between; } }
  .dispatch-pricing-v2-part-icon { width: 54px; height: 54px; border-radius: 10px; background: #f6f3f2; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(195,198,214,0.2); }
  .dispatch-pricing-v2-part-icon .material-symbols-outlined { font-size: 28px; color: #94a3b8; }
  .dispatch-pricing-v2-part-title { font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1rem; color: #1c1b1b; margin: 0 0 4px 0; }
  .dispatch-pricing-v2-part-cat { font-size: 0.65rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; }
  .dispatch-pricing-v2-stepper { display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 4px; border-radius: 8px; border: 1px solid rgba(195,198,214,0.3); }
  .dispatch-pricing-v2-stepper-btn { width: 28px; height: 28px; border-radius: 6px; border: none; background: #ffffff; color: #475569; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border: 1px solid rgba(195,198,214,0.3); }
  .dispatch-pricing-v2-stepper-btn:hover { background: #f1f5f9; }
  .dispatch-pricing-v2-input-dark { background: #f8fafc; border: 1px solid rgba(195,198,214,0.3); border-radius: 6px; height: 36px; padding: 0 10px; font-weight: 700; font-size: 0.9rem; width: 100%; color: #1e293b; transition: border-color 0.2s; }
  .dispatch-pricing-v2-input-dark:focus { border-color: #0056d2; outline: none; }
  .dispatch-pricing-v2-fees-section { display: grid; grid-template-columns: 1fr; gap: 20px; }
  @media (min-width: 768px) { .dispatch-pricing-v2-fees-section { grid-template-columns: 1fr 1fr; } }
  .dispatch-pricing-v2-fee-card { background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid rgba(195, 198, 214, 0.3); }
  .dispatch-pricing-v2-fee-title { font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1rem; color: #1c1b1b; margin: 0 0 16px 0; }
  .dispatch-pricing-v2-fee-input-wrap { position: relative; display: flex; align-items: center; }
  .dispatch-pricing-v2-fee-prefix { position: absolute; right: 14px; font-weight: 700; color: #94a3b8; }
  .dispatch-pricing-v2-fee-input { width: 100%; padding-left: 14px; padding-right: 36px; height: 44px; border-radius: 8px; border: 1px solid rgba(195,198,214,0.3); background: #f8fafc; font-weight: 800; color: #0056d2; font-size: 1.1rem; }
  .dispatch-pricing-v2-fee-label { color: #475569; font-weight: 600; font-size: 0.9rem; }
  .dispatch-pricing-v2-fee-value { font-family: 'Inter', sans-serif; font-weight: 800; color: #334155; font-size: 1.1rem; }
  .dispatch-pricing-v2-fee-hint { font-size: 0.75rem; }
  .dispatch-pricing-v2-textarea { width: 100%; background: #ffffff; border: 1px solid rgba(195,198,214,0.3); border-radius: 12px; padding: 16px; color: #1c1b1b; font-weight: 500; resize: vertical; }
  .dispatch-pricing-v2-textarea:focus { border-color: #0056d2; box-shadow: 0 0 0 3px rgba(0, 86, 210, 0.1); outline: none; }
  .dispatch-pricing-v2-summary { position: sticky; top: 24px; }
  .dispatch-pricing-v2-summary-card { background: #ffffff; border-radius: 16px; padding: 24px; box-shadow: 0 12px 36px rgba(15,23,42,0.08); border: 1px solid rgba(195, 198, 214, 0.4); }
  .dispatch-pricing-v2-summary-title { font-family: 'Inter', sans-serif; font-weight: 800; font-size: 1.25rem; margin: 0; }
  .dispatch-pricing-v2-summary-status { background: rgba(15, 159, 124, 0.15); color: #0f9f7c; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.05em; }
  .dispatch-pricing-v2-summary-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-weight: 600; color: #64748b; font-size: 0.95rem; }
  .dispatch-pricing-v2-summary-row strong { color: #1e293b; }
  .dispatch-pricing-v2-total-callout { display: flex; flex-direction: column; }
  .dispatch-pricing-v2-total-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
  .dispatch-pricing-v2-total-value { font-family: 'Inter', sans-serif; font-weight: 900; font-size: 2.4rem; color: #0056d2; letter-spacing: -0.04em; line-height: 1.1; margin-top: 8px; }
  .dispatch-pricing-v2-footer { position: sticky; bottom: 0; left: 0; width: 100%; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-top: 1px solid rgba(195, 198, 214, 0.4); padding: 16px 24px; z-index: 100; box-shadow: 0 -4px 20px rgba(0,0,0,0.03); }
  .dispatch-pricing-v2-btn { padding: 12px 28px; border-radius: 10px; font-weight: 800; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: all 0.2s ease; font-size: 0.95rem; }
  .dispatch-pricing-v2-btn--ghost { background: transparent; color: #64748b; }
  .dispatch-pricing-v2-btn--ghost:hover { background: #f1f5f9; color: #0f172a; }
  .dispatch-pricing-v2-btn--primary { background: linear-gradient(135deg, #0d7cc1, #095b91); color: #ffffff; box-shadow: 0 4px 14px rgba(13, 124, 193, 0.3); }
  .dispatch-pricing-v2-btn--primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(13, 124, 193, 0.4); }
  .dispatch-pricing-v2-remove-btn { background: transparent; border: none; color: #cbd5e1; cursor: pointer; padding: 6px; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.15s; }
  .dispatch-pricing-v2-remove-btn:hover { background: #fef2f2; color: #dc2626; }
  .dispatch-line-item { /* to be identified by JS */ }
  /* END PRICING V2 STYLES */
EOT;

$NEW_JS = <<<EOT
const buildCostItemRowMarkup = (type, item = {}) => {
  const description = escapeHtml(item?.noi_dung || '');
  const isPart = type === 'part';
  const amount = isPart ? getPartUnitPrice(item) : getNumeric(item?.so_tien);
  const amountValue = amount > 0 ? amount : '';
  const catalogPartId = isPart ? getNumeric(item?.linh_kien_id) : 0;
  const serviceId = isPart ? getNumeric(item?.dich_vu_id) : 0;
  const image = isPart ? escapeHtml(item?.hinh_anh || '') : '';
  const isCatalogItem = isPart && catalogPartId > 0;
  const quantityValue = isPart ? getPartQuantity(item) : '';
  const warrantyValue = isPart && item?.bao_hanh_thang !== null && item?.bao_hanh_thang !== undefined
    ? getNumeric(item.bao_hanh_thang)
    : '';

  if (isPart) {
    return `
      <div class="dispatch-line-item dispatch-pricing-v2-part-card" data-line-type="\${type}" data-catalog-part-id="\${catalogPartId || ''}">
        <input type="hidden" class="js-line-part-id" value="\${catalogPartId || ''}">
        <input type="hidden" class="js-line-service-id" value="\${serviceId || ''}">
        <input type="hidden" class="js-line-image" value="\${image}">
        
        <div class="dispatch-pricing-v2-part-card-inner flex-grow-1 w-100">
          <div class="d-flex gap-3 align-items-center mb-3 mb-md-0" style="flex: 1;">
            <div class="dispatch-pricing-v2-part-icon">
              \${image ? \`<img src="\${image}" style="width:100%; height:100%; object-fit: cover; border-radius:10px;">\` : \`<span class="material-symbols-outlined">settings_input_component</span>\`}
            </div>
            <div class="flex-grow-1">
              <h4 class="dispatch-pricing-v2-part-title m-0">
                <input type="text" class="dispatch-pricing-v2-input-dark js-line-description m-0" style="background:transparent; border:none; padding:0; padding-bottom: 4px; box-shadow:none; outline:none;" value="\${description}" placeholder="Bo mạch..." \${isCatalogItem ? 'readonly' : ''}>
              </h4>
              <span class="dispatch-pricing-v2-part-cat">\${isCatalogItem ? 'TỪ DANH MỤC' : 'TỰ NHẬP MỚI'}</span>
            </div>
          </div>
          
          <div class="d-flex flex-wrap align-items-center gap-3">
             <div class="dispatch-pricing-v2-stepper">
                <button type="button" class="dispatch-pricing-v2-stepper-btn js-quantity-step" data-step="-1"><span class="material-symbols-outlined" style="font-size: 16px;">remove</span></button>
                <input type="number" class="dispatch-pricing-v2-input-dark js-line-quantity text-center" style="width: 44px; padding:0; border:none; background:transparent;" min="1" step="1" value="\${quantityValue}" placeholder="1">
                <button type="button" class="dispatch-pricing-v2-stepper-btn js-quantity-step" data-step="1"><span class="material-symbols-outlined" style="font-size: 16px;">add</span></button>
             </div>
             
             <div style="width: 100px;">
                <label class="text-muted d-block mb-1" style="font-size:0.7rem; font-weight:700; text-transform:uppercase;">Bảo hành(th)</label>
                <input type="number" class="dispatch-pricing-v2-input-dark js-line-warranty" value="\${warrantyValue}" placeholder="12">
             </div>
             
             <div style="width: 120px;" class="text-end">
                <label class="text-muted d-block mb-1 text-end" style="font-size:0.7rem; font-weight:700; text-transform:uppercase;">Đơn giá</label>
                <input type="number" class="dispatch-pricing-v2-input-dark js-line-amount text-end" value="\${amountValue}" placeholder="50000" \${isCatalogItem ? 'readonly' : ''}>
             </div>

             <button type="button" class="dispatch-pricing-v2-remove-btn dispatch-line-item__remove ms-2">
                <span class="material-symbols-outlined">delete</span>
             </button>
          </div>
        </div>
      </div>
    `;
  } else {
    // Labor Row inside Table
    return `
      <tr class="dispatch-line-item" data-line-type="\${type}">
        <td class="px-3 py-2">
           <input type="text" class="dispatch-pricing-v2-input-dark js-line-description" value="\${description}" placeholder="Ví dụ: Vệ sinh dàn lạnh">
        </td>
        <td class="px-3 py-2 text-end" style="width: 200px;">
           <input type="number" class="dispatch-pricing-v2-input-dark js-line-amount text-end" style="color: #0056d2; font-size: 1rem;" value="\${amountValue}" placeholder="100000">
        </td>
        <td class="px-2 py-2 text-center" style="width: 50px;">
           <button type="button" class="dispatch-pricing-v2-remove-btn dispatch-line-item__remove mx-auto" aria-label="Xóa dòng">
              <span class="material-symbols-outlined">close</span>
           </button>
        </td>
      </tr>
    `;
  }
};
EOT;

// Perform the replacements
$content = str_replace('  </style>', $NEW_CSS . "\n  </style>", $content);

$modalStart = '<div class="modal fade dispatch-modal dispatch-modal--pricing" id="modalCosts"';
$modalEndMarker = '</div>' . "\n" . '</div>' . "\n\n" . '<div class="modal fade dispatch-modal" id="modalViewDetails"';
$posStart = strpos($content, $modalStart);
$posEnd = strpos($content, $modalEndMarker);

if ($posStart !== false && $posEnd !== false) {
    $content = substr($content, 0, $posStart) . $NEW_MODAL . "\n\n" . substr($content, $posEnd);
    echo "Modal Replaced.\n";
} else {
    echo "Could not find modal markers.\n";
}

$jsStartMarker = 'const buildCostItemRowMarkup = (type, item = {}) => {';
$jsEndMarker = '};' . "\n\n" . 'const populateCostItemRows =';
$posJsStart = strpos($content, $jsStartMarker);
$posJsEnd = strpos($content, $jsEndMarker);

if ($posJsStart !== false && $posJsEnd !== false) {
    $content = substr($content, 0, $posJsStart) . $NEW_JS . "\n" . substr($content, $posJsEnd + 2);
    echo "JS Replaced.\n";
} else {
    echo "Could not find JS markers.\n";
}

file_put_contents($file, $content);
echo "Done saving file.\n";
?>
