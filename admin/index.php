<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'] ?? 0;
$stok_menipis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stok <= 5"))['total'] ?? 0;
$total_pesanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'] ?? 0;
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM orders WHERE status IN ('paid', 'delivered')"))['total'] ?? 0;

$pesanan_baru = mysqli_query($conn, "SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - 7CellX</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --bg-dark: #0a0a0f;
            --bg-card: #12121a;
            --bg-hover: #1a1a25;
            --accent-gold: #d4af37;
            --accent-gold-light: #f4e5c2;
            --accent-cyan: #00d4ff;
            --accent-glow: rgba(0, 212, 255, 0.15);
            --text-primary: #ffffff;
            --text-secondary: #a0a0b0;
            --text-muted: #6b6b7b;
            --border-color: rgba(255, 255, 255, 0.08);
            --gradient-gold: linear-gradient(135deg, #d4af37 0%, #f4e5c2 50%, #d4af37 100%);
            --gradient-glow: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, rgba(0, 212, 255, 0.2) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* NAVBAR - Dark Premium */
        .navbar {
            background: rgba(18, 18, 26, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }

        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .navbar-content {
            display: flex;
            align-items: center;
            height: 75px;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 900;
            background: var(--gradient-gold);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 14px;
            white-space: nowrap;
            text-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
        }

        .navbar-brand i {
            font-size: 2.2rem;
            background: var(--gradient-gold);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 10px rgba(212, 175, 55, 0.5));
        }

        .nav-menu {
            display: flex;
            gap: 8px;
            list-style: none;
            align-items: center;
            margin-left: auto;
            margin-right: 32px;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 10px 18px;
            border-radius: 12px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .nav-menu a:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
            border-color: var(--border-color);
            transform: translateY(-2px);
        }

        .nav-menu a.active {
            background: var(--gradient-glow);
            color: var(--accent-cyan);
            border-color: rgba(0, 212, 255, 0.3);
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-left: 24px;
            border-left: 1px solid var(--border-color);
        }

        .avatar-small {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-gold);
            background: var(--bg-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-gold);
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
        }

        .user-display {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .user-links {
            display: flex;
            gap: 16px;
        }

        .user-links a {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .user-links a:hover {
            color: var(--accent-cyan);
            transform: translateX(2px);
        }

        /* MAIN CONTENT */
        .main-content {
            padding: 40px 0;
            background: var(--bg-dark);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 0 10px;
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 14px;
            text-shadow: 0 0 30px rgba(212, 175, 55, 0.2);
        }

        .page-header h1 i {
            color: var(--accent-gold);
            filter: drop-shadow(0 0 10px rgba(212, 175, 55, 0.5));
        }

        /* STATS CARDS - Dark Premium */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 24px;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-gold);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(212, 175, 55, 0.3);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(212, 175, 55, 0.1);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 75px;
            height: 75px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stat-icon.gold {
            background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 100%);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
        }

        .stat-info h3 {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--text-primary);
            margin-bottom: 6px;
            background: var(--gradient-gold);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-info p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 600;
        }

        /* CONTENT CARD - Dark */
        .content-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-header h3 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header h3 i {
            color: var(--accent-gold);
        }

        /* BUTTONS - Dark Theme */
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--gradient-gold);
            color: var(--bg-dark);
            box-shadow: 0 4px 20px rgba(212, 175, 55, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.5);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        /* TABLE - Dark Theme */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 16px 12px;
            background: var(--bg-hover);
            font-weight: 700;
            color: var(--text-secondary);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 18px 12px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        tr:hover td {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-paid {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-shipped {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .badge-delivered {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* FOOTER */
        .footer {
            background: var(--bg-card);
            color: var(--text-muted);
            padding: 30px 0;
            text-align: center;
            margin-top: 80px;
            border-top: 1px solid var(--border-color);
        }

        /* GLOW EFFECTS */
        .glow-text {
            text-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
        }

        .glow-border {
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.1);
        }

        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .nav-menu {
                gap: 4px;
                margin-right: 20px;
            }

            .nav-menu a {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 992px) {
            .navbar-content {
                flex-wrap: wrap;
                height: auto;
                padding: 16px 0;
                gap: 16px;
            }

            .nav-menu {
                order: 3;
                flex-wrap: wrap;
                margin-left: 0;
                margin-right: 0;
                width: 100%;
            }

            .user-section {
                margin-left: auto;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
            }

            .navbar-content {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-menu {
                flex-direction: column;
            }

            .user-section {
                border-left: none;
                padding-left: 0;
                justify-content: center;
                width: 100%;
            }

            .user-display {
                align-items: center;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="index.php" class="navbar-brand">
                    <i class="bi bi-phone-fill"></i>
                    7CellX Admin
                </a>

                <ul class="nav-menu">
                    <li><a href="index.php"
                            class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a></li>
                    <li><a href="produk.php"
                            class="<?php echo basename($_SERVER['PHP_SELF']) == 'produk.php' ? 'active' : ''; ?>">
                            <i class="bi bi-box-seam"></i> Produk
                        </a></li>
                    <li><a href="pesanan.php"
                            class="<?php echo basename($_SERVER['PHP_SELF']) == 'pesanan.php' ? 'active' : ''; ?>">
                            <i class="bi bi-cart-check"></i> Pesanan
                        </a></li>
                    <li><a href="laporan.php"
                            class="<?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
                            <i class="bi bi-graph-up-arrow"></i> Laporan
                        </a></li>
                    <li><a href="chat.php"
                            class="<?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
                            <i class="bi bi-chat-left-text"></i> Chat
                        </a></li>
                    <li><a href="../customer/katalog.php" target="_blank">
                            <i class="bi bi-shop"></i> Lihat Toko
                        </a></li>
                </ul>

                <div class="user-section">
                    <?php if (!empty($_SESSION['profile_picture'])): ?>
                        <img src="../uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>"
                            class="avatar-small" alt="Profile">
                    <?php else: ?>
                        <div class="avatar-small">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="user-display">
                        <span class="user-name">
                            <?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['nama']); ?>
                        </span>
                        <div class="user-links">
                            <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                            <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon gold">
                        <i class="bi bi-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php echo $total_produk; ?>
                        </h3>
                        <p>Total Produk</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php echo $stok_menipis; ?>
                        </h3>
                        <p>Stok Menipis</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="bi bi-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php echo $total_pesanan; ?>
                        </h3>
                        <p>Total Pesanan</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Rp
                            <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
                        </h3>
                        <p>Total Pendapatan</p>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-clock-history"></i> Pesanan Terbaru</h3>
                    <a href="pesanan.php" class="btn btn-primary btn-sm">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($pesanan_baru)): ?>
                                <tr>
                                    <td><strong style="color: var(--accent-gold);">#
                                            <?php echo $order['id']; ?>
                                        </strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['username'] ?? 'Guest'); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </td>
                                    <td style="color: var(--text-primary); font-weight: 600;">Rp
                                        <?php echo number_format($order['total_harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td><span class="badge badge-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p style="opacity: 0.8;">© 2024 7CellX - Premium Smartphone Store</p>
        </div>
    </footer>

    <script>setInterval(() => location.reload(), 30000);</script>
</body>

</html>