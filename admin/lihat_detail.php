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
                if (!empty($profile_picture) && file_exists($upload_path . $profile_picture)) {
                    unlink($upload_path . $profile_picture);
                }
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
    }
    header("Location: lihat_detail.php?id=" . (int) $_GET['id']);
    exit;
}

$product_id = (int) ($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

if (!$produk) {
    header('Location: lihat_toko.php');
    exit;
}

$varians = json_decode($produk['varian'], true);
$selected_harga = $produk['harga_min'];
$selected_stok = $produk['stok'];
$selected_label = 'Standard';

if (is_array($varians) && count($varians) > 0) {
    $selected_harga = $varians[0]['harga'];
    $selected_stok = $varians[0]['stok'] ?? 0;
    $selected_label = $varians[0]['ram'] . 'GB/' . $varians[0]['rom'] . 'GB';
}

$comments_raw = [];
$stmt_get = mysqli_prepare($conn, "SELECT c.*, u.nama FROM comments c JOIN users u ON c.user_id = u.id WHERE c.product_id = ? ORDER BY c.created_at ASC");
if ($stmt_get) {
    mysqli_stmt_bind_param($stmt_get, "i", $product_id);
    mysqli_stmt_execute($stmt_get);
    $res_comments = mysqli_stmt_get_result($stmt_get);
    while ($row = mysqli_fetch_assoc($res_comments)) {
        $comments_raw[] = $row;
    }
}

$comments_tree = [];
$replies = [];
foreach ($comments_raw as $c) {
    if ($c['parent_id'] === null) {
        $c['replies'] = [];
        $comments_tree[$c['id']] = $c;
    } else {
        $replies[] = $c;
    }
}
foreach ($replies as $r) {
    if (isset($comments_tree[$r['parent_id']])) {
        $comments_tree[$r['parent_id']]['replies'][] = $r;
    }
}

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
    <title>
        <?= htmlspecialchars($produk['nama_barang']) ?> - 7CellX
    </title>
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

        .detail-card {
            background: var(--bg-card);
            border-radius: 30px;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .image-container {
            background: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            height: 100%;
            border-right: 1px solid var(--border-subtle);
        }

        .image-frame {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: 24px;
            width: 100%;
            max-width: 400px;
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }

        .product-img {
            width: 80%;
            height: 80%;
            object-fit: contain;
            filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.1));
            transition: transform 0.4s ease;
        }

        .product-img:hover {
            transform: scale(1.05);
        }

        .info-container {
            padding: 40px 50px;
        }

        .category-tag {
            background: rgba(156, 39, 176, 0.1);
            color: var(--brand-purple);
            font-weight: 800;
            font-size: 0.75rem;
            padding: 6px 14px;
            border-radius: 10px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }

        .product-title {
            font-weight: 800;
            font-size: 2.2rem;
            color: var(--text-dark);
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .price-main {
            font-weight: 800;
            font-size: 2rem;
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 25px;
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 700;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        .stock-available {
            background: #ECFDF5;
            color: #059669;
        }

        .stock-warning {
            background: #FFFBEB;
            color: #D97706;
        }

        .stock-empty {
            background: #FEF2F2;
            color: #DC2626;
        }

        .variant-btn {
            border: 2px solid var(--border-subtle);
            background: white;
            border-radius: 12px;
            padding: 10px 18px;
            font-weight: 700;
            transition: all 0.2s;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .variant-btn.active {
            border-color: var(--brand-purple);
            background: rgba(156, 39, 176, 0.05);
            color: var(--brand-purple);
            box-shadow: 0 4px 12px rgba(156, 39, 176, 0.1);
        }

        .btn-main {
            background: var(--border-subtle);
            color: var(--text-muted);
            border: none;
            padding: 16px 25px;
            border-radius: 16px;
            font-weight: 700;
            width: 100%;
            font-size: 1.05rem;
            cursor: not-allowed;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .comments-container {
            background: var(--bg-card);
            border-radius: 30px;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--card-shadow);
            padding: 40px;
            margin-bottom: 60px;
        }

        .comments-header {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--brand-navy);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .comment-list {
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .comment-card {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            gap: 15px;
            position: relative;
        }

        .comment-card.reply {
            margin-left: 50px;
            background: #F8FAFC;
            border-left: 4px solid var(--brand-purple);
        }

        .comment-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--brand-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .comment-body {
            flex: 1;
        }

        .comment-body h5 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .comment-body .time {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 10px;
            display: block;
        }

        .comment-body p {
            color: var(--text-dark);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 0;
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

        footer {
            margin-top: auto;
            background: var(--brand-gradient);
            padding: 20px 0;
            text-align: center;
            color: white;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container d-lg-flex px-4">
            <div class="nav-zone-left" style="flex: 1;">
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

            <div class="collapse navbar-collapse nav-zone-center justify-content-center" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-2 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="index.php"><i
                                class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="produk.php"><i
                                class="bi bi-box-seam"></i> Produk</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="pesanan.php"><i
                                class="bi bi-receipt"></i> Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="chat.php"><i
                                class="bi bi-chat-dots"></i> Chat</a></li>
                    <li class="nav-item"><a class="nav-link active d-flex align-items-center gap-2"
                            href="lihat_toko.php"><i class="bi bi-shop"></i> Lihat Toko</a></li>
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
                        <li>
                            <a class="dropdown-item text-danger fw-bold d-flex align-items-center"
                                href="../auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container flex-grow-1">
        <div class="detail-card">
            <div class="row g-0">
                <div class="col-lg-5">
                    <div class="image-container">
                        <div class="image-frame">
                            <img src="../uploads/<?= htmlspecialchars($produk['gambar']) ?>" class="product-img"
                                alt="<?= htmlspecialchars($produk['nama_barang']) ?>"
                                onerror="this.src='https://via.placeholder.com/400x400/F8FAFC/9C27B0?text=No+Image'">
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="info-container">
                        <span class="category-tag">
                            <?= htmlspecialchars($produk['kategori']) ?>
                        </span>
                        <h1 class="product-title">
                            <?= htmlspecialchars($produk['nama_barang']) ?>
                        </h1>

                        <div class="price-main" id="current-price">Rp
                            <?= number_format($selected_harga, 0, ',', '.') ?>
                        </div>

                        <div class="stock-badge <?= $selected_stok > 0 ? 'stock-available' : 'stock-empty' ?>"
                            id="stock-status">
                            <?php if ($selected_stok > 0): ?>
                                <i class="bi bi-check-circle-fill"></i> Tersedia (Sisa
                                <?= $selected_stok ?> Unit)
                            <?php else: ?>
                                <i class="bi bi-x-circle-fill"></i> Stok Varian Ini Habis
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="d-block fw-bold mb-2 small text-muted">OPSI VARIAN:</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if (is_array($varians)):
                                    foreach ($varians as $i => $v): ?>
                                        <button type="button" class="variant-btn <?= ($i == 0) ? 'active' : '' ?>"
                                            onclick="updatePrice(<?= $i ?>)" id="btnVar_<?= $i ?>">
                                            <?= htmlspecialchars($v['ram']) ?>/
                                            <?= htmlspecialchars($v['rom']) ?> GB
                                        </button>
                                    <?php endforeach; endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="d-block fw-bold mb-2 small text-muted">DESKRIPSI PRODUK:</label>
                            <div class="text-secondary"
                                style="font-size: 0.95rem; line-height: 1.6; max-height: 150px; overflow-y: auto; padding-right: 10px;">
                                <?= nl2br(htmlspecialchars($produk['deskripsi'] ?? 'Tidak ada deskripsi tersedia.')) ?>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-top border-subtle">
                            <button class="btn-main" disabled>
                                <i class="bi bi-eye-fill"></i> Mode Pratinjau Admin
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="comments-container">
            <h2 class="comments-header"><i class="bi bi-chat-right-text"></i> Ulasan & Diskusi Produk</h2>

            <div class="alert border-0 text-center rounded-4 py-4 mb-4"
                style="background: rgba(156, 39, 176, 0.05); border: 1px dashed var(--brand-purple) !important;">
                <i class="bi bi-info-circle-fill fs-3 mb-2 d-block" style="color: var(--brand-purple);"></i>
                <p class="mb-0 fw-medium text-dark">Mode Admin: Hanya dapat melihat dan merespon ulasan pelanggan di halaman
                    ini.</p>
            </div>

            <div class="comment-list">
                <?php if (count($comments_tree) > 0): ?>
                    <?php foreach ($comments_tree as $c): ?>
                        <div class="comment-card">
                            <div class="comment-avatar">
                                <?= strtoupper(substr($c['nama'], 0, 1)) ?>
                            </div>
                            <div class="comment-body">
                                <h5>
                                    <?= htmlspecialchars($c['nama']) ?>
                                </h5>
                                <span class="time">
                                    <?= date('d M Y, H:i', strtotime($c['created_at'])) ?>
                                </span>
                                <p>
                                    <?= nl2br(htmlspecialchars($c['komentar'])) ?>
                                </p>
                            </div>
                        </div>

                        <?php foreach ($c['replies'] as $reply): ?>
                            <div class="comment-card reply">
                                <div class="comment-avatar" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                    <?= strtoupper(substr($reply['nama'], 0, 1)) ?>
                                </div>
                                <div class="comment-body">
                                    <h5>
                                        <?= htmlspecialchars($reply['nama']) ?>
                                    </h5>
                                    <span class="time">
                                        <?= date('d M Y, H:i', strtotime($reply['created_at'])) ?>
                                    </span>
                                    <p>
                                        <?= nl2br(htmlspecialchars($reply['komentar'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-chat-square-text" style="font-size: 3rem; opacity: 0.5;"></i>
                        <p class="mt-3 fw-medium">Belum ada ulasan untuk produk ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center small fw-medium opacity-75">
            <p class="mb-0">&copy;
                <?= date('Y') ?> 7CellX
            </p>
        </div>
    </footer>

    <div class="custom-modal-overlay" id="settingsModal">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const varianData = <?= $produk['varian'] ?>;
        const stockStatus = document.getElementById('stock-status');

        function updatePrice(index) {
            const selected = varianData[index];
            document.getElementById('current-price').innerText = 'Rp ' + selected.harga.toLocaleString('id-ID');

            document.querySelectorAll('.variant-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('btnVar_' + index).classList.add('active');

            if (selected.stok > 0) {
                stockStatus.className = 'stock-badge stock-available';
                stockStatus.innerHTML = '<i class="bi bi-check-circle-fill"></i> Tersedia (Sisa ' + selected.stok + ' Unit)';
            } else {
                stockStatus.className = 'stock-badge stock-empty';
                stockStatus.innerHTML = '<i class="bi bi-x-circle-fill"></i> Stok Varian Ini Habis';
            }
        }

        function openSettingsModal() { document.getElementById('settingsModal').style.display = 'flex'; }
        function closeSettingsModal() { document.getElementById('settingsModal').style.display = 'none'; }
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function () { document.getElementById('previewPP').src = reader.result; }
            if (event.target.files[0]) reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>

</html>