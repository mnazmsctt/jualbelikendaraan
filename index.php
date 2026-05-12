<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_GET['submit'])) {
  header("Location: catalog.php?search=" . urlencode($_GET['search']));
  exit;
}
$bestSellers = getKendaraanRandom(3);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stipen Automo</title>
  <!-- FAVICON -->
  <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
  <link rel="icon" href="assets/img/favicon-32x32.png" type="image/png" sizes="32x32">
  <link rel="icon" href="assets/img/favicon-16x16.png" type="image/png" sizes="16x16">
  <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
  <link rel="manifest" href="assets/img/site.webmanifest">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
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

    /* GLASS */
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

    /* NAVBAR – FIX TEXT ILANG SAAT SCROLL */
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
    .light-mode .navbar.scrolled {
      background: rgba(255,255,255,0.92) !important;
    }
    .dark-mode .navbar.scrolled {
      background: rgba(15,15,35,0.95) !important;
    }

    .navbar-brand { 
      font: 700 1.9rem 'Poppins', sans-serif; 
      color: var(--text); 
    }
    .nav-link { 
      color: var(--text) !important; 
      font-weight: 500; 
      padding: 0.5rem 1rem !important; 
      border-radius: 50px; 
      transition: var(--transition); 
    }
    .nav-link:hover, .nav-link.active { 
      background: var(--accent); 
      color: white !important; 
    }

    /* BUTTON */
    .btn-oval { 
      background: var(--accent); 
      color: white; 
      border: none; 
      border-radius: 50px; 
      padding: 0.7rem 1.8rem; 
      font-weight: 600; 
      font-size: 0.92rem; 
      transition: var(--transition); 
    }
    .btn-oval:hover { 
      background: var(--accent-hover); 
      transform: translateY(-2px); 
    }

    /* SECTION TITLE */
    .section-title { 
      font: 700 2.8rem 'Poppins', sans-serif; 
      text-align: center; 
      color: var(--text); 
      margin-bottom: 3.5rem; 
      position: relative; 
    }
    .section-title::after { 
      content: ''; 
      display: block; 
      width: 70px; 
      height: 4px; 
      background: var(--accent); 
      margin: 1rem auto 0; 
      border-radius: 2px; 
    }

    /* HERO TEXT */
    .hero-text { 
      position: absolute; 
      top: 50%; 
      left: 50%; 
      transform: translate(-50%, -50%); 
      width: 90%; 
      max-width: 800px; 
      background: var(--glass); 
      backdrop-filter: blur(16px); 
      border: 1px solid var(--border); 
      border-radius: var(--radius); 
      padding: 2rem; 
      box-shadow: var(--shadow); 
      text-align: center; 
      z-index: 2; 
      transition: transform .6s ease; 
    }
    .hero-text:hover { 
      transform: translate(-50%, -50%) translateY(-25px); 
    }
    .hero-text h5 { 
      font: 700 2.4rem 'Poppins', sans-serif; 
      margin-bottom: .5rem; 
      color: var(--text); 
    }
    .hero-text h5 span { 
      color: var(--accent); 
    }
    .hero-text p { 
      font-size: 1.1rem; 
      color: var(--text-muted); 
    }

    /* FORM */
    .form-control { 
      background: rgba(255,255,255,.7); 
      border: 1px solid var(--border); 
      border-radius: 1.2rem; 
      padding: .9rem 1.2rem; 
      color: var(--text); 
      backdrop-filter: blur(10px); 
      transition: var(--transition); 
    }
    .form-control::placeholder { 
      color: #888; 
    }
    .form-control:focus { 
      background: white; 
      border-color: var(--accent); 
      box-shadow: 0 0 0 .2rem rgba(227,242,253,.5); 
    }
    .dark-mode .form-control { 
      background: rgba(255,255,255,.15); 
      color: white; 
    }
    .dark-mode .form-control::placeholder { 
      color: rgba(255,255,255,.6); 
    }

    /* CARD */
    .card-rec h5, .card-rec .price, .card-rec .bi { 
      color: var(--accent) !important; 
    }
    #about i { 
      color: var(--accent); 
    }

    /* FOOTER */
    footer { 
      background: var(--glass); 
      backdrop-filter: blur(12px); 
      border-top: 1px solid var(--border); 
      padding: 2.5rem 0; 
      margin-top: 5rem; 
      color: var(--text-light); 
      font-size: .95rem; 
    }

    @media (max-width:768px) {
      .hero-text h5 { font-size:1.9rem; }
      .hero-text { padding:1.5rem; }
      .section-title { font-size:2.2rem; }
    }
  </style>
