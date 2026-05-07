<?php
session_start();
require_once '../config/koneksi.php';

$products_per_page = 8;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $products_per_page;

$merk_filter = isset($_GET['merk']) ? $_GET['merk'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

$where = ["1=1"];
$params = [];
$param_types = "";

if ($merk_filter) {
    $where[] = "merk = ?";
    $params[] = $merk_filter;
    $param_types .= "s";
}

if ($search) {
    $where[] = "(nama_produk LIKE ? OR deskripsi LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

$where_clause = implode(" AND ", $where);

$order_by = "created_at DESC";
if ($sort === 'harga_asc') {
    $order_by = "harga_min ASC";
} elseif ($sort === 'harga_desc') {
    $order_by = "harga_min DESC";
} elseif ($sort === 'nama_asc') {
    $order_by = "nama_produk ASC";
}

$count_sql = "SELECT COUNT(*) as total FROM products WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params) && $search) {
    $count_types = str_repeat("s", count($params));
    $count_stmt->bind_param($count_types, ...$params);
}
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $products_per_page);

$sql = "SELECT * FROM products WHERE $where_clause ORDER BY $order_by LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$final_types = $param_types . "ii";
$final_params = array_merge($params, [$products_per_page, $offset]);

$bind_params = [];
for ($i = 0; $i < strlen($final_types); $i++) {
    $bind_params[] = &$final_params[$i];
}
$stmt->bind_param($final_types, ...$bind_params);
$stmt->execute();
$products = $stmt->get_result();

$merk_stmt = $conn->query("SELECT DISTINCT merk FROM products ORDER BY merk ASC");
$merks = $merk_stmt->fetch_all(MYSQLI_ASSOC);

