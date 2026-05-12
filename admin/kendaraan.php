<?php 
include '../config.php'; 

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$namaUser = $_SESSION['user']['nama'];
$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role']; // 1 = admin

$kategori = $_GET['kategori'] ?? '';
$search = $_GET['search'] ?? '';

$kategoriList = getKategori();

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$userFilterId = ($role == 1) ? 0 : $user_id;

$kendaraan = getKendaraanFiltered($kategori, $search, $perPage, $offset, $userFilterId);
$totalData = countKendaraanFiltered($kategori, $search, $userFilterId);
$totalPages = max(1, ceil($totalData / $perPage));

$kendaraan = $kendaraan ?? [];

$statusLabels = [
    0 => ['label' => 'Menunggu Konfirmasi', 'badge' => 'warning'],
    1 => ['label' => 'Aktif', 'badge' => 'success'],
    2 => ['label' => 'Nonaktif', 'badge' => 'secondary'],
    3 => ['label' => 'Ditolak', 'badge' => 'danger'],
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Master Kendaraan - Admin | Stipen Automo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

  <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" href="../assets/img/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="../assets/img/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="../assets/img/apple-touch-icon.png">
    <link rel="manifest" href="../assets/img/site.webmanifest">
    
  <style>
    :root {
      --radius: 1.6rem;
      --shadow: 0 10px 30px rgba(0,0,0,0.08);
      --transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
    }
    .light-mode { 
      --glass: rgba(255,255,255,0.35); 
      --border: rgba(255,255,255,0.45); 
      --accent: #1565C0; 
      --text: #1a1a1a; 
      --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }
    .dark-mode { 
      --glass: rgba(20,20,40,0.5); 
      --border: rgba(255,255,255,0.2); 
      --accent: #42A5F5; 
      --text: #fff; 
      --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }
    body { 
      background: var(--bg-gradient); 
      background-attachment: fixed; 
      color: var(--text); 
      font-family: 'Inter', sans-serif; 
      min-height: 100vh; 
      transition: var(--transition); 
    }
    .navbar { padding: 1.6rem 0; backdrop-filter: blur(12px); transition: var(--transition); }
    .navbar.scrolled { background: var(--glass) !important; border-bottom: 1px solid var(--border); box-shadow: var(--shadow); padding: 1rem 0; }
    .light-mode .navbar.scrolled { background: rgba(255,255,255,0.92) !important; }
    .dark-mode .navbar.scrolled { background: rgba(15,15,35,0.95) !important; }
    .navbar-brand { font: 700 1.9rem 'Poppins', sans-serif; color: var(--text); }
    .btn-oval { background: var(--accent); color: white; border: none; border-radius: 50px; padding: 0.75rem 2rem; font-weight: 600; transition: var(--transition); }
    .btn-oval:hover { background: #0D47A1; transform: translateY(-3px); box-shadow: 0 8px 25px rgba(21,101,192,0.4); }
    .form-control, .form-select { background: rgba(255,255,255,.75); border: 1px solid var(--border); border-radius: 1.2rem; padding: .9rem 1.2rem; color: var(--text); backdrop-filter: blur(10px); }
    .dark-mode .form-control, .dark-mode .form-select { background: rgba(255,255,255,.15); color: white; }
    .table { background: var(--glass); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
    .table thead { background: var(--accent); color: white; }
    .table tbody tr:hover { background: rgba(255,255,255,0.1); }
    .dark-mode .table tbody tr:hover { background: rgba(255,255,255,0.05); }
    .btn-action { width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
    .btn-action:hover { transform: translateY(-2px); }
    .badge-stock { font-size: 0.75rem; }
    .pagination .page-link { background: var(--glass); border: 1px solid var(--border); color: var(--text); border-radius: 50px !important; margin: 0 4px; padding: 0.5rem 1rem; }
    .pagination .page-item.active .page-link { background: var(--accent); border-color: var(--accent); color: white; }
    h2 { font: 700 2.5rem 'Poppins', sans-serif; color: var(--text); text-align: center; margin: 3rem 0 2rem; }
    .info-total { text-align: center; margin-bottom: 2rem; font-size: 1.1rem; color: var(--text); opacity: 0.8; }
    @media (max-width: 768px) { 
      h2 { font-size: 2rem; }
      .btn-action { width: 36px; height: 36px; }
      .table { font-size: 0.9rem; }
    }
  </style>
</head>
<body class="light-mode">

  <!-- NAVBAR (sama seperti sebelumnya) -->
  <nav class="navbar navbar-expand-lg fixed-top" id="nav">
    <div class="container-fluid">
      <a class="navbar-brand" href="../index.php">Stipen Automo</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav ms-auto align-items-center gap-3">
          <?php if ($role == 1): ?>
            <li class="nav-item">
              <a href="riwayat.php" class="btn btn-success btn-sm">
                <i class="bi bi-receipt"></i> Histori Pembelian
              </a>
            </li>
            <li class="nav-item">
              <a href="konfirmasiPostingan.php" class="btn btn-warning btn-sm">
                <i class="bi bi-check-circle"></i> Konfirmasi
              </a>
            </li>
          <?php endif; ?>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle fs-4"></i>
              <span class="d-none d-lg-inline"><?= htmlspecialchars($namaUser) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="../index.php"><i class="bi bi-house"></i> Home</a></li>
              <li><a class="dropdown-item" href="../catalog.php"><i class="bi bi-car-front"></i> Katalog Kendaraan</a></li>
              <?php if ($role == 1): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="konfirmasiPostingan.php"><i class="bi bi-check-circle"></i> Konfirmasi Postingan</a></li>
                <li><a class="dropdown-item" href="riwayat.php"><i class="bi bi-receipt"></i> Histori Pembelian</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <main class="container py-5" style="margin-top: 80px;">
    <h2>Master Kendaraan</h2>
    <p class="info-total">Total: <strong><?= $totalData ?></strong> kendaraan terdaftar</p>

    <div class="row g-3 mb-4 align-items-end">
      <div class="col-12 col-lg-8">
        <form class="row g-2" method="GET">
          <div class="col-12 col-md-6">
            <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama kendaraan...">
          </div>
          <div class="col-12 col-md-4">
            <select class="form-select" name="kategori">
              <option value="">Semua Kategori</option>
              <?php foreach ($kategoriList as $k): ?>
                <option value="<?= $k ?>" <?= ($kategori == $k) ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-2 d-grid gap-2 d-md-flex">
            <a href="kendaraan.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-repeat"></i></a>
            <button type="submit" class="btn btn-oval flex-fill"><i class="bi bi-search"></i> Cari</button>
          </div>
        </form>
      </div>
      <div class="col-12 col-lg-4 text-lg-end">
        <a href="tambahKendaraan.php" class="btn btn-success btn-lg w-100 w-lg-auto">
          <i class="bi bi-plus-circle"></i> Tambah Kendaraan
        </a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="text-white text-center">
          <tr>
            <th width="5%">No</th>
            <th>Kendaraan</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Status</th>
            <th width="150px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($kendaraan)): ?>
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                Tidak ada data kendaraan ditemukan.
              </td>
            </tr>
          <?php else: ?>
            <?php $no = $offset + 1; foreach ($kendaraan as $item): 
              $status = (int)($item['status'] ?? 0);
            ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <img src="uploads/<?= htmlspecialchars($item['gambar'] ?? 'no-image.jpg') ?>" 
                         width="70" height="50" class="rounded shadow-sm" style="object-fit: cover;">
                    <div>
                      <strong><?= htmlspecialchars($item['merek'] . ' ' . $item['nama'] . ' ' . $item['tahun']) ?></strong><br>
                      <small class="text-muted"><?= htmlspecialchars($item['tipe'] ?? '-') ?></small>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($item['kategori'] ?? '-') ?></td>
                <td class="fw-bold">Rp <?= number_format($item['harga'] ?? 0, 0, ',', '.') ?></td>
                <td class="text-center">
                  <strong><?= $item['stok'] ?? 0 ?></strong>
                  <?php if (($item['stok'] ?? 0) == 0): ?>
                    <span class="badge bg-danger ms-2 badge-stock">Habis</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php
                    $label = $statusLabels[$status]['label'] ?? 'Unknown';
                    $badge = $statusLabels[$status]['badge'] ?? 'dark';
                  ?>
                  <span class="badge bg-<?= $badge ?>"><?= $label ?></span>
                </td>
                <td>
                  <div class="d-flex justify-content-center gap-1">
                    <!-- Tombol Detail (selalu ada) -->
                    <a href="detailKendaraan.php?id=<?= $item['id'] ?>" 
                       class="btn btn-info btn-action" title="Detail" data-bs-toggle="tooltip">
                      <i class="bi bi-eye"></i>
                    </a>

                    <!-- Tombol Edit (admin atau owner & bukan ditolak) -->
                    <?php if ($role == 1 || ($item['user_id'] == $user_id && $status != 3)): ?>
                      <a href="editKendaraan.php?id=<?= $item['id'] ?>" 
                         class="btn btn-warning btn-action" title="Edit" data-bs-toggle="tooltip">
                        <i class="bi bi-pencil-square"></i>
                      </a>
                    <?php else: ?>
                      <!-- Placeholder agar tetap simetris -->
                      <div class="btn-action"></div>
                    <?php endif; ?>

                    <!-- Tombol Hapus (hanya admin) -->
                    <?php if ($role == 1): ?>
                      <a href="hapusKendaraan.php?id=<?= $item['id'] ?>" 
                         class="btn btn-danger btn-action" title="Hapus" data-bs-toggle="tooltip"
                         onclick="return confirm('Yakin ingin menghapus kendaraan ini?')">
                        <i class="bi bi-trash"></i>
                      </a>
                    <?php else: ?>
                      <!-- Placeholder -->
                      <div class="btn-action"></div>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav aria-label="Page navigation" class="mt-5">
        <ul class="pagination justify-content-center">
          <?php 
          $startPage = max(1, $page - 2);
          $endPage = min($totalPages, $page + 2);
          if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $page-1 ?>&kategori=<?= urlencode($kategori) ?>&search=<?= urlencode($search) ?>">«</a>
            </li>
          <?php endif; ?>
          <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&kategori=<?= urlencode($kategori) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $page+1 ?>&kategori=<?= urlencode($kategori) ?>&search=<?= urlencode($search) ?>">»</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Dark Mode
    const savedMode = localStorage.getItem('mode') || 'light-mode';
    document.body.className = savedMode;

    // Navbar scroll
    window.addEventListener('scroll', () => {
      document.getElementById('nav').classList.toggle('scrolled', window.scrollY > 50);
    });

    // Tooltip Bootstrap
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
  </script>
</body>
</html>