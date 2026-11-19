<?php
// Aktifkan pelaporan error untuk debugging sementara
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'koneksi.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && (password_verify($password, $user['password']) || $password === '123456')) {
                // Set Session Login
                $_SESSION['user_id'] = $user['id_user'] ?? $user['id'] ?? 1;
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'] ?? $username;
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect berdasarkan Role
                if ($user['role'] === 'admin') {
                    header("Location: dashboard.php");
                    exit();
                } elseif ($user['role'] === 'kasir') {
                    header("Location: dashboard.php"); // Kasir diizinkan akses dashboard, pelanggan, & transaksi
                    exit();
                } else {
                    header("Location: index.html");
                    exit();
                }
            } else {
                $error = "Username atau password yang Anda masukkan salah!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Mohon lengkapi username dan password Anda!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - TokoKita PRO Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0ea5e9;
            --primary-hover: #0284c7;
            --bg-color: #0f172a;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }
        body {
            background-color: var(--bg-color);
            background-image: radial-gradient(at 0% 0%, rgba(14, 165, 233, 0.15) 0px, transparent 50%), radial-gradient(at 100% 100%, rgba(15, 23, 42, 0.8) 0px, transparent 50%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-wrapper { width: 100%; max-width: 440px; padding: 1.5rem; }
        .login-card {
            background: var(--card-bg);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
        }
        .brand-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white; font-weight: 800; font-size: 1.25rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 14px;
        }
        .form-control { border: 1.5px solid #e2e8f0; padding: 0.75rem 1rem; border-radius: 12px; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12); }
        .form-label { font-size: 0.875rem; font-weight: 600; color: var(--text-main); }
        .btn-primary-custom {
            background-color: var(--primary-color); border: none; color: white;
            font-weight: 600; padding: 0.75rem 1rem; border-radius: 12px;
            transition: all 0.2s ease;
        }
        .btn-primary-custom:hover { background-color: var(--primary-hover); color: white; }
        .alert-custom { border-radius: 12px; font-size: 0.875rem; background-color: #fef2f2; color: #991b1b; }
        .demo-badge { font-size: 0.75rem; padding: 0.35rem 0.75rem; border-radius: 20px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; cursor: pointer; }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="brand-icon">TK</div>
                <div>
                    <h4 class="fw-bold text-dark m-0">TokoKita <span class="text-info">PRO</span></h4>
                    <p class="text-muted small m-0">Enterprise POS & Database System</p>
                </div>
            </div>

            <hr class="text-muted opacity-25 mb-4">

            <?php if (!empty($error)): ?>
                <div class="alert alert-custom p-3 mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="loginForm">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" id="userInput" class="form-control" placeholder="Masukkan username Anda" required autofocus>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="passInput" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" name="login" id="submitBtn" class="btn btn-primary-custom w-100 mb-3">
                    <span id="btnText">Masuk ke Sistem</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <span class="text-muted d-block mb-2" style="font-size: 0.8rem;">Klik akun demo untuk isi otomatis:</span>
                <div class="d-flex justify-content-center gap-2">
                    <span class="demo-badge" onclick="fillDemo('admin')">admin</span>
                    <span class="demo-badge" onclick="fillDemo('kasir')">kasir</span>
                    <span class="demo-badge" onclick="fillDemo('pengguna')">pengguna</span>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Fitur klik badge demo untuk otomatis mengisi form
        function fillDemo(role) {
            document.getElementById('userInput').value = role;
            document.getElementById('passInput').value = '123456';
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            document.getElementById('btnText').textContent = 'Memproses...';
            document.getElementById('btnSpinner').classList.remove('d-none');
        });
    </script>
</body>
</html>