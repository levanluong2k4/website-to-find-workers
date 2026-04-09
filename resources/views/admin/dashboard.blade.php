@extends('layouts.app')

@section('title', 'Dashboard admin - Thợ Tốt')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    :root{--bg:#f4f7fb;--card:#fff;--card-2:#f7f9fd;--bd:#e7edf5;--text:#1f2735;--muted:#7e8ca3;--head:#1b2433;--blue:#1b6ce3;--blue-2:#e7f0ff;--ok:#16b364;--ok-2:#e9f9f0;--warn:#f59e0b;--warn-2:#fff5df;--danger:#ef4444;--danger-2:#fff0f0;--side:202px;--shadow:0 24px 60px rgba(153,174,202,.16);--shadow-2:0 14px 30px rgba(168,183,207,.14)}
    *{box-sizing:border-box} body{margin:0;min-height:100vh;font-family:'Inter',sans-serif;color:var(--text);background:radial-gradient(circle at top left,rgba(27,108,227,.12),transparent 32%),linear-gradient(180deg,#f7f9fc 0%,#eef3f8 100%);-webkit-font-smoothing:antialiased}
    body.app-admin-shell{background:var(--bg)!important}
    .adm-shell{display:block;min-height:calc(100vh - 80px)}
    .adm-side,.adm-top{display:none!important}
    .adm-main-shell{display:block;min-width:0}
    .adm-shell{display:grid;grid-template-columns:var(--side) minmax(0,1fr);min-height:100vh}
    .adm-side{position:sticky;top:0;height:100vh;padding:28px 18px 24px;display:flex;flex-direction:column;gap:22px;border-right:1px solid rgba(217,227,240,.9);background:linear-gradient(180deg,rgba(252,253,255,.94),rgba(245,248,252,.9));backdrop-filter:blur(24px)}
    .adm-brand{display:grid;gap:4px;padding:6px 2px 0}.adm-brand h2{margin:0;font:800 1.95rem/1 'Manrope',sans-serif;letter-spacing:-.05em;color:var(--blue)}.adm-brand span{font-size:.71rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--muted)}
    .adm-nav,.adm-side-foot{display:grid;gap:6px}.adm-grow{flex:1}
    .adm-link{display:flex;align-items:center;gap:12px;min-height:40px;padding:0 12px;border-radius:14px;text-decoration:none;color:#6c7d94;font-size:.94rem;font-weight:500;transition:.18s ease}.adm-link i{width:18px;text-align:center;font-size:.95rem}.adm-link:hover{color:var(--blue);background:rgba(27,108,227,.08);transform:translateX(2px)}.adm-link.is-active{position:relative;color:var(--blue);background:linear-gradient(180deg,#fff,rgba(247,250,255,.92));box-shadow:inset 0 0 0 1px rgba(27,108,227,.08)}.adm-link.is-active:before{content:'';position:absolute;left:-18px;top:6px;bottom:6px;width:3px;border-radius:999px;background:var(--blue)}
    .adm-badge{margin-left:auto;min-width:24px;padding:2px 8px;border-radius:999px;background:var(--danger-2);color:var(--danger);font-size:.72rem;font-weight:700;text-align:center}
    .adm-side-foot{padding-top:20px;border-top:1px solid rgba(217,227,240,.9)}
    .adm-main-shell{min-width:0;display:flex;flex-direction:column}
    .adm-top{position:sticky;top:0;z-index:10;display:grid;grid-template-columns:auto minmax(260px,1fr) auto;align-items:center;gap:18px;min-height:76px;padding:18px 28px;border-bottom:1px solid rgba(227,234,243,.92);background:rgba(255,255,255,.78);backdrop-filter:blur(26px)}
    .adm-top h3{margin:0;font:800 1.18rem/1 'Manrope',sans-serif;color:var(--head)}.adm-search{display:flex;align-items:center;gap:10px;min-height:40px;max-width:420px;padding:0 14px;border-radius:999px;color:var(--muted);font-size:.88rem;background:rgba(245,248,252,.96);border:1px solid rgba(224,231,240,.95)}
    .adm-top-right{display:flex;align-items:center;justify-content:flex-end;gap:10px}.adm-icon{width:36px;height:36px;border:0;border-radius:50%;background:transparent;color:#677890;cursor:pointer;transition:.18s ease}.adm-icon:hover{color:var(--blue);background:rgba(27,108,227,.08)}
    .adm-user{display:flex;align-items:center;gap:12px;padding-left:14px;border-left:1px solid rgba(227,234,243,.92)}.adm-user div{display:grid;gap:2px;text-align:right}.adm-user strong{font-size:.84rem;color:var(--head)}.adm-user span{font-size:.72rem;color:var(--muted)}.adm-avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.82rem;font-weight:700;background:linear-gradient(135deg,#17345e,#f18e3d);box-shadow:0 12px 24px rgba(42,68,109,.16)}
    .adm-main{padding:30px 28px 40px;display:grid;gap:28px}.adm-main.is-loading{opacity:.76;pointer-events:none}
    .adm-head{display:flex;align-items:flex-start;justify-content:space-between;gap:20px}.adm-title{display:flex;gap:24px}.adm-title i{width:4px;min-height:36px;margin-top:8px;border-radius:999px;background:var(--blue);box-shadow:0 8px 18px rgba(27,108,227,.26)}.adm-title h1{margin:0;font:800 clamp(2rem,3.4vw,3.2rem)/1 'Manrope',sans-serif;letter-spacing:-.06em;color:var(--head)}.adm-title p{margin:10px 0 0;max-width:620px;color:var(--muted);font-size:1.02rem;line-height:1.5}
    .adm-tools{display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end}.adm-tabs{display:inline-flex;align-items:center;padding:6px;border-radius:999px;background:rgba(245,248,252,.96);border:1px solid rgba(227,234,243,.92)}.adm-tabs button{border:0;min-width:76px;padding:10px 16px;border-radius:999px;background:transparent;color:#6f8098;font-size:.86rem;font-weight:700;cursor:pointer;transition:.18s ease}.adm-tabs button.is-active{color:#fff;background:linear-gradient(180deg,#2375ea,#1761d4);box-shadow:0 16px 26px rgba(27,108,227,.22)}
    .adm-btn{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 16px;border-radius:14px;border:1px solid rgba(227,234,243,.92);background:rgba(255,255,255,.9);color:var(--head);font-size:.88rem;font-weight:700;cursor:pointer;box-shadow:var(--shadow-2)}
    .adm-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
    .adm-kpi,.adm-card{border:1px solid rgba(228,235,244,.94);background:linear-gradient(180deg,rgba(255,255,255,.97),rgba(249,251,255,.96));box-shadow:var(--shadow);overflow:hidden}
    .adm-kpi{position:relative;padding:20px 20px 18px;border-radius:26px}.adm-kpi:after{content:'';position:absolute;inset:auto -30px -42px auto;width:110px;height:110px;border-radius:50%;background:radial-gradient(circle,rgba(27,108,227,.07),transparent 70%)}
    .adm-kpi-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}.adm-kpi-ic{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.96rem}.adm-kpi-1 .adm-kpi-ic{background:var(--blue-2);color:var(--blue)}.adm-kpi-2 .adm-kpi-ic{background:#eef3ff;color:#6d7fb3}.adm-kpi-3 .adm-kpi-ic{background:#fff1e6;color:#d97706}.adm-kpi-4 .adm-kpi-ic{background:#ffeaea;color:var(--danger)}
    .adm-pill{display:inline-flex;align-items:center;justify-content:center;min-height:24px;padding:0 10px;border-radius:10px;font-size:.74rem;font-weight:800;white-space:nowrap}.tone-positive{background:var(--ok-2);color:var(--ok)}.tone-warning{background:var(--warn-2);color:var(--warn)}.tone-danger{background:var(--danger-2);color:var(--danger)}.tone-muted{background:rgba(237,242,248,.92);color:#8090a6}
    .adm-kpi small{display:block;margin:0 0 8px;font-size:.76rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#7d8ca2}.adm-kpi strong{display:block;margin:0;font:800 clamp(1.75rem,2vw,2.25rem)/1 'Manrope',sans-serif;letter-spacing:-.05em;color:var(--head)}
    .adm-board{display:grid;grid-template-columns:minmax(0,1.72fr) minmax(270px,.78fr);gap:22px;align-items:start}.adm-stack{display:grid;gap:22px}.adm-card{border-radius:30px}.adm-card-h{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:24px 26px 0}.adm-card-h h2{margin:0;font:800 1.12rem/1.1 'Manrope',sans-serif;letter-spacing:-.04em;color:var(--head)}.adm-card-h p,.adm-meta{margin:8px 0 0;font-size:.85rem;color:#93a0b4}.adm-meta{white-space:nowrap}
    .adm-focus{display:grid;grid-template-columns:minmax(240px,.9fr) minmax(0,1.1fr);gap:18px;padding:24px 26px 26px}.adm-metric{padding:20px;border-radius:26px;background:linear-gradient(180deg,#fff,#fbfdff);border:1px solid rgba(230,237,245,.98)}.adm-metric p{margin:0;font-size:.9rem;color:#7f90a7}.adm-metric strong{display:block;margin:10px 0 6px;font:800 3rem/1 'Manrope',sans-serif;letter-spacing:-.06em;color:var(--blue)}.adm-metric span{font-size:.82rem;color:#8fa0b6}
    .adm-chip-row,.adm-priority,.adm-queue,.adm-signals,.adm-feedback,.adm-complaints,.adm-mini{display:grid;gap:12px}.adm-chip-row{grid-template-columns:repeat(3,minmax(0,1fr));margin-top:18px}.adm-chip{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:28px;padding:0 10px;border-radius:999px;font-size:.74rem;font-weight:800}.adm-chip-blue{background:var(--blue-2);color:var(--blue)}.adm-chip-rose{background:var(--danger-2);color:var(--danger)}
    .adm-tt{font-size:.76rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#97a6bb}.adm-priority-item,.adm-queue-item,.adm-complaint-item{padding:14px 16px;border-radius:16px;border:1px solid rgba(228,235,244,.94);background:#fff}.adm-priority-item{display:flex;align-items:flex-start;gap:12px}.adm-priority-item h4,.adm-queue-item h4,.adm-feedback-item h4{margin:0;font-size:.86rem;font-weight:700;color:var(--head)}.adm-priority-item p,.adm-queue-item p,.adm-feedback-item p,.adm-complaint-item p{margin:5px 0 0;font-size:.76rem;line-height:1.45;color:#8b9bb1}.adm-priority-item--warning i{color:#e37b21}.adm-priority-item--danger i{color:var(--danger)}.adm-priority-item--info i{color:var(--blue)}
    .adm-rev{padding:18px 26px 24px;display:grid;gap:18px}.adm-rev-top{display:flex;align-items:flex-start;justify-content:space-between;gap:18px}.adm-rev-num{text-align:right}.adm-rev-num strong{display:block;font:800 clamp(2rem,2.2vw,2.8rem)/1 'Manrope',sans-serif;letter-spacing:-.06em;color:var(--blue)}.adm-rev-num span{display:inline-flex;min-height:24px;margin-top:6px;font-size:.83rem;font-weight:700}
    .adm-chart{display:grid;gap:8px}.adm-chart svg{display:block;width:100%;height:268px}.adm-chart-labels{display:grid;gap:6px;padding:0 8px;font-size:.74rem;color:#95a3b6}.adm-chart-labels span{min-width:0;text-align:center}.adm-chart-labels span.is-ghost{color:transparent;user-select:none}
    .adm-rev-foot{display:grid;grid-template-columns:minmax(0,1fr) 216px;gap:20px;align-items:end}.adm-mini > div{display:grid;gap:6px}.adm-mini strong{font-size:.95rem;color:var(--head)}.adm-line{height:5px;border-radius:999px;overflow:hidden;background:rgba(227,234,243,.92)}.adm-line span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--blue),#2d87ff)}
    .adm-donut-card{display:grid;gap:12px;justify-items:center;padding:16px 18px;border-radius:26px;border:1px solid rgba(228,235,244,.94);background:linear-gradient(180deg,rgba(250,252,255,.96),rgba(255,255,255,.98))}.adm-donut{--p:0deg;width:92px;height:92px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(var(--blue) 0deg var(--p),rgba(225,232,242,.96) var(--p) 360deg)}.adm-donut b{width:66px;height:66px;border-radius:50%;display:grid;place-items:center;background:#fff;font:800 1.4rem/1 'Manrope',sans-serif;color:var(--head)}.adm-donut-card small{display:grid;gap:6px;justify-items:start;font-size:.82rem;color:#6f8098}
    .adm-side-card{padding-bottom:18px}.adm-side-grid,.adm-split{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:18px 20px 0}.adm-s{min-height:72px;padding:14px;border-radius:18px;background:rgba(247,249,253,.95);text-align:center}.adm-s-primary{background:var(--blue-2);color:var(--blue)}.adm-s-warn{background:var(--warn-2);color:var(--warn)}.adm-s-ok{background:var(--ok-2);color:var(--ok)}.adm-s strong,.adm-split strong{display:block;font:800 1.6rem/1 'Manrope',sans-serif;letter-spacing:-.05em;color:var(--head)}.adm-s-primary strong,.adm-s-warn strong,.adm-s-ok strong{color:inherit}.adm-s span,.adm-split span{display:block;margin-top:5px;font-size:.72rem;font-weight:800;color:#95a4b8;text-transform:uppercase}
    .adm-side-label{padding:18px 20px 10px}.adm-queue,.adm-signals,.adm-feedback{padding:0 20px}.adm-queue-item{border-left:3px solid var(--blue);background:rgba(247,249,253,.95)}.adm-queue-item--warning{border-left-color:var(--warn)}.adm-queue-item--danger{border-left-color:var(--danger)}.adm-queue-item--info{border-left-color:var(--blue)}
    .adm-workers{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:8px 20px 0}.adm-avatars{display:flex;align-items:center}.adm-avatars span{width:32px;height:32px;margin-left:-8px;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#274978,#132440);color:#fff;font-size:.7rem;font-weight:700;box-shadow:0 10px 18px rgba(31,48,82,.14)}.adm-avatars span:first-child{margin-left:0}.adm-avatars .light{background:rgba(232,237,246,.98);color:#73839a}.adm-workers strong{display:block;font:800 1.32rem/1 'Manrope',sans-serif;letter-spacing:-.04em;color:var(--head)}.adm-workers small{display:block;margin-top:4px;font-size:.79rem;font-weight:700;color:var(--ok)}
    .adm-signals div{display:flex;align-items:flex-start;gap:10px;font-size:.84rem;color:var(--head)}.adm-signals div:before{content:'';width:8px;height:8px;margin-top:6px;border-radius:50%;flex:0 0 auto;background:var(--ok);box-shadow:0 0 0 4px rgba(22,179,100,.12)}
    .adm-table-wrap{padding:18px 0 0;overflow-x:auto}.adm-table{width:100%;border-collapse:collapse}.adm-table th{padding:14px 18px;background:rgba(247,249,253,.96);border-bottom:1px solid rgba(228,235,244,.94);color:#7788a0;font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;text-align:left}.adm-table td{padding:16px 18px;border-bottom:1px solid rgba(238,242,248,.96);font-size:.86rem;color:var(--head);vertical-align:top}.adm-table tr:last-child td{border-bottom:0}.adm-code{display:block;font-weight:800;color:#2c3750}.adm-note{display:block;margin-top:4px;color:#97a6bb;font-size:.76rem}.adm-money{font:800 .94rem/1 'Manrope',sans-serif}.adm-tag{display:inline-flex;align-items:center;min-height:24px;padding:0 10px;border-radius:999px;font-size:.72rem;font-weight:800;background:var(--ok-2);color:var(--ok)}.adm-empty{padding:34px 18px;text-align:center;color:#96a4b8}
    .adm-complaints{padding:12px 20px 0}.adm-complaint-item{display:flex;align-items:center;justify-content:space-between;gap:12px}.adm-complaint-item--danger{background:linear-gradient(180deg,rgba(255,247,247,.98),rgba(255,251,251,.96))}.adm-complaint-item--warning{background:linear-gradient(180deg,rgba(255,250,241,.98),rgba(255,253,247,.96))}.adm-complaint-item strong{font-size:.82rem;color:var(--head)}.adm-complaint-item span{font:800 1rem/1 'Manrope',sans-serif;white-space:nowrap}
    .adm-feedback-item{display:flex;align-items:flex-start;gap:12px}.adm-feedback-item i{width:28px;height:28px;border-radius:50%;flex:0 0 auto;background:linear-gradient(180deg,#dfe7f3,#eef3f9)}
    .adm-fab{position:fixed;right:28px;bottom:28px;width:48px;height:48px;border:0;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#2b83ff,#1968dd);color:#fff;box-shadow:0 20px 30px rgba(27,108,227,.28);cursor:pointer}
    .adm-shell{display:block!important;min-height:calc(100vh - 80px)!important}
    .adm-side,.adm-top{display:none!important}
    .adm-main-shell{display:block!important;min-width:0}
    .adm-map-card{overflow:hidden}
    .adm-map-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:18px 26px 0}
    .adm-map-summary__item{padding:14px 16px;border-radius:20px;border:1px solid rgba(228,235,244,.94);background:linear-gradient(180deg,rgba(255,255,255,.98),rgba(247,250,255,.95));box-shadow:0 12px 30px rgba(153,174,202,.1)}
    .adm-map-summary__item span{display:block;font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#90a0b4}
    .adm-map-summary__item strong{display:block;margin-top:8px;font:800 1.8rem/1 'Manrope',sans-serif;letter-spacing:-.05em;color:var(--head)}
    .adm-map-stage{position:relative;padding:18px 26px 26px;min-height:34rem}
    .adm-map-canvas{width:100%;height:34rem;border-radius:28px;overflow:hidden;background:linear-gradient(135deg,#dbeafe 0%,#edf7ff 48%,#ffffff 100%);box-shadow:inset 0 0 0 1px rgba(224,231,240,.9)}
    .adm-map-canvas .leaflet-control-attribution,.adm-map-canvas .leaflet-control-zoom{display:none}
    .adm-map-status{position:absolute;top:36px;left:42px;z-index:450;display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:0 14px;border-radius:999px;background:rgba(15,23,42,.78);backdrop-filter:blur(14px);color:#f8fafc;font-size:.74rem;font-weight:800;letter-spacing:.04em;box-shadow:0 18px 40px rgba(15,23,42,.18)}
    .adm-map-status:before{content:'';width:8px;height:8px;border-radius:50%;background:#34d399;box-shadow:0 0 0 6px rgba(52,211,153,.16)}
    .adm-map-legend{position:absolute;top:36px;right:42px;z-index:450;display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
    .adm-map-legend__chip{display:inline-flex;align-items:center;gap:8px;min-height:38px;padding:0 14px;border-radius:999px;background:rgba(255,255,255,.88);backdrop-filter:blur(14px);color:var(--head);font-size:.72rem;font-weight:800;box-shadow:0 12px 24px rgba(15,23,42,.08)}
    .adm-map-legend__dot{width:10px;height:10px;border-radius:50%;flex:0 0 auto}
    .adm-map-legend__dot--busy{background:#f97316}
    .adm-map-legend__dot--scheduled{background:#2563eb}
    .adm-map-legend__dot--free{background:#16a34a}
    .adm-map-legend__dot--offline{background:#94a3b8}
    .adm-map-empty{position:absolute;inset:92px 42px 42px;z-index:420;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;border-radius:24px;border:1px dashed rgba(148,163,184,.38);background:rgba(255,255,255,.84);color:#516174;text-align:center;padding:2rem}
    .adm-map-empty i{font-size:2rem;color:var(--blue)}
    .adm-map-empty strong{font-size:1rem;color:var(--head)}
    .adm-map-empty p{max-width:360px;margin:0;font-size:.86rem;line-height:1.5}
    .adm-map-empty.is-hidden{display:none}
    .adm-map-info{position:absolute;left:42px;bottom:42px;z-index:450;width:min(430px,calc(100% - 84px));padding:18px 18px 16px;border-radius:24px;background:rgba(255,255,255,.94);backdrop-filter:blur(18px);box-shadow:0 28px 60px rgba(15,23,42,.14);border:1px solid rgba(226,232,240,.95)}
    .adm-map-info__top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
    .adm-map-info__eyebrow{display:block;font-size:.7rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#91a0b4}
    .adm-map-info__top h3{margin:8px 0 0;font:800 1.08rem/1.2 'Manrope',sans-serif;color:var(--head)}
    .adm-map-info__status{display:inline-flex;align-items:center;justify-content:center;min-height:28px;padding:0 10px;border-radius:999px;font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;white-space:nowrap}
    .adm-map-info__status--busy{background:rgba(249,115,22,.12);color:#c2410c}
    .adm-map-info__status--scheduled{background:rgba(37,99,235,.12);color:#1d4ed8}
    .adm-map-info__status--free{background:rgba(22,163,74,.12);color:#15803d}
    .adm-map-info__status--offline{background:rgba(148,163,184,.18);color:#475569}
    .adm-map-info__detail{margin:12px 0 0;font-size:.9rem;line-height:1.55;color:#526173}
    .adm-map-info__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:14px}
    .adm-map-info__line{display:flex;align-items:flex-start;gap:10px;min-width:0;padding:11px 12px;border-radius:16px;background:rgba(247,249,253,.95);color:#304254;font-size:.82rem;line-height:1.45}
    .adm-map-info__line i{margin-top:2px;color:var(--blue);flex:0 0 auto}
    .adm-map-info__line span{min-width:0}
    .adm-map-tooltip{padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important}
    .adm-map-tooltip .leaflet-tooltip-content{margin:0}
    .adm-map-tooltip__bubble{display:inline-flex;align-items:center;gap:8px;padding:8px 10px;border-radius:14px;background:rgba(15,23,42,.88);color:#fff;font-size:.76rem;font-weight:700;box-shadow:0 18px 34px rgba(15,23,42,.2)}
    .adm-map-tooltip__dot{width:8px;height:8px;border-radius:50%;flex:0 0 auto}
    .adm-map-tooltip__dot--busy{background:#fb923c}
    .adm-map-tooltip__dot--scheduled{background:#60a5fa}
    .adm-map-tooltip__dot--free{background:#4ade80}
    .adm-map-tooltip__dot--offline{background:#cbd5e1}
    .adm-worker-marker{--marker-color:#2563eb;position:relative;width:54px;height:68px}
    .adm-worker-marker__avatar{position:absolute;left:50%;top:0;transform:translateX(-50%);width:50px;height:50px;padding:3px;border-radius:50%;background:var(--marker-color);box-shadow:0 18px 30px rgba(15,23,42,.22)}
    .adm-worker-marker__avatar:after{content:'';position:absolute;left:50%;bottom:-11px;width:16px;height:16px;transform:translateX(-50%) rotate(45deg);border-radius:4px;background:var(--marker-color)}
    .adm-worker-marker__avatar img{display:block;width:100%;height:100%;border-radius:50%;border:2px solid rgba(255,255,255,.95);object-fit:cover;background:#fff}
    .adm-worker-marker__dot{position:absolute;right:-1px;bottom:-1px;width:15px;height:15px;border-radius:50%;border:2px solid #fff;background:var(--marker-color);box-shadow:0 10px 16px rgba(15,23,42,.14)}
    .adm-worker-marker--busy{--marker-color:#f97316}
    .adm-worker-marker--scheduled{--marker-color:#2563eb}
    .adm-worker-marker--free{--marker-color:#16a34a}
    .adm-worker-marker--offline{--marker-color:#94a3b8}
    @media (max-width:1240px){.adm-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.adm-board{grid-template-columns:1fr}}
    @media (max-width:980px){.adm-head{flex-direction:column}.adm-tools{justify-content:flex-start}.adm-focus,.adm-rev-foot{grid-template-columns:1fr}.adm-main{padding:24px 20px 32px}}
    @media (max-width:640px){.adm-main{padding:18px 16px 28px}.adm-kpis,.adm-side-grid,.adm-split{grid-template-columns:1fr}.adm-tabs{width:100%;justify-content:space-between}.adm-tabs button{flex:1;min-width:0}.adm-title{gap:16px}}
    @media (max-width:980px){.adm-map-summary,.adm-map-info__grid{grid-template-columns:1fr}.adm-map-stage{min-height:38rem}.adm-map-canvas{height:38rem}.adm-map-status{left:34px;right:34px;justify-content:center}.adm-map-legend{top:84px;right:34px;left:34px;justify-content:flex-start}.adm-map-empty{inset:142px 34px 34px}.adm-map-info{left:34px;right:34px;bottom:34px;width:auto}}
    @media (max-width:640px){.adm-map-summary{grid-template-columns:1fr}.adm-map-stage{padding:16px;min-height:40rem}.adm-map-canvas{height:40rem;border-radius:24px}.adm-map-status{top:30px;left:30px;right:30px}.adm-map-legend{top:82px;left:30px;right:30px}.adm-map-empty{inset:144px 30px 30px}.adm-map-info{left:30px;right:30px;bottom:30px}.adm-map-info__top{flex-direction:column;align-items:flex-start}}
</style>
@endpush

@section('content')
<app-navbar></app-navbar>
<div class="adm-shell">
    <aside class="adm-side">
        <div class="adm-brand"><h2>Lumina Core</h2><span>Admin Console</span></div>
        <nav class="adm-nav">
            <a href="{{ route('admin.dashboard') }}" class="adm-link is-active"><i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span></a>
            <a href="/admin/bookings" class="adm-link"><i class="fa-regular fa-calendar-check"></i><span>Bookings</span><span class="adm-badge" id="sidebarPendingCount">0</span></a>
            <a href="/admin/users" class="adm-link"><i class="fa-solid fa-users-gear"></i><span>Technicians</span></a>
            <a href="/admin/customer-feedback" class="adm-link"><i class="fa-regular fa-circle-question"></i><span>Complaints</span><span class="adm-badge" id="sidebarComplaintCount">0</span></a>
            <a href="/admin/dispatch" class="adm-link"><i class="fa-solid fa-route"></i><span>Dispatch</span></a>
        </nav>
        <div class="adm-grow"></div>
        <div class="adm-side-foot">
            <a href="/admin/travel-fee-config" class="adm-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
            <a href="#" class="adm-link" id="admLogout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </div>
    </aside>

    <div class="adm-main-shell">
        <header class="adm-top">
            <h3>Admin Dashboard</h3>
            <div class="adm-search"><i class="fa-solid fa-magnifying-glass"></i><span>Tìm kiếm nhanh...</span></div>
            <div class="adm-top-right">
                <button class="adm-icon" type="button" title="Thông báo"><i class="fa-regular fa-bell"></i></button>
                <button class="adm-icon" type="button" title="Trợ giúp"><i class="fa-regular fa-circle-question"></i></button>
                <div class="adm-user"><div><strong>Admin User</strong><span>Super Admin</span></div><div class="adm-avatar">AD</div></div>
            </div>
        </header>

        <main class="adm-main" id="adminDashboard">
            <section class="adm-head">
                <div class="adm-title"><i></i><div><h1 class="adm-page-title">Bảng điều hành admin</h1><p>Theo dõi hoạt động vận hành hệ thống Lumina theo thời gian thực.</p></div></div>
                <div class="adm-tools">
                    <div class="adm-tabs" role="group" aria-label="Chọn khoảng thời gian">
                        <button type="button" class="js-period-btn" data-period="day">Ngay</button>
                        <button type="button" class="js-period-btn is-active" data-period="month">Thang</button>
                        <button type="button" class="js-period-btn" data-period="year">Nam</button>
                    </div>
                    <button type="button" class="adm-btn" id="btnRefresh"><i class="fa-solid fa-rotate"></i>Đồng bộ</button>
                </div>
            </section>

            <section class="adm-kpis">
                <article class="adm-kpi adm-kpi-1"><div class="adm-kpi-top"><div class="adm-kpi-ic"><i class="fa-solid fa-money-bill-wave"></i></div><span class="adm-pill tone-positive" id="summaryRevenueNote">+0%</span></div><small>Doanh thu hôm nay</small><strong id="summaryRevenueToday">0 đ</strong></article>
                <article class="adm-kpi adm-kpi-2"><div class="adm-kpi-top"><div class="adm-kpi-ic"><i class="fa-regular fa-calendar"></i></div><span class="adm-pill tone-muted" id="summaryBookingsNote">Stable</span></div><small>Đơn đặt lịch hôm nay</small><strong id="summaryBookingsToday">0</strong></article>
                <article class="adm-kpi adm-kpi-3"><div class="adm-kpi-top"><div class="adm-kpi-ic"><i class="fa-solid fa-building-columns"></i></div><span class="adm-pill tone-positive" id="summaryCommissionNote">+0%</span></div><small>Hoa hồng hệ thống</small><strong id="summaryCommission">0 đ</strong></article>
                <article class="adm-kpi adm-kpi-4"><div class="adm-kpi-top"><div class="adm-kpi-ic"><i class="fa-regular fa-flag"></i></div><span class="adm-pill tone-danger" id="summaryComplaintsNote">0</span></div><small>Khiếu nại mới</small><strong id="summaryComplaints">0</strong></article>
            </section>

            <section class="adm-board">
                <div class="adm-stack">
                    <article class="adm-card adm-map-card">
                        <div class="adm-card-h">
                            <div>
                                <h2>Bản đồ theo dõi đội thợ</h2>
                                <p>Avatar thợ hiển thị trực tiếp trên bản đồ. Hover vào từng điểm để xem trạng thái đang sửa, đang có lịch hay trống lịch.</p>
                            </div>
                            <span class="adm-meta" id="workerMapMeta">Cập nhật mới nhất</span>
                        </div>
                        <div class="adm-map-summary">
                            <div class="adm-map-summary__item"><span>Thợ có GPS</span><strong id="workerMapTrackedCount">0</strong></div>
                            <div class="adm-map-summary__item"><span>Đang sửa</span><strong id="workerMapRepairingCount">0</strong></div>
                            <div class="adm-map-summary__item"><span>Đang có lịch</span><strong id="workerMapScheduledCount">0</strong></div>
                            <div class="adm-map-summary__item"><span>Trống lịch</span><strong id="workerMapAvailableCount">0</strong></div>
                        </div>
                        <div class="adm-map-stage">
                            <div id="workerTrackingMap" class="adm-map-canvas" aria-label="Bản đồ theo dõi vị trí thợ"></div>
                            <div id="workerMapStatus" class="adm-map-status">Đang tải dữ liệu vị trí đội thợ...</div>
                            <div class="adm-map-legend">
                                <span class="adm-map-legend__chip"><span class="adm-map-legend__dot adm-map-legend__dot--busy"></span>Đang sửa</span>
                                <span class="adm-map-legend__chip"><span class="adm-map-legend__dot adm-map-legend__dot--scheduled"></span>Đang có lịch</span>
                                <span class="adm-map-legend__chip"><span class="adm-map-legend__dot adm-map-legend__dot--free"></span>Trống lịch</span>
                                <span class="adm-map-legend__chip"><span class="adm-map-legend__dot adm-map-legend__dot--offline"></span>Tạm nghỉ</span>
                            </div>
                            <div id="workerMapEmptyState" class="adm-map-empty">
                                <i class="fa-solid fa-location-crosshairs"></i>
                                <strong>Chưa có dữ liệu vị trí thợ</strong>
                                <p>Bản đồ sẽ hiển thị khi thợ có tọa độ hợp lệ trong hồ sơ và đã được phê duyệt hoạt động.</p>
                            </div>
                            <div id="workerMapInfoCard" class="adm-map-info">
                                <div class="adm-map-info__top">
                                    <div>
                                        <span class="adm-map-info__eyebrow">Theo dõi lúc này</span>
                                        <h3 id="workerMapInfoName">Di chuột vào avatar thợ</h3>
                                    </div>
                                    <span id="workerMapInfoStatus" class="adm-map-info__status adm-map-info__status--free">Trống lịch</span>
                                </div>
                                <p id="workerMapInfoDetail" class="adm-map-info__detail">Hover vào avatar trên bản đồ để xem nhanh tình trạng của từng thợ.</p>
                                <div class="adm-map-info__grid">
                                    <div class="adm-map-info__line"><i class="fa-regular fa-star"></i><span id="workerMapInfoRating">Chưa có đánh giá</span></div>
                                    <div class="adm-map-info__line"><i class="fa-solid fa-screwdriver-wrench"></i><span id="workerMapInfoServices">Chưa có nhóm dịch vụ</span></div>
                                    <div class="adm-map-info__line"><i class="fa-regular fa-calendar-check"></i><span id="workerMapInfoSchedule">Chưa có lịch đang mở</span></div>
                                    <div class="adm-map-info__line"><i class="fa-solid fa-location-dot"></i><span id="workerMapInfoArea">Chưa có khu vực</span></div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="adm-card">
                        <div class="adm-card-h">
                            <div><h2>Khối doanh thu theo xu hướng</h2><p>Thống kê cho <span id="metaPeriodLabel">hôm nay</span></p></div>
                            <div class="adm-rev-num"><strong id="revenuePeriodTotal">0 đ</strong><span class="tone-positive" id="revenuePeriodNote">0%</span><div class="adm-meta" id="metaUpdatedAt">Cập nhật --:--</div></div>
                        </div>
                        <div class="adm-rev">
                            <div class="adm-chart"><svg id="revenueChart" viewBox="0 0 720 268" preserveAspectRatio="none" aria-label="Biểu đồ doanh thu"></svg><div class="adm-chart-labels" id="revenueChartLabels"></div></div>
                            <div class="adm-rev-foot">
                                <div class="adm-mini">
                                    <div><span class="adm-tt">Top services</span><strong id="revenueTopService">Chưa có dữ liệu</strong><div class="adm-line"><span style="width:42%"></span></div></div>
                                    <div><span class="adm-tt">Tỷ trọng chuyển khoản</span><strong id="revenueTransferShare">0% doanh thu</strong><div class="adm-line"><span style="width:28%"></span></div></div>
                                </div>
                                <div class="adm-donut-card">
                                    <div class="adm-donut" id="revenueTransferDonut"><b id="revenueTransferPercent">0%</b></div>
                                    <small><span><i class="fa-solid fa-circle" style="color:var(--blue)"></i> Banking</span><span><i class="fa-solid fa-circle" style="color:#b8c3d6"></i> Tiền mặt</span></small>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="adm-card">
                        <div class="adm-card-h"><h2>Bảng chi tiết doanh thu</h2><a href="/admin/bookings" class="adm-meta" style="text-decoration:none;color:var(--blue);font-weight:700">Xuất báo cáo</a></div>
                        <div class="adm-table-wrap">
                            <table class="adm-table">
                                <thead><tr><th>Mã đơn / Dịch vụ</th><th>Ngày</th><th>Tổng tiền</th><th>Tiền công</th><th>Trạng thái</th></tr></thead>
                                <tbody id="revenueTableBody"><tr><td colspan="5" class="adm-empty">Đang tải dữ liệu doanh thu...</td></tr></tbody>
                            </table>
                        </div>
                    </article>
                </div>

                <aside class="adm-stack">
                    <article class="adm-card adm-side-card">
                        <div class="adm-card-h"><h2>Khối cận xử lý</h2><span class="adm-chip adm-chip-blue">Hôm nay</span></div>
                        <div class="adm-side-grid">
                            <div class="adm-s"><strong id="bookingsTodayTotal">0</strong><span>Tổng đơn</span></div>
                            <div class="adm-s adm-s-warn"><strong id="bookingsPendingTotal">0</strong><span>Chờ xác nhận</span></div>
                            <div class="adm-s adm-s-primary"><strong id="bookingsProgressTotal">0</strong><span>Đang thực hiện</span></div>
                            <div class="adm-s adm-s-ok"><strong id="bookingsCompletedTotal">0</strong><span>Hoàn tất</span></div>
                        </div>
                        <div class="adm-side-label">Operational queue</div>
                        <div class="adm-queue" id="bookingQueueList"><div class="adm-queue-item adm-queue-item--info"><h4>Đang tải hàng đợi vận hành...</h4></div></div>
                    </article>

                    <article class="adm-card adm-side-card">
                        <div class="adm-card-h"><h2>Khối đội thợ</h2></div>
                        <div class="adm-workers"><div class="adm-avatars"><span>A</span><span>D</span><span>M</span><span class="light">+85</span></div><div><strong><span id="workersTotal">0</span> ThV</strong><small><span id="workersActive">0</span> đang online</small></div></div>
                        <div class="adm-split"><div class="adm-s"><strong id="workersPending">0</strong><span>Profile chờ duyệt</span></div><div class="adm-s"><strong id="workersLowRating">0</strong><span>Thợ bị rate thấp</span></div></div>
                        <div class="adm-side-label">Signals</div>
                        <div class="adm-signals" id="workerWatchList"><div>Đang tải tín hiệu từ đội thợ...</div></div>
                    </article>

                    <article class="adm-card adm-side-card">
                        <div class="adm-card-h"><h2>Khối khiếu nại/phản ánh</h2></div>
                        <div class="adm-complaints">
                            <div class="adm-complaint-item adm-complaint-item--danger"><div><strong>Khiếu nại chưa xử lý</strong><p>Mức độ ưu tiên: cao</p></div><span id="complaintsNew">0</span></div>
                            <div class="adm-complaint-item adm-complaint-item--warning"><div><strong>Đánh giá dưới 3 sao</strong><p>Theo dõi trong ngày</p></div><span id="complaintsLowRating">0</span></div>
                            <div class="adm-complaint-item"><div><strong>Đơn hủy có lý do</strong><p>Nguy cơ cần kiểm tra</p></div><span id="complaintsCanceled">0</span></div>
                        </div>
                        <div class="adm-side-label">Feedback mới nhất</div>
                        <div class="adm-feedback" id="complaintList"><div class="adm-feedback-item"><i></i><div><h4>Đang tải phản ánh...</h4><p>Hệ thống đang tổng hợp phản hồi từ đánh giá và đơn đặt lịch.</p></div></div></div>
                    </article>
                </aside>
            </section>
        </main>
    </div>
</div>

<button class="adm-fab" type="button" title="Tác vụ nhanh"><i class="fa-solid fa-sparkles"></i></button>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module" src="{{ asset('assets/js/admin/dashboard.js') }}"></script>
<script>
    document.addEventListener('dashboardDataLoaded', (event) => {
        const detail = event.detail || {};
        const pendingBadge = document.getElementById('sidebarPendingCount');
        const complaintBadge = document.getElementById('sidebarComplaintCount');
        if (pendingBadge && detail.pendingBookings !== undefined) pendingBadge.textContent = detail.pendingBookings;
        if (complaintBadge && detail.complaints !== undefined) complaintBadge.textContent = detail.complaints;
    });
</script>
@endpush
