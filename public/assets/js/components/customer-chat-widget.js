import { callApi, showToast } from '../api.js';

const path = window.location.pathname;
const isCustomerScope = path === '/' || path.startsWith('/customer');

if (isCustomerScope) {
    const BOT_AVATAR = '/assets/images/robotAI.png';
    const USER_AVATAR = '/assets/images/customer.png';
    const SEND_ICON = `
        <svg viewBox="0 0 17 14" fill="none" aria-hidden="true">
            <path d="M0.87 13.39C0.55 13.55 0.18 13.25 0.28 12.91L1.8 7.5L0.28 2.09C0.18 1.75 0.55 1.45 0.87 1.61L15.32 6.47C15.7 6.6 15.7 7.14 15.32 7.27L0.87 12.13V13.39Z" fill="currentColor"/>
        </svg>
    `;

    const widget = document.getElementById('customerChatWidget');
    const panel = document.getElementById('customerChatPanel');
    const toggleButton = document.getElementById('customerChatToggle');
    const closeButton = document.getElementById('customerChatClose');
    const quickHotlineButton = document.getElementById('customerChatQuickHotline');
    const focusInputButton = document.getElementById('customerChatFocusInput');
    const form = document.getElementById('customerChatForm');
    const input = document.getElementById('customerChatInput');
    const sendButton = document.getElementById('customerChatSend');
    const messagesContainer = document.getElementById('customerChatMessages');

    if (
        widget
        && panel
        && toggleButton
        && closeButton
        && form
        && input
        && sendButton
        && messagesContainer
    ) {
        widget.style.display = 'flex';
        sendButton.innerHTML = SEND_ICON;

        let isLoaded = false;
        let lastSender = null;

        const escapeHtml = (value) => (value ?? '')
            .toString()
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');

        const formatMessageText = (text) => {
            const escaped = escapeHtml(text);

            return escaped.replace(/(https?:\/\/[^\s<]+)/g, (url) => `
                <a href="${url}" target="_blank" rel="noopener noreferrer" class="customer-chat-link">${url}</a>
            `);
        };

        const scrollBottom = () => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        };

        const currentTimestampLabel = () => {
            const now = new Date();
            const time = now.toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit',
            });

            return `${time}, Hôm nay`;
        };

        const appendTimestamp = () => {
            if (messagesContainer.querySelector('.customer-chat-timestamp')) {
                return;
            }

            const stamp = document.createElement('div');
            stamp.className = 'customer-chat-timestamp';
            stamp.textContent = currentTimestampLabel();
            messagesContainer.appendChild(stamp);
        };

        const clearMessages = () => {
            messagesContainer.innerHTML = '';
            lastSender = null;
            appendTimestamp();
        };

        const renderStatusBadge = (meta) => {
            const badge = meta?.ai?.badge;
            if (!badge || typeof badge !== 'object') {
                return '';
            }

            const tone = badge.tone === 'warning' ? 'warning' : 'info';

            return `
                <div class="customer-chat-status-badge" data-tone="${tone}">
                    <span>${escapeHtml(badge.label || 'Thông báo')}</span>
                    ${badge.message ? `<span>${escapeHtml(badge.message)}</span>` : ''}
                </div>
            `;
        };

        const renderCards = (meta) => {
            if (!meta || typeof meta !== 'object') {
                return '';
            }

            const cases = Array.isArray(meta.cases) ? meta.cases : [];
            const technicians = Array.isArray(meta.technicians) ? meta.technicians : [];

            if (!cases.length && !technicians.length) {
                return '';
            }

            const caseHtml = cases.map((item) => `
                <div class="chat-card">
                    <p class="chat-card-title">${escapeHtml(item.service_type || 'Ca sửa chữa')}</p>
                    <p class="chat-card-line"><strong>Lỗi:</strong> ${escapeHtml(item.problem_description || '')}</p>
                    <p class="chat-card-line"><strong>Nguyên nhân:</strong> ${escapeHtml(item.cause || 'Đang cập nhật')}</p>
                    <p class="chat-card-line"><strong>Hướng xử lý:</strong> ${escapeHtml(item.solution || 'Cần thợ kiểm tra chi tiết tại hiện trường.')}</p>
                    ${item.before_image || item.after_image ? `
                        <div class="chat-card-images">
                            ${item.before_image ? `<img src="${escapeHtml(item.before_image)}" alt="Ảnh trước sửa">` : ''}
                            ${item.after_image ? `<img src="${escapeHtml(item.after_image)}" alt="Ảnh sau sửa">` : ''}
                        </div>
                    ` : ''}
                </div>
            `).join('');

            const techHtml = technicians.map((item) => `
                <div class="chat-card">
                    <p class="chat-card-title">${escapeHtml(item.name || 'Thợ phù hợp')}</p>
                    <p class="chat-card-line"><strong>Kỹ năng:</strong> ${escapeHtml(item.skills || 'Tổng hợp')}</p>
                    <p class="chat-card-line"><strong>Đánh giá:</strong> ${escapeHtml(item.rating ?? '--')} · Đã xong: ${escapeHtml(item.completed_jobs_count ?? 0)} việc</p>
                    ${item.reference_price ? `<p class="chat-card-line"><strong>Giá tham khảo:</strong> ${escapeHtml(item.reference_price)}</p>` : ''}
                    <div class="chat-card-actions">
                        ${item.profile_url ? `<a href="${escapeHtml(item.profile_url)}" class="chat-card-button">Xem hồ sơ</a>` : ''}
                        ${item.booking_url ? `<a href="${escapeHtml(item.booking_url)}" class="chat-card-button chat-card-button-primary">Đặt lịch</a>` : ''}
                    </div>
                </div>
            `).join('');

            return `<div class="customer-chat-rich-content">${caseHtml}${techHtml}</div>`;
        };

        const appendMessage = (sender, text, meta = null) => {
            appendTimestamp();

            const isCompactUserMessage = sender === 'user' && lastSender === 'user';
            const wrapper = document.createElement('div');
            wrapper.className = `customer-chat-message customer-chat-message-${sender}${isCompactUserMessage ? ' is-compact' : ''}`;

            const stack = document.createElement('div');
            stack.className = `customer-chat-stack customer-chat-stack-${sender}`;

            if (sender === 'user') {
                const avatarSlot = document.createElement('div');
                avatarSlot.className = 'customer-chat-avatar-slot';
                avatarSlot.innerHTML = isCompactUserMessage
                    ? '<span class="customer-chat-avatar-placeholder" aria-hidden="true"></span>'
                    : `<img src="${USER_AVATAR}" alt="Khách hàng" class="customer-chat-user-avatar">`;
                wrapper.appendChild(avatarSlot);
            }

            if (sender === 'assistant') {
                const badgeHtml = renderStatusBadge(meta);
                if (badgeHtml) {
                    const badgeWrap = document.createElement('div');
                    badgeWrap.innerHTML = badgeHtml;
                    stack.appendChild(badgeWrap.firstElementChild);
                }
            }

            const bubble = document.createElement('div');
            bubble.className = `chat-bubble chat-bubble-${sender}`;
            bubble.innerHTML = `<div class="chat-bubble-text">${formatMessageText(text)}</div>`;
            stack.appendChild(bubble);

            if (sender === 'assistant') {
                const cardsHtml = renderCards(meta);
                if (cardsHtml) {
                    const cardsWrap = document.createElement('div');
                    cardsWrap.innerHTML = cardsHtml;
                    stack.appendChild(cardsWrap.firstElementChild);
                }
            }

            wrapper.appendChild(stack);
            messagesContainer.appendChild(wrapper);
            lastSender = sender;
            scrollBottom();
        };

        const createTypingIndicator = (loadingId) => {
            const wrapper = document.createElement('div');
            wrapper.id = loadingId;
            wrapper.className = 'customer-chat-message customer-chat-message-assistant';
            wrapper.innerHTML = `
                <div class="customer-chat-stack customer-chat-stack-assistant">
                    <div class="chat-bubble chat-bubble-typing" aria-label="Bot đang soạn tin">
                        <div class="chat-typing-dots">
                            <span class="chat-typing-dot"></span>
                            <span class="chat-typing-dot"></span>
                            <span class="chat-typing-dot"></span>
                        </div>
                    </div>
                </div>
            `;

            return wrapper;
        };

        const renderHistory = async () => {
            try {
                const response = await callApi('/chat/history', 'GET');
                if (!response.ok) {
                    throw new Error(response.data?.message || 'Không thể tải lịch sử chat');
                }

                clearMessages();

                const messages = Array.isArray(response.data?.messages) ? response.data.messages : [];
                if (messages.length === 0) {
                    appendMessage('assistant', 'Chào bạn, Bot NTU đang sẵn sàng. Bạn mô tả nhanh thiết bị hoặc đơn hàng đang cần hỗ trợ nhé.');
                    return;
                }

                messages.forEach((message) => {
                    appendMessage(message.sender, message.text, message.meta || null);
                });
            } catch (error) {
                clearMessages();
                appendMessage('assistant', 'Chưa tải được lịch sử chat lúc này. Bạn cứ mô tả sự cố mới, mình sẽ hỗ trợ ngay khi nhận được thông tin.');
            }
        };

        const setSendingState = (isSending) => {
            input.disabled = isSending;
            sendButton.disabled = isSending;
            sendButton.innerHTML = isSending ? '<span class="customer-chat-send-loader" aria-hidden="true"></span>' : SEND_ICON;
        };

        const setPanelOpen = (isOpen) => {
            panel.style.display = isOpen ? 'flex' : 'none';
            toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            if (isOpen) {
                scrollBottom();
                input.focus();
            }
        };

        toggleButton.addEventListener('click', async () => {
            const isOpening = panel.style.display !== 'flex';
            setPanelOpen(isOpening);

            if (isOpening && !isLoaded) {
                await renderHistory();
                isLoaded = true;
            }
        });

        closeButton.addEventListener('click', () => {
            setPanelOpen(false);
        });

        focusInputButton?.addEventListener('click', () => {
            input.focus();
        });

        quickHotlineButton?.addEventListener('click', async () => {
            if (panel.style.display !== 'flex') {
                setPanelOpen(true);
                if (!isLoaded) {
                    await renderHistory();
                    isLoaded = true;
                }
            }

            input.value = 'Hotline cửa hàng là gì?';
            input.focus();
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const text = input.value.trim();
            if (!text) {
                return;
            }

            appendMessage('user', text);
            input.value = '';
            setSendingState(true);

            const loadingId = `typing-${Date.now()}`;
            const typingNode = createTypingIndicator(loadingId);
            messagesContainer.appendChild(typingNode);
            lastSender = 'assistant';
            scrollBottom();

            try {
                const response = await callApi('/chat/send', 'POST', { text });
                document.getElementById(loadingId)?.remove();

                if (!response.ok) {
                    throw new Error(response.data?.message || 'Gửi tin nhắn thất bại');
                }

                const payload = response.data?.data || {};
                appendMessage(
                    'assistant',
                    payload.assistant_text || 'Tôi đã nhận được yêu cầu của bạn.',
                    {
                        cases: payload.cases || [],
                        technicians: payload.technicians || [],
                        ai: payload.ai || null,
                    },
                );
            } catch (error) {
                document.getElementById(loadingId)?.remove();
                appendMessage('assistant', 'Tôi chưa xử lý được yêu cầu ngay lúc này. Bạn thử lại sau vài giây.');
                showToast(error.message || 'Không gửi được tin nhắn', 'error');
            } finally {
                setSendingState(false);
                input.focus();
            }
        });
    }
}
