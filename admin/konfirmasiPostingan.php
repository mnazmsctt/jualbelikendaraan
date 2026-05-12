<?php 
include '../config.php'; 

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    echo "<script>alert('Akses ditolak! Hanya Admin.'); window.location='../index.php';</script>";
    exit;
}

$namaUser = $_SESSION['user']['nama'];
$kategori = $_GET['kategori'] ?? '';
$search = $_GET['search'] ?? '';
$kategoriList = getKategori();

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$kendaraan = getKendaraanFiltered($kategori, $search, $perPage, $offset, 0, 0); // status = 0 (menunggu)
$totalData = countKendaraanFiltered($kategori, $search, 0, 0);
$totalPages = max(1, ceil($totalData / $perPage));
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Postingan - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root { --accent: #1565C0; }
    .light-mode { --glass: rgba(255,255,255,0.35); --bg: #f8fafc; }
    .dark-mode  { --glass: rgba(20,20,40,0.5); --bg: #0f172a; }
    body { background: linear-gradient(135deg, var(--bg) 0%, #e2e8f0 100%); background-attachment: fixed; font-family: 'Inter', sans-serif; min-height: 100vh; }
    .navbar { backdrop-filter: blur(12px); padding: 1.5rem 0; }
    .navbar.scrolled { background: var(--glass) !important; border-bottom: 1px solid rgba(255,255,255,.2); box-shadow: 0 10px 30px rgba(0,0,0,.08); }
    .glass-card { background: var(--glass); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,.2); border-radius: 1.6rem; box-shadow: 0 10px 30px rgba(0,0,0,.08); padding: 1.5rem; }
    .btn-oval { background: var(--accent); color: white; border: none; border-radius: 50px; padding: 0.7rem 1.8rem; font-weight: 600; }
    .btn-oval:hover { background: #0D47A1; transform: translateY(-2px); }
    .table { background: var(--glass); border-radius: 1.6rem; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
    .table thead { background: var(--accent); color: white; }
    h2 { font: 700 2.5rem 'Poppins', sans-serif; color: var(--accent); text-align: center; margin: 3rem 0 2rem; }
  </style>
</head>
<body class="light-mode">

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg fixed-top" id="nav">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="kendaraan.php">Admin Panel</a>
      <div>
        <a href="kendaraan.php" class="btn btn-outline-secondary btn-sm">Kendaraan</a>
        <a href="riwayat.php" class="btn btn-outline-secondary btn-sm">Histori</a>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <main class="container py-5" style="margin-top:80px;">
    <h2>Konfirmasi Postingan</h2>

    <!-- FORM CARI -->
    <div class="glass-card p-4 mb-4">
      <form method="GET" class="row g-3 align-items-center">
        <div class="col-md-5">
          <input type="text" name="search" class="form-control" placeholder="Cari nama/merek..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
          <select name="kategori" class="form-select">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoriList as $k): ?>
              <option value="<?= $k ?>" <?= $kategori == $k ? 'selected' : '' ?>><?= $k ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
          <button type="submit" class="btn btn-oval flex-fill">Cari</button>
          <a href="konfirmasiPostingan.php" class="btn btn-warning">Reset</a>
        </div>
      </form>
    </div>

    <!-- TABEL -->
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th width="5%">No</th>
            <th>Kendaraan</th>
            <th>Pemilik</th>
            <th>Harga</th>
            <th width="160px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($kendaraan)): ?>
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">
                Tidak ada postingan menunggu konfirmasi.
              </td>
            </tr>
          <?php else: ?>
            <?php $no = $offset + 1; foreach ($kendaraan as $item): ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td>
                  <strong><?= htmlspecialchars($item['merek'] . ' ' . $item['nama']) ?></strong><br>
                  <small class="text-muted"><?= $item['kategori'] ?> • <?= $item['tahun'] ?></small>
                </td>
                <td><?= htmlspecialchars($item['nama_user']) ?></td>
                <td class="fw-bold">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                <td class="text-center">
                  <!-- DETAIL -->
                  <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modal<?= $item['id'] ?>">
                    Detail
                  </button>

                  <!-- KONFIRMASI (SweetAlert) -->
                  <button class="btn btn-success btn-sm ms-1 btn-konfirmasi" data-id="<?= $item['id'] ?>">
                    Confirm
                  </button>

                  <!-- TOLAK (SweetAlert) -->
                  <button class="btn btn-danger btn-sm ms-1 btn-tolak" data-id="<?= $item['id'] ?>">
                    Reject
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
      <nav class="mt-5">
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&kategori=<?= urlencode($kategori) ?>&search=<?= urlencode($search) ?>">
                <?= $i ?>
              </a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </main>

  <!-- ... (bagian atas sama seperti sebelumnya) ... -->

<td class="text-center">
  <div class="d-grid gap-2 d-md-flex justify-content-center">
    <!-- DETAIL -->
    <button class="btn btn-info btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modal<?= $item['id'] ?>" title="Lihat Detail">
      <i class="bi bi-eye"></i>
    </button>

    <!-- KONFIRMASI -->
    <button class="btn btn-success btn-sm px-3 btn-konfirmasi" data-id="<?= $item['id'] ?>" title="Konfirmasi Postingan">
      <i class="bi bi-check-lg"></i>
    </button>

    <!-- TOLAK -->
    <button class="btn btn-danger btn-sm px-3 btn-tolak" data-id="<?= $item['id'] ?>" title="Tolak Postingan">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
</td>

<!-- ... (script di bawah) ... -->

<script>
// SweetAlert Konfirmasi
document.querySelectorAll('.btn-konfirmasi').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.dataset.id;
    Swal.fire({
      title: 'success',
      title: 'Konfirmasi Postingan?',
      text: "Kendaraan akan langsung muncul di katalog publik",
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, Konfirmasi!',
      cancelButtonText: 'Batal',
      reverseButtons: true,
      buttonsStyling: false,
      customClass: {
        confirmButton: 'btn btn-success mx-2 px-4',
        cancelButton:  'btn btn-secondary mx-2 px-4'
      },
      width: '420px'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = `ubahStatusKendaraan.php?id=${id}&status=1`;
      }
    });
  });
});

// SweetAlert Tolak
document.querySelectorAll('.btn-tolak').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.dataset.id;
    Swal.fire({
      title: 'Tolak Postingan Ini?',
      text: "Kendaraan tidak akan muncul di katalog",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Tolak',
      cancelButtonText: 'Batal',
      reverseButtons: true,
      buttonsStyling: false,
      customClass: {
        confirmButton: 'btn btn-danger mx-2 px-4',
        cancelButton:  'btn btn-secondary mx-2 px-4'
      },
      width: '420px'
    }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = `ubahStatusKendaraan.php?id=${id}&status=3`;
        }
      });
  });
});
</script>
</body>
</html>