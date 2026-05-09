<?php
session_start();
require_once '../config/koneksi.php';

/** @var mysqli $conn */

// PERBAIKAN BUG 2 & 3: Tombol pembatalan di pesanan.php adalah Link/URL (GET), bukan Form (POST).
if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $order_id = (int) $_GET['id'];
    $user_id = (int) $_SESSION['user_id'];

    $check = mysqli_query($conn, "SELECT status FROM orders WHERE id = $order_id AND user_id = $user_id");
    $order = mysqli_fetch_assoc($check);

    if ($order && $order['status'] == 'pending') {
        // PERBAIKAN FATAL SQL 2: Kolom di tabel order_details adalah 'jumlah', bukan 'qty'.
        $details = mysqli_query($conn, "SELECT product_id, jumlah FROM order_details WHERE order_id = $order_id");

        while ($item = mysqli_fetch_assoc($details)) {
            // Kembalikan stok ke database
            mysqli_query($conn, "UPDATE products SET stok = stok + {$item['jumlah']} WHERE id = {$item['product_id']}");
        }

        mysqli_query($conn, "UPDATE orders SET status = 'cancelled' WHERE id = $order_id");

        $_SESSION['success'] = 'Pesanan berhasil dibatalkan secara permanen.';
    }
}

header('Location: pesanan.php');
exit;
?>      