<?php
include 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: admin/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$periode = $_GET['periode'] ?? 'minggu';

// === HITUNG TANGGAL YANG BENAR-BENAR SESUAI ===
$start = $end = null;
$judul = "Minggu Ini";

switch ($periode) {
    case 'bulan':
        $start = date('Y-m-01 00:00:00');                    // 1 bulan ini
        $end   = date('Y-m-t 23:59:59');                    // akhir bulan ini
        $judul = "Bulan Ini (" . date('F Y') . ")";
        break;
    case 'tahun':
        $start = date('Y-01-01 00:00:00');                   // 1 Januari tahun ini
        $end   = date('Y-12-31 23:59:59');                   // 31 Desember tahun ini
        $judul = "Tahun Ini (" . date('Y') . ")";
        break;
    case 'semua':
        $judul = "Semua Waktu";
        break;
    default: // minggu
        $start = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $end   = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        $judul = "Minggu Ini";
        break;
}

// === TOTAL & JUMLAH PESANAN ===
$sql_total = "SELECT COUNT(*) AS jml, COALESCE(SUM(total_akhir),0) AS tot FROM pembelian WHERE user_id = ?";
$types_total = "i";
$params_total = [$user_id];

if ($periode !== 'semua') {
    $sql_total .= " AND tanggal >= ? AND tanggal <= ?";
    $types_total .= "ss";
    $params_total[] = $start;
    $params_total[] = $end;
}

$stmt = $conn->prepare($sql_total);
$stmt->bind_param($types_total, ...$params_total);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$total_pendapatan = $res['tot'];
$jumlah_pesanan   = $res['jml'];
$rata_rata        = $jumlah_pesanan > 0 ? $total_pendapatan / $jumlah_pesanan : 0;

// === AMBIL DATA PESANAN ===
$sql = "SELECT p.*, k.nama AS kendaraan_nama, k.merek, k.tahun, k.gambar
        FROM pembelian p
        JOIN kendaraan k ON p.kendaraan_id = k.id
        WHERE p.user_id = ?";

$types = "i";
$params = [$user_id];

if ($periode !== 'semua') {
    $sql .= " AND p.tanggal >= ? AND p.tanggal <= ?";
    $types .= "ss";
    $params[] = $start;
    $params[] = $end;
}

