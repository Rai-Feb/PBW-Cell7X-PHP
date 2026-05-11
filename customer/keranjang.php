<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Perbarui Kuantitas
    if (isset($_POST['update'])) {
        if (isset($_POST['qty']) && is_array($_POST['qty'])) {
            foreach ($_POST['qty'] as $key => $qty) {
                $qty = (int) $qty;
                if ($qty > 0) {
                    $_SESSION['keranjang'][$key] = $qty;
                } else {
                    unset($_SESSION['keranjang'][$key]);
                }
            }
        }
        header('Location: keranjang.php');
        exit;
    }

    // B. Hapus Item
    if (isset($_POST['hapus'])) {
        $key = $_POST['hapus_key'];
        unset($_SESSION['keranjang'][$key]);
        header('Location: keranjang.php');
        exit;
    }

    // C. Checkout Item Terpilih
    if (isset($_POST['checkout'])) {
        if (!empty($_POST['selected_items'])) {
            $_SESSION['checkout_items'] = $_POST['selected_items'];
            header('Location: checkout.php');
            exit;
        } else {
            $_SESSION['error_msg'] = "Pilih minimal satu produk untuk di-checkout.";
            header('Location: keranjang.php');
            exit;
        }
    }
}

$keranjang = $_SESSION['keranjang'] ?? [];
$items = [];
$total = 0;

