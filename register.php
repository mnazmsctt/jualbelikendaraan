<?php
include 'config.php';

if (isset($_SESSION['user'])) {
    echo "<script>window.history.back();</script>";
    exit;
}

$success = false;
$message = '';

if (isset($_POST['submit'])) {
    $hasil = register($_POST);

    if ($hasil['success']) {
        $success = true;
        // Tidak perlu alert jika langsung redirect, tapi boleh ditambahkan jika ingin
        // header("Location: admin/login.php"); // Cara paling bersih (tanpa output sebelumnya)
        // exit;
    } else {
        $message = $hasil['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyStipen - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" href="assets/img/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="assets/img/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="manifest" href="assets/img/site.webmanifest">
    
    <style>
        /* CSS kamu tetap sama, tidak diubah */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
            position: relative;
            background: #000;
        }
        .bg-container {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: url('https://images.unsplash.com/photo-1552519507-da3b142c6e3d?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat;
            filter: blur(12px) brightness(0.7) saturate(1.4);
            transform: scale(1.05);
            transition: background 1.5s ease;
            z-index: 1;
        }
        .glass-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .glass-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: linear-gradient(135deg, RGBA(255,255,255,0.12), rgba(255,255,255,0.05));
            border-radius: 24px;
            pointer-events: none;
            z-index: -1;
        }
        .form-label { color: white; font-weight: 500; }
        .form-control, .input-group-text {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            backdrop-filter: blur(4px);
        }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.7); }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.6);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.3);
        }
        .btn { border-radius: 12px; font-weight: 500; transition: all 0.3s ease; }
        .btn-success {
            background: linear-gradient(45deg, #28a745, #34d058);
            border: none;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.5);
        }
        .btn-outline-secondary {
            border-color: rgba(255,255,255,0.5);
            color: white;
        }
        .btn-outline-secondary:hover {
            background: rgba(255,255,255,0.15);
            border-color: white;
            color: white;
        }
        .password-toggle { cursor: pointer; color: rgba(255, 255, 255, 0.8); }
        .password-toggle:hover { color: white; }
        .alert { border-radius: 12px; font-size: 0.9rem; padding: 0.75rem 1rem; }
    </style>
</head>
<body>

    <div class="bg-container"></div>

    <div class="glass-card">
        <div class="text-center mb-4">
            <img src="../assets/img/logo.png" alt="Logo" style="width: 80px; height: auto;">
            <h4 class="mt-3 text-white">Daftar Akun MyStipen</h4>
            <p class="text-white opacity-75 small">Buat akun baru untuk mulai</p>
        </div>

        <div id="alertContainer"></div>

        <form method="POST" id="registerForm">
            <div class="mb-3">
                <label class="form-label">Nama</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" class="form-control" name="nama" placeholder="Nama Lengkap" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                    <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">No Telepon</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone-fill"></i></span>
                    <input type="text" class="form-control" name="no_telepon" placeholder="08123456789" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-success" name="submit" type="submit">
                    <i class="bi bi-person-add me-1"></i> Daftar
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        // Jika register berhasil → tampilkan alert sukses lalu redirect
        <?php if (isset($_POST['submit']) && $hasil['success'] ?? false): ?>
            setTimeout(() => {
                const alertContainer = document.getElementById('alertContainer');
                alertContainer.innerHTML = `
                    <div class="alert alert-success text-center small">
                        Berhasil mendaftar! Mengarahkan ke halaman login...
                    </div>
                `;
                setTimeout(() => {
                    window.location.href = 'admin/login.php';
                }, 1500); // delay 1.5 detik agar user sempat baca alert
            }, 100);
        <?php endif; ?>

        // Jika gagal → tampilkan error
        <?php if (isset($_POST['submit']) && !($hasil['success'] ?? true)): ?>
            setTimeout(() => {
                const alertContainer = document.getElementById('alertContainer');
                alertContainer.innerHTML = `
                    <div class="alert alert-danger text-center small">
                        Gagal: <?= addslashes($hasil['message']) ?>
                    </div>
                `;
            }, 100);
        <?php endif; ?>
    </script>
</body>
</html>