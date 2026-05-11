<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int) $_POST['order_id'];
    $new_status = $_POST['status'];
    $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    mysqli_stmt_execute($stmt);
    header('Location: pesanan.php');
    exit;
}

if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    $stmt_delete = mysqli_prepare($conn, "DELETE FROM orders WHERE id = ?");
    mysqli_stmt_bind_param($stmt_delete, "i", $delete_id);
    mysqli_stmt_execute($stmt_delete);
    header('Location: pesanan.php');
    exit;
}

$orders_data = [];
$res_orders = mysqli_query($conn, "SELECT o.*, u.nama as customer_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");

while ($row = mysqli_fetch_assoc($res_orders)) {
    $oid = $row['id'];
    $res_items = mysqli_query($conn, "SELECT od.*, p.nama_barang FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = $oid");
    $row['items'] = mysqli_fetch_all($res_items, MYSQLI_ASSOC);
    $orders_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - 7CellX Admin</title>
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

        .badge {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background: #FFFBEB;
            color: #D97706;
            border: 1px solid #FDE68A;
        }

        .badge-paid {
            background: #EFF6FF;
            color: #2563EB;
            border: 1px solid #BFDBFE;
        }

        .badge-shipped {
            background: #F5F3FF;
            color: #7C3AED;
            border: 1px solid #DDD6FE;
        }

        .badge-delivered {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        .badge-cancelled {
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

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: var(--bg-card);
            width: 90%;
            max-width: 450px;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transform: translateY(20px);
            animation: modalFadeIn 0.3s forwards;
        }

        .modal-box-large {
            max-width: 700px;
        }

        .modal-header-custom {
            margin-bottom: 24px;
            text-align: center;
        }

        .modal-header-custom h3 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--brand-navy);
            margin-bottom: 10px;
        }

        .form-select {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            font-size: 1rem;
            background: #F8FAFC;
            font-weight: 500;
            outline: none;
            margin-bottom: 25px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-outline-custom {
            flex: 1;
            padding: 14px;
            border-radius: 14px;
            background: white;
            border: 2px solid var(--border-subtle);
            color: var(--text-muted);
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-align: center;
        }

        .btn-primary-custom {
            flex: 1;
            padding: 14px;
            border-radius: 14px;
            background: var(--brand-gradient);
            border: none;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: var(--glow-shadow);
            text-align: center;
        }

        .detail-item-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed var(--border-subtle);
        }

        .detail-item-row:last-child {
            border-bottom: none;
        }

        footer {
            margin-top: auto;
            background: var(--brand-gradient);
            padding: 20px 0;
            text-align: center;
            color: white;
        }

        @keyframes modalFadeIn {
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
                    <li class="nav-item"><a class="nav-link d-flex align-items-center gap-2" href="produk.php"><i
                                class="bi bi-box-seam"></i> Produk</a></li>
                    <li class="nav-item"><a class="nav-link active d-flex align-items-center gap-2"
                            href="pesanan.php"><i class="bi bi-receipt"></i> Pesanan</a></li>
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
                <h1><i class="bi bi-receipt text-muted"></i> Kelola Pesanan</h1>
            </div>

            <div class="content-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Tanggal</th>
                                <th>Total Bayar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders_data as $order): ?>
                                <tr>
                                    <td class="fw-bold" style="color: var(--brand-purple);">#
                                        <?= $order['id']; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($order['customer_name'] ?? 'Guest'); ?>
                                    </td>
                                    <td class="text-muted"><i class="bi bi-calendar2 me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </td>
                                    <td class="fw-bold">Rp
                                        <?= number_format($order['total_harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td><span class="badge badge-<?= $order['status']; ?>">
                                            <?= strtoupper($order['status']); ?>
                                        </span></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn-action"
                                                onclick='openDetailModal(<?= json_encode($order) ?>)'>
                                                <i class="bi bi-eye"></i> Detail
                                            </button>
                                            <button class="btn-action"
                                                onclick="openStatusModal(<?= $order['id']; ?>, '<?= $order['status']; ?>')">
                                                <i class="bi bi-pencil-square"></i> Status
                                            </button>
                                            <a href="pesanan.php?delete=<?= $order['id']; ?>" class="btn-action btn-delete"
                                                onclick="return confirm('Hapus histori pesanan ini?')">
                                                <i class="bi bi-trash3-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="statusModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header-custom">
                <h3>Update Status</h3>
                <p class="text-muted small mb-0">Pesanan #<span id="displayOrderId"></span></p>
            </div>
            <form method="POST">
                <input type="hidden" name="order_id" id="modalOrderId">
                <select name="status" id="modalStatus" class="form-select" required>
                    <option value="pending">PENDING</option>
                    <option value="paid">PAID</option>
                    <option value="shipped">SHIPPED</option>
                    <option value="delivered">DELIVERED</option>
                    <option value="cancelled">CANCELLED</option>
                </select>
                <div class="modal-actions">
                    <button type="button" class="btn-outline-custom" onclick="closeModal('statusModal')">Batal</button>
                    <button type="submit" name="update_status" class="btn-primary-custom">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="detailModal" class="modal-overlay">
        <div class="modal-box modal-box-large">
            <div class="modal-header-custom"
                style="text-align: left; border-bottom: 1px solid var(--border-subtle); padding-bottom: 15px;">
                <h3><i class="bi bi-box-seam me-2"></i> Detail Pesanan #<span id="detOrderId"></span></h3>
            </div>
            <div id="detailItemsContainer" style="max-height: 400px; overflow-y: auto; margin-bottom: 20px;"></div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                <span class="fw-bold text-muted">TOTAL TAGIHAN</span>
                <span class="fw-bold fs-4 text-gradient" id="detTotal"></span>
            </div>
            <div class="modal-actions mt-4">
                <button type="button" class="btn-primary-custom w-100"
                    onclick="closeModal('detailModal')">Tutup</button>
            </div>
        </div>
    </div>

    <footer>
        <div class="container small fw-medium opacity-75">&copy;
            <?= date('Y') ?> 7CellX
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('displayOrderId').innerText = orderId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function openDetailModal(order) {
            document.getElementById('detOrderId').innerText = order.id;
            document.getElementById('detTotal').innerText = 'Rp ' + parseInt(order.total_harga).toLocaleString('id-ID');

            let html = '';
            order.items.forEach(item => {
                const subtotal = parseInt(item.harga_satuan) * parseInt(item.jumlah);
                html += `
                <div class="detail-item-row">
                    <div>
                        <div class="fw-bold" style="color: var(--brand-navy);">${item.nama_barang}</div>
                        <div class="small text-muted"><span class="badge bg-light text-dark border me-2">${item.varian}</span> x${item.jumlah} Unit</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">@ Rp ${parseInt(item.harga_satuan).toLocaleString('id-ID')}</div>
                        <div class="fw-bold">Rp ${subtotal.toLocaleString('id-ID')}</div>
                    </div>
                </div>`;
            });
            document.getElementById('detailItemsContainer').innerHTML = html;
            document.getElementById('detailModal').classList.add('active');
        }

        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
    </script>
</body>

</html>