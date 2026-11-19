<?php
require_once 'cek_akses.php';
wajibLogin(['admin', 'kasir']);
require_once 'koneksi.php';

try {
    $stmtPelanggan = $pdo->query("SELECT COUNT(*) FROM tbl_pelanggan");
    $totalPelanggan = $stmtPelanggan->fetchColumn();

    $stmtTransaksi = $pdo->query("SELECT COUNT(*) FROM tbl_transaksi");
    $totalTransaksi = $stmtTransaksi->fetchColumn();

    $stmtOmzet = $pdo->query("SELECT SUM(total_belanja) FROM tbl_transaksi");
    $totalOmzet = $stmtOmzet->fetchColumn() ?: 0;

    $stmtRiwayat = $pdo->query("SELECT * FROM v_laporan_penjualan ORDER BY id_transaksi DESC LIMIT 5");
    $riwayatTransaksi = $stmtRiwayat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Gagal mengambil data database: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Informasi TokoKita</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #f8fafc;       /* Warna 1: Background Putih/Abu Sangat Terang */
            --bg-card: #ffffff;      /* Warna 2: Pure White untuk Card & Tabel */
            --text-main: #0f172a;    /* Warna 3: Dark Slate untuk Teks Utama & Kontras Tinggi */
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
            color: #0284c7 !important;
        }

        .card-custom {
            background-color: var(--bg-card);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .card-custom:hover {
            border-color: #cbd5e1;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
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

        .btn-primary-custom {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 600;
            border-radius: 50px;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-primary-custom:hover {
            background-color: #1e293b;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
        }

        .text-muted-custom {
            color: #64748b !important;
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
                <span>Toko<span class="text-primary">Kita</span> <span class="text-muted-custom fs-7 fw-normal">PRO</span></span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-4">
                    <li class="nav-item"><a class="nav-link text-muted-custom" href="index.html">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link text-muted-custom" href="pelanggan.php">Pelanggan</a></li>
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
                <h2 class="fw-bold mb-1 fs-3">Dashboard Analisis</h2>
                <p class="text-muted-custom small mb-0">Ringkasan statistik operasional database TokoKita secara real-time.</p>
            </div>
            <div class="col-auto">
                <a href="transaksi.php" class="btn btn-primary-custom px-4 py-2 small">+ Buat Transaksi</a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger border border-danger text-dark mb-4" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Cards Statistik Utama (Maksimal 3 Warna: Base Terang, Card Putih, Teks Gelap Kontras) -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card-custom p-4 h-100">
                    <div class="text-muted-custom small fw-semibold mb-2">TOTAL PELANGGAN</div>
                    <h3 class="display-6 fw-bold mb-1 text-primary"><?= number_format($totalPelanggan) ?></h3>
                    <span class="text-muted-custom small">Terdaftar aktif dalam database</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-custom p-4 h-100">
                    <div class="text-muted-custom small fw-semibold mb-2">TOTAL TRANSAKSI</div>
                    <h3 class="display-6 fw-bold mb-1 text-success"><?= number_format($totalTransaksi) ?></h3>
                    <span class="text-muted-custom small">Transaksi kasir terekam</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-custom p-4 h-100">
                    <div class="text-muted-custom small fw-semibold mb-2">AKUMULASI OMZET</div>
                    <h3 class="display-6 fw-bold mb-1 text-dark">Rp <?= number_format($totalOmzet, 0, ',', '.') ?></h3>
                    <span class="text-muted-custom small">Total nilai transaksi keseluruhan</span>
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Transaksi Terbaru -->
        <div class="card-custom overflow-hidden">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white">
                <h5 class="fw-bold mb-0 fs-5">5 Transaksi Terbaru (Database View)</h5>
                <a href="laporan.php" class="text-primary small text-decoration-none fw-semibold">Lihat Semua Laporan →</a>
            </div>
            <div class="table-responsive m-0">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4">ID Transaksi</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3">Nama Pelanggan</th>
                            <th class="py-3">Kota</th>
                            <th class="py-3 text-end px-4">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($riwayatTransaksi)): ?>
                            <?php foreach ($riwayatTransaksi as $row): ?>
                                <tr>
                                    <td class="px-4 font-monospace text-muted-custom">#<?= $row['id_transaksi'] ?></td>
                                    <td><?= $row['tanggal'] ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                                    <td><span class="badge bg-light border text-dark fw-normal px-2 py-1"><?= htmlspecialchars($row['kota']) ?></span></td>
                                    <td class="text-end px-4 fw-bold text-success">Rp <?= number_format($row['total_belanja'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted-custom">Belum ada data transaksi yang tercatat.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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