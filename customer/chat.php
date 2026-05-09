<?php
session_start();
/** @var mysqli $conn */
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat - 7CellX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --brand-pink: #E91E63;
            --brand-purple: #9C27B0;
            --brand-navy: #1A237E;
            --bg-main: #F4F7FE;
            --bg-card: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --border-subtle: #E2E8F0;
            --brand-gradient: linear-gradient(135deg, #E91E63 0%, #9C27B0 50%, #1A237E 100%);
            --glow-shadow: 0 15px 35px rgba(156, 39, 176, 0.2);
            --card-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- SINKRONISASI NAVBAR --- */
        .navbar {
            background: var(--brand-gradient) !important;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        @media (min-width: 992px) {
            .nav-zone-left {
                flex: 1;
                display: flex;
                justify-content: flex-start;
            }

            .nav-zone-center {
                flex: 2;
                display: flex;
                justify-content: center;
            }

            .nav-zone-right {
                flex: 1;
                display: flex;
                justify-content: flex-end;
            }
        }

        .brand-pill {
            background: #FFFFFF;
            padding: 6px 20px 6px 8px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .brand-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .brand-logo-img {
            height: 30px;
            width: 30px;
            border-radius: 50%;
            object-fit: contain;
        }

        .text-gradient {
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 600;
            margin: 0 5px;
            padding: 8px 16px !important;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: #FFFFFF !important;
            transform: translateY(-1px);
        }

        .btn-white-nav {
            background: #FFFFFF;
            color: var(--brand-purple);
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 30px;
            border: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }

        .btn-white-nav:hover {
            transform: translateY(-2px);
            color: var(--brand-pink);
        }

        .dropdown-menu {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 10px;
            margin-top: 15px !important;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 500;
        }

        .dropdown-item:hover {
            background-color: #F8FAFC;
            color: var(--brand-purple);
        }

        .dropdown-item.text-danger:hover {
            background-color: #FEF2F2;
            color: #DC2626 !important;
        }

        /* --- CHAT INTERFACE --- */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 900px;
            width: 100%;
            margin: 40px auto;
            background: var(--bg-card);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-subtle);
            overflow: hidden;
        }

        .chat-header {
            background: #FFFFFF;
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-header .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(156, 39, 176, 0.1);
            color: var(--brand-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .chat-header h2 {
            font-size: 1.2rem;
            color: var(--text-dark);
            font-weight: 800;
            margin-bottom: 2px;
        }

        .chat-header .status {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }

        .chat-header .status.online {
            color: #10B981;
        }

        .chat-header .status.offline {
            color: #64748B;
        }

        .chat-messages {
            flex: 1;
            background: #F8FAFC;
            padding: 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            height: 500px;
        }

        .message {
            max-width: 75%;
            padding: 14px 18px;
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
            font-weight: 500;
        }

        /* Bubble Chat Admin (Kiri) */
        .message.admin {
            background: #FFFFFF;
            color: var(--text-dark);
            align-self: flex-start;
            border-radius: 20px 20px 20px 4px;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
        }

        /* Bubble Chat Customer (Kanan) */
        .message.customer {
            background: var(--brand-gradient);
            color: white;
            align-self: flex-end;
            border-radius: 20px 20px 4px 20px;
            box-shadow: 0 4px 15px rgba(156, 39, 176, 0.2);
        }

        .message .time {
            font-size: 0.7rem;
            margin-top: 6px;
            display: block;
            font-weight: 600;
        }

        .message.admin .time {
            color: var(--text-muted);
        }

        .message.customer .time {
            color: rgba(255, 255, 255, 0.8);
        }

        .empty-chat {
            text-align: center;
            color: var(--text-muted);
            margin: auto;
        }

        .empty-chat i {
            font-size: 4rem;
            color: #CBD5E1;
            margin-bottom: 10px;
            display: block;
        }

        .empty-chat p {
            font-weight: 600;
        }

        .chat-input {
            background: #FFFFFF;
            padding: 20px 30px;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chat-input input {
            flex: 1;
            padding: 15px 25px;
            border: 1px solid var(--border-subtle);
            border-radius: 30px;
            font-size: 1rem;
            outline: none;
            background: #F4F7FE;
            font-weight: 500;
            transition: all 0.3s;
        }

        .chat-input input:focus {
            background: #FFFFFF;
            border-color: var(--brand-purple);
            box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1);
        }

        .chat-input button {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: var(--brand-gradient);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: var(--glow-shadow);
        }

        .chat-input button:hover {
            transform: scale(1.05);
        }

        footer {
            margin-top: auto;
            background: var(--brand-gradient);
            padding: 20px 0;
            text-align: center;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container d-lg-flex">

            <div class="nav-zone-left">
                <a class="brand-pill" href="index.php">
                    <img src="../assets/logo.png" alt="Logo" class="brand-logo-img"
                        onerror="this.src='https://via.placeholder.com/40x40/0F172A/FFFFFF?text=7C'">
                    <span class="text-gradient fw-bold fs-5 mb-0" style="letter-spacing: -0.5px;">7CellX</span>
                </a>
                <button class="navbar-toggler ms-auto border-0 shadow-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon" style="filter: brightness(0) invert(1);"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse nav-zone-center" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="katalog.php"><i
                                class="bi bi-grid-fill fs-5"></i> Katalog</a></li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 position-relative" href="keranjang.php">
                            <i class="bi bi-cart3 fs-5"></i> Keranjang
                            <?php
                            $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                            if ($cart_count > 0):
                                ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-white"
                                    style="font-size: 0.6rem;">
                                    <?= $cart_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="pesanan.php"><i
                                class="bi bi-receipt fs-5"></i> Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2 active" href="chat.php"><i
                                class="bi bi-chat-dots fs-5"></i> Chat Seller</a></li>
                </ul>
            </div>

            <div class="collapse navbar-collapse nav-zone-right" id="navbarNavRight">
                <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0 w-100 justify-content-lg-end">
                    <div class="dropdown">
                        <button class="btn-white-nav dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5 text-gradient"></i>
                            <span class="text-gradient">
                                <?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['nama'] ?? 'User') ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i
                                        class="bi bi-gear me-2 text-muted"></i>Settings</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger fw-bold" href="../auth/logout.php"><i
                                        class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </nav>

    <div class="container d-flex flex-column" style="flex: 1;">
        <div class="chat-container">
            <div class="chat-header">
                <div class="avatar"><i class="bi bi-headset"></i></div>
                <div>
                    <h2>Customer Support</h2>
                    <span class="status offline" id="adminStatus">
                        <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Menghubungkan...
                    </span>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="empty-chat">
                    <i class="bi bi-chat-dots"></i>
                    <p>Mulai percakapan dengan admin 7CellX</p>
                </div>
            </div>

            <form class="chat-input" id="chatForm">
                <input type="text" id="msgInput" placeholder="Ketik pesan Anda di sini..." autocomplete="off" required>
                <button type="submit" title="Kirim Pesan"><i class="bi bi-send-fill"></i></button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container text-center text-white small fw-medium opacity-75">
            <p class="mb-0">&copy;
                <?= date('Y') ?> 7CellX. Engineered with precision.
            </p>
        </div>
    </footer>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const msgInput = document.getElementById('msgInput');
        const adminStatus = document.getElementById('adminStatus');
        let lastMsgCount = 0;
        let isActive = 1;

        function formatTime(dateStr) {
            const date = new Date(dateStr);
            const options = {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            };
            return date.toLocaleDateString('id-ID', options);
        }

        function fetchMessages() {
            fetch('../chat_api.php?action=fetch')
                .then(r => r.json())
                .then(data => {
                    if (data.length !== lastMsgCount) {
                        renderMessages(data);
                        lastMsgCount = data.length;
                    }
                });
        }

        function checkAdminStatus() {
            fetch('../chat_api.php?action=check_status')
                .then(r => r.json())
                .then(data => {
                    if (data.is_online == 1) {
                        adminStatus.className = 'status online';
                        adminStatus.innerHTML = '<i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Online';
                    } else {
                        adminStatus.className = 'status offline';
                        adminStatus.innerHTML = '<i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Offline';
                    }
                });
        }

        function updatePresence() {
            fetch('../chat_api.php?action=status&active=' + isActive);
        }

        function renderMessages(messages) {
            chatMessages.innerHTML = '';
            if (messages.length === 0) {
                chatMessages.innerHTML = '<div class="empty-chat"><i class="bi bi-chat-heart"></i><p>Sapa admin kami untuk memulai percakapan</p></div>';
                return;
            }
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = 'message ' + msg.sender_role;
                div.innerHTML = msg.message + '<span class="time">' + formatTime(msg.created_at) + '</span>';
                chatMessages.appendChild(div);
            });
            // Auto scroll ke bawah
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        chatForm.addEventListener('submit', e => {
            e.preventDefault();
            const msg = msgInput.value.trim();
            if (!msg) return;

            fetch('../chat_api.php?action=send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'message=' + encodeURIComponent(msg)
            }).then(() => {
                msgInput.value = '';
                fetchMessages();
            });
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                isActive = 0;
            } else {
                isActive = 1;
            }
            updatePresence();
        });

        // Initial Load
        fetchMessages();
        checkAdminStatus();
        updatePresence();

        // Polling
        setInterval(fetchMessages, 3000);
        setInterval(checkAdminStatus, 5000);
        setInterval(updatePresence, 10000);
    </script>
</body>

</html>