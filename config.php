<?<?php
// config.php → HANYA DI SINI session_start()!

$conn = mysqli_connect("localhost", "root", "", "jual_kendaraan");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

define('BASE_URL', 'http://localhost/Projek-UAS-JualBeliKendaraan/');

// HANYA SATU session_start() → DI SINI SAJA!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === LABEL STATUS ===
$statusLabels = [
    0 => ['label' => 'Menunggu Konfirmasi', 'badge' => 'warning'],
    1 => ['label' => 'Aktif', 'badge' => 'success'],
    2 => ['label' => 'Nonaktif', 'badge' => 'secondary'],
    3 => ['label' => 'Ditolak', 'badge' => 'danger'],
];

// === FUNGSI HELPER (PAKAI $conn) ===
function getKendaraan() {
    global $conn;
    $result = [];
    $query = mysqli_query($conn, "SELECT * FROM kendaraan");
    while ($data = mysqli_fetch_assoc($query)) {
        $result[] = $data;
    }
    return $result;
}

function getKendaraanRandom($limit = 3) {
    global $conn;
    $result = [];
    $query = mysqli_query($conn, "SELECT * FROM kendaraan WHERE status = 1 ORDER BY RAND() LIMIT " . (int)$limit);
    while ($data = mysqli_fetch_assoc($query)) {
        $result[] = $data;
    }
    return $result;
}

function getKategori() {
    return [
        'Motor Matic', 'Motor Bebek', 'Motor Sport', 'Motor BigBike',
        'Mobil Hatchback', 'Mobil Sedan', 'Mobil SUV', 'Mobil MPV', 'Mobil Hybrid/EV','Mobil Sport'
    ];
}

function getDetailKendaraan($id) {
    global $conn;
    $id = (int)$id;
    $query = "SELECT kendaraan.*, user.nama AS nama_user, user.no_telepon 
              FROM kendaraan 
              JOIN user ON kendaraan.user_id = user.id 
              WHERE kendaraan.id = $id";
    $data = mysqli_query($conn, $query);
    $kendaraan = mysqli_fetch_assoc($data);
    if (!$kendaraan) {
        echo "<script>alert('Data tidak ditemukan'); window.location='catalog.php';</script>";
        exit;
    }
    return $kendaraan;
}

function uploadFile($file, $folder = 'uploads') {
    $uploadDir = "../admin/$folder/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = $file['name'];
    $tmp_name = $file['tmp_name'];
    $error    = $file['error'];
    $size     = $file['size'];

    if ($error !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Gagal upload: Error kode ' . $error];
    }

    if ($size > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 5MB.'];
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Hanya JPG, JPEG, PNG, GIF yang diizinkan.'];
    }

    $newName = uniqid('file_') . '.' . $ext;
    $destination = $uploadDir . $newName;

    if (move_uploaded_file($tmp_name, $destination)) {
        return ['success' => true, 'filename' => $newName];
    } else {
        return ['success' => false, 'message' => 'Gagal memindahkan file.'];
    }
}

function simpanKendaraan($data, $files) {
    global $conn;

    // AMBIL user_id DENGAN AMAN
    $user_id = (int)($data['user_id'] ?? 0);
    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'User tidak valid. Silakan login ulang.'];
    }

    $nama = mysqli_real_escape_string($conn, $data['nama'] ?? '');
    $kategori = mysqli_real_escape_string($conn, $data['kategori'] ?? '');
    $merek = mysqli_real_escape_string($conn, $data['merek'] ?? '');
    $tipe = mysqli_real_escape_string($conn, $data['tipe'] ?? '');
    $tahun = (int)($data['tahun'] ?? 0);
    $deskripsi = mysqli_real_escape_string($conn, $data['deskripsi'] ?? '');
    $harga = (int)($data['harga'] ?? 0);
    $stok = (int)($data['stok'] ?? 1);

    // VALIDASI WAJIB
    if (empty($nama) || empty($kategori) || empty($merek) || $tahun < 1900 || $harga <= 0) {
        return ['success' => false, 'message' => 'Data tidak lengkap atau tidak valid.'];
    }

    $gambar = uploadFile($files['gambar'], 'uploads');
    if (!$gambar['success']) return $gambar;

    $bukti = uploadFile($files['bukti_transfer'], 'bukti');
    if (!$bukti['success']) return $bukti;

    $query = "INSERT INTO kendaraan 
              (nama, kategori, merek, tipe, tahun, deskripsi, gambar, harga, stok, bukti_transfer, user_id, status, created_at) 
              VALUES 
              ('$nama', '$kategori', '$merek', '$tipe', $tahun, '$deskripsi', '{$gambar['filename']}', $harga, $stok, '{$bukti['filename']}', $user_id, 0, NOW())";

    if (mysqli_query($conn, $query)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Gagal simpan: ' . mysqli_error($conn)];
    }
}

