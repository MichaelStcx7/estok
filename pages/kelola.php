<?php
session_start();
if (!isset($_SESSION['login'])) header("Location: ../auth/login.php");
include("../config/db.php");

$msg = "";
$action_mode = 'tambah'; // default mode tambah
$judul_form = "";
$stok_form = "";
$pengarang_form = ""; // variabel untuk nampung nama pengarang
$id_edit = 0;

// 1. logikanya edit buku
if (isset($_GET['id'])) {
    $id_edit = (int)$_GET['id'];
    $stmt_edit = $koneksi->prepare("SELECT id_buku, judul, stok, penulis FROM buku WHERE id_buku = ? LIMIT 1");
    $stmt_edit->bind_param("i", $id_edit);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    $data_edit = $result_edit->fetch_assoc();
    $stmt_edit->close();

    if ($data_edit) {
        $action_mode = 'edit';
        $judul_form = $data_edit['judul'];
        $stok_form = $data_edit['stok'];
        $pengarang_form = $data_edit['penulis']; // Isi form pengarang
    } else {
        $msg = "Data buku tidak ditemukan.";
    }
}

// 2. logika hapus buku
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];

    // Pertama: ambil judul & penulis dari buku yang akan dihapus
    $stmt_buku = $koneksi->prepare("SELECT judul, penulis FROM buku WHERE id_buku = ? LIMIT 1");
    $stmt_buku->bind_param("i", $id_hapus);
    $stmt_buku->execute();
    $stmt_buku->bind_result($judul_hapus, $penulis_hapus);
    $stmt_buku->fetch();
    $stmt_buku->close();

    if (!$judul_hapus) {
        $msg = "Data buku tidak ditemukan.";
    } else {
        // Cek apakah masih ada peminjaman aktif (berdasarkan judul & penulis)
        $stmt_cek_pinjam = $koneksi->prepare(
            "SELECT COUNT(*) FROM peminjaman WHERE judul_buku = ? AND penulis = ? AND tanggal_kembali IS NULL"
        );
        $stmt_cek_pinjam->bind_param("ss", $judul_hapus, $penulis_hapus);
        $stmt_cek_pinjam->execute();
        $stmt_cek_pinjam->bind_result($count_pinjam);
        $stmt_cek_pinjam->fetch();
        $stmt_cek_pinjam->close();

        if ($count_pinjam > 0) {
            $msg = "Gagal Hapus: Buku masih ada yang dipinjam!";
        } else {
            $stmt_hapus = $koneksi->prepare("DELETE FROM buku WHERE id_buku = ?");
            $stmt_hapus->bind_param("i", $id_hapus);
            if ($stmt_hapus->execute()) {
                $msg = "Buku berhasil dihapus.";
            } else {
                $msg = "Gagal menghapus buku.";
            }
            $stmt_hapus->close();
        }
    }
}

// 3. logika simpan (tambah atau edit)
if (isset($_POST['submit_form'])) {
    $judul_baru = trim($_POST['judul_form']);
    $stok_baru = (int)$_POST['stok_form'];
    $pengarang_baru = trim($_POST['pengarang_form']);
    $mode = $_POST['mode'];
    $id = (int)$_POST['id_buku'];

    if (empty($judul_baru) || $stok_baru < 0 || empty($pengarang_baru)) { // validasi pengarang
        $msg = "Judul buku, Pengarang, dan stok harus diisi dengan benar!";
    } else {
        if ($mode == 'tambah') {
            // Logika INSERT
            $stmt_tambah = $koneksi->prepare("INSERT INTO buku (judul, stok, penulis) VALUES (?, ?, ?)"); 
            $stmt_tambah->bind_param("sis", $judul_baru, $stok_baru, $pengarang_baru);
            
            if ($stmt_tambah->execute()) {
                $msg = "Buku '" . htmlspecialchars($judul_baru) . "' oleh " . htmlspecialchars($pengarang_baru) . " berhasil ditambahkan.";
            } else {
                // cek error duplikat (kode error 1062)
                if ($koneksi->errno == 1062) {
                    $msg = "Gagal menambahkan buku: Buku dengan judul dan pengarang yang sama sudah ada!";
                } else {
                    $msg = "Gagal menambahkan buku: " . $koneksi->error;
                }
            }
            $stmt_tambah->close();
            // kosongin form setelah tambah
            $judul_form = ""; $stok_form = ""; $pengarang_form = "";
        
        } elseif ($mode == 'edit') {
            // logika update
            $stmt_update = $koneksi->prepare("UPDATE buku SET judul = ?, stok = ?, penulis = ? WHERE id_buku = ?");
            $stmt_update->bind_param("sisi", $judul_baru, $stok_baru, $pengarang_baru, $id);
            
            if ($stmt_update->execute()) {
                $msg = "Data buku berhasil diperbarui!";
                // setelah update, redirect ke mode tambah
                header("Location: kelola.php?msg=" . urlencode($msg));
                exit;
            } else {
                $msg = "Gagal memperbarui data: " . $koneksi->error;
            }
            $stmt_update->close();
        }
    }
    // kalau error, isi ulang form
    $judul_form = $judul_baru; $stok_form = $stok_baru; $pengarang_form = $pengarang_baru;
}

