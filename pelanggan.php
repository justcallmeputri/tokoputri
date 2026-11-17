<?php
require_once 'koneksi.php';

$pesan = "";
$tipe_pesan = "";

// 1. PROSES TAMBAH PELANGGAN BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pelanggan'])) {
    $nama_pelanggan = trim($_POST['nama_pelanggan']);
    $kota = trim($_POST['kota']);

    if (!empty($nama_pelanggan) && !empty($kota)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_pelanggan (nama_pelanggan, kota) VALUES (?, ?)");
            $stmt->execute([$nama_pelanggan, $kota]);
            header("Location: pelanggan.php?status=sukses_tambah");
            exit();
        } catch (PDOException $e) {
            $pesan = "Gagal menambah pelanggan: " . $e->getMessage();
            $tipe_pesan = "danger";
        }
    } else {
        $pesan = "Nama lengkap dan kota asal wajib diisi!";
        $tipe_pesan = "warning";
    }
}

// 2. PROSES EDIT PELANGGAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_pelanggan'])) {
    $id_pelanggan = (int) $_POST['id_pelanggan'];
    $nama_pelanggan = trim($_POST['nama_pelanggan']);
    $kota = trim($_POST['kota']);

    if ($id_pelanggan > 0 && !empty($nama_pelanggan) && !empty($kota)) {
        try {
            $stmt = $pdo->prepare("UPDATE tbl_pelanggan SET nama_pelanggan = ?, kota = ? WHERE id_pelanggan = ?");
            $stmt->execute([$nama_pelanggan, $kota, $id_pelanggan]);
            header("Location: pelanggan.php?status=sukses_edit");
            exit();
        } catch (PDOException $e) {
            $pesan = "Gagal memperbarui pelanggan: " . $e->getMessage();
            $tipe_pesan = "danger";
        }
    } else {
        $pesan = "Data edit pelanggan tidak valid!";
        $tipe_pesan = "warning";
    }
}

// 3. PROSES HAPUS PELANGGAN
if (isset($_GET['hapus_id'])) {
    $id_hapus = (int) $_GET['hapus_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tbl_pelanggan WHERE id_pelanggan = ?");
        $stmt->execute([$id_hapus]);
        header("Location: pelanggan.php?status=sukses_hapus");
        exit();
    } catch (PDOException $e) {
        $pesan = "Gagal menghapus pelanggan karena masih memiliki riwayat transaksi!";
        $tipe_pesan = "danger";
    }
}

// Ambil Daftar Pelanggan menggunakan Stored Procedure
try {
    $stmtPelanggan = $pdo->query("CALL TampilPelanggan()");
    $pelangganList = $stmtPelanggan->fetchAll(PDO::FETCH_ASSOC);
    $stmtPelanggan->closeCursor();
} catch (PDOException $e) {
    $pelangganList = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan - TokoKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #f8fafc;
            --bg-card: #ffffff;
            --bg-subtle: #f1f5f9;
            --accent-color: #0284c7;
            --text-main: #0f172a;
        }
        body { background-color: var(--bg-base); font-family: 'Inter', sans-serif; color: var(--text-main); }
        .navbar { background-color: #ffffff !important; border-bottom: 1px solid #e2e8f0 !important; }
        .navbar-brand, .navbar .nav-link { color: var(--text-main) !important; }
        .navbar .nav-link.active { font-weight: 700; color: var(--accent-color) !important; }
        .card-custom { background-color: var(--bg-card); border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .btn-accent { background-color: var(--accent-color); color: #ffffff; font-weight: 600; border-radius: 50px; border: none; }
        .btn-accent:hover { background-color: #0369a1; color: #ffffff; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top py-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.html">
                <span class="px-2 py-1 bg-dark text-white rounded-2 fs-6 fw-extrabold">TK</span> 
                <span>Toko<span style="color: #0284c7;">Kita</span> <span class="text-muted fs-7 fw-normal">PRO</span></span>
            </a>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-4">
                    <li class="nav-item"><a class="nav-link text-muted" href="index.html">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link text-muted" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="pelanggan.php">Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link text-muted" href="transaksi.php">Kasir / Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link text-muted" href="laporan.php">Laporan</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="mb-4">
            <h2 class="fw-bold mb-1 fs-3">Manajemen Pelanggan</h2>
            <p class="text-muted small mb-0">Implementasi Stored Procedure (Bab 3) & Trigger Log (Bab 5).</p>
        </div>

        <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe_pesan ?> border shadow-sm mb-4">
                <?= $pesan ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- KOLOM KIRI: FORM TAMBAH PELANGGAN -->
            <div class="col-lg-4">
                <div class="card-custom p-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: #0284c7;">
                        <span>➕</span> Tambah Pelanggan Baru
                    </h5>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_pelanggan" required placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Kota Asal</label>
                            <input type="text" class="form-control" name="kota" required placeholder="Contoh: Surabaya">
                        </div>
                        <button type="submit" name="tambah_pelanggan" class="btn btn-accent w-100 py-2 shadow-sm">
                            Simpan Pelanggan
                        </button>
                    </form>
                </div>
            </div>

            <!-- KOLOM KANAN: TABEL DAFTAR PELANGGAN -->
            <div class="col-lg-8">
                <div class="card-custom p-4">
                    <h5 class="fw-bold mb-3">Daftar Seluruh Pelanggan</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>ID</th>
                                    <th>NAMA PELANGGAN</th>
                                    <th>KOTA</th>
                                    <th class="text-end">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pelangganList as $p): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?= $p['id_pelanggan'] ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($p['nama_pelanggan']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['kota']) ?></span></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary px-2 py-1" onclick="bukaModalEdit(<?= $p['id_pelanggan'] ?>, '<?= htmlspecialchars(addslashes($p['nama_pelanggan'])) ?>', '<?= htmlspecialchars(addslashes($p['kota'])) ?>')">Edit</button>
                                                <a href="pelanggan.php?hapus_id=<?= $p['id_pelanggan'] ?>" class="btn btn-outline-danger px-2 py-1" onclick="return confirm('Yakin ingin menghapus pelanggan <?= htmlspecialchars($p['nama_pelanggan']) ?>?')">Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($pelangganList)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Belum ada data pelanggan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL EDIT PELANGGAN -->
    <div class="modal fade" id="modalEditPelanggan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">✏️ Edit Data Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_pelanggan" id="editIdPelanggan">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_pelanggan" id="editNamaPelanggan" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Kota Asal</label>
                            <input type="text" class="form-control" name="kota" id="editKota" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_pelanggan" class="btn btn-accent px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function bukaModalEdit(id, nama, kota) {
            document.getElementById('editIdPelanggan').value = id;
            document.getElementById('editNamaPelanggan').value = nama;
            document.getElementById('editKota').value = kota;

            const modalEdit = new bootstrap.Modal(document.getElementById('modalEditPelanggan'));
            modalEdit.show();
        }
    </script>
</body>
</html>