$current_url = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_filter([
    'merk' => $merk_filter,
    'search' => $search,
    'sort' => $sort
]));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Handphone - 7CellX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-pink: #E91E63;
            --primary-purple: #9C27B0;
            --pink-light: #FCE4EC;
            --purple-light: #F3E5F5;
            --purple-dark: #7B1FA2;
            --text-dark: #2D3748;
            --text-muted: #718096;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #fafafa;
            color: var(--text-dark);
        }

        .navbar {
            background: white !important;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            padding: 1rem 0;
        }

        .navbar-brand img {
            height: 45px;
            transition: transform 0.3s;
        }

        .navbar-brand img:hover {
            transform: scale(1.05);
        }

        .search-box {
            position: relative;
            width: 100%;
            max-width: 500px;
        }

        .search-box input {
            border-radius: 25px;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .search-box input:focus {
            border-color: var(--primary-pink);
            box-shadow: 0 0 0 0.25rem rgba(233, 30, 99, 0.15);
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-pink) 0%, var(--primary-purple) 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(233, 30, 99, 0.4);
            color: white;
        }

        .btn-outline-gradient {
            border: 2px solid var(--primary-pink);
            background: transparent;
            color: var(--primary-pink);
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-outline-gradient:hover {
            background: linear-gradient(135deg, var(--primary-pink) 0%, var(--primary-purple) 100%);
            color: white;
            border-color: transparent;
        }

        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            margin: 0 8px;
            position: relative;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-pink) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, var(--primary-pink), var(--primary-purple));
            transition: all 0.3s;
            transform: translateX(-50%);
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 70%;
        }

        .cart-badge {
            background: linear-gradient(135deg, var(--primary-pink), var(--primary-purple));
            font-size: 0.7rem;
            padding: 4px 7px;
        }

        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 10px 15px;
            font-size: 0.95rem;
            cursor: pointer;
        }

        .form-select:focus {
            border-color: var(--primary-pink);
            box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.15);
        }

        .product-card {
            background: white;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(156, 39, 176, 0.2);
        }

        .product-card .card-img-wrapper {
            height: 220px;
            background: linear-gradient(135deg, var(--pink-light) 0%, var(--purple-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        .product-card .card-img-top {
            max-height: 180px;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.3s;
        }

        .product-card:hover .card-img-top {
            transform: scale(1.05);
        }

        .badge-merk {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, var(--primary-pink), var(--primary-purple));
            color: white;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 20px;
        }

        .card-title {
            font-weight: 600;
            font-size: 1.05rem;
            margin-bottom: 5px;
            color: var(--text-dark);
            line-height: 1.4;
        }

        .card-specs {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .price {
            color: var(--primary-pink);
            font-weight: 700;
            font-size: 1.2rem;
        }

        .price-old {
            color: var(--text-muted);
            text-decoration: line-through;
            font-size: 0.9rem;
            margin-left: 8px;
        }

        .btn-add-cart {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-pink), var(--primary-purple));
            border: none;
            color: white;
            transition: all 0.3s;
        }

        .btn-add-cart:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
        }

        .btn-add-cart i {
            font-size: 1.1rem;
        }

        .pagination .page-link {
            border: none;
            color: var(--text-dark);
            margin: 0 3px;
            border-radius: 10px !important;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            transition: all 0.3s;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-pink), var(--primary-purple));
            color: white;
        }

        .pagination .page-link:hover {
            background: var(--pink-light);
            color: var(--primary-pink);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--pink-light);
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .search-box {
                max-width: 100%;
                margin: 15px 0;
            }

            .navbar-nav {
                margin-top: 15px;
            }

            .product-card .card-img-wrapper {
                height: 200px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="../assets/logo.png" alt="7CellX"
                    onerror="this.src='https://via.placeholder.com/120x45/E91E63/ffffff?text=7CellX'">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex search-box mx-auto" method="GET" action="">
                    <i class="bi bi-search"></i>
                    <input class="form-control" type="search" name="search"
                        placeholder="Cari handphone, merk, spesifikasi..." value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="merk" value="<?= htmlspecialchars($merk_filter) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                </form>

                <ul class="navbar-nav align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="katalog.php">Katalog</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="pesanan.php">Pesanan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="chat.php">Chat</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-lg-3">
                        <a href="keranjang.php" class="btn btn-outline-gradient position-relative">
                            <i class="bi bi-cart3"></i>
                            <?php
                            $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                            if ($cart_count > 0):
                                ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill cart-badge">
                                    <?= $cart_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="profile.php" class="btn btn-gradient">
                                <i class="bi bi-person-circle me-1"></i>
                                <?= htmlspecialchars($_SESSION['nama']) ?>
                            </a>
                        <?php else: ?>
                            <a href="../auth/login.php" class="btn btn-gradient">Login</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4 py-lg-5">

        <div class="filter-section">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Filter Merk</label>
                    <select class="form-select"
                        onchange="location = this.value.includes('merk=') ? this.value : this.value + (this.value.includes('?') ? '&' : '?') + 'merk=' + this.value">
                        <option value="<?= strtok($current_url, '?') ?>">Semua Merk</option>
                        <?php foreach ($merks as $merk): ?>
                            <option value="<?= $current_url . '&merk=' . urlencode($merk['merk']) ?>"
                                <?= $merk_filter === $merk['merk'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($merk['merk']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Urutkan</label>
                    <select class="form-select" onchange="window.location.href=this.value">
                        <option value="<?= $current_url . '&sort=terbaru' ?>" <?= $sort === 'terbaru' ? 'selected' : '' ?>
                            >Terbaru</option>
                        <option value="<?= $current_url . '&sort=harga_asc' ?>" <?= $sort === 'harga_asc' ? 'selected' : '' ?>>Harga: Rendah ke Tinggi</option>
                        <option value="<?= $current_url . '&sort=harga_desc' ?>" <?= $sort === 'harga_desc' ? 'selected' : '' ?>>Harga: Tinggi ke Rendah</option>
                        <option value="<?= $current_url . '&sort=nama_asc' ?>" <?= $sort === 'nama_asc' ? 'selected' : '' ?>>Nama: A-Z</option>
                    </select>
                </div>
                <?php if ($merk_filter || $search): ?>
                    <div class="col-md-6 text-md-end">
                        <a href="<?= strtok($current_url, '?') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i>Reset Filter
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($products->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="card product-card">
                            <span class="badge-merk">
                                <?= htmlspecialchars($product['merk']) ?>
                            </span>
                            <div class="card-img-wrapper">
                                <img src="../uploads/<?= htmlspecialchars($product['gambar']) ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($product['nama_produk']) ?>"
                                    onerror="this.src='https://via.placeholder.com/200x200/FCE4EC/E91E63?text=<?= urlencode($product['merk']) ?>'">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title">
                                    <?= htmlspecialchars($product['nama_produk']) ?>
                                </h6>
                                <p class="card-specs">
                                    <?php
                                    $varian = json_decode($product['varian'], true);
                                    if ($varian && isset($varian[0]['ram'], $varian[0]['rom'])):
                                        ?>
                                        <?= $varian[0]['ram'] ?>GB /
                                        <?= $varian[0]['rom'] ?>GB
                                    <?php else: ?>
                                        Spesifikasi bervariasi
                                    <?php endif; ?>
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="price">Rp
                                                <?= number_format($product['harga_min'], 0, ',', '.') ?>
                                            </span>
                                            <?php if ($product['harga_max'] > $product['harga_min']): ?>
                                                <span class="price-old">s/d Rp
                                                    <?= number_format($product['harga_max'], 0, ',', '.') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="detail.php?id=<?= $product['id'] ?>" class="btn-add-cart">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                    <form action="keranjang.php" method="POST" class="mt-3">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="variant_index" value="0">
                                        <button type="submit" class="btn btn-gradient w-100 btn-sm">
                                            <i class="bi bi-cart-plus me-1"></i>Tambah
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $current_url ?>&page=<?= $page - 1 ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $current_url ?>&page=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $current_url ?>&page=<?= $page + 1 ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-phone"></i>
                <h5 class="mt-3">Produk tidak ditemukan</h5>
                <p class="text-muted">Coba ubah kata kunci atau filter pencarian Anda</p>
                <a href="<?= strtok($current_url, '?') ?>" class="btn btn-outline-gradient mt-2">
                    Lihat Semua Produk
                </a>
            </div>
        <?php endif; ?>

    </div>

    <footer class="bg-white border-top py-4 mt-5">
        <div class="container text-center text-muted small">
            <p class="mb-0">&copy;
                <?= date('Y') ?> 7CellX. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.form-select').forEach(select => {
            select.addEventListener('change', function () {
                if (this.value) window.location.href = this.value;
            });
        });
    </script>
</body>

</html>