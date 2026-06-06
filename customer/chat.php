<?php
session_start();
require_once '../config/koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$initial_msg = $_GET['msg'] ?? '';

$stmt_user = mysqli_prepare($conn, "SELECT nama, username, profile_picture FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$active_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));
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
            --card-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .navbar {
            background: var(--brand-gradient) !important;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
            flex-shrink: 0;
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
            text-decoration: none;
        }

        .user-nav-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--border-subtle);
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

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 900px;
            width: 100%;
            margin: 20px auto;
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
            border-radius: 16px;
            background: rgba(156, 39, 176, 0.1);
            color: var(--brand-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
        }

        .chat-header h3 {
            font-size: 1.2rem;
            color: var(--text-dark);
            font-weight: 800;
            margin-bottom: 2px;
            margin-top: 0;
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
            padding: 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #F8FAFC;
        }

        .message {
            max-width: 75%;
            padding: 14px 18px;
            font-size: 0.95rem;
            line-height: 1.5;
            font-weight: 500;
            position: relative;
        }

        .message.admin {
            background: #FFFFFF;
            color: var(--text-dark);
            align-self: flex-start;
            border-radius: 20px 20px 20px 4px;
            border: 1px solid var(--border-subtle);
        }

        .message.customer {
            background: var(--brand-gradient);
            color: white;
            align-self: flex-end;
            border-radius: 20px 20px 4px 20px;
        }

        .msg-footer {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 6px;
            justify-content: flex-end;
        }

        .message.admin .msg-footer {
            justify-content: flex-start;
        }

        .time {
            font-size: 0.7rem;
            font-weight: 600;
        }

        .message.admin .time {
            color: var(--text-muted);
        }

        .message.customer .time {
            color: rgba(255, 255, 255, 0.8);
        }

        .btn-del-msg {
            background: none;
            border: none;
            font-size: 0.85rem;
            cursor: pointer;
            transition: 0.3s;
            padding: 0;
        }

        .message.customer .btn-del-msg {
            color: rgba(255, 255, 255, 0.6);
        }

        .message.customer .btn-del-msg:hover {
            color: white;
        }

        .chat-image {
            max-width: 250px;
            width: 100%;
            border-radius: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.2s;
        }

        .chat-image:hover {
            transform: scale(1.02);
        }

        .chat-input {
            background: #FFFFFF;
            padding: 20px 30px;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-attach {
            background: #F1F5F9;
            color: var(--text-muted);
            border: 1px solid var(--border-subtle);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-attach:hover {
            background: #E2E8F0;
            color: var(--brand-purple);
        }

        .btn-attach.has-file {
            background: rgba(233, 30, 99, 0.1);
            color: var(--brand-pink);
            border-color: var(--brand-pink);
        }

        .chat-input input[type="text"] {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid var(--border-subtle);
            border-radius: 30px;
            outline: none;
            background: #F8FAFC;
            font-weight: 500;
        }

        .chat-input button[type="submit"] {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--brand-gradient);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .chat-container {
                margin: 0;
                border-radius: 0;
                border: none;
                border-top: 1px solid var(--border-subtle);
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container d-lg-flex px-4">
            <div class="nav-zone-left">
                <a class="brand-pill" href="katalog.php">
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
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="keranjang.php"><i
                                class="bi bi-cart3 fs-5"></i> Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="pesanan.php"><i
                                class="bi bi-receipt fs-5"></i> Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2 active" href="chat.php"><i
                                class="bi bi-chat-dots fs-5"></i> Chat</a></li>
                </ul>
            </div>
            <div class="collapse navbar-collapse nav-zone-right" id="navbarNavRight">
                <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0 w-100 justify-content-lg-end">
                    <div class="dropdown">
                        <button class="btn-white-nav dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <?php if (!empty($active_user['profile_picture'])): ?>
                                <img src="../uploads/profiles/<?= htmlspecialchars($active_user['profile_picture']) ?>"
                                    class="user-nav-avatar">
                            <?php else: ?>
                                <i class="bi bi-person-circle fs-5 text-gradient"></i>
                            <?php endif; ?>
                            <span class="text-gradient">
                                <?= htmlspecialchars($active_user['username'] ?? 'User') ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger fw-bold d-flex align-items-center"
                                    href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="chat-container">
        <div class="chat-header">
            <div class="avatar"><i class="bi bi-headset"></i></div>
            <div>
                <h3>Admin 7CellX</h3>
                <span class="status offline" id="adminStatus"><i class="bi bi-circle-fill"
                        style="font-size: 0.5rem;"></i> Menghubungkan...</span>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages"></div>

        <form class="chat-input" id="chatForm" method="POST" enctype="multipart/form-data">
            <input type="file" id="imageInput" name="image" accept="image/*" class="d-none">
            <button type="button" class="btn-attach" id="attachBtn"
                onclick="document.getElementById('imageInput').click()" title="Lampirkan Gambar">
                <i class="bi bi-paperclip"></i>
            </button>
            <input type="text" id="msgInput" placeholder="Ketik pesan..." autocomplete="off"
                value="<?= htmlspecialchars($initial_msg) ?>">
            <button type="submit" title="Kirim Pesan"><i class="bi bi-send-fill"></i></button>
        </form>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const msgInput = document.getElementById('msgInput');
        const imageInput = document.getElementById('imageInput');
        const attachBtn = document.getElementById('attachBtn');
        const adminStatus = document.getElementById('adminStatus');

        let lastCount = 0;
        let isActive = 1;

        imageInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                attachBtn.classList.add('has-file');
                msgInput.placeholder = "Gambar siap dikirim...";
            } else {
                attachBtn.classList.remove('has-file');
                msgInput.placeholder = "Ketik pesan...";
            }
        });

        function formatTime(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        function loadChat() {
            fetch('../chat_api.php?action=fetch')
                .then(r => r.json())
                .then(data => {
                    if (data.length !== lastCount) {
                        chatMessages.innerHTML = '';
                        data.forEach(msg => {
                            const div = document.createElement('div');
                            div.className = 'message ' + msg.sender_role;

                            let content = '';
                            if (msg.attachment) content += `<img src="../uploads/chat/${msg.attachment}" class="chat-image" onclick="window.open(this.src, '_blank')"><br>`;
                            if (msg.message) content += msg.message;

                            let footer = `<div class="msg-footer">`;
                            if (msg.sender_role === 'customer') {
                                footer += `<button class="btn-del-msg" onclick="deleteMsg(${msg.id})" title="Hapus Pesan"><i class="bi bi-trash3-fill"></i></button>`;
                            }
                            footer += `<span class="time">${formatTime(msg.created_at)}</span></div>`;

                            div.innerHTML = content + footer;
                            chatMessages.appendChild(div);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        lastCount = data.length;
                    }
                }).catch(err => console.error(err));
        }

        function deleteMsg(id) {
            if (!confirm('Hapus pesan ini secara permanen?')) return;
            const fd = new FormData();
            fd.append('msg_id', id);
            fetch('../chat_api.php?action=delete', { method: 'POST', body: fd })
                .then(() => { lastCount = 0; loadChat(); });
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
                        let last = data.last_seen ? formatTime(data.last_seen) : '-';
                        adminStatus.innerHTML = '<i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Terakhir aktif: ' + last;
                    }
                }).catch(() => {
                    adminStatus.className = 'status offline';
                    adminStatus.innerHTML = '<i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Offline';
                });
        }

        chatForm.addEventListener('submit', e => {
            e.preventDefault();
            const msg = msgInput.value.trim();
            const hasFile = imageInput.files.length > 0;

            if (!msg && !hasFile) return;

            const formData = new FormData();
            formData.append('message', msg);
            if (hasFile) formData.append('image', imageInput.files[0]);

            msgInput.value = '';
            imageInput.value = '';
            attachBtn.classList.remove('has-file');
            msgInput.disabled = true;

            fetch('../chat_api.php?action=send', {
                method: 'POST',
                body: formData
            }).then(() => {
                msgInput.disabled = false;
                msgInput.focus();
                lastCount = 0;
                loadChat();
            });
        });

        loadChat(); checkAdminStatus();
        setInterval(loadChat, 3000);
        setInterval(checkAdminStatus, 10000);
        setInterval(() => fetch('../chat_api.php?action=status&active=1'), 15000);
    </script>
</body>

</html>