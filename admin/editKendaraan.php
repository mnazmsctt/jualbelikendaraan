<?php
include '../config.php';

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$role = $_SESSION['user']['role'];
$user_id = $_SESSION['user']['id'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
  echo "<script>alert('ID tidak valid'); window.location='kendaraan.php';</script>";
  exit;
}

// Cek akses
$kendaraan = getDetailKendaraan($id);
if (!$kendaraan) {
  echo "<script>alert('Kendaraan tidak ditemukan'); window.location='kendaraan.php';</script>";
  exit;
}

if ($role != 1 && $kendaraan['user_id'] != $user_id) {
  echo "<script>alert('Akses ditolak'); window.location='kendaraan.php';</script>";
  exit;
}

// Proses update
$success = false;
if ($_POST) {
  $result = updateKendaraan($id, $_POST, $_FILES, $kendaraan['gambar'], $kendaraan['bukti_transfer']);
  if ($result['success']) {
    $success = true;
  } else {
    $error = $result['message'];
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Kendaraan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --radius: 1.8rem;
      --shadow: 0 15px 35px rgba(0,0,0,0.1);
      --transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
    }
    .light-mode { 
      --glass: rgba(255,255,255,0.38); 
      --border: rgba(255,255,255,0.5); 
      --accent: #1565C0; 
      --bg: #f8fafc;
      --card-bg: rgba(255,255,255,0.7);
    }
    .dark-mode { 
      --glass: rgba(20,20,40,0.55); 
      --border: rgba(255,255,255,0.2); 
      --accent: #42A5F5; 
      --bg: #0f172a;
      --card-bg: rgba(30,30,50,0.7);
    }
    body { 
      background: linear-gradient(135deg, var(--bg) 0%, #e2e8f0 100%); 
      background-attachment: fixed;
      color: var(--text); 
      font-family: 'Inter', sans-serif; 
      min-height: 100vh; 
      transition: var(--transition); 
    }
    .glass-card { 
      background: var(--glass); 
      backdrop-filter: blur(18px); 
      border: 1px solid var(--border); 
      border-radius: var(--radius); 
      box-shadow: var(--shadow); 
      padding: 2rem; 
      transition: var(--transition);
    }
    .form-control, .form-select { 
      background: var(--card-bg); 
      border: 1px solid var(--border); 
      border-radius: 1.2rem; 
      padding: 1rem 1.3rem; 
      font-size: 1rem; 
      transition: var(--transition);
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 0.2rem rgba(21,101,192,.25);
    }
    .btn-oval { 
      background: var(--accent); 
      color: white; 
      border: none; 
      border-radius: 50px; 
      padding: 0.9rem 2.2rem; 
      font-weight: 600; 
      transition: var(--transition);
    }
    .btn-oval:hover { 
      background: #0D47A1; 
      transform: translateY(-3px); 
      box-shadow: 0 8px 20px rgba(13,71,161,.3);
    }
    .preview-img { 
      max-height: 200px; 
      object-fit: cover; 
      border-radius: 1rem; 
      border: 2px dashed var(--border);
    }
    .navbar { 
      backdrop-filter: blur(12px); 
      padding: 1.5rem 0; 
      transition: var(--transition);
    }
    .navbar.scrolled { 
      background: var(--glass) !important; 
      border-bottom: 1px solid var(--border); 
      padding: 1rem 0; 
    }
  </style>
</head>
<body class="light-mode">

  <nav class="navbar navbar-expand-lg fixed-top" id="nav">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="kendaraan.php">Admin Panel</a>
      <a href="kendaraan.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
    </div>
  </nav>

  <main class="container py-5 mt-5">
    <div class="glass-card mx-auto" style="max-width: 800px;">
      <h3 class="text-center mb-4" style="font:700 2rem 'Poppins',sans-serif; color:var(--accent);">
        Edit Kendaraan
      </h3>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger text-center">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nama</label>
            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($kendaraan['nama']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Kategori</label>
            <select name="kategori" class="form-select" required>
              <?php foreach (getKategori() as $k): ?>
                <option value="<?= $k ?>" <?= $kendaraan['kategori'] == $k ? 'selected' : '' ?>><?= $k ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Merek</label>
            <input type="text" name="merek" class="form-control" value="<?= htmlspecialchars($kendaraan['merek']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tipe</label>
            <input type="text" name="tipe" class="form-control" value="<?= htmlspecialchars($kendaraan['tipe']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Tahun</label>
            <input type="number" name="tahun" class="form-control" value="<?= $kendaraan['tahun'] ?>" min="1900" max="2100" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Harga</label>
            <input type="number" name="harga" class="form-control" value="<?= $kendaraan['harga'] ?>" min="0" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Stok</label>
            <input type="number" name="stok" class="form-control" value="<?= $kendaraan['stok'] ?>" min="0" required>
          </div>
          <?php if ($role == 1): ?>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach ($statusLabels as $s => $info): ?>
                  <option value="<?= $s ?>" <?= $kendaraan['status'] == $s ? 'selected' : '' ?>>
                    <?= $info['label'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
          <div class="col-12">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($kendaraan['deskripsi']) ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Gambar Saat Ini</label><br>
            <img src="../admin/uploads/<?= htmlspecialchars($kendaraan['gambar']) ?>" class="preview-img w-100" alt="Gambar">
            <small class="text-muted d-block mt-1">Ganti gambar?</small>
            <input type="file" name="gambar" class="form-control" accept="image/*">
          </div>
          <div class="col-md-6">
            <label class="form-label">Bukti Transfer Saat Ini</label><br>
            <img src="../admin/bukti/<?= htmlspecialchars($kendaraan['bukti_transfer']) ?>" class="preview-img w-100" alt="Bukti">
            <small class="text-muted d-block mt-1">Ganti bukti?</small>
            <input type="file" name="bukti_transfer" class="form-control" accept="image/*">
          </div>
          <div class="col-12 text-center mt-4">
            <button type="submit" class="btn btn-oval px-5">
              Simpan Perubahan
            </button>
          </div>
        </div>
      </form>
    </div>
  </main>

  <!-- ALERT SUKSES -->
  <?php if ($success): ?>
    <script>
      alert('Data kendaraan berhasil diupdate!');
      window.location = 'kendaraan.php';
    </script>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.body.className = localStorage.getItem('mode') || 'light-mode';
    window.addEventListener('scroll', () => {
      document.getElementById('nav').classList.toggle('scrolled', window.scrollY > 50);
    });
  </script>
</body>
</html>