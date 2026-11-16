<?php
require_once 'koneksi.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$tunai = isset($_GET['tunai']) ? (float) $_GET['tunai'] : 0;

$stmtTrx = $pdo->prepare("
    SELECT t.id_transaksi, t.tanggal, t.total_belanja, p.nama_pelanggan, p.kota 
    FROM tbl_transaksi t
    JOIN tbl_pelanggan p ON t.id_pelanggan = p.id_pelanggan
    WHERE t.id_transaksi = ?
");
$stmtTrx->execute([$id]);
$transaksi = $stmtTrx->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    die("Data transaksi tidak ditemukan.");
}

$stmtDetail = $pdo->prepare("
    SELECT pr.nama_produk, pr.harga, dt.jumlah, dt.subtotal
    FROM tbl_detail_transaksi dt
    JOIN tbl_produk pr ON dt.id_produk = pr.id_produk
    WHERE dt.id_transaksi = ?
");
$stmtDetail->execute([$id]);
$items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

if ($tunai <= 0) {
    $tunai = $transaksi['total_belanja'];
}
$kembalian = $tunai - $transaksi['total_belanja'];

// Format nomor nota untuk teks barcode (Contoh: TRX00027)
$kodeBarangTrx = 'TRX' . str_pad($transaksi['id_transaksi'], 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota #<?= $transaksi['id_transaksi'] ?></title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0mm;
        }

        * {
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace;
        }

        html, body {
            width: 58mm;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 auto !important;
            padding: 2mm 2.5mm !important;
            background: #ffffff;
            color: #000000;
            font-size: 9.5px;
            line-height: 1.2;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        
        .garis {
            border-top: 1px dashed #000000;
            margin: 3px 0;
        }

        .header-logo {
            text-align: center;
            margin-bottom: 2px;
        }
        .header-logo img {
            width: 32px;
            height: auto;
            filter: grayscale(100%) contrast(200%);
        }

        .header-toko h2 {
            margin: 0;
            font-size: 12px;
            letter-spacing: 0.3px;
        }

        .header-toko p {
            margin: 1px 0;
            font-size: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            padding: 1px 0;
        }

        /* Styling khusus untuk area barcode agar rapi di kertas 58mm */
        .barcode-container {
            text-align: center;
            margin: 6px 0 2px 0;
        }
        .barcode-container img {
            max-width: 100%;
            height: 35px;
        }

        .no-print {
            display: none !important;
        }

        @media print {
            .no-print { display: none !important; }
            html, body {
                width: 58mm;
                height: auto !important;
                margin: 0 !important;
                padding: 1mm 2mm !important;
                overflow: visible !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Gambar / Logo Toko di atas nota -->
    <div class="header-logo">
        <img src="assets/img/tokoputri.png" style="width: 120px; height: auto;" alt="Logo TokoPutri" />
    </div>

    <!-- Header Nota -->
    <div class="center header-toko">
        <h2>TOKOPUTRI RETAIL</h2>
        <p>JL. RAYA PONOROGO NO. 88, JATIM</p>
        <p>NPWP: 01.234.567.8-901.000</p>
    </div>

    <div class="garis"></div>

    <!-- Informasi Transaksi -->
    <table>
        <tr>
            <td>No. Nota</td>
            <td class="right">: <?= $kodeBarangTrx ?></td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td class="right">: <?= date('d/m/Y H:i', strtotime($transaksi['tanggal'])) ?></td>
        </tr>
        <tr>
            <td>Pelanggan</td>
            <td class="right">: <?= htmlspecialchars($transaksi['nama_pelanggan']) ?></td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td class="right">: ADMIN-01</td>
        </tr>
    </table>

    <div class="garis"></div>

    <!-- Daftar Barang yang Dibeli -->
    <table>
        <?php foreach ($items as $item): ?>
            <tr>
                <td colspan="3" class="bold"><?= strtoupper(htmlspecialchars($item['nama_produk'])) ?></td>
            </tr>
            <tr>
                <td><?= $item['jumlah'] ?> x <?= number_format($item['harga'], 0, ',', '.') ?></td>
                <td></td>
                <td class="right"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="garis"></div>

    <!-- Total, Tunai, dan Kembalian -->
    <table>
        <tr>
            <td class="bold">TOTAL BELANJA</td>
            <td class="right bold">Rp <?= number_format($transaksi['total_belanja'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>TUNAI</td>
            <td class="right">Rp <?= number_format($tunai, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td class="bold">KEMBALI</td>
            <td class="right bold">Rp <?= number_format($kembalian, 0, ',', '.') ?></td>
        </tr>
    </table>

    <div class="garis"></div>

    <!-- Barcode Transaksi -->
    <div class="barcode-container">
        <!-- Menggunakan generator barcode publik otomatis berbasis SVG/PNG -->
        <img src="https://barcode.tec-it.com/barcode.ashx?data=<?= $kodeBarangTrx ?>&code=Code128&dpi=96&datamode=Default" alt="Barcode <?= $kodeBarangTrx ?>">
        <div style="font-size: 8px; letter-spacing: 1px; margin-top: 1px;"><?= $kodeBarangTrx ?></div>
    </div>

    <!-- Footer Struk -->
    <div class="center" style="margin-top: 4px;">
        <p style="margin: 1px 0; font-size: 8.5px;">TERIMA KASIH ATAS KUNJUNGAN ANDA</p>
        <p style="margin: 1px 0; font-size: 7.5px;">BARANG YANG DIBELI TIDAK DAPAT DITUKAR</p>
        <p style="margin: 3px 0 0 0; font-size: 7.5px;">*** LAYANAN KONSUMEN: 1500-888 ***</p>
    </div>

</body>
</html>