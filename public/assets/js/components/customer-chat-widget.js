import { callApi, showToast } from '../api.js';

const path = window.location.pathname;
const isCustomerScope = path === '/' || path.startsWith('/customer');

if (isCustomerScope) {
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
                    <div class="fw-semibold mb-1">${escapeHtml(item.service_type || 'Ca sua chua')}</div>
                    <div class="small text-secondary mb-1"><strong>Loi:</strong> ${escapeHtml(item.problem_description || '')}</div>
                    <div class="small text-secondary mb-1"><strong>Nguyen nhan:</strong> ${escapeHtml(item.cause || 'Dang cap nhat')}</div>
                    <div class="small text-secondary"><strong>Huong xu ly:</strong> ${escapeHtml(item.solution || 'Can tho kiem tra chi tiet tai hien truong.')}</div>
                    <div class="d-flex gap-2 mt-2">
                        ${item.before_image ? `<img src="${escapeHtml(item.before_image)}" alt="before" style="width:70px;height:54px;object-fit:cover;border-radius:6px;border:1px solid #cbd5e1;">` : ''}
                        ${item.after_image ? `<img src="${escapeHtml(item.after_image)}" alt="after" style="width:70px;height:54px;object-fit:cover;border-radius:6px;border:1px solid #cbd5e1;">` : ''}
                    </div>
                </div>
            `).join('');

            const techHtml = technicians.map((item) => `
                <div class="chat-card">
                    <div class="fw-semibold">${escapeHtml(item.name || 'Tho')}</div>
                    <div class="small text-secondary">Ky nang: ${escapeHtml(item.skills || 'Tong hop')}</div>
                    <div class="small text-secondary">Danh gia: ${escapeHtml(item.rating ?? '--')} · Da xong: ${escapeHtml(item.completed_jobs_count ?? 0)} viec</div>
                    ${item.reference_price ? `<div class="small text-secondary">Gia tham khao: ${escapeHtml(item.reference_price)}</div>` : ''}
                    <div class="d-flex gap-2 mt-2">
                        ${item.profile_url ? `<a href="${escapeHtml(item.profile_url)}" class="btn btn-sm btn-outline-primary">Xem ho so</a>` : ''}
                        ${item.booking_url ? `<a href="${escapeHtml(item.booking_url)}" class="btn btn-sm btn-primary">Dat lich</a>` : ''}
                    </div>
                </div>
            `).join('');

            const videoHtml = youtubeLinks.map((item) => `
                <div class="chat-card py-2">
                    <a href="${escapeHtml(item.url || '#')}" target="_blank" rel="noopener noreferrer" class="small fw-semibold text-decoration-none">
                        ${escapeHtml(item.title || 'Xem video huong dan')}
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
                if (!response.ok) throw new Error(response.data?.message || 'Khong the tai lich su chat');

                messagesContainer.innerHTML = '';
                const messages = Array.isArray(response.data?.messages) ? response.data.messages : [];

                if (messages.length === 0) {
                    appendMessage('assistant', 'Mo ta nhanh thiet bi dang gap su co. Truoc khi quan sat hoac kiem tra, ban hay ngat cau dao/aptomat cua thiet bi.');
                    return;
                }

                messages.forEach((message) => appendMessage(message.sender, message.text, message.meta || null));
            } catch (error) {
                appendMessage('assistant', 'Chua tai duoc lich su chat luc nay. Ban cu mo ta su co moi, va nho ngat nguon dien truoc khi kiem tra.');
            }
        };

        const setSendingState = (isSending) => {
            input.disabled = isSending;
            const sendBtn = document.getElementById('customerChatSend');
            if (sendBtn) {
                sendBtn.disabled = isSending;
                sendBtn.textContent = isSending ? '...' : 'Gui';
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
            typingWrap.innerHTML = '<div class="chat-bubble chat-bubble-assistant"><span class="text-secondary small">AI dang phan tich...</span></div>';
            messagesContainer.appendChild(typingWrap);
            scrollBottom();

            try {
                const response = await callApi('/chat/send', 'POST', { text });
                document.getElementById(loadingId)?.remove();

                if (!response.ok) {
                    throw new Error(response.data?.message || 'Gui tin nhan that bai');
                }

                const payload = response.data?.data || {};
                appendMessage('assistant', payload.assistant_text || 'Toi da nhan duoc yeu cau cua ban.', {
                    cases: payload.cases || [],
                    technicians: payload.technicians || [],
                    youtube_links: payload.youtube_links || [],
                });
            } catch (error) {
                document.getElementById(loadingId)?.remove();
                appendMessage('assistant', 'Toi chua xu ly duoc yeu cau ngay luc nay. Ban thu lai sau vai giay.');
                showToast(error.message || 'Khong gui duoc tin nhan', 'error');
            } finally {
                setSendingState(false);
                input.focus();
            }
        });
    }
}