</head>
<body class="light-mode">
  <!-- TROLI & SETTING -->
  <div class="position-fixed top-0 end-0 p-3 d-flex gap-2" style="z-index:1050;">
    <a href="troli.php" class="btn btn-sm rounded-circle position-relative d-flex align-items-center justify-content-center"
       style="width:42px;height:42px;background:var(--glass);border:1px solid var(--border);color:var(--text);box-shadow:var(--shadow);text-decoration:none;"
       title="Troli (<?=count($_SESSION['cart']??[])?> item)">
      <i class="bi bi-cart fs-5"></i>
      <?php if(!empty($_SESSION['cart'])): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:.65rem;">
          <?=count($_SESSION['cart'])?>
        </span>
      <?php endif; ?>
    </a>
    <button id="setting-btn" class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center"
            style="width:42px;height:42px;background:var(--glass);border:1px solid var(--border);color:var(--text);box-shadow:var(--shadow);">
      <i class="bi bi-gear-fill fs-5"></i>
    </button>
  </div>

  <!-- PANEL SETTING -->
  <div id="setting-panel" class="position-fixed end-0 p-3 rounded-4 shadow-lg"
       style="top:70px;width:240px;background:var(--glass);border:1px solid var(--border);backdrop-filter:blur(16px);z-index:1040;opacity:0;visibility:hidden;transform:translateY(-10px);transition:all .3s ease;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <span class="small fw-medium">Dark Mode</span>
      <button id="toggle-mode" class="btn btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center"
              style="width:32px;height:32px;background:var(--glass);border:1px solid var(--border);color:var(--text);">
        <i class="bi bi-moon-stars-fill"></i>
      </button>
    </div>
    <hr class="my-2" style="border-color:var(--border);">
    <a href="riwayat.php" class="btn btn-sm btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2 mb-2">
      <i class="bi bi-receipt"></i>
      <span>Riwayat Pembelian</span>
    </a>
    <?php if(!isset($_SESSION['user'])): ?>
      <a href="admin/login.php" class="btn btn-sm btn-oval w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-box-arrow-in-right"></i>
        <span>Login</span>
      </a>
    <?php else: ?>
      <a href="admin/logout.php" class="btn btn-sm w-100 d-flex align-items-center justify-content-center gap-2"
         style="background:#ffebee;color:#c62828;border:1px solid #ffcdd2;">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
      </a>
    <?php endif; ?>
  </div>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg fixed-top" id="nav">
    <div class="container">
      <a class="navbar-brand" href="index.php">Stipen Automo</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mx-auto gap-3">
          <li class="nav-item">
            <a class="nav-link active" href="#home">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="catalog.php">Catalog</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#about">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#contact">Contact</a>
          </li>
          <?php if (isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? 0) == 1): ?>
    <li class="nav-item">
        <a class="nav-link" href="admin/kendaraan.php">
            <i class="bi bi-gear-fill"></i> Master
        </a>
    </li>
