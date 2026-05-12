<?php
include '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: konfirmasiPostingan.php");
    exit;
}

$id = (int)$_GET['id'];
$status = (int)$_GET['status']; // 1 = aktif, 3 = ditolak

// Validasi status
if (!in_array($status, [1, 3])) {
    header("Location: konfirmasiPostingan.php");
    exit;
}

// Update status
$stmt = $conn->prepare("UPDATE kendaraan SET status = ? WHERE id = ? AND status = 0");
$stmt->bind_param("ii", $status, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $pesan = $status == 1 
        ? "Postingan berhasil dikonfirmasi dan sudah tayang!" 
        : "Postingan telah ditolak.";
    $icon = $status == 1 ? "success" : "warning";
} else {
    $pesan = "Gagal mengubah status. Mungkin sudah diproses sebelumnya.";
    $icon = "error";

$stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Processing...</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
  </style>
</head>
<body>

<script>
Swal.fire({
  icon: '<?= $icon ?>',
  title: '<?= $icon === "success" ? "Berhasil!" : ($icon === "warning" ? "Ditolak" : "Gagal") ?>',
  html: `<strong><?= htmlspecialchars($pesan) ?></strong>`,
  timer: 2500,
  timerProgressBar: true,
  showConfirmButton: false,
  allowOutsideClick: false,
  didOpen: () => {
    Swal.showLoading();
  }
}).then(() => {
  window.location = 'konfirmasiPostingan.php';
});
</script>

</body>
</html>