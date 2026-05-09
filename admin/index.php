<?php
session_start();
require_once '../config/koneksi.php';

/** @var mysqli $conn */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$start_query = $start_date . ' 00:00:00';
$end_query = $end_date . ' 23:59:59';

$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'] ?? 0;
$stok_menipis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stok <= 5"))['total'] ?? 0;

$stmt_pesanan = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM orders WHERE created_at BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt_pesanan, "ss", $start_query, $end_query);
mysqli_stmt_execute($stmt_pesanan);
$total_pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pesanan))['total'] ?? 0;

$stmt_pendapatan = mysqli_prepare($conn, "SELECT SUM(total_harga) as total FROM orders WHERE status IN ('paid', 'delivered') AND created_at BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt_pendapatan, "ss", $start_query, $end_query);
mysqli_stmt_execute($stmt_pendapatan);
$total_pendapatan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pendapatan))['total'] ?? 0;

$stmt_baru = mysqli_prepare($conn, "SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.created_at BETWEEN ? AND ? ORDER BY o.created_at DESC LIMIT 10");
mysqli_stmt_bind_param($stmt_baru, "ss", $start_query, $end_query);
mysqli_stmt_execute($stmt_baru);
$pesanan_baru = mysqli_stmt_get_result($stmt_baru);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - 7CellX</title>
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

        .main-content {
            padding: 40px 0;
            flex-grow: 1;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand-navy);
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .filter-box {
            background: var(--bg-card);
            padding: 15px 25px;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filter-box input[type="date"] {
            border: 1px solid var(--border-subtle);
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 600;
            color: var(--text-dark);
            outline: none;
            background: #F8FAFC;
        }

        .filter-box input[type="date"]:focus {
            border-color: var(--brand-purple);
        }

        .btn-filter {
            background: var(--brand-gradient);
            color: white;
            border: none;
            padding: 9px 20px;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow-shadow);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--glow-shadow);
            border-color: var(--brand-purple);
        }

        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-icon.gradient-1 {
            background: linear-gradient(135deg, #3B82F6, #2563EB);
        }

        .stat-icon.gradient-2 {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }

        .stat-icon.gradient-3 {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .stat-icon.gradient-4 {
            background: var(--brand-gradient);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .stat-info p {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .content-card {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--card-shadow);
        }

        .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px dashed var(--border-subtle);
        }

        .card-header-custom h3 {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary-custom {
            background: var(--brand-gradient);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow-shadow);
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #F8FAFC;
            font-weight: 700;
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--border-subtle);
        }

        th:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        th:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        td {
            padding: 16px 15px;
            border-bottom: 1px solid #F1F5F9;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95rem;
        }

        tr:hover td {
            background: #F8FAFC;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background: #FFFBEB;
            color: #D97706;
        }

        .badge-paid {
            background: #EFF6FF;
            color: #2563EB;
        }

        .badge-shipped {
            background: #F5F3FF;
            color: #7C3AED;
        }

        .badge-delivered {
            background: #ECFDF5;
            color: #059669;
        }

        .badge-cancelled {
            background: #FEF2F2;
            color: #DC2626;
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
        <div class="container d-lg-flex">
            <div class="nav-zone-left" style="flex: 1;">
                <a class="brand-pill" href="index.php">
                    <img src="../assets/logo.png" alt="Logo" class="brand-logo-img"
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
                    <li class="nav-item"><a class="nav-link active d-flex align-items-center gap-2" href="index.php"><i
                                class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="produk.php"><i
                                class="bi bi-box-seam"></i> Produk</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="pesanan.php"><i
                                class="bi bi-receipt"></i> Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="chat.php"><i
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

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="bi bi-grid-fill text-muted"></i> Dashboard Overview</h1>
                <form method="GET" class="filter-box">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fw-bold small">DARI:</span>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fw-bold small">SAMPAI:</span>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                    </div>
                    <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filter Data</button>
                </form>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon gradient-1"><i class="bi bi-box-seam-fill"></i></div>
                    <div class="stat-info">
                        <h3>
                            <?= $total_produk ?>
                        </h3>
                        <p>Total Semua Produk</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gradient-2"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div class="stat-info">
                        <h3>
                            <?= $stok_menipis ?>
                        </h3>
                        <p>Produk Menipis</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gradient-3"><i class="bi bi-cart-check-fill"></i></div>
                    <div class="stat-info">
                        <h3>
                            <?= $total_pesanan ?>
                        </h3>
                        <p>Pesanan Masuk</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gradient-4"><i class="bi bi-wallet-fill"></i></div>
                    <div class="stat-info">
                        <h3 style="font-size: 1.4rem;">Rp
                            <?= number_format($total_pendapatan, 0, ',', '.') ?>
                        </h3>
                        <p>Pendapatan Bersih</p>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header-custom">
                    <h3><i class="bi bi-clock-history text-muted"></i> Pesanan Berdasarkan Filter</h3>
                    <a href="pesanan.php" class="btn-primary-custom">Kelola Semua Pesanan <i
                            class="bi bi-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Tanggal</th>
                                <th>Total Bayar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pesanan_baru->num_rows > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($pesanan_baru)): ?>
                                    <tr>
                                        <td class="fw-bold" style="color: var(--brand-purple);">#
                                            <?= $order['id'] ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($order['username'] ?? 'Guest') ?>
                                        </td>
                                        <td class="text-muted"><i class="bi bi-calendar2 me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td class="fw-bold">Rp
                                            <?= number_format($order['total_harga'], 0, ',', '.') ?>
                                        </td>
                                        <td><span class="badge badge-<?= $order['status'] ?>">
                                                <?= strtoupper($order['status']) ?>
                                            </span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted fw-bold">Tidak ada data pesanan pada
                                        rentang tanggal ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container small fw-medium opacity-75">
            &copy;
            <?= date('Y') ?> 7CellX Admin Panel. Engineered with precision.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>