<?php endif; ?>
        </ul>
        <form class="d-flex" action="catalog.php" method="get">
          <input type="text" class="form-control me-2" placeholder="Cari mobil..." name="search">
          <button class="btn btn-oval" type="submit">Go</button>
        </form>
      </div>
    </div>
  </nav>

  <!-- HERO CAROUSEL -->
  <section id="home">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover">
      <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
      </div>
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="assets/img/Carousel_1.jpg" class="d-block w-100" style="height:90vh;object-fit:cover;" alt="Mobil 1">
        </div>
        <div class="carousel-item">
          <img src="assets/img/Carousel_2.jpg" class="d-block w-100" style="height:90vh;object-fit:cover;" alt="Mobil 2">
        </div>
        <div class="carousel-item">
          <img src="assets/img/Carousel_3.jpg" class="d-block w-100" style="height:90vh;object-fit:cover;" alt="Mobil 3">
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span><span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span><span class="visually-hidden">Next</span>
      </button>
      <div class="hero-text glass">
        <h5>Trade Old Car <span>Upgrade Your Style</span></h5>
        <p class="lead">Where Motion Meets Trust</p>
      </div>
    </div>
  </section>

  <!-- RECOMMENDATION -->
  <section class="py-5 mt-4">
    <div class="container">
      <h2 class="section-title">Rekomendasi Unit</h2>
      <div class="row row-cols-1 row-cols-md-3 g-4 g-lg-5">
        <?php foreach($bestSellers as $k): ?>
          <div class="col">
            <div class="glass h-100 overflow-hidden card-rec">
              <img src="admin/uploads/<?=htmlspecialchars($k['gambar'])?>" class="w-100" alt="<?=htmlspecialchars($k['nama'])?>">
              <div class="p-4 d-flex flex-column">
                <h5><?=htmlspecialchars($k['merek'].' '.$k['nama'].' '.$k['tahun'])?></h5>
                <p class="text-muted small flex-grow-1"><?=substr(htmlspecialchars($k['deskripsi']),0,80)?>...</p>
                <p class="price mb-3">Rp<?=number_format($k['harga'],0,',','.')?></p>
                <a href="detail.php?id=<?=$k['id']?>" class="btn btn-oval w-100 mt-auto">Lihat Detail</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ABOUT -->
  <section id="about" class="py-5">
    <div class="container">
      <h2 class="section-title">Tentang Kami</h2>
      <div class="row g-4 text-center">
        <div class="col-md-4">
          <div class="glass p-4"><i class="bi bi-bullseye fs-1"></i><h4 class="my-3">Misi</h4><p class="text-muted">Berkualitas baik, harga merakyat.</p></div>
        </div>
        <div class="col-md-4">
          <div class="glass p-4"><i class="bi bi-eye fs-1"></i><h4 class="my-3">Visi</h4><p class="text-muted">Menjadi dealer terpercaya di Indonesia.</p></div>
        </div>
        <div class="col-md-4">
          <div class="glass p-4"><i class="bi bi-star fs-1"></i><h4 class="my-3">Nilai</h4><p class="text-muted">Integritas, Transparansi, Kepuasan Pelanggan.</p></div>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section id="contact" class="py-5">
    <div class="container">
      <h2 class="section-title">Hubungi Kami</h2>
      <div class="row g-5">
        <div class="col-lg-6">
          <div class="glass p-4">
            <div class="row g-4 justify-content-center">
  <!-- ADMIN 1 -->
  <div class="col-md-5">
    <div class="glass p-4 text-center">
      <img src="assets/img/admin1.jpg" 
           alt="Admin 1" 
           class="rounded-circle mb-3" 
           style="width:100px;height:100px;object-fit:cover;border:3px solid var(--accent);">
      <h5 class="mb-1" style="color:var(--accent);">Nazmi Firdaus</h5>
      <p class="text-muted small mb-3">Co-Founder</p>
      
      <div class="d-flex justify-content-center gap-3 mb-3">
        <a href="https://instagram.com/user0310g" target="_blank" 
           class="text-decoration-none" style="color:#E1306C;">
          <i class="bi bi-instagram fs-4"></i>
        </a>
        <a href="https://wa.me/6285694193698" target="_blank" 
           class="text-decoration-none" style="color:#25D366;">
          <i class="bi bi-whatsapp fs-4"></i>
        </a>
      </div>
      
      <a href="https://wa.me/6285694193698?text=Halo%20Nazmi,%20saya%20ingin%20konsultasi%20kendaraan%20apakah%20kita%20bisa%bertemu?" 
         class="btn btn-wa w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-chat-dots"></i>
        <span>Chat via WhatsApp</span>
      </a>
    </div>
  </div>

  <!-- ADMIN 2 -->
  <div class="col-md-5">
    <div class="glass p-4 text-center">
      <img src="assets/img/admin2.jpg" 
           alt="Admin 2" 
           class="rounded-circle mb-3" 
           style="width:100px;height:100px;object-fit:cover;border:3px solid var(--accent);">
      <h5 class="mb-1" style="color:var(--accent);">Febriansyah</h5>
      <p class="text-muted small mb-3">Founder</p>
      
      <div class="d-flex justify-content-center gap-3 mb-3">
        <a href="https://instagram.com/_fbriiansyh" target="_blank" 
           class="text-decoration-none" style="color:#E1306C;">
          <i class="bi bi-instagram fs-4"></i>
        </a>
        <a href="https://wa.me/62881024046421" target="_blank" 
           class="text-decoration-none" style="color:#25D366;">
          <i class="bi bi-whatsapp fs-4"></i>
        </a>
      </div>
      
      <a href="https://wa.me/62881024046421?text=Halo%20febri,%20saya%20lagi%20free%20engga?%20ketemu%20yuk" 
         class="btn btn-wa w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-chat-dots"></i>
        <span>Chat via WhatsApp</span>
      </a>
    </div>
  </div>
