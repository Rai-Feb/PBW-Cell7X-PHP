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
    <title>Kelola Produk - 7CellX</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --gold-primary: #d4af37;
            --gold-light: #f4e5c2;
            --gold-dark: #aa8c2c;
            --cream: #faf8f3;
            --dark: #1a1a1a;
            --gray: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: var(--cream);
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 16px 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .navbar-content {
            display: flex;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-left: auto;
        }

        .nav-menu {
            display: flex;
            gap: 20px;
            list-style: none;
            align-items: center;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--gray);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--gold-primary);
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-left: 20px;
            border-left: 2px solid #e5e7eb;
        }

        .avatar-small {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gold-light);
        }

        .user-display {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--dark);
        }

        .user-links {
            display: flex;
            gap: 12px;
        }

        .user-links a {
            font-size: 0.85rem;
            color: var(--gold-primary);
            text-decoration: none;
            font-weight: 600;
        }

        .main-content {
            padding: 30px 0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 900;
            color: var(--dark);
        }

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
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px 12px;
            background: var(--cream);
            font-weight: 700;
            color: var(--dark);
            font-size: 0.85rem;
        }

        td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--gray);
            vertical-align: middle;
        }

        tr:hover td {
            background: var(--cream);
        }

        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .footer {
            background: var(--dark);
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 16px;
            }

            .nav-right {
                flex-direction: column;
                gap: 16px;
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }

            .user-section {
                border-left: none;
                padding-left: 0;
                align-items: center;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="index.php" class="navbar-brand"><i class="bi bi-lightning-charge-fill"></i>7CellX Admin</a>
                <div class="nav-right">
                    <ul class="nav-menu">
                        <li><a href="index.php"><i class="bi bi-house"></i> Dashboard</a></li>
                        <li><a href="produk.php" class="active"><i class="bi bi-box"></i> Produk</a></li>
                        <li><a href="pesanan.php"><i class="bi bi-cart"></i> Pesanan</a></li>
                        <li><a href="laporan.php"><i class="bi bi-graph-up"></i> Laporan</a></li>
                        <li><a href="chat.php"><i class="bi bi-chat-dots"></i> Chat</a></li>
                        <li><a href="../customer/katalog.php" target="_blank"><i class="bi bi-eye"></i> Lihat Toko</a>
                        </li>
                    </ul>
                    <div class="user-section">
                        <?php if (!empty($_SESSION['profile_picture'])): ?>
                            <img src="../uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>"
                                class="avatar-small">
                        <?php else: ?>
                            <div class="avatar-small"
                                style="background: var(--gold-light); display: flex; align-items: center; justify-content: center; color: var(--gold-dark); font-weight: 700;">
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
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="bi bi-box"></i> Kelola Produk</h1>
                <a href="tambah_produk.php" class="btn btn-primary"><i class="bi bi-plus"></i> Tambah Produk</a>
            </div>

            <div class="content-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Varian</th>
                                <th>Harga</th>
                                <th>Stok</th>
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
                                    <td><strong>#
                                            <?php echo $p['id']; ?>
                                        </strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php if (!empty($p['gambar'])): ?>
                                                <img src="../uploads/<?php echo $p['gambar']; ?>" class="product-thumb">
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight: 600; color: var(--dark);">
                                                    <?php echo htmlspecialchars($p['nama_barang']); ?>
                                                </div>
                                                <div style="font-size: 0.85rem; color: var(--gray);">
                                                    <?php echo $varian_count; ?> varian
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($p['kategori']); ?>
                                    </td>
                                    <td>
                                        <?php if ($varian_count > 0): ?>
                                            <div style="font-size: 0.85rem; color: var(--gray);">
                                                <?php foreach (array_slice($varian, 0, 2) as $v): ?>
                                                    <div>
                                                        <?php echo $v['ram']; ?>/
                                                        <?php echo $v['rom']; ?> GB
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if ($varian_count > 2): ?>
                                                    <div style="color: var(--gold-primary);">+
                                                        <?php echo ($varian_count - 2); ?> lainnya
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--gray);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rp
                                        <?php echo number_format($p['harga_min'], 0, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <?php echo $p['stok']; ?>
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
                                        <a href="edit_produk.php?id=<?php echo $p['id']; ?>"
                                            class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                                        <a href="produk.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Hapus produk ini?')"><i class="bi bi-trash"></i>
                                            Hapus</a>
                                    </td>
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
            <p style="opacity: 0.8;">© 2024 7CellX - Project UAS PBW</p>
        </div>
    </footer>
</body>

</html>