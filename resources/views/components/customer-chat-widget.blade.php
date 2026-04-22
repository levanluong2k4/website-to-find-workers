@php
    $chatUserAvatar = auth()->user()?->avatar;
    $chatUserAvatarUrl = '';

    if (filled($chatUserAvatar)) {
        $chatUserAvatarUrl = preg_match('/^https?:\/\//i', $chatUserAvatar) || str_starts_with($chatUserAvatar, '/')
            ? $chatUserAvatar
            : '/storage/' . ltrim($chatUserAvatar, '/');
    }
@endphp

<div id="customerChatWidget" style="display:none;" data-user-avatar="{{ $chatUserAvatarUrl }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700&display=swap');

        #customerChatWidget,
        #customerChatWidget * {
            box-sizing: border-box;
        }

        #customerChatWidget {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1200;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        #customerChatPanel {
            display: none;
            flex-direction: column;
            width: min(358px, calc(100vw - 20px));
            height: min(536px, calc(100vh - 92px));
            background: #ffffff;
            border: 1px solid rgba(194, 198, 214, 0.15);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(25, 28, 30, 0.06);
        }

        .customer-chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px 13px;
            background: #26aeeb;
            border-bottom: 1px solid #eceef0;
        }

        .customer-chat-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .customer-chat-brand-avatar {
            position: relative;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.18);
        }

        .customer-chat-brand-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .customer-chat-online-dot {
            position: absolute;
            right: 1px;
            bottom: 1px;
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #22c55e;
            border: 2px solid #ffffff;
        }

        .customer-chat-brand-copy {
            min-width: 0;
        }

        .customer-chat-title {
            margin: 0;
            color: #ffffff;
            font-family: 'Manrope', 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            line-height: 17.5px;
        }

        .customer-chat-subtitle {
            margin: 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 11px;
            font-weight: 400;
            line-height: 13.75px;
        }

        .customer-chat-header-actions {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .customer-chat-icon-button {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #ffffff;
            padding: 0;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease, opacity 0.2s ease;
        }

        .customer-chat-icon-button:hover {
            background: rgba(255, 255, 255, 0.14);
            transform: translateY(-1px);
        }

        .customer-chat-icon-button:focus-visible {
            outline: 2px solid rgba(255, 255, 255, 0.8);
            outline-offset: 2px;
        }

        .customer-chat-icon-button svg {
            width: 15px;
            height: 15px;
            display: block;
        }

        #customerChatMessages {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            background: #f7f9fb;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        #customerChatMessages::-webkit-scrollbar {
            width: 6px;
        }

        #customerChatMessages::-webkit-scrollbar-thumb {
            background: rgba(114, 119, 133, 0.28);
            border-radius: 999px;
        }

        .customer-chat-suggestions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 10px 12px 0;
            background: #ffffff;
            border-top: 1px solid #eceef0;
        }

        .customer-chat-suggestions-title {
            color: #727785;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .customer-chat-suggestions-list {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 2px;
            scrollbar-width: thin;
            scrollbar-color: rgba(114, 119, 133, 0.24) transparent;
        }

        .customer-chat-suggestions-list::-webkit-scrollbar {
            height: 5px;
        }

        .customer-chat-suggestions-list::-webkit-scrollbar-thumb {
            background: rgba(114, 119, 133, 0.24);
            border-radius: 999px;
        }

        .customer-chat-suggestion-chip {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 0 12px;
            border: 1px solid rgba(0, 88, 190, 0.14);
            border-radius: 999px;
            background: #f4f8ff;
            color: #0058be;
            font-size: 12px;
            font-weight: 600;
            line-height: 16px;
            white-space: nowrap;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .customer-chat-suggestion-chip:hover {
            background: #eaf3ff;
            border-color: rgba(0, 88, 190, 0.22);
            transform: translateY(-1px);
        }

        .customer-chat-suggestion-chip:focus-visible {
            outline: 2px solid rgba(0, 88, 190, 0.22);
            outline-offset: 2px;
        }

        .customer-chat-timestamp {
            align-self: center;
            color: #424754;
            font-size: 11px;
            font-weight: 500;
            line-height: 16.5px;
        }

        .customer-chat-message {
            display: flex;
            width: 100%;
        }

        .customer-chat-message-user {
            align-items: flex-end;
            justify-content: flex-start;
            gap: 8px;
        }

        .customer-chat-message-assistant {
            justify-content: flex-end;
        }

        .customer-chat-avatar-slot {
            width: 24px;
            flex: 0 0 24px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 4px;
        }

        .customer-chat-user-avatar,
        .customer-chat-avatar-placeholder {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: block;
            flex: 0 0 24px;
        }

        .customer-chat-user-avatar {
            object-fit: cover;
        }

        .customer-chat-avatar-placeholder {
            opacity: 0;
            visibility: hidden;
        }

        .customer-chat-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 236px;
        }

        .customer-chat-stack-user {
            align-items: flex-start;
        }

        .customer-chat-stack-assistant {
            align-items: flex-end;
        }

        .chat-bubble {
            max-width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            line-height: 20px;
            word-break: break-word;
            white-space: normal;
        }

        .chat-bubble-user {
            background: #0058be;
            color: #ffffff;
            border-radius: 16px 16px 16px 2px;
        }

        .customer-chat-message-user.is-compact .chat-bubble-user {
            border-radius: 2px 16px 16px 2px;
        }

        .chat-bubble-assistant {
            background: #e6e8ea;
            color: #191c1e;
            border-radius: 16px 16px 2px 16px;
        }

        .chat-bubble-typing {
            background: #eceef0;
            border-radius: 16px 16px 2px 16px;
            padding: 10px 16px;
        }

        .chat-bubble-text {
            white-space: pre-line;
        }

        .customer-chat-link {
            color: inherit;
            text-decoration: underline;
            word-break: break-all;
        }

        .customer-chat-rich-content {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .chat-card {
            width: 100%;
            background: #ffffff;
            border: 1px solid #e0e3e5;
            border-radius: 14px;
            padding: 12px;
            color: #191c1e;
            box-shadow: 0 8px 18px -14px rgba(25, 28, 30, 0.4);
        }

        .chat-card-title {
            margin: 0 0 6px;
            font-family: 'Manrope', 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            line-height: 17px;
            color: #191c1e;
        }

        .chat-card-line {
            margin: 0 0 4px;
            color: #424754;
            font-size: 12px;
            line-height: 17px;
        }

        .chat-card-line:last-child {
            margin-bottom: 0;
        }

        .chat-card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .chat-card-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid #d8e2ff;
            background: #ffffff;
            color: #0058be;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .chat-card-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px -14px rgba(0, 88, 190, 0.45);
        }

        .chat-card-button-primary {
            border-color: #0058be;
            background: #0058be;
            color: #ffffff;
        }

        .chat-card-images {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .chat-card-images img {
            width: 70px;
            height: 54px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #d8dde6;
        }

        .customer-chat-service-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .customer-chat-service-card {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 62px;
            padding: 8px;
            border: 1px solid #dce4f2;
            border-radius: 12px;
            background: #ffffff;
            color: #191c1e;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .customer-chat-service-card:hover {
            transform: translateY(-1px);
            border-color: #b8ccf5;
            box-shadow: 0 10px 18px -16px rgba(0, 88, 190, 0.5);
        }

        .customer-chat-service-card-media {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            border-radius: 10px;
            overflow: hidden;
            background: #eef4ff;
        }

        .customer-chat-service-card-media img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .customer-chat-service-card-name {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
            font-size: 12px;
            font-weight: 700;
            line-height: 16px;
            color: #191c1e;
        }

        .customer-chat-services-more {
            width: 100%;
            margin-top: 2px;
        }

        .customer-chat-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
        }

        .customer-chat-status-badge[data-tone="warning"] {
            border-color: #f5c27a;
            background: #fff4e5;
            color: #9a5b00;
        }

        .chat-typing-dots {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .chat-typing-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #727785;
            animation: chatTypingBounce 1.1s infinite ease-in-out;
        }

        .chat-typing-dot:nth-child(2) {
            animation-delay: 0.15s;
        }

        .chat-typing-dot:nth-child(3) {
            animation-delay: 0.3s;
        }

        @keyframes chatTypingBounce {
            0%,
            80%,
            100% {
                transform: translateY(0);
                opacity: 0.45;
            }

            40% {
                transform: translateY(-3px);
                opacity: 1;
            }
        }

        #customerChatForm {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding: 13px 12px 12px;
            background: #ffffff;
            border-top: 1px solid #eceef0;
        }

        .customer-chat-input-action {
            width: 28px;
            height: 28px;
            flex: 0 0 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #0058be;
            padding: 0;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .customer-chat-input-action:hover {
            background: rgba(0, 88, 190, 0.08);
            transform: translateY(-1px);
        }

        .customer-chat-input-action:focus-visible {
            outline: 2px solid rgba(0, 88, 190, 0.28);
            outline-offset: 2px;
        }

        .customer-chat-input-action svg {
            width: 15px;
            height: 15px;
            display: block;
        }

        .customer-chat-input-shell {
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            padding: 6px 8px 6px 12px;
            border-radius: 16px;
            background: #f2f4f6;
        }

        #customerChatInput {
            flex: 1 1 auto;
            min-width: 0;
            border: 0;
            outline: none;
            background: transparent;
            color: #191c1e;
            font-size: 14px;
            line-height: 20px;
            padding: 4px 0;
        }

        #customerChatInput::placeholder {
            color: #727785;
        }

        #customerChatSend {
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #0058be;
            padding: 0;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        #customerChatSend:hover:not(:disabled) {
            transform: translateX(1px);
        }

        #customerChatSend:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #customerChatSend svg {
            width: 16px;
            height: 14px;
            display: block;
        }

        .customer-chat-send-loader {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(0, 88, 190, 0.2);
            border-top-color: #0058be;
            border-radius: 999px;
            animation: customerChatSpin 0.8s linear infinite;
        }

        @keyframes customerChatSpin {
            to {
                transform: rotate(360deg);
            }
        }

        #customerChatToggle {
            position: relative;
            width: 58px;
            height: 58px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, #26aeeb 0%, #0058be 100%);
            color: #ffffff;
            box-shadow: 0 12px 28px rgba(0, 88, 190, 0.28);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        #customerChatToggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(0, 88, 190, 0.32);
        }

        .chat-toggle-icon {
            width: 34px;
            height: 34px;
            object-fit: cover;
            border-radius: 999px;
        }

        .customer-chat-toggle-dot {
            position: absolute;
            right: 11px;
            bottom: 11px;
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #22c55e;
            border: 2px solid #ffffff;
        }

        @media (max-width: 576px) {
            #customerChatWidget {
                right: 8px;
                bottom: 8px;
            }

            #customerChatPanel {
                width: calc(100vw - 16px);
                height: min(540px, calc(100vh - 82px));
            }
        }
    </style>

    <div id="customerChatPanel" aria-labelledby="customerChatTitle" aria-modal="false" role="dialog">
        <div class="customer-chat-header">
            <div class="customer-chat-brand">
                <div class="customer-chat-brand-avatar">
                    <img src="/assets/images/robotAI.png" alt="Bot NTU">
                    <span class="customer-chat-online-dot" aria-hidden="true"></span>
                </div>
                <div class="customer-chat-brand-copy">
                    <p class="customer-chat-title" id="customerChatTitle">Bot NTU</p>
                    <p class="customer-chat-subtitle">Đang hoạt động</p>
                </div>
            </div>

            <div class="customer-chat-header-actions">
                <button id="customerChatQuickHotline" class="customer-chat-icon-button" type="button" aria-label="Hỏi hotline cửa hàng">
                    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M5.21 2.37C5.52 2.05 6.03 2.05 6.34 2.37L7.56 3.58C7.87 3.89 7.87 4.4 7.56 4.71L6.82 5.45C7.17 6.15 7.64 6.79 8.23 7.38C8.82 7.96 9.46 8.44 10.16 8.78L10.89 8.05C11.21 7.73 11.72 7.73 12.03 8.05L13.24 9.26C13.56 9.57 13.56 10.08 13.24 10.39L12.68 10.96C12.18 11.45 11.45 11.65 10.77 11.48C9.32 11.13 7.96 10.37 6.83 9.24C5.69 8.11 4.94 6.75 4.58 5.3C4.42 4.62 4.62 3.89 5.11 3.39L5.21 2.37Z" fill="currentColor"/>
                    </svg>
                </button>
                <button id="customerChatClose" class="customer-chat-icon-button" type="button" aria-label="Đóng chatbot">
                    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M3.53 3.53L12.47 12.47M12.47 3.53L3.53 12.47" stroke="currentColor" stroke-linecap="round" stroke-width="1.75"/>
                    </svg>
                </button>
            </div>
        </div>

        <div id="customerChatMessages" aria-live="polite"></div>

        <div class="customer-chat-suggestions" id="customerChatSuggestionsWrap">
            <div class="customer-chat-suggestions-title">Câu hỏi thường gặp</div>
            <div class="customer-chat-suggestions-list" id="customerChatSuggestions" aria-label="Gợi ý câu hỏi thường gặp"></div>
        </div>

        <form id="customerChatForm" autocomplete="off">
            <button id="customerChatFocusInput" class="customer-chat-input-action" type="button" aria-label="Tập trung vào ô nhập tin nhắn">
                <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="M2 2.5C2 1.95 2.45 1.5 3 1.5H13C13.55 1.5 14 1.95 14 2.5V13.5C14 14.05 13.55 14.5 13 14.5H3C2.45 14.5 2 14.05 2 13.5V2.5Z" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M4.5 10.75L6.75 8.5L8.25 10L10.75 7.5L12 8.75V12H4.5V10.75Z" fill="currentColor"/>
                    <circle cx="5.25" cy="5" r="1" fill="currentColor"/>
                </svg>
            </button>

            <div class="customer-chat-input-shell">
                <input
                    id="customerChatInput"
                    type="text"
                    maxlength="2000"
                    placeholder="Nhập tin nhắn..."
                    aria-label="Nhập tin nhắn cho chatbot"
                >
                <button id="customerChatSend" type="submit" aria-label="Gửi tin nhắn">
                    <svg viewBox="0 0 17 14" fill="none" aria-hidden="true">
                        <path d="M0.87 13.39C0.55 13.55 0.18 13.25 0.28 12.91L1.8 7.5L0.28 2.09C0.18 1.75 0.55 1.45 0.87 1.61L15.32 6.47C15.7 6.6 15.7 7.14 15.32 7.27L0.87 12.13V13.39Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <button id="customerChatToggle" type="button" aria-label="Mở chatbot" aria-expanded="false">
        <img src="/assets/images/robotAI.png" alt="Bot NTU" class="chat-toggle-icon">
        <span class="customer-chat-toggle-dot" aria-hidden="true"></span>
    </button>
</div>
