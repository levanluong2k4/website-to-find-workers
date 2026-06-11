import { callApi, showToast, downloadApiFile } from '../api.js';

const fmt = (n) => Math.round(n || 0).toLocaleString('vi-VN') + 'đ';
const pct = (n) => `${n}%`;

const ChuSo = ["không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín"];
const Tien = ["", "ngàn", "triệu", "tỷ", "ngàn tỷ", "triệu tỷ"];

function docSo3ChuSo(baso) {
    let tram = Math.floor(baso / 100);
    let chuc = Math.floor((baso % 100) / 10);
    let donvi = baso % 10;
    let KetQua = "";
    
    if (tram !== 0 || chuc !== 0 || donvi !== 0) {
        KetQua += ChuSo[tram] + " trăm ";
        if (chuc === 0 && donvi !== 0) KetQua += "lẻ ";
    }
    
    if (chuc !== 0 && chuc !== 1) KetQua += ChuSo[chuc] + " mươi ";
    if (chuc === 1) KetQua += "mười ";
    
    switch (donvi) {
        case 1:
            if (chuc !== 0 && chuc !== 1) KetQua += "mốt ";
            else KetQua += ChuSo[donvi] + " ";
            break;
        case 5:
            if (chuc === 0) KetQua += ChuSo[donvi] + " ";
            else KetQua += "lăm ";
            break;
        default:
            if (donvi !== 0) KetQua += ChuSo[donvi] + " ";
            break;
    }
    return KetQua;
}

const formatReadableText = (amount) => {
    if (!amount) return "Không đồng";
    let soTienStr = Math.round(amount).toString();
    let soLan = Math.ceil(soTienStr.length / 3);
    let KetQua = "";
    
    let mangSo = [];
    for (let i = 0; i < soLan; i++) {
        let start = soTienStr.length - (i + 1) * 3;
        let end = soTienStr.length - i * 3;
        if (start < 0) start = 0;
        mangSo.push(parseInt(soTienStr.slice(start, end), 10));
    }
    
    for (let i = soLan - 1; i >= 0; i--) {
        if (mangSo[i] > 0) {
            let doc = docSo3ChuSo(mangSo[i]);
            if (i === soLan - 1) doc = doc.replace(/^không trăm (lẻ )?/, "");
            KetQua += doc + Tien[i] + " ";
        } else if (i === 3 && soLan > 3) {
            KetQua += Tien[i] + " ";
        }
    }
    
    KetQua = KetQua.trim().replace(/\s+/g, ' ') + " đồng";
    return KetQua.charAt(0).toUpperCase() + KetQua.slice(1);
};

let state = { period: 'today', from: '', to: '', wStatus: 'all', wPage: 1, orderPage: 1, wSearch: '', chart: null };

// ── period buttons ──────────────────────────────────────
document.getElementById('periodBar').addEventListener('click', (e) => {
  const btn = e.target.closest('[data-period]');
  if (!btn) return;
  state.period = btn.dataset.period;
  document.querySelectorAll('.period-btn').forEach(b => b.classList.toggle('active', b === btn));
  
  document.getElementById('customStartDate').value = '';
  document.getElementById('customEndDate').value = '';
  state.from = '';
  state.to = '';
  document.getElementById('customDateLabel').textContent = 'Tùy chọn...';
  
  state.orderPage = 1;

  loadRevenue();
});

// Custom Date Popover logic
const customDateBtn = document.getElementById('customDateBtn');
const datePickerPopover = document.getElementById('datePickerPopover');

customDateBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    datePickerPopover.classList.toggle('tw-hidden');
});

document.addEventListener('click', (e) => {
    if (!document.getElementById('datePickerContainer').contains(e.target)) {
        datePickerPopover.classList.add('tw-hidden');
    }
});

document.getElementById('applyCustomDate').addEventListener('click', () => {
    const start = document.getElementById('customStartDate').value;
    const end = document.getElementById('customEndDate').value;
    
    if (start || end) {
        state.period = 'custom';
        state.from = start;
        state.to = end;
        state.orderPage = 1;
        document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
        customDateBtn.classList.add('active');
        
        let label = '';
        if (start && end) label = `${start} - ${end}`;
        else if (start) label = `Từ ${start}`;
        else label = `Đến ${end}`;
        document.getElementById('customDateLabel').textContent = label;
        
        datePickerPopover.classList.add('tw-hidden');
        loadRevenue();
    }
});

