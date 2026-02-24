# ✈️ TRAVEL-FINANCE PRO

**TRAVEL-FINANCE PRO** adalah sistem manajemen keuangan berbasis web yang dirancang khusus untuk agen perjalanan (travel agency). Aplikasi ini melacak penjualan tiket, pemesanan hotel, paket tur, dan biaya operasional dengan antarmuka modern yang dibangun menggunakan PHP Native dan Tailwind CSS.

---

## 🚀 Panduan Cepat untuk AI Agent / Developer

Untuk melanjutkan pengembangan proyek ini, harap perhatikan logika inti dan arsitektur berikut:

### 1. Tech Stack & Infrastructure
- **Backend**: PHP 7.4+ Native dengan MySQLi/PDO (PDO digunakan dalam proyek ini).
- **Frontend**: Tailwind CSS (via CDN) & Vanilla JavaScript.
- **Database**: MySQL 8.0.
- **Visualization**: Chart.js untuk grafik finansial.

### 2. Docker Environment
Proyek ini dikonfigurasi menggunakan **Docker Compose** dengan layanan berikut:
- **Web Server (PHP-Apache)**: Diakses di port `8080`.
- **Database (MySQL 8.0)**:
  - Container Name: `mysql8`
  - Host: `mysql` (dalam network docker)
  - Port: `3306`
  - Default DB: `tfinance`
  - Credentials: `root` / `root` (Root: `root`)
- **Tools**:
  - **phpMyAdmin**: Tersedia di port `8081` untuk manajemen database visual.

### 3. Instalasi & Lingkungan
- **Instalasi Otomatis**: Jalankan `install.php` melalui browser (`localhost:8080/install.php`) untuk setup tabel dan akun admin.
- **Izin Folder (Permissions)**: Jalankan perintah berikut agar container web dapat menulis file konfigurasi:
  ```bash
  sudo chown -R www-data:www-data src/
  sudo chmod -R 777 src/tfinance/config src/tfinance/assets
  ```
- **System Guard**: Jika `config/db.php` belum ada atau database belum di-setup, sistem akan otomatis redirect ke installer.

---

## 🛠 Fitur Utama

- **Dashboard Intelligence**: Ringkasan Pendapatan, HPP (COGS), Laba Kotor, dan Pembayaran Tertunda dengan grafik tren interaktif.
- **Customer Relationship Management (CRM)**: 
  - Manajemen data pelanggan terpusat (Nama, Telepon, Email, Alamat).
  - Fitur *Auto-save*: Sistem otomatis menyimpan data pelanggan baru saat penginputan invoice.
- **Partial Payment System (DP & Remainder)**:
  - Mendukung pembayaran bertahap: **DP (Down Payment)** dan **Pelunasan (Remainder)**.
  - **Auto-Linking**: Menghubungkan invoice pelunasan dengan invoice DP sebelumnya.
  - **Insta-Calc**: Sistem otomatis menghitung sisa tagihan berdasarkan nilai kontrak dan nilai DP yang dipilih.
  - **Smart Selection**: Hanya menampilkan daftar DP yang belum lunas dalam pilihan pelunasan.
- **Transaction Engine**: 
  - Penanganan dinamis Pendapatan (Sales) dan Beban (Expense).
  - Baris item dinamis menggunakan Vanilla JS tanpa reload halaman.
- **Multi-Level Permissions**:
  - **Superadmin**: Akses penuh termasuk manajemen akun user dan pengaturan sistem.
  - **Admin**: Akses operasional untuk transaksi, pelanggan, dan laporan.
- **Internal Audit Trail**: Mencatat admin pembuat di setiap transaksi (Invoice/Expense) sebagai catatan internal tanpa mengganggu tampilan cetak pelanggan.
- **Sleek UI/UX (Modernization)**:
  - **Kebab Action Menu**: Merapikan daftar transaksi dengan tombol burger tiga titik yang simpel.
  - **Professional Typography**: Optimasi ukuran font dan tata letak untuk tampilan yang lebih padat dan profesional (*Professional Compact View*).
  - **Audit Information**: Menampilkan nama pembuat transaksi langsung di tabel list untuk memudahkan koordinasi tim.
- **Reporting & Printing**: 
  - Mesin cetak (print) untuk Invoice dan Voucher profesional.
  - Rincian breakdown Pembayaran (Nilai Kontrak, DP Terbayar, Sisa Pelunasan) otomatis tercetak.
- **Refund & Reversal System**:
  - Modul khusus untuk menangani pengembalian dana.
  - **Invoice Linking**: Menghubungkan refund dengan invoice asal untuk pengisian data otomatis.
  - **Security Lock**: Mengunci nama customer pada transaksi refund yang terhubung ke invoice untuk validasi data.
  - **Dashboard Integration**: Statistik pengembalian dana tercatat secara terpisah dan memotong laba bersih.

---

## 🛠 Struktur Proyek

```text
/tfinance
├── config/              # Konfigurasi (db.php dibuat otomatis di sini)
├── includes/            # Komponen global (header, footer, sidebar, fungsi inti)
├── sql/                 # Definisi skema database (DDL)
├── index.php            # Dashboard dengan integrasi Chart.js
├── login.php            # Gerbang login
├── customers.php        # Manajemen Database Pelanggan
├── transactions.php     # Mesin Utama Transaksi & Logika DP/Pelunasan
├── update_status.php    # Handler cepat update status bayar (PAID/CANCEL/PENDING)
├── settings.php         # Profil Perusahaan & Sinkronisasi Skema DB
├── print.php            # Layout mesin cetak dengan breakdown pembayaran
└── logout.php           # Handler sesi keluar
```

---

## 📝 Perubahan Arsitektur Terbaru

1. **Integrated CRM**: Pemisahan data pelanggan ke tabel `customers` namun tetap terintegrasi erat dengan alur transaksi.
2. **Logic DP-Linkage**: Implementasi kolom `dp_id` dan `contract_amount` untuk mendukung pelacakan cicilan pembayaran.
3. **User Level Architecture**: Penambahan role-based access control (RBAC) dengan kategori Superadmin dan Admin.
4. **Creator Tracking**: Penambahan foreign key `created_by` pada tabel transaksi untuk sistem audit internal.
5. **Full Integrated Ecosystem**: Seluruh fitur (Refund, DP/Termin, CRM) telah disatukan dalam satu skema database (`sql/schema.sql`) yang bersih untuk kemudahan deployment awal.
6. **UI Compact Overhaul**: Penyesuaian `html body { font-size: 14px }` dan penggunaan dropdown menu untuk aksi tabel demi estetika dashboard yang lebih bersih.
7. **PHP 8.1 Compatibility**: Perbaikan penanganan nilai null pada fungsi `htmlspecialchars()` guna mencegah *Deprecation Warnings* pada server modern.

---

## 📊 Ringkasan Skema Database

Tabel utama meliputi:
- `users`: Data autentikasi.
- `customers`: Database profil pelanggan.
- `transactions`: Header transaksi dengan dukungan `payment_type` (ENUM) dan `dp_id`.
- `transaction_items`: Item baris detail transaksi.
- `settings`: Profil perusahaan untuk kop surat invoice.

---

## 💡 Catatan Pengembang
- **Keamanan**: Selalu panggil `requireLogin()` di bagian atas halaman baru.
- **Status Transaksi**: 
  - `PAID`: Lunas.
  - `PENDING`: Menunggu pembayaran.
  - `CANCELLED`: Dibatalkan (disertai alasan pembatalan).

**Admin Username:** `admin` (Default awal)
