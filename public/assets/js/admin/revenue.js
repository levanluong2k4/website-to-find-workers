import { callApi, showToast } from '../api.js';

const fmt = (n) => Math.round(n || 0).toLocaleString('vi-VN') + 'đ';
const pct = (n) => `${n}%`;

let state = { period: 'today', wStatus: 'all', wPage: 1, wSearch: '', chart: null };

// ── period buttons ──────────────────────────────────────
document.getElementById('periodBar').addEventListener('click', (e) => {
  const btn = e.target.closest('[data-period]');
  if (!btn) return;
  state.period = btn.dataset.period;
  document.querySelectorAll('.period-btn').forEach(b => b.classList.toggle('active', b === btn));
  loadRevenue();
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
    const res = await callApi(`/admin/revenue?period=${state.period}`);
    if (!res.ok) throw new Error();
    const d = res.data.data;

    // wage config banner
    document.getElementById('cfgTax').textContent = pct(d.wage_config.tax_rate);
    document.getElementById('cfgFee').textContent = pct(d.wage_config.fee_rate);
    document.getElementById('cfgNet').textContent = pct(d.wage_config.net_rate);

    // KPIs
    document.getElementById('kpiGop').textContent   = fmt(d.kpis.tong_doanh_thu_gop);
    document.getElementById('kpiThue').textContent  = fmt(d.kpis.tong_thue);
    document.getElementById('kpiPhi').textContent   = fmt(d.kpis.tong_phi_nen_tang);
    document.getElementById('kpiLuong').textContent = fmt(d.kpis.tong_luong_tho);
    document.getElementById('kpiRut').textContent   = fmt(d.kpis.tong_da_rut);
    document.getElementById('kpiTho').textContent   = d.kpis.so_tho_hoat_dong + ' thợ';

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
  } catch (e) {
    showToast('Không tải được dữ liệu doanh thu', 'error');
  }
}

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