document.getElementById('exportExcelBtn').addEventListener('click', async () => {
    try {
        let url = `/admin/revenue/export?period=${state.period}`;
        if (state.from) url += `&from=${state.from}`;
        if (state.to) url += `&to=${state.to}`;
        await downloadApiFile(url, `bang_luong_tho_${state.period}.csv`);
    } catch (err) {
        showToast(err.message || 'Không thể xuất file excel', 'error');
    }
});

// ── withdrawal tabs ──────────────────────────────────────
document.querySelectorAll('[data-wstatus]').forEach(btn => {
  btn.addEventListener('click', () => {
    state.wStatus = btn.dataset.wstatus;
    state.wPage = 1;
    document.querySelectorAll('[data-wstatus]').forEach(b => b.classList.toggle('active', b === btn));
    loadWithdrawals();
  });
});

document.getElementById('wPrev').addEventListener('click', () => { if (state.wPage > 1) { state.wPage--; loadWithdrawals(); } });
document.getElementById('wNext').addEventListener('click', () => { state.wPage++; loadWithdrawals(); });

let wSearchTimer;
document.getElementById('wSearch').addEventListener('input', (e) => {
  clearTimeout(wSearchTimer);
  wSearchTimer = setTimeout(() => { state.wSearch = e.target.value; state.wPage = 1; loadWithdrawals(); }, 400);
});

// ── load revenue ──────────────────────────────────────
async function loadRevenue() {
  try {
    let url = `/admin/revenue?period=${state.period}&order_page=${state.orderPage}`;
    if (state.from) url += `&from=${state.from}`;
    if (state.to) url += `&to=${state.to}`;

    const res = await callApi(url);
    if (!res.ok) throw new Error();
    const d = res.data.data;

    // wage config banner
    document.getElementById('cfgTax').textContent = pct(d.wage_config.tax_rate);
    document.getElementById('cfgFee').textContent = pct(d.wage_config.fee_rate);
    document.getElementById('cfgNet').textContent = pct(d.wage_config.net_rate);

    // KPIs
    document.getElementById('kpiGop').textContent   = fmt(d.kpis.tong_doanh_thu_gop);
    document.getElementById('kpiGop').title         = fmt(d.kpis.tong_doanh_thu_gop);
    document.getElementById('kpiGopText').textContent = formatReadableText(d.kpis.tong_doanh_thu_gop);

    document.getElementById('kpiThue').textContent  = fmt(d.kpis.tong_thue);
    document.getElementById('kpiThue').title        = fmt(d.kpis.tong_thue);
    document.getElementById('kpiThueText').textContent = formatReadableText(d.kpis.tong_thue);

    document.getElementById('kpiPhi').textContent   = fmt(d.kpis.tong_phi_nen_tang);
    document.getElementById('kpiPhi').title         = fmt(d.kpis.tong_phi_nen_tang);
    document.getElementById('kpiPhiText').textContent = formatReadableText(d.kpis.tong_phi_nen_tang);

    document.getElementById('kpiLuong').textContent = fmt(d.kpis.tong_luong_tho);
    document.getElementById('kpiLuong').title       = fmt(d.kpis.tong_luong_tho);
    document.getElementById('kpiLuongText').textContent = formatReadableText(d.kpis.tong_luong_tho);

    document.getElementById('kpiRut').textContent   = fmt(d.kpis.tong_da_rut);
    document.getElementById('kpiRut').title         = fmt(d.kpis.tong_da_rut);
    document.getElementById('kpiRutText').textContent = formatReadableText(d.kpis.tong_da_rut);

    document.getElementById('kpiTho').textContent   = d.kpis.so_tho_hoat_dong + ' thợ';
    document.getElementById('kpiTho').title         = d.kpis.so_tho_hoat_dong + ' thợ';

    // chart
    renderChart(d.chart);

    // top workers
    const topEl = document.getElementById('topWorkerList');
    if (!d.top_workers.length) {
      topEl.innerHTML = '<p class="text-slate-400 text-sm text-center py-4">Chưa có dữ liệu</p>';
    } else {
      topEl.innerHTML = d.top_workers.map((w, i) => `
        <div style="display:flex;align-items:center;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f2f4f6">
          <div style="width:28px;height:28px;border-radius:999px;background:#0058be;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem;flex-shrink:0">${i + 1}</div>
          <div style="flex:1;min-width:0">
            <p style="font-weight:700;font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(w.name)}</p>
            <p style="font-size:.75rem;color:#727785">${w.so_don} đơn · Thực nhận <span style="color:#059669;font-weight:700">${fmt(w.luong_thuc)}</span></p>
          </div>
          <div style="font-weight:800;color:#0058be;font-size:.85rem;white-space:nowrap">${fmt(w.tong_gop)}</div>
        </div>
      `).join('');
    }

    // salary table
    const tb = document.getElementById('salaryTable');
    if (!d.salary_table.length) {
      tb.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:#727785">Chưa có dữ liệu</td></tr>';
    } else {
      tb.innerHTML = d.salary_table.map(w => `
        <tr>
          <td><strong>${esc(w.name)}</strong><br><small style="color:#727785">${esc(w.phone || '')}</small></td>
          <td style="text-align:center">${w.so_don}</td>
          <td style="color:#0058be;font-weight:700">${fmt(w.tong_gop)}</td>
          <td style="color:#ef4444">${fmt(w.thue)}</td>
          <td style="color:#f97316">${fmt(w.phi_nen_tang)}</td>
          <td style="color:#059669;font-weight:800">${fmt(w.luong_thuc)}</td>
          <td>${fmt(w.so_du_vi)}</td>
          <td>${fmt(w.da_rut)}</td>
          <td>${w.co_pending ? '<span class="badge badge-pending">Chờ rút tiền</span>' : '<span class="badge badge-success">Bình thường</span>'}</td>
        </tr>
      `).join('');
    }

    // orders
    renderOrders(d.orders);

  } catch (err) {
    showToast('Lỗi tải dữ liệu doanh thu', 'error');
  }
}

