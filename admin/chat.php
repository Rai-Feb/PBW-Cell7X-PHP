<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $new_password = $_POST['new_password'] ?? '';

    $stmt_get = mysqli_prepare($conn, "SELECT profile_picture FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt_get, "i", $user_id);
    mysqli_stmt_execute($stmt_get);
    $res = mysqli_stmt_get_result($stmt_get);
    $current_user = mysqli_fetch_assoc($res);
    $profile_picture = $current_user['profile_picture'] ?? '';

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = 'pp_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../uploads/profiles/';
            if (!is_dir($upload_path))
                mkdir($upload_path, 0777, true);
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path . $new_filename)) {
                if (!empty($profile_picture) && file_exists($upload_path . $profile_picture))
                    unlink($upload_path . $profile_picture);
                $profile_picture = $new_filename;
            }
        }
    }

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update = mysqli_prepare($conn, "UPDATE users SET nama = ?, username = ?, profile_picture = ?, password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, "ssssi", $nama, $username, $profile_picture, $hashed_password, $user_id);
    } else {
        $stmt_update = mysqli_prepare($conn, "UPDATE users SET nama = ?, username = ?, profile_picture = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, "sssi", $nama, $username, $profile_picture, $user_id);
    }

    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['nama'] = $nama;
        $_SESSION['username'] = $username;
        $_SESSION['profile_picture'] = $profile_picture;
        $_SESSION['success_msg'] = "Profil berhasil diperbarui!";
    }
    header("Location: chat.php");
    exit;
}

$customers = mysqli_query($conn, "SELECT DISTINCT u.id, u.nama, u.username, u.is_online, u.last_seen FROM users u JOIN chats c ON u.id = c.user_id WHERE u.role = 'customer' ORDER BY (SELECT MAX(created_at) FROM chats WHERE user_id = u.id) DESC");
$selected_id = (int) ($_GET['id'] ?? 0);

