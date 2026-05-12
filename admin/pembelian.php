<?php
include '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
  header("Location: login.php");
  exit;
}

// PROSES ACC / BATAL
if (isset($_GET['acc'])) {
  $ref = mysqli_real_escape_string($conn, $_GET['acc']);
  $query = "UPDATE pembelian SET status = 'paid' WHERE qris_ref = '$ref'";
  if (mysqli_query($conn, $query)) {
    $msg = "Pembayaran ACC!";
  }
}

if (isset($_GET['batal'])) {
  $ref = mysqli_real_escape_string($conn, $_GET['batal']);
  $query = "DELETE FROM pembelian WHERE qris_ref = '$ref'";
  if (mysqli_query($conn, $query)) {
    $msg = "Pembelian dibatalkan!";
  }
}

// AMBIL DATA PEMBELIAN
$query = "SELECT p.*, u.nama AS nama_user, k.merek, k.nama AS nama_kendaraan 
          FROM pembelian p 
          JOIN user u ON p.user_id = u.id 
          JOIN kendaraan k ON p.kendaraan_id = k.id 
          ORDER BY p.tanggal DESC";
$result = mysqli_query($conn, $query);
$pembelian = [];
while ($row = mysqli_fetch_assoc($result)) {
  $pembelian[$row['qris_ref']]['items'][] = $row;
  if (!isset($pembelian[$row['qris_ref']]['info'])) {
    $pembelian[$row['qris_ref']]['info'] = [
      'tanggal' => $row['tanggal'],
      'nama_user' => $row['nama_user'],
      'status' => $row['status'],
      'total' => 0
    ];
  }
  $pembelian[$row['qris_ref']]['info']['total'] += $row['total_harga'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Pembelian</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --radius: 1.6rem; --shadow: 0 10px 30px rgba(0,0,0,0.08); }
    .light-mode { --glass: rgba(255,255,255,0.3); --border: rgba(255,255,255,0.4); --accent: #1565C0; --bg: #f8fafc; }
    .dark-mode { --glass: rgba(20,20,40,0.5); --border: rgba(255,255,255,0.15); --accent: #42A5F5; --bg: #0f172a; }
    body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; }
    .glass-card { background: var(--glass); backdrop-filter: blur(16px); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); }
    .status-paid { background: #d1fae5; color: #065f46; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .btn-acc { background: #16a34a; color: white; }
    .btn-batal { background: #dc2626; color: white; }
  </style>
</head>
<body class="light-mode">

  <nav class="navbar navbar-expand-lg fixed-top glass-card">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="kendaraan.php">Admin Panel</a>
      <div>
        <a href="kendaraan.php" class="btn btn-outline-secondary btn-sm">Kendaraan</a>
        <a href="pembelian.php" class="btn btn-primary btn-sm">Pembelian</a>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <main class="container py-5 mt-5">
    <h2 class="text-center mb-5" style="font:700 2.5rem 'Poppins',sans-serif; color:var(--accent);">
      Kelola Pembelian
    </h2>

    <?php if (isset($msg)): ?>
      <div class="alert alert-success text-center glass-card p-3">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="glass-card p-4">
      <?php if (empty($pembelian)): ?>
        <p class="text-center text-muted py-4">Belum ada pembelian.</p>
      <?php else: ?>
        <?php foreach ($pembelian as $ref => $data): ?>
          <?php $info = $data['info']; ?>
          <div class="border rounded p-3 mb-3 glass-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <strong>Ref: <?= htmlspecialchars($ref) ?></strong><br>
                <small class="text-muted">
                  <?= date('d M Y, H:i', strtotime($info['tanggal'])) ?> 
                  • <?= htmlspecialchars($info['nama_user']) ?>
                </small>
              </div>
              <div class="text-end">
                <span class="badge rounded-pill <?= $info['status'] == 'paid' ? 'status-paid' : 'status-pending' ?>">
                  <?= $info['status'] == 'paid' ? 'LUNAS' : 'MENUNGGU' ?>
                </span>
              </div>
            </div>

            <div class="border-top pt-2 mb-2">
              <?php foreach ($data['items'] as $item): ?>
                <div class="d-flex justify-content-between small">
                  <span><?= htmlspecialchars($item['merek'] . ' ' . $item['nama_kendaraan']) ?></span>
                  <span>Rp<?= number_format($item['total_harga'], 0, ',', '.') ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center">
              <strong>Total: Rp<?= number_format($info['total'], 0, ',', '.') ?></strong>
              <div>
                <?php if ($info['status'] == 'pending'): ?>
                  <a href="?acc=<?= urlencode($ref) ?>" class="btn btn-acc btn-sm" 
                     onclick="return confirm('ACC pembayaran ini?')">
                    <i class="bi bi-check-circle"></i> ACC
                  </a>
                  <a href="?batal=<?= urlencode($ref) ?>" class="btn btn-batal btn-sm" 
                     onclick="return confirm('Batalkan pembelian ini?')">
                    <i class="bi bi-x-circle"></i> Batal
                  </a>
                <?php else: ?>
                  <span class="text-success small">Sudah di-ACC</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <script>
    document.body.className = localStorage.getItem('mode') || 'light-mode';
  </script>
</body>
</html>