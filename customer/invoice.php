<?php
// invoice.php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = (int)($_GET['id'] ?? 0);
$email_msg = $_GET['msg'] ?? '';

// Ambil data order
$order_query = mysqli_query($conn, "
    SELECT o.*, u.nama as customer_name, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = $order_id AND o.user_id = " . (int)$_SESSION['user_id']
);
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    header('Location: pesanan.php');
    exit;
}

// Ambil detail produk
$items_query = mysqli_query($conn, "
    SELECT od.*, p.nama_barang, p.gambar 
    FROM order_details od 
    JOIN products p ON od.product_id = p.id 
    WHERE od.order_id = $order_id
");
$items = [];
while($item = mysqli_fetch_assoc($items_query)) {
    $items[] = $item;
}

$status_config = [
    'pending' => ['label' => 'Menunggu Pembayaran', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'paid' => ['label' => 'Dibayar - Diproses', 'color' => '#2563eb', 'bg' => '#dbeafe'],
    'shipped' => ['label' => 'Sedang Dikirim', 'color' => '#7c3aed', 'bg' => '#e0e7ff'],
    'delivered' => ['label' => 'Selesai Diterima', 'color' => '#059669', 'bg' => '#d1fae5'],
    'cancelled' => ['label' => 'Dibatalkan', 'color' => '#dc2626', 'bg' => '#fee2e2']
];

$status = $status_config[$order['status']] ?? ['label' => $order['status'], 'color' => '#64748B', 'bg' => '#F1F5F9'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_id; ?> - 7CellX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-pink: #E91E63; --brand-purple: #9C27B0; --brand-navy: #1A237E;
            --bg-main: #F4F7FE; --bg-card: #FFFFFF; --text-dark: #0F172A; --text-muted: #64748B; --border-subtle: #E2E8F0;
            --brand-gradient: linear-gradient(135deg, #E91E63 0%, #9C27B0 50%, #1A237E 100%);
            --glow-shadow: 0 15px 35px rgba(156, 39, 176, 0.2);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-main); padding: 40px 20px; color: var(--text-dark); }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid var(--border-subtle);
        }
        
        .invoice-header {
            background: var(--brand-gradient);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .invoice-header h1 { font-size: 2rem; margin-bottom: 8px; font-weight: 800; letter-spacing: -1px; }
        .invoice-header p { opacity: 0.9; font-weight: 500; }
        
        .invoice-body { padding: 40px; }
        
        .status-badge {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.9rem;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: <?php echo $status['bg']; ?>;
            color: <?php echo $status['color']; ?>;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin: 30px 0;
            padding: 24px;
            background: var(--bg-main);
            border-radius: 16px;
            border: 1px solid var(--border-subtle);
        }
        
        .info-item h4 { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-item p { font-size: 1rem; color: var(--text-dark); font-weight: 700; margin: 0; }
        
        .products-table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        .products-table th { text-align: left; padding: 14px; background: var(--bg-main); font-weight: 800; color: var(--text-dark); border-bottom: 2px solid var(--brand-purple); font-size: 0.9rem; text-transform: uppercase;}
        .products-table td { padding: 16px 14px; border-bottom: 1px solid #e5e7eb; font-weight: 500; }
        
        .product-info { display: flex; align-items: center; gap: 12px; }
        .product-info img { width: 60px; height: 60px; object-fit: contain; border-radius: 12px; background: var(--bg-main); padding: 5px; border: 1px solid var(--border-subtle); }
        
        .summary-section { background: var(--bg-main); padding: 24px; border-radius: 16px; margin-top: 30px; border: 1px solid var(--border-subtle); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--text-muted); font-weight: 600; }
        .summary-total { display: flex; justify-content: space-between; padding-top: 16px; border-top: 2px dashed var(--border-subtle); font-size: 1.3rem; font-weight: 800; color: var(--text-dark); margin-top: 12px; }
        .summary-total span:last-child { background: var(--brand-gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        
        .action-buttons { display: flex; gap: 16px; margin-top: 30px; }
        .btn-custom { flex: 1; padding: 14px; border: none; border-radius: 14px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 10px; font-size: 1rem; transition: all 0.3s; }
        .btn-primary-custom { background: var(--brand-gradient); color: white; box-shadow: var(--glow-shadow); }
        .btn-primary-custom:hover { transform: translateY(-2px); color: white; }
        .btn-outline-custom { background: white; color: var(--text-muted); border: 2px solid var(--border-subtle); }
        .btn-outline-custom:hover { background: #F8FAFC; color: var(--brand-purple); border-color: var(--brand-purple); }
        
        .alert-info-custom { padding: 16px 20px; border-radius: 12px; margin-top: 24px; background: rgba(156, 39, 176, 0.05); color: var(--brand-purple); border: 1px dashed var(--brand-purple); font-weight: 600; font-size: 0.9rem;}
        
        @media print {
            body { background: white; padding: 0; }
            .invoice-container { box-shadow: none; border: none; }
            .action-buttons { display: none; }
        }
        
        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .products-table th, .products-table td { padding: 10px; font-size: 0.9rem; }
            .product-info img { width: 40px; height: 40px; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1><i class="bi bi-box-seam me-2"></i>7CellX</h1>
            <p class="mb-0">Invoice Pesanan #<?php echo $order_id; ?></p>
            <p style="margin-top: 5px; font-size: 0.9rem;"><i class="bi bi-calendar3 me-1"></i> <?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?></p>
        </div>
        
        <div class="invoice-body">
            <div style="text-align: center;">
                <div class="status-badge">
                    <i class="bi bi-info-circle-fill me-1"></i> <?php echo $status['label']; ?>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <h4><i class="bi bi-person-fill me-2"></i>Nama Penerima</h4>
                    <p><?php echo htmlspecialchars($order['customer_name'] ?? 'Customer'); ?></p>
                    <p style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;"><?php echo htmlspecialchars($order['email'] ?? '-'); ?></p>
                </div>
                <div class="info-item">
                    <h4><i class="bi bi-geo-alt-fill me-2"></i>Alamat Pengiriman</h4>
                    <p><?php echo htmlspecialchars($order['alamat']); ?></p>
                </div>
                <div class="info-item">
                    <h4><i class="bi bi-wallet-fill me-2"></i>Metode Pembayaran</h4>
                    <p><?php echo strtoupper(str_replace('_', ' ', $order['payment_method'])); ?></p>
                </div>
                <div class="info-item">
                    <h4><i class="bi bi-clock-fill me-2"></i>Tanggal Pesan</h4>
                    <p><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
                </div>
            </div>
            
            <h3 style="margin: 30px 0 16px; font-size: 1.2rem; color: var(--text-dark); font-weight: 800;">Detail Pesanan</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Varian</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <?php if(!empty($item['gambar'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($item['gambar']); ?>" alt="Produk">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($item['nama_barang']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span style="background: white; border: 1px solid var(--border-subtle); padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700;"><?php echo htmlspecialchars($item['varian'] ?? '-'); ?></span></td>
                        <td>Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                        <td><?php echo $item['jumlah']; ?></td>
                        <td style="font-weight: 800; color: var(--brand-purple);">Rp <?php echo number_format($item['harga_satuan'] * $item['jumlah'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="summary-section">
                <div class="summary-row">
                    <span>Subtotal Produk</span>
                    <span>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span>Ongkos Kirim</span>
                    <span style="color: #10B981; font-weight: 800;">Gratis</span>
                </div>
                <div class="summary-total">
                    <span>Total Tagihan</span>
                    <span>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="pesanan.php" class="btn-custom btn-outline-custom">
                    <i class="bi bi-arrow-left"></i> Kembali ke Pesanan
                </a>
                <button onclick="window.print()" class="btn-custom btn-primary-custom">
                    <i class="bi bi-printer-fill"></i> Cetak Invoice
                </button>
            </div>
            
            <div style="text-align: center; margin-top: 40px; padding-top: 24px; border-top: 1px solid var(--border-subtle); color: var(--text-muted); font-size: 0.9rem; font-weight: 500;">
                <p><strong style="color: var(--text-dark);">7CellX</strong> - Eksplorasi Katalog Smartphone</p>
                <p style="margin-top: 8px;">Terima kasih telah berbelanja dengan kami!</p>
            </div>
        </div>
    </div>
</body>
</html>