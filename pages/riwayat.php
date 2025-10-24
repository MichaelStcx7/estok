<?php
session_start();
if (!isset($_SESSION['login'])) header("Location: ../auth/login.php");
include("../config/db.php");

// Variabel Filter Default
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'semua';
$filter_judul = isset($_GET['judul']) ? trim($_GET['judul']) : '';
$filter_nisn = isset($_GET['nisn']) ? trim($_GET['nisn']) : '';
$filter_nama = isset($_GET['nama']) ? trim($_GET['nama']) : ''; 
// BARU: Tambahkan variabel filter untuk Penulis
$filter_penulis = isset($_GET['penulis']) ? trim($_GET['penulis']) : '';

$where_clauses = [];
$param_types = '';
$param_values = [];

// Logika Build Query (Status)
if ($filter_status === 'dipinjam') {
    $where_clauses[] = "tanggal_kembali IS NULL";
} elseif ($filter_status === 'dikembalikan') {
    $where_clauses[] = "tanggal_kembali IS NOT NULL";
} elseif ($filter_status === 'terlambat') {
    $where_clauses[] = "tanggal_kembali IS NULL AND batas_kembali < CURDATE()";
}

// Logika Build Query (Judul)
if (!empty($filter_judul)) {
    $where_clauses[] = "judul_buku LIKE ?";
    $param_types .= 's';
    $param_values[] = "%" . $filter_judul . "%";
}

// Logika Build Query (Penulis) - BARU
if (!empty($filter_penulis)) {
    $where_clauses[] = "penulis LIKE ?";
    $param_types .= 's';
    $param_values[] = "%" . $filter_penulis . "%";
}

// Logika Build Query (NISN)
if (!empty($filter_nisn)) {
    $where_clauses[] = "nisn_siswa = ?";
    $param_types .= 's';
    $param_values[] = $filter_nisn;
}

