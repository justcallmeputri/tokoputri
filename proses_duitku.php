<?php
require_once 'cek_akses.php';
wajibLogin(['admin', 'kasir']);
require_once 'koneksi.php';

// GANTI DENGAN KREDENSIAL SANDBOX DUITKU ANDA
$merchantCode = 'DS38200'; 
$apiKey       = 'a9ac5646e0d81ee4c2c1c036ed92fad6';      

$id_pelanggan    = $_POST['id_pelanggan'] ?? 1;
$customerVaName  = $_POST['nama_pelanggan'] ?? 'Pelanggan TokoKita';
$paymentAmount   = (int) ($_POST['total_belanja_hidden'] ?? 0);
$paymentMethod   = $_POST['channel_duitku'] ?? 'SP';
$items           = json_decode($_POST['keranjang_json'] ?? '[]', true);
$merchantOrderId = 'INV-' . time();
$tanggal         = date('Y-m-d');

if ($paymentAmount <= 0 || empty($items)) {
    echo "<script>alert('Keranjang belanja kosong!'); window.location='transaksi.php';</script>";
    exit();
}

$callbackUrl = 'https://tokokita.com/callback.php';
$returnUrl = 'http://localhost/tokokita/transaksi.php?status=duitku_selesai';
$expiryPeriod = 60;
$signature    = md5($merchantCode . $merchantOrderId . $paymentAmount . $apiKey);

$params = [
    'merchantCode'    => $merchantCode,
    'paymentAmount'   => $paymentAmount,
    'paymentMethod'   => $paymentMethod,
    'merchantOrderId' => $merchantOrderId,
    'productDetails'  => 'Belanja TokoKita (' . count($items) . ' item)',
    'customerVaName'  => $customerVaName,
    'email'           => 'kasir@tokokita.com',
    'callbackUrl'     => $callbackUrl,
    'returnUrl'       => $returnUrl,
    'signature'       => $signature,
    'expiryPeriod'    => $expiryPeriod
];

$params_string = json_encode($params);
$url = 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($params_string)
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$request  = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $result = json_decode($request, true);
    if (isset($result['paymentUrl'])) {
        // Opsional: Simpan transaksi ke database sebagai PENDING & kurangi stok
        try {
            $pdo->beginTransaction();
            $stmtTrx = $pdo->prepare("INSERT INTO tbl_transaksi (tanggal, id_pelanggan, total_belanja, metode_pembayaran, status_pembayaran, duitku_reference) VALUES (?, ?, ?, ?, 'PENDING', ?)");
            $stmtTrx->execute([$tanggal, $id_pelanggan, $paymentAmount, 'DUITKU-' . $paymentMethod, $merchantOrderId]);
            $id_trx = $pdo->lastInsertId();

            $stmtDetail = $pdo->prepare("INSERT INTO tbl_detail_transaksi (id_transaksi, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)");
            $stmtStok   = $pdo->prepare("UPDATE tbl_produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?");

            foreach ($items as $item) {
                $stmtDetail->execute([$id_trx, $item['id_produk'], $item['qty'], $item['subtotal']]);
                $stmtStok->execute([$item['qty'], $item['id_produk'], $item['qty']]);
                if ($stmtStok->rowCount() === 0) {
                    throw new Exception("Stok tidak cukup untuk " . $item['nama_produk']);
                }
            }
            $pdo->commit();

            // Redirect ke halaman pembayaran Duitku (QRIS / Virtual Account)
            header('Location: ' . $result['paymentUrl']);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<script>alert('Gagal memproses stok: " . addslashes($e->getMessage()) . "'); window.location='transaksi.php';</script>";
        }
    } else {
        echo "Error Duitku: " . ($result['Message'] ?? 'Gagal mendapatkan URL pembayaran');
    }
} else {
    echo "Gagal menghubungi server Duitku (HTTP $httpCode): " . $request;
}
?>