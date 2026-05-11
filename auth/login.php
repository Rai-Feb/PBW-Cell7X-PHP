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

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    //Mencegah SQL Injeksi
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user_data = mysqli_fetch_assoc($result)) {
        // HYBRID VERIFICATION: Cek enkripsi BCRYPT atau Plain Text (untuk akun lama)
        if (password_verify($password, $user_data['password']) || $password === $user_data['password']) {
            $_SESSION['status_login'] = true;
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['nama'] = $user_data['nama'];
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['role'] = $user_data['role'];

            // Update status online
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET is_online = 1 WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "i", $user_data['id']);
            mysqli_stmt_execute($update_stmt);

            if ($user_data['role'] === 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../customer/katalog.php");
            }
            exit;
        } else {
            $error = "Kata sandi yang Anda masukkan salah!";
        }
    } else {
        $error = "Email tidak ditemukan di sistem kami!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - 7CellX</title>
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
        }

        .bg-orb-1 {
            position: absolute;
            top: -10%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: rgba(233, 30, 99, 0.15);
            border-radius: 50%;
            filter: blur(60px);
            z-index: -1;
        }

        .bg-orb-2 {
            position: absolute;
            bottom: -10%;
            right: -5%;
            width: 500px;
            height: 500px;
            background: rgba(26, 35, 126, 0.15);
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
            max-width: 450px;
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

        .alert-custom {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
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
                <a href="../customer/katalog.php" class="text-decoration-none d-inline-block mb-3">
                    <img src="../assets/img/logo.png" alt="Logo" style="height: 50px;"
                        onerror="this.src='https://via.placeholder.com/50x50/FFFFFF/E91E63?text=7C'">
                </a>
                <h1 class="brand-text d-block">Masuk ke 7CellX</h1>
                <p class="text-muted fw-medium">Lanjutkan eksplorasi teknologi Anda</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-custom mb-4">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">EMAIL</label>
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required
                        autofocus autocomplete="off">
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold text-muted mb-0">KATA SANDI</label>
                    </div>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan kata sandi"
                        required autocomplete="new-password">
                </div>

                <button type="submit" name="login" class="btn-auth mb-4">
                    Masuk Sekarang <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="text-center">
                <p class="text-muted fw-medium mb-0">Belum punya akun?
                    <a href="register.php" class="text-decoration-none fw-bold"
                        style="color: var(--brand-purple);">Daftar di sini</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>