<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// 1. Ambil data user untuk Navbar
$stmt_user = mysqli_prepare($conn, "SELECT nama, username, profile_picture FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$active_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));

// 2. Ambil data item yang akan di-checkout
$cart = [];
$checkout_items = $_SESSION['checkout_items'] ?? [];

if (isset($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $key => $qty) {
        if (in_array($key, $checkout_items)) {
            $cart[$key] = $qty;
        }
    }
}

if (empty($cart)) {
    header('Location: keranjang.php');
    exit;
}

$total = 0;
$items_data = [];
$unique_pids = array_unique(array_map(function ($k) { return explode('_', $k)[0]; }, array_keys($cart)));
$products_data = [];

if (!empty($unique_pids)) {
    $ids = implode(',', $unique_pids);
    $res = mysqli_query($conn, "SELECT id, varian, stok FROM products WHERE id IN ($ids)");
    while ($row = mysqli_fetch_assoc($res)) {
        $products_data[$row['id']] = $row;
    }
}

foreach ($cart as $key => $qty) {
    list($pid, $v_idx) = explode('_', $key);
    if (isset($products_data[$pid])) {
        $p = $products_data[$pid];
        $var_arr = json_decode($p['varian'], true);
        if (isset($var_arr[$v_idx])) {
            $v_data = $var_arr[$v_idx];
            $total += $v_data['harga'] * $qty;
            $items_data[$key] = [
                'id' => $pid, 'v_idx' => $v_idx, 'qty' => $qty,
                'harga_satuan' => $v_data['harga'],
                'label_varian' => $v_data['ram'] . '/' . $v_data['rom'] . ' GB'
            ];
        }
    }
}

// 3. Proses Checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $metode_pembayaran = $_POST['payment_method'];
    $alamat_pengiriman = trim($_POST['alamat']);

    if (empty($alamat_pengiriman)) {
        $error = "Alamat pengiriman wajib diisi!";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $status = ($metode_pembayaran === 'cod') ? 'pending' : 'paid';
            $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, total_harga, alamat, status, payment_method, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "idsss", $user_id, $total, $alamat_pengiriman, $status, $metode_pembayaran);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);

            foreach ($items_data as $item) {
                // Simpan detail order
                $stmt3 = mysqli_prepare($conn, "INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, varian) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt3, "iiids", $order_id, $item['id'], $item['qty'], $item['harga_satuan'], $item['label_varian']);
                mysqli_stmt_execute($stmt3);

                // Update stok (kurangi stok total dan varian)
                $p_id = $item['id'];
                $v_i = $item['v_idx'];
                $res_p = mysqli_query($conn, "SELECT varian, stok FROM products WHERE id = $p_id");
                $p_row = mysqli_fetch_assoc($res_p);
                $v_json = json_decode($p_row['varian'], true);
                $v_json[$v_i]['stok'] -= $item['qty'];
                $new_v_json = json_encode($v_json);
                
                mysqli_query($conn, "UPDATE products SET stok = stok - {$item['qty']}, varian = '$new_v_json' WHERE id = $p_id");
            }

            mysqli_commit($conn);
            foreach ($cart as $key => $qty) { unset($_SESSION['keranjang'][$key]); }
            unset($_SESSION['checkout_items']);
            $success = "Pesanan #$order_id berhasil dibuat! Mengarahkan...";
            header("refresh:2;url=pesanan.php");
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Gagal checkout: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - 7CellX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --brand-pink: #E91E63; --brand-purple: #9C27B0; --brand-navy: #1A237E; 
            --bg-main: #F4F7FE; --brand-gradient: linear-gradient(135deg, #E91E63 0%, #9C27B0 50%, #1A237E 100%); 
            --glow-shadow: 0 15px 35px rgba(156, 39, 176, 0.2); 
        }
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-main); min-height: 100vh; display: flex; flex-direction: column; }
        
        /* Navbar Styling */
        .navbar { background: var(--brand-gradient) !important; padding: 0.8rem 0; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); z-index: 100; }
        .nav-zone-left { flex: 1; display: flex; justify-content: flex-start; }
        .nav-zone-center { flex: 2; display: flex; justify-content: center; }
        .nav-zone-right { flex: 1; display: flex; justify-content: flex-end; }
        .brand-pill { background: #FFFFFF; padding: 6px 20px 6px 8px; border-radius: 30px; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .text-gradient { background: var(--brand-gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-link { color: rgba(255, 255, 255, 0.85) !important; font-weight: 600; margin: 0 5px; padding: 8px 16px !important; border-radius: 12px; transition: 0.3s; }
        .nav-link:hover { background: rgba(255, 255, 255, 0.2); color: #FFFFFF !important; }
        .btn-white-nav { background: #FFFFFF; color: var(--brand-purple); font-weight: 700; padding: 8px 20px; border-radius: 30px; border: none; display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .user-nav-avatar { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; }

        /* Card Styling */
        .checkout-card { background: #FFF; border-radius: 24px; padding: 40px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05); border: 1px solid #E2E8F0; margin: 40px 0; }
        .form-control { border-radius: 12px; padding: 12px 16px; border: 1px solid #E2E8F0; background: #F8FAFC; transition: 0.3s; }
        .form-control:focus { border-color: var(--brand-purple); box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1); background: #FFF; }
        .payment-option { border: 2px solid #E2E8F0; border-radius: 16px; padding: 15px 20px; margin-bottom: 15px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 15px; }
        .form-check-input:checked+.payment-option { border-color: var(--brand-pink); background: rgba(233, 30, 99, 0.05); }
        .dynamic-form { display: none; background: #F8FAFC; border: 1px dashed #E2E8F0; border-radius: 16px; padding: 20px; margin-bottom: 25px; }
        .instruction-box { background: rgba(156, 39, 176, 0.1); color: var(--brand-purple); padding: 10px 15px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; margin-bottom: 15px; }
        .btn-primary { background: var(--brand-gradient); color: white; border: none; padding: 16px; border-radius: 16px; font-weight: 700; width: 100%; transition: 0.3s; font-size: 1.1rem; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: var(--glow-shadow); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container d-lg-flex px-4">
            <div class="nav-zone-left">
                <a class="brand-pill" href="katalog.php">
                    <img src="../assets/img/logo.png" alt="Logo" style="height:30px; width:30px; border-radius:50%;" onerror="this.src='https://via.placeholder.com/40'">
                    <span class="text-gradient fw-bold fs-5 mb-0">7CellX</span>
                </a>
            </div>
            <div class="collapse navbar-collapse nav-zone-center" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link" href="katalog.php">Katalog</a></li>
                    <li class="nav-item"><a class="nav-link" href="keranjang.php">Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link" href="pesanan.php">Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="chat.php">Chat</a></li>
                </ul>
            </div>
            <div class="collapse navbar-collapse nav-zone-right" id="navbarNavRight">
                <div class="dropdown">
                    <button class="btn-white-nav dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?php if (!empty($active_user['profile_picture'])): ?>
                            <img src="../uploads/profiles/<?= htmlspecialchars($active_user['profile_picture']) ?>" class="user-nav-avatar">
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-5 text-gradient"></i>
                        <?php endif; ?>
                        <span class="text-gradient"><?= htmlspecialchars($active_user['username']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: 15px;">
                        <li><a class="dropdown-item text-danger fw-bold" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="checkout-card">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h2 class="fw-bold m-0" style="color: var(--brand-navy);"><i class="bi bi-shield-lock-fill me-2 text-muted"></i>Checkout</h2>
                        <h4 class="fw-bold m-0 text-gradient">Total: Rp <?= number_format($total, 0, ',', '.') ?></h4>
                    </div>

                    <?php if ($error): ?> <div class="alert alert-danger rounded-4 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div> <?php endif; ?>
                    <?php if ($success): ?> <div class="alert alert-success rounded-4 fw-bold"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div> <?php else: ?>
                        
                        <form method="POST" id="formCheckout">
                            <div class="mb-4">
                                <label class="fw-bold text-muted mb-2 small text-uppercase">Alamat Lengkap Pengiriman</label>
                                <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap, No. HP, dan Kode Pos" required></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="fw-bold text-muted mb-3 small text-uppercase">Pilih Metode Pembayaran</label>

                                <label class="w-100 d-block">
                                    <input class="form-check-input d-none" type="radio" name="payment_method" value="cod" checked>
                                    <div class="payment-option">
                                        <i class="bi bi-truck fs-3 text-success"></i>
                                        <div><h5 class="mb-0 fw-bold">Cash on Delivery (COD)</h5><small class="text-muted">Bayar tunai saat kurir mengantar barang.</small></div>
                                    </div>
                                </label>

                                <label class="w-100 d-block">
                                    <input class="form-check-input d-none" type="radio" name="payment_method" value="bank">
                                    <div class="payment-option">
                                        <i class="bi bi-bank fs-3" style="color: var(--brand-navy);"></i>
                                        <div><h5 class="mb-0 fw-bold">Transfer Bank (BCA)</h5><small class="text-muted">Verifikasi otomatis setelah transfer.</small></div>
                                    </div>
                                </label>

                                <label class="w-100 d-block">
                                    <input class="form-check-input d-none" type="radio" name="payment_method" value="dana">
                                    <div class="payment-option">
                                        <i class="bi bi-wallet2 fs-3 text-primary"></i>
                                        <div><h5 class="mb-0 fw-bold">E-Wallet DANA</h5><small class="text-muted">Transfer cepat via aplikasi DANA.</small></div>
                                    </div>
                                </label>
                            </div>

                            <div class="dynamic-form" id="extraPaymentForm">
                                <div class="instruction-box" id="paymentInstructions"></div>
                                <div class="mb-2">
                                    <label class="fw-bold text-dark mb-2 small">Konfirmasi Nominal Transfer (Rp)</label>
                                    <input type="number" class="form-control" id="inputNominal" placeholder="Contoh: <?= $total ?>" min="0">
                                    <div class="form-text text-muted mt-2">Masukan nominal yang Anda transfer. Jika lebih, akan otomatis dibulatkan sesuai total tagihan.</div>
                                </div>
                            </div>

                            <button type="submit" class="btn-primary mt-3">
                                <i class="bi bi-check-circle-fill me-2"></i>Konfirmasi & Buat Pesanan
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const totalTagihan = <?= $total ?>;
        const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
        const extraForm = document.getElementById('extraPaymentForm');
        const inputNominal = document.getElementById('inputNominal');
        const paymentInstructions = document.getElementById('paymentInstructions');

        // Logic tampilkan form nominal jika bukan COD
        paymentRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.value === 'cod') {
                    extraForm.style.display = 'none';
                    inputNominal.removeAttribute('required');
                } else {
                    extraForm.style.display = 'block';
                    inputNominal.setAttribute('required', 'true');
                    if (e.target.value === 'bank') {
                        paymentInstructions.innerHTML = 'Silakan transfer ke <strong>BCA 9810 63 210</strong> (a.n 7CellX)';
                    } else {
                        paymentInstructions.innerHTML = 'Silakan kirim saldo ke <strong>DANA 0812 3456 7890</strong> (a.n 7CellX)';
                    }
                }
            });
        });

        // REVISI: Logika otomatis pembulatan nominal jika input melebihi total tagihan
        inputNominal.addEventListener('input', (e) => {
            let val = parseInt(e.target.value);
            if (val > totalTagihan) {
                e.target.value = totalTagihan; // Otomatis disesuaikan ke total harga checkout
            }
        });

        document.getElementById('formCheckout').addEventListener('submit', (e) => {
            const method = document.querySelector('input[name="payment_method"]:checked').value;
            if (method !== 'cod') {
                const nominal = parseInt(inputNominal.value);
                if (isNaN(nominal) || nominal < totalTagihan) {
                    e.preventDefault();
                    alert('Nominal yang Anda masukkan belum mencukupi total tagihan (Rp ' + totalTagihan.toLocaleString('id-ID') + ').');
                }
            }
        });
    </script>
</body>
</html>