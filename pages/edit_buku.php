<?php
session_start();
if (!isset($_SESSION['login'])) header("Location: ../auth/login.php");
include("../config/db.php");

$msg = "";
$buku_data = null;
$id_buku = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Ambil data buku yang akan diedit
if ($id_buku > 0) {
    $stmt_select = $koneksi->prepare("SELECT id_buku, judul, stok FROM buku WHERE id_buku = ? LIMIT 1");
    $stmt_select->bind_param("i", $id_buku);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $buku_data = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$buku_data) {
        die("Data buku tidak ditemukan.");
    }
} else {
    die("ID buku tidak valid.");
}

// 2. Logika Update Data
if (isset($_POST['update'])) {
    $judul_baru = trim($_POST['judul']);
    $stok_baru = (int)$_POST['stok'];

    if (empty($judul_baru) || $stok_baru < 0) {
        $msg = "Judul dan stok harus diisi dengan benar!";
    } else {
        $stmt_update = $koneksi->prepare("UPDATE buku SET judul = ?, stok = ? WHERE id_buku = ?");
        $stmt_update->bind_param("sii", $judul_baru, $stok_baru, $id_buku);
        
        if ($stmt_update->execute()) {
            $msg = "✅ Data buku berhasil diperbarui!";
            // Perbarui data yang ditampilkan di form
            $buku_data['judul'] = $judul_baru;
            $buku_data['stok'] = $stok_baru;
        } else {
            $msg = "❌ Gagal memperbarui data: " . $koneksi->error;
        }
        $stmt_update->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Buku</title>
</head>
<body>
<h2>Edit Buku: <?= htmlspecialchars($buku_data['judul']) ?></h2>

<nav>
    <a href="kelola.php">Kelola Buku</a> |
    <a href="peminjaman.php">Peminjaman Buku</a> |
    <a href="pengembalian.php">Pengembalian Buku</a> |
    <a href="riwayat.php">Riwayat</a> |
    <a href="../auth/logout.php">Logout</a>
</nav>
<hr>

<a href="kelola.php">← Kembali ke Kelola Buku</a>

<?php 
if (!empty($msg)) echo "<p style='color:green;'>" . htmlspecialchars($msg) . "</p>"; 
?>

<form method="POST">
    <label for="judul">Judul Buku:</label><br>
    <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($buku_data['judul']) ?>" required><br><br>
    
    <label for="stok">Stok Buku:</label><br>
    <input type="number" id="stok" name="stok" value="<?= $buku_data['stok'] ?>" min="0" required><br><br>
    
    <button type="submit" name="update">Simpan Perubahan</button>
</form>

</body>
</html>