<?php
session_start();
if (!isset($_SESSION['login'])) header("Location: ../auth/login.php");
include("../config/db.php");

$msg = "";

if (isset($_POST['pinjam'])) {
    // mengamankan inputan
    $nama = trim($_POST['nama']);
    $judul = trim($_POST['judul']);
    $nisn = trim($_POST['nisn']);
    $batas_kembali = trim($_POST['batas_kembali']); 

    // 2. menentukan tanggal
    $tgl_pinjam = date('Y-m-d'); 

    $penulis_input = trim($_POST['penulis']);

    if (empty($nama) || empty($judul) || empty($nisn) || empty($batas_kembali) || empty($penulis_input)) {
        $msg = "Semua field (Nama siswa, NISN, judul buku, pengarang, dan batas kembali) harus diisi!";
    } else {
        // ngecek apakah buku ada dan stoknya cukup
        $stmt_cek = $koneksi->prepare("SELECT id_buku, stok, penulis FROM buku WHERE judul = ? AND penulis = ? LIMIT 1");
        $stmt_cek->bind_param("ss", $judul, $penulis_input); // menggunakan judul dan penulis dari input
        $stmt_cek->execute();
        $result = $stmt_cek->get_result();
        $data = $result->fetch_assoc();
        $stmt_cek->close();

        if ($data) {
            $id_buku = $data['id_buku'];
            $penulis_buku = $data['penulis'];

            if ($data['stok'] > 0) {

                $stmt_pinjam = $koneksi->prepare(
                    "INSERT INTO peminjaman (nisn_siswa, nama_siswa, judul_buku, penulis, tanggal_pinjam, batas_kembali) 
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt_pinjam->bind_param("ssssss", $nisn, $nama, $judul, $penulis_buku, $tgl_pinjam, $batas_kembali); 
                $stmt_pinjam->execute();
                $stmt_pinjam->close();
                
                // update stok buku
                $stmt_update = $koneksi->prepare("UPDATE buku SET stok = stok - 1 WHERE id_buku = ?");
                $stmt_update->bind_param("i", $id_buku); 
                $stmt_update->execute();
                $stmt_update->close();
                
                $msg = "Buku '" . htmlspecialchars($judul) . "' oleh " . htmlspecialchars($penulis_buku) . " berhasil dipinjam. Batas kembali: " . htmlspecialchars($batas_kembali) . ".";
            } else {
                $msg = "Buku '" . htmlspecialchars($judul) . "' oleh " . htmlspecialchars($penulis_buku) . " tidak tersedia (Stok Habis)!";
            }
        } else {
            $msg = "Buku '" . htmlspecialchars($judul) . "' oleh '" . htmlspecialchars($penulis_input) . "' tidak ditemukan dalam daftar buku!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Peminjaman Buku</title>
    <!-- Google Fonts: Poppins -->
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
                <a class="nav-item active" href="index.php">
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
                <a class="nav-item" href="kelola.php">
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

        <!-- Main content -->
        <section class="main">
            <header class="topbar">
                <h2 class="page-title">Peminjaman</h2>
                <div class="user-pill">
                    <span class="greet">Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <span class="avatar" aria-hidden="true"></span>
                </div>
            </header>

            <div class="hero-bg" role="img" aria-label="Rak buku"></div>

            <section class="form-section">
                <?php if (!empty($msg)): ?>
                    <div class="alert error" role="alert"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>

                <form class="form-card" method="POST" novalidate>
                    <div class="form-stack">
                        <label for="nisn">NISN siswa</label>
                        <input class="form-control" type="text" name="nisn" id="nisn" placeholder="NISN siswa" required />
                    </div>

                    <div class="form-stack">
                        <label for="nama">Nama</label>
                        <input class="form-control" type="text" name="nama" id="nama" placeholder="Nama siswa" required />
                    </div>

                    <div class="form-stack">
                        <label for="judul-input">Judul Buku</label>
                        <input class="form-control" type="text" name="judul" id="judul-input" placeholder="Judul buku" list="judul-buku-list" required />
                    </div>

                    <div class="form-stack">
                        <label for="penulis-input">Nama Pengarang</label>
                        <input class="form-control" type="text" name="penulis" id="penulis-input" placeholder="Nama Pengarang" required />
                    </div>

                    <div class="form-stack">
                        <label for="batas_kembali">Batas Kembali</label>
                        <input class="form-control" type="date" id="batas_kembali" name="batas_kembali" required />
                    </div>

                    <datalist id="judul-buku-list"></datalist>

                    <div class="form-actions">
                        <button type="submit" name="pinjam" class="btn-primary">Kirim</button>
                    </div>
                </form>
            </section>
        </section>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputJudul = document.getElementById('judul-input');
    const inputPenulis = document.getElementById('penulis-input'); 
    const datalist = document.getElementById('judul-buku-list');

    if (inputJudul && datalist) {

        inputJudul.addEventListener('input', function() {
            const query = inputJudul.value;

            // kalau input kosong, kosongin datalist
            if (query.length === 0) { 
                datalist.innerHTML = ''; 
                return;
            }

            // manggil search_buku.php buat dapetin saran judul buku
            fetch('search_buku.php?term=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    datalist.innerHTML = ''; 
                    data.forEach(item => {
                        // nambahin setiap item ke datalist
                        const option = document.createElement('option');
                        option.value = item;
                        datalist.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching data:', error));
        });
        // otomatis ngisi penulis pas judul dipilih
        inputJudul.addEventListener('change', function() {
            const selectedValue = inputJudul.value;
            // ngecek format "Judul (Penulis)"
            const match = selectedValue.match(/\((.*?)\)$/); 
            if (match) {
                // ngisi input penulis
                inputPenulis.value = match[1]; 
                // ngisi ulang judul tanpa bagian penulis
                inputJudul.value = selectedValue.replace(/\s*\((.*?)\)$/, '').trim();
            } else {
                inputPenulis.value = ''; // kosongin penulis kalo format gak sesuai
            }
        });
    }
});
</script>

</body>
</html>