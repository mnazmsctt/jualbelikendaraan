<?php
include 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: admin/login.php?redirect=checkout.php");
    exit;
}
if (!isset($_SESSION['troli']) || empty($_SESSION['troli'])) {
    header("Location: catalog.php");
    exit;
}

$items = array_filter(array_map('getDetailKendaraan', $_SESSION['troli']));
if (empty($items)) {
    header("Location: catalog.php");
    exit;
}

// === HITUNG TOTAL CASH (tanpa bunga) ===
$subtotal = $diskon_kategori = $diskon_member = $ppn = 0;
$is_member = ($_SESSION['user']['role'] ?? 0) == 2;

foreach ($items as $item) {
    $harga = (int)$item['harga'];
    $subtotal += $harga;
    if (stripos($item['kategori'], 'motor') !== false) $diskon_kategori += $harga * 0.04;
    elseif (stripos($item['kategori'], 'mobil') !== false) $diskon_kategori += $harga * 0.07;
    $ppn += $harga > 1000000000 ? $harga * 0.14 : $harga * 0.10;
}
$diskon_member = $is_member ? $subtotal * 0.02 : 0;
$total_cash = $subtotal - $diskon_kategori - $diskon_member + $ppn;

// === VARIABEL UTAMA ===
$success = false;
$qris_ref = '';
$form_errors = [];
$metode_bayar = 'qris';
$tenor = 0;
$total_akhir = $total_cash;

