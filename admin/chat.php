<?php
session_start();
/** @var mysqli $conn */
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$customers = mysqli_query($conn, "SELECT DISTINCT u.id, u.nama, u.email, u.is_online, u.last_seen FROM users u JOIN chats c ON u.id = c.user_id WHERE u.role = 'customer' ORDER BY (SELECT MAX(created_at) FROM chats WHERE user_id = u.id) DESC");
$selected_id = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat Admin - 7CellX</title>
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
            --glow-shadow: 0 15px 35px rgba(156, 39, 176, 0.15);
            --card-shadow: 0 8px 25px rgba(0, 0, 0, 0.03);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--bg-main);
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* NAVBAR */
        .navbar {
            background: var(--brand-gradient) !important;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
            flex-shrink: 0;
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

        .dropdown-menu {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 10px;
            margin-top: 15px !important;
        }

        /* CHAT LAYOUT EKSKLUSIF ADMIN */
        .admin-chat-layout {
            display: flex;
            flex: 1;
            overflow: hidden;
            background: var(--bg-main);
        }

        .sidebar {
            width: 350px;
            background: var(--bg-card);
            border-right: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            z-index: 10;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.02);
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px dashed var(--border-subtle);
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--brand-navy);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .customer-item {
            padding: 15px;
            border-radius: 16px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .customer-item:hover {
            background: #F8FAFC;
            border-color: var(--border-subtle);
        }

        .customer-item.active {
            background: rgba(156, 39, 176, 0.05);
            border-color: rgba(156, 39, 176, 0.2);
            box-shadow: 0 4px 10px rgba(156, 39, 176, 0.05);
        }

        .customer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 16px;
            background: #F1F5F9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-weight: 800;
            font-size: 1.1rem;
            position: relative;
        }

        .customer-item.active .customer-avatar {
            background: var(--brand-gradient);
            color: white;
            box-shadow: var(--glow-shadow);
        }

        .online-dot {
            position: absolute;
            top: -3px;
            right: -3px;
            width: 14px;
            height: 14px;
            background: #10B981;
            border: 2.5px solid white;
            border-radius: 50%;
        }

        .customer-info h4 {
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 700;
            margin: 0 0 2px 0;
        }

        .customer-info p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
            font-weight: 500;
        }

        /* MAIN CHAT AREA */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #F4F7FE;
        }

        .chat-header {
            background: #FFFFFF;
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }

        .chat-header .avatar {
            width: 50px;
            height: 50px;
            border-radius: 16px;
            background: var(--brand-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 800;
            box-shadow: var(--glow-shadow);
        }

        .chat-header h3 {
            font-size: 1.3rem;
            color: var(--text-dark);
            font-weight: 800;
            margin: 0;
        }

        .chat-header .status {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            margin-top: 4px;
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
        }

        .message {
            max-width: 70%;
            padding: 14px 18px;
            font-size: 0.95rem;
            line-height: 1.5;
            font-weight: 500;
            position: relative;
        }

        .message.customer {
            background: #FFFFFF;
            color: var(--text-dark);
            align-self: flex-start;
            border-radius: 20px 20px 20px 4px;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
        }

        .message.admin {
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

        .message.customer .time {
            color: var(--text-muted);
        }

        .message.admin .time {
            color: rgba(255, 255, 255, 0.8);
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
            background: #F8FAFC;
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

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            background: #F8FAFC;
        }

        .empty-state i {
            font-size: 5rem;
            color: #CBD5E1;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            font-weight: 800;
            color: var(--brand-navy);
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4 d-lg-flex">
            <div class="nav-zone-left" style="flex: 1;">
                <a class="brand-pill" href="index.php">
                    <img src="../../assets/logo.png" alt="Logo" class="brand-logo-img"
                        onerror="this.src='https://via.placeholder.com/40x40/0F172A/FFFFFF?text=7C'">
                    <span class="text-gradient fw-bold fs-5 mb-0" style="letter-spacing: -0.5px;">7CellX Admin</span>
                </a>
            </div>
            <div class="collapse navbar-collapse nav-zone-center justify-content-center" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-2">
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="index.php"><i
                                class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="produk.php"><i
                                class="bi bi-box-seam"></i> Produk</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="pesanan.php"><i
                                class="bi bi-receipt"></i> Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link active d-flex align-items-center gap-2" href="chat.php"><i
                                class="bi bi-chat-dots"></i> Chat</a></li>
                </ul>
            </div>
            <div class="collapse navbar-collapse nav-zone-right justify-content-end" id="navbarNavRight"
                style="flex: 1;">
                <div class="dropdown">
                    <button class="btn-white-nav dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-check fs-5 text-gradient"></i>
                        <span class="text-gradient">
                            <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger fw-bold" href="../auth/logout.php"><i
                                    class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="admin-chat-layout">
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="bi bi-chat-text-fill fs-4 text-gradient"></i> Inbox Customers
            </div>
            <div class="customer-list">
                <?php while ($c = mysqli_fetch_assoc($customers)): ?>
                    <a href="?id=<?= $c['id']; ?>" class="customer-item <?= $selected_id == $c['id'] ? 'active' : ''; ?>">
                        <div class="customer-avatar">
                            <?= strtoupper(substr($c['nama'], 0, 1)); ?>
                            <?php if ($c['is_online'] == 1): ?>
                                <span class="online-dot"></span>
                            <?php endif; ?>
                        </div>
                        <div class="customer-info">
                            <h4>
                                <?= htmlspecialchars($c['nama']); ?>
                            </h4>
                            <p>
                                <?= htmlspecialchars($c['email']); ?>
                            </p>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="chat-area">
            <?php if ($selected_id > 0):
                $cust = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama, is_online, last_seen FROM users WHERE id = $selected_id"));
                ?>
                <div class="chat-header">
                    <div class="avatar">
                        <?= strtoupper(substr($cust['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3>
                            <?= htmlspecialchars($cust['nama']); ?>
                        </h3>
                        <span class="status <?= $cust['is_online'] == 1 ? 'online' : 'offline'; ?>" id="customerStatus">
                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                            <?= $cust['is_online'] == 1 ? 'Online' : 'Offline'; ?>
                        </span>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages"></div>

                <form class="chat-input" id="chatForm">
                    <input type="text" id="msgInput" placeholder="Ketik balasan untuk pelanggan..." autocomplete="off"
                        required>
                    <button type="submit" title="Kirim Balasan"><i class="bi bi-send-fill"></i></button>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>Pilih Percakapan</h3>
                    <p>Klik nama customer di panel kiri untuk membalas pesan masuk.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($selected_id > 0): ?>
        <script>
            const chatMessages = document.getElementById('chatMessages');
            const chatForm = document.getElementById('chatForm');
            const msgInput = document.getElementById('msgInput');
            const customerStatus = document.getElementById('customerStatus');
            const targetId = <?= $selected_id; ?>;
            let lastCount = 0;
            let isActive = 1;

            function formatTime(dateStr) {
                const date = new Date(dateStr);
                const options = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
                return date.toLocaleDateString('id-ID', options);
            }

            function loadChat() {
                fetch('../chat_api.php?action=fetch&target_id=' + targetId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.length !== lastCount) {
                            chatMessages.innerHTML = '';
                            data.forEach(msg => {
                                const div = document.createElement('div');
                                div.className = 'message ' + msg.sender_role;
                                div.innerHTML = msg.message + '<span class="time">' + formatTime(msg.created_at) + '</span>';
                                chatMessages.appendChild(div);
                            });
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                            lastCount = data.length;
                        }
                    });
            }

            function checkCustomerStatus() {
                fetch('../chat_api.php?action=check_status&target_id=' + targetId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.is_online == 1) {
                            customerStatus.className = 'status online';
                            customerStatus.innerHTML = '<i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Online';
                        } else {
                            customerStatus.className = 'status offline';
                            customerStatus.innerHTML = '<i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Offline';
                        }
                    });
            }

            function updatePresence() {
                fetch('../chat_api.php?action=status&active=' + isActive);
            }

            chatForm.addEventListener('submit', e => {
                e.preventDefault();
                const msg = msgInput.value.trim();
                if (!msg) return;
                fetch('../chat_api.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'message=' + encodeURIComponent(msg) + '&target_id=' + targetId
                }).then(() => {
                    msgInput.value = '';
                    loadChat();
                });
            });

            document.addEventListener('visibilitychange', () => {
                isActive = document.hidden ? 0 : 1;
                updatePresence();
            });

            loadChat(); checkCustomerStatus(); updatePresence();
            setInterval(loadChat, 3000);
            setInterval(checkCustomerStatus, 5000);
            setInterval(updatePresence, 10000);
        </script>
    <?php endif; ?>
</body>

</html>