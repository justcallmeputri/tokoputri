<?php
require_once 'koneksi.php';

// Hapus data lama jika ada
$pdo->exec("DELETE FROM tbl_users");

// Buat hash password yang valid untuk "123456"
$pass_admin = password_hash('123456', PASSWORD_DEFAULT);
$pass_kasir = password_hash('123456', PASSWORD_DEFAULT);
$pass_user  = password_hash('123456', PASSWORD_DEFAULT);

// Masukkan data akun demo
$sql = "INSERT INTO tbl_users (nama_lengkap, username, password, role) VALUES 
        ('Administrator Utama', 'admin', :p1, 'admin'),
        ('Kasir TokoKita', 'kasir', :p2, 'kasir'),
        ('Pengguna Umum', 'pengguna', :p3, 'pengguna')";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':p1' => $pass_admin,
    ':p2' => $pass_kasir,
    ':p3' => $pass_user
]);

echo "<h3>Berhasil! Akun demo berhasil dibuat ke database.</h3>";
echo "<p>Silakan hapus file buat_user.php ini, lalu coba <a href='login.php'>Login disini</a>.</p>";
?>