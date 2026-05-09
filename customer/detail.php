<?php
session_start();
require_once '../config/koneksi.php';

/** @var mysqli $conn */

$product_id = (int) ($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

if (!$produk) {
    header('Location: katalog.php');
    exit;
}

$varians = json_decode($produk['varian'], true);
$selected_harga = $produk['harga_min'];
$selected_label = 'Standard';

if (is_array($varians) && count($varians) > 0) {
    $selected_harga = $varians[0]['harga'];
    $selected_label = $varians[0]['ram'] . 'GB/' . $varians[0]['rom'] . 'GB';
}

$is_logged_in = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    if ($action === 'beli_sekarang') {
        $qty = (int) ($_POST['qty'] ?? 1);
        if (!isset($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }
        if (isset($_SESSION['keranjang'][$product_id])) {
            $_SESSION['keranjang'][$product_id] += $qty;
        } else {
            $_SESSION['keranjang'][$product_id] = $qty;
        }
        header('Location: checkout.php');
        exit;
    } elseif ($action === 'add_comment') {
        $komentar = trim($_POST['komentar']);
        $parent_id = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

        if (!empty($komentar)) {
            $stmt_comment = mysqli_prepare($conn, "INSERT INTO comments (product_id, user_id, parent_id, komentar, created_at) VALUES (?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt_comment, "iiis", $product_id, $user_id, $parent_id, $komentar);
            mysqli_stmt_execute($stmt_comment);
        }
        header("Location: detail.php?id=$product_id");
        exit;
    } elseif ($action === 'edit_comment') {
        $comment_id = (int) $_POST['comment_id'];
        $komentar_baru = trim($_POST['komentar_baru']);

        if (!empty($komentar_baru)) {
            $stmt_edit = mysqli_prepare($conn, "UPDATE comments SET komentar = ? WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt_edit, "sii", $komentar_baru, $comment_id, $user_id);
            mysqli_stmt_execute($stmt_edit);
        }
        header("Location: detail.php?id=$product_id");
        exit;
    } elseif ($action === 'delete_comment') {
        $comment_id = (int) $_POST['comment_id'];
        $stmt_delete = mysqli_prepare($conn, "DELETE FROM comments WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt_delete, "ii", $comment_id, $user_id);
        mysqli_stmt_execute($stmt_delete);
        header("Location: detail.php?id=$product_id");
        exit;
    }
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
            transition: transform 0.3s, box-shadow 0.3s;
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
        }

        .btn-white-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
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
        }

        .variant-btn.active {
            border-color: var(--brand-purple);
            background: rgba(156, 39, 176, 0.05);
            color: var(--brand-purple);
            box-shadow: 0 4px 12px rgba(156, 39, 176, 0.1);
        }

        .qty-box {
            display: inline-flex;
            align-items: center;
            background: #F8FAFC;
            border-radius: 12px;
            border: 1px solid var(--border-subtle);
            overflow: hidden;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: transparent;
            font-weight: 800;
            color: var(--brand-navy);
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background: #E2E8F0;
        }

        .qty-input {
            width: 50px;
            border: none;
            background: transparent;
            text-align: center;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--text-dark);
            outline: none;
        }

        .total-box {
            background: #F8FAFC;
            border-radius: 16px;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0;
            border: 1px dashed var(--border-subtle);
        }

        .total-price {
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--brand-navy);
        }

        .btn-main {
            background: var(--brand-gradient);
            color: white;
            border: none;
            padding: 16px 25px;
            border-radius: 16px;
            font-weight: 700;
            transition: all 0.3s;
            flex: 2;
            font-size: 1.05rem;
        }

        .btn-main:hover {
            transform: translateY(-3px);
            box-shadow: var(--glow-shadow);
            color: white;
        }

        .btn-chat-seller {
            border: 2px solid var(--border-subtle);
            background: white;
            color: var(--text-muted);
            border-radius: 16px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-chat-seller:hover {
            border-color: var(--brand-purple);
            color: var(--brand-purple);
            background: #F8FAFC;
            transform: scale(1.05);
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

        .comment-form textarea {
            border-radius: 16px;
            padding: 15px;
            border: 1px solid var(--border-subtle);
            background: #F8FAFC;
            resize: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .comment-form textarea:focus {
            background: #FFFFFF;
            border-color: var(--brand-purple);
            box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1);
            outline: none;
        }

        .btn-comment {
            background: var(--brand-gradient);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s;
            margin-top: 15px;
        }

        .btn-comment:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow-shadow);
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
            margin-bottom: 10px;
        }

        .comment-actions {
            display: flex;
            gap: 15px;
        }

        .comment-action-btn {
            background: none;
            border: none;
            padding: 0;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .comment-action-btn:hover {
            color: var(--brand-purple);
        }

        .comment-action-btn.delete:hover {
            color: #DC2626;
        }

        .form-reply,
        .form-edit {
            display: none;
            margin-top: 15px;
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
                    <img src="../assets/img/logo.png" alt="Logo" class="brand-logo-img"
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
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2" href="katalog.php">
                            <i class="bi bi-grid-fill fs-5"></i> Katalog
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 position-relative" href="keranjang.php">
                            <i class="bi bi-cart3 fs-5"></i> Keranjang
                            <?php
                            $cart_count = isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0;
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="pesanan.php">
                                <i class="bi bi-receipt fs-5"></i> Pesanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2" href="chat.php">
                                <i class="bi bi-chat-dots fs-5"></i> Chat Seller
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="collapse navbar-collapse nav-zone-right" id="navbarNavRight">
                <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0 w-100 justify-content-lg-end">
                    <?php if (isset($_SESSION['user_id'])): ?>
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
                    <?php else: ?>
                        <a href="../auth/login.php" class="btn-white-nav text-decoration-none">
                            <i class="bi bi-box-arrow-in-right text-gradient"></i> <span class="text-gradient">Login</span>
                        </a>
                    <?php endif; ?>
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
                                alt="<?= htmlspecialchars($produk['nama_barang']) ?>">
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

                        <?php
                        $stok = (int) $produk['stok'];
                        if ($stok > 3) {
                            $stock_cls = 'stock-available';
                            $stock_txt = '<i class="bi bi-check-circle-fill"></i> Tersedia (Sisa ' . $stok . ' Unit)';
                        } elseif ($stok > 0) {
                            $stock_cls = 'stock-warning';
                            $stock_txt = '<i class="bi bi-exclamation-circle-fill"></i> Sisa ' . $stok . ' Unit';
                        } else {
                            $stock_cls = 'stock-empty';
                            $stock_txt = '<i class="bi bi-x-circle-fill"></i> Stok Habis';
                        }
                        ?>
                        <div class="stock-badge <?= $stock_cls ?>">
                            <?= $stock_txt ?>
                        </div>

                        <div class="mb-4">
                            <label class="d-block fw-bold mb-2 small text-muted">OPSI VARIAN:</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if (is_array($varians)):
                                    foreach ($varians as $i => $v): ?>
                                        <button type="button" class="variant-btn <?= ($i == 0) ? 'active' : '' ?>"
                                            onclick="updatePrice(<?= $v['harga'] ?>, this)"
                                            data-label="<?= htmlspecialchars($v['ram'] . '/' . $v['rom']) ?>">
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

                        <div class="mb-3 mt-4 border-top border-subtle pt-4">
                            <label class="d-block fw-bold mb-2 small text-muted">JUMLAH PEMBELIAN:</label>
                            <div class="qty-box">
                                <button class="qty-btn" onclick="updateQty(-1)">-</button>
                                <input type="number" id="qtyInput" value="1" readonly class="qty-input">
                                <button class="qty-btn" onclick="updateQty(1)">+</button>
                            </div>
                        </div>

                        <div class="total-box">
                            <span class="fw-bold text-muted">Total Bayar:</span>
                            <span class="total-price" id="total-price">Rp
                                <?= number_format($selected_harga, 0, ',', '.') ?>
                            </span>
                        </div>

                        <div class="d-flex gap-3">
                            <?php if ($is_logged_in): ?>
                                <form method="POST" style="flex: 1;" class="m-0 d-flex">
                                    <input type="hidden" name="action" value="beli_sekarang">
                                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                    <input type="hidden" name="qty" id="formQty" value="1">
                                    <input type="hidden" name="variant_label" id="formVariantLabel"
                                        value="<?= htmlspecialchars($selected_label) ?>">
                                    <button type="submit" class="btn-main w-100" <?= ($stok <= 0) ? 'disabled' : '' ?>>
                                        <i class="bi bi-cart-check-fill me-2"></i> Beli Sekarang
                                    </button>
                                </form>
                                <a href="chat.php?msg=<?= urlencode("Halo admin, saya tertarik dengan produk " . $produk['nama_barang']) ?>" class="btn-chat-seller"
                                    title="Tanya Seller tentang produk ini">
                                    <i class="bi bi-chat-dots fs-4"></i>
                                </a>
                            <?php else: ?>
                                <a href="../auth/login.php" class="btn-main text-center text-decoration-none d-block w-100">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Login untuk Membeli
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="comments-container">
            <h2 class="comments-header"><i class="bi bi-chat-right-text"></i> Ulasan & Diskusi Produk</h2>

            <?php if ($is_logged_in): ?>
                <form method="POST" class="comment-form mb-4">
                    <input type="hidden" name="action" value="add_comment">
                    <input type="hidden" name="parent_id" value="">
                    <textarea name="komentar" class="form-control w-100" rows="3"
                        placeholder="Tulis ulasan atau pertanyaan Anda di sini..." required></textarea>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn-comment"><i class="bi bi-send-fill me-2"></i> Kirim Ulasan</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-secondary border-0 text-center rounded-4 py-4 mb-4">
                    <i class="bi bi-lock-fill fs-3 text-muted mb-2 d-block"></i>
                    <p class="mb-0 fw-medium">Silakan <a href="../auth/login.php" class="text-decoration-none fw-bold"
                            style="color: var(--brand-purple);">Login</a> untuk menambahkan ulasan dan diskusi.</p>
                </div>
            <?php endif; ?>

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
                                <p id="comment_text_<?= $c['id'] ?>">
                                    <?= nl2br(htmlspecialchars($c['komentar'])) ?>
                                </p>

                                <?php if ($is_logged_in): ?>
                                    <div class="comment-actions">
                                        <button class="comment-action-btn" onclick="toggleReply(<?= $c['id'] ?>)"><i
                                                class="bi bi-reply-fill"></i> Balas</button>
                                        <?php if ($c['user_id'] == $_SESSION['user_id']): ?>
                                            <button class="comment-action-btn" onclick="toggleEdit(<?= $c['id'] ?>)"><i
                                                    class="bi bi-pencil-fill"></i> Edit</button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus komentar ini?')">
                                                <input type="hidden" name="action" value="delete_comment">
                                                <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="comment-action-btn delete"><i
                                                        class="bi bi-trash3-fill"></i> Hapus</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <form method="POST" class="comment-form form-reply" id="reply_form_<?= $c['id'] ?>">
                                        <input type="hidden" name="action" value="add_comment">
                                        <input type="hidden" name="parent_id" value="<?= $c['id'] ?>">
                                        <textarea name="komentar" class="form-control w-100" rows="2"
                                            placeholder="Tulis balasan untuk <?= htmlspecialchars($c['nama']) ?>..."
                                            required></textarea>
                                        <div class="d-flex justify-content-end gap-2 mt-2">
                                            <button type="button" class="btn btn-sm btn-light border fw-bold"
                                                onclick="toggleReply(<?= $c['id'] ?>)">Batal</button>
                                            <button type="submit" class="btn btn-sm btn-primary fw-bold"
                                                style="background: var(--brand-gradient); border:none;">Kirim Balasan</button>
                                        </div>
                                    </form>

                                    <?php if ($c['user_id'] == $_SESSION['user_id']): ?>
                                        <form method="POST" class="comment-form form-edit" id="edit_form_<?= $c['id'] ?>">
                                            <input type="hidden" name="action" value="edit_comment">
                                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                            <textarea name="komentar_baru" class="form-control w-100" rows="2"
                                                required><?= htmlspecialchars($c['komentar']) ?></textarea>
                                            <div class="d-flex justify-content-end gap-2 mt-2">
                                                <button type="button" class="btn btn-sm btn-light border fw-bold"
                                                    onclick="toggleEdit(<?= $c['id'] ?>)">Batal</button>
                                                <button type="submit" class="btn btn-sm btn-primary fw-bold"
                                                    style="background: var(--brand-gradient); border:none;">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
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
                                    <p id="comment_text_<?= $reply['id'] ?>">
                                        <?= nl2br(htmlspecialchars($reply['komentar'])) ?>
                                    </p>

                                    <?php if ($is_logged_in && $reply['user_id'] == $_SESSION['user_id']): ?>
                                        <div class="comment-actions">
                                            <button class="comment-action-btn" onclick="toggleEdit(<?= $reply['id'] ?>)"><i
                                                    class="bi bi-pencil-fill"></i> Edit</button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus balasan ini?')">
                                                <input type="hidden" name="action" value="delete_comment">
                                                <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                                <button type="submit" class="comment-action-btn delete"><i
                                                        class="bi bi-trash3-fill"></i> Hapus</button>
                                            </form>
                                        </div>

                                        <form method="POST" class="comment-form form-edit" id="edit_form_<?= $reply['id'] ?>">
                                            <input type="hidden" name="action" value="edit_comment">
                                            <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                            <textarea name="komentar_baru" class="form-control w-100" rows="2"
                                                required><?= htmlspecialchars($reply['komentar']) ?></textarea>
                                            <div class="d-flex justify-content-end gap-2 mt-2">
                                                <button type="button" class="btn btn-sm btn-light border fw-bold"
                                                    onclick="toggleEdit(<?= $reply['id'] ?>)">Batal</button>
                                                <button type="submit" class="btn btn-sm btn-primary fw-bold"
                                                    style="background: var(--brand-gradient); border:none;">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-chat-square-text" style="font-size: 3rem; opacity: 0.5;"></i>
                        <p class="mt-3 fw-medium">Belum ada ulasan untuk produk ini. Jadilah yang pertama!</p>
                    </div>
                <?php endif; ?>
            </div>
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
        let currentUnitPrice = <?= $selected_harga ?>;
        const qtyInput = document.getElementById('qtyInput');
        const totalText = document.getElementById('total-price');
        const formQty = document.getElementById('formQty');
        const formVariantLabel = document.getElementById('formVariantLabel');

        function updatePrice(harga, btn) {
            currentUnitPrice = harga;
            document.getElementById('current-price').innerText = 'Rp ' + harga.toLocaleString('id-ID');
            formVariantLabel.value = btn.getAttribute('data-label');
            document.querySelectorAll('.variant-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            calculate();
        }

        function updateQty(change) {
            let val = parseInt(qtyInput.value) + change;
            const max = <?= $stok ?>;
            if (max === 0) {
                qtyInput.value = 0; formQty.value = 0; totalText.innerText = 'Rp 0';
                return;
            }
            if (val < 1) val = 1;
            if (val > max) val = max;
            qtyInput.value = val;
            formQty.value = val;
            calculate();
        }

        function calculate() {
            if (<?= $stok ?> === 0) return;
            const total = currentUnitPrice * parseInt(qtyInput.value);
            totalText.innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        if (<?= $stok ?> === 0) {
            qtyInput.value = 0;
        }

        function toggleReply(id) {
            const form = document.getElementById('reply_form_' + id);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }

        function toggleEdit(id) {
            const form = document.getElementById('edit_form_' + id);
            const text = document.getElementById('comment_text_' + id);
            if (form.style.display === 'block') {
                form.style.display = 'none';
                text.style.display = 'block';
            } else {
                form.style.display = 'block';
                text.style.display = 'none';
            }
        }
    </script>
</body>

</html>