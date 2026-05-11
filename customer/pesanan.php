<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user_email = '';
$user_query = mysqli_query($conn, "SELECT email, nama, username, profile_picture FROM users WHERE id = $user_id");
if ($user_query) {
    $active_user = mysqli_fetch_assoc($user_query);
    $user_email = $active_user['email'] ?? '';
}

$query = "
    SELECT o.*, 
           GROUP_CONCAT(p.nama_barang SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN order_details od ON o.id = od.order_id
    LEFT JOIN products p ON od.product_id = p.id
    WHERE o.user_id = $user_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
";

$result = mysqli_query($conn, $query);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);

$status_config = [
    'pending' => ['label' => 'Menunggu Pembayaran', 'color' => '#D97706', 'bg' => '#FFFBEB'],
    'paid' => ['label' => 'Dibayar - Diproses', 'color' => '#2563EB', 'bg' => '#EFF6FF'],
    'shipped' => ['label' => 'Sedang Dikirim', 'color' => '#7C3AED', 'bg' => '#F5F3FF'],
    'delivered' => ['label' => 'Selesai Diterima', 'color' => '#059669', 'bg' => '#ECFDF5'],
    'cancelled' => ['label' => 'Dibatalkan', 'color' => '#DC2626', 'bg' => '#FEF2F2']
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - 7CellX</title>
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
            transition: 0.2s;
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

        .order-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-subtle);
            transition: all 0.3s;
        }

        .order-card:hover {
            border-color: var(--brand-purple);
            box-shadow: 0 12px 35px rgba(156, 39, 176, 0.08);
            transform: translateY(-2px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 1px dashed var(--border-subtle);
            margin-bottom: 20px;
        }

        .order-id {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .order-id span {
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .order-date {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 4px;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-body {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .order-block h4 {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-block p {
            color: var(--text-dark);
            font-weight: 700;
            font-size: 0.95rem;
            margin: 0;
        }

        .order-total p {
            font-size: 1.25rem;
            font-weight: 800;
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            padding-top: 16px;
            border-top: 1px solid #F1F5F9;
        }

        /* PERBAIKAN: Tombol Aksi Diperkecil */
        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: var(--brand-gradient);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow-shadow);
            color: white;
        }

        .btn-outline-danger {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        .btn-outline-danger:hover {
            background: #DC2626;
            color: white;
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
            font-size: 6rem;
            color: #E2E8F0;
            margin-bottom: 24px;
            display: block;
        }

        footer {
            margin-top: auto;
            background: var(--brand-gradient);
            padding: 20px 0;
            text-align: center;
            color: white;
        }

        @media (max-width: 768px) {
            .order-body {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
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
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="keranjang.php"><i
                                class="bi bi-cart3 fs-5"></i> Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2 active"
                            href="pesanan.php"><i class="bi bi-receipt fs-5"></i> Pesanan</a></li>
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
                                <?= htmlspecialchars($active_user['username'] ?? $_SESSION['username'] ?? 'User') ?>
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

    <div class="page-header">
        <div class="container text-center">
            <h1>Pesanan Saya</h1>
            <p class="mb-0 opacity-75">Pantau status transaksi perangkat impian Anda.</p>
        </div>
    </div>

    <div class="container flex-grow-1 mb-5">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success rounded-4 fw-bold mx-auto mb-4" style="max-width: 800px;">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="bi bi-box2-heart"></i>
                <h3 class="fw-bold" style="color: var(--brand-navy);">Belum Ada Histori Pesanan</h3>
                <p class="text-muted mb-4">Anda belum melakukan transaksi apa pun. Yuk jelajahi katalog kami.</p>
                <a href="katalog.php" class="btn-action btn-primary" style="padding: 10px 20px;">
                    <i class="bi bi-search"></i> Cari Smartphone
                </a>
            </div>
        <?php else: ?>
            <div class="mx-auto" style="max-width: 800px;">
                <?php foreach ($orders as $order):
                    $status = $status_config[$order['status']] ?? ['label' => $order['status'], 'color' => '#64748B', 'bg' => '#F1F5F9'];
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order <span>#
                                        <?= $order['id'] ?>
                                    </span></div>
                                <div class="order-date"><i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                                </div>
                            </div>
                            <div class="status-badge" style="background: <?= $status['bg'] ?>; color: <?= $status['color'] ?>;">
                                <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>
                                <?= $status['label'] ?>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="order-block">
                                <h4>Rincian Produk</h4>
                                <p>
                                    <?= htmlspecialchars($order['product_names'] ?? '-') ?>
                                </p>
                            </div>
                            <div class="order-block">
                                <h4>Metode Bayar</h4>
                                <p>
                                    <?= strtoupper(str_replace('_', ' ', $order['payment_method'] ?? '-')) ?>
                                </p>
                            </div>
                            <div class="order-block order-total">
                                <h4>Total Dibayar</h4>
                                <p>Rp
                                    <?= number_format($order['total_harga'], 0, ',', '.') ?>
                                </p>
                            </div>
                        </div>

                        <div class="order-actions">
                            <a href="invoice.php?id=<?= $order['id'] ?>" class="btn-action btn-primary">
                                <i class="bi bi-receipt"></i> Lihat Invoice
                            </a>
                            <?php if ($order['status'] == 'pending'): ?>
                                <a href="cancel_order.php?id=<?= $order['id'] ?>" class="btn-action btn-outline-danger"
                                    onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini secara permanen?')">
                                    <i class="bi bi-x-circle"></i> Batalkan
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container small fw-medium opacity-75">&copy;
            <?= date('Y') ?> 7CellX. Engineered with precision.
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>