<?php
require_once 'koneksi.php';

$pesan = "";
$tipe_pesan = "";
$id_transaksi_baru = null;

// 1. PROSES TAMBAH BARANG BARU KE KATALOG (tbl_produk)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_produk'])) {
    $nama_produk = trim($_POST['nama_produk']);
    $harga = (float) $_POST['harga'];
    $stok = (int) $_POST['stok'];
    $barcode = trim($_POST['barcode']);

    if (!empty($nama_produk) && $harga > 0 && $stok >= 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_produk (nama_produk, harga, stok, barcode) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_produk, $harga, $stok, $barcode]);
            
            header("Location: transaksi.php?status=sukses_tambah");
            exit();
        } catch (PDOException $e) {
            $pesan = "Gagal menambah barang: " . $e->getMessage();
            $tipe_pesan = "danger";
        }
    } else {
        $pesan = "Pastikan nama produk, harga (>0), dan stok diisi dengan benar!";
        $tipe_pesan = "warning";
    }
}

// 2. PROSES EDIT BARANG KATALOG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_produk'])) {
    $id_produk = (int) $_POST['id_produk'];
    $nama_produk = trim($_POST['nama_produk']);
    $harga = (float) $_POST['harga'];
    $stok = (int) $_POST['stok'];
    $barcode = trim($_POST['barcode']);

    if ($id_produk > 0 && !empty($nama_produk) && $harga > 0 && $stok >= 0) {
        try {
            $stmt = $pdo->prepare("UPDATE tbl_produk SET nama_produk = ?, harga = ?, stok = ?, barcode = ? WHERE id_produk = ?");
            $stmt->execute([$nama_produk, $harga, $stok, $barcode, $id_produk]);
            
            header("Location: transaksi.php?status=sukses_edit");
            exit();
        } catch (PDOException $e) {
            $pesan = "Gagal memperbarui barang: " . $e->getMessage();
            $tipe_pesan = "danger";
        }
    } else {
        $pesan = "Data edit tidak valid!";
        $tipe_pesan = "warning";
    }
}

// 3. PROSES HAPUS BARANG KATALOG
if (isset($_GET['hapus_id'])) {
    $id_hapus = (int) $_GET['hapus_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tbl_produk WHERE id_produk = ?");
        $stmt->execute([$id_hapus]);
        header("Location: transaksi.php?status=sukses_hapus");
        exit();
    } catch (PDOException $e) {
        $pesan = "Gagal menghapus produk karena sudah terikat dengan riwayat transaksi!";
        $tipe_pesan = "danger";
    }
}

// 4. PROSES PEMBAYARAN KASIR DENGAN TCL (COMMIT & ROLLBACK)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_transaksi'])) {
    $id_pelanggan = $_POST['id_pelanggan'];
    $total_belanja = (float) $_POST['total_belanja_hidden'];
    $tunai = (float) $_POST['tunai'];
    $items = json_decode($_POST['keranjang_json'], true);
    $tanggal = date('Y-m-d');

    if (empty($items)) {
        $pesan = "Keranjang belanja masih kosong! Silakan pilih barang terlebih dahulu.";
        $tipe_pesan = "warning";
    } elseif ($tunai < $total_belanja) {
        $pesan = "Uang tunai pembayaran kurang dari total belanja!";
        $tipe_pesan = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            $stmtTrx = $pdo->prepare("INSERT INTO tbl_transaksi (tanggal, id_pelanggan, total_belanja) VALUES (?, ?, ?)");
            $stmtTrx->execute([$tanggal, $id_pelanggan, $total_belanja]);
            $id_transaksi_baru = $pdo->lastInsertId();

            $stmtDetail = $pdo->prepare("INSERT INTO tbl_detail_transaksi (id_transaksi, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)");
            $stmtStok = $pdo->prepare("UPDATE tbl_produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?");

            foreach ($items as $item) {
                $stmtDetail->execute([
                    $id_transaksi_baru,
                    $item['id_produk'],
                    $item['qty'],
                    $item['subtotal']
                ]);

                $stmtStok->execute([$item['qty'], $item['id_produk'], $item['qty']]);
                if ($stmtStok->rowCount() === 0) {
                    throw new Exception("Stok barang '" . $item['nama_produk'] . "' tidak mencukupi!");
                }
            }

            $pdo->commit();
            $pesan = "Transaksi #$id_transaksi_baru berhasil diproses & di-COMMIT!";
            $tipe_pesan = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $id_transaksi_baru = null;
            $pesan = "Transaksi dibatalkan (ROLLBACK): " . $e->getMessage();
            $tipe_pesan = "danger";
        }
    }
}

