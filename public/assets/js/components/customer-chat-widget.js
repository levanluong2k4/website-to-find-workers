import { callApi, showToast } from '../api.js';

const path = window.location.pathname;
const isCustomerScope = path === '/' || path.startsWith('/customer');

if (isCustomerScope) {
    const typingIndicatorHtml = `
        <div class="chat-bubble chat-bubble-assistant">
            <div class="chat-typing">
                <img src="/assets/images/robotAI.png" alt="AI Robot" class="chat-typing-avatar">
                <div class="chat-typing-dots" aria-label="AI \u0111ang so\u1ea1n tin">
                    <span class="chat-typing-dot"></span>
                    <span class="chat-typing-dot"></span>
                    <span class="chat-typing-dot"></span>
                </div>
            </div>
        </div>
    `;

    const widget = document.getElementById('customerChatWidget');
    const panel = document.getElementById('customerChatPanel');
    const toggleButton = document.getElementById('customerChatToggle');
    const closeButton = document.getElementById('customerChatClose');
    const form = document.getElementById('customerChatForm');
    const input = document.getElementById('customerChatInput');
    const messagesContainer = document.getElementById('customerChatMessages');

    if (widget && panel && toggleButton && closeButton && form && input && messagesContainer) {
        widget.style.display = 'block';

        let isLoaded = false;

        const escapeHtml = (value) => (value ?? '')
            .toString()
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;');

        const scrollBottom = () => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        };

        const renderCards = (meta) => {
            if (!meta || typeof meta !== 'object') return '';

            const cases = Array.isArray(meta.cases) ? meta.cases : [];
            const technicians = Array.isArray(meta.technicians) ? meta.technicians : [];
            const youtubeLinks = Array.isArray(meta.youtube_links) ? meta.youtube_links : [];

            const caseHtml = cases.map((item) => `
                <div class="chat-card">
                    <div class="fw-semibold mb-1">${escapeHtml(item.service_type || 'Ca s\u1eeda ch\u1eefa')}</div>
                    <div class="small text-secondary mb-1"><strong>L\u1ed7i:</strong> ${escapeHtml(item.problem_description || '')}</div>
                    <div class="small text-secondary mb-1"><strong>Nguy\u00ean nh\u00e2n:</strong> ${escapeHtml(item.cause || '\u0110ang c\u1eadp nh\u1eadt')}</div>
                    <div class="small text-secondary"><strong>H\u01b0\u1edbng x\u1eed l\u00fd:</strong> ${escapeHtml(item.solution || 'C\u1ea7n th\u1ee3 ki\u1ec3m tra chi ti\u1ebft t\u1ea1i hi\u1ec7n tr\u01b0\u1eddng.')}</div>
                    <div class="d-flex gap-2 mt-2">
                        ${item.before_image ? `<img src="${escapeHtml(item.before_image)}" alt="before" style="width:70px;height:54px;object-fit:cover;border-radius:6px;border:1px solid #cbd5e1;">` : ''}
                        ${item.after_image ? `<img src="${escapeHtml(item.after_image)}" alt="after" style="width:70px;height:54px;object-fit:cover;border-radius:6px;border:1px solid #cbd5e1;">` : ''}
                    </div>
                </div>
            `).join('');

            const techHtml = technicians.map((item) => `
                <div class="chat-card">
                    <div class="fw-semibold">${escapeHtml(item.name || 'Th\u1ee3')}</div>
                    <div class="small text-secondary">K\u1ef9 n\u0103ng: ${escapeHtml(item.skills || 'T\u1ed5ng h\u1ee3p')}</div>
                    <div class="small text-secondary">\u0110\u00e1nh gi\u00e1: ${escapeHtml(item.rating ?? '--')} \u00b7 \u0110\u00e3 xong: ${escapeHtml(item.completed_jobs_count ?? 0)} vi\u1ec7c</div>
                    ${item.reference_price ? `<div class="small text-secondary">Gi\u00e1 tham kh\u1ea3o: ${escapeHtml(item.reference_price)}</div>` : ''}
                    <div class="d-flex gap-2 mt-2">
                        ${item.profile_url ? `<a href="${escapeHtml(item.profile_url)}" class="btn btn-sm btn-outline-primary">Xem h\u1ed3 s\u01a1</a>` : ''}
                        ${item.booking_url ? `<a href="${escapeHtml(item.booking_url)}" class="btn btn-sm btn-primary">\u0110\u1eb7t l\u1ecbch</a>` : ''}
                    </div>
                </div>
            `).join('');

            const videoHtml = youtubeLinks.map((item) => `
                <div class="chat-card py-2">
                    <a href="${escapeHtml(item.url || '#')}" target="_blank" rel="noopener noreferrer" class="small fw-semibold text-decoration-none">
                        ${escapeHtml(item.title || 'Xem video h\u01b0\u1edbng d\u1eabn')}
                    </a>
                </div>
            `).join('');

            return `${caseHtml}${techHtml}${videoHtml}`;
        };

        const appendMessage = (sender, text, meta = null) => {
            const wrapper = document.createElement('div');
            wrapper.className = `d-flex mb-2 ${sender === 'user' ? 'justify-content-end' : 'justify-content-start'}`;

            const bubble = document.createElement('div');
            bubble.className = `chat-bubble ${sender === 'user' ? 'chat-bubble-user' : 'chat-bubble-assistant'}`;
            bubble.innerHTML = `<div style="white-space:pre-line;">${escapeHtml(text)}</div>${sender === 'assistant' ? renderCards(meta) : ''}`;

            wrapper.appendChild(bubble);
            messagesContainer.appendChild(wrapper);
            scrollBottom();
        };

        const renderHistory = async () => {
            try {
                const response = await callApi('/chat/history', 'GET');
                if (!response.ok) throw new Error(response.data?.message || 'Kh\u00f4ng th\u1ec3 t\u1ea3i l\u1ecbch s\u1eed chat');

                messagesContainer.innerHTML = '';
                const messages = Array.isArray(response.data?.messages) ? response.data.messages : [];

                if (messages.length === 0) {
                    appendMessage('assistant', 'M\u00f4 t\u1ea3 nhanh thi\u1ebft b\u1ecb \u0111ang g\u1eb7p s\u1ef1 c\u1ed1. Tr\u01b0\u1edbc khi quan s\u00e1t ho\u1eb7c ki\u1ec3m tra, b\u1ea1n h\u00e3y ng\u1eaft c\u1ea7u dao/aptomat c\u1ee7a thi\u1ebft b\u1ecb.');
                    return;
                }

                messages.forEach((message) => appendMessage(message.sender, message.text, message.meta || null));
            } catch (error) {
                appendMessage('assistant', 'Ch\u01b0a t\u1ea3i \u0111\u01b0\u1ee3c l\u1ecbch s\u1eed chat l\u00fac n\u00e0y. B\u1ea1n c\u1ee9 m\u00f4 t\u1ea3 s\u1ef1 c\u1ed1 m\u1edbi, v\u00e0 nh\u1edb ng\u1eaft ngu\u1ed3n \u0111i\u1ec7n tr\u01b0\u1edbc khi ki\u1ec3m tra.');
            }
        };

        const setSendingState = (isSending) => {
            input.disabled = isSending;
            const sendBtn = document.getElementById('customerChatSend');
            if (sendBtn) {
                sendBtn.disabled = isSending;
                sendBtn.textContent = isSending ? '...' : 'G\u1eedi';
            }
        };

        toggleButton.addEventListener('click', async () => {
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
            if (panel.style.display === 'block' && !isLoaded) {
                await renderHistory();
                isLoaded = true;
            }
            scrollBottom();
        });

        closeButton.addEventListener('click', () => {
            panel.style.display = 'none';
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const text = input.value.trim();
            if (!text) return;

            appendMessage('user', text);
            input.value = '';
            setSendingState(true);

            const loadingId = `typing-${Date.now()}`;
            const typingWrap = document.createElement('div');
            typingWrap.id = loadingId;
            typingWrap.className = 'd-flex justify-content-start mb-2';
            typingWrap.innerHTML = typingIndicatorHtml;
            messagesContainer.appendChild(typingWrap);
            scrollBottom();

            try {
                const response = await callApi('/chat/send', 'POST', { text });
                document.getElementById(loadingId)?.remove();

                if (!response.ok) {
                    throw new Error(response.data?.message || 'G\u1eedi tin nh\u1eafn th\u1ea5t b\u1ea1i');
                }

                const payload = response.data?.data || {};
                appendMessage('assistant', payload.assistant_text || 'T\u00f4i \u0111\u00e3 nh\u1eadn \u0111\u01b0\u1ee3c y\u00eau c\u1ea7u c\u1ee7a b\u1ea1n.', {
                    cases: payload.cases || [],
                    technicians: payload.technicians || [],
                    youtube_links: payload.youtube_links || [],
                });
            } catch (error) {
                document.getElementById(loadingId)?.remove();
                appendMessage('assistant', 'T\u00f4i ch\u01b0a x\u1eed l\u00fd \u0111\u01b0\u1ee3c y\u00eau c\u1ea7u ngay l\u00fac n\u00e0y. B\u1ea1n th\u1eed l\u1ea1i sau v\u00e0i gi\u00e2y.');
                showToast(error.message || 'Kh\u00f4ng g\u1eedi \u0111\u01b0\u1ee3c tin nh\u1eafn', 'error');
            } finally {
                setSendingState(false);
                input.focus();
            }
        });
    }
}
