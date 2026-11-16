<?php
require_once 'koneksi.php';

$pesan = "";
$tipe_pesan = "";

// Proses jika form tambah pelanggan disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pelanggan'])) {
    $nama = trim($_POST['nama_pelanggan']);
    $kota = trim($_POST['kota']);

    if (!empty($nama) && !empty($kota)) {
        try {
            $stmt = $pdo->prepare("CALL TambahPelanggan(?, ?)");
            $stmt->execute([$nama, $kota]);
            $stmt->closeCursor();

            $pesan = "Pelanggan baru berhasil ditambahkan melalui Stored Procedure!";
            $tipe_pesan = "success";
        } catch (PDOException $e) {
            $pesan = "Gagal menambah pelanggan: " . $e->getMessage();
            $tipe_pesan = "danger";
        }
    } else {
        $pesan = "Nama dan Kota pelanggan tidak boleh kosong!";
        $tipe_pesan = "warning";
    }
}

try {
    $stmt = $pdo->query("CALL TampilPelanggan()");
    $pelangganList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    $pelangganList = [];
    $error_db = "Gagal memuat data pelanggan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan - Sistem Informasi TokoKita</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #f8fafc;       /* Warna 1: Background Putih/Abu Sangat Terang */
            --bg-card: #ffffff;      /* Warna 2: Pure White untuk Card & Tabel */
            --accent-color: #0284c7; /* Warna 3: Aksen Biru Cerah (Selaras dengan Beranda) */
            --text-main: #0f172a;    /* Teks Utama Kontras Tinggi */
        }

        body {
            background-color: var(--bg-base);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
        }

        .navbar {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .navbar-brand, .navbar .nav-link {
            color: var(--text-main) !important;
        }

        .navbar .nav-link.active {
            font-weight: 700;
            color: var(--accent-color) !important;
        }

        .card-custom {
            background-color: var(--bg-card);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }

        .table-custom {
            color: var(--text-main);
            background-color: transparent;
        }

        .table-custom th {
            background-color: #f1f5f9;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .table-custom td {
            background-color: var(--bg-card);
            border-bottom: 1px solid #e2e8f0;
            color: var(--text-main);
            vertical-align: middle;
        }

        .btn-accent {
            background-color: var(--accent-color);
            color: #ffffff;
            font-weight: 600;
            border-radius: 50px;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-accent:hover {
            background-color: #0369a1;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }

        .text-muted-custom {
            color: #64748b !important;
        }

        .text-accent {
            color: var(--accent-color) !important;
        }

        footer {
            background-color: #ffffff !important;
            border-top: 1px solid #e2e8f0 !important;
        }
    </style>
</head>
<body>

    <!-- Navbar Light Mode -->
    <nav class="navbar navbar-expand-lg sticky-top py-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.html">
                <span class="px-2 py-1 bg-dark text-white rounded-2 fs-6 fw-extrabold">TK</span> 
                <span>Toko<span class="text-accent">Kita</span> <span class="text-muted-custom fs-7 fw-normal">PRO</span></span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-4">
                    <li class="nav-item"><a class="nav-link text-muted-custom" href="index.html">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link text-muted-custom" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="pelanggan.php">Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link text-muted-custom" href="transaksi.php">Kasir / Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link text-muted-custom" href="laporan.php">Laporan</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container my-5">
        
        <!-- Header Page -->
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h2 class="fw-bold mb-1 fs-3">Manajemen Pelanggan</h2>
                <p class="text-muted-custom small mb-0">Implementasi Stored Procedure (Bab 3) & Trigger Log (Bab 5).</p>
            </div>
        </div>

        <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe_pesan === 'success' ? 'info' : $tipe_pesan ?> border border-secondary text-dark mb-4 shadow-sm" role="alert">
                <?= $pesan ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_db)): ?>
            <div class="alert alert-danger border border-danger text-dark mb-4 shadow-sm" role="alert">
                <?= $error_db ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Form Tambah Pelanggan (Stored Procedure) -->
            <div class="col-lg-4">
                <div class="card-custom p-4">
                    <h5 class="fw-bold mb-3 text-accent">➕ Tambah Pelanggan Baru</h5>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="nama_pelanggan" class="form-label text-muted-custom small">Nama Lengkap</label>
                            <input type="text" class="form-control bg-light text-dark border" id="nama_pelanggan" name="nama_pelanggan" required placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="mb-4">
                            <label for="kota" class="form-label text-muted-custom small">Kota Asal</label>
                            <input type="text" class="form-control bg-light text-dark border" id="kota" name="kota" required placeholder="Contoh: Surabaya">
                        </div>
                        <button type="submit" name="tambah_pelanggan" class="btn btn-accent w-100 py-2 shadow-sm">
                            Simpan Pelanggan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tabel Data Pelanggan -->
            <div class="col-lg-8">
                <div class="card-custom overflow-hidden">
                    <div class="p-4 border-bottom bg-white">
                        <h5 class="fw-bold mb-0 fs-5">Daftar Seluruh Pelanggan</h5>
                    </div>
                    <div class="table-responsive m-0">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4">ID</th>
                                    <th class="py-3">Nama Pelanggan</th>
                                    <th class="py-3 px-4">Kota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pelangganList)): ?>
                                    <?php foreach ($pelangganList as $p): ?>
                                        <tr>
                                            <td class="px-4 font-monospace text-accent fw-bold">#<?= $p['id_pelanggan'] ?></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($p['nama_pelanggan']) ?></td>
                                            <td class="px-4"><span class="badge bg-light border text-dark fw-normal px-2 py-1"><?= htmlspecialchars($p['kota']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted-custom">Belum ada data pelanggan yang tersimpan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer Light Mode -->
    <footer class="text-center py-4 mt-5">
        <div class="container">
            <p class="mb-1 small fw-semibold">TokoKita Enterprise Advanced Database System</p>
            <p class="mb-0 text-muted-custom small">© 2026 MariaDB / MySQL Project. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>