
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
    <link rel="stylesheet" href="./styles/design.css">
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <h2 class="logo">COZIEST</h2>

        <div class="menu">
            <nav><a href="home.php">Home</a></nav>
            <nav><a href="products.php">Product</a></nav>
            <nav><a href="info.php">Info</a></nav>
            <nav><a href="message.php" class="active">Message</a></nav>
            <nav><a href="settings.php">Settings</a></nav>
            <nav><a href="accounts.php">Accounts</a></nav>
            <nav>
            <a href="logout.php">Logout</a>
        </nav>
        </div>

        <div class="profile">
            <img src="https://tinyurl.com/4z6wxw6b" width="50" height="50" alt="Profile">
            <div>
                <h4>COZIEST</h4>
                <span>ACCOUNT</span>
            </div>
        </div>
    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <h3>Hello, <strong>COZIEST</strong></h3>
                <p>Messages</p>
            </div>
            <img class="avatar" src="https://tinyurl.com/4z6wxw6b" width="50" height="50" alt="Avatar">
        </header>

        <section class="messenger-container">
            <div class="messenger-card">
                <div class="chat-header">
                    <span class="back-icon">←</span>
                    <div class="header-info">
                        <h2>Mommy Narlyn Printing Shop</h2>
                        <div class="subtitle">
                            Active now
                            <span class="active-dot"></span>
                        </div>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages"></div>

                <div class="input-footer">
                    <input 
                        type="text" 
                        id="messageInput" 
                        placeholder="Type message..." 
                        autocomplete="off"
                    >
                    <button id="sendBtn">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path fill="currentColor" d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </section>

    </main>

</div>

<script>
    const STORAGE_KEY = "messenger_clean_chat";
    const chatMessagesEl = document.getElementById("chatMessages");
    const inputEl = document.getElementById("messageInput");
    const sendBtn = document.getElementById("sendBtn");

    function loadMessages() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            return stored ? JSON.parse(stored) : [];
        } catch {
            return [];
        }
    }

    function saveMessages(list) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    }

    function renderMessage(message) {
        const row = document.createElement("div");
        row.className = "message-row user";

        const bubble = document.createElement("div");
        bubble.className = "bubble";
        bubble.textContent = message.text;

        row.appendChild(bubble);
        return row;
    }

    function renderAllMessages(messages) {
        chatMessagesEl.innerHTML = "";
        messages.forEach(m => {
            chatMessagesEl.appendChild(renderMessage(m));
        });
        chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
    }

    let messages = loadMessages();
    renderAllMessages(messages);

    function sendMessage() {
        const text = inputEl.value.trim();
        if (!text) return;

        const msg = { text };
        messages.push(msg);
        saveMessages(messages);

        chatMessagesEl.appendChild(renderMessage(msg));
        chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;

        inputEl.value = "";
        inputEl.focus();
    }

    sendBtn.addEventListener("click", sendMessage);

    inputEl.addEventListener("keydown", function(e) {
        if (e.key === "Enter") {
            sendMessage();
        }
    });
</script>

</body>
</html>