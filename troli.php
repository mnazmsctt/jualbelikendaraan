<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['troli'])) $_SESSION['troli'] = [];

// Bersihkan troli dari item tidak valid
$items = []; 
$total = 0; 
$validTroli = [];

foreach ($_SESSION['troli'] as $id) {
    $data = getDetailKendaraan($id);
    if ($data && $data['status'] == 1 && $data['stok'] > 0) {
        $validTroli[] = $id;
        $items[] = $data;
        $total += $data['harga'];
    }
}
$_SESSION['troli'] = $validTroli;

// AJAX: Hapus item(s)
if (isset($_POST['action']) && $_POST['action'] === 'hapus' && isset($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
    $_SESSION['troli'] = array_diff($_SESSION['troli'], $ids);
    $_SESSION['troli'] = array_values($_SESSION['troli']);

    // Hitung ulang total
    $newTotal = 0;
    foreach ($_SESSION['troli'] as $tid) {
        $d = getDetailKendaraan($tid);
        if ($d) $newTotal += $d['harga'];
    }

    echo json_encode([
        'success' => true,
        'total_items' => count($_SESSION['troli']),
        'total_harga' => $newTotal,
        'deleted_count' => count($ids)
    ]);
    exit;
}

// AJAX: Kosongkan semua
if (isset($_POST['action']) && $_POST['action'] === 'kosongkan') {
    $_SESSION['troli'] = [];
    echo json_encode(['success' => true, 'total_items' => 0, 'total_harga' => 0]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Keranjang Belanja - Stipen Automo</title>
  <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root{--radius:1.8rem;--shadow:0 12px 32px rgba(0,0,0,.1);--transition:all .4s ease;}
    .light-mode{--glass:rgba(255,255,255,.38);--border:rgba(255,255,255,.5);--accent:#1565C0;--text:#1a1a1a;--bg-gradient:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%);}
    .dark-mode{--glass:rgba(20,20,40,.55);--border:rgba(255,255,255,.22);--accent:#42A5F5;--text:#fff;--bg-gradient:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);}
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:var(--bg-gradient);background-attachment:fixed;color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;transition:var(--transition);padding-bottom:140px;}

    .glass{background:var(--glass);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);}
    .cart-header{position:fixed;top:0;left:0;right:0;z-index:1030;background:var(--glass);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:1.2rem 0;box-shadow:var(--shadow);}
    .cart-title{font:700 1.8rem 'Poppins',sans-serif;}
    .btn-back{width:48px;height:48px;background:var(--glass);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;transition:var(--transition);}
    .btn-back:hover{background:var(--accent);color:white;transform:scale(1.12);}

    .floating{position:fixed;bottom:24px;right:20px;z-index:1050;display:flex;gap:16px;}
    .float-btn{width:60px;height:60px;background:var(--glass);border:1px solid var(--border);backdrop-filter:blur(12px);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;box-shadow:var(--shadow);transition:var(--transition);}
    .float-btn:hover{transform:translateY(-8px) scale(1.12);}
    .badge-cart{position:absolute;top:-8px;right:-8px;background:#e74c3c;color:white;font-weight:700;font-size:.8rem;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;}

    #setting-panel{position:fixed;bottom:100px;right:20px;width:280px;background:var(--glass);border:1px solid var(--border);border-radius:var(--radius);padding:1.6rem;box-shadow:var(--shadow);opacity:0;visibility:hidden;transform:translateY(12px);transition:var(--transition);z-index:1040;}
    #setting-panel.show{opacity:1;visibility:visible;transform:translateY(0);}

    .item-card{background:var(--glass);border-radius:1.4rem;padding:1.2rem;margin-bottom:1rem;transition:var(--transition);border:1px solid var(--border);}
    .item-card.selected{background:rgba(21,101,192,.12);border-color:var(--accent);}
    .item-img{width:110px;height:82px;object-fit:cover;border-radius:1rem;}
    .form-check-input{width:1.4em;height:1.4em;cursor:pointer;}
    .form-check-input:checked{background-color:var(--accent);border-color:var(--accent);}

    .btn-delete{background:#dc3545;color:white;border:none;border-radius:50px;padding:.8rem 2rem;font-weight:600;display:none;align-items:center;gap:8px;transition:var(--transition);}
    .btn-delete.show{display:inline-flex;}
    .btn-delete:hover{background:#c82333;transform:translateY(-3px);}

    .price-total{font-size:2.6rem;font-weight:800;color:var(--accent);}
    .btn-checkout{background:#ff6b35;color:white;font-size:1.4rem;padding:1rem 0;font-weight:700;border-radius:50px;}
    .btn-checkout:hover{background:#e55a2b;}
    .empty-state i{font-size:5rem;opacity:.4;}
  </style>
</head>
<body class="light-mode">

  <!-- Header & Floating & Setting Panel (sama) -->
  <div class="cart-header">
    <div class="container d-flex align-items-center justify-content-between">
      <a href="catalog.php" class="btn-back"><i class="bi bi-arrow-left"></i></a>
      <div class="cart-title">Keranjang Belanja</div>
      <div style="width:48px;"></div>
    </div>
  </div>

  <div class="floating">
    <a href="troli.php" class="float-btn position-relative">
      <i class="bi bi-cart"></i>
      <?php if(!empty($_SESSION['troli'])): ?>
        <span class="badge-cart"><?=count($_SESSION['troli'])?></span>
      <?php endif; ?>
    </a>
    <button id="setting-btn" class="float-btn"><i class="bi bi-gear-fill"></i></button>
  </div>

  <div id="setting-panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <span class="fw-medium">Dark Mode</span>
      <button id="toggle-mode" class="btn rounded-circle" style="width:40px;height:40px;background:var(--glass);border:1px solid var(--border);">
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

  <div class="container" style="padding-top:100px;">
    <div class="glass p-4 p-md-5">
      <?php if(empty($items)): ?>
        <div class="text-center py-5 empty-state">
          <i class="bi bi-cart-x mb-4"></i>
          <h3 class="mb-3">Keranjang Kosong</h3>
          <p class="text-muted mb-4">Yuk, cari kendaraan impianmu!</p>
          <a href="catalog.php" class="btn btn-oval px-5">Mulai Belanja</a>
        </div>
      <?php else: ?>
        <!-- Pilih Semua & Tombol Hapus -->
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="select-all">
            <label class="form-check-label fw-medium" for="select-all">
              Pilih Semua (<span id="total-items"><?=count($items)?></span> item)
            </label>
          </div>
          <button id="btn-delete" class="btn btn-delete">
            <i class="bi bi-trash"></i> Hapus Terpilih
          </button>
        </div>

        <div id="troli-items">
          <?php foreach($items as $i): ?>
            <div class="item-card d-flex align-items-center gap-4" data-id="<?=$i['id']?>">
              <div class="form-check">
                <input class="form-check-input select-item" type="checkbox" value="<?=$i['id']?>">
              </div>
              <img src="admin/uploads/<?=htmlspecialchars($i['gambar'])?>" class="item-img" alt="">
              <div class="flex-grow-1">
                <h5 class="mb-1 fw-bold"><?=htmlspecialchars($i['merek'].' '.$i['nama'].' '.$i['tahun'])?></h5>
                <div class="price-total">Rp<?=number_format($i['harga'],0,',','.')?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <hr class="my-5" style="border-color:var(--border);opacity:.6">

        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h4 class="mb-1">Total Bayar</h4>
            <small class="text-muted">Semua item di keranjang</small>
          </div>
          <h2 class="price-total" id="total-harga">Rp<?=number_format($total,0,',','.')?></h2>
        </div>

        <div class="d-grid gap-3">
          <a href="checkout.php" class="btn btn-checkout text-center">
            <i class="bi bi-credit-card me-2"></i> CHECKOUT SEKARANG
          </a>
          <button id="kosongkan-troli" class="btn btn-outline-danger rounded-pill py-3">
            <i class="bi bi-trash me-2"></i> Kosongkan Keranjang
          </button>
          <div class="text-center">
            <a href="catalog.php" class="text-decoration-underline fw-medium">← Lanjut Belanja</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Dark Mode & Setting Panel (tetap sama)
    const body = document.body;
    const toggle = document.getElementById('toggle-mode');
    const icon = toggle?.querySelector('i');
    function applyMode() {
      const mode = localStorage.getItem('mode') || 'light-mode';
      body.className = mode;
      if (icon) icon.className = mode === 'dark-mode' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
    applyMode();
    toggle?.addEventListener('click', () => {
      const newMode = body.classList.contains('dark-mode') ? 'light-mode' : 'dark-mode';
      body.className = newMode;
      localStorage.setItem('mode', newMode);
      if (icon) icon.className = newMode === 'dark-mode' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    });

    document.getElementById('setting-btn')?.addEventListener('click', e => {
      e.stopPropagation();
      document.getElementById('setting-panel').classList.toggle('show');
    });
    document.addEventListener('click', e => {
      if (!e.target.closest('#setting-btn') && !e.target.closest('#setting-panel')) {
        document.getElementById('setting-panel').classList.remove('show');
      }
    });

    // Seleksi & Hapus
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.select-item');
    const btnDelete = document.getElementById('btn-delete');

    function updateSelection() {
      const checked = document.querySelectorAll('.select-item:checked').length;
      btnDelete.classList.toggle('show', checked > 0);

      document.querySelectorAll('.item-card').forEach(card => {
        const cb = card.querySelector('.select-item');
        card.classList.toggle('selected', cb?.checked);
      });
    }

    selectAll?.addEventListener('change', () => {
      checkboxes.forEach(cb => cb.checked = selectAll.checked);
      updateSelection();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateSelection));

    // Hapus Terpilih
    btnDelete.addEventListener('click', () => {
      const ids = Array.from(document.querySelectorAll('.select-item:checked')).map(cb => cb.value);

      Swal.fire({
        title: `Hapus ${ids.length} item?`,
        text: "Item akan dihapus dari keranjang",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
          confirmButton: 'btn btn-danger mx-2',
          cancelButton: 'btn btn-secondary mx-2'
        }
      }).then(result => {
        if (result.isConfirmed) {
          fetch('troli.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=hapus&ids=' + ids.join(',')
          })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              ids.forEach(id => {
                const card = document.querySelector(`.item-card[data-id="${id}"]`);
                if (card) {
                  card.style.transition = 'all .5s ease';
                  card.style.opacity = '0';
                  card.style.transform = 'translateX(-40px)';
                  setTimeout(() => card.remove(), 500);
                }
              });

              // Update total harga & jumlah item
              document.getElementById('total-harga').textContent = 'Rp' + Number(data.total_harga).toLocaleString('id-ID');
              document.getElementById('total-items').textContent = data.total_items;
              const badge = document.querySelector('.badge-cart');
              if (badge) badge.textContent = data.total_items;

              if (data.total_items === 0) {
                setTimeout(() => location.reload(), 800);
              }

              Swal.fire('Terhapus!', `${data.deleted_count} item berhasil dihapus`, 'success');
              updateSelection();
            }
          });
        }
      });
    });

    // Kosongkan Keranjang
    document.getElementById('kosongkan-troli')?.addEventListener('click', () => {
      Swal.fire({
        title: 'Kosongkan keranjang?',
        text: "Semua item akan dihapus",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kosongkan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
          confirmButton: 'btn btn-danger mx-2',
          cancelButton: 'btn btn-secondary mx-2'
        }
      }).then(result => {
        if (result.isConfirmed) {
          fetch('troli.php', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=kosongkan'})
            .then(() => location.reload());
        }
      });
    });
  </script>
</body>
</html>