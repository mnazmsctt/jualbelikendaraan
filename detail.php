<?php
include 'config.php';

if (!isset($_GET['id'])) {
  echo "<div class='text-center py-5'><h2>Invalid</h2><a href='catalog.php' class='btn btn-primary'>Kembali</a></div>";
  exit;
}

$kendaraan = getDetailKendaraan($_GET['id']);
if (!$kendaraan) {
  echo "<div class='text-center py-5'><h2>Not Found</h2><a href='catalog.php' class='btn btn-primary'>Kembali</a></div>";
  exit;
}

if (!isset($_SESSION['troli'])) $_SESSION['troli'] = [];
$diTroli = in_array($kendaraan['id'], $_SESSION['troli']);

$sukses = false;
if (isset($_POST['add_to_troli']) && $kendaraan['status'] == 1 && ($kendaraan['stok'] ?? 0) > 0 && !$diTroli) {
  $_SESSION['troli'][] = $kendaraan['id'];
  $diTroli = true;
  $sukses = true;
}

/// === THUMBNAIL AMAN (fix error PNG/JPG rusak) ===
$original = "admin/uploads/" . $kendaraan['gambar'];
$thumb    = "admin/uploads/thumbs/" . $kendaraan['gambar'];

if (!file_exists($thumb) && file_exists($original)) {
    // Pastikan folder thumbs ada
    if (!is_dir("admin/uploads/thumbs")) {
        mkdir("admin/uploads/thumbs", 0755, true);
    }

    // Cek tipe gambar SEBENARNYA lewat MIME, bukan ekstensi
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $original);
    finfo_close($finfo);

    list($w, $h) = getimagesize($original);
    $newW = 900;
    $newH = (int)($h * $newW / $w);

    $src = false;
    if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
        $src = imagecreatefromjpeg($original);
    } elseif ($mime == 'image/png') {
        $src = imagecreatefrompng($original);
    } elseif ($mime == 'image/webp') {
        $src = imagecreatefromwebp($original);
    }

    // Jika tetap gagal buka (file rusak/nama bohong), paksa jadi JPEG
    if (!$src) {
        $src = imagecreatefromstring(file_get_contents($original));
    }

    if ($src) {
        $dst = imagecreatetruecolor($newW, $newH);
        
        // Kalau PNG, biarin transparansi (optional)
        if ($mime == 'image/png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

        // Selalu simpan sebagai JPG (paling aman & kecil ukurannya)
        imagejpeg($dst, $thumb, 88);

        imagedestroy($src);
        imagedestroy($dst);
    }
}

$gambar = file_exists($thumb) ? $thumb : $original;
?>

<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($kendaraan['merek'].' '.$kendaraan['nama']) ?> - Stipen Automo</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary: #0d6efd;
      --gray: #6c757d;
      --light: #f8f9fa;
    }
    [data-theme="dark"] {
      --bg: #121212;
      --card: #1e1e1e;
      --text: #e0e0e0;
      --muted: #aaaaaa;
    }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      background: #f5f7fa;
      color: #222;
      min-height: 100vh;
    }
    [data-theme="dark"] body { background: var(--bg); color: var(--text); }

    .card {
      background: white;
      border-radius: 1.2rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      border: none;
      overflow: hidden;
    }
    [data-theme="dark"] .card { background: var(--card); }

    .car-img {
      width: 100%;
      height: 500px;
      object-fit: cover;
      border-bottom: 1px solid #eee;
    }

    .price {
      font-size: 2.8rem;
      font-weight: 800;
      color: #0d6efd;
    }

    .btn-add {
      background: #0d6efd;
      border: none;
      padding: 0.9rem 2rem;
      font-weight: 600;
      border-radius: 50px;
      font-size: 1.1rem;
    }
    .btn-add:hover { background: #0b5ed7; }

    .wa-btn {
      position: fixed;
      bottom: 25px;
      right: 25px;
      background: #25d366;
      color: white;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      font-size: 1.8rem;
      box-shadow: 0 8px 25px rgba(37,211,102,0.4);
      z-index: 1000;
    }

    #successPopup {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.9);
      background: white;
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 20px 50px rgba(0,0,0,0.3);
      z-index: 9999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.4s ease;
      text-align: center;
      max-width: 90%;
      width: 400px;
    }
    #successPopup.show {
      opacity: 1;
      visibility: visible;
      transform: translate(-50%, -50%) scale(1);
    }
    [data-theme="dark"] #successPopup { background: #1e1e1e; color: white; }
  </style>
