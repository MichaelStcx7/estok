<?php
session_start();
if (!isset($_SESSION['login'])) header("Location: ../auth/login.php");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard</title>
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
                        <!-- home icon -->
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-10.5Z" fill="currentColor"/></svg>
                    </span>
                    <span>Beranda</span>
                </a>
                <a class="nav-item" href="riwayat.php">
                    <span class="icon" aria-hidden="true">
                        <!-- bookmark icon -->
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1Z" fill="currentColor"/></svg>
                    </span>
                    <span>Riwayat</span>
                </a>
                <a class="nav-item" href="kelola.php">
                    <span class="icon" aria-hidden="true">
                        <!-- folder icon -->
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 4 12 7h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6Z" fill="currentColor"/></svg>
                    </span>
                    <span>Kelola</span>
                </a>
                <a class="nav-item" href="../auth/logout.php">
                    <span class="icon" aria-hidden="true">
                        <!-- logout icon -->
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8" stroke="currentColor" stroke-width="2"/><path d="M20 12H9m0 0 4-4m-4 4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <span>Keluar</span>
                </a>
            </nav>
        </aside>

        <!-- Main content -->
        <section class="main">
            <header class="topbar">
                <h2 class="page-title">Beranda</h2>
                <div class="user-pill">
                    <span class="greet">Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <span class="avatar" aria-hidden="true"></span>
                </div>
            </header>

            <div class="hero-bg" role="img" aria-label="Rak buku">
                <!-- background via CSS -->
            </div>

            <div class="actions-grid">
                <a class="action-card" href="peminjaman.php">
                    <span>Peminjaman</span>
                </a>
                <a class="action-card" href="pengembalian.php">
                    <span>Pengembalian</span>
                </a>
            </div>
        </section>
    </div>
</body>
</html>