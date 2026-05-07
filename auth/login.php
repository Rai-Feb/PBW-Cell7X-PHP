<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Validasi langsung ke Database
    $query = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email' AND password = '$password'");
    
    if (mysqli_num_rows($query) > 0) {
        $user_data = mysqli_fetch_assoc($query);
        
        // Daftarkan sesi secara resmi
        $_SESSION['status_login'] = true;
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['nama'] = $user_data['nama'];
        $_SESSION['role'] = $user_data['role'];

        // Routing berdasarkan Role
        if ($user_data['role'] === 'admin') {
            header("Location: ../admin/index.php");
        } else {
            header("Location: ../customer/katalog.php");
        }
        exit;
    } else {
        $error = "Email atau Kata Sandi salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - 7Cellectronic</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --tk-green: #03AC0E;
            --tk-green-dark: #00880B;
            --tk-text: #31353B;
            --tk-text-muted: #8D96AA;
            --tk-border: #E5E7E9;
            --tk-bg: #FFFFFF;
            --tk-surface: #F3F4F5;
            --tk-red: #FF5C5C;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Open Sans', sans-serif; }
        body { background-color: var(--tk-surface); display: flex; justify-content: center; align-items: center; min-height: 100vh; color: var(--tk-text); }
        .login-wrapper { width: 100%; max-width: 400px; padding: 20px; }
        .logo-container { text-align: center; margin-bottom: 32px; }
        .logo { color: var(--tk-green); font-weight: 800; font-size: 36px; letter-spacing: -1.5px; text-decoration: none; }
        .card { background: var(--tk-bg); border-radius: 12px; box-shadow: 0 1px 6px 0 rgba(49, 53, 59, 0.12); padding: 32px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .card-title { font-size: 22px; font-weight: 700; color: var(--tk-text); }
        .card-link { font-size: 13px; color: var(--tk-green); text-decoration: none; font-weight: 600; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-size: 13px; color: var(--tk-text-muted); margin-bottom: 8px; font-weight: 600; }
        .input-group input { width: 100%; padding: 12px 16px; border: 1px solid var(--tk-border); border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s; color: var(--tk-text); }
        .input-group input:focus { border-color: var(--tk-green); }
        .btn-submit { width: 100%; background: var(--tk-green); color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; margin-top: 12px; transition: background 0.2s; }
        .btn-submit:hover { background: var(--tk-green-dark); }
        .error-box { background: #FFEAEA; color: var(--tk-red); padding: 12px; border-radius: 8px; font-size: 12px; font-weight: 600; margin-bottom: 20px; border: 1px solid #FFD0D0; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="logo-container">
            <a href="../customer/katalog.php" class="logo">7Cellectronic</a>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Masuk</div>
                <a href="#" class="card-link">Daftar Akun Baru</a>
            </div>
            
            <?php if (isset($error)) : ?>
                <div class="error-box"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="contoh: admin@gmail.com" required>
                </div>
                <div class="input-group">
                    <label>Kata Sandi</label>
                    <input type="password" name="password" placeholder="Masukkan kata sandi" required>
                </div>
                <button type="submit" name="login" class="btn-submit">Selanjutnya</button>
            </form>
        </div>
    </div>
</body>
</html>