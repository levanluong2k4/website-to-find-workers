export function createRouteGuideController({
  refs,
  routeWorkerMarkerImage = '',
  getAllBookings,
  openBookingDetails,
  showToast,
  escapeHtml,
  getNumeric,
  getBookingServiceNames,
  getAddress,
  getBookingDestination,
  canOpenRouteGuide,
  formatCoordinatePair,
  calculateHaversineKm,
  formatDistanceLabel,
  formatEtaLabel,
  formatLiveUpdatedAt,
  buildExternalDirectionsUrl,
  getLocationLabel,
  getFirstAddressSegment,
  estimateDriveMinutes,
  getScheduleLabel,
}) {
  const {
    previewSection,
    previewBadge,
    previewTitle,
    previewLocation,
    previewMeta,
    previewAction,
    modalEl,
    modalInstance,
    mapCanvas,
    mapFallback,
    mapFallbackTitle,
    mapFallbackText,
    refreshLocationButton,
    openExternalButton,
    serviceName,
    destinationAddress,
    destinationCoords,
    distanceValue,
    distanceHint,
    etaValue,
    etaHint,
    currentCoords,
    lastUpdated,
    trackingStatus,
    mapStatus,
    bookingCode,
  } = refs;

  const state = {
    bookingId: null,
    watchId: null,
    currentOrigin: null,
    lastRouteOrigin: null,
    lastRouteUpdateAt: 0,
    pendingRouteRequestId: 0,
    map: null,
    routeLine: null,
    originMarker: null,
    destinationMarker: null,
  };

  let initialized = false;

  const getActiveRouteBooking = () => getAllBookings().find((item) => item.id === state.bookingId) || null;

  const setRouteMapFallback = (title, text) => {
    if (mapFallbackTitle) {
      mapFallbackTitle.textContent = title;
    }
    if (mapFallbackText) {
      mapFallbackText.textContent = text;
    }
    mapFallback?.removeAttribute('hidden');
    if (mapCanvas) {
      mapCanvas.style.visibility = 'hidden';
    }
  };

  const hideRouteMapFallback = () => {
    mapFallback?.setAttribute('hidden', 'hidden');
    if (mapCanvas) {
      mapCanvas.style.visibility = 'visible';
    }
  };

  const setRouteTrackingStatus = (message, tone = 'info') => {
    if (!trackingStatus) {
      return;
    }

    trackingStatus.textContent = message;
    trackingStatus.dataset.tone = tone;
  };

  const setRouteMapStatus = (message) => {
    if (mapStatus) {
      mapStatus.textContent = message;
    }
  };

  const updateExternalDirectionsLink = (origin = null) => {
    const booking = getActiveRouteBooking();
    const destination = getBookingDestination(booking);
    if (!openExternalButton || !destination) {
      return;
    }

    openExternalButton.href = buildExternalDirectionsUrl(destination, origin);
  };

  const ensureRouteMapReady = () => {
    if (!mapCanvas) {
      return false;
    }

    if (typeof window.L === 'undefined') {
      setRouteMapFallback(
        'Không tải được thư viện bản đồ',
        'Leaflet chưa sẵn sàng nên hệ thống chưa thể hiển thị bản đồ chỉ đường trong trang.',
      );
      setRouteMapStatus('Không tải được thư viện bản đồ OpenStreetMap.');
      return false;
    }

    if (!state.map) {
      state.map = window.L.map(mapCanvas, {
        zoomControl: true,
        attributionControl: true,
      });

      window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
      }).addTo(state.map);
    }

    state.map.invalidateSize();
    return true;
  };

  const clearRouteMapLayers = () => {
    if (!state.map) {
      return;
    }

    if (state.routeLine) {
      state.map.removeLayer(state.routeLine);
      state.routeLine = null;
    }
    if (state.originMarker) {
      state.map.removeLayer(state.originMarker);
      state.originMarker = null;
    }
    if (state.destinationMarker) {
      state.map.removeLayer(state.destinationMarker);
      state.destinationMarker = null;
    }
  };

  const createRoutePinIcon = (label, tone, symbol, imageUrl = '') => window.L.divIcon({
    className: '',
    html: `
      <div class="dispatch-route-pin dispatch-route-pin--${tone}" data-label="${escapeHtml(label)}">
        ${imageUrl
          ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(label)}" class="dispatch-route-pin__image">`
          : `<span class="material-symbols-outlined">${escapeHtml(symbol)}</span>`}
      </div>
    `,
    iconSize: [34, 34],
    iconAnchor: [17, 17],
    popupAnchor: [0, -18],
  });

  const renderRouteMap = (destination, origin = null, routeLatLngs = []) => {
    if (!destination || !ensureRouteMapReady()) {
      return;
    }

    clearRouteMapLayers();

    state.destinationMarker = window.L.marker([destination.lat, destination.lng], {
      icon: createRoutePinIcon('Khách', 'customer', 'home'),
    }).addTo(state.map).bindPopup('Vị trí khách hàng');

    if (origin && Number.isFinite(origin.lat) && Number.isFinite(origin.lng)) {
      state.originMarker = window.L.marker([origin.lat, origin.lng], {
        icon: createRoutePinIcon('Thợ', 'worker', 'construction', routeWorkerMarkerImage),
      }).addTo(state.map).bindPopup('Vị trí hiện tại của thợ');
    }

    const normalizedRoute = Array.isArray(routeLatLngs) && routeLatLngs.length
      ? routeLatLngs
      : (origin ? [[origin.lat, origin.lng], [destination.lat, destination.lng]] : [[destination.lat, destination.lng]]);

    if (normalizedRoute.length >= 2) {
      state.routeLine = window.L.polyline(normalizedRoute, {
        color: '#0d7cc1',
        weight: 5,
        opacity: 0.88,
        lineCap: 'round',
        lineJoin: 'round',
      }).addTo(state.map);
    }

    const bounds = window.L.latLngBounds(normalizedRoute);
    state.map.fitBounds(bounds.pad(0.18), { animate: false });
    hideRouteMapFallback();
  };

  const fetchOsrmRoute = async (origin, destination) => {
    const url = new URL(`https://router.project-osrm.org/route/v1/driving/${origin.lng},${origin.lat};${destination.lng},${destination.lat}`);
    url.searchParams.set('overview', 'full');
    url.searchParams.set('geometries', 'geojson');
    url.searchParams.set('steps', 'false');
    url.searchParams.set('annotations', 'false');

    const response = await fetch(url.toString(), {
      headers: {
        Accept: 'application/json',
      },
    });

    const data = await response.json();
    if (!response.ok || !Array.isArray(data?.routes) || !data.routes.length) {
      throw new Error(data?.message || 'Không lấy được tuyến đường từ OSRM.');
    }

    return data.routes[0];
  };

  const shouldRefreshRouteMetrics = (origin) => {
    if (!state.lastRouteOrigin) {
      return true;
    }

    const movedKm = calculateHaversineKm(
      state.lastRouteOrigin.lat,
      state.lastRouteOrigin.lng,
      origin.lat,
      origin.lng,
    );

    return movedKm >= 0.12 || (Date.now() - state.lastRouteUpdateAt) >= 30000;
  };

  const updateRouteTravelMetrics = async (origin, force = false) => {
    const booking = getActiveRouteBooking();
    const destination = getBookingDestination(booking);
    if (!booking || !destination) {
      return;
    }

    if (!force && !shouldRefreshRouteMetrics(origin)) {
      return;
    }

    const requestId = state.pendingRouteRequestId + 1;
    state.pendingRouteRequestId = requestId;

    if (distanceHint) {
      distanceHint.textContent = 'Đang tính quãng đường theo tuyến đường thực từ OSRM...';
    }
    if (etaHint) {
      etaHint.textContent = 'Đang tính ETA theo thời lượng tuyến đường thực...';
    }

    try {
      const route = await fetchOsrmRoute(origin, destination);

      if (requestId !== state.pendingRouteRequestId) {
        return;
      }

      state.lastRouteOrigin = origin;
      state.lastRouteUpdateAt = Date.now();

      const routeLatLngs = Array.isArray(route?.geometry?.coordinates)
        ? route.geometry.coordinates
          .map((coordinate) => Array.isArray(coordinate) && coordinate.length >= 2
            ? [Number(coordinate[1]), Number(coordinate[0])]
            : null)
          .filter((coordinate) => Array.isArray(coordinate) && coordinate.every(Number.isFinite))
        : [];

      renderRouteMap(destination, origin, routeLatLngs);

      if (distanceValue) {
        distanceValue.textContent = formatDistanceLabel(Number(route.distance || 0) / 1000);
      }
      if (distanceHint) {
        distanceHint.textContent = 'Quãng đường đang hiển thị theo tuyến lái xe thực từ OSRM.';
      }
      if (etaValue) {
        etaValue.textContent = formatEtaLabel(route.duration);
      }
      if (etaHint) {
        etaHint.textContent = 'ETA dựa trên thời lượng tuyến đường từ OSRM, chưa tính giao thông thời gian thực.';
      }
      setRouteMapStatus('Bản đồ đang hiển thị tuyến đường thực bằng OpenStreetMap + OSRM.');
    } catch (error) {
      const fallbackDistanceKm = calculateHaversineKm(origin.lat, origin.lng, destination.lat, destination.lng);
      renderRouteMap(destination, origin);

      if (distanceValue) {
        distanceValue.textContent = formatDistanceLabel(fallbackDistanceKm);
      }
      if (distanceHint) {
        distanceHint.textContent = 'Không lấy được tuyến đường OSRM, đang tạm hiển thị khoảng cách GPS đường chim bay.';
      }
      if (etaValue) {
        etaValue.textContent = '--';
      }
      if (etaHint) {
        etaHint.textContent = 'Không thể cập nhật ETA lúc này.';
      }
      setRouteMapStatus('OSRM tạm thời không phản hồi. Bản đồ đang hiển thị tuyến nối thẳng để bạn vẫn định hướng được.');
    }
  };

  const handleRoutePositionUpdate = (position, force = false) => {
    const booking = getActiveRouteBooking();
    const destination = getBookingDestination(booking);
    if (!booking || !destination) {
      return;
    }

    const origin = {
      lat: position.coords.latitude,
      lng: position.coords.longitude,
    };

    state.currentOrigin = origin;

    if (currentCoords) {
      currentCoords.textContent = formatCoordinatePair(origin);
    }
    if (lastUpdated) {
      lastUpdated.textContent = `Cập nhật lúc ${formatLiveUpdatedAt(new Date())}`;
    }

    const distanceKm = calculateHaversineKm(origin.lat, origin.lng, destination.lat, destination.lng);
    if (distanceValue && !state.lastRouteUpdateAt) {
      distanceValue.textContent = formatDistanceLabel(distanceKm);
    }
    if (distanceHint && !state.lastRouteUpdateAt) {
      distanceHint.textContent = distanceKm < 0.15
        ? 'Bạn đã ở rất gần vị trí của khách hàng.'
        : 'Đang chờ dữ liệu tuyến đường OSRM. Tạm thời hiển thị khoảng cách GPS.';
    }
    if (etaValue && !state.lastRouteUpdateAt) {
      etaValue.textContent = '--';
    }
    if (etaHint && !state.lastRouteUpdateAt) {
      etaHint.textContent = 'ETA sẽ hiển thị sau khi OSRM trả kết quả.';
    }

    setRouteTrackingStatus('Đang theo dõi vị trí realtime của bạn.', 'success');
    updateExternalDirectionsLink(origin);
    updateRouteTravelMetrics(origin, force);
  };

  const handleRouteLocationError = (error) => {
    const booking = getActiveRouteBooking();
    const destination = getBookingDestination(booking);

    let message = 'Không thể lấy vị trí hiện tại.';
    if (error?.code === 1) {
      message = 'Bạn đã từ chối quyền truy cập vị trí.';
    } else if (error?.code === 2) {
      message = 'Không xác định được vị trí hiện tại.';
    } else if (error?.code === 3) {
      message = 'Hết thời gian chờ lấy vị trí. Hãy thử làm mới.';
    }

    if (currentCoords) {
      currentCoords.textContent = 'Chưa lấy được vị trí hiện tại';
    }
    if (lastUpdated) {
      lastUpdated.textContent = message;
    }
    if (distanceValue) {
      distanceValue.textContent = '--';
    }
    if (distanceHint) {
      distanceHint.textContent = 'Cần cấp quyền GPS để cập nhật quãng đường realtime.';
    }
    if (etaValue) {
      etaValue.textContent = '--';
    }
    if (etaHint) {
      etaHint.textContent = 'Cần cấp quyền GPS để tính ETA theo tuyến đường thực.';
    }

    setRouteTrackingStatus(message, 'warning');
    updateExternalDirectionsLink(null);

    if (destination) {
      setRouteMapFallback(
        'Chưa có vị trí hiện tại',
        'Trình duyệt chưa cung cấp GPS nên bản đồ trong trang chưa thể vẽ lộ trình. Bạn vẫn có thể bấm Mở bản đồ ngoài để dẫn đường.',
      );
      setRouteMapStatus('Chỉ đường trong trang cần quyền GPS hiện tại của thiết bị.');
      renderRouteMap(destination);
    }
  };

  const stop = ({ resetState = true } = {}) => {
    if (state.watchId !== null && navigator.geolocation) {
      navigator.geolocation.clearWatch(state.watchId);
    }

    state.watchId = null;
    state.currentOrigin = null;
    state.lastRouteOrigin = null;
    state.lastRouteUpdateAt = 0;
    state.pendingRouteRequestId += 1;

    if (state.map) {
      clearRouteMapLayers();
    }
    setRouteMapFallback(
      'Đang chờ vị trí hiện tại',
      'Cho phép truy cập GPS để hệ thống hiển thị bản đồ chỉ đường từ vị trí của bạn tới nhà khách hàng.',
    );
    if (distanceValue) {
      distanceValue.textContent = '--';
    }
    if (distanceHint) {
      distanceHint.textContent = 'Cho phép GPS để hệ thống tính khoảng cách còn lại.';
    }
    if (etaValue) {
      etaValue.textContent = '--';
    }
    if (etaHint) {
      etaHint.textContent = 'Thời gian đến nơi sẽ hiển thị theo dữ liệu tuyến đường thực.';
    }

    if (resetState) {
      state.bookingId = null;
    }
  };

  const requestRouteLocation = (force = false) => {
    if (!navigator.geolocation) {
      handleRouteLocationError({ code: 2 });
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => handleRoutePositionUpdate(position, force),
      handleRouteLocationError,
      {
        enableHighAccuracy: true,
        maximumAge: 0,
        timeout: 15000,
      },
    );
  };

  const refreshCurrentLocation = () => {
    if (!state.bookingId) {
      return;
    }

    setRouteTrackingStatus('Đang làm mới vị trí hiện tại...', 'info');
    requestRouteLocation(true);
  };

  const open = (id) => {
    const booking = getAllBookings().find((item) => item.id === id);

    if (!booking) {
      showToast('Không tìm thấy đơn cần chỉ đường.', 'error');
      return;
    }

    const destination = getBookingDestination(booking);
    if (!canOpenRouteGuide(booking) || !destination) {
      showToast('Đơn này chưa có đủ tọa độ khách hàng để mở đường đi.', 'error');
      return;
    }

    stop({ resetState: false });
    state.bookingId = booking.id;

    if (serviceName) {
      serviceName.textContent = getBookingServiceNames(booking);
    }
    if (destinationAddress) {
      destinationAddress.textContent = getAddress(booking);
    }
    if (destinationCoords) {
      destinationCoords.textContent = `Tọa độ đích: ${formatCoordinatePair(destination)}`;
    }
    if (distanceValue) {
      distanceValue.textContent = '--';
    }
    if (distanceHint) {
      distanceHint.textContent = 'Đang chờ GPS để tính quãng đường còn lại.';
    }
    if (etaValue) {
      etaValue.textContent = '--';
    }
    if (etaHint) {
      etaHint.textContent = 'Đang chờ dữ liệu tuyến đường thực để tính ETA.';
    }
    if (currentCoords) {
      currentCoords.textContent = 'Đang chờ vị trí hiện tại...';
    }
    if (lastUpdated) {
      lastUpdated.textContent = 'Chưa có lần cập nhật nào.';
    }
    if (bookingCode) {
      bookingCode.textContent = `#${String(booking.id).padStart(4, '0')}`;
    }

    setRouteTrackingStatus('Đang yêu cầu quyền vị trí của trình duyệt...', 'info');
    setRouteMapStatus('Bản đồ sẽ tự tải bằng OpenStreetMap + OSRM sau khi hệ thống nhận được GPS hiện tại của bạn.');
    updateExternalDirectionsLink(null);
    setRouteMapFallback(
      'Đang kết nối GPS',
      'Ngay khi có vị trí hiện tại, bản đồ sẽ hiển thị lộ trình lái xe tới khách hàng bằng OpenStreetMap + OSRM.',
    );
    renderRouteMap(destination);

    modalInstance?.show();

    if (!navigator.geolocation) {
      handleRouteLocationError({ code: 2 });
      return;
    }

    requestRouteLocation(true);
    state.watchId = navigator.geolocation.watchPosition(
      (position) => handleRoutePositionUpdate(position),
      handleRouteLocationError,
      {
        enableHighAccuracy: true,
        maximumAge: 5000,
        timeout: 15000,
      },
    );
  };

  const renderPreview = (bookings = []) => {
    if (!previewSection || !previewTitle || !previewMeta || !previewAction) {
      return;
    }

    const allBookings = getAllBookings();
    const previewBooking = bookings.find((booking) => canOpenRouteGuide(booking))
      || allBookings.find((booking) => canOpenRouteGuide(booking))
      || bookings.find((booking) => booking?.loai_dat_lich === 'at_home')
      || allBookings.find((booking) => booking?.loai_dat_lich === 'at_home')
      || bookings[0]
      || null;

    if (!previewBooking) {
      previewSection.hidden = true;
      previewAction.removeAttribute('data-booking-id');
      return;
    }

    const serviceTitle = getBookingServiceNames(previewBooking);
    const location = getAddress(previewBooking);
    const locationLabel = getFirstAddressSegment(location);
    const estimatedMinutes = estimateDriveMinutes(previewBooking);
    const distanceKm = getNumeric(previewBooking?.khoang_cach);
    const metaParts = [];

    if (distanceKm > 0) {
      metaParts.push(`${distanceKm.toFixed(1)} km`);
    }

    if (estimatedMinutes) {
      metaParts.push(`${estimatedMinutes} phút lái xe`);
    }

    if (!metaParts.length) {
      metaParts.push(getScheduleLabel(previewBooking));
    }

    previewTitle.textContent = serviceTitle;
    if (previewLocation) {
      previewLocation.textContent = locationLabel;
    }
    if (previewBadge) {
      previewBadge.textContent = getLocationLabel(previewBooking);
    }
    previewMeta.textContent = metaParts.join(' • ');
    previewAction.dataset.bookingId = String(previewBooking.id);
    previewAction.innerHTML = canOpenRouteGuide(previewBooking)
      ? `
          <span class="material-symbols-outlined">map</span>
          Xem đường đi
        `
      : `
          <span class="material-symbols-outlined">visibility</span>
          Xem chi tiết
        `;
    previewSection.hidden = false;
  };

  const handlePreviewAction = () => {
    const bookingId = getNumeric(previewAction?.dataset.bookingId);
    if (!bookingId) {
      return;
    }

    const booking = getAllBookings().find((item) => item.id === bookingId);
    if (!booking) {
      return;
    }

    if (canOpenRouteGuide(booking)) {
      open(bookingId);
      return;
    }

    openBookingDetails(bookingId);
  };

  const init = () => {
    if (initialized) {
      return;
    }

    initialized = true;

    previewAction?.addEventListener('click', handlePreviewAction);
    refreshLocationButton?.addEventListener('click', refreshCurrentLocation);
    modalEl?.addEventListener('hidden.bs.modal', () => {
      stop();
    });
    modalEl?.addEventListener('shown.bs.modal', () => {
      state.map?.invalidateSize();
    });
  };

  return {
    init,
    open,
    stop,
    renderPreview,
    refreshCurrentLocation,
  };
}