$active_user = null;
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
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }

        .btn-white-nav:hover {
            transform: translateY(-2px);
            color: var(--brand-pink);
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
            cursor: pointer;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: #F8FAFC;
            color: var(--brand-purple);
        }

        .dropdown-item.text-danger:hover {
            background-color: #FEF2F2;
            color: #DC2626 !important;
        }

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

        .custom-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .custom-modal-box {
            background: var(--bg-card);
            width: 90%;
            max-width: 500px;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transform: translateY(20px);
            animation: modalFadeIn 0.3s forwards;
            text-align: left;
        }

        @keyframes modalFadeIn {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .custom-modal-title {
            font-weight: 800;
            color: var(--brand-navy);
            font-size: 1.25rem;
            margin-bottom: 10px;
        }

        .custom-modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-modal-cancel {
            flex: 1;
            padding: 12px;
            border-radius: 14px;
            background: white;
            border: 2px solid var(--border-subtle);
            color: var(--text-muted);
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-align: center;
        }

        .btn-modal-cancel:hover {
            border-color: var(--text-dark);
            color: var(--text-dark);
        }

        .btn-modal-confirm {
            flex: 1;
            padding: 12px;
            border-radius: 14px;
            background: var(--brand-gradient);
            border: none;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: var(--glow-shadow);
            text-align: center;
        }

        .btn-modal-confirm:hover {
            transform: translateY(-2px);
        }

        .settings-form-label {
            font-weight: 700;
            color: var(--text-muted);
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
        }

        .settings-input {
            width: 100%;
            padding: 12px 18px;
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            background: #F8FAFC;
            font-weight: 500;
            transition: all 0.3s;
            outline: none;
            margin-bottom: 20px;
        }

        .settings-input:focus {
            border-color: var(--brand-purple);
            box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1);
            background: white;
        }

        .settings-avatar-preview {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--brand-pink);
            margin-bottom: 15px;
            box-shadow: var(--glow-shadow);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container d-lg-flex px-4">
            <div class="nav-zone-left" style="flex: 1;">
                <a class="brand-pill" href="index.php">
                    <img src="../assets/img/logo.png" alt="Logo" class="brand-logo-img"
                        onerror="this.src='https://via.placeholder.com/40x40/0F172A/FFFFFF?text=7C'">
                    <span class="text-gradient fw-bold fs-5 mb-0" style="letter-spacing: -0.5px;">7CellX Admin</span>
                </a>
                <button class="navbar-toggler ms-auto border-0 shadow-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon" style="filter: brightness(0) invert(1);"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse nav-zone-center justify-content-center" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-2 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="index.php"><i
                                class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="produk.php"><i
                                class="bi bi-box-seam"></i> Produk</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="pesanan.php"><i
                                class="bi bi-receipt"></i> Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link active d-flex align-items-center gap-2" href="chat.php"><i
                                class="bi bi-chat-dots"></i> Chat</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2"
                            href="../customer/katalog.php" target="_blank"><i class="bi bi-shop"></i> Lihat Toko</a>
                    </li>
                </ul>
            </div>
            <div class="collapse navbar-collapse nav-zone-right justify-content-end" id="navbarNavRight"
                style="flex: 1;">
                <div class="dropdown">
                    <button class="btn-white-nav dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?php if (!empty($active_user['profile_picture'])): ?>
                            <img src="../uploads/profiles/<?= htmlspecialchars($active_user['profile_picture']) ?>"
                                class="user-nav-avatar">
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-5 text-gradient"></i>
                        <?php endif; ?>
                        <span class="text-gradient">
                            <?= htmlspecialchars($active_user['username'] ?? $_SESSION['username'] ?? 'Admin') ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button class="dropdown-item d-flex align-items-center" type="button"
                                onclick="openSettingsModal()">
                                <i class="bi bi-gear me-2 text-muted"></i>Settings
                            </button>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger fw-bold d-flex align-items-center"
                                href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success rounded-4 fw-bold mb-0">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['success_msg']) ?>
            </div>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

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
                            <p>@
                                <?= htmlspecialchars($c['username']); ?>
                            </p>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="chat-area">
            <?php if ($selected_id > 0):
                $cust = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama, username, is_online, last_seen FROM users WHERE id = $selected_id"));
                ?>
                <div class="chat-header">
                    <div class="avatar">
                        <?= strtoupper(substr($cust['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3>
                            <?= htmlspecialchars($cust['nama']); ?> (@
                            <?= htmlspecialchars($cust['username']); ?>)
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

    <?php if ($active_user): ?>
        <div class="custom-modal-overlay" id="settingsModal" style="z-index: 99999;">
            <div class="custom-modal-box custom-settings-box">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-subtle pb-3">
                    <h3 class="custom-modal-title m-0"><i class="bi bi-gear-fill me-2"></i> Pengaturan Profil</h3>
                    <button type="button" class="btn-close shadow-none" onclick="closeSettingsModal()"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="text-center mb-4">
                        <img id="previewPP"
                            src="<?= !empty($active_user['profile_picture']) ? '../uploads/profiles/' . htmlspecialchars($active_user['profile_picture']) : 'https://via.placeholder.com/90/F8FAFC/9C27B0?text=PP' ?>"
                            class="settings-avatar-preview">
                        <label class="form-label d-block text-center" style="font-size: 0.8rem;">GANTI FOTO PROFIL</label>
                        <input type="file" name="profile_picture" id="inputPP" class="form-control form-control-sm mx-auto"
                            accept="image/*" style="max-width: 250px; font-size: 0.8rem;" onchange="previewImage(event)">
                    </div>
                    <label class="settings-form-label">NAMA LENGKAP</label>
                    <input type="text" name="nama" class="settings-input"
                        value="<?= htmlspecialchars($active_user['nama']) ?>" required>
                    <label class="settings-form-label">USERNAME</label>
                    <input type="text" name="username" class="settings-input"
                        value="<?= htmlspecialchars($active_user['username']) ?>" required>
                    <hr class="my-4 border-subtle">
                    <h6 class="fw-bold mb-3" style="color: var(--brand-navy);">Keamanan</h6>
                    <label class="settings-form-label">PASSWORD BARU (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="new_password" class="settings-input" placeholder="Masukkan password baru">
                    <div class="custom-modal-actions mt-2">
                        <button type="button" class="btn-modal-cancel" onclick="closeSettingsModal()">Batal</button>
                        <button type="submit" class="btn-modal-confirm"><i class="bi bi-save-fill me-2"></i> Simpan
                            Profil</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openSettingsModal() { document.getElementById('settingsModal').style.display = 'flex'; }
        function closeSettingsModal() { document.getElementById('settingsModal').style.display = 'none'; }
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function () { document.getElementById('previewPP').src = reader.result; }
            if (event.target.files[0]) reader.readAsDataURL(event.target.files[0]);
        }

        <?php if ($selected_id > 0): ?>
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
        <?php endif; ?>
    </script>
</body>

</html>