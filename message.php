<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COZIEST - Messages</title>
    <link rel="stylesheet" href="./styles/index.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== MESSENGER PAGE OVERRIDES ===== */
        body.dashboard-page {
            font-family: 'DM Sans', 'Segoe UI', sans-serif;
        }

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            padding: 0;
            background: #f7f7f5;
        }

        /* ===== MESSENGER LAYOUT ===== */
        .messenger-container {
            flex: 1;
            display: flex;
            height: 100vh;
            padding: 24px;
            gap: 0;
            overflow: hidden;
        }

        .messenger-card {
            display: flex;
            flex-direction: column;
            flex: 1;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0eeea;
            height: 100%;
        }

        /* ===== CHAT HEADER ===== */
        .chat-header {
            padding: 16px 20px;
            background: #ffffff;
            border-bottom: 1.5px solid #f0eeea;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .back-icon {
            font-size: 20px;
            color: #888;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
            text-decoration: none;
        }

        .back-icon:hover {
            background: #f0eeea;
        }

        .header-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dfa8a2, #c22b1b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .header-info {
            flex: 1;
        }

        .header-info h2 {
            font-size: 15px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 2px;
        }

        .subtitle {
            font-size: 12px;
            color: #888;
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0;
        }

        .active-dot {
            width: 7px;
            height: 7px;
            background: #31a24c;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .header-actions {
            display: flex;
            gap: 6px;
        }

        .header-action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #f5f4f1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: #666;
            margin: 0;
            padding: 0;
            transition: background 0.2s;
        }

        .header-action-btn:hover {
            background: #ede9e3;
        }

        /* ===== CHAT MESSAGES AREA ===== */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px 20px 8px;
            background: #fafaf8;
            display: flex;
            flex-direction: column;
            gap: 8px;
            scroll-behavior: smooth;
        }

        .chat-messages::-webkit-scrollbar {
            width: 4px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #e0ddd8;
            border-radius: 99px;
        }

        /* Date separator */
        .date-separator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
        }

        .date-separator::before,
        .date-separator::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #ebe9e4;
        }

        .date-separator span {
            font-size: 11px;
            color: #aaa;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Empty state */
        .empty-chat {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #bbb;
            padding: 40px 20px;
        }

        .empty-chat-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #f0eeea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .empty-chat p {
            font-size: 14px;
            color: #bbb;
            text-align: center;
        }

        /* ===== MESSAGE ROWS ===== */
        .message-row {
            display: flex;
            width: 100%;
            align-items: flex-end;
            gap: 8px;
        }

        .message-row.user {
            justify-content: flex-end;
        }

        .message-row.other {
            justify-content: flex-start;
        }

        /* Bubble */
        .bubble {
            max-width: 68%;
            padding: 10px 14px;
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word;
            position: relative;
        }

        /* User bubble */
        .message-row.user .bubble {
            background: #c22b1b;
            color: #fff;
            border-radius: 18px 18px 4px 18px;
            box-shadow: 0 2px 8px rgba(194, 43, 27, 0.2);
        }

        /* Other bubble */
        .message-row.other .bubble {
            background: #ffffff;
            color: #1a1a1a;
            border-radius: 18px 18px 18px 4px;
            border: 1px solid #ebe9e4;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .message-time {
            font-size: 10px;
            opacity: 0.55;
            margin-top: 3px;
            text-align: right;
        }

        .message-row.other .message-time {
            text-align: left;
            color: #999;
        }

        /* Sender avatar for "other" */
        .sender-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dfa8a2, #c22b1b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        /* ===== INPUT FOOTER ===== */
        .input-footer {
            background: #ffffff;
            border-top: 1.5px solid #f0eeea;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .input-wrapper {
            flex: 1;
            background: #f5f4f1;
            border-radius: 24px;
            display: flex;
            align-items: center;
            padding: 0 14px;
            gap: 8px;
            border: 1.5px solid transparent;
            transition: border-color 0.2s, background 0.2s;
        }

        .input-wrapper:focus-within {
            border-color: #c22b1b;
            background: #fff;
        }

        .input-footer input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            padding: 11px 0;
            font-size: 14px;
            color: #1a1a1a;
            font-family: 'DM Sans', sans-serif;
            width: 0;
        }

        .input-footer input::placeholder {
            color: #bbb;
        }

        .attach-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #bbb;
            font-size: 16px;
            padding: 0;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .attach-btn:hover {
            color: #888;
            background: none;
        }

        .send-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: #c22b1b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
            flex-shrink: 0;
            transition: background 0.2s, transform 0.15s;
        }

        .send-btn:hover {
            background: #a82416;
        }

        .send-btn:active {
            transform: scale(0.93);
        }

        .send-btn:disabled {
            background: #e0ddd8;
            cursor: not-allowed;
        }

        .send-btn svg {
            width: 18px;
            height: 18px;
            fill: #fff;
            margin-left: 2px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .messenger-container {
                padding: 12px;
            }

            .bubble {
                max-width: 82%;
            }
        }
    </style>