function updateKendaraan($id, $data, $files, $gambarLama = '', $buktiLama = '') {
    global $conn;
    $id = (int)$id;

    $nama = mysqli_real_escape_string($conn, $data['nama']);
    $kategori = mysqli_real_escape_string($conn, $data['kategori']);
    $merek = mysqli_real_escape_string($conn, $data['merek']);
    $tipe = mysqli_real_escape_string($conn, $data['tipe']);
    $tahun = (int)$data['tahun'];
    $deskripsi = mysqli_real_escape_string($conn, $data['deskripsi']);
    $harga = (int)$data['harga'];
    $stok = (int)($data['stok'] ?? 1);
    $status = isset($data['status']) ? (int)$data['status'] : 0;

    $gambarBaru = $gambarLama;
    $buktiBaru = $buktiLama;

    if (!empty($files['gambar']['name'])) {
        $upload = uploadFile($files['gambar'], 'uploads');
        if (!$upload['success']) return $upload;
        $gambarBaru = $upload['filename'];
        if ($gambarLama && file_exists("../admin/uploads/$gambarLama")) {
            unlink("../admin/uploads/$gambarLama");
        }
    }

    if (!empty($files['bukti_transfer']['name'])) {
        $upload = uploadFile($files['bukti_transfer'], 'bukti');
        if (!$upload['success']) return $upload;
        $buktiBaru = $upload['filename'];
        if ($buktiLama && file_exists("../admin/bukti/$buktiLama")) {
            unlink("../admin/bukti/$buktiLama");
        }
    }

    $query = "UPDATE kendaraan SET 
                nama = '$nama',
                kategori = '$kategori',
                merek = '$merek',
                tipe = '$tipe',
                tahun = $tahun,
                deskripsi = '$deskripsi',
                gambar = '$gambarBaru',
                harga = $harga,
                stok = $stok,
                bukti_transfer = '$buktiBaru',
                status = $status
              WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => mysqli_error($conn)];
    }
}

function ubahStatusKendaraan($status, $id) {
    global $conn;
    $status = (int)$status;
    $id = (int)$id;
    $query = "UPDATE kendaraan SET status = $status WHERE id = $id";
    return mysqli_query($conn, $query) && mysqli_affected_rows($conn) > 0;
}

function hapusKendaraan($id) {
    global $conn;
    $id = (int)$id;

    $query = "SELECT gambar, bukti_transfer FROM kendaraan WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $gambar = "../admin/uploads/" . $row['gambar'];
        $bukti = "../admin/bukti/" . $row['bukti_transfer'];
        if (file_exists($gambar)) unlink($gambar);
        if (file_exists($bukti)) unlink($bukti);
    }

    $query = "DELETE FROM kendaraan WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data berhasil dihapus'); window.location='kendaraan.php';</script>";
    } else {
        echo "<script>alert('Gagal: " . mysqli_error($conn) . "');</script>";
    }
}

function getStokSekarang($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT stok FROM kendaraan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['stok'] ?? 0;
}

function getKendaraanFiltered($kategori = '', $search = '', $limit = 0, $offset = 0, $userFilterId = 0, $statusFilter = null) {
    global $conn;

    $query = "SELECT k.*, u.nama AS nama_user 
              FROM kendaraan k 
              JOIN user u ON k.user_id = u.id 
              WHERE 1=1";

    $params = [];
    $types = '';

    if ($userFilterId > 0) {
        $query .= " AND k.user_id = ?";
        $params[] = $userFilterId;
        $types .= 'i';
    }

    if ($statusFilter !== null) {
        $query .= " AND k.status = ?";
        $params[] = $statusFilter;
        $types .= 'i';
    }

    if (!empty($kategori)) {
        $query .= " AND k.kategori = ?";
        $params[] = $kategori;
        $types .= 's';
    }

    if (!empty($search)) {
        $query .= " AND (k.nama LIKE ? OR k.merek LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ss';
    }

    $query .= " ORDER BY k.created_at DESC";

    if ($limit > 0) {
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function countKendaraanFiltered($kategori = '', $search = '', $userFilterId = 0, $statusFilter = null) {
    global $conn;

    $query = "SELECT COUNT(*) as total 
              FROM kendaraan k 
              JOIN user u ON k.user_id = u.id 
              WHERE 1=1";

    $params = [];
    $types = '';

    if ($userFilterId > 0) {
        $query .= " AND k.user_id = ?";
        $params[] = $userFilterId;
        $types .= 'i';
    }

    if ($statusFilter !== null) {
        $query .= " AND k.status = ?";
        $params[] = $statusFilter;
        $types .= 'i';
    }

    if (!empty($kategori)) {
        $query .= " AND k.kategori = ?";
        $params[] = $kategori;
        $types .= 's';
    }

    if (!empty($search)) {
        $query .= " AND (k.nama LIKE ? OR k.merek LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ss';
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

function loginUser($email, $password) {
    global $conn;
    $email = mysqli_real_escape_string($conn, $email);
    $query = mysqli_query($conn, "SELECT * FROM user WHERE email = '$email'");
    $user = mysqli_fetch_assoc($query);
    if ($user && hash('sha256', $password) === $user['password']) {
        $_SESSION['user'] = $user;
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Email atau password salah!'];
}

function register($data) {
    global $conn;
    $nama = mysqli_real_escape_string($conn, $data['nama']);
    $email = mysqli_real_escape_string($conn, $data['email']);
    $password = hash('SHA256', $data['password']);
    $no_tlp = mysqli_real_escape_string($conn, $data['no_telepon']);
    $role = 2;

    $query = "INSERT INTO user (nama, role, email, password, no_telepon) 
              VALUES ('$nama', $role, '$email', '$password', '$no_tlp')";

    if (mysqli_query($conn, $query)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => mysqli_error($conn)];
    }
}

function getNoTeleponByKendaraanId($kendaraanId) {
    global $conn;
    $sql = "SELECT u.no_telepon FROM kendaraan k JOIN user u ON k.user_id = u.id WHERE k.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kendaraanId);
    $stmt->execute();
    $stmt->bind_result($noTelepon);
    return $stmt->fetch() ? $noTelepon : '628123456789';
}
?>