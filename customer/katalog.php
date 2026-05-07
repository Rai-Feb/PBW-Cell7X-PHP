<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'terbaru';

$query = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $query .= " AND (nama_barang LIKE ? OR kategori LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

switch ($sort) {
    case 'termurah':
        $query .= " ORDER BY harga_min ASC";
        break;
    case 'termahal':
        $query .= " ORDER BY harga_min DESC";
        break;
    default:
        $query .= " ORDER BY id DESC";
}

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

$mockup_images = [
    'https://images.unsplash.com/photo-1556656793-02715d8dd6f5?w=400&h=400&fit=crop',
    'https://images.unsplash.com/photo-1592899677712-a5a254503381?w=400&h=400&fit=crop',
    'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400&fit=crop'
];

$user_display = $_SESSION['username'] ?? $_SESSION['nama'] ?? 'Pengguna';
$profile_pic = $_SESSION['profile_picture'] ?? null;
$initial = strtoupper(substr($user_display, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhoneHub - Toko HP Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafd;
            color: #1e293b;
        }

        .navbar {
            background: #ffffff;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
            padding: 16px 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link {
            font-weight: 600;
            color: #475569;
            transition: 0.25s;
            padding: 10px 16px !important;
            border-radius: 12px;
            margin: 0 2px;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #f1f5f9;
            color: #2563eb;
        }

        .user-badge {
            background: #f8fafc;
            border-radius: 40px;
            padding: 6px 12px 6px 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid #e2e8f0;
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
        }

        .hero-section {
            background: radial-gradient(circle at 30% 50%, rgba(37, 99, 235, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 70% 20%, rgba(124, 58, 237, 0.06) 0%, transparent 55%);
            padding: 90px 0 70px;
            margin-bottom: 40px;
        }

        .hero-title {
            font-size: 3.3rem;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.2;
        }

        .hero-title span {
            background: linear-gradient(to right, #2563eb, #9333ea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .search-panel {
            background: #ffffff;
            border-radius: 28px;
            padding: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
            margin-bottom: 45px;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 18px;
            padding: 14px 20px 14px 50px;
            font-weight: 500;
            transition: 0.2s;
        }

        .input-group-custom .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 5px rgba(37, 99, 235, 0.08);
        }

        .input-group-custom .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.2rem;
        }

        .product-card {
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.03);
            transition: all 0.35s ease;
            border: 1px solid #f1f5f9;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 45px rgba(37, 99, 235, 0.1);
            border-color: #cbd5e1;
        }

        .product-img-wrap {
            background: #f8fafc;
            padding: 40px 20px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 260px;
            position: relative;
            overflow: hidden;
        }

        .product-img-wrap img {
            max-height: 200px;
            object-fit: contain;
            transition: transform 0.4s;
            border-radius: 16px;
        }

        .product-card:hover .product-img-wrap img {
            transform: scale(1.04);
        }

        .stock-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            padding: 6px 16px;
            border-radius: 28px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .badge-available {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-limited {
            background: #fef9c3;
            color: #a16207;
        }

        .badge-out {
            background: #fee2e2;
            color: #b91c1c;
        }

        .product-body {
            padding: 20px 22px 22px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .category-tag {
            font-size: 0.7rem;
            font-weight: 700;
            color: #6366f1;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .product-name {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0f172a, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 18px;
        }

        .btn-detail {
            background: #2563eb;
            border: none;
            border-radius: 14px;
            padding: 12px 18px;
            font-weight: 700;
            font-size: 0.85rem;
            color: white;
            transition: 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-detail:hover {
            background: #1d4ed8;
            color: white;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            transform: translateY(-2px);
        }

        .footer {
            background: #0f172a;
            color: #cbd5e1;
            padding: 50px 0 30px;
            margin-top: 70px;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.3rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="katalog.php">
                <i class="bi bi-phone"></i> PhoneHub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="katalog.php"><i
                                class="bi bi-grid-fill me-1"></i> Katalog</a></li>
                    <li class="nav-item"><a class="nav-link" href="keranjang.php"><i class="bi bi-cart3 me-1"></i>
                            Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link" href="pesanan.php"><i class="bi bi-box-seam me-1"></i>
                            Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="chat.php"><i class="bi bi-chat-dots me-1"></i>
                            Chat</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="user-badge me-3">
                        <?php if ($profile_pic): ?>
                            <img src="../uploads/profiles/<?php echo htmlspecialchars($profile_pic); ?>"
                                class="avatar-circle" style="background: none; object-fit: cover;" alt="profil">
                        <?php else: ?>
                            <div class="avatar-circle">
                                <?php echo $initial; ?>
                            </div>
                        <?php endif; ?>
                        <span class="d-none d-md-inline">
                            <?php echo htmlspecialchars($user_display); ?>
                        </span>
                    </div>
                    <a href="settings.php" class="btn btn-outline-secondary rounded-pill me-2"><i
                            class="bi bi-gear"></i></a>
                    <a href="../auth/logout.php" class="btn btn-outline-danger rounded-pill"><i
                            class="bi bi-box-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container text-center">
            <h1 class="hero-title">Temukan <span>Smartphone Impian</span> Anda</h1>
            <p class="lead mt-3 mb-0 text-secondary">Garansi resmi, harga terbaik, pengiriman cepat ke seluruh
                Indonesia.</p>
        </div>
    </section>

    <div class="container">
        <div class="search-panel">
            <div class="row g-3 align-items-center">
                <div class="col-md-8">
                    <div class="input-group-custom">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control" id="searchInput"
                            placeholder="Cari merek, tipe, atau kategori HP..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            onchange="window.location='?q='+encodeURIComponent(this.value)+'&sort=<?php echo urlencode($sort); ?>'">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select rounded-pill px-4 py-3"
                        style="border:2px solid #e2e8f0; font-weight:500;"
                        onchange="window.location='?q=<?php echo urlencode($search); ?>&sort='+this.value">
                        <option value="terbaru" <?php echo $sort === 'terbaru' ? 'selected' : ''; ?>>🔥 Terbaru</option>
                        <option value="termurah" <?php echo $sort === 'termurah' ? 'selected' : ''; ?>>💰 Termurah
                        </option>
                        <option value="termahal" <?php echo $sort === 'termahal' ? 'selected' : ''; ?>>💎 Termahal
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-5 px-4 bg-white rounded-4 shadow-sm">
                <i class="bi bi-emoji-frown display-3 text-muted"></i>
                <h3 class="mt-4 fw-bold">Produk tidak ditemukan</h3>
                <p class="text-secondary">Coba kata kunci lain atau lihat semua koleksi kami.</p>
                <a href="katalog.php" class="btn btn-primary rounded-pill px-4 mt-2">Lihat Semua Produk</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php
                $mockup_index = 0;
                foreach ($products as $produk):
                    $gambar = '';
                    if (!empty($produk['gambar'])) {
                        $full_path = __DIR__ . '/../uploads/' . $produk['gambar'];
                        if (file_exists($full_path)) {
                            $gambar = '../uploads/' . $produk['gambar'];
                        } else {
                            $gambar = $mockup_images[$mockup_index % count($mockup_images)];
                        }
                    } else {
                        $gambar = $mockup_images[$mockup_index % count($mockup_images)];
                    }
                    $mockup_index++;
                    $harga = $produk['harga_min'] ?? $produk['harga'] ?? 0;
                    $stok = $produk['stok'] ?? 0;
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="product-card">
                            <div class="product-img-wrap">
                                <img src="<?php echo htmlspecialchars($gambar); ?>"
                                    alt="<?php echo htmlspecialchars($produk['nama_barang']); ?>"
                                    onerror="this.onerror=null; this.src='https://placehold.co/400x400/f1f5f9/94a3b8?text=PhoneHub';">
                                <?php if ($stok <= 0): ?>
                                    <span class="stock-badge badge-out">Habis</span>
                                <?php elseif ($stok <= 5): ?>
                                    <span class="stock-badge badge-limited">Sisa
                                        <?php echo $stok; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="stock-badge badge-available">Stok Ada</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-body">
                                <div>
                                    <div class="category-tag">
                                        <?php echo htmlspecialchars($produk['kategori']); ?>
                                    </div>
                                    <h5 class="product-name">
                                        <?php echo htmlspecialchars($produk['nama_barang']); ?>
                                    </h5>
                                    <div class="price">Rp
                                        <?php echo number_format($harga, 0, ',', '.'); ?>
                                    </div>
                                </div>
                                <a href="detail.php?id=<?php echo $produk['id']; ?>" class="btn-detail">
                                    <i class="bi bi-eye"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer text-center">
        <div class="container">
            <h4 class="fw-bold text-white mb-2"><i class="bi bi-phone"></i> PhoneHub</h4>
            <p class="mb-0 opacity-75">Toko HP premium pilihan Indonesia. &copy; 2025</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>