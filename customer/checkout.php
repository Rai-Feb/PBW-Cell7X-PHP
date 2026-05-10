<?php
session_start();
require_once '../config/koneksi.php';

/** @var mysqli $conn */

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$cart = [];
$checkout_items = $_SESSION['checkout_items'] ?? [];

// Memastikan hanya memproses barang yang di-check di keranjang
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

// Menghitung Total Harga & Pengecekan Stok Ulang untuk Varian
$total = 0;
$items_data = [];

// Kumpulkan ID unik untuk query
$unique_pids = array_unique(array_map(function ($k) {
    return explode('_', $k)[0]; }, array_keys($cart)));
$products_data = [];

if (!empty($unique_pids)) {
    $ids = implode(',', $unique_pids);
    $res = mysqli_query($conn, "SELECT id, harga_min, stok, varian FROM products WHERE id IN ($ids)");
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

            if ($v_data['stok'] >= $qty) {
                $total += $v_data['harga'] * $qty;
                $items_data[$key] = [
                    'id' => $pid,
                    'v_idx' => $v_idx,
                    'qty' => $qty,
                    'harga_satuan' => $v_data['harga'],
                    'label_varian' => $v_data['ram'] . '/' . $v_data['rom'] . ' GB'
                ];
            } else {
                $error = "Stok untuk varian " . $v_data['ram'] . "/" . $v_data['rom'] . " GB tidak mencukupi.";
                break;
            }
        } else {
            $error = "Varian tidak ditemukan.";
            break;
        }
    } else {
        $error = "Produk tidak ditemukan.";
        break;
    }
}

// Pemrosesan Checkout (Ke Database)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $metode_pembayaran = $_POST['payment_method'];
    $alamat_pengiriman = trim($_POST['alamat']);

    if (empty($alamat_pengiriman)) {
        $error = "Alamat pengiriman wajib diisi!";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $status = 'pending';
            $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, total_harga, alamat, status, payment_method, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "idsss", $user_id, $total, $alamat_pengiriman, $status, $metode_pembayaran);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);

            foreach ($items_data as $key => $item) {
                $pid = $item['id'];
                $v_idx = $item['v_idx'];
                $qty = $item['qty'];

                $stmt2 = mysqli_prepare($conn, "SELECT stok, varian FROM products WHERE id = ? FOR UPDATE");
                mysqli_stmt_bind_param($stmt2, "i", $pid);
                mysqli_stmt_execute($stmt2);
                $res2 = mysqli_stmt_get_result($stmt2);
                $product_db = mysqli_fetch_assoc($res2);

                $varian_json = json_decode($product_db['varian'], true);

                if ($varian_json[$v_idx]['stok'] < $qty) {
                    throw new Exception("Stok produk id $pid tidak cukup saat diproses");
                }

                // Kurangi stok di dalam JSON varian
                $varian_json[$v_idx]['stok'] -= $qty;
                $new_varian_json = json_encode($varian_json);

                $stmt3 = mysqli_prepare($conn, "INSERT INTO order_details (order_id, product_id, jumlah, harga_satuan, varian) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt3, "iiids", $order_id, $pid, $qty, $item['harga_satuan'], $item['label_varian']);
                mysqli_stmt_execute($stmt3);

                // Update tabel products: kurangi stok total & update JSON variannya
                $stmt4 = mysqli_prepare($conn, "UPDATE products SET stok = stok - ?, varian = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt4, "isi", $qty, $new_varian_json, $pid);
                mysqli_stmt_execute($stmt4);
            }

            mysqli_commit($conn);

            // Hapus HANYA item yang di-checkout dari session keranjang utama
            foreach ($cart as $key => $qty) {
                unset($_SESSION['keranjang'][$key]);
            }
            unset($_SESSION['checkout_items']);

            $success = "Pesanan berhasil dibuat! Order ID: #$order_id";
            header("refresh:2;url=pesanan.php");
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Gagal checkout: " . $e->getMessage();
        }
    }
}

