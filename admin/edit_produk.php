<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi akses hanya untuk admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$error = '';

// Ambil data produk yang akan diedit
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

if (!$produk) {
    header('Location: produk.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang']);
    $kategori = trim($_POST['kategori']);
    $deskripsi = trim($_POST['deskripsi']);
    $varian_json = trim($_POST['varian_json'] ?? '[]');

    // Default gambar lama
    $gambar = $produk['gambar'];

    // Logika jika admin mengunggah gambar baru
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../uploads/';
            if (!is_dir($upload_path))
                mkdir($upload_path, 0777, true);

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path . $new_filename)) {
                // Hapus gambar lama jika ada dan bukan gambar default
                if (!empty($produk['gambar']) && file_exists($upload_path . $produk['gambar'])) {
                    unlink($upload_path . $produk['gambar']);
                }
                $gambar = $new_filename;
            } else {
                $error = "Gagal mengupload gambar baru.";
            }
        } else {
            $error = "Format gambar tidak didukung.";
        }
    }

    if (empty($error)) {
        $decoded = json_decode($varian_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($decoded)) {
            $error = "Minimal satu varian RAM/ROM harus diisi.";
        } else {
            // Kalkulasi harga minimum dan maksimum dari varian
            $harga_min = min(array_column($decoded, 'harga'));
            $harga_max = max(array_column($decoded, 'harga'));

            // Kalkulasi ulang total stok
            $stok_total = 0;
            foreach ($decoded as $v) {
                $stok_total += (int) ($v['stok'] ?? 0);
            }

            $stmt_update = mysqli_prepare($conn, "UPDATE products SET nama_barang = ?, kategori = ?, harga_min = ?, harga_max = ?, stok = ?, deskripsi = ?, gambar = ?, varian = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update, "ssiiisssi", $nama_barang, $kategori, $harga_min, $harga_max, $stok_total, $deskripsi, $gambar, $varian_json, $id);

            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['success'] = "Produk berhasil diperbarui!";
                header('Location: produk.php');
                exit;
            } else {
                $error = "Gagal memperbarui produk di database.";
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

        .main-content {
            padding: 40px 0;
            flex-grow: 1;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            justify-content: center;
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
            outline: none;
        }

        .variant-container {
            background: #F8FAFC;
            padding: 24px;
            border-radius: 16px;
            border: 1px dashed var(--border-subtle);
        }

        .variant-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr) 50px;
            gap: 12px;
            margin-bottom: 12px;
            background: white;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border-subtle);
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
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .btn-primary-custom:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: var(--glow-shadow);
            color: white;
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
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .btn-outline-custom:hover {
            border-color: var(--text-dark);
            color: var(--text-dark);
        }

        .current-img-preview {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 12px;
            border: 1px solid var(--border-subtle);
            background: #F8FAFC;
            padding: 5px;
            margin-top: 10px;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
            display: inline-block;
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
        <div class="container px-4">
            <div class="d-flex w-100 align-items-center">
                <a class="brand-pill" href="index.php">
                    <img src="../assets/img/logo.png" alt="Logo" class="brand-logo-img"
                        onerror="this.src='https://via.placeholder.com/40x40/0F172A/FFFFFF?text=7C'">
                    <span class="text-gradient fw-bold fs-5 mb-0" style="letter-spacing: -0.5px;">7CellX Admin</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="bi bi-pencil-square text-muted"></i> Edit Produk</h1>
            </div>
            <div class="content-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-4 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="formProduk">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="nama_barang" class="form-control"
                                value="<?= htmlspecialchars($produk['nama_barang']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori/Brand <span class="text-danger">*</span></label>
                            <select name="kategori" class="form-select" required>
                                <?php
                                $categories = ['Samsung', 'iPhone', 'Xiaomi', 'Oppo', 'Vivo', 'Realme', 'Infinix', 'iQOO', 'Lainnya'];
                                foreach ($categories as $cat) {
                                    $selected = ($produk['kategori'] === $cat) ? 'selected' : '';
                                    echo "<option value=\"$cat\" $selected>$cat</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Upload Gambar Baru (Opsional)</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*">
                            <div class="mt-2">
                                <span class="text-muted small d-block mb-1">Gambar saat ini:</span>
                                <img src="../uploads/<?= htmlspecialchars($produk['gambar']) ?>"
                                    class="current-img-preview"
                                    onerror="this.src='https://via.placeholder.com/100x100/F8FAFC/9C27B0?text=No+Image'">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi Lengkap Produk</label>
                            <textarea name="deskripsi" class="form-control"
                                rows="4"><?= htmlspecialchars($produk['deskripsi']) ?></textarea>
                        </div>
                    </div>

                    <div class="variant-container mb-4">
                        <div class="d-flex justify-content-between mb-3">
                            <label class="form-label mb-0">Konfigurasi Varian & Stok <span
                                    class="text-danger">*</span></label>
                        </div>
                        <div class="d-grid gap-2 mb-2" style="grid-template-columns: repeat(4, 1fr) 50px;">
                            <small class="fw-bold text-muted text-center">RAM (GB)</small>
                            <small class="fw-bold text-muted text-center">ROM (GB)</small>
                            <small class="fw-bold text-muted text-center">HARGA (Rp)</small>
                            <small class="fw-bold text-muted text-center">STOK</small>
                            <small></small>
                        </div>
                        <div id="variant-container"></div>
                        <button type="button" id="add-variant-btn" class="btn-add-var mt-2">
                            <i class="bi bi-plus-lg"></i> Tambah Opsi Varian
                        </button>
                    </div>

                    <input type="hidden" name="varian_json" id="varian-json-input">
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn-primary-custom flex-grow-1" id="btnSubmit">
                            <i class="bi bi-save me-2"></i> Simpan Perubahan
                        </button>
                        <a href="produk.php" class="btn-outline-custom"><i class="bi bi-x-lg me-2"></i> Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <div class="container small fw-medium opacity-75">&copy;
            <?= date('Y') ?> 7CellX Admin Panel. Engineered with precision.
        </div>
    </footer>

    <script>
        const container = document.getElementById('variant-container');
        const addBtn = document.getElementById('add-variant-btn');
        const hiddenInput = document.getElementById('varian-json-input');
        const form = document.getElementById('formProduk');
        const btnSubmit = document.getElementById('btnSubmit');

        // Mengambil data varian yang sudah ada dari PHP
        const existingVariants = <?= empty($produk['varian']) ? '[]' : $produk['varian'] ?>;

        function createVariantRow(ram = '', rom = '', harga = '', stok = '') {
            const row = document.createElement('div');
            row.className = 'variant-row';
            row.innerHTML = `
                <input type="number" class="form-control v-ram" placeholder="RAM" required min="1" value="${ram}">
                <input type="number" class="form-control v-rom" placeholder="ROM" required min="1" value="${rom}">
                <input type="number" class="form-control v-harga" placeholder="Harga" required min="1000" value="${harga}">
                <input type="number" class="form-control v-stok" placeholder="Stok" required min="0" value="${stok}">
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
                    harga: parseInt(row.querySelector('.v-harga').value) || 0,
                    stok: parseInt(row.querySelector('.v-stok').value) || 0
                });
            });
            hiddenInput.value = JSON.stringify(variants);
        }

        // Render varian yang sudah ada saat halaman dimuat
        if (existingVariants.length > 0) {
            existingVariants.forEach(v => {
                createVariantRow(v.ram, v.rom, v.harga, v.stok);
            });
        } else {
            createVariantRow(); // Buat baris kosong jika tidak ada data
        }

        container.addEventListener('input', syncVariants);
        addBtn.addEventListener('click', () => createVariantRow());

        // PENCEGAHAN DOUBLE SUBMIT
        form.addEventListener('submit', function (e) {
            syncVariants();
            setTimeout(() => {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i> Menyimpan...';
            }, 10);
        });
    </script>
</body>

</html>