<?php
session_start();
require_once '../config/koneksi.php';

/** @var mysqli $conn */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$page_title = 'Laporan';
include 'layout_header.php';

$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM orders WHERE status IN ('paid', 'delivered')"))['total'] ?? 0;
$total_pesanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status IN ('paid', 'delivered')"))['total'] ?? 0;
$total_produk_terjual = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM order_details od JOIN orders o ON od.order_id = o.id WHERE o.status IN ('paid', 'delivered')"))['total'] ?? 0;

$pendapatan_harian = mysqli_query($conn, "
    SELECT DATE(created_at) as tanggal, SUM(total_harga) as total, COUNT(*) as jumlah_pesanan
    FROM orders 
    WHERE status IN ('paid', 'delivered')
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY tanggal ASC
");

$dates = [];
$revenue = [];
$orders = [];
while ($row = mysqli_fetch_assoc($pendapatan_harian)) {
    $dates[] = date('d/m', strtotime($row['tanggal']));
    $revenue[] = (int) $row['total'];
    $orders[] = (int) $row['jumlah_pesanan'];
}

$produk_terlaris = mysqli_query($conn, "
    SELECT p.nama_barang, p.kategori, SUM(od.jumlah) as total_terjual, SUM(od.harga_satuan * od.jumlah) as revenue
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    JOIN orders o ON od.order_id = o.id
    WHERE o.status IN ('paid', 'delivered')
    GROUP BY od.product_id
    ORDER BY total_terjual DESC
    LIMIT 10
");
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line me-3"></i>Laporan Penjualan Real-time</h1>
    <p style="color: var(--gray); margin-top: 8px;">Data penjualan 30 hari terakhir (Update otomatis)</p>
</div>

<div
    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div class="content-card" style="display: flex; align-items: center; gap: 20px;">
        <div
            style="width: 70px; height: 70px; border-radius: 16px; background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: white;">
            <i class="fas fa-wallet"></i>
        </div>
        <div>
            <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--dark); margin-bottom: 4px;">Rp
                <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
            </h3>
            <p style="color: var(--gray); font-size: 0.9rem; font-weight: 600;">Total Pendapatan</p>
        </div>
    </div>

    <div class="content-card" style="display: flex; align-items: center; gap: 20px;">
        <div
            style="width: 70px; height: 70px; border-radius: 16px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: white;">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <div>
            <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--dark); margin-bottom: 4px;">
                <?php echo $total_pesanan; ?>
            </h3>
            <p style="color: var(--gray); font-size: 0.9rem; font-weight: 600;">Total Pesanan</p>
        </div>
    </div>

    <div class="content-card" style="display: flex; align-items: center; gap: 20px;">
        <div
            style="width: 70px; height: 70px; border-radius: 16px; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: white;">
            <i class="fas fa-box"></i>
        </div>
        <div>
            <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--dark); margin-bottom: 4px;">
                <?php echo $total_produk_terjual; ?>
            </h3>
            <p style="color: var(--gray); font-size: 0.9rem; font-weight: 600;">Produk Terjual</p>
        </div>
    </div>
</div>

<div class="content-card">
    <h3 style="font-size: 1.4rem; font-weight: 700; color: var(--dark); margin-bottom: 24px;"><i
            class="fas fa-chart-area me-2"></i>Grafik Pendapatan Harian</h3>
    <canvas id="revenueChart" style="max-height: 400px;"></canvas>
</div>

<div class="content-card">
    <h3 style="font-size: 1.4rem; font-weight: 700; color: var(--dark); margin-bottom: 24px;"><i
            class="fas fa-trophy me-2"></i>10 Produk Terlaris</h3>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        #</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Produk</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Kategori</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Terjual</th>
                    <th
                        style="text-align: left; padding: 14px 12px; background: var(--cream); font-weight: 700; color: var(--dark); font-size: 0.85rem;">
                        Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($produk = mysqli_fetch_assoc($produk_terlaris)):
                    ?>
                    <tr>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;"><strong>
                                <?php echo $no++; ?>
                            </strong></td>
                        <td
                            style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: var(--dark);">
                            <?php echo htmlspecialchars($produk['nama_barang']); ?>
                        </td>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;">
                            <?php echo htmlspecialchars($produk['kategori']); ?>
                        </td>
                        <td style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6;"><span
                                style="background: var(--gold-light); color: var(--gold-dark); padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
                                <?php echo $produk['total_terjual']; ?> unit
                            </span></td>
                        <td
                            style="padding: 16px 12px; border-bottom: 1px solid #f3f4f6; font-weight: 700; color: var(--gold-dark);">
                            Rp
                            <?php echo number_format($produk['revenue'], 0, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const dates = <?php echo json_encode($dates); ?>;
    const revenue = <?php echo json_encode($revenue); ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: revenue,
                borderColor: '#d4af37',
                backgroundColor: 'rgba(212, 175, 55, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#d4af37',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { family: 'Plus Jakarta Sans', size: 12 },
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(26, 26, 46, 0.9)',
                    padding: 12,
                    callbacks: {
                        label: function (context) {
                            return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return 'Rp ' + (value / 1000000).toFixed(0) + 'Jt';
                        }
                    }
                }
            }
        }
    });

    setInterval(function () {
        location.reload();
    }, 30000);
</script>

</main>
</body>

</html>