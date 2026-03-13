<div id="customerChatWidget" style="display:none;">
    <style>
        #customerChatWidget {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1200;
            font-family: 'Inter', sans-serif;
        }

        #customerChatToggle {
            width: 58px;
            height: 58px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: #fff;
            box-shadow: 0 10px 30px rgba(2, 132, 199, 0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #customerChatPanel {
            display: none;
            width: min(380px, calc(100vw - 24px));
            height: min(560px, calc(100vh - 90px));
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 55px rgba(15, 23, 42, 0.22);
            border: 1px solid #e2e8f0;
            margin-bottom: 12px;
        }

        #customerChatMessages {
            height: calc(100% - 132px);
            overflow-y: auto;
            background: #f8fafc;
        }

        .chat-bubble {
            max-width: 85%;
            border-radius: 14px;
            padding: 10px 12px;
            font-size: 0.9rem;
            line-height: 1.45;
            word-wrap: break-word;
        }

        .chat-bubble-user {
            background: #0ea5e9;
            color: #fff;
            margin-left: auto;
        }

        .chat-bubble-assistant {
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #0f172a;
        }

        .chat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            margin-top: 8px;
        }
    </style>

    <div id="customerChatPanel">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-white">
            <div>
                <div class="fw-bold" style="font-size:.92rem;color:#0f172a;">Trợ lý AI Điện Lạnh</div>
                <small class="text-secondary">Phân tích lỗi và gợi ý thợ phù hợp</small>
            </div>
            <button id="customerChatClose" type="button" class="btn btn-sm btn-light border">✕</button>
        </div>
        <div id="customerChatMessages" class="p-3"></div>
        <form id="customerChatForm" class="border-top bg-white p-2">
            <div class="input-group">
                <input
                    id="customerChatInput"
                    type="text"
                    class="form-control"
                    placeholder="Nhập lỗi thiết bị... ví dụ: máy lạnh bị chảy nước"
                    maxlength="2000"
                    autocomplete="off"
                >
                <button id="customerChatSend" class="btn btn-primary" type="submit">Gửi</button>
            </div>
        </form>
    </div>

    <button id="customerChatToggle" type="button" aria-label="Mở chatbot">
        <span class="material-symbols-outlined">forum</span>
    </button>
</div>