function renderOrders(ordersData) {
    const tb = document.getElementById('ordersTable');
    if (!ordersData || !ordersData.data || ordersData.data.length === 0) {
        tb.innerHTML = `<tr><td colspan="8" class="tw-text-center tw-py-8 tw-text-slate-400">Không có đơn đặt lịch nào</td></tr>`;
        document.getElementById('ordersPagination').innerHTML = '';
        return;
    }
    
    tb.innerHTML = ordersData.data.map(o => `
        <tr class="hover:tw-bg-slate-50 tw-transition-colors">
            <td class="tw-font-mono tw-text-blue-600 tw-font-semibold">#${o.id}</td>
            <td class="tw-text-slate-500">${o.ngay}</td>
            <td>${o.tho_name} <span class="tw-text-xs tw-text-slate-400">(${o.tho_id})</span></td>
            <td>${o.khach_hang || '—'}</td>
            <td class="tw-text-right tw-font-bold tw-text-slate-700">${fmt(o.tong_gop)}</td>
            <td class="tw-text-right tw-text-red-500">${fmt(o.thue)}</td>
            <td class="tw-text-right tw-text-orange-500">${fmt(o.phi)}</td>
            <td class="tw-text-right tw-font-bold tw-text-green-600">${fmt(o.luong)}</td>
        </tr>
    `).join('');
    
    // pagination
    let html = '';
    const currentPage = ordersData.current_page;
    const lastPage = ordersData.last_page;
    
    if (lastPage > 1) {
        if (currentPage > 1) {
            html += `<button class="tw-px-3 tw-py-1 tw-border tw-border-slate-200 tw-rounded hover:tw-bg-slate-50 tw-text-sm" onclick="changeOrderPage(${currentPage - 1})">Trước</button>`;
        }
        
        let start = Math.max(1, currentPage - 2);
        let end = Math.min(lastPage, currentPage + 2);
        for (let i = start; i <= end; i++) {
            if (i === currentPage) {
                html += `<button class="tw-px-3 tw-py-1 tw-bg-blue-600 tw-text-white tw-rounded tw-font-bold tw-text-sm">${i}</button>`;
            } else {
                html += `<button class="tw-px-3 tw-py-1 tw-border tw-border-slate-200 tw-rounded hover:tw-bg-slate-50 tw-text-sm" onclick="changeOrderPage(${i})">${i}</button>`;
            }
        }
        
        if (currentPage < lastPage) {
            html += `<button class="tw-px-3 tw-py-1 tw-border tw-border-slate-200 tw-rounded hover:tw-bg-slate-50 tw-text-sm" onclick="changeOrderPage(${currentPage + 1})">Sau</button>`;
        }
    }
    document.getElementById('ordersPagination').innerHTML = html;
}