// ambil pesan dari redirect
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// ambil data buku buat ditampilin
$result_buku = $koneksi->query("SELECT id_buku, judul, stok, penulis FROM buku ORDER BY judul ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
            <aside class="sidebar">
                <div class="brand">
                    <img src="../assets/img/logo.png" alt="E-Perpus Logo" class="logo-img" />
                </div>

                <nav class="side-nav">
                    <a class="nav-item" href="index.php">
                        <span class="icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-10.5Z" fill="currentColor"/></svg>
                        </span>
                        <span>Beranda</span>
                    </a>
                    <a class="nav-item" href="riwayat.php">
                        <span class="icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1Z" fill="currentColor"/></svg>
                        </span>
                        <span>Riwayat</span>
                    </a>
                    <a class="nav-item active" href="kelola.php">
                        <span class="icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 4 12 7h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6Z" fill="currentColor"/></svg>
                        </span>
                        <span>Kelola</span>
                    </a>
                    <a class="nav-item" href="../auth/logout.php">
                        <span class="icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8" stroke="currentColor" stroke-width="2"/><path d="M20 12H9m0 0 4-4m-4 4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <span>Keluar</span>
                    </a>
                </nav>
            </aside>

        <!-- Main -->
        <main class="main">
            <div class="hero-bg" aria-hidden="true"></div>
            <div class="topbar">
                <h1 class="page-title">Kelola buku</h1>
                <div class="user-pill">
                    <span>Halo, <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></span>
                    <span class="avatar"></span>
                </div>
            </div>

            <div class="container">
                <?php if (!empty($msg)) { ?>
                    <div class="filter-bar" style="margin-bottom: 16px;">
                        <div class="filter-title">Info</div>
                        <p style="margin: 0; color: #e5e7eb; font-weight: 600;"><?= htmlspecialchars($msg) ?></p>
                    </div>
                <?php } ?>

                <!-- Form tambah/ubah buku -->
                <div class="filter-bar manage-form">
                    <div class="filter-title"><?= ($action_mode == 'edit') ? 'Ubah Data Buku' : 'Tambah Buku Baru' ?></div>
                    <form method="POST" action="kelola.php" class="filter-form">
                        <input type="hidden" name="mode" value="<?= $action_mode ?>">
                        <input type="hidden" name="id_buku" value="<?= (int)$id_edit ?>">

                        <div class="form-stack">
                            <label for="judul_form">Judul buku</label>
                            <input id="judul_form" class="form-control" type="text" name="judul_form" placeholder="Masukkan judul" value="<?= htmlspecialchars($judul_form) ?>" required>
                        </div>

                        <div class="form-stack">
                            <label for="stok_form">Stok buku</label>
                            <input id="stok_form" class="form-control" type="number" name="stok_form" placeholder="0" min="0" value="<?= htmlspecialchars((string)$stok_form) ?>" required>
                        </div>

                        <div class="form-stack">
                            <label for="pengarang_form">Nama pengarang</label>
                            <input id="pengarang_form" class="form-control" type="text" name="pengarang_form" placeholder="Masukkan nama pengarang" value="<?= htmlspecialchars($pengarang_form) ?>" required>
                        </div>

                        <div class="form-stack" style="align-self: end;">
                            <label class="sr-only" for="submit_form">Kirim</label>
                            <button id="submit_form" type="submit" name="submit_form" class="btn-secondary filter-btn">
                                <?= ($action_mode == 'edit') ? 'Simpan Perubahan' : 'Tambah' ?>
                            </button>
                            <?php if ($action_mode == 'edit') { ?>
                                <a href="kelola.php" class="reset-link" style="margin-top: 6px;">Batalkan ubah</a>
                            <?php } ?>
                        </div>
                    </form>
                </div>

                <!-- Tabel daftar buku -->
                <div class="table-card manage-table">
                    <div class="scroll-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Stok</th>
                                <th>Penulis</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $no = 1; 
                        if ($result_buku && $result_buku->num_rows > 0) { 
                            while($row = $result_buku->fetch_assoc()) { ?>
                                <tr>
                                    <td data-label="No"><?= $no++ ?></td>
                                    <td data-label="Judul"><?= htmlspecialchars($row['judul']) ?></td>
                                    <td data-label="Stok"><?= (int)$row['stok'] ?></td>
                                    <td data-label="Penulis"><?= htmlspecialchars($row['penulis']) ?></td>
                                    <td data-label="Update">
                                        <div class="actions">
                                            <a class="icon-btn" title="Ubah" href="kelola.php?id=<?= (int)$row['id_buku'] ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            </a>
                                            <a class="icon-btn" title="Hapus" href="?hapus=<?= (int)$row['id_buku'] ?>" onclick="return confirm('Yakin ingin menghapus buku <?= htmlspecialchars($row['judul'], ENT_QUOTES) ?>?')">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                        <?php } } else { ?>
                                <tr>
                                    <td colspan="5" class="empty">Belum ada data buku.</td>
                                </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>