</head>
<body>

  <!-- WA Float -->
  <a href="https://wa.me/<?= preg_replace('/[^0-9]/','', $kendaraan['no_telepon'] ?? '6285694193698') ?>?text=Saya%20mau%20<?= urlencode($kendaraan['merek'].' '.$kendaraan['nama']) ?>"
     class="d-flex align-items-center justify-content-center wa-btn shadow-lg" target="_blank">
    <i class="bi bi-whatsapp"></i>
  </a>

  <!-- Popup Sukses Simple -->
  <div id="successPopup" class="<?= $sukses ? 'show' : '' ?>">
    <h4 class="fw-bold mb-3">Berhasil!</h4>
    <p class="mb-4"><?= htmlspecialchars($kendaraan['merek'].' '.$kendaraan['nama']) ?> ditambahkan ke troli</p>
    <div class="d-flex gap-2 justify-content-center">
      <a href="troli.php" class="btn btn-success px-4">Lihat Troli</a>
      <button onclick="this.closest('#successPopup').classList.remove('show')" class="btn btn-outline-secondary px-4">Tutup</button>
    </div>
  </div>
  <?php if($sukses): ?>
    <div style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9998;" onclick="document.getElementById('successPopup').classList.remove('show');this.remove()"></div>
  <?php endif; ?>

  <!-- Navbar Simple -->
  <nav class="navbar bg-white shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4" href="index.php">Stipen Automo</a>
      <a href="troli.php" class="btn btn-outline-primary position-relative">
        <i class="bi bi-cart3 fs-4"></i>
        <?php if(!empty($_SESSION['troli'])): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= count($_SESSION['troli']) ?>
          </span>
        <?php endif; ?>
      </a>
    </div>
  </nav>

  <div class="container py-5">
    <div class="row g-4">
      <!-- Gambar (cuma 1, gede, bersih) -->
      <div class="col-lg-7">
        <div class="card">
          <img src="<?= $gambar ?>?v=<?= time() ?>" class="car-img" alt="<?= htmlspecialchars($kendaraan['nama']) ?>">
        </div>
      </div>

      <!-- Detail -->
      <div class="col-lg-5">
        <div class="card h-100 p-4">
          <h1 class="h2 fw-bold"><?= htmlspecialchars($kendaraan['merek'].' '.$kendaraan['nama']) ?></h1>
          <p class="text-muted fs-5 mb-3"><?= $kendaraan['tahun'] ?></p>

          <div class="price mb-4">Rp <?= number_format($kendaraan['harga'], 0, ',', '.') ?></div>

          <div class="row g-3 mb-4 text-center">
            <div class="col-6">
              <small class="text-muted">Kategori</small>
              <p class="fw-semibold mb-0"><?= htmlspecialchars($kendaraan['kategori']) ?></p>
            </div>
            <div class="col-6">
              <small class="text-muted">Transmisi</small>
              <p class="fw-semibold mb-0"><?= htmlspecialchars($kendaraan['tipe']) ?></p>
            </div>
            <div class="col-6">
              <small class="text-muted">Stok</small>
              <p class="fw-semibold mb-0 <?= ($kendaraan['stok']??0)==0?'text-danger':'' ?>">
                <?= ($kendaraan['stok']??0) ?: 0 ?> unit
              </p>
            </div>
            <div class="col-6">
              <small class="text-muted">Pemilik</small>
              <p class="fw-semibold mb-0"><?= htmlspecialchars($kendaraan['nama_user']??'Stipen Automo') ?></p>
            </div>
          </div>

          <hr>

          <h5 class="fw-bold mb-3">Deskripsi</h5>
          <p class="text-muted lh-lg"><?= nl2br(htmlspecialchars($kendaraan['deskripsi'])) ?></p>

          <div class="mt-auto d-flex gap-3">
            <a href="catalog.php" class="btn btn-outline-secondary px-4">Kembali</a>

            <?php if ($kendaraan['status'] == 1 && ($kendaraan['stok'] ?? 0) > 0): ?>
              <?php if ($diTroli): ?>
                <a href="troli.php" class="btn btn-success px-5 flex-fill">Sudah di Troli</a>
              <?php else: ?>
                <form method="POST" class="d-inline w-100">
                  <button type="submit" name="add_to_troli" class="btn btn-primary btn-add w-100">
                    Tambah ke Troli
                  </button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <button class="btn btn-secondary px-5 w-100" disabled>
                <?= ($kendaraan['stok']??0)==0 ? 'Stok Habis' : 'Tidak Tersedia' ?>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Dark mode dari localStorage
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
  </script>
</body>
</html>