@extends('layouts.app')
@section('title', 'Đánh giá khách hàng - Thợ Tốt NTU')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('assets/css/worker/reviews.css') }}"/>
@endpush

@section('content')
<div class="worker-reviews-shell">
  <x-worker-sidebar />

  <main class="worker-reviews">
    <header class="worker-reviews__header">
      <div>
        <div class="worker-reviews__eyebrow">
          <span class="material-symbols-outlined">reviews</span>
          <span>Worker Reputation Workspace</span>
        </div>
        <h1>Đánh giá khách hàng</h1>
        <p id="reviewHeaderCopy">Tổng hợp mức độ hài lòng, các phản hồi cần xem lại và những dịch vụ đang giúp bạn giữ uy tín tốt nhất.</p>
      </div>

      <div class="worker-reviews__header-actions">
        <a href="/worker/profile" class="worker-review-action worker-review-action--ghost">
          <span class="material-symbols-outlined">person</span>
          <span>Hồ sơ thợ</span>
        </a>
        <button type="button" id="reviewRefreshButton" class="worker-review-action worker-review-action--primary">
          <span class="material-symbols-outlined">refresh</span>
          <span>Làm mới đánh giá</span>
        </button>
      </div>
    </header>

    <section class="worker-review-overview">
      <article class="review-hero worker-review-surface">
        <div class="review-hero__top">
          <div>
            <span class="review-hero__label">Điểm uy tín hiện tại</span>
            <div class="review-hero__headline" id="reviewAverageScore">0.0</div>
            <div class="review-stars review-stars--light" id="reviewAverageStars" aria-label="Đánh giá trung bình">
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
              <span class="material-symbols-outlined">star</span>
            </div>
          </div>

          <div>
            <p class="review-hero__title" id="reviewHeroTitle">Chưa có đủ dữ liệu để đánh giá.</p>
            <p class="review-hero__copy" id="reviewHeroCopy">Khi khách hoàn tất đơn và để lại phản hồi, hệ thống sẽ gom lại để bạn nhìn ra chất lượng phục vụ theo thời gian.</p>
          </div>
        </div>

        <div class="review-hero__meta">
          <div class="review-hero__meta-card">
            <span>Tổng số phản hồi</span>
            <strong id="reviewTotalCount">0 đánh giá</strong>
          </div>
          <div class="review-hero__meta-card">
            <span>Tỷ lệ 5 sao</span>
            <strong id="reviewFiveStarRatio">0%</strong>
          </div>
          <div class="review-hero__meta-card">
            <span>Phản hồi 30 ngày gần đây</span>
            <strong id="reviewRecentCount">0 phản hồi</strong>
          </div>
        </div>
      </article>

      <aside class="review-breakdown worker-review-surface">
        <div class="review-panel__kicker">
          <span class="material-symbols-outlined">stacked_bar_chart</span>
          <span>Rating Breakdown</span>
        </div>
        <h2>Phân bố theo số sao</h2>
        <p>Bấm trực tiếp vào từng mức sao để lọc danh sách phản hồi phía dưới.</p>
        <div class="review-breakdown__list" id="reviewBreakdownBars"></div>
      </aside>
    </section>

    <section class="review-metric-grid">
      <article class="review-metric-card worker-review-surface">
        <div class="review-metric-card__label">
          <span class="material-symbols-outlined">forum</span>
          <span>Tổng phản hồi</span>
        </div>
        <strong id="reviewMetricTotal">0</strong>
        <p>Tổng số lượt khách đã để lại đánh giá cho những đơn hoàn thành.</p>
      </article>

      <article class="review-metric-card worker-review-surface">
        <div class="review-metric-card__label">
          <span class="material-symbols-outlined">workspace_premium</span>
          <span>5 sao</span>
        </div>
        <strong id="reviewMetricFiveStar">0</strong>
        <p>Số phản hồi ở mức hài lòng cao nhất. Đây là tín hiệu mạnh nhất về uy tín phục vụ.</p>
      </article>

      <article class="review-metric-card worker-review-surface">
        <div class="review-metric-card__label">
          <span class="material-symbols-outlined">notification_important</span>
          <span>Cần xem lại</span>
        </div>
        <strong id="reviewMetricLow">0</strong>
        <p>Các phản hồi từ 1 đến 3 sao nên được xem lại để rút kinh nghiệm cho lịch sau.</p>
      </article>

      <article class="review-metric-card worker-review-surface">
        <div class="review-metric-card__label">
          <span class="material-symbols-outlined">chat</span>
          <span>Có nhận xét chữ</span>
        </div>
        <strong id="reviewMetricCommented">0%</strong>
        <p>Tỷ lệ phản hồi có nội dung chi tiết, hữu ích hơn cho việc cải thiện thao tác và cách phục vụ.</p>
      </article>
    </section>

    <section class="review-toolbar worker-review-surface">
      <div class="review-toolbar__group" id="reviewRatingFilters">
        <button type="button" class="review-filter-chip is-active" data-rating-filter="all">Tất cả</button>
        <button type="button" class="review-filter-chip" data-rating-filter="5">5 sao</button>
        <button type="button" class="review-filter-chip" data-rating-filter="4">4 sao</button>
        <button type="button" class="review-filter-chip" data-rating-filter="3">3 sao</button>
        <button type="button" class="review-filter-chip" data-rating-filter="2">2 sao</button>
        <button type="button" class="review-filter-chip" data-rating-filter="1">1 sao</button>
      </div>

      <div class="review-toolbar__controls">
        <select id="reviewServiceFilter" class="review-select" aria-label="Lọc theo dịch vụ">
          <option value="all">Tất cả dịch vụ</option>
        </select>

        <select id="reviewSortSelect" class="review-select" aria-label="Sắp xếp đánh giá">
          <option value="latest">Mới nhất</option>
          <option value="lowest">Điểm thấp nhất</option>
          <option value="highest">Điểm cao nhất</option>
          <option value="oldest">Cũ nhất</option>
        </select>

        <button type="button" id="reviewHasCommentToggle" class="review-toggle" aria-pressed="false">
          <span class="material-symbols-outlined">article</span>
          <span>Chỉ hiện review có nhận xét</span>
        </button>
      </div>
    </section>

    <section class="review-content">
      <aside class="review-insights">
        <article class="review-insight-card worker-review-surface">
          <div class="review-panel__kicker">
            <span class="material-symbols-outlined">tips_and_updates</span>
            <span>Quick Insight</span>
          </div>
          <h2>Bức tranh nhanh</h2>
          <p id="reviewInsightLead">Hệ thống sẽ gom các tín hiệu quan trọng để bạn biết nên giữ điều gì và cần xử lý điểm nào trước.</p>
          <div class="review-insight-list" id="reviewInsightList"></div>
        </article>

        <article class="review-insight-card worker-review-surface">
          <div class="review-panel__kicker">
            <span class="material-symbols-outlined">checklist</span>
            <span>Action Queue</span>
          </div>
          <h2>Việc nên làm tiếp theo</h2>
          <p id="reviewActionLead">Mỗi gợi ý đều bám vào số liệu hiện tại thay vì chỉ hiển thị cảm tính.</p>
          <div class="review-insight-steps" id="reviewActionSteps"></div>
        </article>
      </aside>

      <article class="review-feed worker-review-surface">
        <div class="review-feed__head">
          <div>
            <h2>Tất cả phản hồi</h2>
            <p>Danh sách này đi kèm bối cảnh đơn hàng để bạn không phải đoán review đó xuất phát từ lịch nào.</p>
          </div>
          <div class="review-feed__count" id="reviewFeedCount">Đang tải...</div>
        </div>

        <div class="review-feed__list" id="reviewsListContainer">
          <div class="review-loading">
            <span class="material-symbols-outlined">hourglass_top</span>
            <div>Đang tải phản hồi từ khách hàng...</div>
          </div>
        </div>

        <div id="paginationControls" class="review-pagination d-none">
          <ul class="review-pagination__list" id="paginationList"></ul>
        </div>
      </article>
    </section>
  </main>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/worker/reviews.js') }}?v={{ time() }}"></script>
@endpush
