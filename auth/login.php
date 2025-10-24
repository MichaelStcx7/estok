<?php
session_start();
include("../config/db.php");

$msg = "";
$remember_me_duration = time() + (7 * 24 * 60 * 60); // durasi 7 hari

// ngecek cookie untuk otomatis login
if (!isset($_SESSION['login']) && isset($_COOKIE['user_remember'])) {
    $username_from_cookie = $_COOKIE['user_remember'];

    // ambil data user dari DB 
    $stmt_cookie = $koneksi->prepare("SELECT username FROM user WHERE username = ? LIMIT 1");
    $stmt_cookie->bind_param("s", $username_from_cookie);
    $stmt_cookie->execute();
    $result_cookie = $stmt_cookie->get_result();
    $row_cookie = $result_cookie->fetch_assoc();
    $stmt_cookie->close();

    if ($row_cookie) {
        // otomatis login jika cookie valid
        $_SESSION['login'] = true;
        $_SESSION['username'] = $row_cookie['username']; 
        header("Location: ../index.php"); 
        exit;
    } else {
        // hapus cookie jika user tidak ditemukan (mencegah loop)
        setcookie('user_remember', '', time() - 3600, '/'); 
    }
}


if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']); // mengecek apakah 'Remember Me' dicentang

    // memvalidasi username sama password
    $stmt = $koneksi->prepare("SELECT username, password FROM user WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    // ngecek user dan password yang di hash
    if ($row && password_verify($password, $row['password'])) {
        
        // atur session
        $_SESSION['login'] = true;
        $_SESSION['username'] = $row['username']; 
        
        // ngatur cookie jika 'Remember Me' dicentang
        if ($remember) {
            setcookie('user_remember', $row['username'], $remember_me_duration, '/');
        } else {
            // ngehapus cookie lama jika ada dan user tidak mencentang
            setcookie('user_remember', '', time() - 3600, '/');
        }
        
        header("Location: ../index.php"); 
        exit;
    } else {
        $msg = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Staff</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <main class="auth-page">
        <header class="brand">
            <div class="brand-mark">
                <img src="../assets/img/logo2.png" alt="E-Perpus Logo" class="logo-img" />
            </div>
            <div class="brand-text">
                <h1 class="brand-sub">SMKN 1 GIANYAR</h1>
            </div>
        </header>

        <section class="card">
            <div class="card-inner">
                <h2 class="card-title">Masuk</h2>
                <p class="card-subtitle">Masuk untuk mengakses data dan Mengelola Buku Di Perpustakaan SMK Negeri 1 Gianyar</p>

                <form method="POST" class="auth-form" novalidate>
                    <div class="form-field">
                        <label for="username">Nama pengguna</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true">
                                <!-- user icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-icon lucide-user">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                </svg>
                            </span>
                            <input id="username" type="text" name="username" placeholder="Username" required autocomplete="username" />
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="password">Sandi</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true">
                                <!-- key icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round-icon lucide-key-round">
                                    <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>
                                </svg>
                            </span>
                            <input id="password" type="password" name="password" placeholder="********" required autocomplete="current-password" />
                            <button type="button" class="toggle-password" aria-label="Tampilkan sandi" data-target="#password">
                            </button>
                        </div>
                    </div>

                    <div class="form-meta">
                        <label class="checkbox">
                            <input type="checkbox" name="remember" id="remember">
                            <span>Ingat Saya</span>
                        </label>
                    </div>

                    <?php if (!empty($msg)): ?>
                        <div class="alert error" role="alert"><?php echo htmlspecialchars($msg); ?></div>
                    <?php endif; ?>

                    <button type="submit" name="login" class="btn-primary">Masuk</button>
                </form>
            </div>
        </section>
    </main>

    <script>
        // Toggle password visibility
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.toggle-password');
            if (!btn) return;
            const input = document.querySelector(btn.getAttribute('data-target'));
            if (!input) return;
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            btn.setAttribute('aria-label', isPassword ? 'Sembunyikan sandi' : 'Tampilkan sandi');
            btn.classList.toggle('show', isPassword);
        });
    </script>
</body>
</html>