</head>
<body class="dashboard-page">

<div class="dashboard">

    <?php include 'sidebar.php'; ?>

    <main class="main">
        <section class="messenger-container">
            <div class="messenger-card">

                <!-- Header -->
                <div class="chat-header">
                    <a class="back-icon" href="javascript:history.back()">&#8592;</a>
                    <div class="header-avatar">MN</div>
                    <div class="header-info">
                        <h2>Mommy Narlyn Printing Shop</h2>
                        <div class="subtitle">
                            <span class="active-dot"></span>
                            Active now
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="header-action-btn" title="Call">&#128222;</button>
                        <button class="header-action-btn" title="Info">&#9432;</button>
                    </div>
                </div>

                <!-- Messages -->
                <div class="chat-messages" id="chatMessages">
                    <!-- Populated by JS -->
                </div>

                <!-- Input -->
                <div class="input-footer">
                    <div class="input-wrapper">
                        <button class="attach-btn" title="Attach file">&#128206;</button>
                        <input
                            type="text"
                            id="messageInput"
                            placeholder="Type a message..."
                            autocomplete="off"
                        >
                    </div>
                    <button class="send-btn" id="sendBtn" disabled>
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>

            </div>
        </section>
    </main>

</div>

<script>
    const STORAGE_KEY = "messenger_clean_chat";
    const chatEl      = document.getElementById("chatMessages");
    const inputEl     = document.getElementById("messageInput");
    const sendBtn     = document.getElementById("sendBtn");

    /* ── helpers ── */
    function formatTime(ts) {
        const d = new Date(ts);
        return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    }

    function formatDate(ts) {
        const d = new Date(ts);
        const today = new Date();
        if (d.toDateString() === today.toDateString()) return "Today";
        const yesterday = new Date(today); yesterday.setDate(today.getDate() - 1);
        if (d.toDateString() === yesterday.toDateString()) return "Yesterday";
        return d.toLocaleDateString([], { month: "short", day: "numeric" });
    }

    /* ── storage ── */
    function loadMessages() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; }
        catch { return []; }
    }

    function saveMessages(list) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    }

    /* ── rendering ── */
    function makeDateSep(label) {
        const div = document.createElement("div");
        div.className = "date-separator";
        div.innerHTML = `<span>${label}</span>`;
        return div;
    }

    function makeRow(msg) {
        const row = document.createElement("div");
        row.className = `message-row ${msg.sender === "user" ? "user" : "other"}`;

        if (msg.sender !== "user") {
            const av = document.createElement("div");
            av.className = "sender-avatar";
            av.textContent = "MN";
            row.appendChild(av);
        }

        const wrap = document.createElement("div");

        const bubble = document.createElement("div");
        bubble.className = "bubble";
        bubble.textContent = msg.text;

        const time = document.createElement("div");
        time.className = "message-time";
        time.textContent = formatTime(msg.ts);

        wrap.appendChild(bubble);
        wrap.appendChild(time);
        row.appendChild(wrap);

        return row;
    }

    function renderAll(messages) {
        chatEl.innerHTML = "";

        if (messages.length === 0) {
            chatEl.innerHTML = `
                <div class="empty-chat">
                    <div class="empty-chat-icon">&#128172;</div>
                    <p>No messages yet. Say hello!</p>
                </div>`;
            return;
        }

        let lastDate = null;
        messages.forEach(m => {
            const dayLabel = formatDate(m.ts);
            if (dayLabel !== lastDate) {
                chatEl.appendChild(makeDateSep(dayLabel));
                lastDate = dayLabel;
            }
            chatEl.appendChild(makeRow(m));
        });

        chatEl.scrollTop = chatEl.scrollHeight;
    }

    /* ── send ── */
    function sendMessage() {
        const text = inputEl.value.trim();
        if (!text) return;

        const msg = { text, sender: "user", ts: Date.now() };
        messages.push(msg);
        saveMessages(messages);

        // remove empty state if present
        const empty = chatEl.querySelector(".empty-chat");
        if (empty) chatEl.innerHTML = "";

        // check date separator
        const dayLabel = formatDate(msg.ts);
        const seps = chatEl.querySelectorAll(".date-separator span");
        const lastSep = seps.length ? seps[seps.length - 1].textContent : null;
        if (dayLabel !== lastSep) chatEl.appendChild(makeDateSep(dayLabel));

        chatEl.appendChild(makeRow(msg));
        chatEl.scrollTop = chatEl.scrollHeight;

        inputEl.value = "";
        sendBtn.disabled = true;
        inputEl.focus();
    }

    /* ── enable/disable send ── */
    inputEl.addEventListener("input", function () {
        sendBtn.disabled = !this.value.trim();
    });

    sendBtn.addEventListener("click", sendMessage);

    inputEl.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    /* ── init ── */
    let messages = loadMessages();
    renderAll(messages);
</script>

</body>
</html>