<?php
session_start();
/** @var mysqli $conn */
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = (int) $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
$row = mysqli_fetch_assoc($data);

if (!$row) {
    header("Location: produk.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang']);
    $kategori = $_POST['kategori'];
    $stok = (int) $_POST['stok'];
    $deskripsi = trim($_POST['deskripsi']);
    $varian_json = trim($_POST['varian_json'] ?? '[]');

    $nama_file = $row['gambar'];

    // PROSES UPDATE GAMBAR
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // Hapus gambar lama jika ada
            if ($nama_file != 'default.jpg' && file_exists("../uploads/" . $nama_file)) {
                unlink("../uploads/" . $nama_file);
            }
            $nama_file = time() . "_" . uniqid() . "." . $ext;
            $tmp_file = $_FILES['gambar']['tmp_name'];
            move_uploaded_file($tmp_file, "../uploads/" . $nama_file);
        } else {
            $error = "Format gambar tidak didukung.";
        }
    }

    if (empty($error)) {
        // PROSES JSON VARIAN
        $decoded = json_decode($varian_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($decoded)) {
            $error = "Minimal satu varian RAM/ROM harus diisi.";
        } else {
            $harga_min = min(array_column($decoded, 'harga'));
            $harga_max = max(array_column($decoded, 'harga'));

            // PREPARED STATEMENT UPDATE
            $stmt = mysqli_prepare($conn, "UPDATE products SET nama_barang=?, kategori=?, harga_min=?, harga_max=?, stok=?, deskripsi=?, gambar=?, varian=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssiiisssi", $nama_barang, $kategori, $harga_min, $harga_max, $stok, $deskripsi, $nama_file, $varian_json, $id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Data produk berhasil diperbarui!";
                header("Location: produk.php");
                exit;
            } else {
                $error = "Gagal memperbarui database.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - 7CellX Admin</title>
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

        .content-card {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--card-shadow);
            max-width: 900px;
            margin: 0 auto;
        }

        .form-label {
            font-weight: 700;
            color: var(--text-muted);
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 14px;
            padding: 14px 18px;
            border: 1px solid var(--border-subtle);
            background: #F8FAFC;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--brand-purple);
            box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1);
            background: white;
        }

        .img-preview {
            width: 80px;
            height: 80px;
            border-radius: 14px;
            object-fit: contain;
            border: 2px dashed var(--border-subtle);
            background: #F8FAFC;
            padding: 5px;
        }

        .variant-container {
            background: #F8FAFC;
            padding: 24px;
            border-radius: 16px;
            border: 1px dashed var(--border-subtle);
        }

        .variant-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr) 50px;
            gap: 12px;
            margin-bottom: 12px;
            background: white;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border-subtle);
            animation: fadeIn 0.3s ease;
        }

        .btn-remove-var {
            background: #FEF2F2;
            color: #DC2626;
            border: none;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-remove-var:hover {
            background: #DC2626;
            color: white;
        }

        .btn-add-var {
            background: white;
            color: var(--brand-purple);
            border: 2px dashed var(--brand-purple);
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-add-var:hover {
            background: rgba(156, 39, 176, 0.05);
        }

        .btn-primary-custom {
            background: var(--brand-gradient);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 14px;
            font-weight: 700;
            transition: all 0.3s;
            width: 100%;
            font-size: 1.05rem;
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: var(--glow-shadow);
        }

        .btn-outline-custom {
            background: white;
            color: var(--text-muted);
            border: 2px solid var(--border-subtle);
            padding: 14px 24px;
            border-radius: 14px;
            font-weight: 700;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
        }

        .btn-outline-custom:hover {
            border-color: var(--text-dark);
            color: var(--text-dark);
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
            <div class="nav-zone-left">
                <a class="brand-pill" href="index.php">
                    <img src="../../assets/logo.png" alt="Logo" class="brand-logo-img"
                        onerror="this.src='https://via.placeholder.com/40x40/0F172A/FFFFFF?text=7C'">
                    <span class="text-gradient fw-bold fs-5 mb-0" style="letter-spacing: -0.5px;">7CellX Admin</span>
                </a>
            </div>
            <div class="collapse navbar-collapse nav-zone-center">
                <ul class="navbar-nav align-items-center gap-2">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i>
                            Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="produk.php"><i class="bi bi-box-seam"></i>
                            Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="pesanan.php"><i class="bi bi-receipt"></i>
                            Pesanan</a></li>
                </ul>
            </div>
            <div class="nav-zone-right">
                <a href="../auth/logout.php" class="btn-white-nav"><i class="bi bi-box-arrow-right text-danger"></i>
                    <span class="text-danger">Logout</span></a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="page-header justify-content-center">
                <h1><i class="bi bi-pencil-square text-muted"></i> Edit Informasi Produk</h1>
            </div>

            <div class="content-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-4 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="nama_barang" class="form-control"
                                value="<?= htmlspecialchars($row['nama_barang']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori/Brand <span class="text-danger">*</span></label>
                            <select name="kategori" class="form-select" required>
                                <option value="Samsung" <?= $row['kategori'] == 'Samsung' ? 'selected' : '' ?>>Samsung
                                </option>
                                <option value="iPhone" <?= $row['kategori'] == 'iPhone' ? 'selected' : '' ?>>iPhone
                                </option>
                                <option value="Xiaomi" <?= $row['kategori'] == 'Xiaomi' ? 'selected' : '' ?>>Xiaomi
                                </option>
                                <option value="Oppo" <?= $row['kategori'] == 'Oppo' ? 'selected' : '' ?>>Oppo</option>
                                <option value="Vivo" <?= $row['kategori'] == 'Vivo' ? 'selected' : '' ?>>Vivo</option>
                                <option value="Realme" <?= $row['kategori'] == 'Realme' ? 'selected' : '' ?>>Realme
                                </option>
                                <option value="Infinix" <?= $row['kategori'] == 'Infinix' ? 'selected' : '' ?>>Infinix
                                </option>
                                <option value="iQOO" <?= $row['kategori'] == 'iQOO' ? 'selected' : '' ?>>iQOO</option>
                                <option value="Lainnya" <?= $row['kategori'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stok Total Unit <span class="text-danger">*</span></label>
                            <input type="number" name="stok" class="form-control" value="<?= $row['stok'] ?>" min="0"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto Produk (Biarkan kosong jika tidak diubah)</label>
                            <div class="d-flex align-items-center gap-3">
                                <img src="../uploads/<?= htmlspecialchars($row['gambar']) ?>" class="img-preview"
                                    onerror="this.src='https://via.placeholder.com/80/F8FAFC/9C27B0?text=HP'">
                                <input type="file" name="gambar" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi Lengkap Produk</label>
                            <textarea name="deskripsi" class="form-control"
                                rows="4"><?= htmlspecialchars($row['deskripsi']) ?></textarea>
                        </div>
                    </div>

                    <div class="variant-container mb-4">
                        <div class="d-flex justify-content-between mb-3">
                            <label class="form-label mb-0">Konfigurasi Varian (RAM/ROM) & Harga <span
                                    class="text-danger">*</span></label>
                        </div>
                        <div class="d-grid gap-2 mb-2" style="grid-template-columns: repeat(3, 1fr) 50px;">
                            <small class="fw-bold text-muted text-center">RAM (GB)</small>
                            <small class="fw-bold text-muted text-center">ROM (GB)</small>
                            <small class="fw-bold text-muted text-center">HARGA (Rp)</small>
                            <small></small>
                        </div>

                        <div id="variant-container"></div>

                        <button type="button" id="add-variant-btn" class="btn-add-var mt-2">
                            <i class="bi bi-plus-lg"></i> Tambah Opsi Varian
                        </button>
                    </div>

                    <input type="hidden" name="varian_json" id="varian-json-input">

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn-primary-custom flex-grow-1"><i class="bi bi-save me-2"></i>
                            Simpan Perubahan Database</button>
                        <a href="produk.php" class="btn-outline-custom"><i class="bi bi-x-lg"></i> Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <div class="container small fw-medium opacity-75">
            &copy;
            <?= date('Y') ?> 7CellX Admin Panel. Engineered with precision.
        </div>
    </footer>

    <script>
        const container = document.getElementById('variant-container');
        const addBtn = document.getElementById('add-variant-btn');
        const hiddenInput = document.getElementById('varian-json-input');

        // Menarik data JSON dari Database
        const existingVariants = <?= empty($row['varian']) ? '[]' : $row['varian'] ?>;

        function createVariantRow(ram = '', rom = '', harga = '') {
            const row = document.createElement('div');
            row.className = 'variant-row';
            row.innerHTML = `
                <input type="number" class="form-control v-ram" value="${ram}" placeholder="8" required min="1">
                <input type="number" class="form-control v-rom" value="${rom}" placeholder="256" required min="1">
                <input type="number" class="form-control v-harga" value="${harga}" placeholder="5000000" required min="1000">
                <button type="button" class="btn-remove-var" onclick="removeVariant(this)"><i class="bi bi-trash3-fill"></i></button>
            `;
            container.appendChild(row);
            syncVariants();
        }

        function removeVariant(btn) {
            if (container.children.length > 1) {
                btn.closest('.variant-row').remove();
                syncVariants();
            } else {
                alert('Minimal harus ada 1 varian terdaftar!');
            }
        }

        function syncVariants() {
            const rows = document.querySelectorAll('.variant-row');
            const variants = [];
            rows.forEach(row => {
                variants.push({
                    ram: row.querySelector('.v-ram').value || '0',
                    rom: row.querySelector('.v-rom').value || '0',
                    harga: parseInt(row.querySelector('.v-harga').value) || 0
                });
            });
            hiddenInput.value = JSON.stringify(variants);
        }

        container.addEventListener('input', syncVariants);
        addBtn.addEventListener('click', () => createVariantRow());

        // Inisialisasi Data Lama
        if (existingVariants.length > 0) {
            existingVariants.forEach(v => createVariantRow(v.ram, v.rom, v.harga));
        } else {
            createVariantRow();
        }
    </script>
</body>

</html>