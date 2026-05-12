<?php
include '../config.php'; 

if (isset($_SESSION['user'])) {
    header("Location: kendaraan.php");
    exit;
}

$error = '';
if (isset($_POST['submit'])) {
    $login = loginUser($_POST['email'], $_POST['password']);
    if ($login['success']) {
        header("Location: kendaraan.php");
        exit;
    } else {
        $error = $login['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyStipen-Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<!-- Favicon -->
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" href="../assets/img/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="../assets/img/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="../assets/img/apple-touch-icon.png">
    <link rel="manifest" href="../assets/img/site.webmanifest">

    <style>
        /* ... (Liquid Glass CSS) ... */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; overflow: hidden; position: relative; background: #000; }
        .bg-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url('https://images.unsplash.com/photo-1552519507-da3b142c6e3d?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat; filter: blur(12px) brightness(0.7) saturate(1.4); transform: scale(1.05); transition: background 1.5s ease; z-index: 1; }
        .glass-card { position: relative; z-index: 2; width: 100%; max-width: 420px; padding: 2.5rem; background: rgba(255, 255, 255, 0.15); border-radius: 24px; backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%); border: 1.5px solid rgba(255, 255, 255, 0.2); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4); animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .glass-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(255,255,255,0.12), rgba(255,255,255,0.05)); border-radius: 24px; pointer-events: none; z-index: -1; }
        .form-label { color: white; font-weight: 500; }
        .form-control, .input-group-text { background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.3); color: white; backdrop-filter: blur(4px); }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.7); }
        .form-control:focus { background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.6); color: white; box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.3); }
        .btn-success { background: linear-gradient(45deg, #28a745, #34d058); border: none; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40, 167, 69, 0.5); }
        .text-primary { color: #4fc3f7 !important; }
        .password-toggle { cursor: pointer; color: rgba(255, 255, 255, 0.8); }
        .password-toggle:hover { color: white; }

        /* Footer Pencipta */
        .creator-footer {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            text-align: center;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }
        .creator-footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
        }
        .creator-footer a:hover {
            color: #4fc3f7;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="bg-container"></div>

    <div class="glass-card">
    <div class="text-center mb-4">
        <img src="../assets/img/logo.png" 
             alt="Logo" 
             style="width: 80px; height: auto;">

        <h4 class="mt-3 text-white">Login ke MyStipen</h4>
        <p class="text-white opacity-75 small">Masuk untuk melanjutkan</p>
    </div>


        <?php if ($error): ?>
            <div class="alert alert-danger text-center small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                    <input type="email" class="form-control" name="email" placeholder="name@example.com" required autofocus>
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
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </button>
                <a class="btn btn-primary" href="../register.php">
                    <i class="bi bi-person-add me-1"></i> Daftar Sekarang!
                </a>
                <a href="../index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </form>
    </div>
    <div class="creator-footer">
        Dibuat Oleh : <a href="https://github.com/nazmifirdaus" target="_blank">Nazmi Firdaus & Febriansyah</a>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>