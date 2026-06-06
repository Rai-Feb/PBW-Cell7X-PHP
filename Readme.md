# PBW-Project-PHP - Website E-Commerce Elektronik

## 1. Daftar Anggota Kelompok
1. Raihan Febriahdi - 2410631170163
2. Adzki Syauki Nurfalah - 2410631170053
3. Haris Al Khanan - 2410631170024


## 2. Deskripsi dan Tujuan Website
Website ini adalah platform e-commerce sederhana untuk menjual produk elektronik (smartphone). 
Tujuan pembuatan website ini adalah untuk memenuhi tugas akhir mata kuliah Pemrograman Berbasis Web, serta mengimplementasikan konsep full-stack development menggunakan Native PHP dan MySQL.

## 3. Fitur-fitur Utama Website
**Customer:**
- Katalog produk dengan filter kategori.
- Detail produk dengan pilihan varian (RAM/ROM) dinamis.
- Keranjang belanja dan Checkout (Multi-payment: Transfer, E-Wallet, COD).
- Real-time chat dengan admin.
- Riwayat pesanan dan penerimaan invoice via email.

**Admin:**
- Dashboard untuk ringkasan penjualan.
- Manajemen produk (CRUD) dan manajemen pesanan (Update status).
- Panel chat untuk membalas pertanyaan customer.

## 4. Struktur Project dan Penjelasan Folder/File Penting

PBW-Project-PHP/
├── admin/ # Halaman khusus admin (dashboard, produk, pesanan, chat)
├── assets/ # File statis (CSS, JavaScript, gambar)
├── auth/ # Halaman login, register, dan logout
├── config/ # Konfigurasi aplikasi
│ └── koneksi.php # File koneksi database (PDO)
── customer/ # Halaman khusus customer (katalog, keranjang, checkout, dll)
── uploads/ # Folder penyimpanan gambar produk dan attachment chat
├── chat_api.php # API untuk menangani real-time chat (AJAX polling)
├── composer.json # Dependency management (PHPMailer)
├── database.sql # File dump database untuk import
└── index.php # Entry point (redirect ke halaman katalog)


## 5. Cara Menjalankan Aplikasi
1. Clone Repository
   ```bash
   git clone https://github.com/Rai-Feb/PBW-Project-PHP.git

2. Setup Database:
Buka phpMyAdmin, buat database baru bernama db_elektronik.
Import file database.sql ke dalam database tersebut.

3. Install Dependencies:
Buka terminal di folder project, jalankan composer install untuk menginstall PHPMailer.

4. Konfigurasi:
Pastikan konfigurasi database di config/koneksi.php sudah sesuai dengan settingan lokal Anda.

5. Jalankan Aplikasi:
Start Apache dan MySQL di XAMPP/Laragon.
Akses website melalui browser di http://localhost/PBW-Project-PHP/.

6. Link Video Presentasi Project

-menyusul