window.changeOrderPage = (page) => {
    state.orderPage = page;
    loadRevenue();
};

// ── load withdrawals ──────────────────────────────────────
async function loadWithdrawals() {
  try {
    const params = new URLSearchParams({ status: state.wStatus, page: state.wPage, per_page: 20, search: state.wSearch });
    const res = await callApi(`/admin/revenue/withdrawals?${params}`);
    if (!res.ok) throw new Error();
    const d = res.data.data;

    // summary counts
    document.getElementById('cntPending').textContent = d.summary.dang_xu_ly.cnt;
    document.getElementById('cntSuccess').textContent = d.summary.thanh_cong.cnt;

    // table
    const tb = document.getElementById('wTable');
    if (!d.records.length) {
      tb.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#727785">Không có dữ liệu</td></tr>';
    } else {
      tb.innerHTML = d.records.map(r => `
        <tr>
          <td style="color:#727785;font-size:.75rem">#${r.id}</td>
          <td><strong>${esc(r.ten_tho)}</strong></td>
          <td>${esc(r.sdt || '—')}</td>
          <td style="font-weight:700;color:#0f172a">${fmt(r.so_tien)}</td>
          <td>${fmt(r.so_du_vi)}</td>
          <td style="font-size:.78rem;color:#727785">${new Date(r.created_at).toLocaleString('vi-VN')}</td>
          <td>${badgeStatus(r.trang_thai)}</td>
        </tr>
      `).join('');
    }

    // pagination info
    const total = d.total;
    const from = (state.wPage - 1) * 20 + 1;
    const to = Math.min(state.wPage * 20, total);
    document.getElementById('wPagInfo').textContent = total ? `Hiển thị ${from}–${to} / ${total}` : 'Không có kết quả';
    document.getElementById('wPrev').disabled = state.wPage <= 1;
    document.getElementById('wNext').disabled = to >= total;
  } catch (e) {
    showToast('Không tải được lịch sử rút tiền', 'error');
  }
}

// ── chart ──────────────────────────────────────
function renderChart(rows) {
  const ctx = document.getElementById('revenueChart').getContext('2d');
  if (state.chart) state.chart.destroy();
  state.chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: rows.map(r => r.ngay),
      datasets: [
        { label: 'Thuế', data: rows.map(r => r.thue), backgroundColor: '#fca5a5', stack: 'a' },
        { label: 'Phí nền tảng', data: rows.map(r => r.phi), backgroundColor: '#fdba74', stack: 'a' },
        { label: 'Lương thợ', data: rows.map(r => r.luong), backgroundColor: '#6ee7b7', stack: 'a' },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: (ctx) => ` ${ctx.dataset.label}: ${Math.round(ctx.raw).toLocaleString('vi-VN')}đ`,
          },
        },
      },
      scales: { x: { stacked: true }, y: { stacked: true, ticks: { callback: v => (v/1000).toLocaleString('vi-VN') + 'k' } } },
    },
  });
}

function badgeStatus(s) {
  if (s === 'dang_xu_ly') return '<span class="badge badge-pending">Đang xử lý</span>';
  if (s === 'thanh_cong') return '<span class="badge badge-success">Thành công</span>';
  if (s === 'that_bai')   return '<span class="badge badge-fail">Thất bại</span>';
  return `<span class="badge">${s}</span>`;
}

function esc(v) { return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── init ──────────────────────────────────────
loadRevenue();
loadWithdrawals();
