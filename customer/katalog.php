<?php
session_start();
require_once '../config/koneksi.php';

/** @var mysqli $conn */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile' && isset($_SESSION['user_id'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $user_id = $_SESSION['user_id'];
    
    $stmt_get = mysqli_prepare($conn, "SELECT profile_picture FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt_get, "i", $user_id);
    mysqli_stmt_execute($stmt_get);
    $res = mysqli_stmt_get_result($stmt_get);
    $current_user = mysqli_fetch_assoc($res);
    $profile_picture = $current_user['profile_picture'] ?? '';

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'pp_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../uploads/profiles/';
            
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0777, true);
            }

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path . $new_filename)) {
                if (!empty($profile_picture) && file_exists($upload_path . $profile_picture)) {
                    unlink($upload_path . $profile_picture);
                }
                $profile_picture = $new_filename;
            }
        }
    }

    $stmt_update = mysqli_prepare($conn, "UPDATE users SET nama = ?, username = ?, profile_picture = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_update, "sssi", $nama, $username, $profile_picture, $user_id);
    
    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['nama'] = $nama;
        $_SESSION['username'] = $username;
        $_SESSION['profile_picture'] = $profile_picture;
        $_SESSION['success_msg'] = "Profil berhasil diperbarui!";
    }
    header("Location: katalog.php");
    exit;
}

$products_per_page = 8;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $products_per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

$where = ["1=1"];
$params = [];
$param_types = "";