if (isset($_POST['checkout'])) {
    $nama_penerima  = trim($_POST['nama_penerima'] ?? '');
    $no_hp          = trim($_POST['no_hp'] ?? '');
    $alamat_lengkap = trim($_POST['alamat_lengkap'] ?? '');
    $provinsi       = $_POST['provinsi'] ?? '';
    $kota           = trim($_POST['kota'] ?? '');
    $kode_pos       = trim($_POST['kode_pos'] ?? '');
    $catatan        = trim($_POST['catatan'] ?? '');
    $metode_bayar   = $_POST['metode_bayar'] ?? 'qris';
    $tenor          = ($metode_bayar === 'paylater') ? (int)($_POST['tenor'] ?? 0) : 0;

    // Bunga 1% per bulan kalau cicilan
    $total_akhir = $total_cash;
    if ($metode_bayar === 'paylater' && $tenor > 0) {
        $total_akhir = $total_cash * pow(1.01, $tenor);
    }

    // Validasi
    if (empty($nama_penerima)) $form_errors[] = "Nama penerima wajib diisi";
    if (empty($no_hp)) $form_errors[] = "No HP wajib diisi";
    if (empty($alamat_lengkap)) $form_errors[] = "Alamat wajib diisi";
    if (empty($provinsi) || empty($kota) || empty($kode_pos)) $form_errors[] = "Lengkapi alamat";
    if ($metode_bayar === 'paylater' && !in_array($tenor, [3,6,12,24])) $form_errors[] = "Pilih tenor cicilan";

    if (empty($form_errors)) {
        $qris_ref = 'QRIS-' . time() . rand(100,999);

        $conn->begin_transaction();
        try {
            // SESUAI 100% DENGAN TABEL KAMU (tanggal & status pakai default)
            $stmt = $conn->prepare("INSERT INTO pembelian 
                (user_id, kendaraan_id, total_harga, subtotal, diskon_kategori, diskon_member, ppn, total_akhir,
                 qris_ref, nama_penerima, no_hp, alamat_lengkap, provinsi, kota_kab, kode_pos, catatan,
                 metode_bayar, tenor_bulan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($items as $item) {
                $harga = (int)$item['harga'];
                $diskon_kat = (stripos($item['kategori'], 'motor') !== false) ? $harga * 0.04 : ((stripos($item['kategori'], 'mobil') !== false) ? $harga * 0.07 : 0);
                $diskon_mem = $is_member ? $harga * 0.02 : 0;
                $ppn_item = $harga > 1000000000 ? $harga * 0.14 : $harga * 0.10;

                $stmt->bind_param("iiidddddsssssssssi",
                    $_SESSION['user']['id'],
                    $item['id'],
                    $harga,
                    $harga,
                    $diskon_kat,
                    $diskon_mem,
                    $ppn_item,
                    $total_akhir,
                    $qris_ref,
                    $nama_penerima,
                    $no_hp,
                    $alamat_lengkap,
                    $provinsi,
                    $kota,
                    $kode_pos,
                    $catatan,
                    $metode_bayar,
                    $tenor
                );
                $stmt->execute();
            }

            // Kurangi stok
            foreach ($items as $item) {
                $conn->query("UPDATE kendaraan SET stok = stok - 1 WHERE id = " . (int)$item['id'] . " AND stok > 0");
            }

            $conn->commit();

            // Generate QRIS
            require_once 'assets/phpqrcode/qrlib.php';
            if (!is_dir('qris_temp')) mkdir('qris_temp', 0755, true);
            QRcode::png("https://yourdomain.com/verify.php?ref=" . $qris_ref, "qris_temp/{$qris_ref}.png", QR_ECLEVEL_H, 8);

            unset($_SESSION['troli']);
            $success = true;

        } catch (Exception $e) {
            $conn->rollback();
            $form_errors[] = "Gagal checkout: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Stipen Automo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding-top: 100px; }
        .card { border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg,#0d6efd,#6610f2); color:white; padding:2rem; text-align:center; }
        .price { font-size: 2.5rem; font-weight: 900; color: #0d6efd; }
        .btn-bayar { background:#ff6b35; color:white; border:none; padding:1.2rem; border-radius:50px; font-size:1.4rem; }
        .pay-option,.tenor-btn { cursor:pointer; border:3px solid #ddd; border-radius:16px; padding:1.5rem; text-align:center; transition:all .3s; }
        .pay-option.selected,.tenor-btn.selected { border-color:#0d6efd; background:#e3f2fd; }
    </style>
</head>
<body>

<nav class="navbar fixed-top bg-white shadow-sm">
    <div class="container-fluid">
        <a href="index.php" class="navbar-brand fw-bold text-primary fs-4">Stipen Automo</a>
        <a href="troli.php" class="btn btn-outline-secondary">Kembali</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2>Checkout Pembelian</h2>
            <p><?= count($items) ?> item</p>
        </div>
        <div class="card-body p-5">

            <?php if ($success): ?>
                <div class="text-center p-5">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:6rem"></i>
                    <h1 class="text-success mt-3">PESANAN BERHASIL!</h1>
                    <p class="fs-3 fw-bold text-primary"><?= $qris_ref ?></p>
                    <img src="qris_temp/<?= $qris_ref ?>.png" class="img-fluid" style="max-width:320px">
                    <div class="mt-4">
                        <a href="riwayat.php" class="btn btn-success btn-lg">Lihat Riwayat</a>
                        <a href="index.php" class="btn btn-outline-primary btn-lg ms-2">Beranda</a>
                    </div>
                </div>

            <?php else: ?>

                <?php if ($form_errors): ?>
                    <div class="alert alert-danger">
                        <?php foreach($form_errors as $e): ?><p class="mb-1"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="row g-4">
                    <div class="col-lg-7">
                        <h4>Informasi Pengiriman</h4>
                        <div class="row g-3">
                            <div class="col-12"><input name="nama_penerima" class="form-control form-control-lg" placeholder="Nama Penerima" required></div>
                            <div class="col-12"><input name="no_hp" class="form-control form-control-lg" placeholder="08xxxxxxxxxx" required></div>
                            <div class="col-12"><textarea name="alamat_lengkap" class="form-control form-control-lg" rows="3" placeholder="Alamat Lengkap" required></textarea></div>
                            <div class="col-md-4"><input name="kota" class="form-control form-control-lg" placeholder="Kota/Kab" required></div>
                            <div class="col-md-4"><input name="kode_pos" class="form-control form-control-lg" maxlength="5" placeholder="Kode Pos" required></div>
                            <div class="col-md-4">
                                <select name="provinsi" class="form-select form-select-lg" required>
                                    <option value="">Provinsi</option>
                                    <option>Aceh</option>
                                    <option>Bali</option>
                                    <option>Banten</option>
                                    <option>Bengkulu</option>
                                    <option>Daerah Istimewa Yogyakarta</option>
                                    <option>DKI Jakarta</option>
                                    <option>Gorontalo</option>
                                    <option>Jambi</option>
                                    <option>Jawa Barat</option>
                                    <option>Jawa Tengah</option>
                                    <option>Jawa Timur</option>
                                    <option>Kalimantan Barat</option>
                                    <option>Kalimantan Selatan</option>
                                    <option>Kalimantan Tengah</option>
                                    <option>Kalimantan Timur</option>
                                    <option>Kalimantan Utara</option>
                                    <option>Kepulauan Bangka Belitung</option>
                                    <option>Kepulauan Riau</option>
                                    <option>Lampung</option>
                                    <option>Maluku</option>
                                    <option>Maluku Utara</option>
                                    <option>Nusa Tenggara Barat</option>
                                    <option>Nusa Tenggara Timur</option>
                                    <option>Papua</option>
                                    <option>Papua Barat</option>
                                    <option>Papua Barat Daya</option>
                                    <option>Papua Pegunungan</option>
                                    <option>Papua Selatan</option>
                                    <option>Papua Tengah</option>
                                    <option>Riau</option>
                                    <option>Sulawesi Barat</option>
                                    <option>Sulawesi Selatan</option>
                                    <option>Sulawesi Tengah</option>
                                    <option>Sulawesi Tenggara</option>
                                    <option>Sulawesi Utara</option>
                                    <option>Sumatera Barat</option>
                                    <option>Sumatera Selatan</option>
                                    <option>Sumatera Utara</option>
                                </select>
                            </div>
                            <div class="col-12"><textarea name="catatan" class="form-control" rows="2" placeholder="Catatan (opsional)"></textarea></div>
                        </div>

                        <hr class="my-5">

                        <h4>Metode Pembayaran</h4>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="pay-option selected" onclick="pilihMetode('qris')">
                                    <i class="bi bi-qr-code-scan fs-1 text-primary"></i>
                                    <h5>Bayar Sekarang</h5>
                                    <h6>All Payment</h6>
                                    <p class="text-success fw-bold">Rp<?= number_format($total_cash,0,',','.') ?></p>
                                    <input type="radio" name="metode_bayar" value="qris" checked hidden>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="pay-option" onclick="pilihMetode('paylater')">
                                    <i class="bi bi-credit-card fs-1 text-warning"></i>
                                    <h5>Cicilan</h5>
                                    <input type="radio" name="metode_bayar" value="paylater" hidden>
                                </div>
                            </div>
                        </div>

                        <div id="tenor-box" style="display:none;" class="mt-4">
                            <h5>Pilih Tenor</h5>
                            <div class="row g-3">
                                <?php foreach ([3,6,12,24] as $t): 
                                    $total_cicil = $total_cash * pow(1.01, $t);
                                    $angsuran = ceil($total_cicil / $t);
                                ?>
                                    <div class="col-6 col-md-3">
                                        <div class="tenor-btn" onclick="pilihTenor(<?= $t ?>, <?= $total_cicil ?>, <?= $angsuran ?>)">
                                            <?= $t ?> Bulan<br>
                                            <strong>Rp<?= number_format($angsuran,0,',','.') ?>/bln</strong><br>
                                            <small>Total: Rp<?= number_format($total_cicil,0,',','.') ?></small>
                                            <input type="radio" name="tenor" value="<?= $t ?>" hidden>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <hr class="my-5">
                        <div class="d-flex justify-content-between align-items-end">
                            <h4>Total Bayar</h4>
                            <h3 class="price" id="total-harga">Rp<?= number_format($total_cash,0,',','.') ?></h3>
                        </div>

                        <button type="submit" name="checkout" class="btn btn-bayar w-100 mt-4">
                            LANJUTKAN KE PEMBAYARAN
                        </button>
                    </div>

                    <div class="col-lg-5">
                        <div class="text-center sticky-top" style="top:120px;">
                            <h4>Scan QRIS</h4>
                            <div class="bg-white p-4 rounded shadow" style="max-width:380px; margin:auto;">
                                <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="height:320px;">
                                    <i class="bi bi-qr-code-scan fs-1 text-muted"></i>
                                </div>
                                <p class="text-muted mt-3">QRIS muncul setelah checkout berhasil</p>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const totalCash = <?= $total_cash ?>;
function formatRupiah(n) { return 'Rp' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

function pilihMetode(m) {
    document.querySelectorAll('.pay-option').forEach(e=>e.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.querySelector(`input[value="${m}"]`).checked = true;
    document.getElementById('tenor-box').style.display = m==='paylater'?'block':'none';
    if(m==='qris') document.getElementById('total-harga').textContent = formatRupiah(totalCash);
}

function pilihTenor(t, totalCicil, angsuran) {
    document.querySelectorAll('.tenor-btn').forEach(e=>e.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.querySelector(`input[value="${t}"]`).checked = true;
    document.getElementById('total-harga').textContent = formatRupiah(Math.round(totalCicil));
}
</script>

</body>
</html>