<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM products WHERE id = $id"));
    if ($produk && !empty($produk['gambar']) && file_exists('../uploads/' . $produk['gambar'])) {
        unlink('../uploads/' . $produk['gambar']);
    }
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    header('Location: produk.php');
    exit;
}

$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - 7CellX Admin</title>
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

        .main-content {
            padding: 40px 0;
            flex-grow: 1;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand-navy);
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }

        .btn-primary-custom {
            background: var(--brand-gradient);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 14px;
            font-weight: 700;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow-shadow);
            color: white;
        }

        .content-card {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--card-shadow);
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
            vertical-align: middle;
        }

        tr:hover td {
            background: #F8FAFC;
        }

        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 12px;
            background: #F8FAFC;
            padding: 5px;
            border: 1px solid var(--border-subtle);
        }

        .badge {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        .badge-warning {
            background: #FFFBEB;
            color: #D97706;
            border: 1px solid #FDE68A;
        }

        .badge-danger {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            background: #F4F7FE;
            color: var(--brand-purple);
            border: 1px solid var(--border-subtle);
            cursor: pointer;
        }

        .btn-action:hover {
            background: var(--brand-purple);
            color: white;
            border-color: transparent;
        }

        .btn-delete {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        .btn-delete:hover {
            background: #DC2626;
            color: white;
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
                    <li class="nav-item"><a class="nav-link active d-flex align-items-center gap-2" href="produk.php"><i
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
                        <li><a class="dropdown-item text-danger fw-bold d-flex align-items-center"
                                href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="bi bi-box2-heart text-muted"></i> Manajemen Produk</h1>
                <a href="tambah_produk.php" class="btn-primary-custom"><i class="bi bi-plus-circle-fill"></i> Tambah
                    Produk</a>
            </div>

            <div class="content-card">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success rounded-4 fw-bold mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']) ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Varian RAM/ROM (Stok)</th>
                                <th>Harga Dasar</th>
                                <th>Total Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = mysqli_fetch_assoc($products)):
                                $varian = json_decode($p['varian'] ?? '[]', true);
                                $varian_count = is_array($varian) ? count($varian) : 0;
                                ?>
                                <tr>
                                    <td class="fw-bold" style="color: var(--brand-purple);">#
                                        <?= $p['id']; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 15px;">
                                            <img src="../uploads/<?= htmlspecialchars($p['gambar']) ?>"
                                                class="product-thumb"
                                                onerror="this.src='https://via.placeholder.com/60/F8FAFC/9C27B0?text=HP'">
                                            <div>
                                                <div style="font-weight: 700; color: var(--text-dark);">
                                                    <?= htmlspecialchars($p['nama_barang']); ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                                    <i class="bi bi-tags"></i>
                                                    <?= $varian_count; ?> varian
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-muted">
                                        <?= htmlspecialchars($p['kategori']); ?>
                                    </td>
                                    <td>
                                        <?php if ($varian_count > 0): ?>
                                            <div
                                                style="font-size: 0.85rem; color: var(--text-dark); font-weight: 600; display: flex; flex-wrap: wrap; gap: 6px;">
                                                <?php foreach (array_slice($varian, 0, 3) as $v): ?>
                                                    <div
                                                        style="background: #F8FAFC; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-subtle); display: inline-flex; align-items: center; gap: 6px;">
                                                        <?= htmlspecialchars($v['ram']) ?>/
                                                        <?= htmlspecialchars($v['rom']) ?> GB
                                                        <span
                                                            style="background: rgba(156, 39, 176, 0.1); padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; color: var(--brand-purple);">Stok:
                                                            <?= (int) ($v['stok'] ?? 0) ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if ($varian_count > 3): ?>
                                                    <span
                                                        style="color: var(--brand-pink); font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center;">+
                                                        <?= ($varian_count - 3); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold">Rp
                                        <?= number_format($p['harga_min'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="fw-bold text-center">
                                        <?= $p['stok']; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['stok'] <= 0): ?>
                                            <span class="badge badge-danger">Habis</span>
                                        <?php elseif ($p['stok'] <= 5): ?>
                                            <span class="badge badge-warning">Menipis</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="edit_produk.php?id=<?= $p['id']; ?>" class="btn-action btn-edit"><i
                                                    class="bi bi-pencil-square"></i></a>
                                            <a href="produk.php?delete=<?= $p['id']; ?>" class="btn-action btn-delete"
                                                onclick="return confirm('Yakin ingin menghapus produk ini secara permanen?')"><i
                                                    class="bi bi-trash3-fill"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container small fw-medium opacity-75">
            &copy;
            <?= date('Y') ?> 7CellX
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>