// Ambil Data Pelanggan & Daftar Barang
try {
    $stmtPelanggan = $pdo->query("CALL TampilPelanggan()");
    $pelangganList = $stmtPelanggan->fetchAll(PDO::FETCH_ASSOC);
    $stmtPelanggan->closeCursor();

    $stmtProduk = $pdo->query("SELECT * FROM tbl_produk ORDER BY nama_produk ASC");
    $produkList = $stmtProduk->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pelangganList = [];
    $produkList = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir & Transaksi - TokoKita</title>
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
        .card-kasir { background-color: var(--bg-subtle); border: 1px solid #cbd5e1; border-radius: 16px; }
        .btn-accent { background-color: var(--accent-color); color: #ffffff; font-weight: 600; border-radius: 50px; border: none; }
        .btn-accent:hover { background-color: #0369a1; color: #ffffff; }
        .produk-item { border: 1px solid #e2e8f0; border-radius: 12px; transition: all 0.2s; }
        .produk-item:hover { border-color: var(--accent-color); box-shadow: 0 4px 10px rgba(2, 132, 199, 0.12); }
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
                    <li class="nav-item"><a class="nav-link text-muted" href="pelanggan.php">Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link active" href="transaksi.php">Kasir / Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link text-muted" href="laporan.php">Laporan</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h2 class="fw-bold mb-1 fs-3">Kasir & Katalog Barang</h2>
                <p class="text-muted small mb-0">Pilih barang untuk transaksi, atau kelola data produk (Tambah, Edit, Hapus).</p>
            </div>
            <button type="button" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahBarang">
                + Tambah Barang Baru
            </button>
        </div>

        <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe_pesan === 'success' ? 'info' : $tipe_pesan ?> border d-flex flex-wrap justify-content-between align-items-center shadow-sm mb-4">
                <span><?= $pesan ?></span>
                <?php if ($id_transaksi_baru): ?>
                    <a href="cetak_nota.php?id=<?= $id_transaksi_baru ?>&tunai=<?= urlencode($_POST['tunai']) ?>" target="_blank" class="btn btn-dark btn-sm rounded-pill px-4 fw-bold">
                        🖨️ Cetak Nota (Struk Thermal 58mm + Barcode)
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- KOLOM KIRI: DAFTAR KATALOG BARANG -->
            <div class="col-lg-7">
                <div class="card-custom p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">📦 Daftar Barang Tersedia</h5>
                        <input type="text" id="cariBarang" class="form-control form-control-sm w-50" placeholder="Cari nama barang / barcode...">
                    </div>
                    <div class="row g-3" id="daftarProdukGrid">
                        <?php foreach ($produkList as $prod): ?>
                            <div class="col-md-6 item-card-wrapper" data-nama="<?= strtolower(htmlspecialchars($prod['nama_produk'] . ' ' . $prod['barcode'])) ?>">
                                <div class="produk-item bg-white p-3 h-100 d-flex flex-column justify-content-between">
                                    <div style="cursor: pointer;" onclick="tambahKeKeranjang(<?= $prod['id_produk'] ?>, '<?= htmlspecialchars(addslashes($prod['nama_produk'])) ?>', <?= $prod['harga'] ?>, <?= $prod['stok'] ?>)">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($prod['nama_produk']) ?></h6>
                                            <span class="badge <?= $prod['stok'] > 0 ? 'bg-light text-dark border' : 'bg-danger text-white' ?>">
                                                Stok: <?= $prod['stok'] ?>
                                            </span>
                                        </div>

                                        <!-- Render Gambar Barcode Produk -->
                                        <?php if (!empty($prod['barcode'])): ?>
                                            <div class="my-1 py-1 bg-light rounded text-center border-0">
                                                <img src="https://barcode.tec-it.com/barcode.ashx?data=<?= urlencode($prod['barcode']) ?>&code=Code128&dpi=96&datamode=Default" alt="Barcode <?= $prod['barcode'] ?>" style="height: 24px; max-width: 100%;">
                                                <div style="font-size: 7.5px; font-family: monospace; color: #555;"><?= htmlspecialchars($prod['barcode']) ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="fw-bold mt-1" style="color: #0284c7;">
                                            Rp <?= number_format($prod['harga'], 0, ',', '.') ?>
                                        </div>
                                    </div>

                                    <!-- Tombol Aksi Edit & Hapus Produk -->
                                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                        <span class="small text-muted" style="cursor: pointer;" onclick="tambahKeKeranjang(<?= $prod['id_produk'] ?>, '<?= htmlspecialchars(addslashes($prod['nama_produk'])) ?>', <?= $prod['harga'] ?>, <?= $prod['stok'] ?>)">+ Klik beli</span>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="bukaModalEdit(<?= $prod['id_produk'] ?>, '<?= htmlspecialchars(addslashes($prod['nama_produk'])) ?>', <?= $prod['harga'] ?>, <?= $prod['stok'] ?>, '<?= htmlspecialchars($prod['barcode'] ?? '') ?>')">Edit</button>
                                            <a href="transaksi.php?hapus_id=<?= $prod['id_produk'] ?>" class="btn btn-outline-danger py-0 px-2" onclick="return confirm('Yakin ingin menghapus produk <?= htmlspecialchars($prod['nama_produk']) ?>?')">Hapus</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($produkList)): ?>
                            <div class="col-12 text-center py-5 text-muted">Belum ada barang di katalog.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: KERANJANG & PEMBAYARAN KASIR -->
            <div class="col-lg-5">
                <div class="card-kasir p-4">
                    <h5 class="fw-bold mb-3" style="color: #0284c7;">🧾 Keranjang & Pembayaran</h5>
                    <form action="" method="POST" id="formKasir">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Pelanggan</label>
                            <select class="form-select bg-white" name="id_pelanggan" required>
                                <?php foreach ($pelangganList as $p): ?>
                                    <option value="<?= $p['id_pelanggan'] ?>"><?= htmlspecialchars($p['nama_pelanggan']) ?> (<?= htmlspecialchars($p['kota']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tabel Keranjang Otomatis dengan Tombol Tambah/Kurang Qty -->
                        <div class="bg-white rounded-3 border p-2 mb-3" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Barang</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="tabelKeranjang">
                                    <tr><td colspan="4" class="text-center text-muted py-3 small">Belum ada barang dipilih</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-white rounded-3 border p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small fw-semibold">TOTAL BELANJA</span>
                                <span class="fs-4 fw-bold text-dark" id="tampilTotal">Rp 0</span>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted fw-semibold mb-1">Uang Tunai Diterima (Rp)</label>
                                <input type="number" class="form-control fw-bold" name="tunai" id="inputTunai" required placeholder="Masukkan nominal bayar" oninput="hitungKembalian()">
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="text-muted small fw-semibold">KEMBALIAN</span>
                                <span class="fw-bold text-success" id="tampilKembalian">Rp 0</span>
                            </div>
                        </div>

                        <input type="hidden" name="total_belanja_hidden" id="totalBelanjaHidden" value="0">
                        <input type="hidden" name="keranjang_json" id="keranjangJson" value="[]">

                        <button type="submit" name="proses_transaksi" class="btn btn-accent w-100 py-3 shadow-sm fw-bold">
                            Bayar & Simpan Transaksi (COMMIT)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL TAMBAH BARANG BARU -->
    <div class="modal fade" id="modalTambahBarang" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">➕ Tambah Barang Baru ke Katalog</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Barcode / SKU (Scan atau Ketik)</label>
                            <input type="text" class="form-control font-monospace" name="barcode" id="inputBarcodeScan" placeholder="Klik lalu scan barcode pada produk..." autocomplete="off" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Barang</label>
                            <input type="text" class="form-control" name="nama_produk" required placeholder="Contoh: Kopi Susu Botol 250ml">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Harga Satuan (Rp)</label>
                            <input type="number" class="form-control" name="harga" min="100" required placeholder="Contoh: 12000">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Stok Awal</label>
                            <input type="number" class="form-control" name="stok" min="1" value="20" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_produk" class="btn btn-accent px-4">Simpan Barang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT BARANG -->
    <div class="modal fade" id="modalEditBarang" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">✏️ Edit Data Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_produk" id="editIdProduk">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Barcode / SKU</label>
                            <input type="text" class="form-control font-monospace" name="barcode" id="editBarcode" autocomplete="off" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Barang</label>
                            <input type="text" class="form-control" name="nama_produk" id="editNamaProduk" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Harga Satuan (Rp)</label>
                            <input type="number" class="form-control" name="harga" id="editHarga" min="100" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Stok</label>
                            <input type="number" class="form-control" name="stok" id="editStok" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_produk" class="btn btn-accent px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let keranjang = [];

        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }

        function tambahKeKeranjang(id, nama, harga, stok) {
            if (stok <= 0) {
                alert('Maaf, stok barang "' + nama + '" sedang habis!');
                return;
            }
            const index = keranjang.findIndex(item => item.id_produk === id);
            if (index > -1) {
                if (keranjang[index].qty + 1 > stok) {
                    alert('Jumlah melebihi stok tersedia (' + stok + ')!');
                    return;
                }
                keranjang[index].qty += 1;
                keranjang[index].subtotal = keranjang[index].qty * harga;
            } else {
                keranjang.push({
                    id_produk: id,
                    nama_produk: nama,
                    harga: harga,
                    stok: stok,
                    qty: 1,
                    subtotal: harga
                });
            }
            renderKeranjang();
        }

        // Fungsi mengubah kuantitas (+ atau -) langsung di keranjang
        function ubahQty(index, delta) {
            const item = keranjang[index];
            const newQty = item.qty + delta;

            if (newQty <= 0) {
                hapusItem(index);
            } else {
                if (delta > 0 && newQty > item.stok) {
                    alert('Jumlah melebihi stok tersedia (' + item.stok + ')!');
                    return;
                }
                item.qty = newQty;
                item.subtotal = item.qty * item.harga;
                renderKeranjang();
            }
        }

        function hapusItem(index) {
            keranjang.splice(index, 1);
            renderKeranjang();
        }

        function renderKeranjang() {
            const tbody = document.getElementById('tabelKeranjang');
            let total = 0;

            if (keranjang.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3 small">Belum ada barang dipilih</td></tr>';
            } else {
                tbody.innerHTML = '';
                keranjang.forEach((item, idx) => {
                    total += item.subtotal;
                    tbody.innerHTML += `
                        <tr>
                            <td class="small fw-semibold">${item.nama_produk}<br><span class="text-muted fw-normal">${formatRupiah(item.harga)}</span></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm align-items-center">
                                    <button type="button" class="btn btn-outline-secondary px-1 py-0" onclick="ubahQty(${idx}, -1)">-</button>
                                    <span class="px-2 fw-bold small">${item.qty}</span>
                                    <button type="button" class="btn btn-outline-secondary px-1 py-0" onclick="ubahQty(${idx}, 1)">+</button>
                                </div>
                            </td>
                            <td class="text-end small fw-bold">${formatRupiah(item.subtotal)}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm text-danger p-0 fw-bold" onclick="hapusItem(${idx})" title="Hapus item">×</button></td>
                        </tr>
                    `;
                });
            }

            document.getElementById('tampilTotal').textContent = formatRupiah(total);
            document.getElementById('totalBelanjaHidden').value = total;
            document.getElementById('keranjangJson').value = JSON.stringify(keranjang);
            hitungKembalian();
        }

        function hitungKembalian() {
            const total = parseFloat(document.getElementById('totalBelanjaHidden').value) || 0;
            const tunai = parseFloat(document.getElementById('inputTunai').value) || 0;
            const kembali = tunai - total;
            const el = document.getElementById('tampilKembalian');
            el.textContent = kembali >= 0 ? formatRupiah(kembali) : 'Kurang ' + formatRupiah(Math.abs(kembali));
            el.className = kembali >= 0 ? 'fw-bold text-success' : 'fw-bold text-danger';
        }

        // Otomatis fokus ke kolom barcode saat modal Tambah Barang dibuka
        const modalTambah = document.getElementById('modalTambahBarang');
        modalTambah.addEventListener('shown.bs.modal', function () {
            document.getElementById('inputBarcodeScan').focus();
        });

        // Fungsi memicu Modal Edit Produk
        function bukaModalEdit(id, nama, harga, stok, barcode) {
            document.getElementById('editIdProduk').value = id;
            document.getElementById('editNamaProduk').value = nama;
            document.getElementById('editHarga').value = harga;
            document.getElementById('editStok').value = stok;
            document.getElementById('editBarcode').value = barcode;
            
            const modalEdit = new bootstrap.Modal(document.getElementById('modalEditBarang'));
            modalEdit.show();
        }

        // Pencarian barang instan berdasarkan nama atau barcode
        document.getElementById('cariBarang').addEventListener('input', function() {
            const kata = this.value.toLowerCase();
            document.querySelectorAll('.item-card-wrapper').forEach(el => {
                el.style.display = el.getAttribute('data-nama').includes(kata) ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>