$stmt_user = mysqli_prepare($conn, "SELECT nama, username, profile_picture FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$active_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - 7CellX</title>
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
            --glow-shadow: 0 15px 35px rgba(156, 39, 176, 0.2);
            --card-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 10px;
            margin-top: 15px !important;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: #F8FAFC;
            color: var(--brand-purple);
        }

        .dropdown-item.text-danger:hover {
            background-color: #FEF2F2;
            color: #DC2626 !important;
        }

        .checkout-card {
            background: var(--bg-card);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-subtle);
            margin-top: 40px;
            margin-bottom: 60px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid var(--border-subtle);
            background: #F8FAFC;
            font-weight: 500;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--brand-purple);
            box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1);
            background: white;
            outline: none;
        }

        .payment-option {
            border: 2px solid var(--border-subtle);
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .payment-option:hover {
            border-color: var(--brand-purple);
            background: #F8FAFC;
        }

        .form-check-input:checked+.payment-option {
            border-color: var(--brand-pink);
            background: rgba(233, 30, 99, 0.05);
        }

        .dynamic-form {
            display: none;
            background: #F8FAFC;
            border: 1px dashed var(--border-subtle);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            animation: fadeIn 0.3s ease;
        }

        .instruction-box {
            background: rgba(156, 39, 176, 0.1);
            color: var(--brand-purple);
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .btn-primary {
            background: var(--brand-gradient);
            color: white;
            border: none;
            padding: 16px 28px;
            border-radius: 16px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s;
            font-size: 1.05rem;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--glow-shadow);
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
            <div class="nav-zone-left">
                <a class="brand-pill" href="katalog.php">
                    <img src="../assets/img/logo.png" alt="Logo" class="brand-logo-img"
                        onerror="this.src='https://via.placeholder.com/40x40/0F172A/FFFFFF?text=7C'">
                    <span class="text-gradient fw-bold fs-5 mb-0" style="letter-spacing: -0.5px;">7CellX</span>
                </a>
                <button class="navbar-toggler ms-auto border-0 shadow-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon" style="filter: brightness(0) invert(1);"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse nav-zone-center" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="katalog.php"><i
                                class="bi bi-grid-fill fs-5"></i> Katalog</a></li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 position-relative" href="keranjang.php">
                            <i class="bi bi-cart3 fs-5"></i> Keranjang
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="pesanan.php"><i
                                class="bi bi-receipt fs-5"></i> Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="chat.php"><i
                                class="bi bi-chat-dots fs-5"></i> Chat</a></li>
                </ul>
            </div>

            <div class="collapse navbar-collapse nav-zone-right" id="navbarNavRight">
                <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0 w-100 justify-content-lg-end">
                    <div class="dropdown">
                        <button class="btn-white-nav dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <?php if (!empty($active_user['profile_picture'])): ?>
                                <img src="../uploads/profiles/<?= htmlspecialchars($active_user['profile_picture']) ?>"
                                    class="user-nav-avatar">
                            <?php else: ?>
                                <i class="bi bi-person-circle fs-5 text-gradient"></i>
                            <?php endif; ?>
                            <span class="text-gradient">
                                <?= htmlspecialchars($active_user['username'] ?? $_SESSION['username'] ?? 'User') ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger fw-bold d-flex align-items-center"
                                    href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="checkout-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="fw-bold m-0" style="color: var(--brand-navy);"><i
                                class="bi bi-shield-lock-fill me-2 text-muted"></i> Checkout Aman</h2>
                        <h4 class="fw-bold m-0 text-gradient">Rp
                            <?= number_format($total, 0, ',', '.') ?>
                        </h4>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-4 fw-bold"><i
                                class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success rounded-4 fw-bold"><i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($success) ?> <br> <span class="fw-normal">Mengarahkan ke pesanan...</span>
                        </div>
                    <?php else: ?>
                        <form method="POST" id="formCheckout">
                            <div class="mb-4">
                                <label class="fw-bold text-muted mb-2 small">ALAMAT PENGIRIMAN (WAJIB)</label>
                                <textarea name="alamat" class="form-control" rows="3"
                                    placeholder="Masukkan alamat lengkap RT/RW, Kec, Kab, Provinsi" required></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="fw-bold text-muted mb-3 small">METODE PEMBAYARAN</label>

                                <label class="w-100 d-block">
                                    <input class="form-check-input d-none" type="radio" name="payment_method" value="cod"
                                        checked>
                                    <div class="payment-option">
                                        <i class="bi bi-cash-stack fs-3" style="color: #10B981;"></i>
                                        <div>
                                            <h5 class="mb-0 fw-bold">Cash on Delivery (COD)</h5>
                                            <small class="text-muted">Bayar tunai langsung saat kurir tiba di
                                                lokasi.</small>
                                        </div>
                                    </div>
                                </label>

                                <label class="w-100 d-block">
                                    <input class="form-check-input d-none" type="radio" name="payment_method" value="bank">
                                    <div class="payment-option">
                                        <i class="bi bi-bank fs-3" style="color: var(--brand-navy);"></i>
                                        <div>
                                            <h5 class="mb-0 fw-bold">Transfer Bank</h5>
                                            <small class="text-muted">Transfer manual via ATM / M-Banking.</small>
                                        </div>
                                    </div>
                                </label>

                                <label class="w-100 d-block">
                                    <input class="form-check-input d-none" type="radio" name="payment_method" value="dana">
                                    <div class="payment-option">
                                        <i class="bi bi-wallet2 fs-3" style="color: var(--brand-pink);"></i>
                                        <div>
                                            <h5 class="mb-0 fw-bold">DANA</h5>
                                            <small class="text-muted">Pembayaran menggunakan saldo E-Wallet DANA.</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div class="dynamic-form" id="extraPaymentForm">
                                <div class="instruction-box" id="paymentInstructions"></div>
                                <div class="mb-3">
                                    <label class="fw-bold text-dark mb-2 small" id="labelRekening">Nomor
                                        Akun/Rekening</label>
                                    <input type="number" class="form-control" id="inputRekening"
                                        placeholder="Masukkan nomor" min="0">
                                </div>
                                <div class="mb-2">
                                    <label class="fw-bold text-dark mb-2 small">Nominal Transfer (Rp)</label>
                                    <input type="number" class="form-control" id="inputNominal"
                                        placeholder="Masukkan nominal" min="0">
                                </div>
                            </div>

                            <button type="submit" class="btn-primary">
                                <i class="bi bi-check-circle-fill me-2"></i> Konfirmasi & Buat Pesanan
                            </button>
                            <a href="keranjang.php"
                                class="btn btn-link text-muted fw-bold text-decoration-none d-block text-center mt-3">Kembali
                                ke Keranjang</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center small fw-medium opacity-75">
            <p class="mb-0">&copy;
                <?= date('Y') ?> 7CellX. Engineered with precision.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const totalTagihan = <?= $total ?>;
        const formCheckout = document.getElementById('formCheckout');
        const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
        const extraForm = document.getElementById('extraPaymentForm');
        const inputRekening = document.getElementById('inputRekening');
        const inputNominal = document.getElementById('inputNominal');
        const labelRekening = document.getElementById('labelRekening');
        const paymentInstructions = document.getElementById('paymentInstructions');

        paymentRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.value === 'cod') {
                    extraForm.style.display = 'none';
                    inputRekening.removeAttribute('required');
                    inputNominal.removeAttribute('required');
                } else {
                    extraForm.style.display = 'block';
                    inputRekening.setAttribute('required', 'true');
                    inputNominal.setAttribute('required', 'true');

                    if (e.target.value === 'bank') {
                        labelRekening.textContent = 'Nomor Rekening Pengirim';
                        inputRekening.placeholder = 'Contoh: 1234567890';
                        paymentInstructions.innerHTML = 'Silakan transfer ke <strong>Bank BCA 9810 63 210</strong> (a.n 7CellX)';
                    } else if (e.target.value === 'dana') {
                        labelRekening.textContent = 'Nomor HP DANA Pengirim';
                        inputRekening.placeholder = 'Contoh: 081234567890';
                        paymentInstructions.innerHTML = 'Silakan transfer saldo ke <strong>DANA 1234 5678 9101</strong> (a.n 7CellX)';
                    }
                }
            });
        });

        inputNominal.addEventListener('input', (e) => {
            let val = parseInt(e.target.value);
            if (val > totalTagihan) {
                e.target.value = totalTagihan;
            }
        });

        if (formCheckout) {
            formCheckout.addEventListener('submit', (e) => {
                const method = document.querySelector('input[name="payment_method"]:checked').value;
                if (method !== 'cod') {
                    const nominal = parseInt(inputNominal.value);
                    if (isNaN(nominal) || nominal < totalTagihan) {
                        e.preventDefault();
                        alert('Saldo/Nominal tidak cukup! Tagihan Anda adalah Rp ' + totalTagihan.toLocaleString('id-ID') + '.');
                    }
                }
            });
        }
    </script>
</body>

</html>