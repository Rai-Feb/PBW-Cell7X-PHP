<?php
session_start();
/** @var mysqli $conn */
require_once '../config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../customer/katalog.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $username = explode('@', $email)[0]; // Men-generate username otomatis dari email
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Kata sandi dan konfirmasi tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Kata sandi minimal 6 karakter!";
    } else {
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $error = "Email sudah terdaftar! Silakan login.";
        } else {
            // Hashing password dengan BCRYPT
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, 'customer')");
            mysqli_stmt_bind_param($stmt, "ssss", $nama, $username, $email, $hashed_password);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Akun berhasil dibuat! Mengarahkan ke halaman login...";
                header("refresh:2;url=login.php");
            } else {
                $error = "Terjadi kesalahan teknis. Silakan coba lagi.";
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
    <title>Daftar - 7CellX</title>
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
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --brand-gradient: linear-gradient(135deg, #E91E63 0%, #9C27B0 50%, #1A237E 100%);
            --glow-shadow: 0 15px 35px rgba(156, 39, 176, 0.2);
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 40px 0;
        }

        .bg-orb-1 {
            position: absolute;
            top: 10%;
            right: -5%;
            width: 400px;
            height: 400px;
            background: rgba(156, 39, 176, 0.15);
            border-radius: 50%;
            filter: blur(60px);
            z-index: -1;
        }

        .bg-orb-2 {
            position: absolute;
            bottom: 0%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: rgba(233, 30, 99, 0.1);
            border-radius: 50%;
            filter: blur(60px);
            z-index: -1;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 500px;
            padding: 50px 40px;
        }

        .brand-text {
            font-weight: 800;
            font-size: 2rem;
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        .form-control {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 14px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .form-control:focus {
            background: #FFFFFF;
            border-color: var(--brand-purple);
            box-shadow: 0 0 0 4px rgba(156, 39, 176, 0.1);
            outline: none;
        }

        .btn-auth {
            background: var(--brand-gradient);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-auth:hover {
            transform: translateY(-3px);
            box-shadow: var(--glow-shadow);
            color: white;
        }

        .alert-custom-error {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .alert-custom-success {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="bg-orb-1"></div>
    <div class="bg-orb-2"></div>

    <div class="container d-flex justify-content-center">
        <div class="auth-card">
            <div class="text-center mb-4">
                <h1 class="brand-text d-block">Bergabung Sekarang</h1>
                <p class="text-muted fw-medium">Buat akun untuk mulai berbelanja di 7CellX</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-custom-error mb-4">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-custom-success mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NAMA LENGKAP</label>
                        <input type="text" name="nama" class="form-control" placeholder="Masukkan nama Anda" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">EMAIL</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KATA SANDI</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter"
                            required minlength="6">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">KONFIRMASI KATA SANDI</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi kata sandi"
                            required>
                    </div>

                    <button type="submit" class="btn-auth mb-4">
                        Buat Akun <i class="bi bi-person-plus-fill"></i>
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center">
                <p class="text-muted fw-medium mb-0">Sudah punya akun?
                    <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--brand-purple);">Masuk
                        di sini</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>