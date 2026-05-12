<?php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['troli'])) $_SESSION['troli'] = [];

$kategori = $_GET['kategori'] ?? '';
$search   = $_GET['search'] ?? '';

$kategoriList = getKategori();

$perPage = 6;
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset  = ($page - 1) * $perPage;

$kendaraan  = getKendaraanFiltered($kategori, $search, $perPage, $offset, 0, 1);
$totalData  = countKendaraanFiltered($kategori, $search, 0);
$totalPages = ceil($totalData / $perPage);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Katalog Kendaraan - Stipen Automo</title>
  <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2-confetti@1.2.0/dist/sweetalert2-confetti.min.js"></script>

  <style>
    :root {
      --radius: 1.6rem;
      --shadow: 0 10px 30px rgba(0,0,0,0.08);
      --shadow-hover: 0 20px 50px rgba(0,0,0,0.12);
      --transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
    }
    .light-mode { 
      --glass: rgba(255,255,255,0.32); 
      --border: rgba(255,255,255,0.45); 
      --glow: rgba(255,255,255,0.7); 
      --accent: #1565C0; 
      --accent-hover: #0D47A1; 
      --text: #1a1a1a; 
      --text-light: #555; 
      --text-muted: #666;
      --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }
    .dark-mode { 
      --glass: rgba(20,20,40,0.48); 
      --border: rgba(255,255,255,0.2); 
      --glow: rgba(100,150,255,0.4); 
      --accent: #42A5F5; 
      --accent-hover: #1E88E5; 
      --text: #fff; 
      --text-light: #ddd; 
      --text-muted: #aaa;
      --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { 
      background: var(--bg-gradient); 
      background-attachment: fixed; 
      color: var(--text); 
      font-family: 'Inter', sans-serif; 
      min-height: 100vh; 
      line-height: 1.7; 
      overflow-x: hidden; 
      transition: var(--transition); 
    }

    /* Glassmorphism Card */
    .glass { 
      background: var(--glass); 
      backdrop-filter: blur(16px); 
      -webkit-backdrop-filter: blur(16px); 
      border: 1px solid var(--border); 
      border-radius: var(--radius); 
      padding: 2rem; 
      transition: var(--transition); 
      box-shadow: var(--shadow); 
      position: relative; 
      overflow: hidden; 
    }
    .glass:hover { 
      transform: translateY(-8px); 
      box-shadow: var(--shadow-hover); 
      border-color: var(--glow); 
    }

    /* Navbar */
    .navbar { 
      padding: 1.6rem 0; 
      backdrop-filter: blur(12px); 
      -webkit-backdrop-filter: blur(12px); 
      transition: var(--transition); 
    }
    .navbar.scrolled { 
      background: var(--glass) !important; 
      border-bottom: 1px solid var(--border); 
      box-shadow: var(--shadow); 
      padding: 1rem 0; 
    }
    .light-mode .navbar.scrolled { background: rgba(255,255,255,0.92) !important; }
    .dark-mode .navbar.scrolled { background: rgba(15,15,35,0.95) !important; }

    .navbar-brand { font: 700 1.9rem 'Poppins', sans-serif; color: var(--text); }
    .nav-link { color: var(--text) !important; font-weight: 500; padding: 0.5rem 1rem !important; border-radius: 50px; transition: var(--transition); }
    .nav-link:hover, .nav-link.active { background: var(--accent); color: white !important; }

    /* Button */
    .btn-oval { 
      background: var(--accent); color: white; border: none; border-radius: 50px; 
      padding: 0.75rem 2rem; font-weight: 600; transition: var(--transition);
    }
    .btn-oval:hover { background: var(--accent-hover); transform: translateY(-3px); }

    /* Section Title */
    .section-title { 
      font: 700 2.8rem 'Poppins', sans-serif; text-align: center; color: var(--text); 
      margin-bottom: 3.5rem; position: relative; 
    }
    .section-title::after { 
      content: ''; display: block; width: 70px; height: 4px; background: var(--accent); 
      margin: 1rem auto 0; border-radius: 2px; 
    }

    /* Form Control */
    .form-control, .form-select {
      background: rgba(255,255,255,.7); border: 1px solid var(--border); border-radius: 1.2rem; 
      padding: .9rem 1.2rem; color: var(--text); backdrop-filter: blur(10px); transition: var(--transition);
    }
    .dark-mode .form-control, .dark-mode .form-select { background: rgba(255,255,255,.15); }
    .form-control:focus, .form-select:focus { 
      background: white; border-color: var(--accent); box-shadow: 0 0 0 .2rem rgba(227,242,253,.5); 
    }

    /* Product Card */
    .product-card {
      background: var(--glass); backdrop-filter: blur(16px); border: 1px solid var(--border);
      border-radius: var(--radius); overflow: hidden; transition: var(--transition); box-shadow: var(--shadow);
    }
    .product-card:hover { transform: translateY(-12px); box-shadow: var(--shadow-hover); }
    .product-card img { width: 100%; height: 220px; object-fit: cover; }
    .product-body { padding: 1.6rem; display: flex; flex-direction: column; height: 100%; }
    .price { font-size: 1.5rem; font-weight: 700; color: var(--accent); margin: 0.5rem 0 1rem; }

    /* Floating Cart & Setting */
    .floating-btn {
      width: 56px; height: 56px; background: var(--glass); border: 1px solid var(--border);
      backdrop-filter: blur(12px); color: var(--text); box-shadow: var(--shadow); border-radius: 50%;
      display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
      transition: var(--transition); position: relative;
    }
    .floating-btn:hover { transform: translateY(-4px) scale(1.08); box-shadow: var(--shadow-hover); }
    .badge-cart {
      position: absolute; top: -6px; right: -6px; background: #e74c3c; color: white;
      font-size: 0.75rem; width: 22px; height: 22px; border-radius: 50%; display: flex;
      align-items: center; justify-content: center;
    }

    /* Setting Panel */
    #setting-panel {
      position: fixed; top: 140px; right: 20px; width: 260px; background: var(--glass);
      backdrop-filter: blur(16px); border: 1px solid var(--border); border-radius: var(--radius);
      padding: 1.5rem; box-shadow: var(--shadow); z-index: 1040; opacity: 0; visibility: hidden;
      transform: translateY(-10px); transition: var(--transition);
    }
    #setting-panel.show { opacity: 1; visibility: visible; transform: translateY(0); }

    /* Pagination */
    .pagination a {
      background: var(--glass); border: 1px solid var(--border); color: var(--text);
      padding: 0.6rem 1.2rem; border-radius: 50px; margin: 0 4px; transition: var(--transition);
    }
    .pagination a:hover, .pagination a.active {
      background: var(--accent); color: white; border-color: var(--accent);
    }

    @media (max-width:768px) {
      .section-title { font-size:2.2rem; }
      .product-card img { height: 180px; }
    }
  </style>
