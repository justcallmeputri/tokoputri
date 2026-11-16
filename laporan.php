<?php
require_once 'koneksi.php';

try {
    // Mengambil data laporan menggunakan Database View (Bab 1)
    $stmt = $pdo->query("SELECT * FROM v_laporan_penjualan ORDER BY id_transaksi DESC");
    $laporanList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mengambil total keseluruhan omzet dari view
    $stmtTotal = $pdo->query("SELECT SUM(total_belanja) as total_semua FROM v_laporan_penjualan");
    $grandTotal = $stmtTotal->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $laporanList = [];
    $grandTotal = 0;
    $error_db = "Gagal memuat data laporan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Sistem Informasi TokoKita</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #f8fafc;       /* Background Putih/Abu Sangat Terang */
            --bg-card: #ffffff;      /* Pure White untuk Card & Tabel */
            --accent-color: #0284c7; /* Aksen Biru Cerah */
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
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

        @media print {
            .no-print { display: none !important; }
            body { background-color: #ffffff; }
            .card-custom { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>

    <!-- Navbar Light Mode -->
    <nav class="navbar navbar-expand-lg sticky-top py-3 shadow-sm no-print">
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
                    <li class="nav-item"><a class="nav-link text-muted-custom" href="pelanggan.php">Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link text-muted-custom" href="transaksi.php">Kasir / Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link active" href="laporan.php">Laporan</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container my-5">
        
        <!-- Header Page -->
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h2 class="fw-bold mb-1 fs-3">📈 Laporan Penjualan</h2>
                <p class="text-muted-custom small mb-0">Rekapitulasi transaksi keseluruhan.</p>
            </div>
            <div class="col-auto no-print">
                <button onclick="window.print()" class="btn btn-accent px-4 py-2 small">
                    🖨️ Cetak / Simpan Laporan PDF
                </button>
            </div>
        </div>

        <?php if (isset($error_db)): ?>
            <div class="alert alert-danger border border-danger text-dark mb-4 shadow-sm" role="alert">
                <?= $error_db ?>
            </div>
        <?php endif; ?>

        <!-- Card Total Omzet Ringkasan -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card-custom p-4">
                    <div class="text-muted-custom small fw-semibold mb-1">TOTAL KESELURUHAN OMZET</div>
                    <h3 class="fw-bold text-accent mb-0">Rp <?= number_format($grandTotal, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>

        <!-- Tabel Rekapitulasi Laporan -->
        <div class="card-custom overflow-hidden">
            <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 fs-5">Rincian Riwayat Transaksi</h5>
                <span class="badge bg-light border text-dark fw-normal px-3 py-2">Total Data: <?= count($laporanList) ?> Transaksi</span>
            </div>
            <div class="table-responsive m-0">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4">ID Transaksi</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3">Nama Pelanggan</th>
                            <th class="py-3">Kota Asal</th>
                            <th class="py-3 text-end px-4">Total Belanja</th>
                            <th class="py-3 text-center no-print">Aksi Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($laporanList)): ?>
                            <?php foreach ($laporanList as $row): ?>
                                <tr>
                                    <td class="px-4 font-monospace text-accent fw-bold">#<?= $row['id_transaksi'] ?></td>
                                    <td><?= $row['tanggal'] ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                                    <td><span class="badge bg-light border text-dark fw-normal px-2 py-1"><?= htmlspecialchars($row['kota']) ?></span></td>
                                    <td class="text-end px-4 fw-bold text-success">Rp <?= number_format($row['total_belanja'], 0, ',', '.') ?></td>
                                    <td class="text-center no-print px-4">
                                        <a href="cetak_nota.php?id=<?= $row['id_transaksi'] ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-semibold">
                                            Nota
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted-custom">Belum ada data laporan transaksi yang tercatat.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Footer Light Mode -->
    <footer class="text-center py-4 mt-5 no-print">
        <div class="container">
            <p class="mb-1 small fw-semibold">TokoKita Enterprise Advanced Database System</p>
            <p class="mb-0 text-muted-custom small">© 2026 MariaDB / MySQL Project. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>