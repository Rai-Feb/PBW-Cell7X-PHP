<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email' AND password = '$password'");

    if (mysqli_num_rows($query) > 0) {
        $user_data = mysqli_fetch_assoc($query);
        $_SESSION['status_login'] = true;
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['nama'] = $user_data['nama'];
        $_SESSION['role'] = $user_data['role'];

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
    <title>Masuk - 7Cellectronic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --tk-green: #03AC0E;
            --tk-surface: #F8F9FA;
        }

        body {
            background-color: var(--tk-surface);
            display: flex;
            align-items: center;
            min-height: 100vh;
        }

        .card-login {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .btn-green {
            background: var(--tk-green);
            color: white;
            border-radius: 10px;
            font-weight: 700;
            padding: 12px;
        }

        .btn-green:hover {
            background: #00880B;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <h1 class="fw-bold" style="color: var(--tk-green); letter-spacing: -1.5px;">7Cellectronic</h1>
                </div>
                <div class="card card-login p-4">
                    <h4 class="fw-bold mb-4">Masuk</h4>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger py-2" style="font-size: 13px;">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="admin@gmail.com"
                                required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Kata Sandi</label>
                            <input type="password" name="password" class="form-control" placeholder="123" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-green w-100">Lanjutkan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>