if ($search) {
    $where[] = "(nama_barang LIKE ? OR deskripsi LIKE ?)";
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
    $order_by = "nama_barang ASC"; 
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

$current_url_base = $_SERVER['PHP_SELF'];

$active_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt_user = mysqli_prepare($conn, "SELECT nama, username, profile_picture FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt_user, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt_user);
    $active_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eksplorasi Katalog - 7CellX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --card-shadow: 0 8px 25px rgba(0, 0, 0, 0.04);
        }

        * { font-family: 'Plus Jakarta Sans', sans-serif; }

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
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        @media (min-width: 992px) {
            .nav-zone-left { flex: 1; display: flex; justify-content: flex-start; }
            .nav-zone-center { flex: 2; display: flex; justify-content: center; } 
            .nav-zone-right { flex: 1; display: flex; justify-content: flex-end; }
        }

        .brand-pill {
            background: #FFFFFF;
            padding: 6px 20px 6px 8px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .brand-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
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

        .nav-link:hover, .nav-link.active {
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
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .btn-white-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 10px;
            margin-top: 15px !important;
        }
        .dropdown-item { border-radius: 8px; padding: 8px 15px; font-weight: 500; cursor: pointer; transition: all 0.2s;}
        .dropdown-item:hover { background-color: #F8FAFC; color: var(--brand-purple); }
        .dropdown-item.text-danger:hover { background-color: #FEF2F2; color: #DC2626 !important; }

        .hero-colorful {
            background: var(--brand-gradient);
            border-radius: 30px;
            padding: 50px 50px 90px 50px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--glow-shadow);
            margin-top: 30px;
        }

        .orb-1 { position: absolute; top: -40px; right: 5%; width: 250px; height: 250px; background: rgba(255, 255, 255, 0.15); border-radius: 50%; backdrop-filter: blur(8px); }
        .orb-2 { position: absolute; bottom: -50px; right: 15%; width: 150px; height: 150px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; backdrop-filter: blur(4px); }

        .toolbar-overlap {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 15px 20px;
            margin-top: -40px;
            position: relative;
            z-index: 5;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            width: 90%;
            margin-left: auto; margin-right: auto; margin-bottom: 40px;
        }

        .search-box input { background: #F4F7FE; border: 1px solid transparent; color: var(--text-dark); border-radius: 12px; padding: 12px 20px 12px 45px; transition: all 0.3s; font-weight: 500; }
        .search-box input:focus { background: #FFFFFF; border-color: var(--brand-purple); box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1); outline: none; }
        .search-box i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 4; }

        .form-select { background-color: #F4F7FE; border: 1px solid transparent; color: var(--text-dark); border-radius: 12px; padding: 12px 15px; font-weight: 600; cursor: pointer; outline: none; }
        .form-select:focus { border-color: var(--brand-purple); box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1); }

        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-8px);
            border-color: var(--brand-pink);
            box-shadow: var(--glow-shadow);
        }

        .product-card .card-img-wrapper {
            height: 220px;
            display: flex; align-items: center; justify-content: center;
            padding: 20px; border-bottom: 1px solid #F8FAFC;
        }
        .product-card .card-img-top {
            max-height: 100%; max-width: 100%; object-fit: contain; transition: transform 0.5s ease;
        }
        .product-card:hover .card-img-top { transform: scale(1.08); }

        .badge-stock {
            position: absolute; top: 15px; left: 15px;
            color: white; padding: 6px 14px; border-radius: 12px;
            font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; z-index: 2;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .stock-available { background: linear-gradient(135deg, #10B981, #059669); } 
        .stock-warning { background: linear-gradient(135deg, #F59E0B, #D97706); } 
        .stock-empty { background: linear-gradient(135deg, #EF4444, #DC2626); } 

        .card-body { padding: 24px; display: flex; flex-direction: column; flex-grow: 1; }
        .card-title { font-weight: 700; font-size: 1.15rem; margin-bottom: 10px; }
        
        .variant-pill {
            background: #F4F7FE;
            color: var(--text-dark);
            border: 1px solid var(--border-subtle);
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-flex;
            font-weight: 700;
        }

        .price-container { margin-top: 15px; margin-bottom: 20px; }
        .price { color: var(--brand-pink); font-weight: 800; font-size: 1.2rem; }
        .price-range { color: var(--text-dark); font-weight: 800; font-size: 1.1rem; }

        .btn-outline-action {
            border: 2px solid var(--border-subtle);
            background: transparent; color: var(--text-muted);
            border-radius: 14px; width: 45px; height: 45px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s;
        }
        .btn-outline-action:hover {
            background: var(--brand-gradient); 
            color: white; 
            border-color: transparent; 
            box-shadow: var(--glow-shadow); 
            transform: scale(1.05);
        }

        .btn-add-cart {
            background: #F4F7FE; border: none; color: var(--brand-navy);
            border-radius: 14px; font-weight: 700; padding: 10px;
            transition: all 0.3s; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-add-cart:hover {
            background: var(--brand-gradient); color: white; box-shadow: var(--glow-shadow);
        }

        footer { margin-top: auto; background: var(--brand-gradient); padding: 20px 0; text-align: center; color: white;}

        .custom-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .custom-modal-box {
            background: var(--bg-card);
            width: 90%;
            max-width: 400px;
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transform: translateY(20px);
            animation: modalFadeIn 0.3s forwards;
        }

        .custom-settings-box {
            max-width: 500px;
            text-align: left;
        }

        @keyframes modalFadeIn {
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-icon-wrapper {
            width: 70px; height: 70px;
            background: #F4F7FE;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: var(--brand-purple);
        }

        .custom-modal-title { font-weight: 800; color: var(--brand-navy); font-size: 1.25rem; margin-bottom: 10px; }
        .custom-modal-text { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 25px; line-height: 1.5; }
        
        .custom-modal-actions { display: flex; gap: 12px; }
        .btn-modal-cancel { flex: 1; padding: 12px; border-radius: 14px; background: white; border: 2px solid var(--border-subtle); color: var(--text-muted); font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-modal-cancel:hover { border-color: var(--text-dark); color: var(--text-dark); }
        .btn-modal-confirm { flex: 1; padding: 12px; border-radius: 14px; background: var(--brand-gradient); border: none; color: white; font-weight: 700; cursor: pointer; transition: 0.3s; box-shadow: var(--glow-shadow); }
        .btn-modal-confirm:hover { transform: translateY(-2px); }

        .settings-form-label { font-weight: 700; color: var(--text-muted); font-size: 0.85rem; letter-spacing: 0.5px; margin-bottom: 8px; display: block; text-transform: uppercase;}
        .settings-input { width: 100%; padding: 12px 18px; border: 1px solid var(--border-subtle); border-radius: 14px; background: #F8FAFC; font-weight: 500; transition: all 0.3s; outline: none; margin-bottom: 20px;}
        .settings-input:focus { border-color: var(--brand-purple); box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1); background: white; }
        
        .settings-avatar-preview { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid var(--brand-pink); margin-bottom: 15px; box-shadow: var(--glow-shadow);}
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container d-lg-flex">
            <div class="nav-zone-left">
                <a class="brand-pill" href="katalog.php">
                    <img src="../assets/img/logo.png" alt="Logo" class="brand-logo-img" onerror="this.src='https://via.placeholder.com/40x40/0F172A/FFFFFF?text=7C'">
                    <span class="text-gradient fw-bold fs-5 mb-0" style="letter-spacing: -0.5px;">7CellX</span>
                </a>
                <button class="navbar-toggler ms-auto border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon" style="filter: brightness(0) invert(1);"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse nav-zone-center" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3 mt-3 mt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 active" href="katalog.php">
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
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-white" style="font-size: 0.6rem;">
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
                                <?php if (!empty($active_user['profile_picture'])): ?>
                                    <img src="../uploads/profiles/<?= htmlspecialchars($active_user['profile_picture']) ?>" class="user-nav-avatar">
                                <?php else: ?>
                                    <i class="bi bi-person-circle fs-5 text-gradient"></i>
                                <?php endif; ?>
                                <span class="text-gradient"><?= htmlspecialchars($active_user['username'] ?? $_SESSION['username'] ?? 'User') ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item d-flex align-items-center" type="button" onclick="openSettingsModal()">
                                        <i class="bi bi-gear me-2 text-muted"></i>Settings
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger fw-bold d-flex align-items-center" href="../auth/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="../auth/login.php" class="btn-white-nav px-4 text-decoration-none">
                            <i class="bi bi-box-arrow-in-right text-gradient"></i> <span class="text-gradient">Login</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </nav>

    <div class="container flex-grow-1 mb-5">
        
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success rounded-4 fw-bold mt-4 mb-0">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_SESSION['success_msg']) ?>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>

        <div class="hero-colorful">
            <div class="orb-1"></div>
            <div class="orb-2"></div>
            <div class="position-relative z-3">
                <h1 class="display-5 fw-bold text-white mb-2" style="letter-spacing: -1px;">Eksplorasi Katalog</h1>
                <p class="fs-5 text-white opacity-75 mb-0">Temukan perangkat cerdas yang mendefinisikan gaya Anda.</p>
            </div>
        </div>

        <form method="GET" action="" class="toolbar-overlap">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8 col-md-7">
                    <div class="search-box position-relative">
                        <i class="bi bi-search"></i>
                        <input class="form-control w-100 shadow-none" type="search" name="search"
                            placeholder="Cari smartphone, merk, spesifikasi..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-lg-4 col-md-5">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-sort-down-alt text-muted fs-4 d-none d-md-block"></i>
                        <select class="form-select shadow-none m-0" name="sort" onchange="this.form.submit()">
                            <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Rilisan Terbaru</option>
                            <option value="harga_asc" <?= $sort === 'harga_asc' ? 'selected' : '' ?>>Harga: Rendah ke Tinggi</option>
                            <option value="harga_desc" <?= $sort === 'harga_desc' ? 'selected' : '' ?>>Harga: Tinggi ke Rendah</option>
                            <option value="nama_asc" <?= $sort === 'nama_asc' ? 'selected' : '' ?>>Alfabetik: A-Z</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <?php if ($products->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                        <div class="card product-card position-relative">
                            
                            <?php
                            $stok = (int)$product['stok'];
                            if ($stok > 3) {
                                $badge_class = 'stock-available';
                                $badge_text = '<i class="bi bi-check-circle-fill me-1"></i> Tersedia';
                            } elseif ($stok > 0) {
                                $badge_class = 'stock-warning';
                                $badge_text = '<i class="bi bi-exclamation-circle-fill me-1"></i> Sisa ' . $stok;
                            } else {
                                $badge_class = 'stock-empty';
                                $badge_text = '<i class="bi bi-x-circle-fill me-1"></i> Habis';
                            }
                            ?>
                            <span class="badge-stock <?= $badge_class ?>"><?= $badge_text ?></span>
                            
                            <div class="card-img-wrapper">
                                <img src="../uploads/<?= htmlspecialchars($product['gambar']) ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($product['nama_barang']) ?>"
                                    onerror="this.src='https://via.placeholder.com/200x250/F8FAFC/9C27B0?text=No+Image'">
                            </div>
                            
                            <div class="card-body">
                                <h3 class="card-title text-truncate" title="<?= htmlspecialchars($product['nama_barang']) ?>">
                                    <?= htmlspecialchars($product['nama_barang']) ?>
                                </h3>
                                
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <?php
                                    $varian = json_decode($product['varian'], true);
                                    if ($varian && is_array($varian)): 
                                        foreach($varian as $v):
                                            if(isset($v['ram']) && isset($v['rom'])):
                                    ?>
                                        <span class="variant-pill">
                                            <?= htmlspecialchars($v['ram']) ?>/<?= htmlspecialchars($v['rom']) ?> GB
                                        </span>
                                    <?php 
                                            endif;
                                        endforeach;
                                    else: 
                                    ?>
                                        <span class="variant-pill">Custom Spec</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="price-container mt-auto">
                                    <?php if ($product['harga_min'] < $product['harga_max']): ?>
                                        <div class="price-range">
                                            Rp <?= number_format($product['harga_min'], 0, ',', '.') ?> - 
                                            <?= number_format($product['harga_max'], 0, ',', '.') ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="price">
                                            Rp <?= number_format($product['harga_min'], 0, ',', '.') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex align-items-center gap-2 mt-auto">
                                    <a href="detail.php?id=<?= $product['id'] ?>" class="btn-outline-action" title="Lihat Detail">
                                        <i class="bi bi-eye fs-5"></i>
                                    </a>
                                    
                                    <form action="tambah_keranjang.php" method="POST" class="m-0 flex-grow-1" onsubmit="confirmAddToCart(event, this, '<?= htmlspecialchars($product['nama_barang'], ENT_QUOTES) ?>')">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" class="btn-add-cart" <?= $stok == 0 ? 'disabled' : '' ?>>
                                            <i class="bi bi-cart-plus fs-5"></i> <?= $stok == 0 ? 'Habis' : 'Keranjang' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-5 pt-4">
                    <ul class="pagination justify-content-center">
                        <?php
                        $query_params = array_filter(['search' => $search, 'sort' => $sort]);
                        $base_url = $current_url_base . '?' . http_build_query($query_params);
                        $sep = empty($query_params) ? '' : '&';
                        ?>
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= $base_url . $sep ?>page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                        <?php endif; ?>
                        
                        <li class="page-item active"><a class="page-link" href="#" style="pointer-events: none;"><?= $page ?></a></li>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="<?= $base_url . $sep ?>page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-5 mt-4">
                <div class="display-1 text-muted mb-4 opacity-50"><i class="bi bi-search"></i></div>
                <h4 class="fw-bold" style="color: var(--brand-navy);">Produk Tidak Ditemukan</h4>
                <p class="text-muted">Kata kunci pencarian "<?= htmlspecialchars($search) ?>" tidak membuahkan hasil.</p>
                <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-outline-custom mt-3 px-4 fw-bold">Reset Pencarian</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container text-center small fw-medium opacity-75">
            <p class="mb-0">&copy; <?= date('Y') ?> 7CellX. Engineered with precision.</p>
        </div>
    </footer>

    <div class="custom-modal-overlay" id="customConfirmModal">
        <div class="custom-modal-box">
            <div class="modal-icon-wrapper">
                <i class="bi bi-cart-check-fill"></i>
            </div>
            <div class="custom-modal-title">Tambahkan ke Keranjang?</div>
            <div class="custom-modal-text">Anda akan menambahkan <strong id="modalProductName"></strong> ke keranjang belanja Anda.</div>
            <div class="custom-modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Batal</button>
                <button type="button" class="btn-modal-confirm" onclick="executeAddToCart()">Ya, Tambahkan</button>
            </div>
        </div>
    </div>

    <?php if ($active_user): ?>
    <div class="custom-modal-overlay" id="settingsModal">
        <div class="custom-modal-box custom-settings-box">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-subtle pb-3">
                <h3 class="custom-modal-title m-0"><i class="bi bi-gear-fill me-2"></i> Pengaturan Profil</h3>
                <button type="button" class="btn-close shadow-none" onclick="closeSettingsModal()"></button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="formSettings">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="text-center mb-4">
                    <img id="previewPP" src="<?= !empty($active_user['profile_picture']) ? '../uploads/profiles/' . htmlspecialchars($active_user['profile_picture']) : 'https://via.placeholder.com/90/F8FAFC/9C27B0?text=PP' ?>" class="settings-avatar-preview">
                    <label class="form-label d-block text-center" style="font-size: 0.8rem;">GANTI FOTO PROFIL</label>
                    <input type="file" name="profile_picture" id="inputPP" class="form-control form-control-sm mx-auto" accept="image/*" style="max-width: 250px; font-size: 0.8rem;" onchange="previewImage(event)">
                </div>

                <label class="settings-form-label">NAMA LENGKAP</label>
                <input type="text" name="nama" class="settings-input" value="<?= htmlspecialchars($active_user['nama']) ?>" required>

                <label class="settings-form-label">USERNAME</label>
                <input type="text" name="username" class="settings-input" value="<?= htmlspecialchars($active_user['username']) ?>" required>

                <div class="custom-modal-actions mt-2">
                    <button type="button" class="btn-modal-cancel" onclick="closeSettingsModal()">Batal</button>
                    <button type="submit" class="btn-modal-confirm"><i class="bi bi-save-fill me-2"></i> Simpan Profil</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentFormToSubmit = null;

        function confirmAddToCart(event, formElement, productName) {
            event.preventDefault();
            currentFormToSubmit = formElement;
            document.getElementById('modalProductName').textContent = productName;
            document.getElementById('customConfirmModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('customConfirmModal').style.display = 'none';
            currentFormToSubmit = null;
        }

        function executeAddToCart() {
            if (currentFormToSubmit) {
                currentFormToSubmit.submit();
            }
        }

        function openSettingsModal() {
            document.getElementById('settingsModal').style.display = 'flex';
        }

        function closeSettingsModal() {
            document.getElementById('settingsModal').style.display = 'none';
        }

        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('previewPP');
                output.src = reader.result;
            }
            if(event.target.files[0]){
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
</body>
</html>