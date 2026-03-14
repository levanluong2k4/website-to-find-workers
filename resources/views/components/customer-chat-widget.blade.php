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

        .chat-toggle-icon {
            width: 34px;
            height: 34px;
            object-fit: contain;
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

        .chat-typing {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .chat-typing-avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            object-fit: cover;
            border: 1px solid #bae6fd;
            background: #fff;
            padding: 4px;
            flex-shrink: 0;
        }

        .chat-typing-dots {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 16px;
        }

        .chat-typing-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #0ea5e9;
            animation: chatTypingBounce 1.1s infinite ease-in-out;
        }

        .chat-typing-dot:nth-child(2) {
            animation-delay: 0.15s;
        }

        .chat-typing-dot:nth-child(3) {
            animation-delay: 0.3s;
        }

        @keyframes chatTypingBounce {
            0%, 80%, 100% {
                transform: translateY(0);
                opacity: 0.35;
            }

            40% {
                transform: translateY(-4px);
                opacity: 1;
            }
        }
    </style>

    <div id="customerChatPanel">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-white">
            <div>
                <div class="fw-bold" style="font-size:.92rem;color:#0f172a;">Tro ly AI Dien Lanh</div>
                <small class="text-secondary">Phan tich loi va goi y tho phu hop</small>
            </div>
            <button id="customerChatClose" type="button" class="btn btn-sm btn-light border">x</button>
        </div>
        <div id="customerChatMessages" class="p-3"></div>
        <form id="customerChatForm" class="border-top bg-white p-2">
            <div class="input-group">
                <input
                    id="customerChatInput"
                    type="text"
                    class="form-control"
                    placeholder="Nhap loi thiet bi... vi du: may lanh bi chay nuoc"
                    maxlength="2000"
                    autocomplete="off"
                >
                <button id="customerChatSend" class="btn btn-primary" type="submit">Gui</button>
            </div>
        </form>
    </div>

    <button id="customerChatToggle" type="button" aria-label="Mo chatbot">
        <img src="/assets/images/robotAI.png" alt="AI Robot" class="chat-toggle-icon">
    </button>
</div>
