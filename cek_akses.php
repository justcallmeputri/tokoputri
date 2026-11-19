<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function wajibLogin($allowed_roles = []) {
    // Cek apakah user sudah login atau belum
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: login.php?pesan=belum_login");
        exit();
    }

    // Jika role dibatasi, cek apakah role user saat ini diizinkan
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: login.php?pesan=akses_ditolak");
        exit();
    }
}