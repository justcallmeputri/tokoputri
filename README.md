# 🛒 TokoKita PRO - Enterprise Database Operations & POS System

Sistem manajemen penjualan profesional berkecepatan tinggi berbasis **PHP (PDO)** dan **MariaDB/MySQL**, dirancang khusus untuk kebutuhan operasional kasir ritel dengan integrasi *Stored Procedure*, *Trigger*, *Transaction Control Language (TCL)*, *Hardware Barcode Scanner*, dan *Thermal Receipt Printing*.

---

## 🛠️ Bahasa & Teknologi Pemrograman
* **Bahasa Pemrograman**: PHP 8.x (Native dengan ekstensi PDO)
* **Database Management System**: MariaDB / MySQL (mendukung *Stored Procedure*, *Trigger*, *View*, & *TCL*)
* **Frontend Framework**: Bootstrap 5.3 (Responsive Modern UI)
* **Pemrosesan Styling**: CSS3 Custom Variables & Google Fonts (Inter)
* **Peralatan Pendukung**: USB Barcode Scanner (HID Mode) & Thermal Receipt Printer (58mm)

---

## 📂 Struktur Folder Proyek
```text
tokokita/
│
├── koneksi.php              # Konfigurasi koneksi database menggunakan PDO
├── index.html               # Halaman landing / beranda sistem utama
├── dashboard.php            # Dashboard analitik & ringkasan operasional
├── pelanggan.php            # Manajemen data pelanggan (CRUD & Stored Procedure)
├── transaksi.php            # Halaman kasir, katalog produk, barcode, & keranjang
├── laporan.php              # Rekapitulasi laporan penjualan dengan paginasi (20 item)
├── cetak_nota.php           # Modul cetak struk kasir thermal 58mm dengan barcode transaksi
└── cetak_laporan.php        # Modul cetak laporan PDF/Print rekapitulasi omzet
```

---

## ✨ Fitur Utama Sistem
1. **Manajemen Katalog & Barcode Produk (`transaksi.php`)**:
   - Penambahan, pengeditan, dan penghapusan produk langsung dari antarmuka kasir.
   - Integrasi **USB Barcode Scanner** (mode HID) yang otomatis berfungsi sebagai *keyboard input* pada kolom SKU/Barcode.
   - Render gambar barcode otomatis di setiap kartu produk menggunakan generator barcode eksternal.
2. **Transaksi Penjualan & TCL (COMMIT / ROLLBACK)**:
   - Pengelolaan keranjang belanja interaktif dengan tombol tambah/kurang kuantitas secara *real-time*.
   - Validasi stok otomatis dan transaksi aman menggunakan mekanisme *Transaction Control Language* (`BEGIN`, `COMMIT`, `ROLLBACK`) di MariaDB.
3. **Manajemen Pelanggan (`pelanggan.php`)**:
   - Integrasi penuh dengan **Stored Procedure** (`CALL TampilPelanggan()`) untuk performa kueri data pelanggan yang optimal.
4. **Laporan & Paginasi (`laporan.php`)**:
   - Menampilkan rekapitulasi omzet keseluruhan dan rincian transaksi dengan sistem **Pagination** (maksimal 20 data per halaman).
5. **Cetak Struk Thermal (`cetak_nota.php`)**:
   - Format nota ukuran mini 58mm yang dilengkapi barcode ID transaksi unik di bagian bawah struk.

---

## 🚀 Panduan Instalasi & Menjalankan (Lokal XAMPP)
1. **Clone / Pindahkan Proyek**:
   - Simpan seluruh folder proyek `tokokita` ke dalam direktori server lokal `C:\xampp\htdocs\tokokita\`.
2. **Aktifkan Server Lokal**:
   - Buka aplikasi **XAMPP Control Panel**, lalu aktifkan modul **Apache** dan **MySQL**.
3. **Impor Database**:
   - Buka browser dan akses `http://localhost/phpmyadmin/`.
   - Buat database baru dengan nama `db_tokokita`.
   - Impor file SQL proyek yang berisi tabel, *Stored Procedure*, *View*, dan *Trigger*.
4. **Jalankan Aplikasi**:
   - Buka browser dan ketikkan alamat:  
     `http://localhost/tokokita/index.html` atau langsung ke `http://localhost/tokokita/transaksi.php`