if (!empty($keranjang)) {
    // AUTO-HEAL: Memperbaiki keranjang lama yang tidak memiliki index varian
    $healed_cart = [];
    $needs_healing = false;
    foreach ($keranjang as $key => $qty) {
        $parts = explode('_', $key);
        if (count($parts) < 2) {
            $healed_cart[$parts[0] . '_0'] = $qty;
            $needs_healing = true;
        } else {
            $healed_cart[$key] = $qty;
        }
    }
    if ($needs_healing) {
        $_SESSION['keranjang'] = $healed_cart;
        $keranjang = $healed_cart;
    }

    // Ambil Data Produk
    $unique_pids = array_unique(array_map(function ($k) {
        return explode('_', $k)[0]; }, array_keys($keranjang)));
    $products_data = [];

    if (!empty($unique_pids)) {
        $ids = implode(',', $unique_pids);
        $res = mysqli_query($conn, "SELECT * FROM products WHERE id IN ($ids)");
        while ($row = mysqli_fetch_assoc($res)) {
            $products_data[$row['id']] = $row;
        }
    }

    // Ekstraksi Varian spesifik
    foreach ($keranjang as $key => $qty) {
        list($pid, $v_idx) = explode('_', $key);
        if (isset($products_data[$pid])) {
            $p = $products_data[$pid];
            $var_arr = json_decode($p['varian'], true);
            if (isset($var_arr[$v_idx])) {
                $v_data = $var_arr[$v_idx];

                $p['cart_key'] = $key;
                $p['qty'] = $qty;
                $p['harga_satuan'] = $v_data['harga'];
                $p['stok_varian'] = $v_data['stok'];
                $p['label_varian'] = $v_data['ram'] . '/' . $v_data['rom'] . ' GB';
                $p['subtotal'] = $v_data['harga'] * $qty;

                $total += $p['subtotal'];
                $items[] = $p;
            }
        }
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
    <title>Keranjang - 7CellX</title>
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

        .page-header {
            background: var(--brand-gradient);
            padding: 50px 0 40px;
            margin-bottom: 40px;
            color: white;
            border-radius: 0 0 40px 40px;
            box-shadow: var(--glow-shadow);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        .cart-wrapper {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
            margin-bottom: 60px;
        }

        .cart-items {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-subtle);
        }

        .cart-item {
            display: grid;
            grid-template-columns: auto 100px 1fr auto;
            gap: 24px;
            padding: 24px 0;
            border-bottom: 1px solid var(--border-subtle);
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .image-frame {
            background: #F8FAFC;
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            width: 100px;
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .image-frame img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.1));
        }

        .item-details h3 {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .variant-pill {
            background: #F4F7FE;
            color: var(--text-dark);
            border: 1px solid var(--border-subtle);
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-flex;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 1.15rem;
            font-weight: 800;
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
        }

        .qty-control {
            display: inline-flex;
            align-items: center;
            background: #F8FAFC;
            border-radius: 10px;
            border: 1px solid var(--border-subtle);
            overflow: hidden;
        }

        /* PERBAIKAN: Tombol QTY dan Hapus Diperkecil */
        .qty-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            font-weight: 800;
            color: var(--brand-navy);
            transition: background 0.2s;
            font-size: 1rem;
        }

        .qty-btn:hover {
            background: #E2E8F0;
        }

        .qty-input {
            width: 40px;
            border: none;
            background: transparent;
            text-align: center;
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--text-dark);
            outline: none;
        }

        .item-subtotal {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
        }

        .item-subtotal h4 {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 4px;
            font-weight: 600;
        }

        .item-subtotal p {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .btn-remove {
            background: #FEF2F2;
            color: #DC2626;
            border: none;
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .btn-remove:hover {
            background: #DC2626;
            color: white;
        }

        .cart-summary {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-subtle);
            position: sticky;
            top: 120px;
            height: fit-content;
        }

        .summary-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--brand-navy);
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .summary-label {
            color: var(--text-muted);
            font-weight: 600;
        }

        .summary-value {
            font-weight: 800;
            color: var(--text-dark);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 2px dashed var(--border-subtle);
            margin-top: 16px;
            margin-bottom: 24px;
            align-items: center;
        }

        .summary-total .summary-label {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .summary-total .summary-value {
            font-size: 1.4rem;
            font-weight: 800;
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* PERBAIKAN: Tombol Checkout Diperkecil */
        .btn-primary {
            background: var(--brand-gradient);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s;
            font-size: 0.95rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--glow-shadow);
            color: white;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-outline {
            background: transparent;
            color: var(--text-muted);
            border: 2px solid var(--border-subtle);
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            margin-top: 12px;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: #F8FAFC;
            color: var(--brand-purple);
            border-color: var(--brand-purple);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 30px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-subtle);
            margin-bottom: 60px;
        }

        .empty-state i {
            font-size: 5rem;
            color: #CBD5E1;
            margin-bottom: 20px;
            display: block;
        }

        footer {
            margin-top: auto;
            background: var(--brand-gradient);
            padding: 20px 0;
            text-align: center;
            color: white;
        }

        @media (max-width: 1024px) {
            .cart-wrapper {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

            .cart-item {
                grid-template-columns: auto 80px 1fr;
                gap: 16px;
                align-items: start;
            }

            .item-subtotal {
                grid-column: 1 / -1;
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                border-top: 1px dashed var(--border-subtle);
                padding-top: 15px;
            }

            .item-subtotal h4,
            .item-subtotal p {
                margin-bottom: 0;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container d-lg-flex px-4">
            <div class="nav-zone-left">
                <a class="brand-pill" href="katalog.php">
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
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="katalog.php"><i
                                class="bi bi-grid-fill fs-5"></i> Katalog</a></li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 position-relative active"
                            href="keranjang.php">
                            <i class="bi bi-cart3 fs-5"></i> Keranjang
                            <?php $cart_count = isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0;
                            if ($cart_count > 0): ?>
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
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="chat.php"><i
                                class="bi bi-chat-dots fs-5"></i> Chat Seller</a></li>
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
                            <li><a class="dropdown-item text-danger fw-bold d-flex align-items-center"
                                    href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container text-center">
            <h1>Keranjang Belanja</h1>
            <p class="mb-0 opacity-75">Selesaikan pesanan Anda sebelum kehabisan stok.</p>
        </div>
    </div>

    <div class="container flex-grow-1">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success rounded-4 fw-bold mt-4 mb-4">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['success_msg']) ?>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger rounded-4 fw-bold mt-4 mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['error_msg']) ?>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="empty-state">
                <i class="bi bi-cart-x"></i>
                <h3 class="fw-bold" style="color: var(--brand-navy);">Keranjang Anda Kosong</h3>
                <p class="text-muted mb-4">Mulai eksplorasi dan temukan smartphone impian Anda di katalog kami.</p>
                <a href="katalog.php" class="btn-primary d-inline-flex m-auto" style="max-width: 250px;">
                    <i class="bi bi-grid-fill"></i> Eksplorasi Katalog
                </a>
            </div>
        <?php else: ?>
            <form method="POST" id="formKeranjang">
                <div class="cart-wrapper">
                    <div class="cart-items">
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom border-subtle">
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="checkbox" id="selectAll" checked
                                    onchange="toggleSelectAll(this)"
                                    style="transform: scale(1.2); cursor: pointer; border-color: var(--brand-purple);">
                                <label class="form-check-label fw-bold text-dark ms-2" for="selectAll"
                                    style="cursor: pointer;">Pilih Semua Produk</label>
                            </div>
                        </div>

                        <?php foreach ($items as $item): ?>
                            <div class="cart-item">
                                <div class="form-check d-flex align-items-center justify-content-center">
                                    <input class="form-check-input item-check shadow-none m-0" type="checkbox"
                                        name="selected_items[]" value="<?= $item['cart_key'] ?>"
                                        data-price="<?= $item['subtotal'] ?>" checked
                                        style="transform: scale(1.3); cursor: pointer; border-color: var(--brand-purple);">
                                </div>
                                <div class="image-frame">
                                    <img src="<?= !empty($item['gambar']) ? '../uploads/' . $item['gambar'] : 'https://via.placeholder.com/200x200/F8FAFC/9C27B0?text=No+Image' ?>"
                                        alt="<?= htmlspecialchars($item['nama_barang']) ?>">
                                </div>
                                <div class="item-details">
                                    <h3>
                                        <?= htmlspecialchars($item['nama_barang']) ?>
                                    </h3>
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <span class="variant-pill">
                                            <?= htmlspecialchars($item['label_varian']) ?>
                                        </span>
                                    </div>
                                    <div class="item-price">Rp
                                        <?= number_format($item['harga_satuan'], 0, ',', '.') ?>
                                    </div>
                                    <div class="qty-control">
                                        <button type="button" class="qty-btn"
                                            onclick="updateQty('<?= $item['cart_key'] ?>', -1)"><i
                                                class="bi bi-dash"></i></button>
                                        <input type="number" name="qty[<?= $item['cart_key'] ?>]" value="<?= $item['qty'] ?>"
                                            min="1" max="<?= $item['stok_varian'] ?>" data-max="<?= $item['stok_varian'] ?>"
                                            class="qty-input" readonly>
                                        <button type="button" class="qty-btn"
                                            onclick="updateQty('<?= $item['cart_key'] ?>', 1)"><i
                                                class="bi bi-plus"></i></button>
                                    </div>
                                    <div
                                        class="mt-2 small <?= $item['stok_varian'] <= 5 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                        Sisa stok:
                                        <?= $item['stok_varian'] ?>
                                    </div>
                                </div>
                                <div class="item-subtotal">
                                    <div class="d-flex flex-column align-items-end justify-content-center h-100">
                                        <h4>Subtotal</h4>
                                        <p>Rp
                                            <?= number_format($item['subtotal'], 0, ',', '.') ?>
                                        </p>
                                        <button type="button" class="btn-remove"
                                            onclick="hapusItem('<?= $item['cart_key'] ?>')">
                                            <i class="bi bi-trash3-fill"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top border-subtle">
                            <button type="submit" name="update" style="display: none;" id="btnUpdateCart">Update</button>
                        </div>
                    </div>

                    <div class="cart-summary">
                        <h3 class="summary-title">Ringkasan Pesanan</h3>
                        <div class="summary-row">
                            <span class="summary-label">Produk Dipilih</span>
                            <span class="summary-value" id="summary-total-item">
                                <?= count($items) ?> Produk
                            </span>
                        </div>
                        <div class="summary-total">
                            <span class="summary-label">Total Bayar</span>
                            <span class="summary-value" id="summary-total-price">Rp
                                <?= number_format($total, 0, ',', '.') ?>
                            </span>
                        </div>
                        <button type="submit" name="checkout" class="btn-primary mb-2" id="btn-checkout">
                            <i class="bi bi-lock-fill"></i> Checkout Sekarang
                        </button>
                        <a href="katalog.php" class="btn-outline">
                            <i class="bi bi-arrow-left"></i> Lanjut Belanja
                        </a>
                    </div>
                </div>
            </form>

            <form method="POST" id="formHapus" style="display: none;">
                <input type="hidden" name="hapus" value="1">
                <input type="hidden" name="hapus_key" id="inputHapusKey">
            </form>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container small fw-medium opacity-75">&copy;
            <?= date('Y') ?> 7CellX. Engineered with precision.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateQty(key, change) {
            saveCheckedState();
            const input = document.querySelector('input[name="qty[' + key + ']"]');
            const maxStock = parseInt(input.getAttribute('data-max'));
            let newValue = parseInt(input.value) + change;

            if (newValue < 1) newValue = 1;
            if (newValue > maxStock) newValue = maxStock;
            input.value = newValue;

            const form = document.getElementById('formKeranjang');
            const hiddenUpdate = document.createElement('input');
            hiddenUpdate.type = 'hidden';
            hiddenUpdate.name = 'update';
            hiddenUpdate.value = '1';
            form.appendChild(hiddenUpdate);
            form.submit();
        }

        function hapusItem(key) {
            if (confirm('Hapus item ini dari keranjang?')) {
                document.getElementById('inputHapusKey').value = key;
                document.getElementById('formHapus').submit();
            }
        }

        function calculateSummary() {
            let totalBayar = 0; let totalItem = 0;
            document.querySelectorAll('.item-check:checked').forEach(cb => {
                totalBayar += parseInt(cb.getAttribute('data-price'));
                totalItem += 1;
            });
            const summaryItem = document.getElementById('summary-total-item');
            const summaryPrice = document.getElementById('summary-total-price');
            const btnCheckout = document.getElementById('btn-checkout');
            if (summaryItem) summaryItem.innerText = totalItem + ' Produk';
            if (summaryPrice) summaryPrice.innerText = 'Rp ' + totalBayar.toLocaleString('id-ID');
            if (btnCheckout) btnCheckout.disabled = totalItem === 0;
        }

        function toggleSelectAll(master) {
            document.querySelectorAll('.item-check').forEach(cb => cb.checked = master.checked);
            calculateSummary(); saveCheckedState();
        }

        function saveCheckedState() {
            const checked = Array.from(document.querySelectorAll('.item-check:checked')).map(cb => cb.value);
            sessionStorage.setItem('cartCheckedItems', JSON.stringify(checked));
        }

        document.querySelectorAll('.item-check').forEach(cb => {
            cb.addEventListener('change', () => {
                const total = document.querySelectorAll('.item-check').length;
                const checked = document.querySelectorAll('.item-check:checked').length;
                const selectAll = document.getElementById('selectAll');
                if (selectAll) selectAll.checked = (total === checked);
                calculateSummary(); saveCheckedState();
            });
        });

        window.addEventListener('DOMContentLoaded', () => {
            const checked = JSON.parse(sessionStorage.getItem('cartCheckedItems'));
            if (checked && document.querySelectorAll('.item-check').length > 0) {
                document.querySelectorAll('.item-check').forEach(cb => {
                    if (checked.includes(cb.value)) cb.checked = true;
                });
                const total = document.querySelectorAll('.item-check').length;
                const cCount = document.querySelectorAll('.item-check:checked').length;
                const selectAll = document.getElementById('selectAll');
                if (selectAll) selectAll.checked = (total === cCount && total > 0);
            }
            calculateSummary();
        });
    </script>
</body>

</html>