</div>

<!-- JUDUL DI ATAS -->
<div class="text-center mb-5">
  <h3 class="section-title" style="font-size:2.2rem;margin-bottom:1rem;">Hubungi Kami</h3>
  <p class="text-muted">Jual Kendaraanmu Sekarang Juga, Dan Beli Lagi di Toko Kami</p>
</div>
          </div>
        </div>
        <div class="col-lg-6" style="color:var(--text-light);">
          <p><i class="bi bi-envelope me-2"></i> info@stipenautomo.com</p>
          <p><i class="bi bi-phone me-2"></i> +62 856 9419 3698</p>
          <p><i class="bi bi-phone me-2"></i> +62 321 321 321</p>
          <p><i class="bi bi-geo-alt me-2"></i> Jl. Kita Hari Ini, Kota Bogor</p>
          <div class="ratio ratio-16x9 mt-3">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3963.693413804888!2d106.795277614769!3d-6.589666195209!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69c5a9b7b7b7b7%3A0x9b7b7b7b7b7b7b7b!2sBogor!5e0!3m2!1sen!2sid!4v1234567890" allowfullscreen loading="lazy" style="border-radius:var(--radius);border:1px solid var(--border);"></iframe>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="text-center">
    <div class="container">
      <p class="mb-0">© 2025 Stipen Automo. All Rights Reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Setting & Theme
    const settingBtn = document.getElementById('setting-btn');
    const settingPanel = document.getElementById('setting-panel');
    const toggleMode = document.getElementById('toggle-mode');
    const icon = toggleMode.querySelector('i');
    const body = document.body;

    settingBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isVisible = settingPanel.style.opacity === '1';
      settingPanel.style.opacity = isVisible ? '0' : '1';
      settingPanel.style.visibility = isVisible ? 'hidden' : 'visible';
      settingPanel.style.transform = isVisible ? 'translateY(-10px)' : 'translateY(0)';
    });

    document.addEventListener('click', (e) => {
      if (!settingBtn.contains(e.target) && !settingPanel.contains(e.target)) {
        settingPanel.style.opacity = '0';
        settingPanel.style.visibility = 'hidden';
        settingPanel.style.transform = 'translateY(-10px)';
      }
    });

    function applySaved() {
      const mode = localStorage.getItem('mode') || 'light-mode';
      body.className = mode;
      icon.className = mode === 'dark-mode' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
    applySaved();

    toggleMode.addEventListener('click', () => {
      const isDark = body.classList.contains('dark-mode');
      const newMode = isDark ? 'light-mode' : 'dark-mode';
      body.className = newMode;
      localStorage.setItem('mode', newMode);
      icon.className = newMode === 'dark-mode' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    });

    // Navbar Scroll 
    window.addEventListener('scroll', () => {
      const nav = document.getElementById('nav');
      const scrolled = window.scrollY > 50;
      nav.classList.toggle('scrolled', scrolled);

      // Force text color saat scroll
      const textEls = nav.querySelectorAll('.navbar-brand, .nav-link, .form-control, .btn');
      textEls.forEach(el => {
        if (scrolled) {
          el.style.color = body.classList.contains('light-mode') ? '#1a1a1a' : 'white';
        } else {
          el.style.color = '';
        }
      });
    });
  </script>
</body>
</html>