$sql .= " ORDER BY p.tanggal DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$pesanan = [];
while ($row = $result->fetch_assoc()) {
    $ref = $row['qris_ref'];
    if (!isset($pesanan[$ref])) {
        $pesanan[$ref] = ['info' => $row, 'items' => []];
    }
    $pesanan[$ref]['items'][] = [
        'merek'  =>  $row['merek'],
        'nama'   =>  $row['kendaraan_nama'],
        'tahun'  =>  $row['tahun'],
        'gambar' =>  $row['gambar'],
        'harga'  =>  $row['total_harga']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Pembelian - Stipen Automo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; padding-top: 100px; font-family: 'Segoe UI', sans-serif; }
    .card { border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow:hidden; }
    .card-header { background: linear-gradient(135deg,#0d6efd,#6610f2); color:white; padding:2rem; text-align:center; }
    .status-lunas { background:#d1fae5; color:#065f46; padding:0.5rem 1rem; border-radius:50px; }
    .dp-box { background:#fff3cd; border-left:5px solid #ffc107; padding:1rem; border-radius:8px; }
    .angsuran-box { background:#d1ecf1; border-left:5px solid #17a2b8; padding:1rem; border-radius:8px; }
  </style>
</head>
<body>

<nav class="navbar fixed-top bg-white shadow-sm">
  <div class="container-fluid">
    <a href="index.php" class="navbar-brand fw-bold text-primary fs-4">Stipen Automo</a>
    <a href="catalog.php" class="btn btn-outline-secondary">Belanja Lagi</a>
  </div>
</nav>

<div class="container mt-4">
  <div class="card">
    <div class="card-header">
      <h2 class="mb-0">Riwayat Pembelian</h2>
      <p class="mb-0 opacity-75"><?= $jumlah_pesanan ?> pesanan</p>
    </div>
    <div class="card-body p-5">

      <!-- TOMBOL PERIODE -->
      <div class="text-center mb-5">
        <div class="btn-group" role="group">
          <a href="?periode=minggu" class="btn <?= $periode=='minggu' ? 'btn-primary' : 'btn-outline-primary' ?>">Minggu Ini</a>
          <a href="?periode=bulan"  class="btn <?= $periode=='bulan'  ? 'btn-success' : 'btn-outline-success' ?>">Bulan Ini</a>
          <a href="?periode=tahun"  class="btn <?= $periode=='tahun'  ? 'btn-warning' : 'btn-outline-warning' ?>">Tahun Ini</a>
          <a href="?periode=semua"  class="btn <?= $periode=='semua'  ? 'btn-secondary' : 'btn-outline-secondary' ?>">Semua Waktu</a>
        </div>
      </div>

      <!-- CARD LAPORAN -->
      <div class="card mb-5 border-start border-5 border-info shadow">
        <div class="card-body text-center py-4">
          <h4 class="text-info fw-bold mb-3"><?= $judul ?></h4>
          <div class="row">
            <div class="col-md-4">
              <p class="fs-2 fw-bold text-info mb-0">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></p>
              <small class="text-muted">Total Pendapatan</small>
            </div>
            <div class="col-md-4">
              <p class="fs-2 fw-bold text-primary mb-0"><?= $jumlah_pesanan ?></p>
              <small class="text-muted">Jumlah Pesanan</small>
            </div>
            <div class="col-md-4">
              <p class="fs-2 fw-bold text-success mb-0">Rp <?= number_format($rata_rata, 0, ',', '.') ?></p>
              <small class="text-muted">Rata-rata Transaksi</small>
            </div>
          </div>
        </div>
      </div>

      <!-- DAFTAR PESANAN -->
      <?php if (empty($pesanan)): ?>
        <div class="text-center py-5">
          <i class="bi bi-inbox fs-1 text-muted"></i>
          <h4 class="mt-4 text-muted">Tidak ada pesanan di periode ini</h4>
        </div>
      <?php else: ?>
        <?php foreach ($pesanan as $ref => $o): 
          $i = $o['info'];
          $dp = $i['metode_bayar'] === 'paylater' ? $i['total_harga'] * 0.3 : 0;
          $sisa = $i['total_harga'] - $dp;
          $bunga = $sisa * 0.01 * ($i['tenor_bulan'] ?? 0);
          $angsuran = ($i['tenor_bulan'] ?? 0) > 0 ? ceil(($sisa + $bunga) / $i['tenor_bulan']) : 0;
        ?>
          <div class="border-bottom pb-4 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5>Pesanan #<?= htmlspecialchars($ref) ?></h5>
                <small class="text-muted"><?= date('d M Y, H:i', strtotime($i['tanggal'])) ?></small>
              </div>
              <div class="text-end">
                <?= $i['metode_bayar'] === 'paylater' 
                  ? '<div class="dp-box mb-2"><strong>DP 30% SUDAH DIBAYAR</strong></div><div class="angsuran-box"><strong>CICILAN '.$i['tenor_bulan'].' BULAN</strong></div>' 
                  : '<span class="status-lunas">LUNAS QRIS</span>' ?>
              </div>
            </div>

            <div class="row align-items-center">
              <div class="col-auto">
                <img src="admin/uploads/<?= htmlspecialchars($o['items'][0]['gambar'] ?? 'no-image.jpg') ?>" 
                     class="rounded" width="90" height="70" style="object-fit:cover;">
              </div>
              <div class="col">
                <strong><?= htmlspecialchars($o['items'][0]['merek'] . ' ' . $o['items'][0]['nama'] . ' ' . $o['items'][0]['tahun']) ?></strong>
                <?= count($o['items']) > 1 ? '<br><small class="text-muted">+ '.(count($o['items'])-1).' item lain</small>' : '' ?>
              </div>
              <div class="col-auto text-end">
                <?= $i['metode_bayar'] === 'paylater' 
                  ? '<div class="fs-4 text-warning">Rp '.number_format($angsuran,0,',','.').'</div><small class="text-muted">/ bulan</small>' 
                  : '<div class="fs-4">Rp '.number_format($i['total_akhir'],0,',','.').'</div>' ?>
              </div>
            </div>

            <div class="mt-3 text-end">
              <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-<?= htmlspecialchars($ref) ?>">
                Lihat Detail
              </button>
            </div>
          </div>

          <!-- Modal Detail -->
          <div class="modal fade" id="modal-<?= htmlspecialchars($ref) ?>">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5>Detail Pesanan #<?= htmlspecialchars($ref) ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="row">
                    <div class="col-md-7">
                      <?php foreach ($o['items'] as $it): ?>
                        <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                          <img src="admin/uploads/<?= htmlspecialchars($it['gambar']) ?>" width="70" class="rounded">
                          <div>
                            <strong><?= htmlspecialchars($it['merek'].' '.$it['nama'].' '.$it['tahun']) ?></strong><br>
                            Rp <?= number_format($it['harga'],0,',','.') ?>
                          </div>
                        </div>
                      <?php endforeach; ?>
                      <hr>
                      <h5 class="text-end">Total: Rp <?= number_format($i['total_akhir'],0,',','.') ?></h5>
                    </div>
                    <div class="col-md-5">
                      <?= $i['metode_bayar'] === 'paylater' 
                        ? '<div class="dp-box mb-3"><strong>DP Dibayar:</strong> Rp '.number_format($dp,0,',','.').'</div>
                           <div class="angsuran-box"><strong>Angsuran:</strong> Rp '.number_format($angsuran,0,',','.').' × '.$i['tenor_bulan'].' bulan</div>' 
                        : '' ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>