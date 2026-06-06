<?php
session_start();
require_once '../config/koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = (int) ($_GET['id'] ?? 0);
$user_id = (int) $_SESSION['user_id'];

// Data User untuk Navbar
$stmt_user = mysqli_prepare($conn, "SELECT nama, username, profile_picture FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$active_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));

$order_query = mysqli_query($conn, "SELECT o.*, u.nama as customer_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = $order_id AND o.user_id = $user_id");
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    header('Location: pesanan.php');
    exit;
}

$items_query = mysqli_query($conn, "SELECT od.*, p.nama_barang, p.gambar FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = $order_id");
$items = [];
while ($item = mysqli_fetch_assoc($items_query)) {
    $items[] = $item;
}

$status_config = [
    'pending' => ['label' => 'Menunggu Pembayaran', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'paid' => ['label' => 'Dibayar - Diproses', 'color' => '#2563eb', 'bg' => '#dbeafe'],
    'shipped' => ['label' => 'Sedang Dikirim', 'color' => '#7c3aed', 'bg' => '#e0e7ff'],
    'delivered' => ['label' => 'Selesai Diterima', 'color' => '#059669', 'bg' => '#d1fae5'],
    'cancelled' => ['label' => 'Dibatalkan', 'color' => '#dc2626', 'bg' => '#fee2e2']
];
$status = $status_config[$order['status']] ?? ['label' => $order['status'], 'color' => '#64748B', 'bg' => '#F1F5F9'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #
        <?= $order_id; ?> - 7CellX
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

        .invoice-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid var(--border-subtle);
        }

        .invoice-header {
            background: var(--brand-gradient);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .invoice-header h1 {
            font-size: 2rem;
            margin-bottom: 8px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .invoice-header p {
            opacity: 0.9;
            font-weight: 500;
        }

        .invoice-body {
            padding: 40px;
        }

        .status-badge {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.9rem;
            margin-top: -60px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: <?= $status['bg'] ?>;
            color: <?= $status['color'] ?>;
            border: 2px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 5;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin: 20px 0 30px;
            padding: 24px;
            background: #F8FAFC;
            border-radius: 16px;
            border: 1px dashed var(--border-subtle);
        }

        .info-item h4 {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item p {
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 700;
            margin: 0;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .products-table th {
            text-align: left;
            padding: 14px;
            background: #F8FAFC;
            font-weight: 800;
            color: var(--text-muted);
            border-bottom: 2px solid var(--brand-purple);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .products-table td {
            padding: 16px 14px;
            border-bottom: 1px solid #F1F5F9;
            font-weight: 500;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-info img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 10px;
            background: #F8FAFC;
            padding: 5px;
            border: 1px solid var(--border-subtle);
        }

        .summary-section {
            background: #F8FAFC;
            padding: 24px;
            border-radius: 16px;
            margin-top: 30px;
            border: 1px solid var(--border-subtle);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 2px dashed var(--border-subtle);
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-top: 12px;
        }

        .summary-total span:last-child {
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .action-buttons {
            display: flex;
            gap: 16px;
            margin-top: 30px;
        }

        .btn-custom {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .btn-primary-custom {
            background: var(--brand-gradient);
            color: white;
            box-shadow: var(--glow-shadow);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            color: white;
        }

        .btn-outline-custom {
            background: white;
            color: var(--text-muted);
            border: 2px solid var(--border-subtle);
        }

        .btn-outline-custom:hover {
            background: #F8FAFC;
            color: var(--brand-purple);
            border-color: var(--brand-purple);
        }

        footer {
            margin-top: auto;
            background: var(--brand-gradient);
            padding: 20px 0;
            text-align: center;
            color: white;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .navbar,
            .action-buttons,
            footer {
                display: none !important;
            }

            .invoice-container {
                box-shadow: none;
                border: none;
                margin: 0;
                max-width: 100%;
            }

            .invoice-header {
                background: white !important;
                color: black !important;
                border-bottom: 2px solid black;
            }

            .invoice-header h1 {
                color: black;
            }

            .summary-total span:last-child {
                -webkit-text-fill-color: black;
                background: none;
                color: black;
            }
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
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

    <div class="container flex-grow-1">
        <div class="invoice-container">
            <div class="invoice-header">
                <h1><i class="bi bi-box-seam me-2"></i>7CellX</h1>
                <p class="mb-0">Invoice Pesanan #
                    <?= $order_id; ?>
                </p>
                <p style="margin-top: 5px; font-size: 0.9rem;"><i class="bi bi-calendar3 me-1"></i>
                    <?= date('d F Y, H:i', strtotime($order['created_at'])); ?>
                </p>
            </div>

            <div class="invoice-body">
                <div style="text-align: center;">
                    <div class="status-badge"><i class="bi bi-info-circle-fill me-1"></i>
                        <?= $status['label']; ?>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <h4><i class="bi bi-person-fill me-2"></i>Nama Penerima</h4>
                        <p>
                            <?= htmlspecialchars($order['customer_name'] ?? 'Customer'); ?>
                        </p>
                        <p style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500; margin-top: 4px;">
                            <?= htmlspecialchars($order['email'] ?? '-'); ?>
                        </p>
                    </div>
                    <div class="info-item">
                        <h4><i class="bi bi-geo-alt-fill me-2"></i>Alamat Pengiriman</h4>
                        <p>
                            <?= htmlspecialchars($order['alamat']); ?>
                        </p>
                    </div>
                    <div class="info-item">
                        <h4><i class="bi bi-wallet-fill me-2"></i>Metode Pembayaran</h4>
                        <p>
                            <?= strtoupper(str_replace('_', ' ', $order['payment_method'])); ?>
                        </p>
                    </div>
                    <div class="info-item">
                        <h4><i class="bi bi-clock-fill me-2"></i>Tanggal Pesan</h4>
                        <p>
                            <?= date('d M Y, H:i', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                </div>

                <h3 style="margin: 30px 0 16px; font-size: 1.2rem; color: var(--text-dark); font-weight: 800;">Detail
                    Pesanan</h3>
                <div style="overflow-x: auto;">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Varian</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <img src="../uploads/<?= htmlspecialchars($item['gambar']); ?>"
                                                onerror="this.src='https://via.placeholder.com/60x60/F8FAFC/9C27B0?text=HP'">
                                            <div style="font-weight: 700; color: var(--text-dark);">
                                                <?= htmlspecialchars($item['nama_barang']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span
                                            style="background: white; border: 1px solid var(--border-subtle); padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">
                                            <?= htmlspecialchars($item['varian'] ?? '-'); ?>
                                        </span></td>
                                    <td>Rp
                                        <?= number_format($item['harga_satuan'], 0, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <?= $item['jumlah']; ?>
                                    </td>
                                    <td style="font-weight: 800; color: var(--brand-purple);">Rp
                                        <?= number_format($item['harga_satuan'] * $item['jumlah'], 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-section">
                    <div class="summary-row">
                        <span>Subtotal Produk</span>
                        <span>Rp
                            <?= number_format($order['total_harga'], 0, ',', '.'); ?>
                        </span>
                    </div>
                    <div class="summary-row">
                        <span>Ongkos Kirim</span>
                        <span style="color: #10B981; font-weight: 800;">Gratis</span>
                    </div>
                    <div class="summary-total">
                        <span>Total Tagihan</span>
                        <span>Rp
                            <?= number_format($order['total_harga'], 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="pesanan.php" class="btn-custom btn-outline-custom">
                        <i class="bi bi-arrow-left"></i> Kembali ke Pesanan
                    </a>
                    <button onclick="window.print()" class="btn-custom btn-primary-custom">
                        <i class="bi bi-printer-fill"></i> Cetak Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container small fw-medium opacity-75">&copy;
            <?= date('Y') ?> 7CellX. Engineered with precision.
        </div>
    </footer>
</body>

</html>