// Logika Build Query (NAMA)
if (!empty($filter_nama)) {
    $where_clauses[] = "nama_siswa LIKE ?";
    $param_types .= 's';
    $param_values[] = "%" . $filter_nama . "%";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// PERBAIKAN: SELECT juga kolom penulis
$query_riwayat = "SELECT id_pinjam, nisn_siswa, nama_siswa, judul_buku, penulis, tanggal_pinjam, batas_kembali, tanggal_kembali 
                  FROM peminjaman 
                  " . $where_sql . " 
                  ORDER BY tanggal_pinjam DESC";

// --- LOGIKA EXPORT CSV/EXCEL (menggunakan query yang sudah difilter) ---
if (isset($_POST['export_csv'])) {
    
    // Eksekusi query dengan filter yang sama
    $stmt_export = $koneksi->prepare($query_riwayat);
    if (!empty($param_types)) {
        $stmt_export->bind_param($param_types, ...$param_values);
    }
    $stmt_export->execute();
    $result_export = $stmt_export->get_result();

    // Persiapan header CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_riwayat_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');

    // PERBAIKAN: Tambahkan 'Penulis' di Header Kolom CSV
    fputcsv($output, array('No', 'NISN', 'Nama Siswa', 'Judul Buku', 'Penulis', 'Tgl Pinjam', 'Batas Kembali', 'Tgl Kembali', 'Status'));

    // Data Baris
    $no = 1;
    while ($row = $result_export->fetch_assoc()) {
        $status = $row['tanggal_kembali'] ? 'Dikembalikan' : 'Dipinjam';
        $data_row = array(
            $no++,
            $row['nisn_siswa'],
            $row['nama_siswa'],
            $row['judul_buku'],
            $row['penulis'], // BARU: Data Penulis
            $row['tanggal_pinjam'],
            $row['batas_kembali'],
            $row['tanggal_kembali'],
            $status
        );
        fputcsv($output, $data_row);
    }

    $stmt_export->close();
    fclose($output);
    exit;
}

// Eksekusi Query untuk Tampilan Halaman
$stmt_riwayat = $koneksi->prepare($query_riwayat);
if (!empty($param_types)) {
    $stmt_riwayat->bind_param($param_types, ...$param_values);
}
$stmt_riwayat->execute();
$result_riwayat = $stmt_riwayat->get_result();
$stmt_riwayat->close();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Riwayat Peminjaman</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/style.css" />
    </head>
    <body>
        <div class="dashboard">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="brand">
                    <img src="../assets/img/logo.png" alt="E-Perpus Logo" class="logo-img" />
                </div>

                <nav class="side-nav">
                    <a class="nav-item" href="../index.php">
                        <span class="icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-10.5Z" fill="currentColor"/></svg>
                        </span>
                        <span>Beranda</span>
                    </a>
                    <a class="nav-item active" href="riwayat.php">
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

            <section class="main">
                <header class="topbar">
                    <h2 class="page-title">Riwayat</h2>
                    <div class="user-pill">
                        <span class="greet">Halo, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                        <span class="avatar" aria-hidden="true"></span>
                    </div>
                </header>

                <div class="hero-bg" role="img" aria-label="Rak buku"></div>

                <div class="container">
                    <section class="filter-bar">
                        <h3 class="filter-title">Filter riwayat</h3>
                        <form class="filter-form" method="GET" action="riwayat.php">
                            <label class="sr-only" for="status">Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="semua" <?= $filter_status == 'semua' ? 'selected' : '' ?>>Status</option>
                                <option value="dipinjam" <?= $filter_status == 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                                <option value="dikembalikan" <?= $filter_status == 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                                <option value="terlambat" <?= $filter_status == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                            </select>
                            <label class="sr-only" for="judul">Judul</label>
                            <input class="form-control" type="text" name="judul" id="judul" value="<?= htmlspecialchars($filter_judul) ?>" placeholder="Judul" />
                            <label class="sr-only" for="nisn">NISN</label>
                            <input class="form-control" type="text" name="nisn" id="nisn" value="<?= htmlspecialchars($filter_nisn) ?>" placeholder="NISN" />
                            <label class="sr-only" for="nama">Nama</label>
                            <input class="form-control" type="text" name="nama" id="nama" value="<?= htmlspecialchars($filter_nama) ?>" placeholder="Nama" />
                            <label class="sr-only" for="penulis">Penulis</label>
                            <input class="form-control" type="text" name="penulis" id="penulis" value="<?= htmlspecialchars($filter_penulis) ?>" placeholder="Penulis" />
                            <button class="btn-primary filter-btn-riwayat" type="submit">Filter</button>
                            <a class="reset-link" href="riwayat.php">Reset</a>
                        </form>
                    </section>

                    <form class="export-row" method="POST" action="riwayat.php?status=<?= urlencode($filter_status) ?>&judul=<?= urlencode($filter_judul) ?>&nisn=<?= urlencode($filter_nisn) ?>&nama=<?= urlencode($filter_nama) ?>&penulis=<?= urlencode($filter_penulis) ?>">
                        <button class="btn-secondary" type="submit" name="export_csv">Export ke Excel</button>
                    </form>

                    <section class="table-card">
                        <?php if ($result_riwayat->num_rows > 0) { ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NISN</th>
                                        <th>Nama Siswa</th>
                                        <th>Judul Buku</th>
                                        <th>Penulis</th>
                                        <th>Tgl. Pinjam</th>
                                        <th>Batas Kembali</th>
                                        <th>Tgl. Kembali</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; while($row = $result_riwayat->fetch_assoc()) { 
                                        $is_terlambat = !$row['tanggal_kembali'] && strtotime($row['batas_kembali']) < time();
                                    ?>
                                        <tr class="<?= $is_terlambat ? 'late' : '' ?>">
                                            <td data-label="No"><?= $no++ ?></td>
                                            <td data-label="NISN"><?= htmlspecialchars($row['nisn_siswa']) ?></td>
                                            <td data-label="Nama Siswa"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                            <td data-label="Judul Buku"><?= htmlspecialchars($row['judul_buku']) ?></td>
                                            <td data-label="Penulis"><?= htmlspecialchars($row['penulis']) ?></td>
                                            <td data-label="Tgl. Pinjam"><?= htmlspecialchars($row['tanggal_pinjam']) ?></td>
                                            <td data-label="Batas Kembali"><?= htmlspecialchars($row['batas_kembali']) ?></td>
                                            <td data-label="Tgl. Kembali">
                                                <?= $row['tanggal_kembali'] ? htmlspecialchars($row['tanggal_kembali']) : 'Belum Kembali' ?>
                                            </td>
                                            <td data-label="Status">
                                                <?= $is_terlambat ? 'TERLAMBAT!' : ($row['tanggal_kembali'] ? 'Dikembalikan' : 'Dipinjam') ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } else { ?>
                            <p class="empty">Tidak ada riwayat peminjaman yang tercatat dengan filter saat ini.</p>
                        <?php } ?>
                    </section>
                </div>
            </section>
        </div>
    </body>
    </html>