# PBW-Project-PHP - Website E-Commerce Konter Handphone

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

## 4. Struktur Project dan Penjelasan File/Folder Penting
Project ini terdiri dari beberapa folder utama dan file konfigurasi yang masing-masing memiliki tanggung jawab spesifik.
Folder admin/ berisi seluruh halaman yang hanya bisa diakses oleh admin, meliputi dashboard ringkasan penjualan, manajemen produk (CRUD), manajemen pesanan, dan panel chat. 
Folder customer/ berisi halaman yang diakses oleh pembeli, seperti katalog produk, keranjang belanja, halaman checkout, dan riwayat pesanan.
Folder auth/ menangani alur autentikasi pengguna, mencakup halaman login, register, dan proses logout.
Folder assets/ menyimpan semua file statis seperti CSS, JavaScript, dan gambar antarmuka. 
Folder uploads/ digunakan sebagai tempat penyimpanan gambar produk yang diunggah admin serta attachment yang dikirim melalui fitur chat.
Folder config/ menyimpan konfigurasi aplikasi, dengan file utamanya adalah koneksi.php yang mengelola koneksi ke database menggunakan PDO.
Untuk file-file di root project: index.php adalah entry point aplikasi yang mengarahkan pengguna ke halaman katalog. 
chat_api.php adalah endpoint API yang menangani fitur real-time chat berbasis AJAX polling. 
database.sql adalah file dump database yang digunakan saat setup awal. composer.json mendefinisikan dependensi project, dalam hal ini PHPMailer untuk pengiriman invoice via email.


## 5. Cara Menjalankan Aplikasi
1. Clone Repository
   ```bash
   git clone https://github.com/Rai-Feb/PBW-Cell7X-PHP.git

2. Setup Database:
Buka phpMyAdmin, buat database baru bernama db_elektronik.
Import file database.sql ke dalam database tersebut.

3. Install Dependencies:
Buka terminal di folder project, jalankan composer install untuk menginstall PHPMailer.

4. Konfigurasi:
Pastikan konfigurasi database di config/koneksi.php sudah sesuai dengan settingan lokal Anda.

5. Jalankan Aplikasi:
Start Apache dan MySQL di XAMPP/Laragon.
Akses website melalui browser di http://localhost/PBW-Cell7X-PHP/.

6. Link Video Presentasi Project
https://drive.google.com/file/d/1JTfJPwRf7pyIX2TMyyfXBroRak5WCEXi/view?usp=sharing
