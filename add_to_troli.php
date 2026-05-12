<?php

ob_start();
session_start();
include 'config.php';
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu!']);
    exit;
}

$kendaraan_id = (int)($_POST['id'] ?? 0);
if ($kendaraan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID kendaraan tidak valid!']);
    exit;
}

$conn->begin_transaction();
try {
    // Kunci baris + ambil data
    $stmt = $conn->prepare("SELECT id, nama, merek, stok, status FROM kendaraan WHERE id = ? AND status = 1 AND stok > 0 FOR UPDATE");
    if (!$stmt) throw new Exception('Prepare query gagal!');
    $stmt->bind_param("i", $kendaraan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $kendaraan = $result->fetch_assoc();

    if (!$kendaraan) {
        throw new Exception('Kendaraan tidak tersedia, stok habis, atau belum disetujui!');
    }

    if (!isset($_SESSION['troli'])) {
        $_SESSION['troli'] = [];
    }

    if (in_array($kendaraan_id, $_SESSION['troli'])) {
        throw new Exception('Kendaraan ini sudah ada di troli!');
    }

    // Tambah ke troli
    $_SESSION['troli'][] = $kendaraan_id;

    // Kurangi stok
    $update = $conn->prepare("UPDATE kendaraan SET stok = stok - 1 WHERE id = ? AND stok > 0");
    $update->bind_param("i", $kendaraan_id);
    if (!$update->execute() || $update->affected_rows === 0) {
        throw new Exception('Gagal mengurangi stok! Mungkin stok sudah habis.');
    }

    $conn->commit();

    // Ambil stok terbaru setelah update
    $stmt2 = $conn->prepare("SELECT stok FROM kendaraan WHERE id = ?");
    $stmt2->bind_param("i", $kendaraan_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $stokSekarang = $result2->fetch_assoc()['stok'] ?? 0;

    $nama = $kendaraan['merek'] . ' ' . $kendaraan['nama'];
    echo json_encode([
        'success' => true,
        'message' => "$nama berhasil ditambahkan ke troli!",
        'total'   => count($_SESSION['troli']),
        'stok_sekarang' => $stokSekarang
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
?>