</head>
<body class="light-mode">

  <!-- Floating Cart & Setting -->
  <div class="position-fixed top-0 end-0 p-3 d-flex gap-3" style="z-index:1050;">
    <a href="troli.php" class="floating-btn position-relative" title="Troli">
      <i class="bi bi-cart"></i>
      <?php if(!empty($_SESSION['troli'])): ?>
        <span class="badge-cart"><?=count($_SESSION['troli'])?></span>
      <?php endif; ?>
    </a>
    <button id="setting-btn" class="floating-btn">
      <i class="bi bi-gear-fill"></i>
    </button>
  </div>

  <!-- Setting Panel -->
  <div id="setting-panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <span class="small fw-medium">Dark Mode</span>
      <button id="toggle-mode" class="btn btn-sm rounded-circle" style="width:36px;height:36px;background:var(--glass);border:1px solid var(--border);">
        <i class="bi bi-moon-stars-fill"></i>
      </button>
    </div>
    <hr style="border-color:var(--border);">
    <a href="riwayat.php" class="btn btn-oval w-100 mb-2"><i class="bi bi-receipt"></i> Riwayat</a>
    <?php if(!isset($_SESSION['user'])): ?>
      <a href="admin/login.php" class="btn btn-oval w-100" style="background:#28a745"><i class="bi bi-box-arrow-in-right"></i> Login</a>
    <?php else: ?>
      <a href="admin/logout.php" class="btn btn-oval w-100" style="background:#dc3545"><i class="bi bi-box-arrow-right"></i> Logout</a>
    <?php endif; ?>
  </div>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top" id="nav">
    <div class="container">
      <a class="navbar-brand" href="index.php">Stipen Automo</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mx-auto gap-3">
          <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link active" href="catalog.php">Catalog</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#about">About</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#contact">Contact</a></li>
          <?php if(isset($_SESSION['user'])): ?><li class="nav-item"><a class="nav-link" href="admin/kendaraan.php">Master</a></li><?php endif; ?>
        </ul>
        <form class="d-flex" action="catalog.php">
          <input type="text" name="search" class="form-control me-2" placeholder="Cari mobil..." value="<?=htmlspecialchars($search??'')?>">
          <button class="btn btn-oval" type="submit">Go</button>
        </form>
      </div>
    </div>
  </nav>

  <!-- Catalog Section -->
  <section class="container my-5 py-5" style="margin-top:120px;">
    <h2 class="section-title">Katalog Kendaraan</h2>

    <div class="row g-5">
      <!-- Sidebar Filter -->
      <div class="col-lg-3">
        <div class="glass">
          <h5 class="mb-4" style="color:var(--accent)">Filter Pencarian</h5>
          <form method="get">
            <div class="mb-3">
              <input type="text" name="search" class="form-control" placeholder="Nama kendaraan..." value="<?=htmlspecialchars($search??'')?>">
            </div>
            <div class="mb-4">
              <select name="kategori" class="form-select">
                <option value="">Semua Kategori</option>
                <?php foreach($kategoriList as $k): ?>
                  <option value="<?=htmlspecialchars($k)?>" <?=($kategori==$k)?'selected':''?>><?=htmlspecialchars($k)?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="d-flex gap-2">
              <a href="catalog.php" class="btn btn-oval flex-fill" style="background:#6c757d">Reset</a>
              <button type="submit" class="btn btn-oval flex-fill">Terapkan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Product Grid -->
      <div class="col-lg-9">
        <?php if(empty($kendaraan)): ?>
          <div class="glass text-center p-5">
            <i class="bi bi-emoji-frown fs-1 text-muted mb-3"></i>
            <p class="fs-4">Kendaraan tidak ditemukan.</p>
          </div>
        <?php else: ?>
          <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach($kendaraan as $item): 
              $diTroli = in_array($item['id'], $_SESSION['troli']);
            ?>
              <div class="col">
                <div class="product-card h-100 d-flex flex-column">
                  <img src="admin/uploads/<?=htmlspecialchars($item['gambar'])?>" alt="<?=htmlspecialchars($item['nama'])?>">
                  <div class="product-body flex-grow-1">
                    <h5 class="mb-2"><?=htmlspecialchars($item['merek'].' '.$item['nama'].' '.$item['tahun'])?></h5>
                    <div class="price mb-3">Rp<?=number_format($item['harga'],0,',','.')?></div>
                    <p class="text-muted small flex-grow-1"><?=mb_strimwidth(htmlspecialchars($item['deskripsi']),0,100,'...')?></p>

                    <div class="mt-3 d-flex flex-column gap-2">
                      <?php if($item['status']==1): ?>
                        <a href="https://wa.me/<?=getNoTeleponByKendaraanId($item['id'])?>?text=Halo,%20saya%20tertarik%20dengan%20<?=urlencode($item['merek'].' '.$item['nama'])?>"
                           class="btn btn-oval" style="background:#25d366" target="_blank">
                          <i class="bi bi-whatsapp"></i> Chat WA
                        </a>
                      <?php endif; ?>

                      <?php if($item['status']==1 && $item['stok']>0): ?>
                        <?php if($diTroli): ?>
                          <a href="troli.php" class="btn btn-oval" style="background:#16a34a">
                            <i class="bi bi-check-circle"></i> Sudah di Troli
                          </a>
                        <?php else: ?>
                          <button onclick="addToTroli(<?=$item['id']?>)" class="btn btn-oval" style="background:#16a34a">
                            <i class="bi bi-cart-plus"></i> Add to Troli
                          </button>
                        <?php endif; ?>
                      <?php else: ?>
                        <button class="btn btn-oval" style="background:#6c757d" disabled>
                          <?= $item['stok']==0 ? 'Stok Habis' : 'Tidak Tersedia' ?>
                        </button>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Pagination -->
          <?php if($totalPages>1): ?>
            <div class="d-flex justify-content-center gap-2 mt-5 pagination">
              <?php if($page>1): ?><a href="?page=<?=($page-1)?>&kategori=<?=urlencode($kategori)?>&search=<?=urlencode($search)?>">Previous</a><?php endif; ?>
              <?php for($i=max(1,$page-2); $i<=min($totalPages,$page+2); $i++): ?>
                <a href="?page=<?=$i?>&kategori=<?=urlencode($kategori)?>&search=<?=urlencode($search)?>" class="<?=($i==$page)?'active':''?>"><?=$i?></a>
              <?php endfor; ?>
              <?php if($page<$totalPages): ?><a href="?page=<?=($page+1)?>&kategori=<?=urlencode($kategori)?>&search=<?=urlencode($search)?>">Next</a><?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Dark Mode Toggle & Save
    const toggleMode = document.getElementById('toggle-mode');
    const body = document.body;
    const icon = toggleMode.querySelector('i');

    function applyMode() {
      const mode = localStorage.getItem('mode') || 'light-mode';
      body.className = mode;
      icon.className = mode === 'dark-mode' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
    applyMode();

    toggleMode.addEventListener('click', () => {
      const isDark = body.classList.contains('dark-mode');
      const newMode = isDark ? 'light-mode' : 'dark-mode';
      body.className = newMode;
      localStorage.setItem('mode', newMode);
      icon.className = newMode === 'dark-mode' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    });

    // Setting Panel
    document.getElementById('setting-btn').addEventListener('click', e => {
      e.stopPropagation();
      document.getElementById('setting-panel').classList.toggle('show');
    });
    document.addEventListener('click', e => {
      if (!e.target.closest('#setting-btn') && !e.target.closest('#setting-panel')) {
        document.getElementById('setting-panel').classList.remove('show');
      }
    });

    // Navbar Scroll Effect
    window.addEventListener('scroll', () => {
      document.getElementById('nav').classList.toggle('scrolled', window.scrollY > 50);
    });

    // Add to Troli - Versi dengan alert yang bisa ditutup (silang / klik luar)
function addToTroli(id) {
  const btn = event.target.closest('button');
  const original = btn.innerHTML;

  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Loading...';

  fetch('add_to_troli.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'id=' + id
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Update tombol jadi "Sudah di Troli"
      const container = btn.closest('.d-flex');
      container.innerHTML = `
        <a href="troli.php" class="btn btn-oval" style="background:#16a34a">
          <i class="bi bi-check-circle"></i> Sudah di Troli
        </a>
      `;

      // Update badge troli
      const badge = document.querySelector('.badge-cart');
      if (badge) {
        badge.textContent = data.total;
      } else if (data.total > 0) {
        document.querySelector('.floating-btn[title="Troli"]')
          .insertAdjacentHTML('beforeend', `<span class="badge-cart">${data.total}</span>`);
      }

      // SweetAlert
      Swal.fire({
        icon: 'success',
        title: 'Yeay! Berhasil Ditambahkan 🚗',
        html: `
          <strong>${data.message}</strong><br>
          <small class="text-muted">Total item di troli: <strong>${data.total}</strong></small>
        `,
        showConfirmButton: true,
        confirmButtonText: 'Lihat Troli',
        showCancelButton: true,
        cancelButtonText: 'Lanjut Belanja',
        allowOutsideClick: true, 
        allowEscapeKey: true,      
        buttonsStyling: false,
        customClass: {
          popup: 'animated zoomIn faster',
          confirmButton: 'btn btn-oval mx-2',
          cancelButton: 'btn btn-outline-secondary mx-2'
        },
        didOpen: () => {
          // Efek Confetti
          const duration = 3 * 1000;
          const animationEnd = Date.now() + duration;
          const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

          function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
          }

          const interval = setInterval(function() {
            const timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) return clearInterval(interval);

            const particleCount = 40 * (timeLeft / duration);
            confetti(Object.assign({}, defaults, {
              particleCount,
              origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
            }));
            confetti(Object.assign({}, defaults, {
              particleCount,
              origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
            }));
          }, 250);
        }
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'troli.php';
        }
        // cancel
      });

    } else {
      // Gagal
      Swal.fire({
        icon: 'error',
        title: 'Gagal Ditambahkan',
        text: data.message || 'Terjadi kesalahan saat menambahkan ke troli.',
        confirmButtonText: 'OK',
        allowOutsideClick: true,
        buttonsStyling: false,
        customClass: {
          confirmButton: 'btn btn-danger'
        }
      });
      btn.disabled = false;
      btn.innerHTML = original;
    }
  })
  .catch(err => {
    console.error(err);
    Swal.fire({
      icon: 'error',
      title: 'Koneksi Bermasalah',
      text: 'Tidak dapat terhubung ke server. Coba lagi nanti.',
      confirmButtonText: 'OK',
      allowOutsideClick: true
    });
    btn.disabled = false;
    btn.innerHTML = original;
  });
}
  </script>
</body>
</html>